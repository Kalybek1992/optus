<?php

namespace Source\Project\Controllers;


use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\StatementLogLM;
use Source\Project\Viewer\ApiViewer;


class RollbackController extends BaseController
{

    public function rollbackErrorUpload(): array
    {
        $selected_id = InformationDC::get('id');
        $statement_log = StatementLogLM::getStatementLogStepsError($selected_id);

        if (!$statement_log) {
            return ApiViewer::getErrorBody(['value' => 'not_statement_log']);
        }

        if (isset($statement_log['steps_array']) && $statement_log['steps_array']) {

            try {
                StatementLogLM::rollbackError($statement_log['steps_array']);
            } catch (Throwable $e) {
                $steps_string = serialize($statement_log['steps_array']);
                StatementLogLM::updateStatementLog([
                    'steps = "' . $steps_string . '"'
                ], $statement_log['id']);

                Logger::log(print_r($statement_log['steps_array'], true), 'rollback_error');
                return ApiViewer::getErrorBody(['value' => 'error_rollback']);
            }

            StatementLogLM::updateStatementLog([
                'status = ' . 3
            ], $selected_id);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

}
