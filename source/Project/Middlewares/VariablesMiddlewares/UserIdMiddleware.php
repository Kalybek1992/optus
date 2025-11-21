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

final class UserIdMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {
        $user_id = InformationDC::get('user_id');


        $query_builder = Users::newQueryBuilder()
            ->select()
            ->where([
                'id = "' . $user_id . '"'
            ])
            ->limit(1);

        $user = PdoConnector::execute($query_builder)[0] ?? null;


        if (!$user) {
            return false;
        }

        InformationDC::set('user_role', $user->role);


        return $next();
    }
}