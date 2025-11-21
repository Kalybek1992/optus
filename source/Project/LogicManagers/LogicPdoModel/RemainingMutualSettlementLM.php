<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\MutualSettlement;
use Source\Project\Models\RemainingMutualSettlement;


/**
 *
 */
class RemainingMutualSettlementLM
{


    public static function setNewRemaining($remaining_mutual_settlement)
    {
        $builder = RemainingMutualSettlement::newQueryBuilder()
            ->select([
                "*",
            ])
            ->where([
                "date = '" . $remaining_mutual_settlement["date"] . "'",
                "supplier_id = '" . $remaining_mutual_settlement["supplier_id"] . "'",
            ])
            ->limit(1);


        $remaining_mutual_settlement_db = PdoConnector::execute($builder) ?? null;

        if ($remaining_mutual_settlement_db) {

            $builder_update = RemainingMutualSettlement::newQueryBuilder()
                ->update([
                    'supplier_goods =' . $remaining_mutual_settlement["supplier_goods"],
                    'client_services =' . $remaining_mutual_settlement["client_services"],
                ])
                ->where([
                    "date = '" . $remaining_mutual_settlement["date"] . "'",
                    "supplier_id = '" . $remaining_mutual_settlement["supplier_id"] . "'",
                ])
                ->limit(1);

            return PdoConnector::execute($builder_update);

        }else{

            $builder_insert = RemainingMutualSettlement::newQueryBuilder()
                ->insert($remaining_mutual_settlement);

            return PdoConnector::execute($builder_insert);
        }


    }


    public static function getRemainingMutualSettlement($date, $supplier_id){

        $builder = RemainingMutualSettlement::newQueryBuilder()
            ->select([
                "*",
            ])
            ->where([
                "date = '" . $date . "'",
                "supplier_id = '" . $supplier_id . "'",
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? [];
    }

}