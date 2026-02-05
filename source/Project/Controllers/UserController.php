<?php

namespace Source\Project\Controllers;


use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\ManagersLM;
use Source\Project\LogicManagers\LogicPdoModel\ShopLM;
use Source\Project\LogicManagers\LogicPdoModel\StatementLogLM;
use Source\Project\LogicManagers\LogicPdoModel\StockBalancesLM;
use Source\Project\LogicManagers\LogicPdoModel\SupplierBalanceLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\UsersLM;
use Source\Project\Models\BankOrder;
use Source\Project\Models\Clients;
use Source\Project\Models\CompanyFinances;
use Source\Project\Models\CreditCards;
use Source\Project\Models\Debts;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\MutualSettlement;
use Source\Project\Models\StockBalances;
use Source\Project\Models\SupplierBalance;
use Source\Project\Models\TaskPlanner;
use Source\Project\Models\Transactions;
use Source\Project\Models\UploadedDocuments;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Viewer\ApiViewer;
use Source\Project\Models\EndOfDaySettlement;
use Source\Project\Models\StatementLog;

class UserController extends BaseController
{

    /**
     * @return array
     * @throws \Exception
     */

    public function auth(): array
    {

        $token = InformationDC::get('token');

        if (!$token) {
            return ApiViewer::getErrorBody(['value' => 'Bad email or password']);
        }

        setcookie("AuthToken", $token, time() + (86400 * 30), "/", "", false, false);

        return ApiViewer::getOkBody(['token' => $token]);
    }

    public function addUser(): string
    {
        $suppliers = SuppliersLM::getSuppliersAll();
        $peturn_pages = InformationDC::get('return_page') ?? null;

        return $this->twig->render('User/AddUsers.twig', [
            'suppliers' => $suppliers,
            'return_page' => $peturn_pages
        ]);
    }

    public function addUserRole(): array
    {
        $repeat = InformationDC::get('repeat');
        $result_insert = InformationDC::get('result_insert');
        $new_user = InformationDC::get('new_user');

        $role = VariablesDC::get('role');
        $percent = InformationDC::get('percent');
        $supplier_id = InformationDC::get('supplier_id');
        $supplier = [];


        if ($repeat) {
            return ApiViewer::getErrorBody(['value' => 'repeat_email']);
        }

        if (!$result_insert) {
            return ApiViewer::getErrorBody(['value' => 'failed_to_add_new_user']);
        }

        if ($role == 'client' && !$percent) {
            return ApiViewer::getErrorBody(['value' => 'no_percent']);
        }

        if ($role == 'client_services' && !$supplier_id) {
            return ApiViewer::getErrorBody(['value' => 'no_supplier_id']);
        }


        if ($role == 'client_services') {
            $supplier = SuppliersLM::getSuppliersId($supplier_id);
        }

        if ($role == 'client_services' && !$supplier) {
            return ApiViewer::getErrorBody(['value' => 'no_supplier_id']);
        }


        if ($role == 'client') {
            ClientsLM::insertNewClients([
                'user_id' => $new_user->id,
                'percentage' => $percent,
            ]);
        }

        if ($role == 'supplier') {
            SuppliersLM::insertNewSuppliers([
                'user_id' => $new_user->id,
            ]);
        }

        if ($role == 'courier') {
            CouriersLM::insertNewCouriers([
                'user_id' => $new_user->id,
                'current_balance' => 0,
                'last_update' => date('Y-m-d H:i:s')
            ]);
        }

        if ($role == 'client_services') {
            ClientServicesLM::insertNewClients([
                'user_id' => $new_user->id,
                'supplier_id' => $supplier_id,
            ]);
        }

        if ($role == 'shop') {
            ShopLM::insertNewShop([
                'user_id' => $new_user->id,
            ]);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function getUsersLinking(): array
    {
        $page = InformationDC::get('page') - 1;

        $offset = $page * 8;

        $users = UsersLM::geUsers($offset);
        $users_array = [];

        if (!$users) {
            return ApiViewer::getOkBody([
                'success' => 'reached_the_end'
            ]);
        }

        foreach ($users as $user) {
            $users_array[] = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'name' => $user->name,
            ];
        }

        Logger::log(print_r($users_array, true), 'getUsersLinking');


        return ApiViewer::getOkBody([
            'users' => $users_array
        ]);
    }

    public function bindAccountSupplier(): array
    {
        $entity_id = InformationDC::get('entity_id');
        $legal_entities = LegalEntitiesLM::getEntitiesBindAccount($entity_id);
        $user_id = VariablesDC::get('user_id');
        $percent = VariablesDC::get('percent');
        $user = UsersLM::getUserSupplier($user_id);
        $debts = [];
        $insert_new_balance = [];

        if (!$user) {
            return ApiViewer::getErrorBody(['value' => 'user_id']);
        }

        if (!$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_entity_id']);
        }

        $transactions = TransactionsLM::getTransactionFromOrToAccountId(
            $entity_id,
            $entity_id
        );

        if (!$transactions) {
            return ApiViewer::getErrorBody(['value' => 'not_transaction']);
        }

        foreach ($transactions as $transaction) {
            if ($transaction->type != 'expense') {
                return ApiViewer::getErrorBody(['value' => 'bad_transaction']);
            }
        }

        foreach ($transactions as $transaction) {
            $from_account_id = $transaction->from_account_id;
            $to_account_id = $transaction->to_account_id;
            $percent_income = abs($transaction->amount) * ($percent / 100);

            if ($transaction->type == 'expense') {
                $recipient = LegalEntitiesLM::getEntitiesId($transaction->to_account_id);
                $sender = LegalEntitiesLM::getEntitiesId($transaction->from_account_id);
                $supplier_balance = SupplierBalanceLM::getSupplierBalance(
                    $recipient->inn,
                    $sender->inn
                );

                if (!$supplier_balance) {
                    $key = $recipient->inn . '_' . $sender->inn;
                    if (!isset($insert_new_balance[$key])) {
                        $insert_new_balance[$key] = [
                            'recipient_inn' => $recipient->inn,
                            'sender_inn' => $sender->inn,
                            'amount' => $transaction->amount,
                        ];
                    }else{
                        $insert_new_balance[$key]['amount'] += $transaction->amount;
                    }
                }

                if ($supplier_balance) {
                    $new_balance = $supplier_balance->amount + $transaction->amount;
                    SupplierBalanceLM::updateSupplierBalance(
                        [
                            'amount = ' . $new_balance,
                        ],
                        $supplier_balance->id,
                    );
                }
            }

            $debts[] = [
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction->id,
                'type_of_debt' => 'supplier_goods',
                'amount' => $transaction->amount - $percent_income,
                'date' => date('Y-m-d'),
                'status' => 'active'
            ];

            TransactionsLM::updateTransactionsId([
                'interest_income =' . $percent_income,
                'percent =' . $percent,
            ], $transaction->id);
        }

        LegalEntitiesLM::updateLegalEntities([
            'supplier_id =' . $user->suppliers_id,
            'our_account =' . 0,
            'percent =' . $percent,
        ], $legal_entities->id);


        if ($insert_new_balance) {
            $insert_new_balance = array_values($insert_new_balance);
            SupplierBalanceLM::setNewSupplierBalance($insert_new_balance);
        }

        if ($debts) {
            DebtsLM::setNewDebts($debts);
        }

        //Logger::log(print_r($user, true), 'bindAccountSupplier');

        return ApiViewer::getOkBody([
            'success' => 'ok',
            'percent' => $percent
        ]);
    }

    public function bindAccountClientServices(): array
    {
        $entity_id = InformationDC::get('entity_id');
        $legal_entities = LegalEntitiesLM::getEntitiesBindAccount($entity_id);
        $user_id = VariablesDC::get('user_id');
        $percent = VariablesDC::get('percent');
        $user = UsersLM::getUserClientServices($user_id);
        $debts = [];

        if (!$user) {
            return ApiViewer::getErrorBody(['value' => 'user_id']);
        }

        if (!$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_entity_id']);
        }


        $transactions = TransactionsLM::getTransactionFromOrToAccountId(
            $entity_id,
            $entity_id
        );

        if (!$transactions) {
            return ApiViewer::getErrorBody(['value' => 'not_transaction']);
        }

        foreach ($transactions as $transaction) {
            if ($transaction->type == 'expense') {
                return ApiViewer::getErrorBody(['value' => 'bad_transaction']);
            }
        }

        foreach ($transactions as $transaction) {
            $from_account_id = $transaction->from_account_id;
            $to_account_id = $transaction->to_account_id;
            $percent_income = abs($transaction->amount) * ($percent / 100);


            $debts[] = [
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction->id,
                'type_of_debt' => 'client_services',
                'amount' => $transaction->amount - $percent_income,
                'date' => date('Y-m-d'),
                'status' => 'active'
            ];

            TransactionsLM::updateTransactionsId([
                'interest_income =' . $percent_income,
                'percent =' . $percent,
            ], $transaction->id);
        }


        LegalEntitiesLM::updateLegalEntities([
            'supplier_id =' . $user->suppliers_id,
            'client_service_id =' . $user->client_services_id,
            'client_services =' . 1,
            'percent =' . $percent,
        ], $legal_entities->id);


        if ($debts) {
            DebtsLM::setNewDebts($debts);
        }

        //Logger::log(print_r($user, true), 'bindAccountSupplier');


        return ApiViewer::getOkBody([
            'success' => 'ok',
            'percent' => $percent
        ]);
    }

    public function bindAccountClient(): array
    {
        $entity_id = InformationDC::get('entity_id');
        $legal_entities = LegalEntitiesLM::getEntitiesBindAccount($entity_id);

        $user_id = VariablesDC::get('user_id');
        $user = UsersLM::getUserClients($user_id);
        $debts = [];

        if (!$user) {
            return ApiViewer::getErrorBody(['value' => 'bad_user_id']);
        }

        if (!$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_entity_id']);
        }

        $percent = $user->client_percentage;
        $transactions = TransactionsLM::getTransactionFromOrToAccountId($entity_id, $entity_id);

        if (!$transactions) {
            return ApiViewer::getErrorBody(['value' => 'not_transaction']);
        }

        foreach ($transactions as $transaction) {
            if ($transaction->type == 'expense') {
                return ApiViewer::getErrorBody(['value' => 'bad_transaction']);
            }
        }

        foreach ($transactions as $transaction) {
            $percent_income = abs($transaction->amount) * ($percent / 100);

            $from_account_id = $transaction->from_account_id;
            $to_account_id = $transaction->to_account_id;

            $debts[] = [
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction->id,
                'type_of_debt' => 'client_goods',
                'amount' => $transaction->amount - $percent_income,
                'date' => date('Y-m-d'),
                'status' => 'active'
            ];

            TransactionsLM::updateTransactionsId([
                'interest_income =' . $percent_income,
                'percent =' . $percent,
            ], $transaction->id);
        }

        if ($debts) {
            DebtsLM::setNewDebts($debts);
        }

        LegalEntitiesLM::updateLegalEntities([
            'client_id = ' . $user->client_id,
            'percent =' . $percent,
        ], $legal_entities->id);


        return ApiViewer::getOkBody([
            'success' => 'ok',
            'percent' => $percent
        ]);
    }

    public function getClients(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 8;
        $offset = $page * $limit;

        $clients = ClientsLM::getClients($offset, $limit);

        if (!$clients) {
            return $this->twig->render('User/NoClients.twig');
        }

        $clients_count = ClientsLM::getClientsCount()->count ?? 0;
        $page_count = ceil($clients_count / $limit);


        return $this->twig->render('User/GetClients.twig', [
            'clients' => $clients,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function getShop(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 8;
        $offset = $page * $limit;

        $shops = ShopLM::getShops($offset, $limit);

        if (!$shops) {
            return $this->twig->render('User/NoClients.twig');
        }

        $shops_count = ShopLM::getShopsCount()->count ?? 0;
        $page_count = ceil($shops_count / $limit);


        return $this->twig->render('User/GetShop.twig', [
            'clients' => $shops,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function getClientServices(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 8;
        $offset = $page * $limit;
        $clients = ClientServicesLM::getClientsServices($offset, $limit);

        if (!$clients) {
            return $this->twig->render('User/NoClients.twig');
        }

        $clients_count = ClientServicesLM::getClientsServicesCount()->count ?? 0;
        $page_count = ceil($clients_count / $limit);


        return $this->twig->render('User/GetClientServices.twig', [
            'clients' => $clients,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function getCouriers(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 8;

        $offset = $page * $limit;

        $couriers = CouriersLM::getCouriers($offset, $limit);

        if (!$couriers) {
            return $this->twig->render('User/NoClients.twig');
        }

        $clients_count = CouriersLM::getCouriersCount()->count ?? 0;
        $page_count = ceil($clients_count / $limit);


        return $this->twig->render('User/GetCouriers.twig', [
            'couriers' => $couriers,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function getAdministrators(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 8;
        $offset = $page * $limit;

        $administrators = UsersLM::getUserAdministrators($offset, $limit);

        if (!$administrators) {
            return $this->twig->render('User/NoClients.twig');
        }

        $clients_count = UsersLM::getAdministratorsCount()->count ?? 0;
        $page_count = ceil($clients_count / $limit);


        return $this->twig->render('User/GetAdministrators.twig', [
            'administrators' => $administrators,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function getSuppliers(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 8;
        $offset = $page * $limit;
        $suppliers = SuppliersLM::getSuppliers($offset, $limit);

        if (!$suppliers) {
            return $this->twig->render('NoClients.twig');
        }

        $suppliers_count = SuppliersLM::getSuppliersCount()->count ?? 0;
        $page_count = ceil($suppliers_count / $limit);


        return $this->twig->render('User/GetSuppliers.twig', [
            'suppliers' => $suppliers,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function changeEmail(): array
    {
        $email = VariablesDC::get('email');
        $user_id = InformationDC::get('user_id');
        $repeat = VariablesDC::get('repeat');

        if ($repeat) {
            return ApiViewer::getErrorBody(['value' => 'repeat_email']);
        }

        $result_update = UsersLM::updateUserEmail($user_id, $email);

        if (!$result_update) {
            return ApiViewer::getErrorBody(['value' => 'bad_update']);
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function changePassword(): array
    {
        $user_id = InformationDC::get('user_id');
        $password = VariablesDC::get('password');

        $encrypted = openssl_encrypt($password, Config::METHOD, Config::ENCRYPTION);
        $encoded = base64_encode($encrypted);

        $result_update = UsersLM::updateUserPassword($user_id, $encoded);

        if (!$result_update) {
            return ApiViewer::getErrorBody(['value' => 'bad_update']);
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function userDelete(): array
    {
        $user_id = InformationDC::get('user_id');
        $user = UsersLM::getUserId($user_id);
        $legals_id = [];

        if (!$user) {
            return ApiViewer::getErrorBody(['value' => 'bad_user_id']);
        }

        if ($user->role == 'client') {

            $client = ClientsLM::getClientId($user->clients_id);

            if (!$client) {
                return ApiViewer::getErrorBody(['value' => 'bad_client_id']);
            }

            $legals_id = $client['legal_id'] ? explode(',', $client['legal_id']) : [];

            ClientsLM::clientIdDelete($client['id']);
        }

        if ($user->role == 'client_services' || $user->role == 'supplier') {
            $client_debt_suppliers = 0;

            if ($user->role == 'supplier') {
                $role = SuppliersLM::getSupplierIdLegalOff($user->suppliers_id);
            } else {
                $role = ClientServicesLM::getClientServicesIdLegalOff($user->client_services_id);
            }

            if (!$role) {
                return ApiViewer::getErrorBody(['value' => 'bad_client_id']);
            }


            $legals_id = $role['legal_id'] ? explode(',', $role['legal_id']) : [];

            if ($user->role == 'supplier') {
                ClientsLM::supplierClientsAllDelete($role['id']);
                ManagersLM::supplierManagersAllDelete($role['id']);
                ClientServicesLM::supplierClientServicessAllDelete($role['id']);
                SuppliersLM::supplierIdDelete($role['id']);
            } else {
                ClientServicesLM::clientServicessDelete($role['id']);
            }
        }

        if ($user->role == 'shop') {
            ShopLM::ShopIdDelete($user->shop_id);
        }

        if ($user->role == 'courier') {
            $courier = CouriersLM::getCouriersId($user->courier_id);
            $stock_balances = StockBalancesLM::getStockBalances();
            $balance = $stock_balances->balance ?? 0;

            if ($courier->current_balance) {
                $new_stock_balance = $balance + $courier->current_balance;
                StockBalancesLM::updateStockBalances([
                    'balance =' . $new_stock_balance,
                    'updated_date =' . date('Y-m-d')
                ]);
            }

            CouriersLM::courierIdDelete($user->courier_id);
        }


        foreach ($legals_id as $legal_id) {
            DebtsLM::deleteAllActiveDebtUser($legal_id);
        }

        UsersLM::deleteUserId($user_id);

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function changePercentage(): array
    {
        $user_id = InformationDC::get('user_id');
        $percentage = InformationDC::get('percentage');
        $user_role = InformationDC::get('user_role');


        if ($user_role == 'client') {
            $builder = Clients::newQueryBuilder()
                ->update([
                    'percentage =' . $percentage
                ])
                ->where([
                    'user_id =' . $user_id
                ]);

            PdoConnector::execute($builder);
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function changeRestrictedAccess(): array
    {
        $user_id = InformationDC::get('user_id');
        $restricted_access = VariablesDC::get('restricted_access');

        $restricted_access = [
            'unlimited' => 0,
            'limitation' => 1,
        ][$restricted_access];

        $result_update = UsersLM::updateUserEstrictedAccess($user_id, $restricted_access);

        if (!$result_update) {
            return ApiViewer::getErrorBody(['value' => 'bad_update']);
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function testDellDb(): array
    {
        die();
        $builder = CompanyFinances::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = BankOrder::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = UploadedDocuments::newQueryBuilder()->delete();

        PdoConnector::execute($builder);

        $builder = Transactions::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = CreditCards::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = LegalEntities::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = Debts::newQueryBuilder()->delete();
        PdoConnector::execute($builder);


        $builder = EndOfDaySettlement::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = TaskPlanner::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = MutualSettlement::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = SupplierBalance::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = StatementLog::newQueryBuilder()->delete();
        PdoConnector::execute($builder);

        $builder = StockBalances::newQueryBuilder()->update([
            'balance =' . 0,
            'leasing_balance =' . 0
        ])->where([
            'id =' . 1
        ]);
        PdoConnector::execute($builder);


        //Logger::log(print_r($user, true), 'bindAccountSupplier');


        return ApiViewer::getOkBody([
            'success' => 'ok',
        ]);
    }

}
