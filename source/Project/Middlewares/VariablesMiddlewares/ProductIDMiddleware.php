<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;


use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Connectors\RedisConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\LogicManagers\LogicPdoModel\MarketProductsLM;
use Source\Project\LogicManagers\LogicPdoModel\ProductsLM;
use Source\Project\LogicManagers\DocumentLM\MerchantProductsLM;
use Source\Project\Models\KaspiAccounts;
use Source\Project\Requests\KaspiKz;
use Source\Project\Responses\ApiResponse;

final class ProductIDMiddleware extends Middleware
{
    /**
     * @DESC Check product existence in sql db. Go to next if exists, else send data to 'get_details... ' redis
     * @DESC and go to next again
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        //VariablesDC::get('products_min_prices')
        if (VariablesDC::get('sku')) {


            /**
             * @DESC check product existence in db
             */
            InformationDC::set('market_product', MerchantProductsLM::getUsersProductBySku(
                InformationDC::get('user')->id
            ));

//            InformationDC::set('market_product',
//                ProductsLM::getProducts(VariablesDC::get('sku'), 'sku')[0] ?? null
//            );


            /**
             * @DESC if product exists go to next middleware
             */
            if (InformationDC::get('market_product')) {

                return $next();
            }




            MiddlewareManager::errorSet('sku');

            return false;

        }

        return $next();



    }
}