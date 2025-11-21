<?php


use Source\Base\Core\Logger;
use Source\Base\Core\Response;
use Source\Project\Controllers\TgController;


ini_set('error_reporting', 1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



//echo get_current_user();
//exit;

set_error_handler(function ($num, $str, $file, $line) {
    var_dump($num, $str, $file, $line);
    die;
}, -1);


include __DIR__ . "/../../vendor/autoload.php";

$response = new Response();
//$response->setHeader('Content-Type', 'application/json');
//$response->setHeader('Access-Control-Allow-Origin', '*');

try {
//    $response = json_decode(file_get_contents('php://input'));
//    Logger::log(json_encode($response), 'tg_bot');
    /**
     * написать все правила для проверки состава запроса
     * $QuESTION какие символы допустимы в строчных переменных?
     *
     * UrlValidateMiddleware там все правила запроса
     */
    // Загрузка конфигурации
//    ConfigManager::loadConfigs(dirname(__DIR__));
    // Загрузка конфигурации маршрутов
//    MiddlewareManager::loadRoutes(Path::RESOURCES_DIR . 'routes/bot.php');

    // Инициализация роутера и других компонентов...
//    $request = new Request();
//
//    $router = new BotRouter();
//    // Вызов роутера
//    $response_array = $router->route($request);

    function isTelegramRequest(): bool {
        $telegramIPs = [
            '149.154.160.0/20',
            '91.108.4.0/22',
        ];

        $remoteIP = $_SERVER['REMOTE_ADDR'];

        foreach ($telegramIPs as $cidr) {
            list($subnet, $mask) = explode('/', $cidr);
            if ((ip2long($remoteIP) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet)) {
                return true;
            }
        }
        return false;
    }

    if (!isTelegramRequest()) {
        http_response_code(403);
        exit('Forbidden');
    }else{
        $controller = new TgController();
        $controller->index();
    }


//    var_dump();
    // Ответ
//    $response->setBody(json_encode($response_array));
//    $response->sendExit();
} catch (Exception $e) {
    Logger::critical(json_encode($e));
}