<?php

namespace Source\Base\Core;

use Source\Base\Core\Interfaces\ControllerInterface;

/**
 * The main abstract Controller class that implements ControllerInterface.
 */
abstract class Controller implements ControllerInterface
{
    public function __construct()
    {
    }

    public static function getClass(string $class, string $name_space = 'Source\\Project\\Controllers\\'): string
    {
        return $name_space . (str_contains($class, 'Controller') ? $class : $class . 'Controller');
    }

    public function __call(string $name = null, array $arguments = null)
    {
        return $this->default();
    }

}