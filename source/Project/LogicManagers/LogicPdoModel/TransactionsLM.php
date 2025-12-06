<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\LogicManagers\DocumentLM\DocumentLM;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Transactions;


/**
 *
 */
class TransactionsLM
{
    public static function updateTransactionsId(array $data, $id)
    {

        $builder = Transactions::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        //return $builder->build();


        return PdoConnector::execute($builder);
    }

    public static function deleteTransactionsId($id)
    {
        $builder = Transactions::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $id
            ]);

        //return $builder->build();


        return PdoConnector::execute($builder);
    }

    public static function insertNewTransactions(array $dataset)
    {

        $builder = Transactions::newQueryBuilder()
            ->insert($dataset);

        return PdoConnector::execute($builder);
    }

    public static function getTranslationMaxId()
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                'MAX(id) as max_id',
            ])
            ->limit(1);


        //Logger::log(print_r($builder->build(), true), 'transaction_insert');
        return PdoConnector::execute($builder)[0]->max_id ?? 1;
    }

    public static function getToAccountId($to_account_id)
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'to_account_id =' . $to_account_id,
            ]);


        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return PdoConnector::execute($builder);
    }

    public static function getTransactionInToAccountId($to_account_id, $date_from, $date_to)
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
                'le_recipient.inn as inn_recipient',
                'le_recipient.company_name as company_name_recipient',
                'le_recipient.bank_name as bank_name_recipient',
                ////////////////////////////////////////////////
                'le_sender.inn as inn_sender',
                'le_sender.company_name as company_name_sender',
                'le_sender.bank_name as bank_name_sender',
            ])
            ->leftJoin('legal_entities le_recipient')
            ->on([
                'le_recipient.id = to_account_id',
            ])
            ->leftJoin('legal_entities le_sender')
            ->on([
                'le_sender.id = from_account_id',
            ]);

        if ($date_from && $date_to) {
            $builder->where([
                "transactions.to_account_id IN($to_account_id)",
                "transactions.date BETWEEN '$date_from' AND '$date_to'",
            ]);
        }


        if ($date_from && !$date_to) {
            $builder->where([
                "transactions.to_account_id IN($to_account_id)",
                "transactions.date >='" . $date_from . "'",
            ]);
        }


        Logger::log(print_r($builder->build(), true), 'transaction');
        return PdoConnector::execute($builder);
    }

    public static function getTransactionEntitiesId($id)
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'id =' . $id
            ])
            ->limit(1);


        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getTransactionsStatusPending()
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
                /* ======================================================
                 * ===================== ПОЛУЧАТЕЛЬ =====================
                 * ====================================================== */
                'le_recipient.id                    AS recipient_id',
                'le_recipient.our_account           AS recipient_our_account',
                'le_recipient.supplier_id           AS recipient_supplier_id',
                'le_recipient.client_services       AS recipient_client_services',
                'le_recipient.client_service_id     AS recipient_client_service_id',
                'le_recipient.manager_id            AS recipient_manager_id',
                'le_recipient.supplier_client_id    AS recipient_supplier_client_id',
                'le_recipient.client_id             AS recipient_client_id',
                'le_recipient.shop_id               AS recipient_shop_id',
                'le_recipient.percent               AS recipient_percent',
                'le_recipient.inn                   AS recipient_inn',
                'le_recipient.bank_name             AS recipient_bank_name',
                'le_recipient.company_name          AS recipient_company_name',
                /* ======================================================
                 * ===================== ОТПРАВИТЕЛЬ =====================
                 * ====================================================== */
                'le_sender.id                       AS sender_id',
                'le_sender.our_account              AS sender_our_account',
                'le_sender.supplier_id              AS sender_supplier_id',
                'le_sender.client_services          AS sender_client_services',
                'le_sender.client_service_id        AS sender_client_service_id',
                'le_sender.manager_id               AS sender_manager_id',
                'le_sender.supplier_client_id       AS sender_supplier_client_id',
                'le_sender.client_id                AS sender_client_id',
                'le_sender.shop_id                  AS sender_shop_id',
                'le_sender.percent                  AS sender_percent',
                'le_sender.inn                      AS sender_inn',
                'le_sender.bank_name                AS sender_bank_name',
                'le_sender.company_name             AS sender_company_name',
            ])
            ->leftJoin('legal_entities le_recipient')
            ->on([
                'le_recipient.id = to_account_id',
            ])
            ->leftJoin('legal_entities le_sender')
            ->on([
                'le_sender.id = from_account_id',
            ])
            ->where([
                'status = "pending"',
            ]);


        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return PdoConnector::execute($builder) ?? [];
    }

    public static function getTransactionsMinDate($from_account_id)
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                'date as min_date',
            ])
            ->from('transactions')
            ->where([
                'from_account_id =' . $from_account_id
            ])
            ->orderBy('date')
            ->limit(1);


        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return PdoConnector::execute($builder)[0]->min_date ?? null;
    }

    public static function updateTransactionsStatusPending()
    {

        $builder = Transactions::newQueryBuilder()
            ->update([
                "status =" . "processed"
            ])
            ->where([
                "status =" . "'pending'"
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getTransactionFromOrToAccountId($from_account_id, $to_account_id)
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
                'le.our_account as our_account',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = to_account_id',
            ])
            ->where([
                'from_account_id =' . $from_account_id,
                'to_account_id =' . $to_account_id
            ], 'OR');


        //Logger::log(print_r($builder->build(), true), 'transaction_insert');
        return PdoConnector::execute($builder);
    }

    public static function getFromAccountId($from_account_id, $offset, $limit, $date_from, $date_to): array
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
                'le_recipient.inn as inn_recipient',
                'le_recipient.company_name as company_name_recipient',
                'le_recipient.bank_name as bank_name_recipient',
                'le_sender.inn as inn_sender',
                'le_sender.company_name as company_name_sender',
                'le_sender.bank_name as bank_name_sender',
            ])
            ->leftJoin('legal_entities le_recipient')
            ->on([
                'le_recipient.id = to_account_id',
            ])
            ->leftJoin('legal_entities le_sender')
            ->on([
                'le_sender.id = from_account_id',
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
                    "from_account_id =" . $from_account_id . " OR " . " to_account_id =" . $from_account_id,
                    "date BETWEEN '$date_from' AND '$date_to'",
                ]);
        }


        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "from_account_id =" . $from_account_id . " OR " . " to_account_id =" . $from_account_id,
                    "date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "from_account_id =" . $from_account_id . " OR " . " to_account_id =" . $from_account_id,
                ]);
        }

        $builder
            ->where([
                "type != 'internal_transfer'",
            ]);

        $builder
            ->orderBy('date', 'DESC')
            ->limit($limit)
            ->offset($offset);


        $transactions = PdoConnector::execute($builder);
        $transactions_arr = [];


        foreach ($transactions as $transaction) {
            $legal_entities = '';
            $company_name = '';
            $inn_recipient = '';
            $legal_id = 0;
            $bank_name_recipient = '';

            if ($transaction->type == 'expense' || $transaction->type == 'return') {
                $legal_entities = $transaction->account_sender;
                $company_name = $transaction->company_name_sender;
                $inn_recipient = $transaction->inn_sender;
                $legal_id = $transaction->to_account_id;
                $bank_name_recipient = $transaction->bank_name_sender;
            }

            if ($transaction->type == 'income') {
                $legal_entities = $transaction->account_recipient;
                $company_name = $transaction->company_name_recipient;
                $inn_recipient = $transaction->inn_recipient;
                $legal_id = $transaction->from_account_id;
                $bank_name_recipient = $transaction->bank_name_recipient;
            }


            $transactions_arr[] = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'date' => date('d.m.Y', strtotime($transaction->date)),
                'description' => $transaction->description,
                'from_account_id' => $transaction->from_account_id,
                'to_account_id' => $transaction->to_account_id,
                'status' => $transaction->status,
                'percent' => $transaction->percent,
                'interest_income' => $transaction->interest_income,
                'total_amount' => $transaction->amount - $transaction->interest_income,
                'legal_id' => $legal_id,
                'company_name' => $company_name,
                'inn_recipient' => $inn_recipient,
                'account_recipient' => $legal_entities,
                'bank_name_recipient' => $bank_name_recipient,
            ];
        }


        //Logger::log(print_r($transactions, true), 'transaction_insert');


        return $transactions_arr;
    }

    public static function getTransactionsByPercentage($supplier_id, $date_from, $date_to, $transit_debt): array
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                't.percent AS percent',
                'SUM(t.amount) AS total_amount',
                'SUM(t.interest_income) AS interest_income',
                'SUM(t.amount - t.interest_income) AS clean_amount',
                "COALESCE(SUM(CASE WHEN d.status = 'active' THEN d.amount ELSE 0 END), 0) AS debt_amount",
            ])
            ->from('transactions t')
            ->innerJoin('legal_entities le')
            ->on([
                'le.id = t.to_account_id',
            ])
            ->leftJoin('debts d')
            ->on([
                'd.transaction_id = t.id',
                "d.type_of_debt = 'supplier_goods'"
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
            $builder->where([
                "le.supplier_id = " . $supplier_id,
                "le.client_services = 0",
                "t.date BETWEEN '$date_from' AND '$date_to'",
            ]);
        }


        if ($date_from && !$date_to) {
            $builder->where([
                "le.supplier_id = " . $supplier_id,
                "le.client_services = 0",
                "t.date >='" . $date_from . "'",
            ]);
        }


        $builder
            ->groupBy('t.percent')
            ->orderBy('t.percent');


        $transactions = PdoConnector::execute($builder);
        $transactions_by_percentage = [];

        foreach ($transactions as $transaction) {
            $commission = $transaction->percent; // или любое другое значение
            $clean_amount = ($transaction->total_amount - $transit_debt['transit_debt_amount']) * (1 - $commission / 100);

            $transactions_by_percentage[] = [
                'percent' => $transaction->percent,
                'total_amount' => $transaction->total_amount - $transit_debt['transit_debt_amount'],
                'interest_income' => $transaction->interest_income,
                'clean_amount' => $clean_amount,
                'debt_amount' => $transaction->debt_amount,
            ];
        }


        return $transactions_by_percentage;
    }

    public static function getFromAccountIdCount($from_account_id)
    {

        $builder = Transactions::newQueryBuilder()
            ->select([
                'COUNT(le_recipient.id) as recipient_count',
                'COUNT(le_sender.id) as sender_count',
            ])
            ->leftJoin('legal_entities le_recipient')
            ->on([
                'le_recipient.id = to_account_id',
            ])
            ->leftJoin('legal_entities le_sender')
            ->on([
                'le_sender.id = from_account_id',
            ])
            ->where([
                'from_account_id =' . $from_account_id,
                'to_account_id =' . $from_account_id,
            ], 'OR');


        $transactions = PdoConnector::execute($builder)[0] ?? null;


        if ($transactions->recipient_count && $transactions->sender_count) {
            return $transactions->recipient_count + $transactions->sender_count;
        }


        return $transactions->recipient_count ?? $transactions->sender_count ?? 0;
    }

    public static function getAllTransactionsUnknownAccount($id)
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                '*',
                'le_recipient.inn as inn_recipient',
                'le_recipient.bank_name as bank_name_recipient',
                'le_recipient.company_name as company_name_recipient',
                'le_recipient.our_account  as recipient_our_account',

                'le_sender.inn as inn_sender',
                'le_sender.bank_name as bank_name_sender',
                'le_sender.company_name as company_name_sender',
                'le_sender.our_account as sender_our_account',
            ])
            ->leftJoin('legal_entities le_recipient')
            ->on([
                'le_recipient.id = from_account_id',
            ])
            ->leftJoin('legal_entities le_sender')
            ->on([
                'le_sender.id = to_account_id',
            ])
            ->where([
                'to_account_id =' . $id,
                'from_account_id =' . $id,
            ], 'OR');


        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');
        return PdoConnector::execute($builder);
    }

    public static function getEntitiesShopTransactions($shop_id, $offset, $limit, $date_from, $date_to): array
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                't.id as transaction_id',
                't.date as transaction_date',
                't.description as description',
                't.amount as transaction_amount',

                'sender.id as sender_id',
                'sender.company_name as sender_company_name',
                'sender.bank_name as sender_bank_name',

                'recipient.id as recipient_id',
                'recipient.company_name as recipient_company_name',
                'recipient.bank_name as recipient_bank_name',
            ])
            ->from('transactions t');

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        $builder
            ->innerJoin('legal_entities sender')
            ->on([
                "sender.id = t.from_account_id",
            ])
            ->innerJoin('legal_entities recipient')
            ->on([
                "recipient.id = t.to_account_id",
            ]);

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "(sender.shop_id = $shop_id OR recipient.shop_id = $shop_id)",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "sender.shop_id = $shop_id OR recipient.shop_id = $shop_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "(sender.shop_id = $shop_id OR recipient.shop_id = $shop_id)",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->orderBy('t.date', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $transactions = PdoConnector::execute($builder) ?? null;
        $transactions_arr = [];


        if (!$transactions) {
            return [];
        }

        foreach ($transactions as $transaction) {
            $transactions_arr[] = [
                'transaction_id' => $transaction->transaction_id,
                'legal_id' => $transaction->legal_id,
                'description' => $transaction->description,
                'date' => date('d.m.Y', strtotime($transaction->transaction_date)),
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
            ];
        }


        //Logger::log(print_r($transactions_arr, true), 'transactions_arr');

        return $transactions_arr;
    }

    public static function getEntitiesShopTransactionsSum($shop_id, $date_from, $date_to): array
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
            ])
            ->from('transactions t');;

        $builder
            ->innerJoin('legal_entities sender')
            ->on([
                "sender.id = t.from_account_id",
            ])
            ->innerJoin('legal_entities recipient')
            ->on([
                "recipient.id = t.to_account_id",
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "(sender.shop_id = $shop_id OR recipient.shop_id = $shop_id)",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "sender.shop_id = $shop_id OR recipient.shop_id = $shop_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "(sender.shop_id = $shop_id OR recipient.shop_id = $shop_id)",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }


        $transactions = PdoConnector::execute($builder)[0] ?? [];
        $sum_amount = $transactions->sum_amount ?? 0;

        return [
            'sum_amount' => $sum_amount,
        ];
    }

    public static function getEntitiesShopTransactionsCount($shop_id, $date_from, $date_to): int
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                'COUNT(t.id) as count',
            ])
            ->from('transactions t');

        $builder
            ->innerJoin('legal_entities sender')
            ->on([
                "sender.id = t.from_account_id",
            ])
            ->innerJoin('legal_entities recipient')
            ->on([
                "recipient.id = t.to_account_id",
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "(sender.shop_id = $shop_id OR recipient.shop_id = $shop_id)",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->where([
                    "sender.shop_id = $shop_id OR recipient.shop_id = $shop_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "(sender.shop_id = $shop_id OR recipient.shop_id = $shop_id)",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }


        return PdoConnector::execute($builder)[0]->count ?? 0;
    }


    public static function getEntitiesOurTransactions($offset, $limit, $date_from, $date_to): array
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                't.id as transaction_id',
                't.date as transaction_date',
                't.description as description',
                't.amount as transaction_amount',

                'sender.id as sender_id',
                'sender.company_name as sender_company_name',
                'sender.bank_name as sender_bank_name',

                'recipient.id as recipient_id',
                'recipient.company_name as recipient_company_name',
                'recipient.bank_name as recipient_bank_name',
            ])
            ->from('transactions t');

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        $builder
            ->innerJoin('legal_entities sender')
            ->on([
                "sender.id = t.from_account_id",
            ])
            ->innerJoin('legal_entities recipient')
            ->on([
                "recipient.id = t.to_account_id",
            ]);

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->where([
                "sender.our_account =" . 1,
                "recipient.our_account =" . 1
            ]);

        $builder
            ->orderBy('t.date', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $transactions = PdoConnector::execute($builder) ?? null;
        $transactions_arr = [];


        if (!$transactions) {
            return [];
        }

        foreach ($transactions as $transaction) {
            $transactions_arr[] = [
                'transaction_id' => $transaction->transaction_id,
                'legal_id' => $transaction->legal_id,
                'description' => $transaction->description,
                'date' => date('d.m.Y', strtotime($transaction->transaction_date)),
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
            ];
        }


        //Logger::log(print_r($transactions_arr, true), 'transactions_arr');

        return $transactions_arr;
    }

    public static function getEntitiesOurTransactionsSum($date_from, $date_to): array
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
            ])
            ->from('transactions t');;

        $builder
            ->innerJoin('legal_entities sender')
            ->on([
                "sender.id = t.from_account_id",
            ])
            ->innerJoin('legal_entities recipient')
            ->on([
                "recipient.id = t.to_account_id",
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->where([
                "sender.our_account =" . 1,
                "recipient.our_account =" . 1
            ]);


        $transactions = PdoConnector::execute($builder)[0] ?? [];
        $sum_amount = $transactions->sum_amount ?? 0;

        return [
            'sum_amount' => $sum_amount,
        ];
    }

    public static function getEntitiesOurTransactionsCount($date_from, $date_to): int
    {
        $builder = Transactions::newQueryBuilder()
            ->select([
                'COUNT(t.id) as count',
            ])
            ->from('transactions t');

        $builder
            ->innerJoin('legal_entities sender')
            ->on([
                "sender.id = t.from_account_id",
            ])
            ->innerJoin('legal_entities recipient')
            ->on([
                "recipient.id = t.to_account_id",
            ]);

        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->where([
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->where([
                "sender.our_account =" . 1,
                "recipient.our_account =" . 1
            ]);


        return PdoConnector::execute($builder)[0]->count ?? 0;
    }


}