<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface RulesInterface
 * Provides the necessary contract for managing rules.
 *
 * @package Source\Base\Interfaces
 */
interface RulesInterface
{
    /**
     * Factory method to create an instance of the class.
     *
     * @return self
     */
    public static function create(): self;

    /**
     * Magic method for accessing undefined properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name);

    /**
     * Retrieves the last occurred error.
     *
     * @return string
     */
    public function getLastError(): string;
}
