<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\Clients;
use Source\Project\Models\Shop;


/**
 *
 */
class ShopLM
{

    public static function insertNewShop(array $data)
    {
        $builder = Shop::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getShopsCount()
    {
        $builder = Shop::newQueryBuilder()
            ->select(['COUNT(shop.id) as count']);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getShops(int $offset = 0, int $limit = 8): array
    {

        $builder = Shop::newQueryBuilder()
            ->select([
                '*'
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->orderBy('u.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $shops = PdoConnector::execute($builder);

        if (!$shops) {
            return [];
        }

        $selects = '';

        foreach ($shops as $key => $shop) {
            if ($shops[$key + 1] ?? false) {
                $selects .= "$shop->user_id, ";
            } else {
                $selects .= "$shop->user_id";
            }
        }

        $builder = Shop::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'u.role as role',
                'u.id as user_id',
                'u.email as email',
                'u.password as password',
                'le.bank_account as bank_account',
                'le.inn as inn',
                'le.company_name as company_name',
                'le.id as le_id',
                'ba.balance as balance',
            ])
            ->from('shop')
            ->leftJoin('legal_entities le')
            ->on([
                'le.shop_id = id',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = le.id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->where([
                "user_id IN($selects)"
            ])
            ->groupBy("shop.id, u.id, le.id",);


        $shops_array = [];
        $shops = PdoConnector::execute($builder);

        if (!$shops) {
            return [];
        }

        foreach ($shops as $shop) {
            $existing_index = array_search($shop->id, array_column($shops_array, 'id'));
            $balance_sum = $shop->balance ?? 0;
            $bank_accounts = null;
            $decoded = openssl_decrypt(base64_decode($shop->password), Config::METHOD, Config::ENCRYPTION);

            if ($shop->bank_account ?? false) {
                $bank_accounts = [
                    'account' => $shop->bank_account,
                    'inn' => $shop->inn,
                    'company_name' => $shop->company_name,
                    'balance' => $balance_sum,
                    'le_id' => $shop->le_id,
                ];
            }

            if ($existing_index === false) {
                $shops_array[] = [
                    'id' => $shop->id,
                    'email' => $shop->email,
                    'role' => $shop->role,
                    'name' => $shop->username,
                    'balance_sum' => $balance_sum,
                    'password' => $decoded,
                    'user_id' => $shop->user_id,
                    'bank_accounts' => $bank_accounts ? [$bank_accounts] : [],
                ];
            } else {
                $shops_array[$existing_index]['balance_sum'] += $balance_sum;

                if ($bank_accounts) {
                    $shops_array[$existing_index]['bank_accounts'][] = $bank_accounts;
                }
            }
        }



        return $shops_array;
    }

    public static function getShopId(int $id): ?array
    {

        $builder = Shop::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                'GROUP_CONCAT(le.id SEPARATOR ' . '", "' . ') as legal_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = user_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.shop_id = id',
            ])
            ->where([
                'id =' . $id,
            ])
            ->groupBy('shop.id')
            ->limit(1);

        $shop = PdoConnector::execute($builder)[0] ?? [];

        if (!$shop) {
            return null;
        }

        return [
            'id' => $shop->id,
            'username' => $shop->username,
            'percentage' => $shop->percentage,
            'legal_id' => $shop->legal_id,
        ];
    }

    public static function ShopIdDelete(int $shop_id)
    {
        $builder = Shop::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $shop_id,
            ]);


        return PdoConnector::execute($builder);
    }

}