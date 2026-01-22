<?php

namespace Source\Project\Controllers;

use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\LogicManagers\HtmlLM\HtmlLM;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
use Source\Project\LogicManagers\LogicPdoModel\CreditCardsLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\ExpenseCategoriesLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\ShopLM;
use Source\Project\LogicManagers\LogicPdoModel\StockBalancesLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\UsersLM;
use Source\Project\Viewer\ApiViewer;


class TransactionController extends BaseController
{
    /**
     * @return string
     * @throws \Exception
     */
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
        $old_profit = $transaction->interest_income;
        $new_percent = $percent;
        $new_profit = $transfer_amount * ($new_percent / 100);


        TransactionsLM::updateTransactionsId([
            'interest_income =' . $new_profit,
            'percent =' . $new_percent,
        ], $transaction_id);


        DebtsLM::editDebtTransactionId($transaction_id, $transfer_amount - $new_profit);

        //Logger::log(print_r("Возврат старой прибыли: $old_profit", true), 'changePercentage');
        //Logger::log(print_r("Новый процент: $new_percent%", true), 'changePercentage');
        //Logger::log(print_r("Новая прибыль: $new_profit", true), 'changePercentage');
        //Logger::log(print_r("--------------------------------------------", true), 'changePercentage');


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
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();

        $expenses = CompanyFinancesLM::getExpenses(
            $offset,
            $limit,
            $category,
            $date_from,
            $date_to,
            'company'
        );
        $expenses_count = CompanyFinancesLM::getTranslationExpensesCount(
            $category,
            $date_from,
            $date_to,
            'company'
        );
        $page_count = ceil($expenses_count / $limit);


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }

        return $this->twig->render('Transaction/GetExpenses.twig', [
            'page' => $page + 1,
            'expenses' => $expenses,
            'categories_html' => $categories_html,
            'page_count' => $page_count,
        ]);
    }

    public function getTransferYourself(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;


        $transactions = TransactionsLM::getEntitiesOurTransactions(
            $offset,
            $limit,
            $date_from,
            $date_to
        );

        $transactions_sum = TransactionsLM::getEntitiesOurTransactionsSum(
            $date_from,
            $date_to
        );

        $transactions_count = TransactionsLM::getEntitiesOurTransactionsCount(
            $date_from,
            $date_to
        );
        $page_count = ceil($transactions_count / $limit);


        return $this->twig->render('Transaction/GetTransferYourself.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'page_count' => $page_count,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);
    }

    public function getExpensesStockBalances(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $category = InformationDC::get('category');
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $leasing = InformationDC::get('leasing');
        $limit = 30;
        $offset = $page * $limit;

        if ($leasing == 1){
            $type = 'leasing';
        }else{
            $type = 'stock_balances';
        }

        $expenses = CompanyFinancesLM::getExpenses(
            $offset,
            $limit,
            $category,
            $date_from,
            $date_to,
            $type
        );
        $expenses_count = CompanyFinancesLM::getTranslationExpensesCount(
            $category,
            $date_from,
            $date_to,
            $type
        );
        $page_count = ceil($expenses_count / $limit);


        //Logger::log(print_r($expenses, true), 'getExpenses');
        return $this->twig->render('Transaction/GetExpensesStockBalances.twig', [
            'page' => $page + 1,
            'expenses' => $expenses,
            'page_count' => $page_count,
        ]);
    }

    public function createTransaction(): string
    {
        // Получаем списки из базы
        $our_accounts = LegalEntitiesLM::getEntitiesOurAccount();
        $suppliers = SuppliersLM::getSuppliersAll();
        $couriers = CouriersLM::getCouriersAll();
        $clients = ClientsLM::getClients();
        $companies = LegalEntitiesLM::getNonOurCompanies();

        return $this->twig->render('Transaction/CreateTransaction.twig', [
            'our_accounts' => $our_accounts,
            'suppliers' => $suppliers,
            'couriers' => $couriers,
            'clients' => $clients,
            'companies' => $companies,
        ]);
    }

    public function returnTransaction(): string
    {
        $our_accounts = LegalEntitiesLM::getEntitiesOurAccount();
        $suppliers = SuppliersLM::getSuppliersAll();
        $couriers = CouriersLM::getCouriersAll();
        $clients = ClientsLM::getClients();
        $companies = LegalEntitiesLM::getNonOurCompanies();

        return $this->twig->render('Transaction/ReturnTransaction.twig', [
            'our_accounts' => $our_accounts,
            'suppliers' => $suppliers,
            'couriers' => $couriers,
            'clients' => $clients,
            'companies' => $companies,
        ]);
    }

    public function getCourierFinances(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $category = InformationDC::get('category');
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $courier_id = InformationDC::get('courier_id');
        $limit = 30;
        $offset = $page * $limit;

        $get_courier_finances = CompanyFinancesLM::getCourierFinances($courier_id, $offset, $limit, $category, $date_from, $date_to);
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();
        $get_categories_project = ExpenseCategoriesLM::getExpenseCategories(null, 1);

        $expenses_count = CompanyFinancesLM::getCourierFinancesCount($courier_id, $category, $date_from, $date_to);
        $page_count = ceil($expenses_count / $limit);
        $courier = CouriersLM::getCourierCourierId($courier_id);


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }

        if ($get_categories_project) {
            $project_html = HtmlLM::renderCategoryLevels($get_categories_project);
        } else {
            $project_html = HtmlLM::renderCategoryNot();
        }


        return $this->twig->render('Transaction/GetCourierFinances.twig', [
            'page' => $page + 1,
            'courier' => $courier,
            'get_courier_finances' => $get_courier_finances,
            'categories_html' => $categories_html,
            'project_html' => $project_html,
            'page_count' => $page_count,
        ]);
    }

    public function clientReceiptsDate(): string
    {
        $client_id = InformationDC::get('client_id') ?? 0;
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;


        $client = ClientsLM::getClientId($client_id);
        $transactions = LegalEntitiesLM::getEntitiesClientTransactions(
            $client_id,
            $offset,
            $limit,
            $date_from,
            $date_to
        );
        $transactions_sum = LegalEntitiesLM::getEntitiesClientTransactionsSum(
            $client_id,
            $date_from,
            $date_to
        );

        $transactions_count = LegalEntitiesLM::getEntitiesClientTransactionsCount($client_id, $date_from, $date_to);
        $page_count = ceil($transactions_count / $limit);

        //Logger::log(print_r($transactions_count, true), 'transactions_count');
        //Logger::log(print_r($transactions, true), 'clientReceiptsDate');

        return $this->twig->render('Transaction/ClientReceiptsDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'client' => $client,
            'page_count' => $page_count,
        ]);
    }

    public function clientServicesReceiptsDate(): string
    {
        $client_id = InformationDC::get('client_id') ?? 0;
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;
        $client = ClientServicesLM::clientServicesId($client_id);
        $supplier_id = $client['supplier_id'] ?? 0;


        $transactions = LegalEntitiesLM::getEntitiesClientServicesTransactions(
            $supplier_id,
            $offset,
            $limit,
            $date_from,
            $date_to,
            null,
            $client_id,
        );
        $transactions_sum = LegalEntitiesLM::getEntitiesClientServicesTransactionsSum(
            $supplier_id,
            $date_from,
            $date_to,
            null,
            $client_id,
        );

        $transactions_count = LegalEntitiesLM::getEntitiesClientServicesTransactionsCount(
            $supplier_id,
            $date_from,
            $date_to,
            null,
            $client_id,
        );
        $page_count = ceil($transactions_count / $limit);


        return $this->twig->render('Transaction/ClientServicesReceiptsDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'client' => $client,
            'page_count' => $page_count,
        ]);
    }

    public function suppliersSendingsDate(): string
    {
        $supplier_id = InformationDC::get('supplier_id') ?? 0;
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;

        $supplier = SuppliersLM::getSuppliersIdDebt($supplier_id);
        $transactions = LegalEntitiesLM::getEntitiesSuppliersTransactions(
            $supplier_id,
            $offset,
            $limit,
            $date_from,
            $date_to
        );
        $transactions_sum = LegalEntitiesLM::getEntitiesSuppliersTransactionsSum(
            $supplier_id,
            $date_from,
            $date_to
        );

        $transactions_count = LegalEntitiesLM::getEntitiesSuppliersTransactionsCount(
            $supplier_id,
            $date_from,
            $date_to
        );
        $page_count = ceil($transactions_count / $limit);



        return $this->twig->render('Transaction/SuppliersSendingsDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'supplier' => $supplier,
            'page_count' => $page_count,
        ]);
    }

    public function setStockBalances(): array
    {
        $entity_id = InformationDC::get('entity_id');
        $leasing = InformationDC::get('leasing') ?? false;
        $legal_entities = LegalEntitiesLM::getEntitiesId($entity_id);
        $stock_balances = StockBalancesLM::getStockBalances();
        $insert_bank_order = [];
        $insert_company_finances = [];
        $new_balance = $leasing ? $stock_balances->leasing_balance : $stock_balances->balance;

        if (!$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_entity_id']);
        }

        $transactions = TransactionsLM::getToAccountId($entity_id);
        $bank_order_id_max = BankOrderLM::getBankOrderMaxId();

        if (!$transactions) {
            return ApiViewer::getErrorBody(['value' => 'not_transaction']);
        }

        foreach ($transactions as $transaction) {
            $bank_order_id_max += 1;
            $insert_company_finances[] = [
                'order_id' => $bank_order_id_max,
                'transaction_id' => $transaction->id,
                'type' => $leasing ? 'leasing' : 'stock_balances',
                'status' => 'processed'
            ];

            $insert_bank_order[] = [
                'id' => $bank_order_id_max,
                'type' => 'expense',
                'amount' => $transaction->amount,
                'date' => $transaction->date,
                'from_account_id' => $transaction->from_account_id,
                'transaction_id' => $transaction->id,
                'description' => $transaction->description,
                'recipient_company_name' => $legal_entities->company_name,
                'recipient_bank_name' => $legal_entities->bank_name,
                'recipient_inn' => $legal_entities->inn,
                'account' => $legal_entities->account,
                'status' => 'processed',
                'return_account' => 1
            ];

            TransactionsLM::updateTransactionsId([
                'to_account_id = ' . '<NULL>',
            ], $transaction->id);

            $new_balance += $transaction->amount;
        }

        LegalEntitiesLM::deleteLegalEntitiesId($entity_id);

        if ($leasing){
            StockBalancesLM::updateStockBalances([
                'leasing_balance =' . $new_balance,
                'updated_date =' . date('Y-m-d')
            ]);
        }else{
            StockBalancesLM::updateStockBalances([
                'balance =' . $new_balance,
                'updated_date =' . date('Y-m-d')
            ]);
        }


        if ($insert_bank_order && $insert_company_finances) {
            BankOrderLM::insertNewBankOrder($insert_bank_order);
            CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);
        }

        return ApiViewer::getOkBody([
            'success' => 'ok',
            'new_stock_balance' => number_format($new_balance, 2),
        ]);
    }

    public function setYourself(): array
    {
        $legal_id = InformationDC::get('entity_id');
        $legal_entities = LegalEntitiesLM::getEntitiesId($legal_id);

        if (!$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_entity_id']);
        }


        $result = LegalEntitiesLM::updateLegalEntities([
            'our_account =' . 1,
            'client_id = ' . '<NULL>',
            'supplier_id =' . '<NULL>',
            'shop_id =' . '<NULL>',
            'manager_id =' . '<NULL>',
            'supplier_client_id =' . '<NULL>',
            'client_service_id =' . '<NULL>',
            'client_services =' . 0,
            'percent =' . 0,
        ], $legal_id);

        if (!$result) {
            return ApiViewer::getErrorBody(['value' => 'bad_unlink_account']);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    /**
     * Handle POST request to create a new transaction
     */
    public function storeTransaction(): array
    {
        try {
            // --- Получаем все параметры из формы ---
            $from_account_raw = trim((string)InformationDC::get('from_account'));
            $to_account_id = (int)InformationDC::get('to_account');
            $user_raw = trim((string)InformationDC::get('user_id'));
            $amount = (float)InformationDC::get('amount');
            $percent = (float)InformationDC::get('percent');
            $receipt_date = trim((string)InformationDC::get('date_received'));
            $description = trim((string)InformationDC::get('description'));

            if (
                !$from_account_raw ||
                !$to_account_id ||
                !$user_raw ||
                $amount <= 0 ||
                !$receipt_date
            ) {
                return ApiViewer::getErrorBody(['value' => 'invalid_parameters']);
            }

            // --- Парсим `from_account` (client_123) ---
            [$from_type, $from_id] = explode('_', $from_account_raw) + [null, null];
            $from_id = (int)$from_id;
            $company = LegalEntitiesLM::getLegalEntitieById($from_id);
            $var_exist = ['client_id', 'supplier_id', 'client_services'];
            $value = null;

            foreach ($var_exist as $key) {
                if (isset($company[0]->variables[$key])) {
                    $name = explode('_', $key)[0];
                    $id = $company[0]->variables[$key];
                    $value = $name . '_' . $id;
                }
            }
            [$from_type_name, $from_type_id] = explode('_', $value);
            $from_account_id = match ($from_type_name) {
                'client' => LegalEntitiesLM::getEntityIdByClient($from_type_id),
                'supplier' => LegalEntitiesLM::getEntityIdBySupplier($from_type_id),
                'courier' => LegalEntitiesLM::getEntityIdByCourier($from_type_id),
                default => null,
            };

            if (!$from_account_id) {
                return ApiViewer::getErrorBody(['value' => 'invalid_sender']);
            }

            // --- Парсим `user_id` (supplier_5) ---
            [$user_type, $user_id] = explode('_', $user_raw) + [null, null];
            $user_id = (int)$user_id;

            // --- Рассчитываем процентный доход ---
            $interest_income = round($amount * $percent / 100, 2);
            $total_amount = $amount + $interest_income;

            // --- Подготавливаем данные для вставки ---
            $transactionData = [
                'type' => 'income',
                'amount' => $amount,
                'percent' => $percent,
                'interest_income' => $interest_income,
                'date' => date('Y-m-d H:i:s'),
                'date_received' => $receipt_date,
                'description' => $description,
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'status' => 'processed'
            ];

            TransactionsLM::insertNewTransactions([$transactionData]);

            return ApiViewer::getOkBody(['success' => true]);

        } catch (\Exception $e) {
            Logger::log('Transaction error: ' . $e->getMessage(), 'transaction_error');
            return ApiViewer::getErrorBody(['value' => 'transaction_failed']);
        }
    }

    public function storeReturnTransaction(): array
    {
        try {
            $from_account_id = (int)InformationDC::get('from_account');
            $to_account_raw = trim((string)InformationDC::get('to_account'));
            $user_raw = trim((string)InformationDC::get('user_id'));

            $amount = (float)InformationDC::get('amount');
            $receipt_date = trim((string)InformationDC::get('date_received'));
            $description = trim((string)InformationDC::get('description'));

            if ($from_account_id <= 0 || $amount <= 0 || !$receipt_date || !$to_account_raw) {
                return ApiViewer::getErrorBody(['value' => 'invalid_parameters']);
            }

            $to_account_id = 0;
            if (ctype_digit($to_account_raw)) {
                $to_account_id = (int)$to_account_raw;
            } else {
                [$to_type, $to_id] = explode('_', $to_account_raw) + [null, null];
                $to_id = (int)$to_id;
                $to_account_id = match ($to_type) {
                    'client' => LegalEntitiesLM::getEntityIdByClient($to_id),
                    'supplier' => LegalEntitiesLM::getEntityIdBySupplier($to_id),
                    'courier' => LegalEntitiesLM::getEntityIdByCourier($to_id),
                    default => null,
                } ?? 0;
            }

            if ($to_account_id <= 0) {
                return ApiViewer::getErrorBody(['value' => 'invalid_recipient']);
            }

            [$user_type, $user_id] = explode('_', $user_raw) + [null, null];
            $user_id = (int)$user_id;

            \Source\Project\Connectors\PdoConnector::beginTransaction();
            try {
                TransactionsLM::insertNewTransactions([[
                    'type' => 'return', // <--- ВАЖНО: используйте корректный тип!
                    'amount' => $amount,
                    'date' => date('Y-m-d H:i:s'),
                    'date_received' => $receipt_date,
                    'description' => $description,
                    'from_account_id' => $from_account_id,
                    'to_account_id' => $to_account_id,
                    'user_id' => $user_id,
                    'status' => 'processed'
                ]]);

                BankAccountsLM::updateBankAccounts(
                    ['balance = balance - ' . $amount],
                    $from_account_id
                );
                BankAccountsLM::updateBankAccounts(
                    ['balance = balance + ' . $amount],
                    $to_account_id
                );

                \Source\Project\Connectors\PdoConnector::commit();
                return ApiViewer::getOkBody(['success' => true]);
            } catch (\Throwable $e) {
                \Source\Project\Connectors\PdoConnector::rollback();
                Logger::log('Return transaction error: ' . $e->getMessage(), 'return_transaction_error');
                return ApiViewer::getErrorBody(['value' => 'transaction_failed']);
            }

        } catch (\Throwable $e) {
            Logger::log('Return transaction error: ' . $e->getMessage(), 'return_transaction_error');
            return ApiViewer::getErrorBody(['value' => 'transaction_failed']);
        }
    }

    public function distributionCommodityMoney(): string
    {
        $couriers = CouriersLM::getCouriersAll();
        $clients = ClientsLM::getClientsAll();
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();
        $stock_balances = StockBalancesLM::getStockBalances()->balance ?? 0;
        $suppliers = SuppliersLM::getSuppliersAllNoLeasing();

        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }

        return $this->twig->render('Transaction/DistributionCommodityMoney.twig', [
            'suppliers' => $suppliers,
            'couriers' => $couriers,
            'clients' => $clients,
            'categories_html' => $categories_html,
            'stock_balances' => $stock_balances,
        ]);
    }

    public function wasteOfLease(): string
    {
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();
        $leasing_balance = StockBalancesLM::getStockBalances()->leasing_balance ?? 0;
        $suppliers = SuppliersLM::getSuppliersAllNoLeasing();

        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }

        return $this->twig->render('Transaction/WasteOfLease.twig', [
            'suppliers' => $suppliers,
            'categories_html' => $categories_html,
            'leasing_balance' => $leasing_balance,
        ]);
    }

    public function supplierReports(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $supplier_id = InformationDC::get('supplier_id') ?? null;
        $limit = 30;
        $offset = $page * $limit;
        $suppliers = SuppliersLM::getSuppliersAll();
        $user_name = '';

        if (!$supplier_id) {
            return $this->twig->render('Transaction/SupplierReports.twig', [
                'suppliers' => $suppliers,
            ]);
        }


        foreach ($suppliers as $supplier) {
            if ($supplier['supplier_id'] == $supplier_id){
                $user_name = $supplier['username'];
            }
        }

        $transactions = LegalEntitiesLM::getEntitiesSuppliersTransactions($supplier_id, $offset, $limit, $date_from, $date_to);
        $debt_up_to_this_point = LegalEntitiesLM::getSupplierGoodDebt($supplier_id, $date_to);


        $transactions_sum = LegalEntitiesLM::getEntitiesSuppliersTransactionsSum($supplier_id, $date_from, $date_to);
        $transactions_count = LegalEntitiesLM::getEntitiesSuppliersTransactionsCount($supplier_id, $date_from, $date_to)->count ?? 0;
        $page_count = ceil($transactions_count / $limit);
        $transit_debt = LegalEntitiesLM::getTransitDebt($supplier_id, $date_from, $date_to);
        $get_transactions_by_percentage = TransactionsLM::getTransactionsByPercentage($supplier_id, $date_from, $date_to, $transit_debt);


        //Logger::log(print_r($get_transactions_by_percentage, true), 'transactions');


        return $this->twig->render('Transaction/SupplierReports.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'suppliers' => $suppliers,
            'page_count' => $page_count,
            'transit_debt_amount' => number_format($transit_debt['transit_debt_amount'], 2, ',', ' '),
            'transit_amount' => number_format($transit_debt['transit_amount'], 2, ',', ' '),
            'debt_up_to_this_point' => number_format($debt_up_to_this_point, 2, ',', ' '),
            'transactions_by_percentage' => $get_transactions_by_percentage,
            'user_name' => $user_name,
        ]);
    }

    public function supplierScan(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $supplier_id = InformationDC::get('supplier_id') ?? null;
        $limit = 30;
        $offset = $page * $limit;
        $suppliers = SuppliersLM::getSuppliersAll();
        $user_name = '';

        if (!$supplier_id) {
            return $this->twig->render('Transaction/SupplierScan.twig', [
                'suppliers' => $suppliers,
            ]);
        }


        foreach ($suppliers as $supplier) {
            if ($supplier['supplier_id'] == $supplier_id){
                $user_name = $supplier['username'];
            }
        }

        $transactions_suppliers = LegalEntitiesLM::getEntitiesSuppliersTransactions($supplier_id, $offset, $limit, $date_from, $date_to);
        $transactions_count = LegalEntitiesLM::getEntitiesSuppliersTransactionsCount($supplier_id, $date_from, $date_to)->count ?? 0;
        $page_count = ceil($transactions_count / $limit);
        $transactions_sum_suppliers = LegalEntitiesLM::getEntitiesSuppliersTransactionsSum($supplier_id, $date_from, $date_to);
        $transit_debt = LegalEntitiesLM::getTransitDebt($supplier_id, $date_from, $date_to);


        $grouped_transactions = [];

        foreach ($transactions_suppliers as $t) {
            $id = $t['legal_id'];

            if (!isset($grouped_transactions[$id])) {
                $grouped_transactions[$id] = $t;
            } else {
                $grouped_transactions[$id]['total_amount'] += $t['total_amount'];
                $grouped_transactions[$id]['transaction_amount'] += $t['transaction_amount'];
                $grouped_transactions[$id]['interest_income'] += $t['interest_income'];
                $grouped_transactions[$id]['transaction_amount_sum'] += $t['transaction_amount_sum'];
            }
        }

        $grouped_transactions = array_values($grouped_transactions);


        $transactions_client_services = LegalEntitiesLM::getEntitiesClientServicesTransactions($supplier_id, $offset, $limit, $date_from, $date_to);
        $transactions_sum_client_services = LegalEntitiesLM::getEntitiesClientServicesTransactionsSum($supplier_id, $date_from, $date_to);

        $transactions_count_client_services = LegalEntitiesLM::getEntitiesClientServicesTransactionsCount($supplier_id, $date_from, $date_to);
        $page_count_client_services = ceil($transactions_count_client_services / $limit);

        //Logger::log(print_r($get_transactions_by_percentage, true), 'transactions');


        return $this->twig->render('Transaction/SupplierScan.twig', [
            'page' => $page + 1,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page_count' => $page_count,
            'consumption_transactions' => $transactions_suppliers,
            'coming_transactions' => $transactions_client_services,
            'suppliers' => $suppliers,
            'transactions_sum_client_services' => $transactions_sum_client_services,
            'transactions_sum_supplier' => $transactions_sum_suppliers,
            'transit_debt_amount' => number_format($transit_debt['transit_debt_amount'], 2, ',', ' '),
            'transit_amount' => number_format($transit_debt['transit_amount'], 2, ',', ' '),
            'grouped_transactions' => $grouped_transactions,
        ]);
    }

    public function setShop(): array
    {
        $entity_id = InformationDC::get('entity_id');
        $user_id = VariablesDC::get('user_id');

        $legal_entities = LegalEntitiesLM::getEntitiesId($entity_id);
        $user = UsersLM::getUserShop($user_id);

        if (!$legal_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_entity_id']);
        }

        if (!$user) {
            return ApiViewer::getErrorBody(['value' => 'bad_user_id']);
        }


        LegalEntitiesLM::updateLegalEntities([
            'client_id = ' . '<NULL>',
            'supplier_id =' . '<NULL>',
            'shop_id = ' . $user->shop_id,
            'our_account =' . 0,
        ], $legal_entities->id);


        //Logger::log(print_r($new_stock_balance, true), 'setStockBalances');
        return ApiViewer::getOkBody([
            'success' => 'ok'
        ]);
    }

    public function shopReceiptsDate(): string
    {
        $shop_id = InformationDC::get('shop_id') ?? 0;
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;

        $shop = ShopLM::getShopId($shop_id);

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

        //Logger::log(print_r($transactions_sum, true), 'clientReceiptsDate');


        return $this->twig->render('Transaction/ShopReceiptsDate.twig', [
            'page' => $page + 1,
            'transactions' => $transactions,
            'transactions_sum' => $transactions_sum,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'shop' => $shop,
            'page_count' => $page_count,
        ]);
    }

    public function sendingCourier(): array
    {
        $legal_id = InformationDC::get('legal_id');
        $card_id = InformationDC::get('card_id');
        $comment = InformationDC::get('comment');
        $courier_id = InformationDC::get('courier_id');
        $insert_company_finances = [];
        $insert_bank_order = [];

        $get_entities = LegalEntitiesLM::getEntitiesId($legal_id);
        $credit_card = CreditCardsLM::getCardId($card_id);
        $courier = CouriersLM::getCouriersId($courier_id);

        if (!$get_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_legal_id']);
        }
        if (!$credit_card) {
            return ApiViewer::getErrorBody(['value' => 'bad_credit_cards']);
        }
        if (!$courier) {
            return ApiViewer::getErrorBody(['value' => 'bad_courier_id']);
        }

        $transactions = TransactionsLM::getToAccountId($legal_id);
        $bank_order_id_max = BankOrderLM::getBankOrderMaxId();

        foreach ($transactions as $transaction) {
            $bank_order_id_max += 1;

            if ($transaction->type == 'expense') {
                $insert_company_finances[] = [
                    'order_id' => $bank_order_id_max,
                    'comments' => $comment,
                    'type' => 'courier_balances',
                    'transaction_id' => $transaction->id,
                    'card_id' => $credit_card->id,
                    'courier_id' => $courier->id,
                    'status' => 'confirm_courier'
                ];

                $insert_bank_order[] = [
                    'id' => $bank_order_id_max,
                    'type' => 'sending_by_courier',
                    'amount' => $transaction->amount,
                    'date' => $transaction->date,
                    'from_account_id' => $transaction->from_account_id,
                    'transaction_id' => $transaction->id,
                    'description' => $transaction->description,
                    'recipient_company_name' => $get_entities->company_name,
                    'recipient_bank_name' => $get_entities->bank_name,
                    'recipient_inn' => $get_entities->inn,
                    'account' => $get_entities->account,
                    'status' => 'processed',
                    'return_account' => 1
                ];

                TransactionsLM::updateTransactionsId([
                    'to_account_id =' . '<NULL>',
                ], $transaction->id);
            }
        }

        if ($insert_bank_order && $insert_company_finances) {
            BankOrderLM::insertNewBankOrder($insert_bank_order);
            CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);
        }

        LegalEntitiesLM::deleteLegalEntitiesId($legal_id);
        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function mutualSettlement(): array
    {
        $role_id = InformationDC::get('role_id');
        $amount = InformationDC::get('amount');
        $role = InformationDC::get('role');
        $user_debit = InformationDC::get('user_debit');
        $translation_max_id = TransactionsLM::getTranslationMaxId();
        $insert_company_finances = [];
        $legals_id = false;

        if (!$user_debit) {
            return ApiViewer::getErrorBody(['value' => 'bad_user_debit']);
        }

        if ($user_debit['debit_amount'] < $amount || $user_debit['company_debit_amount'] < $amount) {
            return ApiViewer::getErrorBody(['value' => 'bad_amount']);
        }

        if ($role == 'supplier') {
            $supplier = SuppliersLM::getSupplierIdLegal($role_id);

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Закрытие долга поставщика при займе расчёте.',
                'status' => 'processed'
            ]);

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'supplier_id' => $role_id,
                'comments' => 'Закрытие долга поставщика при займе расчёте.',
                'type' => 'debt_repayment_transaction',
                'status' => 'confirm_admin'
            ];

            DebtsLM::payOffCompaniesDebt(
                $supplier['legal_id'],
                $amount,
                $translation_max_id + 1
            );

            $legals_id = $supplier['legal_id'];
        }

        if ($role == 'client') {
            $client = ClientsLM::getClientId($role_id);

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Взаиморасчеты клиентам.',
                'status' => 'processed'
            ]);

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'client_id' => $role_id,
                'comments' => 'Взаиморасчеты клиентам.',
                'type' => 'debt_repayment_transaction',
                'status' => 'processed',
            ];

            DebtsLM::payOffClientsDebt(
                $client['legal_id'],
                $amount,
                $translation_max_id + 1
            );

            $legals_id = $client['legal_id'];
        }

        if ($role == 'client_services') {
            $client_services = ClientServicesLM::clientServicesId($role_id);

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'internal_transfer',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Взаиморасчеты клиент услуги.',
                'status' => 'processed'
            ]);

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'client_services_id' => $role_id,
                'comments' => 'Взаиморасчеты клиент услуги.',
                'type' => 'debt_repayment_transaction',
                'status' => 'confirm_admin',
            ];


            DebtsLM::payOffClientServicesDebt(
                $client_services['legal_id'],
                $amount,
                $translation_max_id + 1,
            );

            $legals_id = $client_services['legal_id'];
        }


        if (!$insert_company_finances) {
            return ApiViewer::getErrorBody(['value' => 'bad_add_stock_balances']);
        }


        DebtsLM::mutualSettlementsDebts(
            $legals_id,
            $amount,
            $translation_max_id + 1,
        );

        CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);
        Logger::log(print_r($user_debit, true), 'mutualSettlement');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }
}
