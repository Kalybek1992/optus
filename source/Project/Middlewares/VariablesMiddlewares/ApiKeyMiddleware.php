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

final class ApiKeyMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {

        $auth_token = RequestDC::get('auth_token');
        $home_controller = new HomeController();

        if (!$auth_token) {
            echo $home_controller->authPage();
            die();
        }

        $query_builder = Users::newQueryBuilder()
            ->select()
            ->where([
                'token = "' . $auth_token . '"'
            ])
            ->limit(1);


        $user = PdoConnector::execute($query_builder)[0] ?? null;



        if (!$user || $user->redirect) {
            echo $home_controller->authPage();
            die();
        }

        $user_arr = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'token' => $user->token,
            'restricted_access' => $user->restricted_access,
        ];

        InformationDC::set('user', $user_arr);


        return $next();
    }
}