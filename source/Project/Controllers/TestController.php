<?php

namespace Source\Project\Controllers;

use Source\Base\Core\DataContainer;
use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\TaskPlannerLM;
use Source\Project\Viewer\ApiViewer;
use DateTime;

class TestController extends BaseController
{
    /**
     * @return string
     * @throws \Exception
     */

    public function addNewScheduler(): array
    {

        Logger::log(print_r('tet', true), 'cron');

        return ApiViewer::getOkBody(['success' => 'ok']);
    }


}
