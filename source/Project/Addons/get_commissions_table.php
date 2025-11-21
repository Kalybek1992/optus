<?php

//use Source\Base\Core\Logger;
//use Source\Base\Core\MultiCurl;
//use Source\Project\Connectors\RedisConnector;
//use Source\Project\LogicManagers\KaspiLM;
//use Source\Project\Requests\KaspiKz;
//use Source\Project\Models\MarketProducts;
//use Source\Project\Models\ProductsStat;
//use Source\Project\Requests\WhatsApp;
//use Source\Project\Addons\KaspiFunctions;
use Source\Base\HttpRequests\WebHttpRequest;

//ini_set('error_reporting', 1);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//
//set_error_handler(function ($num, $str, $file, $line) {
//    var_dump($num, $str, $file, $line);
//    die;
//}, -1);
//
//include __DIR__ . "/../../vendor/autoload.php";
//Logger::log('start', 'test');
$request = new WebHttpRequest();

$request_get = $request->get('https://guide.kaspi.kz/partner/ru/shop/conditions/commissions');
if ($request_get){
    //file_put_contents('/var/www/app/texts/commissions.txt', $request_get);
    var_dump($request_get);
} else {
    echo 'NO!';
}die;

