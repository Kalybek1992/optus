<?php

namespace Source\Project\Controllers;


use Source\Base\Core\Logger;
use Source\LogicManagers\TgBot\ButtonsLM;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\LogicManagers\Tg\ButtonsManager;
use Source\Project\LogicManagers\Tg\TranslateManager;
use Source\Project\LogicManagers\Tg\WordsManager;
use Source\Project\Models\UsersDeveloper;
use Source\Project\Requests\TgBot;




class TgController extends BaseController
{
    /**
     * @return void
     * @throws \Exception
     */
    public function index()
    {
        TranslateManager::start();
        /**
         * @desc all data from bot and functions with user
         * @class TgBotLM
         *
         * @desc @class TgBotLM - all methods and functions with tg
         *
         * @desc has $logic_manager->tg_bot @class TgBotLM and $logic_manager->user @class UsersTelegram
         */

        $tg_bot = new TgBot('7896554343:AAF_uHtzxDa9aMOUane6RfzxlLBJ1lV29UE');
        $tg_bot->getInputData();


        if ($tg_bot->text == '/ping'){
            ButtonsManager::pong($tg_bot);
            return;
        }


        $user_builder = UsersDeveloper::newQueryBuilder()
            ->select([
                '*',
                'ad.developer_user_id as admin',
                'sd.developer_user_id as support'
            ])
            ->leftJoin('admins_developer ad')
            ->on([
                'ad.developer_user_id = id'
            ])
            ->leftJoin('supports_developer sd')
            ->on([
                'sd.developer_user_id = id'
            ])
            ->where([
                'telegram_id = ' . $tg_bot->chat_id,
            ])
            ->limit(1);


        //Logger::log(print_r($user_builder->build() ?? "EMPTY", 1) ?? null, 'user');

        $user = PdoConnector::execute($user_builder)[0] ?? null;


        /**
         * @desc if no user => create
         */


        if ($user == null) {

            UsersDeveloper::insert([
                'telegram_id' => $tg_bot->chat_id,
                'name' => $tg_bot->name,
                'action' => '/start'
            ]);

            $user_builder = UsersDeveloper::newQueryBuilder()
                ->select([
                    '*',
                    'ad.developer_user_id as admin',
                    'sd.developer_user_id as support'
                ])
                ->leftJoin('admins_developer ad')
                ->on([
                    'ad.developer_user_id = id'
                ])
                ->leftJoin('supports_developer sd')
                ->on([
                    'sd.developer_user_id = id'
                ])
                ->where([
                    'telegram_id = ' . $tg_bot->chat_id,
                ])
                ->limit(1);

            $user = PdoConnector::execute($user_builder)[0] ?? null;
        }

        if (!$user->admin && !$user->support) {
            die;
        }



        if (
            TranslateManager::button($tg_bot->text) &&
            (
                TranslateManager::rText($tg_bot->text) ||
                (TranslateManager::iText($tg_bot->text) ?? false)
            )
        ) {

            $action = TranslateManager::button($tg_bot->text);
            $tg_bot->setAction($action);

        } else {
            if (empty($tg_bot->data_bot)) {
                $data_bot = explode('-', $user->action);
                if (WordsManager::MAPPING_USER_ACTION[$data_bot[0]] ?? null) {
                    $tg_bot->data_bot = $data_bot;
                }
            }
        }

        $action = $tg_bot->getAction();
        return ButtonsManager::$action($tg_bot, $user);
    }
}
