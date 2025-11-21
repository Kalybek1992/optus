<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;


use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Connectors\RedisConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\LogicManagers\LogicPdoModel\MarketProductsLM;
use Source\Project\Models\KaspiAccounts;
use Source\Project\Requests\KaspiKz;
use Source\Project\Responses\ApiResponse;

final class ProductsIDMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        if (VariablesDC::get('file')){
            $products_min_prices = [];
            /**
             * @DESC check file extension and explode accordingly.
             * assign data to VariableDC
             */
            switch(substr(VariablesDC::get('file')['name'], -4)){
                case '.csv':
                    $handle = fopen(VariablesDC::get('file')['tmp_name'], "r");
                    $contents = fread($handle, filesize(VariablesDC::get('file')['tmp_name']));
                    fclose($handle);
                    $explode = explode(',', $contents);
                    $len = count($explode);
                    for ($i = 0; $i < $len; $i++){

                        $sku = $explode[$i];
                        $min_price = $explode[++$i];

                        $products_min_prices[$sku] = $min_price;
                    }

                    break;
                case '.txt':
                    $handle = fopen(VariablesDC::get('file')['tmp_name'], "r");
                    $contents = fread($handle, filesize(VariablesDC::get('file')['tmp_name']));
                    fclose($handle);
                    $explode = explode(',', $contents);
                    foreach ($explode as $product) {
                        $explode_product = explode(':', $product);

                        $sku = $explode_product[0];
                        $min_price = $explode_product[1];

                        $products_min_prices[$sku] = $min_price;
                    }
                    break;
                default:

                    MiddlewareManager::errorSet('file');
                    return false;
            }
            /**
             * @DESC if data exists check if product exists in sql db
             */
            if ($products_min_prices) {
                $listed_products = [];

                $user_id = InformationDC::get('user')->id;
                /**
                 * @DESC if product exists in db assign data to InformationDC for update in sql db
                 * else add data to redis 'for_get_details... ' for further addition
                 */

                foreach ($products_min_prices as $sku => $min_price) {
                    $listed_product = MarketProductsLM::getUsersProductBySku(
                        $sku, $user_id
                    );

                    if ($listed_product){
                        $listed_product['new_min_price'] = $min_price;
                        $listed_products[] = $listed_product;
                    }
                }

                InformationDC::set('market_products', $listed_products ?? null);

                if (InformationDC::get('market_products')) {
                    return $next();
                }
                MiddlewareManager::errorSet('sku');

                return false;
            }

        }

        return $next();
    }
}