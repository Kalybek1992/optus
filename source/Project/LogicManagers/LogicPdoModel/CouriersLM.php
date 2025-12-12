<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\Clients;
use Source\Project\Models\Couriers;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Users;


/**
 *
 */
class CouriersLM
{

    public static function insertNewCouriers(array $data)
    {
        $builder = Couriers::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getCouriersCount()
    {
        $builder = Couriers::newQueryBuilder()
            ->select(['COUNT(couriers.id) as count']);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getCouriers(int $offset = 0, int $limit = 8): array
    {

        $builder = Couriers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
                'SUM(bo.amount) as balance_unaccepted',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('company_finances cf')
            ->on([
                'cf.courier_id = id',
                "cf.status =" . "'confirm_courier'",
            ])
            ->leftJoin('bank_order bo')
            ->on([
                'bo.id = cf.order_id',
            ])
            ->groupBy('couriers.id')
            ->orderBy('u.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset);


        $couriers_array = [];
        $couriers = PdoConnector::execute($builder);

        if (!$couriers) {
            return [];
        }


        foreach ($couriers as $courier) {
            $decoded = openssl_decrypt(base64_decode($courier->password), Config::METHOD, Config::ENCRYPTION);

            $couriers_array[] = [
                'id' => $courier->id,
                'email' => $courier->email,
                'role' => $courier->role,
                'name' => $courier->username,
                'password' => $decoded,
                'balance_sum' => $courier->current_balance,
                'user_id' => $courier->user_id,
                'created_at' => $courier->created_at,
                'balance_unaccepted' => $courier->balance_unaccepted,
            ];
        }


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $couriers_array;
    }


    public static function getCourierCourierId(int $courier_id): array
    {
        $builder = Couriers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
                'SUM(bo.amount) as balance_unaccepted',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('company_finances cf')
            ->on([
                'cf.courier_id = id',
                "cf.status =" . "'confirm_courier'",
            ])
            ->leftJoin('bank_order bo')
            ->on([
                'bo.id = cf.order_id',
            ])
            ->where([
                'id =' . $courier_id,
            ])
            ->groupBy('couriers.id')
            ->orderBy('u.created_at', 'DESC')
            ->limit(1);

        $courier = PdoConnector::execute($builder)[0] ?? [];

        if (!$courier) {
            return [];
        }

        $decoded = openssl_decrypt(base64_decode($courier->password), Config::METHOD, Config::ENCRYPTION);

        $couriers_array = [
            'id' => $courier->id,
            'email' => $courier->email,
            'role' => $courier->role,
            'name' => $courier->username,
            'password' => $decoded,
            'balance_sum' => $courier->current_balance,
            'user_id' => $courier->user_id,
            'created_at' => $courier->created_at,
            'balance_unaccepted' => $courier->balance_unaccepted,
        ];

        //Logger::log(print_r($couriers_array, true), 'clients_array');
        return $couriers_array;
    }

    public static function getCouriersAll(): array
    {

        $builder = Couriers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->orderBy('u.created_at', 'DESC');


        $couriers_array = [];
        $couriers = PdoConnector::execute($builder);

        if (!$couriers) {
            return [];
        }


        foreach ($couriers as $courier) {
            $decoded = openssl_decrypt(base64_decode($courier->password), Config::METHOD, Config::ENCRYPTION);
            $couriers_array[] = [
                'id' => $courier->id,
                'email' => $courier->email,
                'role' => $courier->role,
                'name' => $courier->username,
                'password' => $decoded,
                'balance_sum' => $courier->current_balance,
                'user_id' => $courier->user_id,
                'created_at' => $courier->created_at,

            ];
        }


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $couriers_array;
    }

    public static function getCouriersNotUser($user_id): array
    {

        $builder = Couriers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                'user_id != ' . $user_id,
            ])
            ->orderBy('u.created_at', 'DESC');


        $couriers_array = [];
        $couriers = PdoConnector::execute($builder);

        if (!$couriers) {
            return [];
        }


        foreach ($couriers as $courier) {
            $decoded = openssl_decrypt(base64_decode($courier->password), Config::METHOD, Config::ENCRYPTION);
            $couriers_array[] = [
                'id' => $courier->id,
                'email' => $courier->email,
                'role' => $courier->role,
                'name' => $courier->username,
                'password' => $decoded,
                'balance_sum' => $courier->current_balance,
                'user_id' => $courier->user_id,
                'created_at' => $courier->created_at,

            ];
        }


        //Logger::log(print_r($builder->build(), true), 'clients_array');

        return $couriers_array;
    }

    public static function getCouriersId($id)
    {
        $builder = Couriers::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'id =' . $id,
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function updateCouriers(array $data, int $id)
    {
        $builder = Couriers::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);
        return PdoConnector::execute($builder);
    }

    public static function adjustCurrentBalance(int $courier_id, float $delta)
    {
        $builder = Couriers::newQueryBuilder()
            ->update([
                'current_balance = ' . $delta
            ])
            ->where([
                'id =' . $courier_id
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getCourierByUserId(int $user_id): array
    {
        $builder = Couriers::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.email as email',
                'u.password as password',
                'u.id as user_id',
                'u.created_at as created_at',
                'SUM(t.amount) as balance_unaccepted',
                'сс.card_number as card_number',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id'
            ])
            ->leftJoin('company_finances cf')
            ->on([
                'cf.courier_id = id',
                "cf.status = 'confirm_courier'"
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = cf.transaction_id'
            ])
            ->leftJoin('credit_cards сс')
            ->on([
                'сс.id = cf.card_id'
            ])
            ->where([
                'u.id =' . $user_id
            ])
            ->groupBy('couriers.id')
            ->limit(1);

        $c = PdoConnector::execute($builder)[0] ?? null;


        if (!$c) return [];

        $card_number = '';

        if ($c->card_number){
            $card_number = trim(chunk_split($c->card_number, 4, ' '));
        }

        $decoded = openssl_decrypt(base64_decode($c->password), Config::METHOD, Config::ENCRYPTION);

        return [
            'id' => $c->id,
            'email' => $c->email,
            'role' => $c->role,
            'name' => $c->username,
            'password' => $decoded,
            'balance_sum' => $c->current_balance,
            'user_id' => $c->user_id,
            'created_at' => $c->created_at,
            'balance_unaccepted' => $c->balance_unaccepted,
            'card_number' => $card_number,
        ];
    }

    public static function courierIdDelete(int $courier_id)
    {
        $builder = Couriers::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $courier_id,
            ]);


        return PdoConnector::execute($builder);
    }

}