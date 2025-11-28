<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\BankOrder;
use Source\Project\Models\Clients;
use Source\Project\Models\ClientServices;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Transactions;
use Source\Project\Models\Users;


/**
 *
 */
class LegalEntitiesLM
{
    public static function setNewLegalEntitie(array $legal_entities_insert)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->insert($legal_entities_insert);

        return PdoConnector::execute($builder);
    }

    public static function getLegalEntitieById(int $id)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select(['*'])
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getLegalEntitiesMaxId()
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'MAX(id) as max_id',
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->max_id ?? 0;
    }

    public static function deleteLegalEntitiesId($legal_id)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $legal_id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getBankAccounts(array $select_bank_account)
    {
        $selects = '';
        foreach ($select_bank_account as $key => $bank_account) {
            if ($select_bank_account[$key + 1] ?? false) {
                $selects .= "$bank_account, ";
            } else {
                $selects .= "$bank_account";
            }
        }


        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
                '(SELECT SUM(d.amount) FROM debts d WHERE d.from_account_id = legal_entities.id AND d.type_of_debt = "client_goods" AND d.status = "active" ) AS debt',
                'ba.balance as balance',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = id',
            ])
            ->where([
                "bank_account IN($selects)"
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsSupplierGoods($suppliers_id): array
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
                'd.id as debit_id',
                'd.from_account_id as from_account_id',
                'd.to_account_id as to_account_id',
                'd.type_of_debt as type_of_debt',
                'd.amount as debit_amount',
                'd.date as debit_date',
                'd.status as debit_status',
                't.description as description',
                't.amount as transaction_amount',
                't.date as transaction_date',
                't.id as transaction_id',
                't.percent as percent',
            ])
            ->innerJoin('debts d')
            ->on([
                'd.to_account_id = id',
                "d.type_of_debt ='supplier_goods'",
                "d.status = 'active'",
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = d.transaction_id',
            ])
            ->where([
                "supplier_id IN($suppliers_id)"
            ])
            ->groupBy('d.id');

        $supplier_goods = PdoConnector::execute($builder) ?? [];
        $supplier_goods_array = [];
        $sum_amount = 0;


        if (!$supplier_goods) {
            return [];
        }

        foreach ($supplier_goods as $good) {
            $sum_amount += $good->transaction_amount;

            $supplier_goods_array[] = [
                'legal_entities_id' => $good->id,
                'supplier_id' => $good->supplier_id,
                'debit_id' => $good->debit_id,
                'transaction_good_id' => $good->transaction_id,
                'good_company_name' => $good->company_name,
                'good_inn' => $good->inn,
                'good_percent' => $good->percent,
                'good_description' => $good->description,
                'good_amount' => round($good->transaction_amount),
                'good_date' => $good->debit_date,
                'debit_amount' => round($good->debit_amount),
            ];
        }

        foreach ($supplier_goods_array as $key => $good) {
            $supplier_goods_array[$key]['supplier_sum_amount'] = round($sum_amount);
        }


        //Logger::log(print_r($builder->build(), true), 'editDebtSupplier');


        return $supplier_goods_array;
    }

    public static function getDebtsSupplierGoodSum($suppliers_id)
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
                'SUM(d.amount) as sum_amount',
            ])
            ->innerJoin('debts d')
            ->on([
                'd.to_account_id = id',
                "d.type_of_debt ='supplier_goods'",
                "d.status = 'active'",
            ])
            ->where([
                "supplier_id IN($suppliers_id)"
            ])
            ->groupBy('d.id');


        //Logger::log(print_r($supplier_goods_array, true), 'editDebtSupplier');


        return PdoConnector::execute($builder)[0]->sum_amount ?? 0;
    }

    public static function getBankAccount(string $select_bank_account)
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'id',
                'client_id',
                'supplier_id',
                'bank_account',
                'our_account',
                'ba.balance as balance',
                'c.percentage as client_percent',
                's.percentage as supplier_percent',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = id',
            ])
            ->leftJoin('clients c')
            ->on([
                'c.id = client_id',
            ])
            ->leftJoin('suppliers s')
            ->on([
                's.id = supplier_id',
            ])
            ->where([
                "bank_account =" . "'$select_bank_account'"
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getEntitiesNull($offset, $limit): array
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'id',
                'bank_account',
                'bank_name',
                'inn',
                'kpp',
                'bic',
                'company_name',
                'correspondent_account',
                'our_account',
            ])
            ->where([
                "client_id IS NULL",
                "supplier_id IS NULL",
                "shop_id IS NULL",
                "our_account = 0",
            ])
            ->limit($limit)
            ->offset($offset);

        $get_entities_null = PdoConnector::execute($builder);
        $entities = [];

        foreach ($get_entities_null as $entity) {

            $get_transaction = TransactionsLM::getAllTransactionsUnknownAccount($entity->id);
            $transaction_data = [];
            $our_bank_name = '';
            $our_company_name = '';

            foreach ($get_transaction as $transaction) {
                $bank_account = '';
                $bank_name = '';
                $company_name = '';
                $inn = '';
                $formatted_date = '';

                if ($transaction->recipient_our_account){
                    $our_bank_name = $transaction->bank_name_recipient ?? '';
                    $our_company_name = $transaction->company_name_recipient ?? '';
                }
                if ($transaction->sender_our_account){
                    $our_bank_name = $transaction->bank_name_sender ?? '';
                    $our_company_name = $transaction->company_name_sender ?? '';
                }

                if ($transaction->type == 'expense') {
                    $bank_account = $transaction->bank_account_sender ?? '';
                    $bank_name = $transaction->bank_name_sender ?? '';
                    $company_name = $transaction->company_name_sender ?? '';
                    $inn = $transaction->inn_sender ?? '';
                }

                if ($transaction->type == 'income') {
                    $bank_account = $transaction->bank_account_recipient ?? '';
                    $bank_name = $transaction->bank_name_recipient ?? '';
                    $company_name = $transaction->company_name_recipient ?? '';
                    $inn = $transaction->inn_recipient ?? '';
                }

                $timestamp = strtotime($transaction->date);
                if ($timestamp) {
                    $formatted_date = date('d.m.Y', $timestamp);
                }


                $transaction_data[] = [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'date' => $formatted_date,
                    'description' => $transaction->description,
                    'bank_account' => $bank_account,
                    'bank_name' => $bank_name,
                    'company_name' => $company_name,
                    'inn' => $inn,
                ];
            }

            $entities[] = [
                'id' => $entity->id,
                'bank_account' => $entity->bank_account,
                'bank_name' => $entity->bank_name,
                'company_name' => $entity->company_name,
                'inn' => $entity->inn,
                'our_bank_name' => $our_bank_name,
                'our_company_name' => $our_company_name,
                'transaction_data' => $transaction_data,
            ];
        }


        //Logger::log(print_r($entities, true), 'entities');

        return array_values($entities);
    }

    public static function getEntitiesNullLegalId($legal_id): array
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'id',
                'bank_account',
                'bank_name',
                'inn',
                'kpp',
                'bic',
                'company_name',
                'correspondent_account',
                'ba.balance as balance',
                't_to.date as to_date',
                't_to.description as to_description',
                't_to.from_account_id as from_account_id',
                't_from.to_account_id as to_account_id',
                't_from.date as from_date',
                't_from.description as from_description',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = id',
            ])
            ->leftJoin('transactions t_to')
            ->on([
                't_to.to_account_id = id',
            ])
            ->leftJoin('transactions t_from')
            ->on([
                't_from.from_account_id = id',
            ])
            ->where([
                "client_id IS NULL",
                "supplier_id IS NULL",
                "our_account = 0",
                'id =' . $legal_id,
            ])
            ->limit(1);

        $entity = PdoConnector::execute($builder)[0] ?? null;

        if (!$entity) {
            return [];
        }

        $entities = [];

        //TODO от кого
        $from_whom = null;
        //TODO кому
        $to_whom = null;
        $legal_id_card = null;

        if ($entity->from_account_id) {
            $from_whom = self::getEntitiesId($entity->from_account_id);
            if ($from_whom->our_account) {
                $legal_id_card = $from_whom->id;
            }
        }

        if ($entity->to_account_id) {
            $to_whom = self::getEntitiesId($entity->to_account_id);
            if ($to_whom->our_account) {
                $legal_id_card = $to_whom->id;
            }
        }

        $date = $entity->to_date ?? $entity->from_date;
        $formatted_date = '';

        if (!empty($date)) {
            $timestamp = strtotime($date);
            if ($timestamp) {
                $formatted_date = date('d.m.Y', $timestamp);
            }
        }

        $entities[] = [
            'id' => $entity->id,
            'bank_account' => $entity->bank_account,
            'bank_name' => $entity->bank_name,
            'company_name' => $entity->company_name,
            'inn' => $entity->inn,
            'balance' => number_format(abs($entity->balance), 2, '.', ''),
            'transaction_data' => $formatted_date,
            'from_whom' => $from_whom->company_name ?? '',
            'to_whom' => $to_whom->company_name ?? '',
            'from_whom_bank' => $from_whom->bank_name ?? '',
            'to_whom_bank' => $to_whom->bank_name ?? '',
            'description' => $entity->to_description ?? $entity->from_description,
            'legal_id_card' => $legal_id_card
        ];

        return $entities;
    }

    public static function getEntitiesNulCount()
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'COUNT(id)',
            ])
            ->where([
                "client_id IS NULL",
                "supplier_id IS NULL",
                "shop_id IS NULL",
                "our_account = 0",
            ]);

        $unknown_accounts = PdoConnector::execute($builder)[0] ?? false;

        if ($unknown_accounts) {
            $unknown_accounts = $unknown_accounts->variables['COUNT(id)'];
        } else {
            $unknown_accounts = 0;
        }


        return $unknown_accounts;
    }

    public static function getEntitiesId($id)
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
                'ba.balance as balance',
                'ba.stock_balance as stock_balance',
                'ba.id as bank_account_id',
                's.percentage as supplier_percentage',
                'c.percentage as client_percentage',
                'u.name as user_name',
                'u.role as user_role',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = id',
            ])
            ->leftJoin('suppliers s')
            ->on([
                's.id = supplier_id',
            ])
            ->leftJoin('clients c')
            ->on([
                'c.id = client_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = s.user_id',
                'u.id = c.user_id',
            ], 'OR')
            ->where([
                'id =' . $id,
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getOurAccountBalance($id)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'ba.balance as balance',
            ])
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = id',
            ])
            ->where([
                'id =' . $id,
                'our_account =' . 1,
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->balance ?? null;
    }

    public static function getOurAccountOneId()
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'id as id',
            ])
            ->where([
                'our_account =' . 1,
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->id ?? null;
    }

    public static function getEntitiesOurAccount(): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'le.*',
                'MAX(ud.date) as max_uploaded_date',
            ])
            ->from('legal_entities as le')
            ->leftJoin('uploaded_documents ud')
            ->on([
                'ud.inn = le.inn',
            ])
            ->where([
                'le.our_account =' . 1,
            ])
            ->groupBy('le.id');

        $our_account = PdoConnector::execute($builder);
        $our_account_arr = [];
        $legal_in = [];

        if (!$our_account) {
            return [];
        }

        foreach ($our_account as $account) {
            $legal_in[] = $account->id;
            $date = '';
            $is_expired = false;

            if ($account->date_created ?? false) {
                $date = DateTime::createFromFormat('Y-m-d', $account->date_created);
                $date = $date->format('d.m.Y');
            }

            if ($account->max_uploaded_date ?? false) {

                $last_upload = (new DateTime())->setTimestamp($account->max_uploaded_date);
                $today = new DateTime('today');
                $today_11 = new DateTime('today 11:00');

                // Проверяем, что день сегодня > день последней загрузки
                if ($today->format('Y-m-d') > $last_upload->format('Y-m-d') && new DateTime() >= $today_11) {
                    $is_expired = true;
                }
            }

            $total_received_interest = $account->total_received ?? 0;
            if ($total_received_interest > 0) {
                $total_received_interest -= $total_received_interest * 0.29;
                $total_received_interest = round($total_received_interest, 2); // ограничение до 2 знаков
            }


            $our_account_arr[] = [
                'id' => $account->id,
                'company_name' => $account->company_name,
                'bank_name' => $account->bank_name,
                'inn' => $account->inn,
                'total_received' => $account->total_received,
                'total_written_off' => $account->total_written_off,
                'final_remainder' => $account->final_remainder,
                'total_received_interest' => $total_received_interest,
                'date_created' => $date,
                'is_expired' => $is_expired,
            ];
        }

        $company_cards = CreditCardsLM::getAllLegalCardNumbersByIds($legal_in);

        foreach ($company_cards as $card) {
            foreach ($our_account_arr as $key => $account) {

                if ($account['id'] == $card->legal_id) {
                    $our_account_arr[$key]['cards'][] = [
                        'id' => $card->id,
                        'card_number' => chunk_split($card->card_number, 4, ' '), // каждые 4 символа + пробел
                        'balance' => $card->balance,
                        'date' => $card->date,
                    ];
                }
            }
        }
        usort($our_account_arr, function ($a, $b) {
            $a_no_date = empty($a['date_created']);
            $b_no_date = empty($b['date_created']);

            $a_zero = empty($a['final_remainder']);
            $b_zero = empty($b['final_remainder']);

            if ($a_no_date !== $b_no_date) {
                return $a_no_date <=> $b_no_date;
            }
            return $a_zero <=> $b_zero;
        });

        //Logger::log(print_r($our_account_arr, true), 'our_account_arr');
        return $our_account_arr;
    }

    public static function getEntitiesBalance()
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(CASE WHEN le.our_account = 1 THEN ba.balance ELSE 0 END) AS our_account_balance',
                '(SELECT SUM(d.amount) FROM debts d WHERE d.type_of_debt = "supplier_goods" AND d.status = "active") AS supplier_goods_balance',
                '(SELECT SUM(d.amount) FROM debts d WHERE d.type_of_debt = "client_goods" AND d.status = "active") AS client_goods_balance',
                '(SELECT SUM(d.amount) FROM debts d WHERE d.type_of_debt = "client_services" AND d.status = "active") AS client_services_balance',
                '(SELECT SUM(d.amount) FROM debts d WHERE d.type_of_debt = "сlient_debt" AND d.status = "active") AS сlient_debt',
                '(SELECT SUM(current_balance) FROM couriers) AS couriers_balance',
            ])
            ->from('legal_entities le')
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = le.id',
            ]);


        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getSupplierAccounts($supplier_id)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'GROUP_CONCAT(DISTINCT id) as accounts_id',
            ])
            ->where([
                'supplier_id =' . $supplier_id,
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function updateLegalEntities(array $data, $id)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getEntitiesClientTransactions($client_id, $offset, $limit, $date_from, $date_to, $supplier_client_id = null): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'client_id as client_id',
                'bank_name as sender_bank_name',
                'company_name as sender_company_name',
                't.description as description',
                't.amount as transaction_amount',
                't.percent as transaction_percent',
                't.interest_income as transaction_interest_income',
                't.date as transaction_date',
                't.id as transaction_id',
                't.date as date',
                'le.id as legal_id',
                'le.bank_name as recipient_bank_name',
                'le.company_name as recipient_company_name',
                'd.writing_transaction_id as writing_transaction_id',
                'd.amount as debit_amount',
                'd.id as debit_id',
                'd.status as debit_status',
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
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.to_account_id",
            ]);


        if ($supplier_client_id) {
            $builder
                ->innerJoin('debts d')
                ->on([
                    "d.transaction_id = t.id",
                    "d.type_of_debt = 'сlient_debt_supplier'",
                ])
                ->where([
                    'supplier_client_id =' . $supplier_client_id
                ]);
        } else {
            $builder
                ->innerJoin('debts d')
                ->on([
                    "d.transaction_id = t.id",
                    "d.type_of_debt = 'client_goods'",
                ])
                ->where([
                    'client_id =' . $client_id
                ]);
        }

        $builder
            ->groupBy('t.id')
            ->orderBy('t.date', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $transactions = PdoConnector::execute($builder) ?? null;
        $transactions_arr = [];

        if (!$transactions) {
            return [];
        }

        foreach ($transactions as $transaction) {
            $company_finances = '';
            $date_of_issue = '';

            if ($transaction->writing_transaction_id) {
                $company_finances = CompanyFinancesLM::getWritingTransactionById($transaction->writing_transaction_id);
                if ($company_finances->issue_date ?? null) {
                    $date_of_issue = date('d.m.Y', strtotime($company_finances->issue_date));
                }
            }

            $transactions_arr[] = [
                'transaction_id' => $transaction->transaction_id,
                'legal_id' => $transaction->legal_id,
                'description' => $transaction->description,
                'date' => date('d.m.Y', strtotime($transaction->date)),
                'percent' => $transaction->transaction_percent,
                'interest_income' => $transaction->transaction_interest_income,
                'total_amount' => $transaction->transaction_amount - $transaction->transaction_interest_income,
                'debit_amount' => $transaction->debit_status == 'active' ? $transaction->debit_amount : 0,
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
                'issuance' => $company_finances->amount ?? '',
                'who_issued_it' => $company_finances->username ?? '',
                'date_of_issue' => $date_of_issue,
                'comments' => $company_finances->comments ?? '',
            ];
        }

        //Logger::log(print_r($transactions_arr, true), 'transactions_arr');

        return $transactions_arr;
    }

    public static function getEntitiesClientTransactionsCount($client_id, $date_from, $date_to, $supplier_client_id = null): int
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'COUNT(t.id) as transactions_count',
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
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                ]);
        }

        $builder
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.to_account_id",
            ]);

        if ($supplier_client_id) {
            $builder
                ->where([
                    'supplier_client_id =' . $supplier_client_id
                ]);
        } else {
            $builder
                ->where([
                    'client_id =' . $client_id
                ]);
        }

        $builder
            ->groupBy('t.id')
            ->limit(1);

        return PdoConnector::execute($builder)[0]->transactions_count ?? 0;
    }

    public static function getEntitiesClientTransactionsSum($client_id, $date_from, $date_to, $supplier_client_id = null): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
                'SUM(t.interest_income) as sum_interest_income',
                'SUM(d.amount) as debt_amount',
            ])
            ->from('legal_entities le');

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
                    "t.from_account_id = le.id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = le.id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = le.id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->leftJoin('debts d')
            ->on([
                "d.transaction_id = t.id",
                "d.status = 'active'",
            ]);


        if ($supplier_client_id) {
            $builder
                ->where([
                    'le.supplier_client_id =' . $supplier_client_id
                ]);
        } else {
            $builder
                ->where([
                    'le.client_id =' . $client_id
                ]);
        }

        $builder
            ->groupBy('le.client_id');

        $transactions = PdoConnector::execute($builder)[0] ?? [];

        $sum_amount = $transactions->sum_amount ?? 0;
        $sum_interest_income = $transactions->sum_interest_income ?? 0;
        $debt_amounts = $transactions->debt_amount ?? 0;

        $transactions_sum = [
            'sum_amount' => $sum_amount,
            'sum_interest_income' => $sum_interest_income,
            'debts_amount' => $debt_amounts,
        ];

        //Logger::log(print_r($builder->build(), true), 'clientReceiptsDate');

        return $transactions_sum;
    }

    public static function getEntitiesClientServicesTransactions($supplier_id, $offset, $limit, $date_from, $date_to, $manager_id = null, $client_id = null): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'client_id as client_id',
                'bank_name as sender_bank_name',
                'company_name as sender_company_name',
                't.description as description',
                't.amount as transaction_amount',
                't.percent as transaction_percent',
                't.interest_income as transaction_interest_income',
                't.date as transaction_date',
                't.id as transaction_id',
                't.date as date',
                'le.id as legal_id',
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
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.to_account_id",
            ])
            ->where([
                'supplier_id =' . $supplier_id,
                'client_services =' . 1,
            ]);

        if ($manager_id) {
            $builder
                ->where([
                    'manager_id =' . $manager_id,
                ]);
        }

        if ($client_id) {
            $builder
                ->where([
                    'client_service_id =' . $client_id,
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
                'legal_id' => $transaction->legal_id,
                'description' => $transaction->description,
                'date' => date('d.m.Y', strtotime($transaction->date)),
                'percent' => $transaction->transaction_percent,
                'interest_income' => $transaction->transaction_interest_income,
                'total_amount' => $transaction->transaction_amount - $transaction->transaction_interest_income,
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
            ];
        }


        //Logger::log(print_r($transactions_arr, true), 'clientReceiptsDate');

        return $transactions_arr;
    }

    public static function getEntitiesClientServicesTransactionsSum($supplier_id, $date_from, $date_to, $manager_id = null, $client_id = null): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
                'SUM(t.interest_income) as sum_interest_income',
                'SUM(d_client_services.amount) as debt_client_services',
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
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->leftJoin('debts d_client_services')
            ->on([
                "d_client_services.transaction_id = t.id",
                "d_client_services.type_of_debt = 'client_services'",
                "d_client_services.status = 'active'",
            ]);


        $builder
            ->where([
                'supplier_id =' . $supplier_id,
                'client_services =' . 1,
            ]);

        if ($manager_id) {
            $builder
                ->where([
                    'manager_id =' . $manager_id,
                ]);
        }

        if ($client_id) {
            $builder
                ->where([
                    'client_service_id =' . $client_id,
                ]);
        }

        $builder
            ->groupBy('legal_entities.supplier_id');

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


    public static function getEntitiesClientServicesManagerSum($date, int $manager_id)
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
            ]);

        if ($date) {
            $date = DateTime::createFromFormat('d.m.Y', $date);
            $date = $date->format('Y-m-d');
        }


        if ($date) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date ='" . $date . "'",
                ]);
        }


        $builder
            ->where([
                'client_services =' . 1,
                'manager_id =' . $manager_id,
            ])
            ->groupBy('legal_entities.supplier_id');

        $transactions = PdoConnector::execute($builder)[0] ?? [];

        $sum_amount = $transactions->sum_amount ?? 0;


        return ['sum_amount' => $sum_amount];
    }


    public static function getEntitiesClientServicesTransactionsReturnSum($supplier_id, $date_from, $date_to, $manager_id = null, $client_id = null): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
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
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                    "t.type = 'return_client_services'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.type = 'return_client_services'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                    "t.type = 'return_client_services'",
                ]);
        }

        $builder
            ->where([
                'supplier_id =' . $supplier_id,
                'client_services =' . 1,
            ]);

        if ($manager_id) {
            $builder
                ->where([
                    'manager_id =' . $manager_id,
                ]);
        }

        if ($client_id) {
            $builder
                ->where([
                    'client_service_id =' . $client_id,
                ]);
        }

        $builder
            ->groupBy('legal_entities.supplier_id');

        $transactions = PdoConnector::execute($builder)[0] ?? [];

        $sum_amount = $transactions->sum_amount ?? 0;


        return [
            'sum_amount' => $sum_amount,
        ];
    }


    public static function getEntitiesClientServicesTransactionsCount($supplier_id, $date_from, $date_to, $manager_id = null, $client_id = null): int
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'COUNT(t.id) as transactions_count',
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
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        $builder
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.to_account_id",
            ])
            ->where([
                'supplier_id =' . $supplier_id,
                'client_services =' . 1,
            ]);


        if ($manager_id) {
            $builder
                ->where([
                    'manager_id =' . $manager_id,
                ]);
        }

        if ($client_id) {
            $builder
                ->where([
                    'client_service_id =' . $client_id,
                ]);
        }


        $builder
            ->groupBy('id')
            ->limit(1);

        return PdoConnector::execute($builder)[0]->transactions_count ?? 0;
    }

    public static function getEntitiesSuppliersTransactions($supplier_id, $offset, $limit, $date_from, $date_to, $client_services = 0): array
    {
        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'client_id as client_id',
                'bank_name as sender_bank_name',
                'company_name as sender_company_name',
                't.description as description',
                't.amount as transaction_amount',
                'SUM(t.amount) as transaction_amount_sum',
                't.percent as transaction_percent',
                't.interest_income as transaction_interest_income',
                't.date as transaction_date',
                't.id as transaction_id',
                't.date as date',
                'le.id as legal_id',
                'le.bank_name as recipient_bank_name',
                'le.company_name as recipient_company_name',
            ]);

        if ($date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                ]);
        }


        $builder
            ->innerJoin('debts d')
            ->on([
                "d.transaction_id = t.id",
                "d.type_of_debt = 'supplier_goods'",
            ])
            ->innerJoin('legal_entities le')
            ->on([
                "le.id = t.from_account_id",
            ]);

        if ($client_services == 0) {
            $builder
                ->where([
                    'supplier_id =' . $supplier_id,
                    'client_services =' . 0
                ]);
        } else {
            $builder
                ->where([
                    'supplier_id =' . $supplier_id,
                ]);
        }

        $builder
            ->groupBy('id')
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
                'date' => date('d.m.Y', strtotime($transaction->date)),
                'percent' => $transaction->transaction_percent,
                'interest_income' => $transaction->transaction_interest_income,
                'total_amount' => $transaction->transaction_amount - $transaction->transaction_interest_income,
                'transaction_amount' => $transaction->transaction_amount,
                'sender_bank_name' => $transaction->sender_bank_name,
                'sender_company_name' => $transaction->sender_company_name,
                'recipient_bank_name' => $transaction->recipient_bank_name,
                'recipient_company_name' => $transaction->recipient_company_name,
                'transaction_amount_sum' => $transaction->transaction_amount_sum,
            ];
        }


        //Logger::log(print_r($builder->build(), true), 'clientReceiptsDate');

        return $transactions_arr;
    }

    public static function getTransitDebt($supplier_id, $date_from, $date_to)
    {
        if ($date_from) {
            $date_from = DateTime::createFromFormat('d.m.Y', $date_from);
            $date_from = $date_from->format('Y-m-d');
        }

        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(d.amount) as debt_amount',
                'SUM(t.amount) as transit_amount',
            ]);


        if ($date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.from_account_id = id",
                ]);
        }

        $builder
            ->innerJoin('debts d')
            ->on([
                "d.transaction_id = t.id",
                "d.type_of_debt = 'client_services'",
            ]);

        $builder
            ->where([
                'supplier_id =' . $supplier_id,
                'client_services =' . 1
            ])
            ->groupBy('id');


        $Legal_entities = PdoConnector::execute($builder)[0] ?? null;


        return [
            'transit_debt_amount' => $Legal_entities->debt_amount ?? 0,
            'transit_amount' => $Legal_entities->transit_amount ?? 0,
        ];
    }

    public static function getSupplierGoodDebt($supplier_id, $date_to)
    {
        if ($date_to) {
            $date_to = DateTime::createFromFormat('d.m.Y', $date_to);
            $date_to = $date_to->format('Y-m-d');
        }

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(d.amount) as debt_up_to_this_point',
            ])
            ->innerJoin('debts d')
            ->on([
                "d.to_account_id = id",
                "d.type_of_debt = 'supplier_goods'",
                "d.status = 'active'",
                "d.date <='" . $date_to . "'",
            ])
            ->where([
                'supplier_id =' . $supplier_id,
                'client_services =' . 0
            ]);


        return PdoConnector::execute($builder)[0]->debt_up_to_this_point ?? 0;

    }

    public static function getEntitiesSuppliersTransactionsSum($supplier_id, $date_from, $date_to, $client_services = 0): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'SUM(t.amount) as sum_amount',
                'SUM(t.interest_income) as sum_interest_income',
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
                    "t.to_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                ]);
        }

        if ($client_services == 0) {
            $builder
                ->where([
                    'supplier_id =' . $supplier_id,
                    'client_services =' . 0
                ]);
        } else {
            $builder
                ->where([
                    'supplier_id =' . $supplier_id,
                ]);
        }

        $builder
            ->groupBy('supplier_id');

        $transactions = PdoConnector::execute($builder)[0] ?? [];

        $sum_amount = $transactions->sum_amount ?? 0;
        $sum_interest_income = $transactions->sum_interest_income ?? 0;

        $transactions_sum = [
            'sum_amount' => $sum_amount,
            'sum_interest_income' => $sum_interest_income,
            'debts_amount' => $sum_amount - $sum_interest_income,
        ];


        //Logger::log(print_r($transactions_arr, true), 'clientReceiptsDate');

        return $transactions_sum;
    }

    public static function getEntitiesSuppliersTransactionsCount($supplier_id, $date_from, $date_to, $client_services = 0): int
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'COUNT(t.id) as transactions_count',
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
                    "t.to_account_id = id",
                    "t.date >='" . $date_from . "'",
                ]);
        }

        if ($date_from && $date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                    "t.date BETWEEN '" . $date_from . "'" . "AND '" . $date_to . "'",
                ]);
        }

        if (!$date_from && !$date_to) {
            $builder
                ->innerJoin('transactions t')
                ->on([
                    "t.to_account_id = id",
                ]);
        }


        if ($client_services == 0) {
            $builder
                ->where([
                    'supplier_id =' . $supplier_id,
                    'client_services =' . 0
                ]);
        } else {
            $builder
                ->where([
                    'supplier_id =' . $supplier_id,
                ]);
        }

        $builder
            ->limit(1);

        return PdoConnector::execute($builder)[0]->transactions_count ?? 0;

    }

    public static function getClients(int $offset = 0, int $limit = 8): array
    {

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'our_account =' . 1,
            ])
            ->limit($limit)
            ->offset($offset);

        $our_accounts = PdoConnector::execute($builder);

        if (!$our_accounts) {
            return [];
        }

        $selects = [];

        foreach ($our_accounts as $key => $account) {
            $selects[] = $account->bank_account;
        }

        $selects = implode(', ', $selects);


        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
                'ba.balance as balance',
                'd.amount as debit_amount',
            ])
            ->from('legal_entities')
            ->leftJoin('bank_accounts ba')
            ->on([
                'ba.legal_entity_id = le.id',
            ])
            ->leftJoin('credit_cards с')
            ->on([
                'с.legal_id = le.id',
            ])
            ->where([
                "bank_account IN($selects)"
            ]);


        $clients_array = [];
        $clients = PdoConnector::execute($builder);

        if (!$clients) {
            return [];
        }

        foreach ($clients as $client) {
            $existing_index = array_search($client->id, array_column($clients_array, 'id'));
            $balance_sum = $client->balance ?? 0;
            $debit_amount_sum = $client->debit_amount ?? 0;
            $bank_accounts = null;

            if ($client->bank_account ?? false) {
                $bank_accounts = [
                    'account' => $client->bank_account,
                    'inn' => $client->inn,
                    'company_name' => $client->company_name,
                    'balance' => $balance_sum,
                    'debit_amount' => $debit_amount_sum,
                    'le_id' => $client->le_id,
                ];
            }

            if ($existing_index === false) {
                $clients_array[] = [
                    'id' => $client->id,
                    'email' => $client->email,
                    'role' => $client->role,
                    'name' => $client->username,
                    'password' => $client->password,
                    'percentage' => $client->percentage,
                    'balance_sum' => $balance_sum,
                    'debit_amount_sum' => $debit_amount_sum,
                    'user_id' => $client->user_id,
                    'created_at' => $client->created_at,
                    'bank_accounts' => $bank_accounts ? [$bank_accounts] : [],
                ];
            } else {
                $clients_array[$existing_index]['balance_sum'] += $balance_sum;
                $clients_array[$existing_index]['debit_amount_sum'] += $debit_amount_sum;

                if ($bank_accounts) {
                    $clients_array[$existing_index]['bank_accounts'][] = $bank_accounts;
                }
            }
        }


        Logger::log(print_r($clients_array, true), 'clients_array');

        return $clients_array;
    }

    public static function getOurCompanyInfo()
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*'
            ])
            ->where([
                'our_account =' . 1,
            ]);

        return PdoConnector::execute($builder)[0] ?? null;
    }

    /**
     * Получить id юр.лица по client_id
     */
    public static function getEntityIdByClient(int $client_id): ?int
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select(['id'])
            ->where(['client_id =' . $client_id])
            ->limit(1);
        $row = PdoConnector::execute($builder)[0] ?? null;
        return $row?->id;
    }

    /**
     * Получить id юр.лица по supplier_id
     */
    public static function getEntityIdBySupplier(int $supplier_id): ?int
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select(['id'])
            ->where(['supplier_id =' . $supplier_id])
            ->limit(1);
        $row = PdoConnector::execute($builder)[0] ?? null;
        return $row?->id;
    }

    /**
     * Получить id юр.лица по courier_id
     */
    public static function getEntityIdByCourier(int $courier_id): ?int
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select(['id'])
            ->where(['courier_id =' . $courier_id])
            ->limit(1);
        $row = PdoConnector::execute($builder)[0] ?? null;
        return $row?->id;
    }

    public static function getNonOurCompanies(): array|null
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*'
            ])
            ->where([
                'our_account =' . 0,
            ]);

        $companies = PdoConnector::execute($builder) ?? [];

        $companies_arr = [];

        if (!$companies) {
            return [];
        }


        foreach ($companies as $account) {
            $companies_arr[] = [
                'id' => $account->id,
                'company_name' => $account->company_name,
                'bank_name' => $account->bank_name,
                'inn' => $account->inn,
                'total_received' => $account->total_received,
                'total_written_off' => $account->total_written_off,
                'final_remainder' => $account->final_remainder,
            ];
        }

        return $companies_arr;
    }

    public static function getClientsServicesSuppler(int $supplier_id): string
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'GROUP_CONCAT(id SEPARATOR ' . '", "' . ') as legal_id',
            ])
            ->where([
                'supplier_id = ' . $supplier_id,
                'client_services = ' . 0,
            ]);


        return PdoConnector::execute($builder)[0]->legal_id ?? '';
    }

    public static function getLegalNoManagers($supplier_id): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'le.*',
                'recipient_le.id as recipient_le_id',
                'recipient_le.bank_name as recipient_bank_name',
                'recipient_le.company_name as recipient_company_name',
                't.id as transaction_id',
                't.description as description',
                't.date as date',
                't.amount as amount',
                't.interest_income as interest_income',
                't.percent as percent',
                't.status as status',
            ])
            ->from('legal_entities as le')
            ->innerJoin('transactions t')
            ->on([
                't.from_account_id = le.id',
            ])
            ->leftJoin('legal_entities recipient_le')
            ->on([
                'recipient_le.id = t.to_account_id',
            ])
            ->where([
                "le.manager_id IS NULL",
                "le.supplier_client_id IS NULL",
                "le.supplier_id =" . $supplier_id,
                "le.client_services =" . 1
            ])
            ->groupBy('t.id');

        $no_managers = PdoConnector::execute($builder);
        $no_managers_arr = [];


        foreach ($no_managers as $no_manager) {
            $date = '';

            if (!empty($no_manager->date)) {
                try {
                    $dt = new DateTime($no_manager->date);
                    $date = $dt->format('d.m.Y');
                } catch (Exception $e) {
                    // если дата некорректная, оставляем пустую строку
                    $date = '';
                }
            }

            $no_managers_arr[] = [
                'id' => $no_manager->id,
                'date' => $date,
                'company_name' => $no_manager->company_name,
                'description' => $no_manager->description,
                'amount' => $no_manager->amount,
            ];
        }

        return $no_managers_arr;
    }

    public static function getLegalSupplierCompany($supplier_id): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'le.*',
                'b.balance as balance',
                'b.stock_balance as stock_balance',
            ])
            ->from('legal_entities as le')
            ->leftJoin('bank_accounts as b')
            ->on([
                'b.legal_entity_id = le.id',
            ])
            ->where([
                "le.supplier_id =" . $supplier_id,
                "le.client_services =" . 0
            ]);

        $legal_entities = PdoConnector::execute($builder);
        $legal_entities_arr = [];


        foreach ($legal_entities as $entities) {

            $legal_entities_arr[] = [
                'id' => $entities->id,
                'company_name' => $entities->company_name,
                'bank_name' => $entities->bank_name,
                'inn' => $entities->inn,
                'balance' => $entities->balance,
                'stock_balance' => $entities->stock_balance,
            ];
        }


        return $legal_entities_arr;
    }

}