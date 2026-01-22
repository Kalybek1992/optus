<?php

namespace Source\Project\Controllers;


use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\LogicManagers\HtmlLM\HtmlLM;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
use Source\Project\LogicManagers\LogicPdoModel\CreditCardsLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\ExpenseCategoriesLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\StockBalancesLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\UploadedLogLM;
use Source\Project\LogicManagers\LogicPdoModel\UsersLM;
use Source\Project\DataContainers\InformationDC;
use Source\Project\Viewer\ApiViewer;
use DateTime;

class EntitiesController extends BaseController
{
    public function unknownAccounts(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 10;
        $offset = $page * $limit;
        $get_entities_null = LegalEntitiesLM::getEntitiesNull($offset, $limit);


        $expenses_count = LegalEntitiesLM::getEntitiesNulCount();
        $page_count = ceil($expenses_count / $limit);

        $users = UsersLM::geUsers();
        $users_arr = [];
        foreach ($users as $user) {
            $users_arr[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ];
        }

        if (!$get_entities_null) {
            return $this->twig->render('Entities/UnknownNot.twig');
        }

        //Logger::log(print_r($get_entities_null, true), 'unknownAccounts');

        return $this->twig->render('Entities/UnknownAccounts.twig', [
            'entities' => $get_entities_null,
            'users' => $users_arr,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function unlinkAccount(): array
    {
        $legal_id = InformationDC::get('legal_id');
        $legal = LegalEntitiesLM::getEntitiesId($legal_id);

        if (!$legal) {
            return ApiViewer::getErrorBody(['error' => 'not_legal']);
        }

        $client_debt_suppliers = DebtsLM::getDebtsFromClientDebtSuppliers($legal_id);

        if ($client_debt_suppliers) {
            return ApiViewer::getErrorBody(['error' => 'client_debt_suppliers']);
        }

        DebtsLM::deleteAllActiveDebtUser($legal_id);

        $result = LegalEntitiesLM::updateLegalEntities([
            'our_account =' . 0,
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

        //Logger::log(print_r($legal_id, true), 'unlinkAccount');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function linkExpenses(): string
    {
        $legal_id = InformationDC::get('legal_id');
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();
        $entities = LegalEntitiesLM::getEntitiesNullLegalId($legal_id);

        //Logger::log(print_r($entities, true), 'linkExpenses');

        if (!$entities) {
            return $this->twig->render('Entities/UnknownExpenses.twig');
        }


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }


        return $this->twig->render('Entities/LinkExpenses.twig', [
            'entities' => $entities,
            'categories_html' => $categories_html,
        ]);
    }

    public function linkExpensesCourier(): string
    {
        $legal_id = InformationDC::get('legal_id');
        $entities = LegalEntitiesLM::getEntitiesNullLegalId($legal_id);
        $cards = [];
        $legal_id_card = null;

        if (!$entities) {
            return $this->twig->render('Entities/UnknownExpenses.twig');
        }

        if ($entities[0]["legal_id_card"] ?? false) {
            $legal_id_card = $entities[0]["legal_id_card"];
            $cards = CreditCardsLM::getAllLegalCardNumber($legal_id_card);
        }

        $couriers = CouriersLM::getCouriersAll();

        return $this->twig->render('Entities/LinkExpensesCourier.twig', [
            'entities' => $entities,
            'legal_id' => $legal_id_card,
            'cards' => $cards,
            'couriers' => $couriers,
        ]);
    }

    public function addExpenses(): array
    {
        $legal_id = InformationDC::get('legal_id');
        $category = InformationDC::get('category');
        $comment = InformationDC::get('comment');
        $insert_company_finances = [];
        $insert_bank_order = [];
        $get_entities = LegalEntitiesLM::getEntitiesId($legal_id);

        if (!$get_entities) {
            return ApiViewer::getErrorBody(['value' => 'bad_legal_id']);
        }

        $transactions = TransactionsLM::getToAccountId($legal_id);
        $bank_order_id_max = BankOrderLM::getBankOrderMaxId();

        if (!$transactions) {
            return ApiViewer::getErrorBody(['value' => 'no_transaction']);
        }

        foreach ($transactions as $transaction) {
            $bank_order_id_max += 1;

            if ($transaction->type == 'expense') {
                $insert_company_finances[] = [
                    'order_id' => $bank_order_id_max,
                    'transaction_id' => $transaction->id,
                    'category' => $category,
                    'comments' => $comment,
                    'type' => 'expense',
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

    public function addExpensesOrder(): array
    {
        $order_id = InformationDC::get('order_id');
        $category = InformationDC::get('category');
        $comment = InformationDC::get('comment');
        $auto_detection = InformationDC::get('auto_detection') ?? 0;
        $get_order = BankOrderLM::getBankOrder($order_id);

        if (!$get_order) {
            return ApiViewer::getErrorBody(['value' => 'bad_order_id']);
        }

        $translation_max_id = TransactionsLM::getTranslationMaxId();


        if ($auto_detection) {
            $get_order_description =
                BankOrderLM::getBankOrderAllDescription($get_order->description);

            $insert_transaction = [];
            $insert_company_finances = [];

            foreach ($get_order_description as $order_description) {
                $translation_max_id++;
                $insert_transaction[] = [
                    'id' => $translation_max_id,
                    'type' => 'expense',
                    'amount' => $order_description->amount,
                    'date' => $order_description->date,
                    'description' => $order_description->description,
                    'from_account_id' => $order_description->from_account_id,
                    'status' => 'processed',
                ];

                $insert_company_finances[] = [
                    'category' => $category,
                    'comments' => $comment,
                    'type' => 'expense',
                    'order_id' => $order_id,
                    'transaction_id' => $translation_max_id,
                    'status' => 'processed',
                ];

                BankOrderLM::updateBankOrder([
                    'status = processed',
                    'auto_detection = 1'
                ], $order_description->id);
            }

            TransactionsLM::insertNewTransactions($insert_transaction);
            CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);

            return ApiViewer::getOkBody(['success' => 'ok']);
        }

        $insert_transaction = [
            'id' => $translation_max_id + 1,
            'type' => 'expense',
            'amount' => $get_order->amount,
            'date' => $get_order->date,
            'description' => $get_order->description,
            'from_account_id' => $get_order->from_account_id,
            'status' => 'processed',
        ];

        $insert_company_finances = [
            'category' => $category,
            'comments' => $comment,
            'type' => 'expense',
            'order_id' => $order_id,
            'transaction_id' => $translation_max_id + 1,
            'status' => 'processed',
        ];

        TransactionsLM::insertNewTransactions($insert_transaction);
        CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);


        BankOrderLM::updateBankOrder([
            'status = processed'
        ], $get_order->id);


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function legalEntities(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 30;
        $offset = $page * $limit;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');

        $legal_id = InformationDC::get('legal_id');

        $entities = LegalEntitiesLM::getEntitiesId($legal_id);
        $transactions = TransactionsLM::getFromAccountId($legal_id, $offset, $limit, $date_from, $date_to);

        if (!$entities) {
            return $this->twig->render('Entities/NoAccount.twig');
        }

        $transactions_count = TransactionsLM::getFromAccountIdCount($legal_id);
        $page_count = ceil($transactions_count / $limit);

        $entities = [
            'user_name' => $entities->user_name,
            'user_role' => $entities->user_role,
            'inn' => $entities->inn,
            'bank_account' => $entities->bank_account,
            'bank_name' => $entities->bank_name,
            'company_name' => $entities->company_name,
            'balance' => $entities->balance,
            'percent' => $entities->supplier_percentage ?? $entities->client_percentage,
        ];


        return $this->twig->render('Entities/LegalEntities.twig', [
            'page' => $page + 1,
            'entities' => $entities,
            'transactions' => $transactions,
            'page_count' => $page_count,
        ]);
    }

    public function unknownBankOrder(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $limit = 10;
        $offset = $page * $limit;
        $pending_bank_orders = BankOrderLM::getBankOrders($offset, $limit);


        if (!$pending_bank_orders) {
            return $this->twig->render('Entities/UnknownNot.twig');
        }

        $expenses_count = BankOrderLM::getBankOrderCountPending();
        $page_count = ceil($expenses_count / $limit);


        return $this->twig->render('Entities/UnknownBankOrder.twig', [
            'bank_orders' => $pending_bank_orders,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function linkExpensesOrder(): string
    {
        $order_id = InformationDC::get('order_id');
        $get_bank_order = BankOrderLM::getBankOrderId($order_id);
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();

        if (!$get_bank_order) {
            return $this->twig->render('Entities/UnknownExpenses.twig');
        }


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevels($get_categories);
        } else {
            $categories_html = HtmlLM::renderCategoryNot();
        }


        return $this->twig->render('Entities/LinkExpensesOrder.twig', [
            'bank_orders' => $get_bank_order,
            'categories_html' => $categories_html,
        ]);
    }

    public function withdrawingOrder(): array
    {
        $order_id = InformationDC::get('order_id');
        $card_id = InformationDC::get('card_id');
        $comment = InformationDC::get('comment');
        $courier_id = InformationDC::get('courier_id');
        $purpose = InformationDC::get('purpose');
        $get_order = BankOrderLM::getBankOrder($order_id);
        $translation_max_id = TransactionsLM::getTranslationMaxId();
        $insert_company_finances = [];
        $insert_transaction = [];

        if (!$get_order) {
            return ApiViewer::getErrorBody(['value' => 'bad_order_id']);
        }

        if ($purpose == 'courier') {

            $credit_card = CreditCardsLM::getCardId($card_id);
            $courier = CouriersLM::getCouriersId($courier_id);

            if (!$credit_card) {
                return ApiViewer::getErrorBody(['value' => 'bad_credit_cards']);
            }

            if (!$courier) {
                return ApiViewer::getErrorBody(['value' => 'bad_courier_id']);
            }

            $insert_company_finances = [
                'comments' => $comment,
                'type' => 'courier_balances',
                'order_id' => $order_id,
                'transaction_id' => $translation_max_id + 1,
                'courier_id' => $courier->id,
                'card_id' => $credit_card->id,
                'status' => 'confirm_courier'
            ];

            $insert_transaction = [
                'id' => $translation_max_id + 1,
                'type' => 'income',
                'amount' => $get_order->amount,
                'date' => $get_order->date,
                'description' => $get_order->description,
                'from_account_id' => $get_order->from_account_id,
                'status' => 'processed'
            ];
        }

        if ($purpose == 'stock_balances') {
            $credit_card = CreditCardsLM::getCardId($card_id);
            $stock_balances = StockBalancesLM::getStockBalances();

            if (!$credit_card) {
                return ApiViewer::getErrorBody(['value' => 'bad_credit_cards']);
            }
            if (!$stock_balances) {
                return ApiViewer::getErrorBody(['value' => 'bad_stock_balances']);
            }

            $insert_company_finances = [
                'comments' => $comment,
                'type' => 'stock_balances',
                'order_id' => $order_id,
                'transaction_id' => $translation_max_id + 1,
                'card_id' => $credit_card->id,
                'status' => 'processed'
            ];

            $insert_transaction = [
                'id' => $translation_max_id + 1,
                'type' => 'income',
                'amount' => $get_order->amount,
                'date' => $get_order->date,
                'description' => $get_order->description,
                'from_account_id' => $get_order->from_account_id,
                'status' => 'processed'
            ];

            $new_stock_balance = $stock_balances->balance + $get_order->amount;

            StockBalancesLM::updateStockBalances([
                'balance =' . $new_stock_balance,
                'updated_date =' . date('Y-m-d')
            ]);
        }

        TransactionsLM::insertNewTransactions($insert_transaction);
        CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);


        BankOrderLM::updateBankOrder([
            'status = processed'
        ], $get_order->id);

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function linkRemovalOrder(): string
    {
        $order_id = InformationDC::get('order_id');
        $get_bank_order = BankOrderLM::getBankOrderId($order_id);


        if (!$get_bank_order) {
            return $this->twig->render('Entities/UnknownExpenses.twig');
        }

        $legal_id = $get_bank_order[0]['legal_id'];
        $cards = CreditCardsLM::getAllLegalCardNumber($legal_id);
        $couriers = CouriersLM::getCouriersAll();


        return $this->twig->render('Entities/LinkRemovalOrder.twig', [
            'bank_order' => $get_bank_order[0],
            'cards' => $cards,
            'couriers' => $couriers,
            'legal_id' => $legal_id
        ]);
    }

    public function getOurEntities(): string
    {
        $companies = LegalEntitiesLM::getEntitiesOurAccount();

        //Logger::log(print_r($companies, true), 'getOurEntities');

        return $this->twig->render('Entities/ListCompanies.twig', [
            'companies' => $companies,
        ]);
    }

    public function archiveOfExtracts(): string
    {
        $date = InformationDC::get('date');
        $our_accounts = UploadedLogLM::getEntitiesOurAccountDate($date);


        //Logger::log(print_r($companies, true), 'getOurEntities');

        return $this->twig->render('Entities/OurAccounts.twig', [
            'our_accounts' => $our_accounts,
            'date' => $date
        ]);
    }

    public function definitionCommodityMoney(): array
    {
        $delivery_type = InformationDC::get('delivery_type');
        $selected_id = InformationDC::get('selected_id');
        $amount = InformationDC::get('amount');
        $comments = InformationDC::get('comments');
        $category_path = InformationDC::get('category_path');
        $date = InformationDC::get('date');
        $insert_company_finances = [];
        $translation_max_id = TransactionsLM::getTranslationMaxId();
        $user = InformationDC::get('user');
        $expense = InformationDC::get('expense');

        $status_company_finances = 'processed';
        $type_company_finances = 'stock_balances';
        $description = '';
        $courier_id = null;
        $courier_balance = 0;
        $dt = DateTime::createFromFormat('d.m.Y', $date);
        $issue_date = $dt->format('Y-m-d');

        if ($user['role'] == 'courier' && $delivery_type == 'client') {
            $status_company_finances = 'confirm_admin';
            $type_company_finances = 'return_debit_courier';
        }

        if ($user['role'] == 'courier'){
            $courier = CouriersLM::getCourierByUserId($user['id']);
            $courier_id = $courier['id'] ?? null;
            $balance = $courier['balance_sum'] ?? 0;
            $courier_balance = $balance - $amount;
        }

        if ($delivery_type == 'admin') {
            $description = 'Перевод на администратора товарного баланса.';

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'comments' => $comments,
                'courier_id' => $courier_id,
                'type' => 'stock_balances',
                'status' => 'confirm_admin',
                'issue_date' => $issue_date
            ];

            CouriersLM::adjustCurrentBalance($courier_id, $courier_balance);
        }

        if ($delivery_type == 'expense') {
            $type = $expense == 'leasing_balance' ? 'expense_leasing_balance' : 'expense_stock_balances';
            $description = $expense == 'leasing_balance' ? 'Расход лизинг денег.' : 'Расход товарных денег.';

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'category' => $category_path,
                'comments' => $comments,
                'type' => $type,
                'status' => 'processed',
                'issue_date' => $issue_date
            ];
        }

        if ($delivery_type == 'courier') {
            $courier = CouriersLM::getCouriersId($selected_id);

            if (!$courier) return ApiViewer::getErrorBody(['value' => 'bad_courier']);

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'courier_id' => $selected_id,
                'comments' => $comments,
                'type' => $type_company_finances,
                'status' => 'confirm_courier',
                'issue_date' => $issue_date
            ];

            $description = 'Перевод на курьера товарных денег.';

            if ($courier_id) {
                $insert_company_finances['sender_courier_id'] = $courier_id;
                CouriersLM::adjustCurrentBalance($courier_id, $courier_balance);
            }
        }

        if ($delivery_type == 'supplier_leasing') {
            $supplier = SuppliersLM::getSuppliersId($selected_id);
            if (!$supplier) return ApiViewer::getErrorBody(['value' => 'bad_courier']);

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'supplier_id' => $selected_id,
                'comments' => $comments,
                'type' => 'debt_leasing',
                'status' => 'confirm_supplier',
                'issue_date' => $issue_date
            ];
            $description = 'Перевод поставщику на лизинг.';
        }

        if ($delivery_type == 'client') {
            $client = ClientsLM::getClientId($selected_id);

            if (!$client) return ApiViewer::getErrorBody(['value' => 'bad_client']);
            if (!$client['legal_id']) return ApiViewer::getErrorBody(['value' => 'bad_client_legal_entities']);

            $description = 'Перевод клиенту товарных денег.';

            $insert_company_finances = [
                'transaction_id' => $translation_max_id + 1,
                'client_id' => $selected_id,
                'comments' => $comments,
                'type' => $type_company_finances,
                'status' => $status_company_finances,
                'issue_date' => $issue_date
            ];

            if ($courier_id) {
                $insert_company_finances['courier_id'] = $courier_id;
                CouriersLM::adjustCurrentBalance($courier_id, $courier_balance);
            }else{
                DebtsLM::payOffClientsDebt(
                    $client['legal_id'],
                    $amount,
                    $translation_max_id + 1
                );
            }
        }


        if (!$insert_company_finances) {
            return ApiViewer::getErrorBody(['value' => 'bad_add_stock_balances']);
        }

        if (!$courier_id) {
            $balances = StockBalancesLM::getStockBalances();

            if ($expense == 'leasing_balance') {
                $new_balance = $balances->leasing_balance - $amount;
                StockBalancesLM::updateStockBalances([
                    'leasing_balance =' . $new_balance,
                    'updated_date=' . date('Y-m-d')
                ]);
            }else{
                $new_balance = $balances->balance - $amount;
                StockBalancesLM::updateStockBalances([
                    'balance =' . $new_balance,
                    'updated_date =' . date('Y-m-d')
                ]);
            }
        }

        TransactionsLM::insertNewTransactions([
            'id' => $translation_max_id + 1,
            'type' => 'internal_transfer',
            'amount' => $amount,
            'date' => date('Y-m-d H:i:s'),
            'description' => $description,
            'status' => 'processed'
        ]);

        CompanyFinancesLM::insertTransactionsExpenses($insert_company_finances);
        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function returnAccount(): array
    {
        $order_id = InformationDC::get('order_id');
        $bank_order = BankOrderLM::getBankOrderReturn($order_id);
        $balances = InformationDC::get('balances');
        $sum_amout = 0;

        if (!$bank_order) {
            return ApiViewer::getErrorBody(['error' => 'not_bank_order']);
        }

        $bank_order_all = BankOrderLM::getBankOrderRecipientCompanyInn($bank_order->recipient_inn, $bank_order->account);
        $entities_max_id = LegalEntitiesLM::getLegalEntitiesMaxId();

        LegalEntitiesLM::setNewLegalEntitie([
            'id' => $entities_max_id + 1,
            'inn' => $bank_order->recipient_inn,
            'account' => $bank_order->account,
            'bank_name' => $bank_order->recipient_bank_name,
            'company_name' => $bank_order->recipient_company_name,
        ]);

        foreach ($bank_order_all as $order) {
            $sum_amout = $sum_amout + $order->amount;
            $transaction_id = $order->transaction_id ?? false;

            if ($transaction_id){
                TransactionsLM::updateTransactionsId([
                    'to_account_id =' . $entities_max_id + 1
                ], $order->transaction_id);

                if ($balances == 'courier') {
                    $fin = CompanyFinancesLM::getFinancesOrderId($order->id);

                    if ($fin) {
                        if ($fin->type == 'courier_balances' && $fin->status == 'processed' && $fin->courier_id) {
                            $courier = CouriersLM::getCourierCourierId($fin->courier_id);
                            $new_balanse = $courier['balance_sum'] - $order->amount;

                            CouriersLM::updateCouriers([
                                'current_balance =' . $new_balanse,
                            ],  $fin->courier_id);
                        }
                    }
                }

                CompanyFinancesLM::deleteOrderId($order->id);
                BankOrderLM::deleteBankOrderRecipientId($order->id);
            }
        }

        if ($balances == 'сompany') {
            $stock_balances = StockBalancesLM::getStockBalances();
            $stock_balances = $stock_balances->balance - $sum_amout;

            StockBalancesLM::updateStockBalances([
                'balance=' . $stock_balances,
                'updated_date=' . date('Y-m-d')
            ]);
        }

        if ($balances == 'leasing'){
            $leasing_balance = StockBalancesLM::getStockBalances();
            $leasing_balance = $leasing_balance->leasing_balance - $sum_amout;

            StockBalancesLM::updateStockBalances([
                'leasing_balance =' . $leasing_balance,
                'updated_date =' . date('Y-m-d')
            ]);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }
}
