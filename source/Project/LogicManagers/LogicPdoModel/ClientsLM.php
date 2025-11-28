<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\Clients;


/**
 *
 */
class ClientsLM
{


    public static function insertNewClients(array $data)
    {
        $builder = Clients::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getClientsCount()
    {
        $builder = Clients::newQueryBuilder()
            ->select(['COUNT(clients.id) as count'])
            ->where([
                "supplier_id IS NULL",
            ]);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getClients(int $offset = 0, int $limit = 8): array
    {

        $builder = Clients::newQueryBuilder()
            ->select([
                '*',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                "supplier_id IS NULL",
            ])
            ->orderBy('u.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }

        $selects = '';

        foreach ($clients as $key => $client) {
            if ($clients[$key + 1] ?? false) {
                $selects .= "$client->user_id, ";
            } else {
                $selects .= "$client->user_id";
            }
        }


        $builder = Clients::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
                'le.bank_account as bank_account',
                'le.inn as inn',
                'le.company_name as company_name',
                'le.id as le_id',
                'le.final_remainder as final_remainder',
                'ba.balance as balance',
                'd.amount as debit_amount',
            ])
            ->from('clients')
            ->leftJoin('legal_entities le')
            ->on([
                'le.client_id = id',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = le.id',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.from_account_id = le.id',
                "d.type_of_debt =" . "'client_goods'",
                "d.status =" . "'active'",
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                "user_id IN($selects)"
            ])->groupBy("clients.id, u.id, le.id, d.amount",);


        $clients_array = [];
        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }

        foreach ($clients as $client) {
            $client_id = $client->id;
            if (!isset($clients_array[$client_id])) {
                $clients_array[$client_id] = [
                    'id' => $client->id,
                    'email' => $client->email,
                    'role' => $client->role,
                    'name' => $client->username,
                    'percentage' => $client->percentage,
                    'balance_sum' => 0,
                    'debit_amount_sum' => 0,
                    'final_remainder_sum' => 0,
                    'user_id' => $client->user_id,
                    'created_at' => $client->created_at,
                    'bank_accounts' => [],
                ];
            }

            $clients_array[$client_id]['balance_sum'] += (float) ($client->balance ?? 0);
            $clients_array[$client_id]['debit_amount_sum'] += (float) ($client->debit_amount ?? 0);
            $clients_array[$client_id]['final_remainder_sum'] += (float) ($client->final_remainder ?? 0);

            $accountRaw = $client->bank_account ?? null;
            $account = is_null($accountRaw) ? '' : trim((string) $accountRaw);

            if ($account !== '') {
                $key = $account;
                if (!isset($clients_array[$client_id]['bank_accounts'][$key])) {
                    $clients_array[$client_id]['bank_accounts'][$key] = [
                        'account' => $account,
                        'inn' => $client->inn ?? null,
                        'company_name' => $client->company_name ?? null,
                        'balance' => (float) ($client->balance ?? 0),
                        'debit_amount' => (float) ($client->debit_amount ?? 0),
                        'le_id' => $client->le_id ?? null,
                    ];
                } else {
                    $clients_array[$client_id]['bank_accounts'][$key]['balance'] += (float) ($client->balance ?? 0);
                    $clients_array[$client_id]['bank_accounts'][$key]['debit_amount'] += (float) ($client->debit_amount ?? 0);

                    if (empty($clients_array[$client_id]['bank_accounts'][$key]['inn']) && !empty($client->inn)) {
                        $clients_array[$client_id]['bank_accounts'][$key]['inn'] = $client->inn;
                    }
                    if (empty($clients_array[$client_id]['bank_accounts'][$key]['company_name']) && !empty($client->company_name)) {
                        $clients_array[$client_id]['bank_accounts'][$key]['company_name'] = $client->company_name;
                    }
                    if (empty($clients_array[$client_id]['bank_accounts'][$key]['le_id']) && !empty($client->le_id)) {
                        $clients_array[$client_id]['bank_accounts'][$key]['le_id'] = $client->le_id;
                    }
                }
            }
        }

        $clients_array = array_values(array_map(function ($client) {
            $client['bank_accounts'] = array_values($client['bank_accounts']);
            return $client;
        }, $clients_array));



        //Logger::log(print_r($clients_array, true), 'clients_array');

        return $clients_array;
    }

    public static function getClientId(int $id): ?array
    {
        $builder = Clients::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.email as email',
                'SUM(d.amount) as debit_amount',
                'GROUP_CONCAT(le.id SEPARATOR ' . '", "' . ') as legal_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.client_id = id',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.from_account_id = le.id',
                "d.type_of_debt =" . "'client_goods'",
                "d.status =" . "'active'",
            ])
            ->where([
                'id =' . $id,
            ])
            ->groupBy('clients.id')
            ->limit(1);

        $client = PdoConnector::execute($builder)[0] ?? [];

        if (!$client) {
            return null;
        }


        $client_arr = [
            'id' => $client->id,
            'email' => $client->email,
            'username' => $client->username,
            'percentage' => $client->percentage,
            'debit_amount' => $client->debit_amount,
            'legal_id' => $client->legal_id,
        ];


        return $client_arr;
    }


    /**
     * Получить простой список клиентов для форм (id, company_name)
     */
    public static function getClientsAll($supplier_id = null): array
    {
        $builder = Clients::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.id as user_id',
                'u.created_at as created_at',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ]);

        if ($supplier_id){
            $builder
                ->where([
                'supplier_id =' . $supplier_id,
            ]);
        }else{
            $builder
                ->where([
                    "supplier_id IS NULL",
                ]);
        }

        $builder
            ->orderBy('u.created_at', 'DESC');


        $clients_array = [];
        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }

        foreach ($clients as $client) {

            $clients_array[] = [
                'id' => $client->id,
                'email' => $client->email,
                'role' => $client->role,
                'name' => $client->username,
                'balance_sum' => $client->current_balance,
                'user_id' => $client->user_id,
                'created_at' => $client->created_at,

            ];
        }


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $clients_array;
    }


    public static function getClientSupplierId(int $id, int $supplier_id): ?array
    {

        $builder = Clients::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'SUM(d.amount) as debit_amount',
                'GROUP_CONCAT(le.id SEPARATOR ' . '", "' . ') as legal_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_client_id = id',
                'le.supplier_id =' . $supplier_id,
            ])
            ->leftJoin('debts d')
            ->on([
                'd.from_account_id = le.id',
                "d.type_of_debt =" . "'сlient_debt_supplier'",
                "d.status =" . "'active'",
            ])
            ->where([
                'id =' . $id,
            ])
            ->groupBy('clients.id')
            ->limit(1);

        $client = PdoConnector::execute($builder)[0] ?? [];

        if (!$client) {
            return null;
        }


        return [
            'id' => $client->id,
            'username' => $client->username,
            'percentage' => $client->percentage,
            'debit_amount' => $client->debit_amount,
            'legal_id' => $client->legal_id,
        ];
    }

    public static function supplierClientsAllDelete(int $supplier_id)
    {
        $builder = Clients::newQueryBuilder()
            ->delete()
            ->where([
                'supplier_id =' . $supplier_id,
            ]);

        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return PdoConnector::execute($builder);
    }

    public static function clientIdDelete(int $client_id)
    {
        $builder = Clients::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $client_id,
            ]);


        return PdoConnector::execute($builder);
    }
}