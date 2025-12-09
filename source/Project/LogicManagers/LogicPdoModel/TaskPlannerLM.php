<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\CompanyFinances;
use Source\Project\Models\TaskPlanner;
use DateTime;

/**
 *
 */
class TaskPlannerLM
{

    public static function insertNewTask(array $data)
    {
        $builder = TaskPlanner::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getTaskPlannerCount(): int
    {
        $builder = TaskPlanner::newQueryBuilder()
            ->select(['COUNT(task_planner.id) as count']);

        return PdoConnector::execute($builder)[0]->task_count ?? 0;
    }

    public static function getAllTask($date_from, $date_to, int $offset = 0, int $limit = 8, $legal_id = null, $coming_days = false): array
    {
        $builder = TaskPlanner::newQueryBuilder()
            ->select([
                'tp.*',
                'le.bank_name as bank_name',
                'le.company_name as company_name',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = tp.legal_id',
            ])
            ->from('task_planner tp');

        $day_from = $month_from = null;
        $day_to = $month_to = null;

        if ($date_from) {
            $df = DateTime::createFromFormat('d.m.Y', $date_from);
            $day_from = (int)$df->format('d');
            $month_from = (int)$df->format('m');
        }

        if ($date_to) {
            $dt = DateTime::createFromFormat('d.m.Y', $date_to);
            $day_to = (int)$dt->format('d');
            $month_to = (int)$dt->format('m');
        }

        if ($day_from && $month_from && $day_to && $month_to) {
            $builder->where([
                "(tp.month > $month_from OR (tp.month = $month_from AND tp.day >= $day_from))",
                "(tp.month < $month_to OR (tp.month = $month_to AND tp.day <= $day_to))"
            ]);
        } elseif ($day_from && $month_from) {
            $builder->where([
                "(tp.month > $month_from OR (tp.month = $month_from AND tp.day >= $day_from))"
            ]);
        } elseif ($day_to && $month_to) {
            $builder->where([
                "(tp.month < $month_to OR (tp.month = $month_to AND tp.day <= $day_to))"
            ]);
        }

        if ($legal_id) {
            $builder->where([
                "tp.legal_id = " . $legal_id
            ]);
        }

        if ($coming_days) {
            $builder->where([
                "tp.amount IS NOT NULL",
                "tp.paid IS NOT NULL",
                "tp.amount >= tp.paid"
            ]);
        }

        $builder
            ->orderBy("(CASE WHEN (tp.month < MONTH(CURDATE())) OR (tp.month = MONTH(CURDATE()) AND tp.day < DAY(CURDATE()))
            THEN 1
            ELSE 0 END), tp.month, tp.day");

        if ($limit) {
            $builder
                ->limit($limit)
                ->offset($offset);
        }

        $task_array = [];
        $task = PdoConnector::execute($builder);

        if (!$task) {
            return [];
        }


        return self::getArrStatus($task);
    }

    public static function getAllTaskPlan(): array
    {
        $date_from = date('d.m.Y');
        $date_to = date('d.m.Y', strtotime('+4 days'));

        $builder = TaskPlanner::newQueryBuilder()
            ->select([
                'tp.*',
                'le.bank_name as bank_name',
                'le.company_name as company_name',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = tp.legal_id',
            ])
            ->from('task_planner tp');

        $day_from = $month_from = null;
        $day_to = $month_to = null;

        if ($date_from) {
            $df = DateTime::createFromFormat('d.m.Y', $date_from);
            if ($df) {
                $day_from = (int)$df->format('d');
                $month_from = (int)$df->format('m');
            }
        }

        if ($date_to) {
            $dt = DateTime::createFromFormat('d.m.Y', $date_to);
            if ($dt) {
                $day_to = (int)$dt->format('d');
                $month_to = (int)$dt->format('m');
            }
        }

        if ($day_from && $month_from && $day_to && $month_to) {
            $builder
                ->where(["
           (
            ((tp.month > $month_from OR (tp.month = $month_from AND tp.day >= $day_from))
             AND 
             (tp.month < $month_to OR (tp.month = $month_to AND tp.day <= $day_to)))
             OR 
            ((tp.month < $month_from OR (tp.month = $month_from AND tp.day < $day_from))
             AND tp.amount IS NOT NULL 
             AND tp.paid IS NOT NULL 
             AND tp.amount > tp.paid)
           )
        "]);

        } elseif ($day_from && $month_from) {
            $builder
                ->where([
                "(tp.month > $month_from OR (tp.month = $month_from AND tp.day >= $day_from))"
            ]);
        } elseif ($day_to && $month_to) {
            $builder
                ->where([
                "(tp.month < $month_to OR (tp.month = $month_to AND tp.day <= $day_to))"
            ]);
        }

        // Исключаем полностью оплаченные
        $builder
            ->where([
            "tp.amount IS NOT NULL",
            "tp.paid IS NOT NULL",
            "tp.amount > tp.paid"
        ]);

        $builder
            ->orderBy("
                (CASE 
                    WHEN (tp.month < MONTH(CURDATE())) 
                      OR (tp.month = MONTH(CURDATE()) AND tp.day < DAY(CURDATE())) 
                    THEN 1 
                    ELSE 0 
                END), 
                tp.month, 
                tp.day
            ");


        $task = PdoConnector::execute($builder);

        if (!$task) {
            return [];
        }



        return self::getArrStatus($task);
    }


    public static function getTaskRepeat($plan_name, $comment, $amount, $day, $month, $legal_id = null)
    {
        $builder = TaskPlanner::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                "plan_name = " . "'$plan_name'",
                "comment = " . "'$comment'",
                "day = " . $day,
                "month = " . "'$month'",
                "amount = " . $amount,
            ]);

        if ($legal_id) {
            $builder
                ->where([
                    'legal_id =' . $legal_id
                ]);
        } else {
            $builder
                ->where([
                    "legal_id =" . "'null'"
                ]);
        }

        $builder
            ->limit(1);

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function taskIdDelete(int $task_id)
    {
        $builder = TaskPlanner::newQueryBuilder()
            ->delete()
            ->where([
                'id =' . $task_id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getIdTaskPlanner(int $task_id)
    {
        $builder = TaskPlanner::newQueryBuilder()
            ->select([
                '*'
            ])
            ->where([
                'id =' . $task_id,
            ]);


        return PdoConnector::execute($builder)[0] ?? [];
    }


    public static function updateTaskPlannerId(array $data, $id)
    {

        $builder = TaskPlanner::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        return PdoConnector::execute($builder);
    }

    public static function getArrStatus($task): array
    {
        $task_array = [];

        foreach ($task as $t) {
            $weekday = [
                1 => 'Понедельник',
                2 => 'Вторник',
                3 => 'Среда',
                4 => 'Четверг',
                5 => 'Пятница',
                6 => 'Суббота',
                7 => 'Воскресенье',
            ][$t->weekday] ?? '';

            $today = new DateTime('today');
            $task_date = null;

            // === NONE ===
            if ($t->repeat_type === 'none') {
                $year = isset($t->year) && $t->year ? (int)$t->year : (int)$today->format('Y');
                $month = (int)$t->month;
                $day = (int)$t->day;


                $task_date = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
                if (!$task_date) {
                    $task_date = null;
                }
            }
            // === WEEKLY ===
            elseif ($t->repeat_type === 'weekly') {
                $current_weekday = (int)$today->format('N'); // 1..7
                $target_weekday = max(1, min(7, (int)$t->weekday)); // безопасность
                $diff = $target_weekday - $current_weekday;
                if ($diff < 0) $diff += 7; // ближайший в пределах 0..6
                $task_date = clone $today;
                if ($diff > 0) {
                    $task_date->modify("+{$diff} days");
                }
            }
            // === MONTHLY ===
            elseif ($t->repeat_type === 'monthly') {
                $year = (int)$today->format('Y');
                $month = (int)$today->format('m');
                $day = (int)$t->day;

                // Создаём дату в текущем месяце (без смещения)
                $task_date = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
                if (!$task_date) {
                    $task_date = null;
                }
            }

            $status_color = 'green';

            if ($task_date instanceof DateTime) {

                $diff_days = (int)$today->diff($task_date)->format('%r%a');


                // только если сумма ещё не оплачена
                if ($t->amount > $t->paid) {
                    if ($diff_days <= 0) {
                        // сегодня или уже прошло — просрочено
                        $status_color = 'red';
                    } elseif ($diff_days == 1) {
                        // завтра
                        $status_color = 'yellow';
                    } else {
                        // послезавтра и дальше
                        $status_color = 'green';
                    }
                } else {
                    // полностью оплачено
                    $status_color = 'green';
                }
            } else {
                $status_color = ($t->amount > $t->paid) ? 'red' : 'green';
            }

            $task_array[] = [
                'id' => $t->id,
                'plan_name' => $t->plan_name,
                'comment' => $t->comment,
                'amount' => $t->amount,
                'paid' => $t->paid,
                'repeat_type' => $t->repeat_type,
                'day' => $t->day,
                'month' => $t->month,
                'weekday' => $weekday,
                'legal_id' => $t->legal_id,
                'bank_name' => $t->bank_name,
                'company_name' => $t->company_name,
                'created_at' => $t->created_at,
                'status_color' => $status_color,
                'updated_at' => $t->updated_at,
                'paid_at' => $t->paid >= $t->amount && $t->repeat_type == 'none',
            ];
        }


        return $task_array;
    }

}