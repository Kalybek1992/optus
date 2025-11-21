<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\MutualSettlement;


/**
 *
 */
class MutualSettlementLM
{
    public static function getMutualSettlementCount($date_from, $date_to, $supplier_id = null)
    {

        if ($supplier_id) {
            $builder = MutualSettlement::newQueryBuilder()
                ->select([
                    'COUNT(*) AS mutual_settlement_count',
                ])
                ->from("(SELECT ms.date 
             FROM mutual_settlement ms 
             INNER JOIN debts d ON d.id = ms.debt_id 
             INNER JOIN legal_entities le ON le.id = d.from_account_id AND le.supplier_id = '$supplier_id'
             INNER JOIN suppliers s ON s.id = le.supplier_id 
             WHERE ms.repayment_type = 'client_services' 
             GROUP BY ms.date, s.id
             ) AS sub");

        } else {
            $builder = MutualSettlement::newQueryBuilder()
                ->select([
                    'COUNT(*) AS mutual_settlement_count',
                ])
                ->from("(SELECT ms.date FROM mutual_settlement ms 
            LEFT JOIN debts d ON d.id = ms.debt_id 
            LEFT JOIN legal_entities le ON le.id = d.from_account_id 
            LEFT JOIN suppliers s ON s.id = le.supplier_id 
            WHERE ms.repayment_type = 'client_services' GROUP BY ms.date, s.id) AS sub"
                );
        }


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
                    "sub.date BETWEEN '" . "$date_from" . "' AND '" . $date_to . "'",
                ]);
        }
        if ($date_from && !$date_to) {
            $builder
                ->where([
                    "sub.date >= '" . $date_from . "'",
                ]);
        }

        return PdoConnector::execute($builder)[0]->mutual_settlement_count ?? 0;
    }

    public static function getMutualSettlementClientServices($date_from, $date_to, int $offset = 0, int $limit = 8, $supplier_id = null)
    {
        $builder = MutualSettlement::newQueryBuilder()
            ->select([
                'MIN(ms.id) as id',
                'SUM(ms.repaid) as sum_repaid',
                'SUM(ms.remainder) as sum_remainder',
                'GROUP_CONCAT(DISTINCT ms.id_mutual_settlement) as id_mutual_settlements',
                'GROUP_CONCAT(DISTINCT ms.debt_id) as debt_ids',
                'ms.date as date_mutual_settlement',
                'u.name as user_name',
                'u.email as email',
                'u_client_services.name as client_services_name',
                'ms.status as status',
                's.id as supplier_id',
            ])
            ->from('mutual_settlement ms')
            ->innerJoin('debts d')
            ->on([
                'd.id = ms.debt_id'
            ]);

        if ($supplier_id) {
            $builder
                ->innerJoin('legal_entities le')
                ->on([
                    'le.supplier_id =' . $supplier_id,
                ]);
        } else {
            $builder
                ->innerJoin('legal_entities le')
                ->on([
                    'le.id = d.from_account_id',
                ]);
        }

        $builder
            ->leftJoin('suppliers s')
            ->on([
                's.id = le.supplier_id',
            ])
            ->leftJoin('client_services cs')
            ->on([
                'cs.supplier_id = s.id',
            ])
            ->leftJoin('users u_client_services')
            ->on([
                'u_client_services.id = cs.user_id',
            ])
            ->leftJoin('users u')
            ->on([
                'u.id = s.user_id',
            ]);

        if ($date_from) {
            $date_from = date('Y-m-d', strtotime($date_from));
        }

        if ($date_to) {
            $date_to = date('Y-m-d', strtotime($date_to));
        }

        if ($date_from && $date_to) {
            $builder->where([
                "ms.date >= '" . $date_from . "'",
                "ms.date <= '" . $date_to . "'",
                "ms.repayment_type = 'client_services'",
            ]);
        }

        if ($date_from && !$date_to) {
            $builder->where([
                "ms.date >= '" . $date_from . "'",
                "ms.repayment_type = 'client_services'",
            ]);
        }

        if (!$date_from && !$date_to) {
            $builder->where([
                "ms.repayment_type = 'client_services'",
            ]);
        }


        $builder
            ->groupBy('ms.date, s.id')
            ->orderBy('ms.date', 'DESC')
            ->limit($limit)
            ->offset($offset);


        return PdoConnector::execute($builder);
    }

    public static function getMutualSettlementSupplierGoods($mutual_ids)
    {
        $builder = MutualSettlement::newQueryBuilder()
            ->select([
                "SUM(remainder) as remainder",
            ])
            ->where([
                "mutual_settlement.id_mutual_settlement IN($mutual_ids)",
                "repayment_type = 'supplier_goods'",
            ]);

        return PdoConnector::execute($builder)[0]->remainder ?? 0;
    }

    public static function getMutualSettlement($date_from, $date_to, int $offset = 0, int $limit = 8, $supplier_id = null): array
    {
        $mutual_settlements = self::getMutualSettlementClientServices($date_from, $date_to, $offset, $limit, $supplier_id);

        if (!$mutual_settlements) {
            return [];
        }

        $mutual_settlements_arr = [];
        $debt_ids = [];
        $mutual_ids = [];

        foreach ($mutual_settlements as $mutual) {

            $m_ids = array_map('intval', explode(',', $mutual->id_mutual_settlements ?:
                    $mutual->id_mutual_settlements)
            );
            $mutual_ids = array_merge($mutual_ids, $m_ids);

            $d_ids = array_map('intval', explode(',', $mutual->debt_ids ?:
                    $mutual->debt_ids)
            );
            $debt_ids = array_merge($debt_ids, $d_ids);


            $remaining = RemainingMutualSettlementLM::getRemainingMutualSettlement($mutual->date_mutual_settlement, $mutual->supplier_id);


            $mutual_settlements_arr[] = [
                'id' => $mutual->id,
                'username' => $mutual->user_name,
                'email' => $mutual->email,
                'date' => date('d.m.Y', strtotime($mutual->date_mutual_settlement)),
                'amount' => $mutual->sum_repaid,
                'client_remaining' => $remaining->client_services ?? 0,
                'supplier_remaining' => $remaining->supplier_goods ?? 0,
                'debt_ids' => $debt_ids,
                'supplier_id' => $mutual->supplier_id,
                'mutual_ids' => $mutual_ids,
                'status' => $mutual->status,
                'client_services_name' => $mutual->client_services_name,
            ];

            $debt_ids = [];
            $mutual_ids = [];
        }


        //Logger::log(print_r($mutual_settlements_arr, true), 'editDebtSupplier');
        return $mutual_settlements_arr;
    }

    public static function insertNewMutualSettlement($data)
    {
        $builder = MutualSettlement::newQueryBuilder()
            ->insert($data);


        return PdoConnector::execute($builder);
    }

    public static function getMutualDebtsSettlement($supplier_accounts, $date): array
    {
        $mutual_settlements = DebtsLM::getDebtsClientServicesGroupDate($supplier_accounts, $date);

        if (!$mutual_settlements) {
            return [];
        }


        $mutual_settlements_arr = [];

        foreach ($mutual_settlements as $mutual) {
            $mutual_settlements_arr[] = [
                'client_company_name' => $mutual->company_name,
                'client_inn' => $mutual->inn,
                'client_supplier_id' => $mutual->supplier_id,
                'client_description' => $mutual->description,
                'client_transaction_amount' => $mutual->client_transaction_amount,
                'client_date' => $mutual->client_date,
                'client_percent' => $mutual->client_percent,
                'client_interest_income' => $mutual->client_interest_income,
                'our_company_name' => $mutual->our_company_name,
                'our_inn' => $mutual->our_description,
                'our_description' => $mutual->our_description,
                'our_transaction_amount' => $mutual->our_transaction_amount,
                'our_date' => $mutual->our_date,
                'our_percent' => $mutual->our_percent,
                'our_interest_income' => $mutual->our_interest_income,
                'tet' => $mutual->tet,
            ];
        }


        //Logger::log(print_r($mutual_settlements_arr, true), 'editDebtSupplier');
        return $mutual_settlements_arr;
    }
}