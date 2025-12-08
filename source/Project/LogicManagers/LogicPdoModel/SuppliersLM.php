<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\Clients;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Managers;
use Source\Project\Models\StockBalances;
use Source\Project\Models\Suppliers;
use Source\Project\Models\Users;


/**
 *
 */
class SuppliersLM
{

    public static function insertNewSuppliers(array $data)
    {
        $builder = Suppliers::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getSuppliers(int $offset = 0, int $limit = 4): array
    {
        $builder = Suppliers::newQueryBuilder()
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

        $suppliers = PdoConnector::execute($builder);
        $selects = '';

        if (!$suppliers) {
            return [];
        }


        foreach ($suppliers as $key => $supplier) {
            if ($suppliers[$key + 1] ?? false) {
                $selects .= "$supplier->user_id, ";
            } else {
                $selects .= "$supplier->user_id";
            }
        }

        $builder = Suppliers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.restricted_access as restricted_access',
                'u.created_at as created_at',
                'le.inn as inn',
                'le.company_name as company_name',
                'le.id as le_id',
                'd.amount as debit_amount',
            ])
            ->from('suppliers')
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_id = id',
                'le.client_services =' . 0,
            ])
            ->leftJoin('debts d')
            ->on([
                'd.to_account_id = le.id',
                "d.type_of_debt =" . "'supplier_goods'",
                "d.status =" . "'active'",
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                "user_id IN($selects)"
            ])
            ->groupBy("suppliers.id, u.id, le.id, d.amount");


        $suppliers_array = [];
        $suppliers = PdoConnector::execute($builder);


        if (!$suppliers) {
            return [];
        }

        foreach ($suppliers as $supplier) {
            $supplier_id = $supplier->id;

            if (!isset($suppliers_array[$supplier_id])) {
                $decoded_password = openssl_decrypt(
                    base64_decode($supplier->password),
                    Config::METHOD,
                    Config::ENCRYPTION
                );

                $suppliers_array[$supplier_id] = [
                    'id' => $supplier->id,
                    'email' => $supplier->email,
                    'role' => $supplier->role,
                    'name' => $supplier->username,
                    'password' => $decoded_password,
                    'percentage' => $supplier->percentage,
                    'restricted_access' => $supplier->restricted_access,
                    'balance_sum' => 0.0,
                    'debit_amount_sum' => 0.0,
                    'user_id' => $supplier->user_id,
                    'created_at' => $supplier->created_at,
                    'bank_accounts' => [],
                ];
            }

            $balance_sum = (float) ($supplier->balance ?? 0);
            $debit_amount_sum = (float) ($supplier->debit_amount ?? 0);

            $suppliers_array[$supplier_id]['balance_sum'] += $balance_sum;
            $suppliers_array[$supplier_id]['debit_amount_sum'] += $debit_amount_sum;

            $account_raw = $supplier->inn ?? null;
            $account = is_null($account_raw) ? '' : trim((string) $account_raw);

            if ($account !== '') {
                if (!isset($suppliers_array[$supplier_id]['bank_accounts'][$account])) {
                    $suppliers_array[$supplier_id]['bank_accounts'][$account] = [
                        'account' => $account,
                        'inn' => $supplier->inn ?? null,
                        'company_name' => $supplier->company_name ?? null,
                        'debit_amount' => $debit_amount_sum,
                        'balance' => $balance_sum,
                        'le_id' => $supplier->le_id ?? null,
                    ];
                } else {
                    $suppliers_array[$supplier_id]['bank_accounts'][$account]['debit_amount'] += $debit_amount_sum;

                    if (empty($suppliers_array[$supplier_id]['bank_accounts'][$account]['inn']) && !empty($supplier->inn)) {
                        $suppliers_array[$supplier_id]['bank_accounts'][$account]['inn'] = $supplier->inn;
                    }
                    if (empty($suppliers_array[$supplier_id]['bank_accounts'][$account]['company_name']) && !empty($supplier->company_name)) {
                        $suppliers_array[$supplier_id]['bank_accounts'][$account]['company_name'] = $supplier->company_name;
                    }
                    if (empty($suppliers_array[$supplier_id]['bank_accounts'][$account]['le_id']) && !empty($supplier->le_id)) {
                        $suppliers_array[$supplier_id]['bank_accounts'][$account]['le_id'] = $supplier->le_id;
                    }
                }
            }
        }

        $suppliers_array = array_values(array_map(function ($s) {
            $s['bank_accounts'] = array_values($s['bank_accounts']);
            return $s;
        }, $suppliers_array));

        //Logger::log(print_r($suppliers_array, true), 'clients_array');

        return $suppliers_array;
    }

    public static function getSuppliersCount()
    {
        $builder = Suppliers::newQueryBuilder()
            ->select(['COUNT(suppliers.id) as count']);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getSuppliersId(int $id)
    {
        $builder = Suppliers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.email as email',
                'u_client_services.name as client_services_name',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('client_services cs')
            ->on([
                'cs.supplier_id = id',
            ])
            ->leftJoin('users u_client_services')
            ->on([
                'u_client_services.id = cs.user_id',
            ])
            ->where([
                'id =' . $id,
            ])
            ->limit(1);


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getSuppliersAll(): array
    {
        $builder = Suppliers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.email as email',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ]);

        $suppliers = PdoConnector::execute($builder);
        $suppliers_array = [];

        if (!$suppliers) {
            return [];
        }

        foreach ($suppliers as $supplier) {
            $suppliers_array[] = [
                'supplier_id' => $supplier->id,
                'username' => $supplier->username,
                'email' => $supplier->email,
            ];
        }

        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $suppliers_array;
    }

    public static function getSuppliersIdDebt(int $id): ?array
    {

        $builder = Suppliers::newQueryBuilder()
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
                'le.supplier_id = id',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.to_account_id = le.id',
                "d.type_of_debt =" . "'supplier_goods'",
                "d.status =" . "'active'",
            ])
            ->where([
                'id =' . $id,
            ])
            ->groupBy('suppliers.id')
            ->limit(1);

        $supplier = PdoConnector::execute($builder)[0] ?? [];

        if (!$supplier) {
            return null;
        }

        $client_arr = [
            'id' => $supplier->id,
            'email' => $supplier->email,
            'username' => $supplier->username,
            'percentage' => $supplier->percentage,
            'debit_amount' => $supplier->debit_amount ?? 0,
        ];


        return $client_arr;
    }

    public static function updateSuppliers(array $data, int $id)
    {
        $builder = Suppliers::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getSupplierIdLegal(int $supplier_id): ?array
    {
        $builder = Suppliers::newQueryBuilder()
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
                'le.supplier_id = id',
                'le.client_services = 0',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.to_account_id = le.id',
                "d.type_of_debt =" . "'supplier_goods'",
                "d.status =" . "'active'",
            ])
            ->where([
                'id =' . $supplier_id,
            ])
            ->groupBy('suppliers.id')
            ->limit(1);

        $supplier = PdoConnector::execute($builder)[0] ?? [];

        if (!$supplier) {
            return null;
        }

        return [
            'id' => $supplier->id,
            'username' => $supplier->username,
            'percentage' => $supplier->percentage,
            'debit_amount' => $supplier->debit_amount ?? 0,
            'legal_id' => $supplier->legal_id,
            'stock_balance' => $supplier->stock_balance ?? 0,
        ];
    }

    public static function getSupplierIdLegalOff(int $supplier_id): ?array
    {
        $builder = Suppliers::newQueryBuilder()
            ->select([
                '*',
                'GROUP_CONCAT(le.id SEPARATOR ' . '", "' . ') as legal_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.supplier_id = id',
            ])
            ->where([
                'id =' . $supplier_id,
            ])
            ->groupBy('suppliers.id')
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

    public static function supplierIdDelete(int $supplier_id)
    {
        $builder = Clients::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $supplier_id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getSuppliersUsers($role, int $supplier_id, int $offset = 0, int $limit = 4): array
    {
        if ($role == 'manager') {
            $builder = Managers::newQueryBuilder();
        }

        if ($role == 'client') {
            $builder = Clients::newQueryBuilder();
        }

        $builder
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.id as user_id',
                'u.created_at as created_at',
                'le.inn as inn',
                'le.company_name as company_name',
                'le.id as le_id',
                'd.amount as debit_amount',
            ]);

        if ($role == 'manager') {
            $builder
                ->leftJoin('legal_entities le')
                ->on([
                    'le.client_services =' . 1,
                    'le.manager_id = id',
                ]);
        }

        if ($role == 'client') {
            $builder
                ->leftJoin('legal_entities le')
                ->on([
                    'le.client_services =' . 1,
                    'le.supplier_client_id = id',
                ]);
        }

        $builder
            ->leftJoin('debts d')
            ->on([
                'd.to_account_id = le.id',
                "d.type_of_debt =" . "'supplier_debt_сlient'",
                "d.status =" . "'active'",
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                "supplier_id =" . $supplier_id,
            ]);

        $builder
            ->orderBy('u.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $suppliers_array = [];
        $suppliers = PdoConnector::execute($builder);


        if (!$suppliers) {
            return [];
        }

        foreach ($suppliers as $supplier) {
            $existing_index = array_search($supplier->id, array_column($suppliers_array, 'id'));
            $balance_sum = $supplier->balance ?? 0;
            $bank_accounts = null;
            $debit_amount_sum = $supplier->debit_amount ?? 0;

            if ($supplier->bank_account ?? false) {
                $bank_accounts = [
                    'account' => $supplier->bank_account,
                    'inn' => $supplier->inn,
                    'company_name' => $supplier->company_name,
                    'debit_amount' => $debit_amount_sum,
                    'balance' => $balance_sum,
                    'le_id' => $supplier->le_id,
                ];
            }

            if ($existing_index === false) {
                $suppliers_array[] = [
                    'id' => $supplier->id,
                    'role' => $supplier->role,
                    'name' => $supplier->username,
                    'percentage' => $supplier->percentage,
                    'balance_sum' => $balance_sum,
                    'debit_amount_sum' => $debit_amount_sum,
                    'user_id' => $supplier->user_id,
                    'created_at' => $supplier->created_at,
                    'bank_accounts' => $bank_accounts ? [$bank_accounts] : [],
                ];
            } else {
                $suppliers_array[$existing_index]['balance_sum'] += $balance_sum;
                $suppliers_array[$existing_index]['debit_amount_sum'] += $debit_amount_sum;
                if ($bank_accounts) {
                    $suppliers_array[$existing_index]['bank_accounts'][] = $bank_accounts;
                }
            }
        }


        //Logger::log(print_r($suppliers_array, true), 'clients_array');

        return $suppliers_array;
    }


    public static function getSuppliersUsersCount($role, int $supplier_id,) : int
    {
        if ($role == 'manager') {
            $builder = Managers::newQueryBuilder();
        }

        if ($role == 'client') {
            $builder = Clients::newQueryBuilder();
        }

        $builder
            ->select([
                'COUNT(id) as count',
            ]);
        $builder
            ->where([
                "supplier_id =" . $supplier_id,
            ]);

        return PdoConnector::execute($builder)[0]->count ?? 0;
    }
}