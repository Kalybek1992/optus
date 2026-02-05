<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\EndOfDaySettlement;


/**
 *
 */
class EndOfDaySettlementLM
{
    public static function getTheLastClosedDay($manager_id, $date)
    {
        if ($date) {
            $date = DateTime::createFromFormat('d.m.Y', $date);
            $date = $date->format('Y-m-d');
        }

        $builder = EndOfDaySettlement::newQueryBuilder()
            ->select([
                '*',
            ])
            ->from('end_of_day_settlement')
            ->where([
                'manager_id =' . $manager_id,
                "date < " . "'$date'"
            ])
            ->orderBy('date', 'DESC')
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getTheLastClosedToday($manager_id, $date)
    {
        $builder = EndOfDaySettlement::newQueryBuilder()
            ->select([
                '*',
            ])
            ->from('end_of_day_settlement')
            ->where([
                'manager_id =' . $manager_id,
                "date =" . "'$date'"
            ])
            ->orderBy('date', 'DESC')
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getMaxDate()
    {

        $builder = EndOfDaySettlement::newQueryBuilder()
            ->select([
                'MAX(date) as max_date',
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->max_date ?? null;
    }

    public static function insertEndOfDaySettlement(array $dataset)
    {
        $builder = EndOfDaySettlement::newQueryBuilder()
            ->insert($dataset);

        return PdoConnector::execute($builder);
    }

    public static function updateEndOfDayTransactions($dataset): void
    {
        $inserts = [];
        foreach ($dataset as $key => $value) {
            $current = new DateTime($value['date']);
            $end = new DateTime('today');
            $manager_id = $value['manager_id'];
            $transit_rate = $value['transit_rate'] ?? null;
            $cash_bet = $value['cash_bet'] ?? null;

            while ($current <= $end) {
                $date_format = $current->format('d.m.Y');
                $date = $current->format('Y-m-d');
                $today = self::getTheLastClosedToday($manager_id, $date);

                $report = ReportsLM::checkinglastDaysScript(
                    $date_format,
                    $date_format,
                    $manager_id,
                    $transit_rate,
                    $cash_bet
                );

                if ($today) {
                    if ($report) {
                        self::updateEndOfDaySettlement([
                            'amount =' . $report['amount'],
                            'scenario =' . $report['scenario'],
                            'transit_rate =' . $report['transit_rate'],
                            'cash_bet =' . $report['cash_bet'],
                            'date =' . $date,
                        ], $today->id);
                    }
                } else {
                    if ($report) {
                        $inserts[] = [
                            'manager_id' => $manager_id,
                            'amount' => $report['amount'],
                            'scenario' => $report['scenario'],
                            'transit_rate' => $report['transit_rate'],
                            'cash_bet' => $report['cash_bet'],
                            'date' => $date,
                        ];
                    }
                }

                $current->modify('+1 day');
            }
        }

        if ($inserts) {
            self::insertEndOfDaySettlement($inserts);
        }
    }

    public static function updateEndOfDaySettlement(array $data, $id)
    {
        $builder = EndOfDaySettlement::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);


        return PdoConnector::execute($builder);
    }

}