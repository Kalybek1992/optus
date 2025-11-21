<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\LegalEntities;


/**
 *
 */
class BankAccountsLM
{
    public static function updateBankAccounts(array $data, $legal_entity_id)
    {

        $builder = BankAccounts::newQueryBuilder()
            ->update($data)
            ->where([
                'legal_entity_id =' . $legal_entity_id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function setNewBankAccounts(array $data)
    {
        $builder = BankAccounts::newQueryBuilder()
            ->insert($data);


        return PdoConnector::execute($builder);
    }

    public static function getBankAccounts(array $array, $legal_entity_id)
    {
        $builder = BankAccounts::newQueryBuilder()
            ->update($array)
            ->where([
                'legal_entity_id =' . $legal_entity_id
            ]);

        return PdoConnector::execute($builder);
    }

}