<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\StatementLog;


/**
 *
 */
class StatementLogLM
{
    public static function updateStatementLog(array $data, int $id)
    {

        $builder = StatementLog::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function setNewStatementLog(array $data)
    {
        $builder = StatementLog::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getStatementLogMaxId(): int
    {
        $builder = StatementLog::newQueryBuilder()
            ->select([
                'MAX(id) as max_id'
            ])
            ->limit(1);

        return PdoConnector::execute($builder)[0]->max_id + 1 ?? 1;
    }


    public static function getStatementLogDate($date = null)
    {
        if (!empty($date)) {
            $dt = DateTime::createFromFormat('d.m.Y', $date);
            $check_date = $dt ? $dt->format('Y-m-d') : date('Y-m-d');
        } else {
            $check_date = date('Y-m-d');
        }


        $builder = StatementLog::newQueryBuilder()
            ->select([
                '*'
            ])->where([
                "status =" . 0,
            ]);

        return PdoConnector::execute($builder);
    }


    public static function getStatementLogStatusError(): array
    {
        $builder = StatementLog::newQueryBuilder()
            ->select([
                '*'
            ])->where([
                "status =" . 0,
            ]);

        $statement_log = PdoConnector::execute($builder);

        $error_load = [];
        foreach ($statement_log as $key => $value) {
            $steps_string = $value->steps;
            $steps_string = trim($steps_string, '"');
            $steps_array = @unserialize($steps_string);

            if ($steps_array === false && $steps_string !== 'b:0;') {
                $steps_array = json_decode($steps_string, true);
            }

            $or_account = $steps_array['or_account'] ?? 0;
            $legal = LegalEntitiesLM::getOurAccountId($or_account);
            $dt = new \DateTime($value->created_at);
            $date_ru = $dt->format('d.m.Y');
            $time_ru = $dt->format('H:i');

            $error_load[] = [
                'id' => $value->id,
                'company_name' => $legal->company_name ?? 'Неизвестно',
                'date' => $date_ru,
                'time' => $time_ru,
            ];

        }

        return $error_load;
    }

    public static function getStatementLogStepsError(int $id)
    {
        $builder = StatementLog::newQueryBuilder()
            ->select([
                '*'
            ])->where([
                "id =" . $id,
            ])
            ->limit(1);

        $statement_log = PdoConnector::execute($builder)[0] ?? [];

        if (!$statement_log){
            return [];
        }

        $steps_string = $statement_log->steps;
        $steps_string = trim($steps_string, '"');
        $steps_array = @unserialize($steps_string);

        if ($steps_array === false && $steps_string !== 'b:0;') {
            $steps_array = json_decode($steps_string, true);
        }

        return [
            'id' => $statement_log->id,
            'steps_array' => $steps_array,
        ];
    }




}