<?php

namespace Source\Base\Core;

use Source\Base\Core\Exceptions\MiddlewareException;
use Source\Base\Core\Interfaces\MiddlewareInterface;
use Source\Base\Core\Interfaces\RulesInterface;

/**
 * Class Rules
 * Provides the base structure for rule management.
 *
 * @package Source\Base\Core
 */
abstract class Rules implements RulesInterface
{
    /**
     * Container for rule errors.
     *
     * @var array
     */
    public array $rule_errors = [];
    /**
     * Creates an instance of the class.
     *
     * @return static
     */
    public static function create(): self
    {
        return new static();
    }

    /**
     * Magic method for accessing undefined properties.
     *
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
        $this->rule_errors[] = $name;

        return null;
    }

    /**
     * Retrieves the last occurred error.
     *
     * @return string
     */
    public function getLastError(): string
    {
        return array_pop($this->rule_errors);
    }
}
