<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Logger;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\HomeController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Users;

final class GetFileMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {

        $file = RequestDC::get('file');

        if (!$file) {
          return false;
        }

        return $next();
    }
}