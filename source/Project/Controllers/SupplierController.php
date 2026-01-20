<?php

namespace Source\Project\Controllers;

use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\HtmlLM\HtmlLM;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\EndOfDaySettlementLM;
use Source\Project\LogicManagers\LogicPdoModel\ExpenseCategoriesLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\ManagersLM;
use Source\Project\LogicManagers\LogicPdoModel\ReportsLM;
use Source\Project\LogicManagers\LogicPdoModel\StockBalancesLM;
use Source\Project\LogicManagers\LogicPdoModel\SupplierBalanceLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\UsersLM;
use Source\Project\Viewer\ApiViewer;
use DateTime;
use Source\Base\Core\Logger;

class SupplierController extends BaseController
{
    public function addUserPage(): string
    {
        return $this->twig->render('Supplier/AddUsers.twig');
    }

    public function addUser(): array
    {
        $repeat = InformationDC::get('repeat');
        $name = InformationDC::get('name');
        $suplier = InformationDC::get('suplier');
        $role = InformationDC::get('role');
        $percent = InformationDC::get('percent');
        $transit_rate = InformationDC::get('transit_rate');
        $cash_bet = InformationDC::get('cash_bet');

        if ($repeat) {
            return ApiViewer::getErrorBody(['value' => 'repeat_name_manager']);
        }

        $token = bin2hex(random_bytes(32 / 2));

        UsersLM::insertNewUser([
            'name' => $name,
            'token' => $token,
            'role' => $role,
        ]);

        $user = UsersLM::getUserToken($token);

        if (!$user) {
            return ApiViewer::getErrorBody(['value' => 'error_insert_user']);
        }

        if ($role == 'manager' && $transit_rate && $cash_bet) {
            ManagersLM::insertNewManagers([
                'user_id' => $user->id,
                'current_balance' => 0,
                'transit_rate' => $transit_rate,
                'cash_bet' => $cash_bet,
                'supplier_id' => $suplier['supplier_id'],
                'last_update' => date('Y-m-d H:i:s')
            ]);
        }

        if ($role == 'client' && $percent) {
            ClientsLM::insertNewClients([
                'user_id' => $user->id,
                'percentage' => $percent,
                'supplier_id' => $suplier['supplier_id'],
            ]);
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function linkUserLegal(): array
    {
        $legal_id = InformationDC::get('legal_id');
        $role = InformationDC::get('role');
        $role_id = InformationDC::get('role_id');
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'];

        $get_entities = LegalEntitiesLM::getEntitiesId($legal_id);

        if (!$get_entities || $get_entities->manager_id || $supplier_id != $get_entities->supplier_id) {
            return ApiViewer::getErrorBody(['value' => 'no_legal_id']);
        }

        if ($role == 'manager') {
            $manager = ManagersLM::getManagerId($role_id);

            if (!$manager) {
                return ApiViewer::getErrorBody(['value' => 'no_manager']);
            }


            LegalEntitiesLM::updateLegalEntities([
                'manager_id =' . $role_id,
            ], $get_entities->id);

            $transactions_min_date = TransactionsLM::getTransactionsMinDate($legal_id);

            if ($transactions_min_date) {
                $update_end_of_day[] = [
                    'manager_id' => $role_id,
                    'supplier_id' => $supplier_id,
                    'date' => $transactions_min_date,
                ];
                EndOfDaySettlementLM::updateEndOfDayTransactions($update_end_of_day);
            }

            return ApiViewer::getOkBody(['success' => 'ok']);
        } else {
            $client = ClientsLM::getClientId($role_id);
            $debts = [];

            if (!$client) {
                return ApiViewer::getErrorBody(['value' => 'no_client']);
            }

            $transactions = TransactionsLM::getTransactionFromOrToAccountId(
                $get_entities->id,
                $get_entities->id
            );

            foreach ($transactions as $transaction) {
                $from_account_id = $transaction->from_account_id;
                $to_account_id = $transaction->to_account_id;
                $percent = $client['percentage'];

                $percent_income = abs($transaction->amount) * ($percent / 100);

                if ($transaction->type == 'income') {
                    $debts[] = [
                        'from_account_id' => $from_account_id,
                        'to_account_id' => $to_account_id,
                        'transaction_id' => $transaction->id,
                        'type_of_debt' => 'сlient_debt_supplier',
                        'amount' => $transaction->amount - $percent_income,
                        'date' => date('Y-m-d'),
                        'status' => 'active'
                    ];
                }
            }

            if ($debts) {
                DebtsLM::setNewDebts($debts);
            }

            LegalEntitiesLM::updateLegalEntities([
                'supplier_client_id =' . $role_id,
            ], $get_entities->id);
        }


        //Logger::log(print_r($get_entities, true), 'get_entities');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function distributeAmount(): array
    {
        $balance_id = InformationDC::get('balance_id');
        $manager_id = InformationDC::get('manager_id');
        $suplier = InformationDC::get('suplier');
        $comment = InformationDC::get('comment');
        $amount = InformationDC::get('amount');
        $date = InformationDC::get('date');
        $date_obj = DateTime::createFromFormat('d.m.Y', $date);
        $supplier_id = $suplier['supplier_id'];


        $get_balance = SupplierBalanceLM::getSupplierBalanceId($balance_id);
        $manager = ManagersLM::getManagerId($manager_id);

        if (!$get_balance) {
            return ApiViewer::getErrorBody(['value' => 'no_legal_id']);
        }

        if (!$manager) {
            return ApiViewer::getErrorBody(['value' => 'no_manager']);
        }

        $legal = LegalEntitiesLM::getEntitiesInn($get_balance->sender_inn);

        $translation_max_id = TransactionsLM::getTranslationMaxId();
        TransactionsLM::insertNewTransactions([
            'id' => $translation_max_id + 1,
            'type' => 'internal_transfer',
            'amount' => $amount,
            'date' => $date_obj->format('Y-m-d H:i:s'),
            'description' => 'Отгрузка для менеджера - ' . $manager->username,
            'from_account_id' => $legal->id,
            'status' => 'processed'
        ]);

        CompanyFinancesLM::insertTransactionsExpenses([
            'type' => 'shipping_manager',
            'manager_id' => $manager_id,
            'transaction_id' => $translation_max_id + 1,
            'comments' => $comment,
            'status' => 'processed',
        ]);

        $update_end_of_day[] = [
            'manager_id' => $manager_id,
            'supplier_id' => $supplier_id,
            'date' => $date_obj->format('Y-m-d'),
        ];

        EndOfDaySettlementLM::updateEndOfDayTransactions($update_end_of_day);

        $new_balance = $get_balance->amount - $amount;
        SupplierBalanceLM::updateSupplierBalance([
            ' amount =' .  $new_balance,
        ], $balance_id);


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function movedCash(): array
    {
        $manager_id = InformationDC::get('manager_id');
        $suplier = InformationDC::get('suplier');
        $comment = InformationDC::get('comment');
        $amount = InformationDC::get('amount');
        $manager = ManagersLM::getManagerId($manager_id);
        $stock_balance = $suplier['stock_balance'] ?? 0;
        $date = InformationDC::get('date');
        $date_obj = DateTime::createFromFormat('d.m.Y', $date);
        $supplier_id = $suplier['supplier_id'];

        if (!$manager) {
            return ApiViewer::getErrorBody(['value' => 'no_manager']);
        }

        $translation_max_id = TransactionsLM::getTranslationMaxId();

        TransactionsLM::insertNewTransactions([
            'id' => $translation_max_id + 1,
            'type' => 'internal_transfer',
            'amount' => $amount,
            'date' => $date_obj->format('Y-m-d H:i:s'),
            'description' => 'Перенести кэш - ' . $manager->username,
            'status' => 'processed'
        ]);

        CompanyFinancesLM::insertTransactionsExpenses([
            'type' => 'moved_cash',
            'manager_id' => $manager_id,
            'transaction_id' => $translation_max_id + 1,
            'comments' => $comment,
            'status' => 'processed',
        ]);


        $update_end_of_day[] = [
            'manager_id' => $manager_id,
            'supplier_id' => $supplier_id,
            'date' => $date_obj->format('Y-m-d'),
        ];

        EndOfDaySettlementLM::updateEndOfDayTransactions($update_end_of_day);

        SuppliersLM::updateSuppliers([
            'stock_balance =' . $stock_balance + $amount,
        ], $suplier['supplier_id']);

        //Logger::log(print_r($new_balance, true), 'movedCash');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function createReturnManager(): array
    {
        $balance_id = InformationDC::get('balance_id');
        $manager_id = InformationDC::get('manager_id');
        $suplier = InformationDC::get('suplier');
        $comment = InformationDC::get('comment');
        $amount = InformationDC::get('amount');
        $return_type = InformationDC::get('return_type');
        $date = InformationDC::get('date');
        $date_obj = DateTime::createFromFormat('d.m.Y', $date);
        $supplier_id = $suplier['supplier_id'];

        $get_balance = SupplierBalanceLM::getSupplierBalanceId($balance_id);
        $manager = ManagersLM::getManagerId($manager_id);

        if (!$get_balance) {
            return ApiViewer::getErrorBody(['value' => 'no_legal_id']);
        }

        if (!$manager) {
            return ApiViewer::getErrorBody(['value' => 'no_manager']);
        }

        $recipient = LegalEntitiesLM::getEntitiesInn($get_balance->recipient_inn);
        $sender = LegalEntitiesLM::getEntitiesInn($get_balance->sender_inn);

        $translation_max_id = TransactionsLM::getTranslationMaxId();
        TransactionsLM::insertNewTransactions([
            'id' => $translation_max_id + 1,
            'type' => 'internal_transfer',
            'amount' => $amount,
            'date' => $date_obj->format('Y-m-d H:i:s'),
            'description' => 'Возврат отгрузки менеджера - ' . $manager->username,
            'from_account_id' => $recipient->id,
            'to_account_id' => $sender->id,
            'status' => 'processed'
        ]);

        CompanyFinancesLM::insertTransactionsExpenses([
            'type' => 'shipping_return',
            'manager_id' => $manager_id,
            'transaction_id' => $translation_max_id + 1,
            'comments' => $comment,
            'return_type' => $return_type,
            'status' => 'processed',
        ]);

        $update_end_of_day[] = [
            'manager_id' => $manager_id,
            'supplier_id' => $supplier_id,
            'date' => $date_obj->format('Y-m-d'),
        ];
        EndOfDaySettlementLM::updateEndOfDayTransactions($update_end_of_day);

        if ($return_type == 'cash') {
            $balance = $get_balance->amount;
            $new_balance = $balance + $amount;
            SupplierBalanceLM::updateSupplierBalance([
                'amount =' .  $new_balance,
            ], $balance_id);
        }

        if ($return_type == 'wheel') {
            $balance = $get_balance->stock_balance;
            $new_balance = $balance + $amount;
            SupplierBalanceLM::updateSupplierBalance([
                'stock_balance =' .  $new_balance,
            ], $balance_id);
        }

        //Logger::log(print_r($new_balance, true), 'distributeAmount');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function clientServicesManager(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $suplier = InformationDC::get('suplier');
        $manager_id = InformationDC::get('manager_id');
        $limit = 30;
        $offset = $page * $limit;
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $manager = ManagersLM::getManagerId($manager_id);
        $supplier_companies = LegalEntitiesLM::getLegalSupplierCompany($supplier_id);

        if (!$manager) {
            return $this->twig->render('Supplier/ErrorPage.twig');
        }

        $transactions = LegalEntitiesLM::getEntitiesClientServicesTransactions(
            $supplier_id,
            $offset,
            $limit,
            $date_from,
            $date_to,
            $manager_id
        );

        $transactions_sum = LegalEntitiesLM::getEntitiesClientServicesTransactionsSum(
            $supplier_id,
            $date_from,
            $date_to,
            $manager_id
        );

        $transactions_count = LegalEntitiesLM::getEntitiesClientServicesTransactionsCount(
            $supplier_id,
            $date_from,
            $date_to,
            $manager_id
        );


        $page_count = ceil($transactions_count / $limit);
        //Logger::log(print_r($date_from, true), 'clientServicesManager');

        return $this->twig->render('Supplier/CientServicesManager.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'manager' => $manager->username,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page_count' => $page_count,
            'supplier_companies' => $supplier_companies,
        ]);
    }

    public function deliveredGoods(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $suplier = InformationDC::get('suplier');
        $manager_id = InformationDC::get('manager_id');
        $legal_id = InformationDC::get('legal_id');
        $limit = 30;
        $offset = $page * $limit;
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $manager = ManagersLM::getManagerId($manager_id);
        $supplier_companies = LegalEntitiesLM::getLegalSupplierCompany($supplier_id);

        if (!$manager) {
            return $this->twig->render('Supplier/ErrorPage.twig');
        }

        $get_manager_finances = CompanyFinancesLM::getManagerFinances(
            $manager_id,
            $offset,
            $limit,
            $date_from,
            $date_to,
            'shipping_manager',
            $legal_id
        );
        $manager_finances_count = CompanyFinancesLM::getManagerFinancesCount(
            $manager_id,
            $date_from,
            $date_to,
            'shipping_manager',
            $legal_id
        )->count ?? 0;
        $page_count = ceil($manager_finances_count / $limit);

        //Logger::log(print_r($get_manager_finances, true), 'deliveredGoods');

        return $this->twig->render('Supplier/DeliveredGoods.twig', [
            'page' => $page + 1,
            'manager_finances' => $get_manager_finances,
            'manager' => $manager->username,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page_count' => $page_count,
            'supplier_companies' => $supplier_companies,
        ]);
    }

    public function getMovedCash(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $suplier = InformationDC::get('suplier');
        $manager_id = InformationDC::get('manager_id');
        $limit = 30;
        $offset = $page * $limit;
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $manager = ManagersLM::getManagerId($manager_id);

        if (!$manager) {
            return $this->twig->render('Supplier/ErrorPage.twig');
        }

        $get_manager_finances = CompanyFinancesLM::getManagerFinances(
            $manager_id,
            $offset,
            $limit,
            $date_from,
            $date_to,
            'moved_cash',
        );
        $manager_finances_count = CompanyFinancesLM::getManagerFinancesCount(
            $manager_id,
            $date_from,
            $date_to,
            'moved_cash',
        )->count ?? 0;
        $page_count = ceil($manager_finances_count / $limit);

        //Logger::log(print_r($get_manager_finances, true), 'deliveredGoods');

        return $this->twig->render('Supplier/GetMovedCash.twig', [
            'page' => $page + 1,
            'manager_finances_cash' => $get_manager_finances,
            'manager' => $manager->username,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page_count' => $page_count,
        ]);
    }

    public function returnGoods(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $suplier = InformationDC::get('suplier');
        $manager_id = InformationDC::get('manager_id');
        $limit = 30;
        $offset = $page * $limit;
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $manager = ManagersLM::getManagerId($manager_id);
        $legal_id = InformationDC::get('legal_id');
        $supplier_companies = LegalEntitiesLM::getLegalSupplierCompany($supplier_id);

        if (!$manager) {
            return $this->twig->render('Supplier/ErrorPage.twig');
        }

        $get_manager_finances = CompanyFinancesLM::getManagerFinances(
            $manager_id,
            $offset,
            $limit,
            $date_from,
            $date_to,
            'shipping_return',
            $legal_id
        );

        $manager_finances_count = CompanyFinancesLM::getManagerFinancesCount(
            $manager_id,
            $date_from,
            $date_to,
            'shipping_return',
            $legal_id
        )->count ?? 0;

        $page_count = ceil($manager_finances_count / $limit);


        return $this->twig->render('Supplier/ReturnGoods.twig', [
            'page' => $page + 1,
            'manager_finances' => $get_manager_finances,
            'manager' => $manager->username,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page_count' => $page_count,
            'supplier_companies' => $supplier_companies,
        ]);
    }

    public function stockBalanceCommodity(): string
    {
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $stock_balance = $suplier['stock_balance'] ?? 0;

        $clients = ClientsLM::getClientsAll($supplier_id);
        $get_categories = ExpenseCategoriesLM::getExpenseCategories($supplier_id);
        $couriers = CouriersLM::getCouriersAll();


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }


        return $this->twig->render('Supplier/StockBalanceCommodity.twig', [
            'clients' => $clients,
            'categories_html' => $categories_html,
            'stock_balances' => $stock_balance,
            'couriers' => $couriers,
        ]);
    }

    public function saveProductBalance(): array
    {
        $finances_id = InformationDC::get('finances_id');
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $supplier_stock_balance = $suplier['stock_balance'] ?? 0;
        $finances = CompanyFinancesLM::getReturnTypeWheelId($finances_id);

        if (!$finances) {
            return ApiViewer::getErrorBody(['value' => 'no_finances']);
        }

        $supplier_stock_balance = $supplier_stock_balance + $finances->amount;
        $bank_accounts_stock_balance = $finances->stock_balance - $finances->amount;

        SuppliersLM::updateSuppliers([
            'stock_balance =' . $supplier_stock_balance,
        ], $supplier_id);

        SupplierBalanceLM::updateSupplierBalance([
            'stock_balance =' .  $bank_accounts_stock_balance,
        ], $finances->supplier_balance_id);

        CompanyFinancesLM::updateCompanyFinancesId([
            'return_type =' . 'return_wheel',
        ], $finances->id);

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function definitionCommodityMoney(): array
    {
        $delivery_type = InformationDC::get('delivery_type');
        $selected_id = InformationDC::get('selected_id');
        $amount = InformationDC::get('amount');
        $comments = InformationDC::get('comments');
        $category_path = InformationDC::get('category_path');
        $date = InformationDC::get('date');
        $translation_max_id = TransactionsLM::getTranslationMaxId();
        $leasing = InformationDC::get('leasing');

        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $stock_balance = $suplier['stock_balance'] ?? 0;

        $dt = DateTime::createFromFormat('d.m.Y', $date);
        $issue_date = $dt->format('Y-m-d');

        if ($delivery_type == 'expense') {
            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Расход товарных денег поставщика.',
                'status' => 'processed'
            ]);

            CompanyFinancesLM::insertTransactionsExpenses([
                'transaction_id' => $translation_max_id + 1,
                'category' => $category_path,
                'comments' => $comments,
                'type' => 'expense_stock_balances_supplier',
                'supplier_id' => $supplier_id,
                'issue_date' => $issue_date,
                'status' => 'processed'
            ]);
        }

        if ($delivery_type == 'client') {
            $client = ClientsLM::getClientSupplierId($selected_id, $supplier_id);

            if (!$client || !$client['legal_id']) {
                return ApiViewer::getErrorBody(['value' => 'bad_client']);
            }

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Перевод товарных денег для закрытия долга поставщиком клиенту.',
                'status' => 'processed'
            ]);

            CompanyFinancesLM::insertTransactionsExpenses([
                'transaction_id' => $translation_max_id + 1,
                'client_id' => $selected_id,
                'supplier_id' => $supplier_id,
                'comments' => $comments,
                'issue_date' => $issue_date,
                'type' => 'debt_repayment_client_supplier',
                'status' => 'processed'
            ]);

            DebtsLM::payOffSupplierClientServicesDebt(
                $client['legal_id'],
                $amount,
                $translation_max_id + 1
            );
        }

        if ($delivery_type == 'debt') {
            $supplier = SuppliersLM::getSupplierIdLegal($supplier_id);

            if (!$supplier || !$supplier['legal_id']) {
                return ApiViewer::getErrorBody(['value' => 'bad_supplier']);
            }

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Закрытие долга компании поставщиком.',
                'status' => 'processed'
            ]);

            CompanyFinancesLM::insertTransactionsExpenses([
                'transaction_id' => $translation_max_id + 1,
                'supplier_id' => $supplier_id,
                'comments' => $comments,
                'issue_date' => $issue_date,
                'type' => 'debt_repayment_сompanies_supplier',
                'status' => 'confirm_admin'
            ]);
        }

        if ($delivery_type == 'courier') {
            $supplier = SuppliersLM::getSupplierIdLegal($supplier_id);
            $courier = CouriersLM::getCourierCourierId($selected_id);

            if (!$supplier || !$supplier['legal_id']) {
                return ApiViewer::getErrorBody(['value' => 'bad_supplier']);
            }

            if (!$courier) {
                return ApiViewer::getErrorBody(['value' => 'not_courier']);
            }

            $type = 'debt_repayment_сompanies_supplier';
            if ($leasing){
                $type = 'debt_leasing';
                $new_debt_leasing = $suplier['debt_leasing'] - $amount;

                SuppliersLM::updateSuppliers([
                    'debt_leasing =' . $new_debt_leasing,
                ], $supplier_id);
            }

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Закрытие долга лизинг передав курьеру. ' . $courier['name'],
                'status' => 'processed'
            ]);

            CompanyFinancesLM::insertTransactionsExpenses([
                'transaction_id' => $translation_max_id + 1,
                'supplier_id' => $supplier_id,
                'courier_id' => $courier['id'],
                'comments' => $comments,
                'issue_date' => $issue_date,
                'type' => $type,
                'status' => 'confirm_courier'
            ]);

            if (!$leasing){
                // TODO Можно убрать когда у курьера появится кнопка отказать (при принятия)
                DebtsLM::payOffCompaniesDebt(
                    $supplier['legal_id'],
                    $amount,
                    $translation_max_id + 1
                );
            }
        }

        if ($suplier['restricted_access'] == 0){
            SuppliersLM::updateSuppliers([
                'stock_balance =' . $stock_balance - $amount,
            ], $supplier_id);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function getExpenses(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $category = InformationDC::get('category');
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $get_categories = ExpenseCategoriesLM::getExpenseCategories($supplier_id);

        $expenses = CompanyFinancesLM::getExpenses(
            $offset,
            $limit,
            $category,
            $date_from,
            $date_to,
            'supplier_expense',
            $supplier_id
        );

        $expenses_count = CompanyFinancesLM::getTranslationExpensesCount(
            $category,
            $date_from,
            $date_to,
            'supplier_expense',
            $supplier_id
        );
        $page_count = ceil($expenses_count / $limit);


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }


        return $this->twig->render('Supplier/GetExpenses.twig', [
            'page' => $page + 1,
            'expenses' => $expenses,
            'categories_html' => $categories_html,
            'page_count' => $page_count,
        ]);
    }

    public function supplierClientReceiptsDate(): string
    {
        $client_id = InformationDC::get('client_id') ?? 0;
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $client = ClientsLM::getClientSupplierId($client_id, $supplier_id);

        if (!$client) {
            return $this->twig->render('Supplier/ErrorPage.twig');
        }

        $transactions = LegalEntitiesLM::getEntitiesClientTransactions(
            null,
            $offset,
            $limit,
            $date_from,
            $date_to,
            $client_id
        );

        foreach ($transactions as &$item) {
            $percent = $item['supplier_client_percent'] ?? $client['percentage'] ?? 0;

            $item['interest_income'] = round($item['total_amount'] * ($percent / 100), 2);
            $item['percent'] = $percent;
        }

        $transactions_sum = LegalEntitiesLM::getEntitiesClientTransactionsSum(
            null,
            $date_from,
            $date_to,
            $client_id,
        );

        $transactions_count = LegalEntitiesLM::getEntitiesClientTransactionsCount(
            null,
            $date_from,
            $date_to,
            $client_id,
        );
        $page_count = ceil($transactions_count / $limit);


        return $this->twig->render('Supplier/ClientReceiptsDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'client' => $client,
            'page_count' => $page_count,
        ]);
    }

    public function debtExpenses(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;

        $expenses = CompanyFinancesLM::getExpenses(
            $offset,
            $limit,
            $category = null,
            $date_from,
            $date_to,
            'supplier_debit',
            $supplier_id
        );

        //Logger::log(print_r($expenses, true), 'debtExpenses');

        $expenses_count = CompanyFinancesLM::getTranslationExpensesCount(
            $category,
            $date_from,
            $date_to,
            'supplier_debit',
            $supplier_id
        );
        $page_count = ceil($expenses_count / $limit);


        return $this->twig->render('Supplier/DebtExpenses.twig', [
            'page' => $page + 1,
            'expenses' => $expenses,
            'page_count' => $page_count,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);
    }

    public function getTransactionDate(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;

        $transactions = LegalEntitiesLM::getEntitiesSuppliersTransactions(
            $supplier_id,
            $offset,
            $limit,
            $date_from,
            $date_to, 1
        );

        $transactions_sum = LegalEntitiesLM::getEntitiesSuppliersTransactionsSum(
            $supplier_id,
            $date_from,
            $date_to,
            1
        );

        $transactions_count = LegalEntitiesLM::getEntitiesSuppliersTransactionsCount(
            $supplier_id,
            $date_from,
            $date_to,
            1
        )->count ?? 0;
        $page_count = ceil($transactions_count / $limit);

        //Logger::log(print_r($client, true), 'clientReceiptsDate');

        return $this->twig->render('Supplier/GetTransactionDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page_count' => $page_count,
        ]);
    }

    public function confirmAdmin(): array
    {
        $finances_id = InformationDC::get('finances_id');
        $status = InformationDC::get('status');
        $action_type = InformationDC::get('action_type');
        $finances = CompanyFinancesLM::getPendingById($finances_id);

        if (!$finances) {
            return ApiViewer::getErrorBody(['value' => 'no_finances']);
        }

        if ($status == 'confirm') {
            $supplier = SuppliersLM::getSupplierIdLegal($finances->supplier_id);

            CompanyFinancesLM::updateCompanyFinancesId([
                'status = ' . 'processed',
            ], $finances_id);

            TransactionsLM::updateTransactionsId([
                'status = ' . 'processed',
            ], $finances->transaction_id);

            $stock_balance = StockBalancesLM::getStockBalances();
            $new_balance = $finances->amount + $stock_balance->balance;

            DebtsLM::payOffCompaniesDebt(
                $supplier['legal_id'],
                $finances->amount,
                $finances->transaction_id
            );

            StockBalancesLM::updateStockBalances([
                'balance =' . $new_balance,
                'updated_date =' . date('Y-m-d')
            ]);
        }

        if ($status == 'cancel') {
            $supplier = SuppliersLM::getSupplierIdLegal($finances->supplier_id);
            $new_balance = $finances->amount + $supplier['stock_balance'];


            CompanyFinancesLM::deleteCompanyFinancesId($finances->id);
            TransactionsLM::deleteTransactionsId($finances->transaction_id);


            SuppliersLM::updateSuppliers([
                'stock_balance  =' . $new_balance,
            ], $finances->supplier_id);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function confirmSupplier(): array
    {
        $finances_id = InformationDC::get('finances_id');
        $status = InformationDC::get('status');
        $action_type = InformationDC::get('action_type');
        $finances = CompanyFinancesLM::getPendingByIdConfirmSupplier($finances_id);
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;

        if (!$finances) {
            return ApiViewer::getErrorBody(['value' => 'no_finances']);
        }

        if ($status == 'confirm') {
            CompanyFinancesLM::updateCompanyFinancesId([
                'status = ' . 'processed',
            ], $finances_id);

            TransactionsLM::updateTransactionsId([
                'status = ' . 'processed',
            ], $finances->transaction_id);

            $new_debt_leasing = $finances->amount + $suplier['debt_leasing'] ?? 0;

            SuppliersLM::updateSuppliers([
                'debt_leasing =' . $new_debt_leasing,
            ], $supplier_id);
        }

        if ($status == 'cancel') {
            $stock_balances = StockBalancesLM::getStockBalances();
            $new_balance = $finances->amount + $stock_balances->balance;

            CompanyFinancesLM::deleteCompanyFinancesId($finances->id);
            TransactionsLM::deleteTransactionsId($finances->transaction_id);

            StockBalancesLM::updateStockBalances([
                'balance=' . $new_balance,
                'updated_date=' . date('Y-m-d')
            ]);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function managerDailyReports(): string
    {
        $suplier = InformationDC::get('suplier');
        $manager_id = InformationDC::get('manager_id');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $manager = ManagersLM::getManagerId($manager_id);
        $date_from_raw = InformationDC::get('date') ?? date('d.m.Y');

        $date_from = $date_from_raw;
        $date_to  = $date_from;

        if (!$manager || $date_from_raw > date('d.m.Y')) {
            return $this->twig->render('Supplier/ErrorPage.twig');
        }

        $transactions = LegalEntitiesLM::getEntitiesClientServicesTransactions(
            $supplier_id,
            null,
            null,
            $date_from,
            $date_to,
            $manager_id
        );

        $get_manager_finances = CompanyFinancesLM::getManagerFinances(
            $manager_id,
            null,
            null,
            $date_from,
            $date_to,
        );

        $get_manager_finances_cash = CompanyFinancesLM::getManagerFinances(
            $manager_id,
            null,
            null,
            $date_from,
            $date_to,
            'moved_cash',
        );

        $today_report = ReportsLM::getTodayReportSupplier(
            $date_from,
            $date_to,
            $manager_id,
            $supplier_id
        );


        return $this->twig->render('Supplier/ManagerDailyReports.twig', [
            'transactions' => $transactions,
            'manager_finances' => $get_manager_finances,
            'manager_finances_cash' => $get_manager_finances_cash,
            'today_report' => $today_report,
            'manager' => $manager->username,
            'date' => $date_from,
        ]);
    }

    public function getUsersByRole(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $role = InformationDC::get('role');
        $limit = 8;
        $offset = $page * $limit;
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $users = SuppliersLM::getSuppliersUsers($role, $supplier_id, $offset, $limit);


        if (!$users) {
            return $this->twig->render('User/NoClients.twig');
        }

        $suppliers_user_count = SuppliersLM::getSuppliersUsersCount($role, $supplier_id);
        $page_count = ceil($suppliers_user_count / $limit);


        return $this->twig->render('Supplier/GetUsersByRole.twig', [
            'clients' => $users,
            'page' => $page + 1,
            'page_count' => $page_count,
            'role' => $role,
        ]);
    }

    public function unlinkAccount(): array
    {
        $legal_id = InformationDC::get('legal_id');
        $legal = LegalEntitiesLM::getEntitiesId($legal_id);
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'];

        if (!$legal) {
            return ApiViewer::getErrorBody(['error' => 'not_legal']);
        }


        DebtsLM::deleteAllActiveDebtUser($legal_id);

        $transactions_min_date = TransactionsLM::getTransactionsMinDate($legal_id);

        if ($transactions_min_date && $legal->manager_id) {
            $update_end_of_day[] = [
                'manager_id' => $legal->manager_id,
                'supplier_id' => $supplier_id,
                'date' => $transactions_min_date,
            ];
            EndOfDaySettlementLM::updateEndOfDayTransactions($update_end_of_day);
        }


        $result = LegalEntitiesLM::updateLegalEntities([
            'manager_id =' . '<NULL>',
            'supplier_client_id =' . '<NULL>',
        ], $legal_id);

        if (!$result) {
            return ApiViewer::getErrorBody(['value' => 'bad_unlink_account']);
        }

        //Logger::log(print_r($legal_id, true), 'unlinkAccount');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function changePercentage(): array
    {
        $transaction_id = InformationDC::get('transaction_id');
        $legal_id = InformationDC::get('legal_id');
        $percent = InformationDC::get('percent');


        $transaction = TransactionsLM::getTransactionEntitiesId($transaction_id);
        $legal_entities = LegalEntitiesLM::getEntitiesId($legal_id);


        if (!$transaction && !$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_transaction']);
        }

        $transfer_amount = $transaction->amount;
        $new_percent = $percent;
        $new_debit = $transfer_amount - ($transfer_amount * $new_percent / 100);


        TransactionsLM::updateTransactionsId([
            'supplier_client_percent =' . $new_percent,
        ], $transaction_id);

        DebtsLM::updateDebtsClientSupplier([
            'amount =' . $new_debit,
        ], $transaction_id);

        //Logger::log(print_r("Возврат старой прибыли: $old_profit", true), 'changePercentage');
        //Logger::log(print_r("Новый процент: $new_percent%", true), 'changePercentage');
        //Logger::log(print_r("Новая прибыль: $new_profit", true), 'changePercentage');
        //Logger::log(print_r("--------------------------------------------", true), 'changePercentage');


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function changeSettlementRates(): array
    {
        $manager_id = InformationDC::get('manager_id');
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'];
        $date_from_raw = InformationDC::get('date') ?? date('d.m.Y');
        $transit_rate = InformationDC::get('transit_rate');
        $cash_bet = InformationDC::get('cash_bet');
        $manager = ManagersLM::getManagerId($manager_id);

        if (!$manager) {
            return ApiViewer::getErrorBody(['value' => 'bad_manager']);
        }

        $update_end_of_day[] = [
            'manager_id' => $manager_id,
            'supplier_id' => $supplier_id,
            'date' => $date_from_raw,
            'transit_rate' => $transit_rate,
            'cash_bet' => $cash_bet,
        ];
        EndOfDaySettlementLM::updateEndOfDayTransactions($update_end_of_day);


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function changeRatesManager(): array
    {
        $manager_id = InformationDC::get('manager_id');
        $date_from_raw = InformationDC::get('date') ?? date('d.m.Y');
        $transit_rate = InformationDC::get('transit_rate');
        $cash_bet = InformationDC::get('cash_bet');


        $manager = ManagersLM::getManagerId($manager_id);

        if (!$manager) {
            return ApiViewer::getErrorBody(['value' => 'bad_manager']);
        }

        ManagersLM::managerUpdate([
            'transit_rate =' . $transit_rate,
            'cash_bet =' . $cash_bet,
        ], $manager_id);


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function debtLeasingReport(): string
    {
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;

        $expenses = CompanyFinancesLM::getExpenses(
            $offset,
            $limit,
            null,
            $date_from,
            $date_to,
            'debt_leasing',
            $supplier_id
        );

        $expenses_count = CompanyFinancesLM::getTranslationExpensesCount(
            null,
            $date_from,
            $date_to,
            'debt_leasing',
            $supplier_id
        );

        $page_count = ceil($expenses_count / $limit);

        return $this->twig->render('Supplier/DebtLeasingReport.twig', [
            'page' => $page + 1,
            'expenses' => $expenses,
            'page_count' => $page_count,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);
    }

}