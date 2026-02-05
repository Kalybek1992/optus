<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\TransactionProviders;
use DateTime;


/**
 *
 */
class TransactionProvidersLM
{

    public static function insertNewTransactionProviders(array $data)
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getTransactionProviderId($id)
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function transactionProviderDeleteId(int $id)
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $id,
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

    public static function transactionProvidersUpdate(array $data, int $id)
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

    public static function transactionIdUpdate(array $data, int $transaction_id)
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->update($data)
            ->where([
                'transaction_id =' . $transaction_id
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

    public static function getTransactionId(int $transaction_id)
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'manager_id as manager_id',
                'client_id as client_id',
                'id as provider_id',
                't.*',
            ])
            ->innerJoin('transactions t')
            ->on([
                "t.id = transaction_id",
            ])
            ->where([
                'transaction_id =' . $transaction_id
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }


    /**
     * @param $date
     * @param int $manager_id
     * @return int[]
     * @throws \Exception
     */
    public static function getTransactionManagerSum($date, int $manager_id): array
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
            ])
            ->from('transaction_providers tp');

        if ($date) {
            $date = DateTime::createFromFormat('d.m.Y', $date);
            $date = $date->format('Y-m-d');
        }

        if ($date) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_id",
                    "t.date ='" . $date . "'",
                ]);
        }

        $builder
            ->where([
                'tp.manager_id =' . $manager_id,
            ]);

        $transactions = PdoConnector::execute($builder)[0] ?? [];
        $sum_amount = $transactions->sum_amount ?? 0;

        return ['sum_amount' => $sum_amount];
    }


    public static function getTransactionsReturnSum($date_from, $date_to, $manager_id): array
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
            ])
            ->from('transaction_providers tp');

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
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_id",
                    "t.date >='" . $date_from . "'",
                    "t.type = 'return_client_services'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_id",
                    "t.type = 'return_client_services'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                    "t.type = 'return_client_services'",
                ]);
        }

        $builder
            ->where([
                'tp.manager_id =' . $manager_id,
            ]);


        $transactions = PdoConnector::execute($builder)[0] ?? [];

        $sum_amount = $transactions->sum_amount ?? 0;

        return [
            'sum_amount' => $sum_amount,
        ];
    }

    public static function getTransactionsSum($date_from, $date_to, $legal_id): array
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
                'SUM(t.interest_income) as sum_interest_income',
                "TRIM(BOTH ',' FROM REPLACE(GROUP_CONCAT(DISTINCT t.percent SEPARATOR ','), ',0', '')) AS percents"
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
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if ($legal_id){
            $builder
                ->where([
                    'legal_id =' . $legal_id
                ]);
        }

        $transactions = PdoConnector::execute($builder)[0] ?? [];
        $sum_amount = $transactions->sum_amount ?? 0;
        $sum_interest_income = $transactions->sum_interest_income ?? 0;
        $percents = $transactions->percents ?? '';

        if ($percents) {
            $percents = implode(', ', array_map(function ($p) {
                $p = trim($p);
                if ($p === '' || $p == 0) return null;
                $p = rtrim(rtrim($p, '0'), '.');
                return $p . '%';
            }, explode(',', $percents)));

            $percents = preg_replace('/(, )+/', ', ', trim($percents, ', '));
        } else {
            $percents = '';
        }

        return [
            'sum_amount' => $sum_amount,
            'sum_interest_income' => $sum_interest_income,
            'debts_amount' => $sum_amount - $sum_interest_income,
            'debt_client_services' => $transactions->debt_client_services ?? 0,
            'percents' => $percents,
        ];
    }


    public static function getClientDebitSum($client_id): int
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'SUM(d.amount) as amount',
            ])
            ->from('transaction_providers tp')
            ->innerJoin('debts d')
            ->on([
                "d.transaction_id = tp.transaction_id",
                "d.type_of_debt = 'сlient_debt_supplier'",
                "d.status = 'active'",
            ])
            ->where([
                'tp.client_id =' . $client_id
            ]);;


        $transactions = PdoConnector::execute($builder)[0] ?? [];


        return $transactions->amount ?? 0;
    }

    public static function getClientDebits($client_id): array
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'tp.transaction_id as transaction_id_tp',
                'd.*',
            ])
            ->from('transaction_providers tp')
            ->innerJoin('debts d')
            ->on([
                "d.transaction_id = tp.transaction_id",
                "d.type_of_debt = 'сlient_debt_supplier'",
                "d.status = 'active'",
            ])
            ->where([
                'tp.client_id =' . $client_id
            ]);


        return PdoConnector::execute($builder);
    }


    public static function geTransactionsManager($manager_id, $offset, $limit, $date_from, $date_to, $legal_id = null): array
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                't.description as description',
                't.amount as transaction_amount',
                't.date as transaction_date',
                't.id as transaction_id',
                't.date as date',
                'le_from.bank_name as sender_bank_name',
                'le_from.company_name as sender_company_name',
                'le.bank_name as recipient_bank_name',
                'le.company_name as recipient_company_name',
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
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.to_account_id",
            ])
            ->innerJoin('legal_entities le_from')
            ->on([
                "le_from.id = t.from_account_id",
            ])
            ->where([
                'manager_id =' . $manager_id
            ]);

        if ($legal_id){
            $builder
                ->where([
                    'legal_id =' . $legal_id
                ]);
        }

        $builder
            ->groupBy('t.id')
            ->orderBy('t.date', 'DESC');

        if ($limit) {
            $builder
                ->limit($limit)
                ->offset($offset);
        }


        $transactions = PdoConnector::execute($builder) ?? null;
        $transactions_arr = [];

        if (!$transactions) {
            return [];
        }

        foreach ($transactions as $transaction) {
            $transactions_arr[] = [
                'transaction_id' => $transaction->transaction_id,
                'description' => $transaction->description,
                'date' => date('d.m.Y', strtotime($transaction->date)),
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
            ];
        }

        return $transactions_arr;
    }


    public static function geTransactionsClient($client_id, $offset, $limit, $date_from, $date_to): array
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'tp.percent as transaction_percent',
                'le_from.bank_name as sender_bank_name',
                'le_from.company_name as sender_company_name',
                't.description as description',
                't.amount as transaction_amount',
                't.interest_income as transaction_interest_income',
                't.date as transaction_date',
                't.id as transaction_id',
                't.date as date',
                'le.bank_name as recipient_bank_name',
                'le.company_name as recipient_company_name',
                'GROUP_CONCAT(dc.id ORDER BY dc.id) as debit_closing_ids',
                'GROUP_CONCAT(dc.transaction_id ORDER BY dc.id) as debit_closing_transaction_ids',
                'GROUP_CONCAT(dc.amount ORDER BY dc.id) as debit_closing_amounts'
            ])
            ->from('transaction_providers tp');

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
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_idd",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = tp.transaction_id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.to_account_id",
            ])
            ->innerJoin('legal_entities le_from')
            ->on([
                "le_from.id = t.from_account_id",
            ])
            ->innerJoin('debts d')
            ->on([
                "d.transaction_id = t.id",
                "d.type_of_debt = 'сlient_debt_supplier'",
            ])
            ->leftJoin('debt_closings dc')
            ->on([
                "dc.debt_id = d.id",
                "d.transaction_id = t.id",
            ])
            ->where([
                'tp.client_id =' . $client_id
            ]);

        $builder
            ->groupBy('t.id')
            ->orderBy('t.date', 'DESC');

        if ($limit) {
            $builder
                ->limit($limit)
                ->offset($offset);
        }


        $transactions = PdoConnector::execute($builder) ?? null;
        $transactions_arr = [];

        if (!$transactions) {
            return [];
        }

        foreach ($transactions as $transaction) {
            $interest_income = $transaction->transaction_amount * $transaction->transaction_percent / 100;
            $issuance = [];

            if ($transaction->debit_closing_ids) {
                $company_finances = CompanyFinancesLM::getWritingTransactionByInIds(
                    $transaction->debit_closing_transaction_ids
                );

                foreach ($company_finances as $company_finance) {
                    $issuance[] = [
                        'amount' => $company_finance->amount,
                        'issue_date' => $company_finance->issue_date,
                        'comments' => $company_finance->comments,
                        'who_issued_it' => $company_finance->username ?? 'Админ'
                    ];
                }
            }

            $transactions_arr[] = [
                'transaction_id' => $transaction->transaction_id,
                'description' => $transaction->description,
                'date' => date('d.m.Y', strtotime($transaction->date)),
                'percent' => $transaction->transaction_percent,
                'interest_income' => $interest_income,
                'debit_amount' => $transaction->transaction_amount - $interest_income,
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
                'issuance' => $issuance
            ];
        }

        return $transactions_arr;
    }

    public static function geTransactionsManagerCount($manager, $date_from, $date_to, $legal_id = null): int
    {
        $builder = TransactionProviders::newQueryBuilder()
            ->select([
                'COUNT(DISTINCT t.id) as count',
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
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.id = transaction_id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->where([
                'manager_id =' . $manager
            ]);

        if ($legal_id){
            $builder
                ->where([
                    'legal_id =' . $legal_id
                ]);
        }

        return PdoConnector::execute($builder)[0]->count ?? 0;
    }


}