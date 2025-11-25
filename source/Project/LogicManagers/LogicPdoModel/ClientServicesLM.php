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
                'le.bank_account as bank_account',
                'le.inn as inn',
                'le.company_name as company_name',
                'le.id as le_id',
                'ba.balance as balance',
                'd.amount as debit_amount',
            ])
            ->from('client_services')
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_id = supplier_id',
                'le.client_services =' . 1,
                'le.client_service_id = id',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = le.id',
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
            ->groupBy("client_services.id, u.id, le.id",);


        $clients_array = [];
        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }

        foreach ($clients as $client) {
            $existing_index = array_search($client->id, array_column($clients_array, 'id'));
            $balance_sum = $client->balance ?? 0;
            $debit_amount_sum = $client->debit_amount ?? 0;
            $bank_accounts = null;
            $suppler = SuppliersLM::getSuppliersId($client->supplier_id);

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
                    'suppler_name' => $suppler->username ?? '',
                    'suppler_email' => $suppler->email ?? '',
                    'balance_sum' => $balance_sum,
                    'debit_amount_sum' => $debit_amount_sum,
                    'user_id' => $client->user_id,
                    'created_at' => $client->created_at,
                    'bank_accounts' => $bank_accounts ? [$bank_accounts] : [],
                ];
            } else {
                $clients_array[$existing_index]['balance_sum'] += $balance_sum;
                $clients_array[$existing_index]['debit_amount_sum'] += $debit_amount_sum;

                if ($bank_accounts) {
                    $clients_array[$existing_index]['bank_accounts'][] = $bank_accounts;
                }
            }
        }


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
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_id = supplier_id',
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
}