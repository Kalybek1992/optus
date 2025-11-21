<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Responses\ApiResponse;

final class MarketIDMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        $merchant_id = VariablesDC::get('merchant_id');
        
        if ($merchant_id) {

            foreach (InformationDC::get('user')->markets_ids as $id) {
                if ($id == $merchant_id) {
                    return $next();
                }
            }

//            if ((InformationDC::get('user')->markets_ids[0]??null) == $merchant_id) {
//                return $next();
//            }
        }

        MiddlewareManager::errorSet('market');

        return false;
    }
}