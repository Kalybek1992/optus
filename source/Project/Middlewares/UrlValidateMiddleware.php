<?php

namespace Source\Project\Middlewares;

use Override;
use Source\Base\Core\Middleware;
use Source\Base\Rules\RequestRules;
use Source\Project\DataContainers\RequestDC;

class UrlValidateMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     */
    #[Override] public function handle(callable $next): bool
    {
        /**
         * @DESC Берет урл /controller/function
         */
        $url_controller_function = '/' . RequestDC::$request_function;
        /**
         * @var
         */
        $request_rules = new RequestRules(RequestDC::$request_url_rules);

        /**
         * @DESC $this->request->getMethod() - тип запроса GET или POST
         */

//        if ($request_rules['sku'])
        $match_result =  $request_rules->validateRequest(
            RequestDC::$request_method,
            $url_controller_function,
            RequestDC::$request_params//$_GET,
        );


        if ($match_result) {
            return $next();
        }

        return false;
    }
}