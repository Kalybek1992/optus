<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\DebtClosings;
use Source\Project\Models\StatementLog;


/**
 *
 */
class StatementLogLM
{
    public static function updateStatementLog(array $data, int $id)
    {

        $builder = StatementLog::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function setNewStatementLog(array $data)
    {
        $builder = StatementLog::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getStatementLogMaxId(): int
    {
        $builder = StatementLog::newQueryBuilder()
            ->select([
                'MAX(id) as max_id'
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0]->max_id + 1 ?? 1;
    }


    public static function getStatementLogDate($date = null)
    {
        if (!empty($date)) {
            $dt = DateTime::createFromFormat('d.m.Y', $date);
            $check_date = $dt ? $dt->format('Y-m-d') : date('Y-m-d');
        } else {
            $check_date = date('Y-m-d');
        }


        $builder = StatementLog::newQueryBuilder()
            ->select([
                '*'
            ])->where([
                "status =" . 0,
            ]);

        return PdoConnector::execute($builder);
    }


    public static function getStatementLogStatusError(): array
    {
        $builder = StatementLog::newQueryBuilder()
            ->select([
                '*'
            ])->where([
                "status =" . 0,
                "steps IS NOT NULL",
            ]);

        $statement_log = PdoConnector::execute($builder);

        $error_load = [];
        foreach ($statement_log as $key => $value) {
            $steps_string = $value->steps;
            $steps_string = trim($steps_string, '"');
            $steps_array = @unserialize($steps_string);

            if ($steps_array === false && $steps_string !== 'b:0;') {
                $steps_array = json_decode($steps_string, true);
            }

            $or_account = $steps_array['or_account'] ?? 0;
            $legal = LegalEntitiesLM::getOurAccountId($or_account);
            $dt = new \DateTime($value->created_at);
            $date_ru = $dt->format('d.m.Y');
            $time_ru = $dt->format('H:i');

            $error_load[] = [
                'id' => $value->id,
                'company_name' => $legal->company_name ?? 'Неизвестно',
                'date' => $date_ru,
                'time' => $time_ru,
            ];
        }

        return $error_load;
    }

    public static function getStatementLogStepsError(int $id): array
    {
        $builder = StatementLog::newQueryBuilder()
            ->select([
                '*'
            ])->where([
                "id =" . $id,
            ])
            ->limit(1);

        $statement_log = PdoConnector::execute($builder)[0] ?? [];

        if (!$statement_log) {
            return [];
        }

        $steps_string = $statement_log->steps;
        $steps_string = trim($steps_string, '"');
        $steps_array = @unserialize($steps_string);

        if ($steps_array === false && $steps_string !== 'b:0;') {
            $steps_array = json_decode($steps_string, true);
        }

        return [
            'id' => $statement_log->id,
            'steps_array' => $steps_array,
        ];
    }

    public static function rollbackError(array $errors): void
    {
        self::lastStatementDownloadDel($errors);
        self::updateKnownLegalEntitiesTotals($errors);
        self::uploadedDocumentsDel($errors);
        self::bankOrdersDel($errors);
        self::expenditureOnGoodsDel($errors);
        self::updateBalanceSupplier($errors);
        self::balanceSupplierDel($errors);
        self::closeDebt($errors, 'close_supplier_debt');
        self::closeDebt($errors, 'close_client_services_debt');
        self::closeDebt($errors, 'close_clients_debt');
        self::transactionsDel($errors);
        self::accountsDel($errors);
        self::endOfDaySettlement($errors);
    }

    public static function lastStatementDownloadDel(array $errors): void
    {
        if (isset($errors['last_statement_download']) && $errors['last_statement_download']) {
            $id = $errors['last_statement_download'];
            UploadedLogLM::deleteUploadedLog($id);
        }
    }

    public static function updateKnownLegalEntitiesTotals(array $errors): void
    {
        if (isset($errors['update_known_legal_entities_totals']) && $errors['update_known_legal_entities_totals']) {

            $update_known = $errors['update_known_legal_entities_totals'];

            if ($update_known['total_received'] > 0 || $update_known['total_written_off'] > 0 || $update_known['final_remainder'] > 0) {
                LegalEntitiesLM::updateLegalEntities([
                    'total_received =' . $update_known['total_received'],
                    'total_written_off =' . $update_known['total_written_off'],
                    'final_remainder =' . $update_known['final_remainder'],
                    'date_created =' . $update_known['date_created'],
                ], $errors['or_account'] ?? 0);
            }

            $id = $errors['last_statement_download'];
            UploadedLogLM::deleteUploadedLog($id);
        }
    }

    public static function uploadedDocumentsDel(array $errors): void
    {
        if (isset($errors['uploaded_documents']) && $errors['uploaded_documents']) {
            $ids = $errors['uploaded_documents'];
            $ids = implode(", ", $ids);

            UploadedDocumentsLM::deleteUploadedDocumentsIds($ids);
        }
    }

    public static function bankOrdersDel(array $errors): void
    {
        if (isset($errors['new_bank_orders']) && $errors['new_bank_orders']) {
            $bank_orders = $errors['new_bank_orders'];


            $bank_orders = implode(", ", $bank_orders);

            BankOrderLM::deleteInBankOrders($bank_orders);
        }
    }

    public static function expenditureOnGoodsDel(array $errors): void
    {
        if (isset($errors['expenditure_on_goods']) && $errors['expenditure_on_goods']) {
            $transaction_id = $errors['expenditure_on_goods'];


            $transaction_id = implode(", ", $transaction_id);

            DebtsLM::deleteTransactionIdGoods($transaction_id);
        }
    }

    public static function updateBalanceSupplier(array $errors): void
    {
        if (isset($errors['update_balance_supplier']) && $errors['update_balance_supplier']) {
            $update_balance_supplier = $errors['update_balance_supplier'];

            foreach ($update_balance_supplier as $key => $value) {
                $id = $value['id'];
                $supplier_balance = SupplierBalanceLM::getSupplierBalanceId($id);

                $new_balance = $supplier_balance->amount - $value['amount'];

                SupplierBalanceLM::updateSupplierBalance(
                    [
                        'amount = ' . $new_balance,
                    ],
                    $supplier_balance->id,
                );
            }
        }
    }

    public static function balanceSupplierDel(array $errors): void
    {
        if (isset($errors['insert_new_balance']) && $errors['insert_new_balance']) {
            $ids = $errors['insert_new_balance'];
            $ids = implode(", ", $ids);

            SupplierBalanceLM::deleteBalanceIds($ids);
        }
    }

    public static function closeDebt(array $errors, string $type): void
    {
        if (isset($errors[$type]) && $errors[$type]) {
            $ids = $errors[$type];
            $transaction_ids = implode(", ", $ids);

            $debt = DebtClosingsLM::getDebtClosingsInTransactionId($transaction_ids);

            foreach ($debt as $key => $value) {
                $debit_id = $value->debt_id;
                $amount = $value->amount;
                $debit = DebtsLM::getDebtId($debit_id);

                if ($debit) {
                    if ($debit->amount == $amount && $debit->debt_type == 'paid') {
                        DebtsLM::updateDebtsId([
                            'status = active',
                        ], $debit->id);
                    }

                    if ($debit->amount != $amount && $debit->debt_type == 'active') {
                        $new_amount = $debit->amount + $amount;

                        DebtsLM::updateDebtsId([
                            'amount =' . $new_amount,
                        ], $debit->id);
                    }
                }
            }

            $goods_type = [
                'close_supplier_debt' => 'supplier_debt',
                'close_client_services_debt' => 'client_services_debt',
                'close_clients_debt' => 'clients_debt',
            ][$type];

            DebtClosingsLM::deleteDebtClosingsInTransactionId($transaction_ids);
            DebtsLM::deleteTransactionIdGoodsType($transaction_ids, $goods_type);
        }
    }

    public static function transactionsDel(array $errors): void
    {
        if (isset($errors['add_new_transactions']) && $errors['add_new_transactions']) {
            $transactions_ids = $errors['add_new_transactions'];


            $transactions_ids  = implode(", ", $transactions_ids);

            TransactionsLM::deleteTransactionsIds($transactions_ids);
        }
    }

    public static function accountsDel(array $errors): void
    {
        if (isset($errors['add_new_accounts']) && $errors['add_new_accounts']) {
            $accounts_ids = $errors['add_new_accounts'];

            if (isset($errors['add_or_new_account']) && $errors['add_or_new_account']) {
                $accounts_id = $errors['add_or_new_account'];

                $accounts_ids[] = $accounts_id;
            }

            $accounts_ids  = implode(", ", $accounts_ids);

            LegalEntitiesLM::deleteLegalsEntitiesIds($accounts_ids);
        }
    }

    public static function endOfDaySettlement(array $errors): void
    {
        if (isset($errors['end_of_day_settlement']) && $errors['end_of_day_settlement']) {
            $end_of_day_settlement = $errors['end_of_day_settlement'];

            EndOfDaySettlementLM::updateEndOfDayTransactions($end_of_day_settlement);
        }
    }

}