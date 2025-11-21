<?php
namespace Source\Project\LogicManagers\Tg;

/**
 * Class TgBotWords
 * @package Source\Enums\IndexController
 */
class WordsManager
{
    public  const array MAPPING_LANGUAGE_ABBREVIATION = [
        "🔄 English" => 'en',
        "🔄 Русский" => 'ru'
    ];

    public const array MAPPING_TEXT_ACTION = [
        "return" => 'simple',
        "/start" => 'simple',
        "about" => 'about',
        "about_sub" => 'about_sub',
        "history_payments" => 'history_payments',
        "services" => 'services',
        "settings" => 'settings',
        "how_connect" => 'how_connect',
        "activations_panel" => 'activations_panel',
        "order_activation" => 'order_activation',
        "search-order_activation" => 'search-order_activation',
        "select_service" => 'select_service',
        "active_activations" => 'active_activations',
        "history_activations" => 'history_activations',
        "help" => 'help',

        "💵 Максимальная цена за номер" => 'option-max_price',
        "🔥 Выдавать номер несколько раз 🔄" => 'option-repeat_sms',
        "🐌 Душилка" => 'option-limit',
        "🕹 Свободный номер" => 'option-free_sms',
        "❓ FAQ (Гайды / Ответы на вопросы)" => 'faq'
    ];

    public const array MAPPING_USER_ACTION = [
        "search_set_id" => 'search_set_id',
        "search_set_token" => 'search_set_token',
        "search_set_user" => 'search_set_user',
        "change_password" => 'change_password',
        "change_token" => 'change_token',
        "add_tariff" => 'add_tariff',
        "get_id_payment" => 'get_id_payment',
        "set_coupon_name" => 'set_coupon_name',
        "set_coupon_count" => 'set_coupon_count',
        "get_coupon_information" => 'get_coupon_information',
        "set_coupon_days" => 'set_coupon_days',
        "result_search_product" => 'result_search_product'
    ];

}

