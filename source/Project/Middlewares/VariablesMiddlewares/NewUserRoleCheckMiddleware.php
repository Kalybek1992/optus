<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\LogicManagers\LogicPdoModel\UsersLM;
use Source\Project\Models\Users;
use Source\Project\Viewer\ApiViewer;

final class NewUserRoleCheckMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        $email = VariablesDC::get('email');
        $password = VariablesDC::get('password');
        $name = VariablesDC::get('name');
        $role = VariablesDC::get('role');
        $token = InformationDC::get('token');
        $result_insert = false;
        $repeat = InformationDC::get('repeat');
        $new_user = [];

        $encrypted = openssl_encrypt($password, Config::METHOD, Config::ENCRYPTION);
        $encoded = base64_encode($encrypted);

        if ($role == 'client' && !$repeat) {
            $result_insert = UsersLM::insertNewUser([
                'name' => $name,
                'role' => $role,
                'token' => $token
            ]);

        }elseif ($role != 'client' && !$repeat) {
            $result_insert = UsersLM::insertNewUser([
                'email' => $email,
                'password' => $encoded,
                'name' => $name,
                'role' => $role,
                'token' => $token
            ]);
        }

        if ($result_insert) {
            $new_user = UsersLM::getUserToken($token);
        }

        InformationDC::set('result_insert', $result_insert);
        InformationDC::set('new_user', $new_user);

        return $next();
    }
}