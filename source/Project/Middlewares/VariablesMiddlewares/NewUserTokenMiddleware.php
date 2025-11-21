<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Users;

final class NewUserTokenMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        $correct = true;
        $token = '';

        while ($correct){
            $token = bin2hex(random_bytes(32 / 2));

            $query_builder = Users::newQueryBuilder()
                ->select()
                ->where([
                    'token = "' . $token . '"',
                ])
                ->limit(1);


            $user = PdoConnector::execute($query_builder)[0] ?? null;

            if (!$user) {
                $correct = false;
            }
        }

        InformationDC::set('token', $token);

        return $next();
    }
}