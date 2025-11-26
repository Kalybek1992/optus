<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\Couriers;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Users;


/**
 *
 */
class UsersLM
{
    public static function geUsers(int $offset = 0, int $limit = 8)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'id',
                'name',
                'email',
                'role'
            ])
            ->where([
                'role !=' . "'courier'",
                'role !=' . "'admin'",
                'role !=' . "'manager'",
            ])
            ->orderBy('role');

        return PdoConnector::execute($builder);
    }

    public static function getUserEmail(string $email)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'c.id as client_id',
                'c.percentage as client_percentage',
                's.id as suppliers_id',
                's.percentage as suppliers_percentage',
            ])
            ->leftJoin('clients c')
            ->on([
                'c.user_id = id',
            ])
            ->leftJoin('suppliers s')
            ->on([
                's.user_id = id',
            ])
            ->where([
                'email =' . "'" . $email . "'"
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getUserSupplier($user_id)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'id',
                'name',
                'email',
                'role',
                's.id as suppliers_id',
                's.balance as balance',
                's.stock_balance as stock_balance',
            ])
            ->innerJoin('suppliers s')
            ->on([
                's.user_id = id',
            ])
            ->where([
                'id =' . "'" . $user_id . "'",
                'role =' . "'supplier'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }


    public static function getUserClientServices($user_id)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'id',
                'name',
                'email',
                'role',
                's.id as suppliers_id',
                'cs.id as client_services_id',
            ])
            ->innerJoin('client_services cs')
            ->on([
                'cs.user_id = id',
            ])
            ->innerJoin('suppliers s')
            ->on([
                's.id = cs.supplier_id',
            ])
            ->where([
                'id =' . "'" . $user_id . "'",
                'role =' . "'client_services'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getUserClients($user_id)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'c.id as client_id',
                'c.percentage as client_percentage',
            ])
            ->innerJoin('clients c')
            ->on([
                'c.user_id = id',
            ])
            ->where([
                'id =' . "'" . $user_id . "'",
                'role =' . "'client'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getUserShop($user_id)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'id',
                'name',
                'role',
                's.id as shop_id',
            ])
            ->innerJoin('shop s')
            ->on([
                's.user_id = id',
            ])
            ->where([
                'id =' . "'" . $user_id . "'",
                'role =' . "'shop'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getAdministratorsCount()
    {
        $builder = Users::newQueryBuilder()
            ->select(['COUNT(users.id) as count'])
            ->where([
                'role =' . "'admin'",
            ]);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getUserAdministrators(int $offset = 0, int $limit = 8)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'role =' . "'admin'",
            ])
            ->limit($limit)
            ->offset($offset);

        $administrators_array = [];
        $administrators = PdoConnector::execute($builder);

        if (!$administrators) {
            return [];
        }


        foreach ($administrators as $admin) {
            $decoded = openssl_decrypt(base64_decode($admin->password), Config::METHOD, Config::ENCRYPTION);
            $administrators_array[] = [
                'id' => $admin->id,
                'email' => $admin->email,
                'role' => $admin->role,
                'name' => $admin->name,
                'password' => $decoded,
                'user_id' => $admin->id,
                'created_at' => $admin->created_at,

            ];
        }

        return $administrators_array;
    }

    public static function getUserToken(string $token)
    {
        $builder = Users::newQueryBuilder()
            ->select()
            ->where([
                'token =' . "'" . $token . "'"
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function updateUserTokenRedirect(string $token)
    {
        $builder = Users::newQueryBuilder()
            ->update([
                'redirect =' . 0
            ])
            ->where([
                'token =' . "'" . $token . "'"
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function updateUserEmail(int $id, string $new_email)
    {
        $builder = Users::newQueryBuilder()
            ->update([
                'email =' . $new_email,
                'redirect =' . 1
            ])
            ->where([
                'id =' . $id
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

    public static function updateUserPassword(int $id, string $new_password)
    {
        $builder = Users::newQueryBuilder()
            ->update([
                'password =' . $new_password,
                'redirect =' . 1
            ])
            ->where([
                'id =' . $id
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

    public static function insertNewUser(array $data)
    {
        $builder = Users::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }


    public static function getUserId($user_id)
    {
        $builder = Users::newQueryBuilder()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                's.id as suppliers_id',
                'c.id as clients_id',
                'cs.id as client_services_id',
                'sp.id as shop_id',
                'cr.id as courier_id',
            ])
            ->from('users')
            ->leftJoin('suppliers s')
            ->on([
                's.user_id = users.id',
            ])
            ->leftJoin('clients c')
            ->on([
                'c.user_id = users.id',
            ])
            ->leftJoin('client_services cs')
            ->on([
                'cs.user_id = users.id',
            ])
            ->leftJoin('shop sp')
            ->on([
                'sp.user_id = users.id',
            ])
            ->leftJoin('couriers cr')
            ->on([
                'cr.user_id = users.id',
            ])
            ->where([
                'users.id =' . "'" . $user_id . "'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function deleteUserId($user_id)
    {
        $builder = Users::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . "'" . $user_id . "'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }


}