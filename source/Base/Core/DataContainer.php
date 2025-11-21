<?php

namespace Source\Base\Core;

use Source\Base\Core\Interfaces\DataContainerInterface;

/**
 * Class DataContainer
 *
 * Represents a container for storing and retrieving data.
 * @property mixed|null $token
 */
class DataContainer implements DataContainerInterface
{
    private static array $data = [];

    public function __set(string $key, $value): void
    {
        static::set($key, $value);
    }

    public function __get(string $key): mixed
    {
        return static::get($key) ?? null;
    }

    public static function get($name): mixed
    {
        return static::$data[$name] ?? null;
    }

    public static function set($name, $mixed): void
    {
        static::$data[$name] = $mixed;
    }

    public function all(): array {
        return static::$data;
    }
}