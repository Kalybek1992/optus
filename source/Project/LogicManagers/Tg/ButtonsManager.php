<?php

namespace Source\Project\LogicManagers\Tg;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\LogicManagers\LogicPdoModel\MarketProductsLM;
use Source\Project\LogicManagers\LogicPdoModel\MerchantsLM;
use Source\Project\LogicManagers\LogicPdoModel\PaymentsLM;
use Source\Project\LogicManagers\LogicPdoModel\ProductsLM;
use Source\Project\LogicManagers\LogicPdoModel\TariffsLM;
use Source\Project\LogicManagers\LogicPdoModel\UserDeveloperLM;
use Source\Project\LogicManagers\LogicPdoModel\UserLM;
use Source\Project\LogicManagers\LogicPdoModel\UsersPremiumLM;
use Source\Project\Models\UsersBanned;
use Source\Project\Models\UsersPremium;
use Source\Project\Requests\TgBot;

class ButtonsManager
{

    /**
     * @param \Source\Project\Requests\TgBot $tg_bot
     * @param $user
     * @return void
     */

    /**
     * @ /ping
     */
    public static function pong(TgBot $tg_bot): void
    {

        TgManager::sendMessages(
            $tg_bot, [
            'r_text' => "pong"
        ]);

    }

    /**
     * @ 📚 Главное меню
     */
    public static function simple(TgBot $tg_bot, $user): void
    {

        $r_buttons[] = [
            TranslateManager::button('clients'),
            TranslateManager::button('waiting_confirm_payment'),
        ];

        $r_buttons[] = [
            TranslateManager::button('products')
        ];

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('simple')
        ]);

    }

    /**
     * @ 🙋🏿‍♂️ Работа с клиентами
     */
    public static function clients(TgBot $tg_bot, $user): void
    {
        $r_buttons = [
            [
                TranslateManager::button('search_id'),
                TranslateManager::button('search_token')
            ],
            TranslateManager::button('search_mail'),
            TranslateManager::button('return')
        ];

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('clients')
        ]);
    }

    /**
     * @ 🎟 Поиск по ID
     */

    public static function searchId(TgBot $tg_bot, $user): void
    {
        $r_buttons = [TranslateManager::button('return')];

        UserDeveloperLM::actionDeveloper($tg_bot->chat_id, "search_set_user-id");


        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('search_id')
        ]);
    }

    /**
     * @ 🎫 Поиск по токену
     */
    public static function searchToken(TgBot $tg_bot, $user): void
    {
        $r_buttons = [TranslateManager::button('return')];

        UserDeveloperLM::actionDeveloper($tg_bot->chat_id, "search_set_user-token");

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('search_token')
        ]);
    }

    /**
     * @ ✉ Поиск по почте
     */
    public static function searchMail(TgBot $tg_bot, $user): void
    {
        $r_buttons = [TranslateManager::button('return')];

        UserDeveloperLM::actionDeveloper($tg_bot->chat_id, "search_set_user-email");

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('search_mail')
        ]);
    }
    /**
     * @ 🏪 BAZARBAY 🏪
     */
    public static function searchSetUser(TgBot $tg_bot, $user): void
    {
        $r_buttons = [TranslateManager::button('return')];
        $token_or_id = $tg_bot->data_bot[1];

        UserDeveloperLM::actionDeveloper($tg_bot->chat_id);

        if (
            ($token_or_id == "token" && mb_strlen($tg_bot->text) != 32) ||
            ($token_or_id == "id" && !is_numeric($tg_bot->text)) ||
            ($token_or_id == "mail" && !is_numeric($tg_bot->text))
        ) {

            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::iTextConnect('search_set_user',
                    [],
                    "error_$token_or_id")
            ]);

            return;
        }

        $user = UserLM::getUser($tg_bot->text, $token_or_id);

        if (!$user) {
            TgManager::sendMessages(
                $tg_bot, [
                    'r_buttons' => $r_buttons,
                    'r_text' => TranslateManager::iTextConnect('search_set_user',
                        [],
                        'not_user')
                ]
            );
        }


        if ($user->is_banned) {
            $i_buttons[] = TranslateManager::iButtonsConnect('unblock_user', [$user->id]);
        } else {
            $i_buttons[] = TranslateManager::iButtonsConnect('block_user', [$user->id]);
        }

        $buttons_config = [
            ['add_tariff', 'delete_tariff'],
            ['issuance_whatsapp_tariff'],
            ['change_password', 'change_token'],
            [$user->merchants ? 'user_store' : ''],
            ['delete_account', 'quick_login']
        ];

        foreach ($buttons_config as $config) {
            $merged_buttons = [];
            foreach ($config as $button) {
                if ($button) {
                    $merged_buttons = array_merge($merged_buttons, TranslateManager::iButtonsConnect($button, [$user->id]));
                }
            }
            $i_buttons[] = $merged_buttons;
        }

        TgManager::sendMessages(
            $tg_bot, [
            'i_text' => TranslateManager::iTextConnect(
                'search_set_user', [
                    $user->id,
                    $user->email,
                    $user->token,
                    $user->premium_end_at
                        ? $user->tariff_name . " | " . $user->premium_tariff_abs
                        : "нет тарифа",
                    $user->merchants ?? 'отсутствует',
                    $user->tg_username ?? 'hidden',
                    date('d M Y H:i:s', $user->created_at),
                    $user->tariff_price ?? 0,
                    $user->premium_end_at
                        ? date('d M Y | H:i:s', $user->premium_end_at)
                        : "неактивна",
                    $user->premium_tariff_pc ?? 0
                ]
            ),
            'i_buttons' => $i_buttons,
            'r_text' => '➖➖➖➖➖➖➖➖',
            'r_buttons' => $r_buttons,
        ]);
    }

    /**
     * @ 🛒 Товары
     */
    public static function products(TgBot $tg_bot): void
    {
        $r_buttons = [
            TranslateManager::button('search_product_id'),
            TranslateManager::button('search_product_sku'),
            TranslateManager::button('search_product_name'),
            TranslateManager::button('return')
        ];

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('products')
        ]);
    }

    /**
     * @ 🆔 Поиск по продукт ID
     */
    public static function searchProductId(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];

        UserDeveloperLM::actionDeveloper(
            $tg_bot->chat_id,
            "result_search_product-id"
        );

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('search_product_id')
        ]);
    }

    /**
     * @ 🔍 Поиск по SKU
     */
    public static function searchProductSku(TgBot $tg_bot): void
    {
        $r_buttons = [
            TranslateManager::button('return')
        ];

        UserDeveloperLM::actionDeveloper(
            $tg_bot->chat_id,
            "result_search_product-sku"
        );

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('search_product_sku')
        ]);
    }

    /**
     * @ 🔠 Поиск по названию
     */
    public static function searchProductName(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];

        UserDeveloperLM::actionDeveloper(
            $tg_bot->chat_id,
            "result_search_product-name"
        );

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('search_product_name')
        ]);
    }

    /**
     * @ *Список вариантов товара.*
     */
    public static function resultSearchProduct(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];


        UserDeveloperLM::actionDeveloper($tg_bot->chat_id);
        $parameter_search = $tg_bot->data_bot[1];
        $search_txt = $tg_bot->text;

        $products = ProductsLM::getProducts($search_txt, $parameter_search);

        if (!$products) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect('search_product_information',
                    $tg_bot->text,
                    'no_product'
                )
            ]);
            return;
        }

        $active_products = [];
        $inactive_products = [];

        foreach ($products as $product) {
            $active_txt = $product->active > 0 ? "✅ " : "❌ ";
            $product_name_shop = $product->name_shop ?? 'No store';
            if ($product->active > 0) {
                $active_products += [$active_txt . "$product_name_shop | " . $product->model =>
                    "$product->id-$product->merchant_id"];
            } else {
                $inactive_products += [$active_txt . "$product_name_shop  | " . $product->model =>
                    "$product->id-$product->merchant_id"];
            }

        }

        $product_array = $active_products + $inactive_products;

        $i_buttons = TranslateManager::iButtonsGetPages(
            'search_product_information',
            8,
            "result_search_product",
            $tg_bot->data_bot,
            $product_array
        );

        $i_text = TranslateManager::iTextConnect('search_product_information');
        //Logger::log(print_r($i_buttons ?? "EMPTY", 1), "i_buttons");

        $edit_messages = false;
        if (in_array('forward', $tg_bot->data_bot) ||
            in_array('backward', $tg_bot->data_bot)) {
            $edit_messages = true;

        }

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => '➖➖➖➖➖➖➖➖',
            'i_buttons' => $i_buttons,
            'i_text' => $i_text,
        ], $edit_messages);
    }

    /**
     * @ ℹ*Информация о товаре*
     */
    public static function searchProductInformation(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];

        $product_id = $tg_bot->data_bot[1];
        $merchant_id = $tg_bot->data_bot[2];

        $product = MarketProductsLM::getProductsProductId($product_id, $merchant_id);

        $merchant_name = $merchant_id ? $product->merchant_name : 'No store';


        $r_text = TranslateManager::rTextConnect('search_product_information', [
            $product->active > 0 ? "✅ Активен" : "❌ Неактивен",
            $merchant_name,
            $product->max_price ?? 0,
            $product->min_price ?? 0,
            $product->price ?? 0,
            $product->model,
            $product->id,
            $product->sku,
        ]);

        TgManager::sendMessages(
            $tg_bot, [
            'r_text' => $r_text,
            'r_buttons' => $r_buttons,
        ]);
    }

    /**
     * @ Быстрый вход 🚪🚶‍♂️
     */
    public static function getQuickLoginLink(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];

        $user_id = $tg_bot->data_bot[1];
        $user = UserLM::getUser($user_id, 'id');

        $i_buttons = TranslateManager::iButtonsConnect('get_quick_login_link', [
            "https://panel.bazarbay.kz/fastlog=$user->token"
        ]);

        $i_text = TranslateManager::iTextConnect('get_quick_login_link');

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'i_buttons' => $i_buttons,
            'i_text' => $i_text,
            'r_text' => '➖➖➖➖➖➖➖➖',
        ]);
    }

    /**
     * @ Заблокировать 🔒
     */
    public static function blockUser(TgBot $tg_bot, $user): void
    {
        $user_id = $tg_bot->data_bot[1];
        $r_buttons[] = [
            TranslateManager::button('return')
        ];

        $builder = UsersBanned::newQueryBuilder()
            ->insert(["user_id" => $user_id]);

        $result_build = PdoConnector::execute($builder);

        if (!$result_build) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect(
                    'block_user',
                    $user_id,
                    'error_block'
                )
            ]);
        } else {
            $tg_bot->data_bot = ['blockUser', 'id'];
            $tg_bot->text = $user_id;
            self::searchSetUser($tg_bot, $user);
        }
    }

    /**
     * @ Разблокировать 🔐
     */
    public static function unblockUser(TgBot $tg_bot, $user): void
    {
        $user_id = $tg_bot->data_bot[1];
        $r_buttons[] = [
            TranslateManager::button('return')
        ];

        $builder = UsersBanned::newQueryBuilder()
            ->delete()
            ->where([
                'user_id = ' . $user_id
            ]);

        $result_build = PdoConnector::execute($builder);

        if (!$result_build) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect(
                    'unblock_user',
                    $user_id,
                    'error_unblock'
                )
            ]);
        } else {
            $tg_bot->data_bot = ['unblockUser', 'id'];
            $tg_bot->text = $user_id;
            self::searchSetUser($tg_bot, $user);
        }
    }

    /**
     * @ Добавить тариф 💼
     */
    public static function addTariff(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];
        $user_id = $tg_bot->data_bot[1];
        $tariffs = TariffsLM::getAllTariffs();

        if (!$tariffs) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_text' => TranslateManager::rTextConnect("tariffs", [], 'no_tariff'),
            ]);
        }


        $i_buttons_arr = [];
        foreach ($tariffs as $tariff) {
            $i_button_txt = TranslateManager::rTextConnect("tariffs", [
                $tariff->name,
                $tariff->products_count,
                $tariff->price,
                $tariff->days
            ], 'tariff');

            $data_action = "$tariff->name-$tariff->products_count-$tariff->price-$tariff->days-$tariff->abs-$tariff->id-$user_id";
            $i_buttons_arr += [$i_button_txt => $data_action];

        }

        $i_text = TranslateManager::iTextConnect("tariffs");
        $i_buttons = TranslateManager::iButtonsGetPages(
            'tariffs',
            8,
            "add_tariff",
            $tg_bot->data_bot,
            $i_buttons_arr
        );

        $edit_messages = in_array('forward', $tg_bot->data_bot) || in_array('backward', $tg_bot->data_bot);


        TgManager::sendMessages(
            $tg_bot, [
            'i_buttons' => $i_buttons,
            'i_text' => $i_text,
            'r_buttons' => $r_buttons,
            'r_text' => '➖➖➖➖➖➖➖➖',
        ], $edit_messages);
    }

    /**
     * @ Добавить тариф 💼
     */
    public static function setTariff(TgBot $tg_bot, $user): void
    {
        list(
            $_,
            $tariff_name,
            $tariff_products_count,
            $tariff_price,
            $tariff_days,
            $tariff_abs,
            $tariff_id,
            $user_id
            ) = $tg_bot->data_bot;


        $user_premium = UsersPremiumLM::getUserPremium($user_id);

        $time = time();
        $new_time = $time + ($tariff_days * 24 * 60 * 60);
        $success = 'success_1';

        if ($user_premium && $user_premium->end_at > $time) {
            $new_time += $user_premium->end_at - $time;
        }

        if ($user_premium) {
            $success = 'tariff_updated';
            UsersPremiumLM::setUserPremium([
                "tariff_id = $tariff_id",
                "updated_at =" . $time,
                "end_at = $new_time"
            ]);
        } else {
            UsersPremiumLM::setNewUserPremium([
                    "user_id" => $user_id,
                    "tariff_id" => $tariff_id,
                    "updated_at " => $time,
                    "end_at" => $new_time
                ]);
        }

        $r_text = TranslateManager::rTextConnect('add_tariff',
            [
                $tariff_name,
                $tariff_abs,
                $tariff_price,
                date('d M Y | H:i:s', $new_time),
                $tariff_products_count
            ],
            $success
        );

        TgManager::sendMessages(
            $tg_bot, [
            'r_text' => $r_text,
        ]);

        $tg_bot->data_bot = ['setTariff', 'id'];
        $tg_bot->text = $user_id;
        self::searchSetUser($tg_bot, $user);
    }

    /**
     * @ Удалить тариф 🔧
     */
    public static function deleteTariff(TgBot $tg_bot, $user): void
    {

        $r_buttons = [TranslateManager::button('return')];
        $user_id = $tg_bot->data_bot[1];
        $client_user = UserLM::getUser($user_id, 'id') ?? null;


        if (!$client_user->tariff_name) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_text' => TranslateManager::rTextConnect('delete_tariff', $user_id, 'error_no_tariff')
            ]);

            $tg_bot->data_bot = ['deleteTariff', 'id'];
            $tg_bot->text = $user_id;
            self::searchSetUser($tg_bot, $user);
            return;
        }

        UsersPremiumLM::deleteUserPremium($user_id);

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect(
                'delete_tariff',
                $client_user->tariff_name,
            )
        ]);

        $tg_bot->data_bot = ['deleteTariff', 'id'];
        $tg_bot->text = $user_id;
        self::searchSetUser($tg_bot, $user);
    }

    /**
     * @ Удалить аккаунт 🚮
     */
    public static function deleteAccountConfirmation(TgBot $tg_bot): void
    {
        $r_buttons[] = [TranslateManager::button('return')];
        $user_id = $tg_bot->data_bot[1];

        $i_buttons = TranslateManager::iButtonsConnect('delete_account_confirmation', [
            $user_id,
            $user_id
        ]);

        $keys_to_wrap = [
            ["set_delete_account", "set_delete_account"],
        ];

        $i_buttons = TranslateManager::iButtonsLayout($i_buttons, $keys_to_wrap);
        $i_text = TranslateManager::iTextConnect('delete_account_confirmation');


        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'i_buttons' => $i_buttons,
            'i_text' => $i_text,
            'r_text' => '➖➖➖➖➖➖➖➖',
        ]);
    }

    /**
     * @ Удалить аккаунт 🚮 (✅ Да | ❌ Нет)
     */
    public static function setDeleteAccount(TgBot $tg_bot, $user): void
    {

        $user_id = $tg_bot->data_bot[1];

        $result_txt = [
            'yes' => TranslateManager::rTextConnect('set_delete_account', [], 'delete_account'),
            'no' => TranslateManager::rTextConnect('set_delete_account', [], 'delete_account_cancel')
        ][$tg_bot->data_bot[2]];

        TgManager::sendMessages(
            $tg_bot, [
            'r_text' => $result_txt,
        ]);


        if ($tg_bot->data_bot[2] == 'yes') {
            UserLM::setUserIsDeleted($user_id, 1);
            self::simple($tg_bot, $user);
        } else {
            $tg_bot->data_bot = ['searchSetUser', 'id'];
            $tg_bot->text = $user_id;
            self::searchSetUser($tg_bot, $user);
        }
    }

    /**
     * @ Заменить пароль 🔑
     */
    public static function changePassword(TgBot $tg_bot, $user): void
    {
        $r_buttons = [TranslateManager::button('return')];
        $user_id = $tg_bot->data_bot[1];
        $old_user_password = $tg_bot->data_bot[2] ?? null;


        if ($tg_bot->data['callback_query_id'] ?? false) {
            $user_client = UserLM::getUser($user_id, 'id');

            UserDeveloperLM::actionDeveloper($tg_bot->chat_id,
                "change_password-$user_id-$user_client->password"
            );

            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect(
                    'change_password',
                    $user_client->password,
                    'waiting_new_password'
                )
            ]);
            return;
        }

        if (
            mb_strlen($tg_bot->text) < 8 ||
            mb_strlen($tg_bot->text) > 15 ||
            !preg_match('/^[a-zA-Z0-9]+$/', $tg_bot->text) ||
            $tg_bot->text == $old_user_password
        ) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect(
                    'change_password',
                    $old_user_password,
                    'error_password'
                )
            ]);

            return;
        }


        UserLM::setUserNewPassword($user_id, $tg_bot->text);

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect(
                'change_password',
                [
                    $old_user_password,
                    $tg_bot->text
                ],
                'new_password'
            )
        ]);

        $tg_bot->data_bot = ['changePassword', 'id'];
        $tg_bot->text = $user_id;
        self::searchSetUser($tg_bot, $user);
    }

    /**
     * @ Заменить токен 🔄
     */
    public static function changeToken(TgBot $tg_bot, $user): void
    {
        $r_buttons = [TranslateManager::button('return')];

        $user_id = $tg_bot->data_bot[1];
        $new_token = $tg_bot->data_bot[2] ?? false;

        if (!$new_token) {
            $user = UserLM::getUser($user_id, 'id');
            $new_token = substr(str_shuffle(bin2hex(random_bytes(32 / 2))), 0, 32);

            TgManager::sendMessages(
                $tg_bot, [
                'i_text' => TranslateManager::rTextConnect(
                    'change_token',
                    [
                        $user->token,
                        $new_token
                    ],
                    'new_token'
                ),
                'i_buttons' => TranslateManager::iButtonsConnect('change_token',
                    [
                        $user->id,
                        $new_token
                    ]),
                'r_text' => '➖➖➖➖➖➖➖➖',
                'r_buttons' => $r_buttons,
            ]);

            return;
        }

        UserLM::setUserNewToken($user_id, $new_token);

        $tg_bot->data_bot = ['changeToken', 'id'];
        $tg_bot->text = $user_id;
        self::searchSetUser($tg_bot, $user);
    }

    /**
     * @ Выдача whatsapp тарифа 💬
     */
    public static function issuanceWhatsappTariff(TgBot $tg_bot): void
    {
        $r_buttons[] = [
            TranslateManager::button('return')
        ];

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => 'Экшен issuanceWhatsappTariff'
        ]);
    }

    /**
     * @ 💳 Подтвердить платёж
     */
    public static function waitingConfirmPayment(TgBot $tg_bot): void
    {
        $r_buttons[] = [
            TranslateManager::button('return')
        ];
        UserDeveloperLM::actionDeveloper($tg_bot->chat_id, "get_id_payment");

        TgManager::sendMessages(
            $tg_bot, [
            'r_buttons' => $r_buttons,
            'r_text' => TranslateManager::rTextConnect('waiting_confirm_payment'),

        ]);
    }

    /**
     * @ Введите ID оплаты.
     */
    public static function getIdPayment(TgBot $tg_bot): void
    {
        $id_payment = $tg_bot->text;
        $r_buttons[] = [
            TranslateManager::button('return')
        ];
        UserDeveloperLM::actionDeveloper($tg_bot->chat_id);

        if (!is_numeric($id_payment)) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect('waiting_confirm_payment',
                    [],
                    'error_txt'
                ),

            ]);
            return;
        }

        $user_payments = PaymentsLM::getPayments($id_payment);

        if (
            !$user_payments ||
            !$user_payments->user ||
            $user_payments->status
        ) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect('waiting_confirm_payment',
                    $id_payment,
                    'error_no_payments'
                ),

            ]);
            return;
        }

        $new_premium_end_at = $user_payments->updated_at;

        if ($user_payments->premium_end_at ?? 0 > time()) {
            $new_premium_end_at += $user_payments->premium_end_at - time();
        }

        $i_text = TranslateManager::iTextConnect('waiting_confirm_payment', [
            $user_payments->user,
            "$user_payments->tariff_name | $user_payments->tariff_abs",
            $user_payments->tariff_price,
            date('d M Y | H:i:s', $user_payments->created_at),
            date('d M Y | H:i:s', $new_premium_end_at),
            $user_payments->tariff_products_count
        ]);

        $i_buttons = TranslateManager::iButtonsConnect('waiting_confirm_payment',
            [
                $id_payment,
                $user_payments->user,
                $new_premium_end_at,
                $user_payments->tariff_id,
                $user_payments->premium_end_at ?? null
            ]);

        TgManager::sendMessages(
            $tg_bot, [
            'i_buttons' => $i_buttons,
            'i_text' => $i_text,
            'r_buttons' => $r_buttons,
            'r_text' => '➖➖➖➖➖➖➖➖',

        ]);
    }

    /**
     * @ Подтвердить оплату ✅
     */
    public static function confirmPayment(TgBot $tg_bot, $user): void
    {
        list($_, $id_payment, $user_id, $new_premium_end_at, $tariff_id, $update)
            = $tg_bot->data_bot;
        PaymentsLM::updateStatusPayments($id_payment);

        if ($update) {
            $premium_build = UsersPremium::newQueryBuilder()
                ->update([
                    "tariff_id = $tariff_id",
                    "updated_at =" . time(),
                    "end_at = $new_premium_end_at",
                ])
                ->where(['user_id = ' . '"' . $user_id . '"']);
        } else {
            $premium_build = UsersPremium::newQueryBuilder()
                ->insert([
                    "tariff_id" => $tariff_id,
                    "updated_at " => time(),
                    "end_at" => $new_premium_end_at,
                    "user_id" => $user_id,
                ]);
        }

        PdoConnector::execute($premium_build);

        $tg_bot->data_bot = ['confirmPayment', 'id'];
        $tg_bot->text = $user_id;
        self::searchSetUser($tg_bot, $user);
    }

    /**
     * @ Магазины 🛍️
     */
    public static function userStore(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];
        $user_id = $tg_bot->data_bot[1];

        $merchants = MerchantsLM::getMerchantsUserId($user_id);

        foreach ($merchants as $merchant) {
            $i_buttons[] = TranslateManager::iButtonsConnect('user_merchants',
                [
                    $merchant->name => $merchant->merchant_id,
                    $merchant->product_count => 0
                ]);
        }

        $i_text = TranslateManager::iTextConnect('user_merchants');

        TgManager::sendMessages(
            $tg_bot, [
            'i_buttons' => $i_buttons,
            'i_text' => $i_text,
            'r_buttons' => $r_buttons,
            'r_text' => '➖➖➖➖➖➖➖➖',

        ]);
    }

    /**
     * @ ℹ*Список магазинов*
     */
    public static function userMerchants(TgBot $tg_bot): void
    {
        $r_buttons[] = [
            TranslateManager::button('return')
        ];

        $merchant_id = $tg_bot->data_bot[1];

        $products = MarketProductsLM::getProductsMerchantId($merchant_id);

        if (!$products) {
            TgManager::sendMessages(
                $tg_bot, [
                'r_buttons' => $r_buttons,
                'r_text' => TranslateManager::rTextConnect('product_information',
                    [],
                    'no_product'
                ),

            ]);
            return;
        }

        $active_products = [];
        $inactive_products = [];
        foreach ($products as $product) {
            $key = ($product->active > 0 ? "✅ " : "❌ ") . $product->product_model;
            if (isset($key_count[$key])) {
                $key_count[$key]++;
                $new_key = $key . '_' . $key_count[$key];
            } else {
                $key_count[$key] = 0;
                $new_key = $key;
            }

            if ($product->active > 0) {
                $active_products[$new_key] = $product->product_id;
            } else {
                $inactive_products[$new_key] = $product->product_id;
            }
        }

        $product_array = $active_products + $inactive_products;

        $i_buttons = TranslateManager::iButtonsGetPages(
            'product_information',
            8,
            "user_merchants",
            $tg_bot->data_bot,
            $product_array
        );

        $edit_messages = in_array('forward', $tg_bot->data_bot) || in_array('backward', $tg_bot->data_bot);
        $i_text = TranslateManager::iTextConnect('product_information');

        TgManager::sendMessages(
            $tg_bot, [
            'i_text' => $i_text,
            'i_buttons' => $i_buttons,
            'r_text' => "➖➖➖➖➖➖➖➖",
            'r_buttons' => $r_buttons,
        ], $edit_messages);
    }

    /**
     * @ ℹ*Список товаров* | ℹ*Информация о товаре*
     */
    public static function productInformation(TgBot $tg_bot): void
    {
        $r_buttons = [TranslateManager::button('return')];
        $product_id = $tg_bot->data_bot[1];
        $product = ProductsLM::getProducts($product_id)[0];

        $r_text = TranslateManager::rTextConnect('product_information', [
            $product->active > 0 ? "✅ Активен" : "❌ Неактивен",
            $product->max_price ?? 0,
            $product->min_price ?? 0,
            $product->price ?? 0,
            $product->model,
            $product->id,
            $product->sku,
        ]);

        TgManager::sendMessages(
            $tg_bot, [
            'r_text' => $r_text,
            'r_buttons' => $r_buttons,
        ]);
    }

    //Logger::log(print_r($products_arg ?? "EMPTY", 1), 'ButtonsLM');
    public static function __callStatic(string $name, array $arguments)
    {

        $name = strtolower($name);

        if (strpos($name, '_') !== false || strpos($name, '-') !== false) {
            $name = str_replace('_', '', explode('-', $name)[0]);
            self::$name(...$arguments);

            return;
        }

        if ($name != 'stopaction') {
            Logger::critical('Error function ' . $name);
        }
    }
}