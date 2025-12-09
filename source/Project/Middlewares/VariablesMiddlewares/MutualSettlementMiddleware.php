<?php

namespace Source\Project\Middlewares\VariablesMiddlewares;

use Source\Base\Core\Logger;
use Source\Base\Core\Middleware;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Connectors\PdoConnector;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\Models\Users;
use Source\Project\Viewer\ApiViewer;

final class MutualSettlementMiddleware extends Middleware
{
    /**
     * @param callable $next
     * @return bool
     * @throws \Exception
     */
    public function handle(callable $next): bool|string
    {
        $role = InformationDC::get('role');
        $role_id = InformationDC::get('role_id');
        $user_debit = [];

        if ($role == 'supplier') {
            $user_debit = SuppliersLM::getSuppliersDebitCompany($role_id)[0] ?? false;
        }

        if ($role == 'client') {
            $user_debit = ClientsLM::getClientsDebitCompany($role_id)[0] ?? false;
        }

        if ($role == 'client_services') {
            $user_debit = ClientServicesLM::getClientServicesDebitCompany($role_id)[0] ?? false;
        }


        InformationDC::set('user_debit', $user_debit);

        return $next();
    }
}