<?php

namespace Source\Base\LogicManagers;

use Source\Base\Core\Interfaces\DataContainerInterface;
use Source\Base\Core\Interfaces\RequestInterface;
use Source\Base\Core\Logger;

class MiddlewareManager
{
    /**
     * @var string|null
     */
    protected static ?string $current_middleware = null;
    /**
     * @var string|null
     */
    public static ?string $error_variable = null;
    private static array $routes = [];

    public static function loadRoutes(string $path): void
    {
        $routes = include $path;

        self::$routes = $routes;
    }

    public static function getMiddlewares(string $method, string $url): array
    {
        return self::$routes[$method][$url]['middlewares'] ?? [];
    }

    public static function validateRequest(string $method, string $url, RequestInterface $request): bool
    {
        $rules = self::$routes[$method][$url]['validation'] ?? [];
        $rules_validation = self::$routes[$method][$url] ?? [];

        //Logger::log(print_r($rules, true), 'rules');
        //Logger::log(print_r($url, true), 'rules_validation');

        if ($rules == [] && $rules_validation == []) {
            return false;
        }

        foreach ($rules as $param => $rule) {
            if ($method == 'GET'){
                $value = $request->getQueryParam($param);
            } else {
                $value = $request->getParsedBodyParam($param);
            }

            if (isset($rule['required']) && $rule['required'] && !$value) {
                return false;
            }

            if (isset($rule['regex']) && $rule['regex'] && !preg_match($rule['regex'], $value)) {
                return false;
            }

            if (isset($rule['custom_logic']) && $rule['custom_logic'] && !call_user_func($rule['custom_logic'], $value)) {
                return false;
            }
        }

        return true;
    }

    public static function runMiddlewares(string $method, string $url): bool
    {
        $index = 0;
        $middlewares = self::getMiddlewares($method, $url);

        $next = function () use (&$index, &$next, &$current_middleware, $middlewares) {

            if ($middlewares[$index] ?? false) {
                self::$current_middleware = $middlewares[$index]::class;

                $middleware = $middlewares[$index];
                $index++;


                return $middleware->handle($next);
            }

            return true;
        };

        return $next();
    }

    /**
     * @param string $string
     * @return void
     */
    public static function errorSet(string $string): void
    {
        static::$error_variable = $string;
    }

    /**
     * @return string|null
     */
    public static function errorGet(): ?string
    {
        return static::$error_variable;
    }
}