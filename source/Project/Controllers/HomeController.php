<?php

namespace Source\Project\Controllers;

use Source\Base\Core\DataContainer;
use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\ManagersLM;
use Source\Project\LogicManagers\LogicPdoModel\StatementLogLM;
use Source\Project\LogicManagers\LogicPdoModel\StockBalancesLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TaskPlannerLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\UsersLM;
use Source\Project\Models\StatementLog;


class HomeController extends BaseController
{
    /**
     * @return string
     * @throws \Exception
     */
    public function homePage(): string
    {
        $user = InformationDC::get('user');
        $home_page_role = $user['role'] . 'HomePage';

        if (method_exists($this, $home_page_role)) {
            return $this->$home_page_role();
        }

        return $this->authPage();
    }

    public function authPage(): string
    {
        //Logger::log(print_r('tet', true), 'bindAccountSupplier');
        return $this->twig->render('AuthPage.twig');
    }

    public function adminHomePage(): string
    {
        $unknown_accounts = LegalEntitiesLM::getEntitiesNulCount();
        $pending_orders = BankOrderLM::getBankOrderCountPending();
        $finance = LegalEntitiesLM::getEntitiesBalance();
        $client_services = DebtsLM::getDebtsClientServicesCount();
        $stock_balances = StockBalancesLM::getStockBalances()->balance ?? 0;
        $our_accounts = LegalEntitiesLM::getEntitiesOurAccount();
        $confirmation = CompanyFinancesLM::confirmationCostsCourier();
        $task_planner = TaskPlannerLM::getAllTaskPlan();
        $legal_entitie = LegalEntitiesLM::getNonOurCompanies();
        $error_uploads = StatementLogLM::getStatementLogStatusError();
        $mutual_settlements = [];

        if ($finance['company_client_services_debt'] > 0) {
            $mutual_settlements = array_merge($mutual_settlements, ClientServicesLM::getClientServicesDebitCompany());
        }

        if ($finance['company_сlient_debt'] > 0) {
            $mutual_settlements = array_merge($mutual_settlements, ClientsLM::getClientsDebitCompany());
        }

        if ($finance['company_supplier_debt'] > 0) {
            $mutual_settlements = array_merge($mutual_settlements, SuppliersLM::getSuppliersDebitCompany());
        }


        Logger::log(print_r($error_uploads, true), 'adminHomePage');

        //var_dump($balance);

        return $this->twig->render('AdminHomePage.twig', [
            'unknown_accounts' => $unknown_accounts,
            'pending_orders' => $pending_orders,
            'finance' => $finance,
            'client_services' => $client_services,
            'stock_balances' => $stock_balances,
            'our_accounts' => $our_accounts,
            'confirmation_costs_courier' => $confirmation['courier_expense'] ?? [],
            'return_debit_courier' => $confirmation['return_debit_courier'] ?? [],
            'courier_income_other' => $confirmation['courier_income_other'] ?? [],
            'debt_repayment_companies_supplier' => $confirmation['debt_repayment_companies_supplier'] ?? [],
            'task_planner' => $task_planner,
            'legal_entitie' => $legal_entitie,
            'mutual_settlements' => $mutual_settlements,
            'error_uploads' => $error_uploads,
            'role' => 'admin'
        ]);
    }

    public function courierHomePage(): string
    {
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierByUserId($user['id']);

        return $this->twig->render('Courier/CourierHome.twig', [
            'courier' => $courier,
            'role' => 'courier'
        ]);
    }

    public function supplierHomePage(): string
    {
        $user = InformationDC::get('user');
        if ($user['restricted_access'] === 1){
            return $this->supplierHomeRestrictedAccessPage();
        }

        $supplier = UsersLM::getUserSupplier($user['id']);
        $no_managers = LegalEntitiesLM::getLegalNoManagers($supplier->suppliers_id);
        $supplier_users = ManagersLM::getManagersOrAll($supplier->suppliers_id);
        $supplier_companies = LegalEntitiesLM::getLegalSupplierCompany($supplier->suppliers_id);
        $supplier_debts = DebtsLM::getDebtSupplierPage($supplier->suppliers_id);
        $not_accepted = CompanyFinancesLM::getFinancesSumNotAcceptedSupplier($supplier->suppliers_id);


        return $this->twig->render('Supplier/SupplierHome.twig', [
            'supplier' => $user,
            'supplier_users' => $supplier_users,
            'no_managers' => $no_managers,
            'companies' => $supplier_companies,
            'debts' => $supplier_debts,
            'stock_balance' => $supplier->stock_balance ?? 0,
            'not_accepted' => $not_accepted,
            'role' => 'supplier'
        ]);
    }

    public function shopHomePage(): string
    {
        $user = InformationDC::get('user');
        $user_shop = UsersLM::getUserShop($user['id']);
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 60;
        $offset = $page * $limit;
        $shop_id = $user_shop->shop_id ?? 0;

        if (!$user_shop) {
            return $this->twig->render('Shop/ErrorPage.twig');
        }

        $transactions = TransactionsLM::getEntitiesShopTransactions(
            $shop_id,
            $offset,
            $limit,
            $date_from,
            $date_to
        );

        $transactions_sum = TransactionsLM::getEntitiesShopTransactionsSum(
            $shop_id,
            $date_from,
            $date_to
        );

        $transactions_count = TransactionsLM::getEntitiesShopTransactionsCount(
            $shop_id,
            $date_from,
            $date_to
        );
        $page_count = ceil($transactions_count / $limit);

        //Logger::log(print_r($user, true), 'supplier_companies');

        return $this->twig->render('Shop/ShopReceiptsDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'shop_name' => $user_shop->name ?? '',
            'page_count' => $page_count,
            'shop_id' => $shop_id,
        ]);
    }

    public function supplierHomeRestrictedAccessPage(): string
    {
        $user = InformationDC::get('user');
        $supplier = UsersLM::getUserSupplier($user['id']);
        $supplier_companies = LegalEntitiesLM::getLegalSupplierCompany($supplier->suppliers_id);
        $supplier_debts = DebtsLM::getDebtSupplierPage($supplier->suppliers_id);
        $couriers = CouriersLM::getCouriersAll();

        Logger::log(print_r($user, true), 'supplier_users');

        return $this->twig->render('Supplier/SupplierHomeRestrictedAccess.twig', [
            'supplier' => $user,
            'companies' => $supplier_companies,
            'debts' => $supplier_debts,
            'stock_balance' => $supplier->stock_balance ?? 0,
            'couriers' => $couriers,
            'role' => 'supplier_restricted_access'
        ]);
    }
}
