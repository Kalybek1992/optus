<?php


use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Request;
use Source\Base\LogicManagers\ConfigManager;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Responses\AppResponse;
use Source\Project\Routers\AppRouter;
use Source\Project\Controllers\ErrorController;
use Source\Base\Core\Logger;
use \Source\Base\Constants\Settings\Time;

ini_set('error_reporting', 1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_error_handler(function ($num, $str, $file, $line) {
    var_dump($num, $str, $file, $line);
    die;
}, -1);
set_time_limit(60);

include __DIR__ . "/../vendor/autoload.php";

$response = new AppResponse();
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('Access-Control-Allow-Origin', '*');
$response->setStatusCode(200);


try {
    ConfigManager::loadConfigs(dirname(__DIR__));
    MiddlewareManager::loadRoutes(Path::RESOURCES_DIR . 'routes/app.php');
    date_default_timezone_set(Time::TIME_ZONE);

    $request = new Request();
    $router = new AppRouter();
    $response_set = $router->route($request);

    //$error_controller = new ErrorController();
    //$time_from = '23:50'; $time_to = '00:10'; $current_time = date('H:i');
    //if ($time_from < $time_to) {
    //    $in_range = ($current_time >= $time_from && $current_time <= $time_to);
    //} else {
    //    $in_range = ($current_time >= $time_from || $current_time <= $time_to);
    //}

    //if ($in_range) {
    //    $body = $error_controller->timeBlocking($time_from, $time_to);
    //    $response->setBody($body);
    //    $response->sendHtmlExit();
    //}

    if (is_array($response_set)) {
        $response->setBody(json_encode($response_set));
        $response->sendExit();
    } elseif (is_string($response_set)) {
        $response->setBody($response_set);
        $response->sendHtmlExit();
    }

} catch (Throwable $e) {

    $log = "EXCEPTION\n";
    $log .= "Message: " . $e->getMessage() . "\n";
    $log .= "File: " . $e->getFile() . "\n";
    $log .= "Line: " . $e->getLine() . "\n";
    $log .= "Code: " . $e->getCode() . "\n";
    $log .= "Trace:\n" . $e->getTraceAsString();

    Logger::log($log, 'error_exception');

    $error_controller = new ErrorController();
    $body = $error_controller->errorPage();

    $response->setBody($body);
    $response->sendHtmlExit();
}
