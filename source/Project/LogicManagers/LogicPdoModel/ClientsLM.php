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
            ->select(['COUNT(clients.id) as count']);

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
            ]);


        $clients_array = [];
        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }

        foreach ($clients as $client) {
            $existing_index = array_search($client->id, array_column($clients_array, 'id'));
            $balance_sum = $client->balance ?? 0;
            $debit_amount_sum = $client->debit_amount ?? 0;
            $final_remainder = $client->final_remainder ?? 0;
            $bank_accounts = null;

            if ($client->bank_account ?? false) {
                $bank_accounts = [
                    'account' => $client->bank_account,
                    'inn' => $client->inn,
                    'company_name' => $client->company_name,
                    'balance' => $balance_sum,
                    'debit_amount' => $debit_amount_sum,
                    'le_id' => $client->le_id,
                ];
            }

            if ($existing_index === false) {
                $clients_array[] = [
                    'id' => $client->id,
                    'email' => $client->email,
                    'role' => $client->role,
                    'name' => $client->username,
                    'percentage' => $client->percentage,
                    'balance_sum' => $balance_sum,
                    'debit_amount_sum' => $debit_amount_sum,
                    'final_remainder_sum' => $final_remainder,
                    'user_id' => $client->user_id,
                    'created_at' => $client->created_at,
                    'bank_accounts' => $bank_accounts ? [$bank_accounts] : [],
                ];
            } else {
                $clients_array[$existing_index]['balance_sum'] += $balance_sum;
                $clients_array[$existing_index]['debit_amount_sum'] += $debit_amount_sum;
                $clients_array[$existing_index]['final_remainder_sum'] += $final_remainder;

                if ($bank_accounts) {
                    $clients_array[$existing_index]['bank_accounts'][] = $bank_accounts;
                }
            }
        }


        Logger::log(print_r($clients_array, true), 'clients_array');

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

    public static function clientIdDelete(int $client_id): array
    {
        $builder = Clients::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $client_id,
            ]);


        return PdoConnector::execute($builder);
    }
}