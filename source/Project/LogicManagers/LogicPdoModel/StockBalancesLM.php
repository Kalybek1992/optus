<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\StockBalances;


/**
 *
 */
class StockBalancesLM
{
    public static function updateStockBalances(array $data)
    {

        $builder = StockBalances::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . 1
            ]);

        return PdoConnector::execute($builder);
    }


    public static function getStockBalances()
    {

        $builder = StockBalances::newQueryBuilder()
            ->select()
            ->where([
                'id =' . 1
            ])
            ->limit(1);

        $stock_balances = PdoConnector::execute($builder)[0] ?? null;

        if (!$stock_balances) {
            self::setNewStockBalances([
                'id' => 1,
                'updated_date' => date('Y-m-d')
            ]);
        }

        $builder = StockBalances::newQueryBuilder()
            ->select()
            ->where([
                'id =' . 1
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? null;
    }


    public static function setNewStockBalances(array $data)
    {
        $builder = StockBalances::newQueryBuilder()
            ->insert($data);


        return PdoConnector::execute($builder);
    }

}