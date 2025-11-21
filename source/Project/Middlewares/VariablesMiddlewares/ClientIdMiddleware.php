<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Logger;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Clients;
use Source\Project\Models\Users;

final class ClientIdMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {
        $client_id = InformationDC::get('client_id');


        $query_builder = Clients::newQueryBuilder()
            ->select()
            ->where([
                'id = "' . $client_id . '"'
            ])
            ->limit(1);

        $client = PdoConnector::execute($query_builder)[0] ?? null;


        if (!$client) {
            return false;
        }


        return $next();
    }
}