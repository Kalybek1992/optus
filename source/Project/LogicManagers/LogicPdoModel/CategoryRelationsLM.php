<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\CategoryRelations;
use Source\Project\Models\Clients;
use Source\Project\Models\ExpenseCategories;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Suppliers;
use Source\Project\Models\Users;


/**
 *
 */
class CategoryRelationsLM
{

    public static function insertNewRelations(array $data)
    {
        $builder = CategoryRelations::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }



}