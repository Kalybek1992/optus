<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\SupplierBalance;


/**
 *
 */
class SupplierBalanceLM
{
    public static function updateSupplierBalance(array $data, int $legal_id, int $sender_legal_id)
    {

        $builder = SupplierBalance::newQueryBuilder()
            ->update($data)
            ->where([
                'legal_id =' . $legal_id,
                'sender_legal_id =' . $sender_legal_id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function setNewSupplierBalance(array $data)
    {
        $builder = SupplierBalance::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getSupplierBalance(int $legal_id, int $sender_legal_id)
    {
        $builder = SupplierBalance::newQueryBuilder()
            ->select()
            ->where([
                'legal_id =' . $legal_id,
                'sender_legal_id =' . $sender_legal_id
            ])
            ->limit(1);

        return PdoConnector::execute($builder);
    }

}