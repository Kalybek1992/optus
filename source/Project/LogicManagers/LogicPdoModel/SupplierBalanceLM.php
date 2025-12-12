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
    public static function updateSupplierBalance(array $data, int $id)
    {

        $builder = SupplierBalance::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id,
            ]);

        return PdoConnector::execute($builder);
    }

    public static function setNewSupplierBalance(array $data)
    {
        $builder = SupplierBalance::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getSupplierBalance($recipient_inn, $sender_inn)
    {
        $builder = SupplierBalance::newQueryBuilder()
            ->select()
            ->where([
                'recipient_inn =' . $recipient_inn,
                'sender_inn =' . $sender_inn
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getSupplierBalanceId(int $id)
    {
        $builder = SupplierBalance::newQueryBuilder()
            ->select()
            ->where([
                'id =' . $id,
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getSupplierBalanceCompany($recipient_inn)
    {
        $builder = SupplierBalance::newQueryBuilder()
            ->select([
                '*',
                'le.inn as inn',
                'le.company_name as company_name',
            ])
            ->leftJoin('legal_entities as le')
            ->on([
                'le.inn = sender_inn',
            ])
            ->where([
                'recipient_inn =' . $recipient_inn,
            ])
            ->groupBy('id');

        return PdoConnector::execute($builder);
    }


}