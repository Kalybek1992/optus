<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Logger;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Users;

final class RepeatManagerNameMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        $name = VariablesDC::get('name');
        $role = InformationDC::get('role');
        $user = [];

        if ($name) {
            $query_builder = Users::newQueryBuilder()
                ->select()
                ->where([
                    "name = '" . $name . "'",
                    "role = '" . $role . "'",
                ])
                ->limit(1);


            $user = PdoConnector::execute($query_builder)[0] ?? null;
        }

        if ($user){
            InformationDC::set('repeat', true);
        }else{
            InformationDC::set('repeat', false);
        }

        return $next();
    }
}