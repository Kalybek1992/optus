<?php

namespace Source\Project\Controllers;

use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\ExpenseCategoriesLM;
use Source\Project\LogicManagers\HtmlLM\HtmlLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\Viewer\ApiViewer;
use DateTime;

class CourierController extends BaseController
{

    public function pending(): string
    {
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierByUserId($user['id']);

        $page = (int)(InformationDC::get('page') ?? 0);
        $limit = 10;
        $offset = $page * $limit;

        $pending = CompanyFinancesLM::getCourierPending($courier['id'] ?? 0, $offset, $limit);
        $total = CompanyFinancesLM::getCourierPendingCount($courier['id'] ?? 0);
        $page_count = (int)ceil($total / $limit);

        return $this->twig->render('Courier/CourierPending.twig', [
            'courier' => $courier,
            'pending' => $pending,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function confirm(): array
    {
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierByUserId($user['id']);
        $finance_id = (int)InformationDC::get('company_finances_id');

        if (!$finance_id || !$courier) {
            return ApiViewer::getErrorBody(['value' => 'bad_params']);
        }

        $row = CompanyFinancesLM::getPendingByIdForConfirm($finance_id);
        if (!$row || (int)$row->courier_id !== (int)($courier['id'] ?? 0)) {
            return ApiViewer::getErrorBody(['value' => 'not_found']);
        }

        $balance = $courier['balance_sum'] ?? 0;
        $new_balance = $balance + $row->amount;


        try {
            \Source\Project\Models\CompanyFinances::update(
                ['status = processed'],
                ['id =' . $finance_id]
            );
            CouriersLM::adjustCurrentBalance($courier['id'], $new_balance);
            return ApiViewer::getOkBody(['success' => true]);
        } catch (\Throwable $e) {
            return ApiViewer::getErrorBody(['value' => 'confirm_failed']);
        }
    }

    public function incomeOtherForm(): string
    {
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();
        $categories_html = $get_categories ? HtmlLM::renderCategoryLevels($get_categories) : HtmlLM::renderCategoryNot();

        return $this->twig->render('Courier/CourierIncomeOther.twig', [
            'categories_html' => $categories_html,
        ]);
    }

    public function storeIncomeOther(): array
    {
        $user = InformationDC::get('user');

        $courier = CouriersLM::getCourierByUserId($user['id']);
        $amount = InformationDC::get('amount');
        $category = InformationDC::get('category');
        $receipt_date = InformationDC::get('date');
        $comments = InformationDC::get('comments');
        $translation_max_id = TransactionsLM::getTranslationMaxId();
        $dt = DateTime::createFromFormat('d.m.Y', $receipt_date);
        $issue_date = $dt->format('Y-m-d');

        if (!$courier) {
            return ApiViewer::getErrorBody(['value' => 'invalid_parameters']);
        }

        $balance = $courier['balance_sum'] ?? 0;
        $new_balance = $balance + $amount;

        try {

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'courier_income',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Доход курьера из других источников ' . 'дата - ' . $receipt_date,
                'status' => 'pending'
            ]);

            CompanyFinancesLM::insertTransactionsExpenses([
                'transaction_id' => $translation_max_id + 1,
                'type' => 'courier_income_other',
                'courier_id' => $courier['id'],
                'category' => $category,
                'comments' => $comments,
                'issue_date' => $issue_date,
                'status' => 'confirm_admin',
            ]);

            CouriersLM::adjustCurrentBalance($courier['id'], $new_balance);

            return ApiViewer::getOkBody(['success' => 'ok']);

        } catch (\Throwable $e) {
            return ApiViewer::getErrorBody(['value' => 'income_other_failed']);
        }

    }

    public function expenseForm(): string
    {
        $user = InformationDC::get('user');
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();
        $categories_html = $get_categories ? HtmlLM::renderCategoryLevels($get_categories) : HtmlLM::renderCategoryNot();
        $courier = CouriersLM::getCourierByUserId($user['id']);


        return $this->twig->render('Courier/CourierExpense.twig', [
            'categories_html' => $categories_html,
            'courier' => $courier,
        ]);
    }

    public function storeExpense(): array
    {
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierByUserId($user['id']);
        $amount = (float)InformationDC::get('amount');
        $category = trim((string)InformationDC::get('category_path'));
        $comments = trim((string)InformationDC::get('comments'));
        $translation_max_id = TransactionsLM::getTranslationMaxId();

        if (!$courier) {
            return ApiViewer::getErrorBody(['value' => 'invalid_parameters']);
        }

        $balance = $courier['balance_sum'] ?? 0;
        $new_balance = $balance - $amount;


        try {

            TransactionsLM::insertNewTransactions([
                'id' => $translation_max_id + 1,
                'type' => 'courier_expense',
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'Расход курьера - ' . $courier['name'],
                'status' => 'pending'
            ]);

            CompanyFinancesLM::insertTransactionsExpenses([
                'type' => 'courier_expense',
                'courier_id' => $courier['id'],
                'transaction_id' => $translation_max_id + 1,
                'category' => $category,
                'comments' => $comments,
                'status' => 'confirm_admin',
            ]);


            CouriersLM::adjustCurrentBalance($courier['id'], $new_balance);
            return ApiViewer::getOkBody(['success' => true]);
        } catch (\Throwable $e) {
            return ApiViewer::getErrorBody(['value' => 'expense_failed']);
        }
    }

    public function debtPayoutForm(): string
    {
        $clients = ClientsLM::getClientsAll();
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierByUserId($user['id']);

        $stock_balances = $courier['balance_sum'] ?? 0;

        return $this->twig->render('Courier/CourierDebtPayout.twig', [
            'clients' => $clients,
            'stock_balances' => $stock_balances,
        ]);
    }

    public function storeDebtPayout(): array
    {
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierCourierId($user['id']);
        $client_id = (int)InformationDC::get('client_id');
        $amount = (float)InformationDC::get('amount');
        $comments = trim((string)InformationDC::get('comments'));

        if ($client_id <= 0 || $amount <= 0) {
            return ApiViewer::getErrorBody(['value' => 'invalid_parameters']);
        }

        try {
            CompanyFinancesLM::insertTransactionsExpenses([
                [
                    'type' => 'courier_debt_client',
                    'courier_id' => $courier['id'],
                    'client_id' => $client_id,
                    'comments' => $comments,
                    'status' => 'processed',
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            ]);
            CouriersLM::adjustCurrentBalance($courier['id'], -$amount);
            return ApiViewer::getOkBody(['success' => true]);
        } catch (\Throwable $e) {
            return ApiViewer::getErrorBody(['value' => 'debt_payout_failed']);
        }
    }

    public function expenseAdmin(): array
    {
        $finances_id = InformationDC::get('finances_id');
        $status = InformationDC::get('status');
        $action_type = InformationDC::get('action_type');
        $finances = CompanyFinancesLM::getPendingById($finances_id);

        if (!$finances) {
            return ApiViewer::getErrorBody(['value' => 'no_finances']);
        }

        if ($status == 'confirm'){
            $client = ClientsLM::getClientId($finances->client_id);

            DebtsLM::payOffClientsDebt(
                $client['legal_id'],
                $finances->amount,
                $finances->transaction_id
            );

            CompanyFinancesLM::updateCompanyFinancesId([
                'status = ' . 'processed',
            ], $finances_id);

            TransactionsLM::updateTransactionsId([
                'status = ' . 'processed',
            ], $finances->transaction_id);
        }

        if ($status == 'cancel'){

            if ($action_type == 'consumption'){
                $new_balance = $finances->amount + $finances->current_balance;

                CompanyFinancesLM::deleteCompanyFinancesId($finances->id);
                TransactionsLM::deleteTransactionsId($finances->transaction_id);


                CouriersLM::adjustCurrentBalance(
                    $finances->courier_id,
                    $new_balance
                );
            }

            if ($action_type == 'debt'){
                $new_balance = $finances->amount + $finances->current_balance;
                $client = ClientsLM::getClientId($finances->client_id);

                if (!$client) {
                    return ApiViewer::getErrorBody(['value' => 'bad_client']);
                }
                if (!$client['legal_id']){
                    return ApiViewer::getErrorBody(['value' => 'bad_client_legal_entities']);
                }


                CompanyFinancesLM::deleteCompanyFinancesId($finances->id);
                TransactionsLM::deleteTransactionsId($finances->transaction_id);


                CouriersLM::adjustCurrentBalance(
                    $finances->courier_id,
                    $new_balance
                );
            }
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function issueaAnotherCourier(): string
    {
        $user = InformationDC::get('user');
        $couriers = CouriersLM::getCouriersNotUser($user['id']);
        $courier = CouriersLM::getCourierByUserId($user['id']);

        $stock_balances = $courier['balance_sum'] ?? 0;

        return $this->twig->render('Courier/IssueAnotherCourier.twig', [
            'couriers' => $couriers,
            'stock_balances' => $stock_balances,
        ]);
    }
}