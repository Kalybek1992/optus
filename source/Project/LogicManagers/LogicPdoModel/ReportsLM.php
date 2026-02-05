<?php

namespace Source\Project\LogicManagers\LogicPdoModel;


use Source\Base\Core\Logger;
use DateTime;
use Source\Project\Models\TransactionProviders;

class ReportsLM
{
    public static function getTodayReportSupplier($date_from, $date_to, $manager_id): array
    {
        $finances_sum = CompanyFinancesLM::getManagerFinancesSumAndType($manager_id, $date_from, $date_to);

        $transactions_sum = TransactionProvidersLM::getTransactionsSum(
            $date_from,
            $date_to,
            $manager_id
        );

        $transactions_sum_return = TransactionProvidersLM::getTransactionsReturnSum(
            $date_from,
            $date_to,
            $manager_id
        );

        //TODO зборданных сумм каторый на таблице
        //TODO менеджер товар услуга
        //TODO 1) Сумма сумма, всего бн которое зашло в этот день. %8, %6 че за хрень это блять, патоки ска
        $customer_service_manager = $transactions_sum['sum_amount'];


        $date = DateTime::createFromFormat('d.m.Y', $date_from);
        $date = $date->format('Y-m-d');
        $get_the_lastclosed_today = EndOfDaySettlementLM::getTheLastClosedToday($manager_id, $date);

        //TODO 2) если суда ставить реальные данные с переносами то работает нормально
        $debt_client_services = 0;
        //TODO 9) долг по отгрузки за прошлый день ( №3 сценарий)
        $scenario_3 = 0;


        if ($get_the_lastclosed_today){
            //TODO Данные из менеджера которые берутся для перерасчёта - это Ставка транзит, ставка кэш
            $transit_rate = $get_the_lastclosed_today->transit_rate;
            $cash_bet = $get_the_lastclosed_today->cash_bet;
        }else{
            $manager = ManagersLM::getManagerId($manager_id);
            $transit_rate = $manager->transit_rate;
            $cash_bet = $manager->cash_bet;
        }

        //TODO 2) Сумма долг в бн переходящий (долг с прошлого дня)
        $get_the_last_closed_day = EndOfDaySettlementLM::getTheLastClosedDay($manager_id, $date_from);

        if ($get_the_last_closed_day) {
            if ($get_the_last_closed_day->scenario == 1 || $get_the_last_closed_day->scenario == 2) {
                $debt_client_services = $get_the_last_closed_day->amount;
            }

            if ($get_the_last_closed_day->scenario == 3) {
                $scenario_3 = $get_the_last_closed_day->amount;
            }
        }

        //TODO 3) были возвраты с счета компании
        $shipping_return_sum = $transactions_sum_return['sum_amount'];

        //TODO 4) остаток транзитных денег после всех вычетов $shipping_return_sum Это должна была минусоватца в конце но в нашем случае она минусуется автоматический при загрузке выписки!
        $transit_balance_after_deductions = ($customer_service_manager - $debt_client_services) - $shipping_return_sum;


        //TODO 5) комиссия за услугу
        $transactions_percents = $transactions_sum['percents'];

        //TODO 6) сумма к отгрузки с учктом вычета комиссии
        $net_shipment_amount = $transit_balance_after_deductions * $transit_rate;

        //TODO 7) сумма отгрузки
        $shipping_manager_sum = $finances_sum->shipping_manager_amount;

        //TODO 8) возврат коментарий. Выбор склада на который вернули
        $return_to_warehouse = $finances_sum->wheel_return_amount;


        //TODO 10)  наш долг перед менеджером в б/н (отрицательная, то она отображается как 0 ( ноль))
        $noncash_manager_debt = ($net_shipment_amount - $shipping_manager_sum) + ($return_to_warehouse + $scenario_3);
        $noncash_manager_debt = max(0, $noncash_manager_debt);

        //TODO 11)  менеджер отгрузил товар в долг (ВАЖНО если сумма получилась отрицательная, то она отображается как 0 ( ноль))
        $debt_goods_shipped = ($shipping_manager_sum - $net_shipment_amount) - $return_to_warehouse;
        $debt_goods_shipped = max(0, $debt_goods_shipped);

        //TODO 12) всего выданно кэш;
        $total_cash_issued = $finances_sum->moved_cash_amount;
        $scenario = [];

        if ($debt_goods_shipped > 0 && $total_cash_issued <= 0) { //TODO Это обработка сценарий 1
            $scenario = [
                'scenario' => 1,
                'topic' => 'долг по отгрузке 0, займ в товаре есть. Кэш нет выдачи',
                'займ в товаре' => $debt_goods_shipped,
                'долг в бн отправка' => $debt_goods_shipped / $transit_rate,
            ];

        } elseif ($debt_goods_shipped > 0 && $total_cash_issued > 0) { //TODO Это обработка сценарий 2
            $conversion_cash_to_bn = (max(($debt_goods_shipped * $cash_bet) - $total_cash_issued, $total_cash_issued - ($debt_goods_shipped * $cash_bet))) / $cash_bet;
            $scenario = [
                'scenario' => 2,
                'topic' => 'долг по отгрузке 0 займ в товаре есть. Кэш выдача есть',
                'займ в товаре' => $debt_goods_shipped,
                'долг в бн' => $debt_goods_shipped / $transit_rate,
                'долг в кэш' => $debt_goods_shipped * $cash_bet,
                'выданно кэш' => $total_cash_issued,
                'конвертация кэш к бн' => $conversion_cash_to_bn,
                'переходящий долг в бн' => $conversion_cash_to_bn / $transit_rate,
            ];

        } elseif ($noncash_manager_debt > 0) { //TODO Это обработка сценарий 3
            $scenario = [
                'scenario' => 3,
                'topic' => 'долг по отгрузке есть займ в товаре нет . Кэш нет выдачи',
                'долг по отгрузке' => $noncash_manager_debt
            ];
        }

        $result = [
            'customer_service_manager' => $customer_service_manager,
            'debt_client_services' => $debt_client_services,
            'shipping_return_sum' => $shipping_return_sum,
            'transit_balance_after_deductions' => $transit_balance_after_deductions,
            'transactions_percents' => $transactions_percents,
            'net_shipment_amount' => $net_shipment_amount,
            'shipping_manager_sum' => $shipping_manager_sum,
            'return_to_warehouse' => $return_to_warehouse,
            'previous_day_shipping_debt' => '',
            'noncash_manager_debt' => $noncash_manager_debt,
            'debt_goods_shipped' => $debt_goods_shipped,
            'total_cash_issued' => $total_cash_issued,
            'scenario_3' => $scenario_3,
            'scenario' => $scenario,
        ];

        foreach ($result as $key => $value) {
            if ($key === 'scenario') {
                continue;
            }
            if (is_numeric($value)) {
                $result[$key] = number_format($value, 0, ',', ' ');
            }
        }

        if (is_array($result['scenario'])) {
            foreach ($result['scenario'] as $scenario_key => $scenario_value) {
                if (is_numeric($scenario_value)) {
                    $result['scenario'][$scenario_key] = number_format($scenario_value, 0, ',', ' ');
                }
            }
        }

        $result['transit_rate'] = $transit_rate;
        $result['cash_bet'] = $cash_bet;

        return $result;
    }

    /**
     * @throws \Exception
     */
    public static function checkinglastDaysScript($date_from, $date_to, $manager_id, $transit_rate, $cash_bet): array
    {
        $finances_sum = CompanyFinancesLM::getManagerFinancesSumAndType($manager_id, $date_from, $date_to);

        $transactions_sum = TransactionProvidersLM::getTransactionManagerSum(
            $date_from,
            $manager_id
        );

        $transactions_sum_return = TransactionProvidersLM::getTransactionsReturnSum(
            $date_from,
            $date_to,
            $manager_id
        );

        if (!$transit_rate && !$cash_bet){
            $manager = ManagersLM::getManagerId($manager_id);
            //TODO Данные из менеджера которые берутся для перерасчёта - это Ставка транзит, ставка кэш
            $transit_rate = $manager->transit_rate;
            $cash_bet = $manager->cash_bet;
        }

        //TODO 1) Сумма сумма, всего бн которое зашло в этот день. %8, %6 че за хрень это блять, патоки ска
        $customer_service_manager = $transactions_sum['sum_amount'];

        //TODO 2) Сумма долг в бн переходящий (долг с прошлого дня)
        $get_the_last_closed_day = EndOfDaySettlementLM::getTheLastClosedDay($manager_id, $date_from);

        //TODO 2) если суда ставить реальные данные с переносами то работает нормально
        $debt_client_services = 0;

        //TODO 9) долг по отгрузки за прошлый день ( №3 сценарий)
        $scenario_3 = 0;

        if ($get_the_last_closed_day) {
            if ($get_the_last_closed_day->scenario == 1 || $get_the_last_closed_day->scenario == 2) {
                $debt_client_services = $get_the_last_closed_day->amount;
            }

            if ($get_the_last_closed_day->scenario == 3) {
                $scenario_3 = $get_the_last_closed_day->amount;
            }
        }

        //TODO 3) были возвраты с счета компании
        $shipping_return_sum = $transactions_sum_return['sum_amount'];

        //TODO 4) остаток транзитных денег после всех вычетов $shipping_return_sum Это должна была минусоватца в конце но в нашем случае она минусуется автоматический при загрузке выписки!
        $transit_balance_after_deductions = ($customer_service_manager - $debt_client_services) - $shipping_return_sum;

        //TODO 6) сумма к отгрузки с учктом вычета комиссии
        $net_shipment_amount = $transit_balance_after_deductions * $transit_rate;

        //TODO 7) сумма отгрузки
        $shipping_manager_sum = $finances_sum->shipping_manager_amount;

        //TODO 8) возврат коментарий. Выбор склада на который вернули
        $return_to_warehouse = $finances_sum->wheel_return_amount;

        //TODO 10)  наш долг перед менеджером в б/н (отрицательная, то она отображается как 0 ( ноль))
        $noncash_manager_debt = ($net_shipment_amount - $shipping_manager_sum) + ($return_to_warehouse + $scenario_3);
        $noncash_manager_debt = max(0, $noncash_manager_debt);

        //TODO 11)  менеджер отгрузил товар в долг (ВАЖНО если сумма получилась отрицательная, то она отображается как 0 ( ноль))
        $debt_goods_shipped = ($shipping_manager_sum - $net_shipment_amount) - $return_to_warehouse;
        $debt_goods_shipped = max(0, $debt_goods_shipped);

        //TODO 12) всего выданно кэш;
        $total_cash_issued = $finances_sum->moved_cash_amount;

        if ($debt_goods_shipped > 0 && $total_cash_issued <= 0) { //TODO Это обработка сценарий 1

            return [
                'amount' => $debt_goods_shipped / $transit_rate,
                'scenario' => 1,
                'transit_rate' => $transit_rate,
                'cash_bet' => $cash_bet,
            ];
        } elseif ($debt_goods_shipped > 0 && $total_cash_issued > 0) { //TODO Это обработка сценарий 2
            $conversion_cash_to_bn = (max(($debt_goods_shipped * $cash_bet) - $total_cash_issued, $total_cash_issued - ($debt_goods_shipped * $cash_bet))) / $cash_bet;

            return [
                'amount' => $conversion_cash_to_bn / $transit_rate,
                'scenario' => 2,
                'transit_rate' => $transit_rate,
                'cash_bet' => $cash_bet,
            ];
        } elseif ($noncash_manager_debt > 0) { //TODO Это обработка сценарий 3

            return [
                'amount' => $noncash_manager_debt,
                'scenario' => 3,
                'transit_rate' => $transit_rate,
                'cash_bet' => $cash_bet,
            ];
        }

        return [];
    }
}