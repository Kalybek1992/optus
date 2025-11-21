<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Logger;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Users;

final class ApiKeyShopreceiptsDateMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {

        $auth_token = RequestDC::get('auth_token');

        if (!$auth_token) {
            return false;
        }


        $query_builder = Users::newQueryBuilder()
            ->select()
            ->where([
                'token = "' . $auth_token . '"'
            ])
            ->limit(1);

        $user = PdoConnector::execute($query_builder)[0] ?? null;


        if (!$user) {
            return false;
        }

        if ($user->role == 'supplier' ||
            $user->role == 'client_services' ||
            $user->role == 'manager' ||
            $user->role == 'client' ||
            $user->role == 'courier') {
            return false;
        }


        return $next();
    }
}