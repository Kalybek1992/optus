<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\DebtClosings;


/**
 *
 */
class DebtClosingsLM
{
    public static function setNewDebtClosings(array $data)
    {
        $builder = DebtClosings::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getDebtClosingsId(int $id)
    {
        $builder = DebtClosings::newQueryBuilder()
            ->select()
            ->where([
                'id =' . $id,
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getDebtClosingsInAmaut(string $ids)
    {
        $builder = DebtClosings::newQueryBuilder()
            ->select([
                'SUM(amount) as sum_amount'
            ])
            ->where([
                "debt_id IN($ids)",
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }


    public static function getDebtClosingsInTransactionId(string $ids)
    {
        $builder = DebtClosings::newQueryBuilder()
            ->select([
                '*'
            ])
            ->where([
                "transaction_id IN($ids)",
            ]);

        return PdoConnector::execute($builder) ?? [];
    }

    public static function deleteDebtClosingsInTransactionId(string $ids)
    {
        $builder = DebtClosings::newQueryBuilder()
            ->delete()
            ->where([
                "transaction_id IN($ids)",
            ]);

        return PdoConnector::execute($builder);
    }


}