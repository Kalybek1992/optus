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

    $data = json_decode($argv[1], true);

    Logger::log(print_r($data, true), 'end_of_day_settlement');

} catch (Exception $e) {
    Logger::log(print_r($e, true), 'end_of_day_settlement');
    exit($e->getMessage());
}
