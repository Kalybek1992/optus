<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Models\Users;

final class EmailMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool
    {
        $email = VariablesDC::get('email');
        $clean_email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!$clean_email) {
            return false;
        }

        $validated_email = filter_var($clean_email, FILTER_VALIDATE_EMAIL);

        if (!$validated_email){
            return false;
        }

        $query_builder = Users::newQueryBuilder()
            ->select([
                'id',
                'email',
                'ub.user_id as is_banned',
            ])
            ->leftJoin('users_banned ub')
            ->on([
                'ub.user_id = id'
            ])
            ->where([
                'email = "' . $validated_email . '"'
            ])
            ->groupBy('users.id')
            ->limit(1);


        InformationDC::set('user', PdoConnector::execute($query_builder)[0] ?? null);

        if (InformationDC::get('user')) {

            if (InformationDC::get('user')->is_banned) {
                MiddlewareManager::errorSet('banned');

                return false;
            }

            return $next();
        }

        MiddlewareManager::errorSet('email');

        return false;

    }
}