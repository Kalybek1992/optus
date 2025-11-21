<?php

namespace Source\Base\DataContainers;

use Source\Base\Core\Interfaces\DataContainerInterface;

class DataContainerStatic implements DataContainerInterface
{
    private static array $data = [];

    public static function __callStatic(string $name, array $arguments)
    {
        $method = "static$name";

        return static::$method(...$arguments) ?? null;
    }


    public static function get($name): mixed
    {
        return static::$data[$name] ?? null;
    }

    public static function set($name, $mixed): void
    {
        static::$data[$name] = $mixed;
    }

    public static function all(): array
    {
        return static::$data;
    }

}