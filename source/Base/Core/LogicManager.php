<?php

namespace Source\Base\Core;

use Source\Base\Core\Interfaces\LogicManagerInterface;

abstract class LogicManager implements LogicManagerInterface
{
    public array $variables = [];

    public ?string $error = null;

    public ?int $time = null;

    public function __construct()
    {
        $this->time = time();
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws \Exception
     */
    public static function __callstatic(string $name, array $arguments)
    {
        throw new \Exception("Bad get method <b> $name </b> in " . static::class);
    }



    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name): mixed
    {
        return $this->variables[$name] ?? null;
    }
}

