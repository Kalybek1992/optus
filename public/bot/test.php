<?php


use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\LogicManagers\LogicPdoModel\UserLM;
use Source\Project\LogicManagers\Tg\TranslateManager;
use Source\Project\Models\Users;
use Source\Project\Models\UsersDeveloper;
use Source\Project\Requests\TgBot;

include __DIR__ . "/../../vendor/autoload.php";

//$user_builder = UsersDeveloper::newQueryBuilder()
//    ->select([
//        '*',
//        'ad.developer_user_id as admin',
//        'sd.developer_user_id as support'
//    ])
//    ->leftJoin('admins_developer ad')
//    ->on([
//        'ad.developer_user_id = id'
//    ])
//    ->leftJoin('supports_developer sd')
//    ->on([
//        'sd.developer_user_id = id'
//    ])
//    ->where([
//        'telegram_id = ' . 5204778806,
//    ])
//    ->limit(1);
//
//$user = PdoConnector::execute($user_builder)[0] ?? null;

//$test = TranslateManager::rTextConnect('clients');

var_dump(date('Y-m-d H:i:s', 1723613156));


