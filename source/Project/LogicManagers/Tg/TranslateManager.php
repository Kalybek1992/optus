<?php

namespace Source\Project\LogicManagers\Tg;

use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Logger;
use Source\Base\Core\Validator;

/**
 * Class TgLocalization
 * @package Source\LogicManagers\TgBot\Enums
 */
class TranslateManager
{
    /**
     * @var mixed
     */
    protected static mixed $translate = null;

    public static ?string $lang = null;

    /**
     * @param string $language
     * @return void
     */
    public static function start(string $language = 'ru'): void
    {
        self::$lang = $language;

        self::$translate = json_decode(
            file_get_contents(Path::RESOURCES_DIR
                . 'json/localization/'
                . $language . '.json'
            ),
            true
        );
    }

    /**
     * @param $original_values
     * @param $replaced_array
     * @return array|mixed|string|string[]|null
     */
    protected static function replaceValues($original_values, $replaced_array = null): mixed
    {
        if ($replaced_array !== null && $original_values !== null) {
            if (is_array($original_values)) {

                $replaced_keys = array_keys($replaced_array);
                $original_keys = array_keys($original_values);

                $implode_keys = implode('$#', $original_keys);
                $implode_values = implode('$#', $original_values);

                preg_match_all("#{{text}}#", $implode_keys, $original_keys_values);
                $count_original_keys = count($original_keys_values[0]);

                preg_match_all("#{{text}}#", $implode_values, $original_values_v);
                $count_original_values_v = count($original_values_v[0]);

                if ($count_original_keys) {
                    $original_keys = explode('$#',
                        preg_replace(
                            array_fill(0, $count_original_keys, "#{{text}}#"),
                            $replaced_keys,
                            $implode_keys,
                            1
                        )
                    );
                }

                if ($count_original_values_v) {
                    $original_values = explode('$#',
                        preg_replace(
                            array_fill(0, $count_original_values_v, "#{{text}}#"),
                            $replaced_array,
                            $implode_values,
                            1
                        )
                    );
                }

                return array_combine($original_keys, $original_values);
            } else {

                preg_match_all("#{{text}}#", $original_values, $values);
                $count = count($values[0]);

                $original_values = preg_replace(
                    array_fill(0, $count, "#{{text}}#"),
                    is_array($replaced_array)
                        ? $replaced_array
                        : [$replaced_array],
                    $original_values,
                    1
                );

            }
        }

        return $original_values;
    }

    /**
     * @param string $text
     * @param array|null $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function button(string $text, array $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        return self::getTranslate($text, null, 'button', $replace_array);
    }

    /**
     * @param string $text_connect
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function iTextConnect(string $text_connect, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        $button = self::getTranslate($text_connect, null, 'button', $replace_array);

        return self::getTranslate($button, $success, 'i_text', $replace_array);
    }

    /**
     * @param string $text_connect
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function iButtonsConnect(string $text_connect, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        $button = self::getTranslate($text_connect, null, 'button', $replace_array);

        //Logger::log(print_r($button ?? "EMPTY", 1) ?? null, '$button');


        return self::getTranslate($button, $success, 'i_buttons', $replace_array);
    }

    public static function iButtonsLayout(array $buttons_array, array $keys_to_wrap): array
    {
        foreach ($keys_to_wrap as $group_keys) {
            $wrapped = [];
            foreach ($group_keys as $key_to_wrap) {
                foreach ($buttons_array as $key => $value) {

                    if (is_string($value)){
                        $value_explode = explode('-', $value)[0];
                    }else{
                        $value_explode = $value;
                    }

                    if ($value_explode === $key_to_wrap) {
                        $wrapped[$key] = $value;
                        unset($buttons_array[$key]);
                    }
                }
            }
            if (!empty($wrapped)) {
                $buttons_array[] = $wrapped;
            }
        }

        return $buttons_array;
    }
    /**
     * @param string $text_connect
     * @param ?string $success
     * @param array $options
     * @return array|string
     *
     * @desc @param array $options
     * callback_key
     * callback_id_key
     * callback_param
     * page
     * page_limit
     * data_array
     * button_text
     * @throws \Exception
     */
    public static function iButtonsConnectPages(string $text_connect, ?string $success = 'success_1', array $options = []): array|string
    {
        $button = self::getTranslate($text_connect, null, 'button');

        $i_buttons = [];

        $page = $options['page'] ?? 0;
        $page_limit = $options['page_limit'] ?? 10;

        $is_success = !is_bool($success) && $success !== null;

        if ($is_success) {
            $success_regex = $success;
        }

        $callback_key = $options['callback_key'];
        $callback_id_key = $options['callback_id_key'];
        $callback_param = ($options['callback_param'] ?? false)
            ? "-" . $options['callback_param']
            : null;

        $data_array = $options['data_array'];
        $button_text = $options['button_text'] ?? 'button_text';

        $next_page = array_slice($data_array, $page_limit, 1, true) == [] ? null : $page + 1;
        $previous_page = $page > 0 ? $page - 1 : $page;

        for ($i = 0, $count = count($data_array); $i < $page_limit && $i < $count; $i++) {


            if ($callback_id_key && $callback_key) {
                $key = $callback_key . $callback_param .
                    '-' . ($data_array[$i][$callback_id_key] ?? null);
            } else {
                $key = 'empty_button';
            }

            if (is_array($button_text)) {
                $replaced_array = [];

                foreach ($button_text as $value) {
                    $replaced_array[$data_array[$i][$value]] = $key;

                    if ($is_success) {
                        $success = Validator::validate($data_array[$i][$value], $success_regex);
                    }
                }

                $i_buttons += self::getTranslate($button, $success, 'i_button_text', $replaced_array);
            } else {
                $i_buttons += self::getTranslate($button, $success, 'i_button_text', [
                    $data_array[$i][$button_text] => $key
                ]);
            }

        }

//        MainLogger::write(print_r($i_buttons ?? null, 1), 'buttons').die();

        $i_buttons[$i][$page ? '⬅️' : '⏹'] = $page
            ? $text_connect . '-' . $previous_page . $callback_param
            : 'empty_button';

        $i_buttons[$i][$next_page ? '➡️' : '⏹ '] = $next_page
            ? $text_connect . '-' . $next_page . $callback_param
            : 'empty_button';

        return $i_buttons;
    }

    public static function iButtonsGetPages(string $text_connect, int $buttons_count, $action, $data_bot = [], $options = [],  ?string $success = 'success_1'): array|string
    {
        $chunks = array_chunk($options, $buttons_count);

        if (in_array('forward', $data_bot)){
            array_pop($data_bot);
            $page = array_pop($data_bot);

            $page = $page < count($chunks) - 1 ? $page + 1 : $page;
        }elseif (in_array('backward', $data_bot)){
            array_pop($data_bot);
            $page = array_pop($data_bot);

            $page = $page > 0 ? $page - 1 : $page;
        }else{
            $page = 0;
        }

        $actual_data = array_slice(
            $options,
            $page * $buttons_count,
            $buttons_count
        );

        $next_data = array_slice(
            $options,
            ($page + 1) * $buttons_count,
            $buttons_count
        );


        $i_buttons = [];

        foreach ($actual_data as $option => $value) {
            $i_buttons[] = self::iButtonsConnect($text_connect, [
                $option => $value
            ], $success);
        }

        foreach ($data_bot as $data => $value){
            if ($data > 0 && $value){
                $action .= "-$value";
            }
        }

        $txt_backward = $page != 0 ? "◀" : "⏹";

        $txt_forward = empty($next_data) ? '⏹️' : "▶️";


        $forward_action = [
            '⏹️' => 'stop_action',
            '▶️' => $action
        ][$txt_forward];

        $backward_action = [
            "⏹" => 'stop_action',
            '◀' => $action
        ][$txt_backward];


        $i_buttons[] = [
            $txt_backward  => "$backward_action-$page-backward",
            $txt_forward => "$forward_action-$page-forward"
        ];

        return $i_buttons;
    }

    /**
     * @param string $text_connect
     * @param string|null $success
     * @param array $options
     * @return array|string
     */
    public static function iButtonsConnectPagesTest(string $text_connect, ?string $success = 'success_1', array $options = []): array|string
    {
        $button = self::getTranslate($text_connect, null, 'button');

        $i_buttons = [];

        $page = $options['page'] ?? 0;
        $page_limit = $options['page_limit'] ?? 10;


        $callback_key = $options['callback_key'];
        $callback_id_key = $options['callback_id_key'];
        $callback_param = ($options['callback_param'] ?? false)
            ? "-" . $options['callback_param']
            : null;

        $data_array = $options['data_array'];
        $button_text = $options['button_text'] ?? 'button_text';

        $next_page = array_slice($data_array, $page_limit, 1, true) == [] ? null : $page + 1;
        $previous_page = $page > 0 ? $page - 1 : $page;


        for ($i = 0, $count = count($data_array); $i < $count; $i++) {


            if ($callback_id_key && $callback_key) {
                $key = $callback_key . $callback_param .
                    '-' . ($data_array[$i][$callback_id_key] ?? null);
            } else {
                $key = 'empty_button';
            }

            if (is_array($button_text)) {
                $replaced_array = [];

                foreach ($button_text as $value) {
                    $replaced_array[$data_array[$i][$value]] = $key;
                }

                $i_buttons += self::getTranslate($button, $success, 'i_button_text', $replaced_array);
            }

        }

        //MainLogger::write(print_r($next_page ?? null, 1), 'buttons').die();


        $i_buttons[$i][$page ? '⬅️' : '⏹'] = $page
            ? $text_connect . '-' . $previous_page . $callback_param
            : 'empty_button';

        $i_buttons[$i][$next_page ? '➡️' : '⏹ '] = $next_page
            ? $text_connect . '-' . $next_page . $callback_param
            : 'empty_button';

        return $i_buttons;
    }

    /**
     * @param string $text_connect
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function rTextConnect(string $text_connect, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        $button = self::getTranslate($text_connect, null, 'button', $replace_array);

        return self::getTranslate($button, $success, 'r_text', $replace_array);
    }

    /**
     * @param string $text_connect
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function rButtonsConnect(string $text_connect, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        $button = self::getTranslate($text_connect, null, 'button', $replace_array);

        return self::getTranslate($button, $success, 'r_buttons', $replace_array);
    }

    /**
     * @param string $text
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function iText(string $text, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        return self::getTranslate($text, $success, 'i_text', $replace_array);
    }

    /**
     * @param string $text
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function iButtons(string $text, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        return self::getTranslate($text, $success, 'i_buttons', $replace_array);
    }

    /**
     * @param string $text
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function rText(string $text, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        return self::getTranslate($text, $success, 'r_text', $replace_array);
    }

    /**
     * @param string $text
     * @param $replace_array
     * @param string|null $success
     * @return array|string|null
     */
    public static function rButtons(string $text, $replace_array = null, ?string $success = 'success_1'): array|string|null
    {
        return self::getTranslate($text, $success, 'r_buttons', $replace_array);
    }

    /**
     * @param string $text
     * @param string|null $success
     * @param string|null $localization_key
     * @param $replace_array
     * @return mixed
     */
    protected static function getTranslate(string $text, ?string $success = 'success_1', string $localization_key = null, $replace_array = null): mixed
    {
        if ($success) {
            $translated = self::$translate[$text][$success][$localization_key] ?? null;
        } else {
            $translated = self::$translate[$text][$localization_key] ?? null;
        }

        return self::replaceValues($translated, $replace_array);
    }
}