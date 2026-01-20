<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\Clients;
use Source\Project\Models\Managers;


/**
 *
 */
class ManagersLM
{

    public static function insertNewManagers(array $data)
    {
        $builder = Managers::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getManagersOrAll($supplier_id): array
    {
        $builder = Managers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                'supplier_id =' . $supplier_id
            ]);

        $managers = PdoConnector::execute($builder);

        $builder = Clients::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                'supplier_id =' . $supplier_id
            ]);

        $clients = PdoConnector::execute($builder);

        $managers_array = [];

        if (!$managers) {
            return [];
        }

        foreach ($managers as $manager) {
            $managers_array[] = [
                'username' => $manager->username,
                'role_id' => $manager->id,
                'current_balance' => $manager->current_balance,
                'role' => $manager->role,
            ];
        }

        foreach ($clients as $client) {
            $managers_array[] = [
                'username' => $client->username,
                'role_id' => $client->id,
                'current_balance' => $client->current_balance,
                'role' => $client->role,
            ];
        }


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $managers_array;
    }

    public static function getManagerId($manager_id)
    {
        $builder = Managers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                'id =' . $manager_id
            ]);


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function supplierManagersAllDelete(int $supplier_id)
    {
        $builder = Managers::newQueryBuilder()
            ->delete()
            ->where([
                'supplier_id =' . $supplier_id,
            ]);

        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return PdoConnector::execute($builder);
    }

    public static function managerUpdate(array $data, int $manager_id)
    {
        $builder = Managers::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $manager_id
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

    public static function getManagersAll($supplier_id): array
    {
        $builder = Managers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                'supplier_id =' . $supplier_id
            ]);

        $managers = PdoConnector::execute($builder);


        $managers_array = [];

        if (!$managers) {
            return [];
        }

        foreach ($managers as $manager) {
            $managers_array[] = [
                'username' => $manager->username,
                'manager_id' => $manager->id,
                'current_balance' => $manager->current_balance,
                'role' => $manager->role,
            ];
        }

        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $managers_array;
    }

}