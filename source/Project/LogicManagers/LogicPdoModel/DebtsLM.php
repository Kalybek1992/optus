<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\Debts;
use Source\Project\Models\LegalEntities;


/**
 *
 */
class DebtsLM
{
    public static function updateDebts(array $data, $legal_id)
    {

        $builder = Debts::newQueryBuilder()
            ->update($data)
            ->where([
                'from_account_id =' . $legal_id
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getDebtsFromAccountIdIn($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                'from_account_id IN(' . $legal_id . ')',
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsFromClientGoods($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                'from_account_id IN(' . $legal_id . ')',
                "type_of_debt = 'client_goods'",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function deleteTransactionIdGoods(string $transaction_id)
    {
        $builder = Debts::newQueryBuilder()
            ->delete()
            ->where([
                'transaction_id IN(' . $transaction_id . ')',
            ]);

        return PdoConnector::execute($builder);
    }

    public static function deleteTransactionIdGoodsType(string $transaction_id, string $goods_type)
    {
        $builder = Debts::newQueryBuilder()
            ->delete()
            ->where([
                'transaction_id IN(' . $transaction_id . ')',
                'type_of_debt = ' . $goods_type,
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsFromSupplierGoods($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                'to_account_id IN(' . $legal_id . ')',
                "type_of_debt = 'supplier_goods'",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsFromClientDebtSuppliers($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                'from_account_id IN(' . $legal_id . ')',
                "type_of_debt = 'сlient_debt_supplier'",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsCompanyMutual($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                '(' . $legal_id . ' IS NOT NULL AND (from_account_id IN(' . $legal_id . ') OR to_account_id IN(' . $legal_id . ')))',
                "type_of_debt IN ('supplier_debt', 'client_services_debt', 'сlient_debt')",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsFromClientServices($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                'from_account_id IN(' . $legal_id . ')',
                "type_of_debt = 'client_services'",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function deleteAllActiveDebtUser(int $legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->delete()
            ->where([
                "(from_account_id = '$legal_id' OR to_account_id = '$legal_id')",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsFromCompanies($legal_id)
    {
        $builder = Debts::newQueryBuilder()
            ->select()
            ->where([
                'to_account_id IN(' . $legal_id . ')',
                "type_of_debt = 'supplier_goods'",
                "status = 'active'",
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtId($id)
    {
        $builder = Debts::newQueryBuilder()
            ->select([
                '*'
            ])
            ->where([
                "id =" . $id,
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function updateDebtsId(array $data, $id)
    {

        $builder = Debts::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function updateDebtsTransactionId(array $data, $transaction_id)
    {

        $builder = Debts::newQueryBuilder()
            ->update($data)
            ->where([
                'transaction_id =' . $transaction_id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function updateDebtsClientSupplier(array $data, $transaction_id)
    {

        $builder = Debts::newQueryBuilder()
            ->update($data)
            ->where([
                'transaction_id =' . $transaction_id,
                'type_of_debt =' . '"сlient_debt_supplier"',
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getDebtsTypeSumLegalId($transaction_id)
    {

        $builder = Debts::newQueryBuilder()
            ->select(['amount as amount_debit'])
            ->where([
                'transaction_id =' . $transaction_id,
                'status =' . '"active"',
                'type_of_debt !=' . '"сlient_debt_supplier"',
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0]->amount_debit ?? null;
    }

    public static function setNewDebts(array $data)
    {
        $builder = Debts::newQueryBuilder()
            ->insert($data);


        return PdoConnector::execute($builder);
    }

    public static function editDebtTransactionId($transaction_id, $new_debit): bool
    {
        $debt = self::getDebtsTypeSumLegalId($transaction_id);

        if (!$debt) {
            return false;
        }

        self::updateDebtsTransactionId([
            'amount =' . $new_debit,
        ], $transaction_id);


        return true;
    }

    public static function getDebtsClientServicesCount(): bool
    {

        $builder = Debts::newQueryBuilder()
            ->select([
                'amount as amount',
                'le.supplier_id as supplier_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id'
            ])
            ->where([
                'type_of_debt ="client_services"',
                'status =' . '"active"'
            ])
            ->groupBy('id');

        $client_services = PdoConnector::execute($builder);
        $suppliers_id = [];

        if (!$client_services) {
            return false;
        }

        foreach ($client_services as $client_service) {
            if (!in_array($client_service->supplier_id, $suppliers_id)) {
                $suppliers_id[] = $client_service->supplier_id;
            }
        }


        $suppliers_id = implode(', ', $suppliers_id);
        $supplier_good_sum = LegalEntitiesLM::getDebtsSupplierGoodSum($suppliers_id);

        if (!$supplier_good_sum) {
            return false;
        }

        //Logger::log(print_r($supplier_good_sum, true), 'adminHomePage');

        return true;
    }

    public static function getDebtsClientServices(): array
    {
        $builder = Debts::newQueryBuilder()
            ->select([
                '*',
                'le.company_name as company_name',
                'le.inn as inn',
                'le.supplier_id as supplier_id',
                't.description as description',
                't.amount as transaction_amount',
                't.date as date',
                't.percent as percent',
                't.interest_income as interest_income',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = transaction_id',
            ])
            ->where([
                'type_of_debt ="client_services"',
                'status =' . '"active"',
            ])
            ->groupBy('debts.id');

        $client_services = PdoConnector::execute($builder) ?? [];

        $client_services_array = [];
        $suppliers_id = [];


        if (!$client_services) {
            return [];
        }

        foreach ($client_services as $client) {

            if (!in_array($client->supplier_id, $suppliers_id)) {
                $suppliers_id[] = $client->supplier_id;
            }

            $client_services_array[$client->supplier_id]['services'][] = [
                'client_services_id' => $client->id,
                'supplier_id' => $client->supplier_id,
                'service_transaction_id' => $client->transaction_id,
                'service_company_name' => $client->company_name,
                'service_inn' => $client->inn,
                'service_description' => $client->description,
                'service_transaction_amount' => round($client->transaction_amount),
                'service_percent' => $client->percent,
                'service_interest_income' => round($client->interest_income),
                'service_amount' => round($client->amount),
                'service_date' => $client->date,
            ];

        }


        $suppliers_id = implode(', ', $suppliers_id);


        return ['client_services' => $client_services_array, 'suppliers_id' => $suppliers_id];
    }

    public static function getDebtsClientServicesFrom($supplier_id): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                '*',
                'd.id as debit_id',
                'd.amount as amount',
                't.id as transaction_id',
                't.description as description',
                't.amount as transaction_amount',
                't.date as date',
                't.percent as percent',
                't.interest_income as interest_income',
            ])
            ->innerJoin('debts d')
            ->on([
                'd.from_account_id = id',
                "d.type_of_debt = 'client_services'",
                "d.status = 'active'",
            ])
            ->leftJoin('transactions t')
            ->on([
                't.id = d.transaction_id',
            ])
            ->where([
                'supplier_id =' . $supplier_id,
            ])
            ->groupBy('d.id');

        $client_services = PdoConnector::execute($builder) ?? [];

        $client_services_array = [];
        $sum_amount = 0;
        $interest_income_sum = 0;
        $transaction_amount_sum = 0;


        if (!$client_services) {
            return [];
        }

        foreach ($client_services as $client) {
            $sum_amount += $client->amount;
            $interest_income_sum += $client->interest_income;
            $transaction_amount_sum += $client->transaction_amount;

            $client_services_array[] = [
                'client_services_id' => $client->debit_id,
                'supplier_id' => $client->supplier_id,
                'service_transaction_id' => $client->transaction_id,
                'service_company_name' => $client->company_name,
                'service_inn' => $client->inn,
                'service_description' => $client->description,
                'service_transaction_amount' => round($client->transaction_amount),
                'service_percent' => $client->percent,
                'service_interest_income' => round($client->interest_income),
                'service_amount' => round($client->amount),
                'service_date' => $client->date,
            ];
        }


        foreach ($client_services_array as $key => $client_service) {
            $client_services_array[$key]['client_sum_amount'] = round($sum_amount);
            $client_services_array[$key]['interest_income_sum'] = round($interest_income_sum);
            $client_services_array[$key]['transaction_amount_sum'] = round($transaction_amount_sum);
        }


        return $client_services_array;
    }

    public static function getDebtsClientServicesGroup(): array
    {

        $client_services = self::getDebtsClientServices();
        if (!$client_services) {
            return [];
        }

        $supplier_goods = LegalEntitiesLM::getDebtsSupplierGoods($client_services['suppliers_id']);
        $client_services = $client_services['client_services'];

        foreach ($client_services as $key => $client_service) {
            $service_supplier = $client_service['services'][0]['supplier_id'];

            foreach ($supplier_goods as $supplier) {
                $supplier_id_goods = $supplier['supplier_id'];
                if ($service_supplier == $supplier_id_goods) {
                    $client_services[$key]['suppliers'][] = $supplier;
                }
            }
        }

        foreach ($client_services as $key => $item) {
            if (!isset($item['suppliers'])) {
                unset($client_services[$key]);
            }
        }


        //Logger::log(print_r($client_services, true), 'getDebtsClientServices');
        //Logger::log(print_r($supplier_goods, true), 'getDebtsClientServices');

        return $client_services;
    }

    public static function getDebtsClientServicesGroupRemaining($debt_ids)
    {
        //TODO только не оплаченные берем???

        $builder = Debts::newQueryBuilder()
            ->select([
                "SUM(CASE WHEN status IN ('offs_confirmation', 'paid') THEN 0 ELSE amount END) AS remaining",
                "le.supplier_id AS supplier_id",
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "debts.id IN($debt_ids)",
                "type_of_debt = 'client_services'",
            ])
            ->groupBy('le.supplier_id');

        //return $builder->build();

        return PdoConnector::execute($builder)[0]->remaining ?? 0;
    }

    public static function getDebtsMutualSettlement($amount_repaid, $supplier_id, $client_services, $supplier_goods): array
    {
        $repayments = [];
        $insert_mutual_settlement = [];
        $id_mutual_settlement = time();
        $remaining_amount = $amount_repaid;

        $they_wrote_off_all_the_debts = false;

        $remaining_client_services = 0;
        $remaining_supplier_goods = 0;

        foreach ($client_services as $client_service) {

            $service_amount = $client_service['service_amount'];
            $to_repay = min($remaining_amount, $service_amount);
            $service_percent = $client_service['service_percent'];
            $remaining = $service_amount - $to_repay;
            $new_debt_amount = 0;
            $new_transfer_amount = 0;
            $new_profit = 0;


            if (!$they_wrote_off_all_the_debts) {
                if ($remaining > 0) {
                    $new_debt_amount = $remaining;
                    $new_transfer_amount = $new_debt_amount / (1 - $service_percent / 100);
                    $new_profit = $new_transfer_amount - $new_debt_amount;
                }

                $repayments[] = [
                    'debt_id' => $client_service['client_services_id'],
                    'repaid' => $to_repay,
                    'new_debt_amount' => round($new_debt_amount),
                    'type_of_debt' => 'client_services',
                    'transaction_id' => $client_service['service_transaction_id'],
                    'new_transaction_amount' => $new_transfer_amount,
                    'new_interest_income' => $new_profit
                ];

                $insert_mutual_settlement[] = [
                    'debt_id' => $client_service['client_services_id'],
                    'id_mutual_settlement' => $id_mutual_settlement,
                    'repaid' => $to_repay,
                    'remainder' => $remaining,
                    'date' => date('Y-m-d'),
                    'repayment_type' => 'client_services',
                    'status' => 'pending'
                ];

                $remaining_client_services = $remaining_client_services + round($new_debt_amount);
            } else {
                $remaining_client_services = $remaining_client_services + $client_service['service_amount'];
            }


            $remaining_amount -= $to_repay;

            if ($remaining > 0) {
                $they_wrote_off_all_the_debts = true;
            }
        }

        $they_wrote_off_all_the_debt = false;
        $remaining_amount = $amount_repaid;

        foreach ($supplier_goods as $goods) {
            $service_amount = $goods['good_amount'];
            $to_repay = min($remaining_amount, $service_amount);
            $remaining = $service_amount - $to_repay;
            $percent = $goods['good_percent'];
            $new_transfer_amount = 0;
            $new_profit = 0;
            $new_debt_amount = 0;

            if (!$they_wrote_off_all_the_debt) {

                if ($remaining) {
                    $new_transfer_amount = $service_amount - $remaining_amount;
                    $new_profit = $new_transfer_amount * $percent / 100;
                    $new_debt_amount = $new_transfer_amount - $new_profit;
                }

                $repayments[] = [
                    'debt_id' => $goods['debit_id'],
                    'repaid' => $to_repay,
                    'new_debt_amount' => round($new_debt_amount),
                    'type_of_debt' => 'supplier_goods',
                    'transaction_id' => $goods['transaction_good_id'],
                    'new_transaction_amount' => $new_transfer_amount,
                    'new_interest_income' => $new_profit
                ];

                $insert_mutual_settlement[] = [
                    'debt_id' => $goods['debit_id'],
                    'id_mutual_settlement' => $id_mutual_settlement,
                    'repaid' => $to_repay,
                    'remainder' => $remaining,
                    'date' => date('Y-m-d'),
                    'repayment_type' => 'supplier_goods',
                    'status' => 'pending'
                ];
                $remaining_supplier_goods = $remaining_supplier_goods + round($new_debt_amount);

            } else {

                $remaining_supplier_goods = $remaining_supplier_goods + $goods['debit_amount'];
            }


            if ($remaining > 0) {
                $they_wrote_off_all_the_debt = true;
            }
        }


        $remaining_mutual_settlement = [
            'supplier_goods' => $remaining_supplier_goods,
            'client_services' => $remaining_client_services,
            'supplier_id' => $supplier_id,
            'date' => date('Y-m-d'),
            'status' => 'pending'
        ];


        return [
            'repayments' => $repayments,
            'mutual_settlement' => $insert_mutual_settlement,
            'remaining_mutual_settlement' => $remaining_mutual_settlement,
        ];
    }

    public static function getDebtsClientServicesGroupDate($from_account_id, $date): array
    {

        $formattedDate = DateTime::createFromFormat('d.m.Y', $date)->format('Y-m-d');

        $builder = Debts::newQueryBuilder()
            ->select([
                'le_from.company_name as client_company_name',
                'le_from.inn as client_inn',
                'le_from.supplier_id as client_supplier_id',
                't_from.description as client_description',
                't_from.amount as client_transaction_amount',
                't_from.date as client_date',
                't_from.percent as client_percent',
                't_from.interest_income as client_interest_income',
                ////////////////////////////////////////////
                'le_good.company_name as our_company_name',
                'le_good.inn as our_inn',
                't_good.description as our_description',
                't_good.amount as our_transaction_amount',
                't_good.date as our_date',
                't_good.percent as our_percent',
                't_good.interest_income as our_interest_income',
                'ms_client.id_mutual_settlement as tet',
            ])
            ->from('debts d')
            ->leftJoin('legal_entities le_from')
            ->on([
                'le_from.id = d.from_account_id',
            ])
            ->leftJoin('transactions t_from')
            ->on([
                't_from.id = d.transaction_id',
            ])
            ->innerJoin('mutual_settlement ms_client')
            ->on([
                'ms_client.debt_id = d.id',
                "ms_client.date = '" . $formattedDate . "'",
            ])
            ->innerJoin('mutual_settlement ms_good')
            ->on([
                'ms_good.id_mutual_settlement = ms_client.id_mutual_settlement',
                "ms_good.date = '" . $formattedDate . "'",
            ])
            ->leftJoin('debts d_good')
            ->on([
                'd_good.id = ms_good.debt_id',
            ])
            ->leftJoin('legal_entities le_good')
            ->on([
                'le_good.id = d_good.from_account_id',
            ])
            ->leftJoin('transactions t_good')
            ->on([
                't_good.id = d_good.transaction_id',
            ])
            ->where([
                'd.from_account_id IN(' . $from_account_id . ')',
                "d.type_of_debt ='" . 'client_services' . "'",
            ])
            ->groupBy('ms_client.id_mutual_settlement');

        //Logger::log(print_r($builder->build(), true), 'getDebtsClientServicesGroupDat');

        $client_services = PdoConnector::execute($builder) ?? [];


        //Logger::log(print_r($client_services, true), 'getDebtsClientServicesGroupDat');

        return $client_services;
    }

    public static function payOffClientsDebt($legal_id, $amount, $transaction_id): bool
    {
        $debts = self::getDebtsFromClientGoods($legal_id);
        $debt_closings_insert = [];

        if (!$debts) {
            // Если долгов нет, создаём новый долг
            $our_account_id = LegalEntitiesLM::getOurAccountOneId();
            $to_account_id = explode(',', $legal_id)[0];

            self::setNewDebts([
                'from_account_id' => $our_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'сlient_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);

            return true;
        }

        $from_account_id = null;
        $to_account_id = null;

        foreach ($debts as $debt) {
            if ($amount <= 0) break;

            $debt_amount = $debt->amount;

            if ($amount >= $debt_amount) {
                // Полностью закрываем долг
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount -= $debt_amount;

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

                $debt_closings_insert = [
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount,
                ];

            } else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $debt_closings_insert = [
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount,
                ];
                $amount = 0;
                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;
                break;
            }
        }

        if ($debt_closings_insert) {
            DebtClosingsLM::setNewDebtClosings($debt_closings_insert);
        }

        // Если остались лишние деньги — создаём новый долг
        if ($amount > 0 && $from_account_id !== null && $to_account_id !== null) {
            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'сlient_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);
        }

        return true;
    }

    public static function payOffSupplierClientServicesDebt($legal_id, $amount, $transaction_id): bool
    {
        $debts = self::getDebtsFromClientDebtSuppliers($legal_id);
        $debt_closings_insert = [];

        //Если нету никаких долгов
        if (!$debts) {
            $our_account_id = LegalEntitiesLM::getOurAccountOneId();

            $from_account_id = $our_account_id;
            $to_account_id = explode(',', $legal_id)[0];

            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'supplier_debt_сlient',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);

            return true;
        }

        $from_account_id = null;
        $to_account_id = null;

        foreach ($debts as $debt) {
            if ($amount <= 0) break;

            $debt_amount = $debt->amount;

            if ($amount >= $debt_amount) {
                // Полностью закрываем долг
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount -= $debt_amount;

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

                $debt_closings_insert = [
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount,
                ];

            } else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $debt_closings_insert = [
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount,
                ];

                $amount = 0;
                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;
                break;
            }
        }

        if ($debt_closings_insert) {
           DebtClosingsLM::setNewDebtClosings($debt_closings_insert);
        }

        if ($amount > 0 && $from_account_id != null && $to_account_id != null) {
            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'supplier_debt_сlient',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);
        }

        return true;
    }

    public static function mutualSettlementsDebts($legal_id, $amount, $transaction_id)
    {
        $debts = self::getDebtsCompanyMutual($legal_id);

        //Если нету никаких долгов
        if (!$debts) {
            return $amount;
        }

        foreach ($debts as $debt) {
            if ($amount <= 0) {
                break;
            }

            $debt_amount = $debt->amount;
            if ($amount >= $debt_amount) {
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);


                $amount -= $debt_amount;
            }
            else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount = 0;
                break;
            }
        }

        return $amount;
    }

    public static function payOffClientServicesDebt($legal_id, $amount, $transaction_id):bool
    {
        $debts = self::getDebtsFromClientServices($legal_id);

        // Если нет долгов — создаём новый
        if (!$debts) {
            $our_account_id = LegalEntitiesLM::getOurAccountOneId();
            $to_account_id = explode(',', $legal_id)[0];

            self::setNewDebts([
                'from_account_id' => $our_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'client_services_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);

            return true;
        }

        $from_account_id = null;
        $to_account_id = null;

        foreach ($debts as $debt) {
            if ($amount <= 0) break;

            $debt_amount = $debt->amount;

            if ($amount >= $debt_amount) {
                // Полностью закрываем долг
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount -= $debt_amount;

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

            } else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

                $amount = 0;
                break;
            }
        }

        // Если остались лишние деньги — создаём новый долг
        if ($amount > 0 && $from_account_id !== null && $to_account_id !== null) {
            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'client_services_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);
        }

        return true;
    }

    public static function payOffCompaniesDebt($legal_id, $amount, $transaction_id):bool
    {
        $debts = self::getDebtsFromCompanies($legal_id);
        $debt_closings_insert = [];

        // Если долгов нет — создаём новый долг
        if (!$debts) {
            $our_account_id = LegalEntitiesLM::getOurAccountOneId();
            $from_account_id = explode(',', $legal_id)[0];

            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $our_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'supplier_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);

            return true;
        }

        $from_account_id = null;
        $to_account_id = null;

        foreach ($debts as $debt) {
            if ($amount <= 0) break;

            $debt_amount = $debt->amount;

            if ($amount >= $debt_amount) {
                // Полностью закрываем долг
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount -= $debt_amount;

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

                $debt_closings_insert = [
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount,
                ];

            } else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

                $debt_closings_insert = [
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount,
                ];

                $amount = 0;
                break;
            }
        }

        if ($debt_closings_insert){
            DebtClosingsLM::setNewDebtClosings($debt_closings_insert);
        }

        // Если остались лишние деньги — создаём новый долг
        if ($amount > 0 && $from_account_id !== null && $to_account_id !== null) {
            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'supplier_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);
        }

        return true;
    }

    public static function getDebtSupplierPage($supplier_id): array
    {
        $builder = LegalEntities::newQueryBuilder()
            ->select([
                "SUM(CASE WHEN d.type_of_debt = 'client_services' AND d.status='active' THEN d.amount ELSE 0 END) AS client_services",
                "SUM(CASE WHEN d.type_of_debt = 'supplier_goods' AND d.status='active' THEN d.amount ELSE 0 END) AS supplier_goods",
                "SUM(CASE WHEN d.type_of_debt = 'сlient_debt_supplier' AND d.status='active' THEN d.amount ELSE 0 END) AS сlient_debt_supplier",
            ])
            ->from('legal_entities le')
            ->leftJoin('debts d')
            ->on([
                'd.from_account_id = le.id',
                'd.to_account_id = le.id',
            ], 'OR')
            ->where([
                'le.supplier_id =' . $supplier_id,
            ]);

        $debts = PdoConnector::execute($builder)[0] ?? [];


        return [
            'client_services' => $debts->client_services ?? 0,
            'supplier_goods' => $debts->supplier_goods ?? 0,
            'client_debt_supplier' => $debts->сlient_debt_supplier ?? 0
        ];
    }


    public static function payOffCompaniesExcessDebt($legal_id, $amount, $transaction_id):bool
    {
        $debts = self::getDebtsFromCompanies($legal_id);

        // Если долгов нет — создаём новый долг
        if (!$debts) {
            return false;
        }


        foreach ($debts as $debt) {
            if ($amount <= 0) break;

            $debt_amount = $debt->amount;

            if ($amount >= $debt_amount) {
                // Полностью закрываем долг
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount -= $debt_amount;

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

            } else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                break;
            }
        }

        return true;
    }

    public static function returnOffSuppliersDebt($legal_id, $amount, $transaction_id): bool
    {
        $debts = self::getDebtsFromSupplierGoods($legal_id);

        // Если долгов нет — создаём новый долг
        if (!$debts) {
            $our_account_id = LegalEntitiesLM::getOurAccountOneId();
            $to_account_id = explode(',', $legal_id)[0];

            self::setNewDebts([
                'from_account_id' => $our_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'supplier_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);

            return true;
        }

        $from_account_id = null;
        $to_account_id = null;

        foreach ($debts as $debt) {
            if ($amount <= 0) break;

            $debt_amount = $debt->amount;

            if ($amount >= $debt_amount) {
                // Полностью закрываем долг
                self::updateDebtsId([
                    'status = paid',
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $amount -= $debt_amount;

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

            } else {
                // Частично закрываем долг
                $new_debt_amount = $debt_amount - $amount;

                self::updateDebtsId([
                    'amount = ' . $new_debt_amount,
                    'writing_transaction_id = ' . $transaction_id,
                ], $debt->id);

                $from_account_id = $debt->to_account_id;
                $to_account_id = $debt->from_account_id;

                $amount = 0;
                break;
            }
        }

        // Если остались лишние деньги — создаём новый долг
        if ($amount > 0 && $from_account_id !== null && $to_account_id !== null) {
            self::setNewDebts([
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'type_of_debt' => 'supplier_debt',
                'date' => date('Y-m-d'),
                'status' => 'active'
            ]);
        }

        return true;
    }


}