<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\CompanyFinances;


/**
 *
 */
class CompanyFinancesLM
{

    public static function updateCompanyFinancesId(array $data, $id)
    {

        $builder = CompanyFinances::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function deleteCompanyFinancesId($id)
    {

        $builder = CompanyFinances::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function deleteOrderId($id)
    {

        $builder = CompanyFinances::newQueryBuilder()
            ->delete()
            ->where([
                'order_id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getFinancesOrderId($id)
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select(['*'])
            ->where([
                'order_id =' . $id
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function insertTransactionsExpenses(array $dataset)
    {

        $builder = CompanyFinances::newQueryBuilder()
            ->insert($dataset);

        return PdoConnector::execute($builder);
    }

    public static function getTranslationExpensesCount($category, $date_from, $date_to, $type, $supplier_id = null)
    {
        $type_condition = [
            'company' => "(company_finances.type = 'expense' OR company_finances.type = 'expense_stock_balances' OR company_finances.type = 'courier_expense')",
            'supplier_expense' => "company_finances.type = 'expense_stock_balances_supplier'",
            'supplier_debit' => "company_finances.type = 'debt_repayment_companies_supplier' OR company_finances.type = 'debt_repayment_client_supplier'",
            'stock_balances' => "company_finances.type = 'stock_balances'"
        ][$type];

        $builder = CompanyFinances::newQueryBuilder()
            ->select(['COUNT(company_finances.id) as count'])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($category && $date_from && $date_to) {
            $builder
                ->where([
                    "company_finances.category LIKE CONCAT('$category', '%')",
                    "t.date BETWEEN '$date_from' AND '$date_to'",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if (!$category && $date_from && $date_to) {
            $builder
                ->where([
                    "t.date BETWEEN '$date_from' AND '$date_to'",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if (!$category && $date_from && !$date_to) {
            $builder
                ->where([
                    "t.date >='" . $date_from . "'",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if ($category && !$date_from && !$date_to) {
            $builder
                ->where([
                    "company_finances.category LIKE CONCAT('$category', '%')",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if (!$category && !$date_from && !$date_to) {
            $builder
                ->where([
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if ($supplier_id) {
            $builder
                ->where([
                    "company_finances.supplier_id =" . $supplier_id,
                ]);
        }


        return PdoConnector::execute($builder)[0]->count ?? [];
    }

    public static function getExpenses($offset, $limit, $category, $date_from, $date_to, $type, $supplier_id = null): array
    {
        $type_condition = [
            'company' => "(company_finances.type = 'expense' OR company_finances.type = 'expense_stock_balances' OR company_finances.type = 'courier_expense')",
            'supplier_expense' => "company_finances.type = 'expense_stock_balances_supplier'",
            'supplier_debit' => "company_finances.type = 'debt_repayment_companies_supplier' OR company_finances.type = 'debt_repayment_client_supplier'",
            'stock_balances' => "company_finances.type = 'stock_balances'"
        ][$type];

        $clients = [
            'company' => [
                'c.id = client_id',
            ],
            'supplier_expense' => [
                'c.id = client_id',
                'c.supplier_id =' . $supplier_id,
            ],
            'supplier_debit' => [
                'c.id = client_id',
                'c.supplier_id =' . $supplier_id,
            ],
            'stock_balances' => [
                'c.id = client_id',
            ],
        ][$type];


        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*',
                'bo.recipient_company_name as company_name',
                'bo.recipient_bank_name as bank_name',
                'bo.return_account as return_account',
                'bo.id as bank_order_id',
                't.amount as amount',
                't.date as date',
                't.description as description',
                'le.bank_name as sender_bank_name',
                'le.company_name as sender_company_name',
                'u_client.name as client_name',
            ])
            ->leftJoin('clients c')
            ->on($clients)
            ->leftJoin('users u_client')
            ->on([
                'u_client.id = c.user_id',
            ])
            ->leftJoin('bank_order bo')
            ->on([
                'bo.id = order_id',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = t.from_account_id',
            ]);


        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($category && $date_from && $date_to) {
            $builder
                ->where([
                    "company_finances.category LIKE CONCAT('$category', '%')",
                    "t.date BETWEEN '$date_from' AND '$date_to'",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if (!$category && $date_from && $date_to) {
            $builder
                ->where([
                    "t.date BETWEEN '$date_from' AND '$date_to'",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if (!$category && $date_from && !$date_to) {
            $builder
                ->where([
                    "t.date >='" . $date_from . "'",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if ($category && !$date_from && !$date_to) {
            $builder
                ->where([
                    "company_finances.category LIKE CONCAT('$category', '%')",
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if (!$category && !$date_from && !$date_to) {
            $builder
                ->where([
                    $type_condition,
                    "company_finances.status = 'processed'"
                ]);
        }

        if ($supplier_id) {
            $builder
                ->where([
                    "company_finances.supplier_id =" . $supplier_id,
                ]);
        }

        $builder
            ->orderBy("t.date", "DESC")
            ->limit($limit)
            ->offset($offset);

        $expenses = PdoConnector::execute($builder);


        $expenses_arr = [];

        foreach ($expenses as $expense) {

            $expenses_arr[] = [
                'id' => $expense->id,
                'category' => $expense->category,
                'comments' => $expense->comments,
                'bank_name' => $expense->bank_name,
                'company_name' => $expense->company_name,
                'amount' => $expense->amount,
                'date' => date('d.m.Y', strtotime($expense->date)),
                'description' => $expense->description,
                'sender_bank_name' => $expense->sender_bank_name ?? '',
                'sender_company_name' => $expense->sender_company_name ?? '',
                'status' => $expense->status,
                'client_name' => $expense->client_name ?? '',
                'type' => $expense->type,
                'return_account' => $expense->return_account,
                'bank_order_id' => $expense->bank_order_id,
            ];
        }

        return $expenses_arr;
    }

    public static function getCourierFinancesCount($courier_id, $category, $date_from, $date_to)
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select(['COUNT(company_finances.id) as count'])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($category && $date_from && $date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "company_finances.category LIKE CONCAT('$category', '%')",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$category && $date_from && $date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$category && $date_from && !$date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($category && !$date_from && !$date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "company_finances.category LIKE CONCAT('$category', '%')",
                ]);
        }

        if (!$category && !$date_from && !$date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                ]);
        }



        return PdoConnector::execute($builder)[0]->count ?? 0;
    }

    public static function getCourierFinances($courier_id, $offset, $limit, $category, $date_from, $date_to)
    {

        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*',
                't.amount as amount',
                't.date as date',
                't.description as description',
                'bo.id as bank_order_id',
                'bo.recipient_company_name as company_name',
                'bo.recipient_bank_name as bank_name',
                'bo.return_account as return_account',
                'u_supplier.name as supplier_name',
            ])
            ->leftJoin('suppliers s')
            ->on([
                's.id = supplier_id',
            ])
            ->leftJoin('users u_supplier')
            ->on([
                'u_supplier.id = s.user_id',
            ])
            ->leftJoin('bank_order bo')
            ->on([
                'bo.id = order_id',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ]);


        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($category && $date_from && $date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "company_finances.category LIKE CONCAT('$category', '%')",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$category && $date_from && $date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$category && $date_from && !$date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($category && !$date_from && !$date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                    "company_finances.category LIKE CONCAT('$category', '%')",
                ]);
        }

        if (!$category && !$date_from && !$date_to) {
            $builder
                ->where([
                    "courier_id =" . $courier_id,
                ]);
        }

        $builder
            ->orderBy("t.date", "DESC")
            ->limit($limit)
            ->offset($offset);

        $expenses = PdoConnector::execute($builder);

        $expenses_arr = [];

        foreach ($expenses as $expense) {
            $formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
            $formatter->setPattern('EEEE');

            $date = new \DateTime($expense->date);
            $translated_date = $formatter->format($date);

            $expenses_arr[] = [
                'id' => $expense->id,
                'category' => $expense->category,
                'comments' => $expense->comments,
                'amount' => $expense->amount,
                'date' => date('d.m.Y', strtotime($expense->date)),
                'dey' => $translated_date,
                'description' => $expense->description,
                'supplier_name' => $expense->supplier_name ?? '',
                'status' => $expense->status,
                'bank_order_id' => $expense->bank_order_id,
                'company_name' => $expense->company_name,
                'bank_name' => $expense->bank_name,
                'return_account' => $expense->return_account,
            ];
        }

        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return $expenses_arr;
    }

    public static function getCourierPendingCount(int $courier_id): int
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select(['COUNT(company_finances.id) as cnt'])
            ->leftJoin('bank_order bo')
            ->on([
                'bo.id = order_id',
            ])
            ->where([
                'courier_id =' . $courier_id,
                "status = 'confirm_courier'",
            ]);

        $row = PdoConnector::execute($builder)[0] ?? null;
        return (int)($row?->variables['cnt'] ?? 0);
    }

    public static function getCourierPending(int $courier_id, int $offset, int $limit): array
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                'company_finances.id as id',
                'company_finances.category as category',
                'company_finances.comments as comments',
                'company_finances.status as status',
                't.amount as amount',
                't.date as bo_date',
                'сс.card_number as card_number',
                'u_supplier.name as supplier_name',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('suppliers s')
            ->on([
                's.id = supplier_id',
            ])
            ->leftJoin('users u_supplier')
            ->on([
                'u_supplier.id = s.user_id',
            ])
            ->leftJoin('credit_cards сс')
            ->on([
                'сс.id = card_id',
            ])
            ->where([
                'courier_id =' . $courier_id,
                "status = 'confirm_courier'",
            ])
            ->orderBy('t.date', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $rows = PdoConnector::execute($builder) ?? [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => $r->id,
                'category' => $r->category,
                'comments' => $r->comments,
                'status' => $r->status,
                'amount' => $r->amount,
                'card_number' => $r->card_number,
                'supplier_name' => $r->supplier_name ?? 'Администратор',
                'date' => $r->bo_date ? date('d.m.Y', strtotime($r->bo_date)) : '',
            ];
        }
        return $out;
    }

    public static function getPendingByIdForConfirm(int $company_finances_id): ?object
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                'company_finances.id as id',
                'company_finances.courier_id as courier_id',
                'company_finances.status as status',
                't.amount as amount',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->where([
                'company_finances.id =' . $company_finances_id,
                "status = 'confirm_courier'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getPendingById(int $company_finances_id): ?object
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                'id as id',
                'courier_id as courier_id',
                'status as status',
                'client_id as client_id',
                'supplier_id as supplier_id',
                't.amount as amount',
                't.id as transaction_id',
                'с.current_balance as current_balance',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('couriers с')
            ->on([
                'с.id =  courier_id',
            ])
            ->where([
                'id =' . $company_finances_id,
                "status = 'confirm_admin'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getReturnTypeWheelId(int $company_finances_id): ?object
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*',
                't.amount as amount',
                't.from_account_id as from_account_id',
                'b.stock_balance as stock_balance',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('bank_accounts b')
            ->on([
                'b.legal_entity_id = t.from_account_id',
            ])
            ->where([
                'id =' . $company_finances_id,
                "return_type = 'wheel'",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function confirmationCostsCourier(): array
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*',
                't.amount as amount',
                't.date as date',
                't.description as description',
                'u.email as email',
                'u.name as name',
                'u.password as password',

                'cs.id as client_id',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('couriers c')
            ->on([
                'c.id = courier_id',
            ])
            ->leftJoin('clients cs')
            ->on([
                'cs.id = client_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = c.user_id',
            ])
            ->where([
                "status = 'confirm_admin'",
                "(
                company_finances.type = 'return_debit_courier' OR 
                company_finances.type = 'courier_expense' OR 
                company_finances.type = 'courier_income_other' OR
                company_finances.type = 'debt_repayment_сompanies_supplier'
                )",
            ]);


        $confirmation = PdoConnector::execute($builder) ?? [];

        $courier_expense = [];
        $return_debit_courier = [];
        $courier_income_other = [];
        $debt_repayment_companies_supplier = [];

        if (!$confirmation) {
            return [];
        }

        foreach ($confirmation as $confirm) {
            if ($confirm->type == 'courier_expense') {
                $courier_expense[] = [
                    'id' => $confirm->id,
                    'transaction_id' => $confirm->transaction_id,
                    'courier_id' => $confirm->courier_id,
                    'category' => $confirm->category,
                    'comments' => $confirm->comments,
                    'amount' => $confirm->amount,
                    'date' => date('d.m.Y H:i', strtotime($confirm->date)),
                    'type' => $confirm->type,
                    'email' => $confirm->email,
                    'name' => $confirm->name,
                ];
            }

            if ($confirm->type == 'return_debit_courier' && $confirm->client_id) {

                $client = ClientsLM::getClientId($confirm->client_id);

                $return_debit_courier [] = [
                    'id' => $confirm->id,
                    'transaction_id' => $confirm->transaction_id,
                    'courier_id' => $confirm->courier_id,
                    'comments' => $confirm->comments,
                    'amount' => $confirm->amount,
                    'date' => date('d.m.Y H:i', strtotime($confirm->date)),
                    'type' => $confirm->type,
                    'email' => $confirm->email,
                    'courier_name' => $confirm->name,
                    'client_name' => $client['username'] ?? '',
                    'debit_amount' => $client['debit_amount'] ?? 0,
                ];
            }

            if ($confirm->type == 'courier_income_other') {

                $courier_income_other[] = [
                    'id' => $confirm->id,
                    'transaction_id' => $confirm->transaction_id,
                    'courier_id' => $confirm->courier_id,
                    'comments' => $confirm->comments,
                    'amount' => $confirm->amount,
                    'date' => date('d.m.Y H:i', strtotime($confirm->date)),
                    'type' => $confirm->type,
                    'email' => $confirm->email,
                    'courier_name' => $confirm->name,
                    'transaction_description' => $confirm->description,
                ];
            }

            if ($confirm->type == 'debt_repayment_сompanies_supplier') {
                $supplier = SuppliersLM::getSuppliersId($confirm->supplier_id);

                $debt_repayment_companies_supplier[] = [
                    'id' => $confirm->id,
                    'transaction_id' => $confirm->transaction_id,
                    'supplier_id' => $confirm->supplier_id,
                    'comments' => $confirm->comments,
                    'amount' => $confirm->amount,
                    'date' => date('d.m.Y H:i', strtotime($confirm->date)),
                    'type' => $confirm->type,
                    'email' => $supplier->email,
                    'supplier_name' => $supplier->username,
                    'transaction_description' => $confirm->description,
                ];
            }
        }

        //Logger::log(print_r($debt_repayment_companies_supplier, true), 'adminHomePage');

        return [
            'courier_expense' => $courier_expense,
            'return_debit_courier' => $return_debit_courier,
            'courier_income_other' => $courier_income_other,
            'debt_repayment_companies_supplier' => $debt_repayment_companies_supplier,
        ];
    }

    public static function getManagerFinancesCount($manager_id, $date_from, $date_to, $type = 'shipping_manager', $legal_id = null)
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select(['COUNT(company_finances.id) as count'])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "type = '" . $type . "'",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "type = '" . $type . "'",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "type = '" . $type . "'",
                    "manager_id =" . $manager_id,
                ]);
        }

        if ($legal_id) {
            $builder
                ->where([
                    "t.from_account_id ='" . $legal_id . "'",
                ]);
        }

        $builder
            ->orderBy("t.date", "DESC");


        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getFinancesId(int $id)
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*'
            ])
            ->where([
                "id =" . $id,
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getManagerFinances($manager_id, $offset, $limit, $date_from, $date_to, $type = 'shipping_manager', $legal_id = null): array
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*',
                't.amount as amount',
                't.date as date',
                't.description as description',
                'le.company_name as company_name',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = t.from_account_id',
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "type = '" . $type . "'",
                    "DATE(t.date) BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "type = '" . $type . "'",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "type = '" . $type . "'",
                ]);
        }

        if ($legal_id) {
            $builder
                ->where([
                    "t.from_account_id ='" . $legal_id . "'",
                ]);
        }


        $builder
            ->orderBy("t.date", "DESC");

        if ($limit) {
            $builder
                ->limit($limit)
                ->offset($offset);
        }

        $manager_finances = PdoConnector::execute($builder);

        $manager_finances_arr = [];

        $formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
        $formatter->setPattern('EEEE');

        foreach ($manager_finances as $finances) {
            $date = new \DateTime($finances->date);
            $translated_date = $formatter->format($date);

            $manager_finances_arr[] = [
                'id' => $finances->id,
                'company_name' => $finances->company_name,
                'category' => $finances->category,
                'comments' => $finances->comments,
                'amount' => $finances->amount,
                'date' => date('d.m.Y', strtotime($finances->date)),
                'dey' => $translated_date,
                'description' => $finances->description,
                'return_type' => $finances->return_type,
                'status' => $finances->status,
            ];
        }

        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return $manager_finances_arr;
    }

    public static function getWritingTransactionById(int $transaction_id): ?object
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                '*',
                'u.name as username',
                't.amount as amount',
                't.date as date',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->leftJoin('couriers с')
            ->on([
                'с.id = courier_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id =  с.user_id',
            ])
            ->where([
                'transaction_id =' . $transaction_id,
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getManagerFinancesSumAndType($manager_id, $date_from, $date_to)
    {
        $builder = CompanyFinances::newQueryBuilder()
            ->select([
                "COALESCE(SUM(CASE WHEN company_finances.type = 'shipping_return' THEN t.amount END), 0) AS shipping_return_amount",
                "COALESCE(SUM(CASE WHEN company_finances.type = 'shipping_manager' THEN t.amount END), 0) AS shipping_manager_amount",
                "COALESCE(SUM(CASE WHEN company_finances.type = 'moved_cash' THEN t.amount END), 0) AS moved_cash_amount",
                "COALESCE(SUM(CASE 
                WHEN company_finances.type = 'shipping_return' 
                     AND (company_finances.return_type = 'wheel' OR company_finances.return_type = 'return_wheel' OR company_finances.return_type = 'cash') 
                THEN t.amount 
                END),0) AS wheel_return_amount"
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "DATE(t.date) BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "manager_id =" . $manager_id,
                ]);
        }


        $builder
            ->orderBy("t.date", "DESC");


        return PdoConnector::execute($builder)[0] ?? [];
    }



}