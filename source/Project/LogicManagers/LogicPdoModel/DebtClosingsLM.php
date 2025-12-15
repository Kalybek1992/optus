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


}