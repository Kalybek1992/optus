<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\HomeController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Users;

final class AuthMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {

        $email = VariablesDC::get('email');
        $password = VariablesDC::get('password');

        $encrypted = openssl_encrypt($password, Config::METHOD, Config::ENCRYPTION);
        $encoded = base64_encode($encrypted);

        $query_builder = Users::newQueryBuilder()
            ->select()
            ->where([
                'email = "' . $email . '"',
                'password = "' . $encoded . '"'
            ])
            ->limit(1);


        $user = PdoConnector::execute($query_builder)[0] ?? false;

        if (!$user){
            InformationDC::set('token', false);
            InformationDC::set('redirect', 0);
            return $next;
        }

        InformationDC::set('token', $user->token);
        InformationDC::set('redirect', $user->redirect);


        return $next();
    }
}