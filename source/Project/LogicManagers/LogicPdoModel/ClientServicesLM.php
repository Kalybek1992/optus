<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\Clients;
use Source\Project\Models\ClientServices;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Suppliers;
use Source\Project\Models\Users;


/**
 *
 */
class ClientServicesLM
{


    public static function insertNewClients(array $data)
    {
        $builder = ClientServices::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getClientsServicesCount()
    {
        $builder = ClientServices::newQueryBuilder()
            ->select(['COUNT(client_services.id) as count']);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function clientServicessDelete(int $id)
    {
        $builder = ClientServices::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getClientsServices(int $offset = 0, int $limit = 8): array
    {

        $builder = ClientServices::newQueryBuilder()
            ->select([
                '*',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
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


        $builder = ClientServices::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
                'le.inn as inn',
                'le.company_name as company_name',
                'le.id as le_id',
                'd.amount as debit_amount',
            ])
            ->from('client_services')
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_id = supplier_id',
                'le.client_services =' . 1,
                'le.client_service_id = id',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.from_account_id = le.id',
                "d.type_of_debt =" . "'client_services'",
                "d.status =" . "'active'",
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                "user_id IN($selects)"
            ])
            ->groupBy("client_services.id, u.id, le.id, d.amount");


        $clients_array = [];
        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }


        foreach ($clients as $client) {
            $client_id = $client->id;
            $suppler = SuppliersLM::getSuppliersId($client->supplier_id);

            if (!isset($clients_array[$client_id])) {
                $clients_array[$client_id] = [
                    'id' => $client->id,
                    'email' => $client->email,
                    'role' => $client->role,
                    'name' => $client->username,
                    'suppler_name' => $suppler->username ?? '',
                    'suppler_email' => $suppler->email ?? '',
                    'balance_sum' => 0.0,
                    'debit_amount_sum' => 0.0,
                    'user_id' => $client->user_id,
                    'created_at' => $client->created_at,
                    'bank_accounts' => [],
                ];
            }

            $balance_sum = (float)($client->balance ?? 0);
            $debit_amount_sum = (float)($client->debit_amount ?? 0);

            $clients_array[$client_id]['balance_sum'] += $balance_sum;
            $clients_array[$client_id]['debit_amount_sum'] += $debit_amount_sum;

            $account_raw = $client->inn ?? null;
            $account = is_null($account_raw) ? '' : trim((string)$account_raw);

            if ($account !== '') {
                if (!isset($clients_array[$client_id]['bank_accounts'][$account])) {
                    $clients_array[$client_id]['bank_accounts'][$account] = [
                        'account' => $account,
                        'inn' => $client->inn ?? null,
                        'company_name' => $client->company_name ?? null,
                        'balance' => $balance_sum,
                        'debit_amount' => $debit_amount_sum,
                        'le_id' => $client->le_id ?? null,
                    ];
                } else {
                    $clients_array[$client_id]['bank_accounts'][$account]['balance'] += $balance_sum;
                    $clients_array[$client_id]['bank_accounts'][$account]['debit_amount'] += $debit_amount_sum;

                    if (empty($clients_array[$client_id]['bank_accounts'][$account]['inn']) && !empty($client->inn)) {
                        $clients_array[$client_id]['bank_accounts'][$account]['inn'] = $client->inn;
                    }
                    if (empty($clients_array[$client_id]['bank_accounts'][$account]['company_name']) && !empty($client->company_name)) {
                        $clients_array[$client_id]['bank_accounts'][$account]['company_name'] = $client->company_name;
                    }
                    if (empty($clients_array[$client_id]['bank_accounts'][$account]['le_id']) && !empty($client->le_id)) {
                        $clients_array[$client_id]['bank_accounts'][$account]['le_id'] = $client->le_id;
                    }
                }
            }
        }

        $clients_array = array_values(array_map(function ($c) {
            $c['bank_accounts'] = array_values($c['bank_accounts']);
            return $c;
        }, $clients_array));


        return $clients_array;
    }

    public static function clientServicesId(int $id): ?array
    {
        $builder = ClientServices::newQueryBuilder()
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
                'le.client_service_id = id',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.from_account_id = le.id',
                "d.type_of_debt =" . "'client_services'",
                "d.status =" . "'active'",
            ])
            ->where([
                'id =' . $id,
            ])
            ->groupBy('client_services.id')
            ->limit(1);

        $client = PdoConnector::execute($builder)[0] ?? [];

        if (!$client) {
            return null;
        }

        $client_arr = [
            'id' => $client->id,
            'supplier_id' => $client->supplier_id,
            'email' => $client->email,
            'username' => $client->username,
            'debit_amount' => $client->debit_amount,
            'legal_id' => $client->legal_id,
        ];


        return $client_arr;
    }

    public static function supplierClientServicessAllDelete(int $supplier_id)
    {
        $builder = ClientServices::newQueryBuilder()
            ->delete()
            ->where([
                'supplier_id =' . $supplier_id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getClientServicesIdLegalOff(int $client_services_id): ?array
    {
        $builder = ClientServices::newQueryBuilder()
            ->select([
                '*',
                'GROUP_CONCAT(le.id SEPARATOR ' . '", "' . ') as legal_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_id = id',
            ])
            ->where([
                'id =' . $client_services_id,
            ])
            ->groupBy('client_services.id')
            ->limit(1);

        $client = PdoConnector::execute($builder)[0] ?? [];

        if (!$client) {
            return null;
        }

        return [
            'id' => $client->id,
            'legal_id' => $client->legal_id ?? null,
        ];
    }

    public static function getClientServicesDebitCompany($id = null): array
    {
        $builder = ClientServices::newQueryBuilder()
            ->select([
                'cs.id as client_id',
                'u.name as username',
                'u.role as role',
                'SUM(d_company.amount) as company_debit_amount',
                'SUM(d_client.amount) as debit_amount',
            ])
            ->from('client_services as cs')
            ->innerJoin('users u')
            ->on([
                'u.id = cs.user_id',
            ])
            ->innerJoin('legal_entities le')
            ->on([
                'le.client_service_id = cs.id',
            ])
            ->innerJoin('debts d_company')
            ->on([
                'd_company.to_account_id = le.id',
                'd_company.type_of_debt = "client_services_debt"',
                'd_company.status = "active"',
            ])
            ->innerJoin('debts d_client')
            ->on([
                'd_client.from_account_id = le.id',
                'd_client.type_of_debt = "client_services"',
                'd_client.status = "active"',
            ]);

        if ($id) {
            $builder
                ->where([
                    'cs.id =' . $id,
                ]);
        }

        $builder
            ->groupBy("le.id");

        $clients = PdoConnector::execute($builder);
        $clients_array = [];

        foreach ($clients as $client) {
            $clients_array[] = [
                'id' => $client->client_id,
                'username' => $client->username,
                'role' => $client->role,
                'company_debit_amount' => $client->company_debit_amount,
                'debit_amount' => $client->debit_amount,
            ];
        }

        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $clients_array;
    }
}