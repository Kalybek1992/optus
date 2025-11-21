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

class SchedulerController extends BaseController
{
    /**
     * @return string
     * @throws \Exception
     */
    public function addScheduler(): string
    {
        $legal_entitie = LegalEntitiesLM::getNonOurCompanies();

        return $this->twig->render('Scheduler/AddScheduler.twig', [
            'legal_entitie' => $legal_entitie
        ]);
    }

    public function addNewScheduler(): array
    {
        $comment = InformationDC::get('comment');
        $plan_name = InformationDC::get('plan_name');
        $amount = InformationDC::get('amount');
        $date = InformationDC::get('date');
        $legal_id = InformationDC::get('legal_id');
        $repeat_type = InformationDC::get('repeat_type');
        $weekday = InformationDC::get('weekday');
        $day_month = explode('.', $date);

        $day = $day_month[0];
        $month = $day_month[1];

        $get_task_repeat = TaskPlannerLM::getTaskRepeat(
            $plan_name, $comment, $amount, $day, $month, $legal_id
        );

        if ($get_task_repeat) {
            return ApiViewer::getErrorBody(['error' => 'repeat_task']);
        }

        $new = [
            'plan_name' => $plan_name,
            'comment' => $comment,
            'amount' => $amount,
            'day' => $day,
            'month' => $month,
            'repeat_type' => $repeat_type,
            'weekday' => $weekday,
        ];

        if ($legal_id) {
            $new['legal_id'] = $legal_id;
        }

        TaskPlannerLM::insertNewTask($new);

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function allSchedule(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $legal_id = InformationDC::get('legal_id');
        $limit = 30;
        $offset = $page * $limit;

        $task = TaskPlannerLM::getAllTask(
            $date_from,
            $date_to,
            $offset,
            $limit,
            $legal_id
        );
        $task_count = TaskPlannerLM::getTaskPlannerCount();
        $legal_entitie = LegalEntitiesLM::getNonOurCompanies();

        //Logger::log(print_r($task, true), 'allSchedule');

        $page_count = ceil($task_count / $limit);

        return $this->twig->render('Scheduler/AllSchedule.twig', [
            'page' => $page + 1,
            'page_count' => $page_count,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'legal_entitie' => $legal_entitie,
            'plans' => $task,
        ]);
    }

    public function deleteTask(): array
    {
        $task_id = InformationDC::get('task_id');
        $task = TaskPlannerLM::getIdTaskPlanner($task_id);

        if (!$task) {
            return ApiViewer::getErrorBody(['error' => 'task_not_found']);
        }


        TaskPlannerLM::taskIdDelete($task_id);


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function payTask(): array
    {
        $task_id = InformationDC::get('task_id');
        $amount = InformationDC::get('amount');
        $task = TaskPlannerLM::getIdTaskPlanner($task_id);

        if (!$task) {
            return ApiViewer::getErrorBody(['error' => 'task_not_found']);
        }

        $year = (int)date('Y');
        if ($task->repeat_type == 'weekly' || $task->repeat_type == 'monthly') {
            $paid = $task->paid + $amount;

            if ($paid >= $task->amount) {
                $dey = $task->day;
                $month = $task->month;

                $modify = [
                    'weekly' => '+7 days',
                    'monthly' => '+1 month',
                ][$task->repeat_type];

                $date = DateTime::createFromFormat('Y-m-d', "$year-$month-$dey");
                $date->modify($modify);
                $dey = (int)$date->format('d');
                $month = (int)$date->format('m');

                TaskPlannerLM::updateTaskPlannerId([
                    'day =' . $dey,
                    'month =' . $month,
                    'paid =' . 0
                ], $task_id);
            } else {
                TaskPlannerLM::updateTaskPlannerId([
                    'paid =' . $amount + $task->paid,
                ], $task_id);
            }
        }

        if ($task->repeat_type == 'none') {
            TaskPlannerLM::updateTaskPlannerId([
                'paid =' . $amount + $task->paid,
            ], $task_id);
        }


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function editTask(): array
    {
        $comment = InformationDC::get('comment');
        $plan_name = InformationDC::get('plan_name');
        $amount = InformationDC::get('amount');
        $date = InformationDC::get('date');
        $legal_id = InformationDC::get('legal_id');
        $repeat_type = InformationDC::get('repeat_type');
        $weekday = InformationDC::get('weekday');
        $day_month = explode('.', $date);
        $task_id = InformationDC::get('task_id');

        $day = $day_month[0];
        $month = $day_month[1];

        $task = TaskPlannerLM::getIdTaskPlanner($task_id);

        if (!$task) {
            return ApiViewer::getErrorBody(['error' => 'repeat_task']);
        }

        $new = [
            'plan_name =' .  $plan_name,
            'comment =' .  $comment,
            'amount =' . $amount,
            'day =' . $day,
            'month =' . $month,
            'repeat_type =' . $repeat_type,
            'weekday =' . $weekday,
        ];

        if ($legal_id) {
            $new['legal_id'] = $legal_id;
        }

        TaskPlannerLM::updateTaskPlannerId($new, $task_id);
        return ApiViewer::getOkBody(['success' => 'ok']);
    }

}
