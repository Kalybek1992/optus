<?php

use Source\Base\Core\Logger;

use Source\Project\LogicManagers\LogicPdoModel\ReportsLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\ManagersLM;
use Source\Project\LogicManagers\LogicPdoModel\EndOfDaySettlementLM;

ini_set('error_reporting', 1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_error_handler(function ($num, $str, $file, $line) {
    Logger::critical(implode(' - ', ['num : ' . $num, 'str : ' . $str, 'file : ' . $file, 'line : ' . $line]));
    die;
}, -1);

include __DIR__ . "/../../vendor/autoload.php";

try {

    $max_date_raw = EndOfDaySettlementLM::getMaxDate(); // формат Y-m-d
    $today = date('d.m.Y');
    $inserts = [];
    $suppliers = SuppliersLM::getSuppliersAll();

    if ($max_date_raw) {
        // Есть данные
        $max_date = date('d.m.Y', strtotime($max_date_raw));
        // Если база уже содержит запись за сегодня — выход
        if ($max_date === $today) {
            Logger::log("Сегодняшний отчет уже есть", 'today_report_supplier');
            exit;
        }
        // Начинаем с дня после max_date
        $current_date = date('d.m.Y', strtotime($max_date . ' +1 day'));
    } else {
        // Базы нет → проверяем только сегодня
        $current_date = $today;
    }

    while (true) {
        $date_from = $current_date;
        $date_to = $current_date;

        foreach ($suppliers as $supplier) {
            $supplier_id = $supplier['supplier_id'];
            $managers = ManagersLM::getManagersAll($supplier_id);

            foreach ($managers as $manager) {
                $manager_id = $manager['manager_id'];

                $report = ReportsLM::checkinglastDaysScript(
                    $date_from,
                    $date_to,
                    $manager_id,
                    $supplier_id
                );

                if ($report && $report['amount'] != 0) {
                    $inserts[] = [
                        'manager_id' => $manager_id,
                        'amount' => $report['amount'],
                        'scenario' => $report['scenario'],
                        'date' => date('Y-m-d', strtotime($current_date)),
                    ];
                }
            }

            usleep(50000);
        }

        // Дошли до сегодняшнего дня — стоп
        if ($current_date === $today) {
            break;
        }

        $current_date = date('d.m.Y', strtotime($current_date . ' +1 day'));
    }

    if ($inserts) {
        EndOfDaySettlementLM::insertEndOfDaySettlement($inserts);
        Logger::log("Добавили отчеты за {$current_date}", 'today_report_supplier');
    } else {
        Logger::log("Нет данных за {$current_date}", 'today_report_supplier');
    }

} catch (Exception $e) {
    exit($e->getMessage());
}
