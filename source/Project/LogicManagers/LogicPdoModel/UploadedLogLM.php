<?php

namespace Source\Project\LogicManagers\LogicPdoModel;


use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\UploadedDocuments;
use Source\Project\Models\UploadedLog;


/**
 *
 */
class UploadedLogLM
{
    public static function getUploadedLog()
    {

        $builder = UploadedLog::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
            ]);


        return PdoConnector::execute($builder);
    }

    public static function insertUploadedLog(array $dataset)
    {
        $builder = UploadedLog::newQueryBuilder()
            ->insert($dataset);

        return PdoConnector::execute($builder);
    }


    public static function getAccountsUploadedMaxTime(array $selects_inn)
    {
        if (!$selects_inn) {
            return [];
        }

        $selects_inn = implode(',', array_map('intval', $selects_inn));

        $builder = UploadedLog::newQueryBuilder()
            ->select([
                'MAX(date) as max_date',
            ])
            ->where([
                "inn IN($selects_inn)"
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getAccountsUploadedMaxid(): int
    {
        $builder = UploadedLog::newQueryBuilder()
            ->select([
                'MAX(id) as max_id',
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->max_id ?? 1;
    }

}