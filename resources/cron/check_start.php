<?php

use Source\Base\LogicManagers\ProcessManager;
use Source\Base\Core\Logger;

ini_set('error_reporting', 1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_error_handler(function ($num, $str, $file, $line) {
    var_dump($num, $str, $file, $line);
    die;
}, -1);

include __DIR__ . "/../../vendor/autoload.php";


ProcessManager::runCountProcess('today_report_supplier.php');