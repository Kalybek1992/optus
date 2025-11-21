<?php

namespace Source\Project\Middlewares;

use Source\Base\Core\Middleware;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;

final class BlockMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {

        $to_block = [];

        $merchant_id = VariablesDC::get('merchant_id');
        $product_id = VariablesDC::get('product_id');
        $sku = VariablesDC::get('sku');


        if ($merchant_id){
            if (str_contains($merchant_id, 'https://kaspi.kz/shop/info/merchant/')) {

                preg_match("#(?<=/\?merchantId=).*#", $merchant_id, $match);

                if ($match) {
                    $merchant_id = $match[0];
                }
            }
            $to_block['merchant_id'] = $merchant_id;
        }


        if (!($to_block['merchant_id'] ?? null)){
            return false;
        }

        if ($sku) {
            $product_id_from_sku = InformationDC::get('market_product')->variables['product_id'];
            $to_block['product_id'] = $product_id_from_sku;
        }

        if ($product_id) {
            $to_block['product_id'] = $product_id;
        }

        if ($product_id && $sku){
            if ($product_id != $product_id_from_sku){
                return false;
            }
        }

        if ($to_block) {

            InformationDC::set('to_block', $to_block);

            return $next();
        }

        return false;
    }
}
