<?php

namespace Source\Project\Viewer;

use Exception;
use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Exceptions\ResponseException;

class ApiViewer
{
    /**
     * @var string|null
     */
    public static ?string $api_version = '1.0';

    /**
     * @desc versions api
     */
    public const array API_VERSIONS = [
        '1.0'
    ];

    /**
     * @desc type versions
     */
    public const array TYPE_VERSIONS = [
        'json'
    ];

    public static ?object $json_defined_answer = null;

    /**
     * @param string|null $api_version
     * @param string $lang
     * @throws ResponseException
     */
    public static function init(?string $api_version = '1.0', string $lang = 'en'): void
    {
        try {
            if (!static::$json_defined_answer) {
                static::$api_version = in_array($api_version, static::API_VERSIONS)
                    ? $api_version
                    : static::API_VERSIONS[0];

                static::$json_defined_answer = json_decode(file_get_contents(
                        Path::RESOURCES_DIR .
                        "api/$lang/" .
                        ceil(static::$api_version) . '.json')
                );
            }
        } catch (Exception $e) {
            throw new ResponseException( "No file /resources/api/$lang/" . ceil(static::$api_version) . '.json');
        }
    }


    /**
     * @param array|null $values
     * @return string
     */
    public static function getBody(?array $values): string
    {
        return json_encode($values);
    }

    /**
     * @param array|null $values
     * @return array
     */
    public static function getErrorBody(?array $values): array
    {
        return array_merge(['status' => 'error'], $values);
    }

    /**
     * @param array|null $values
     * @return array
     */
    public static function getOkBody(?array $values = []): array
    {
        return  array_merge(['status' => 'ok'], $values);
    }

    /**
     * @param string $key_name
     * @return array
     * @throws \Source\Base\Core\Exceptions\ResponseException
     */
    public static function setDefinedAnswer(string $key_name): array
    {
        static::init();
        return static::getErrorBody(
            ['value' =>  static::$json_defined_answer->$key_name ??  static::$json_defined_answer->default]
        );
    }

}