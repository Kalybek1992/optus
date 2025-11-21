<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface GlobalVariableInterface
 * Defines the contract for managing global variables within the application.
 */
interface GlobalVariableInterface
{
    /**
     * Check if a key belongs to the global variables.
     *
     * @param string $key
     * @return bool
     */
    public static function belong(string $key): bool;

    /**
     * Set a global variable.
     *
     * @param string $key
     * @param mixed $value
     * @param ValidatorInterface|null $validator
     */
    public static function set(string $key, mixed $value, ValidatorInterface $validator = null);

    /**
     * Set multiple global variables.
     *
     * @param array $array
     * @param ValidatorInterface|null $validator
     */
    public static function mSet(array $array, ValidatorInterface $validator = null);

    /**
     * Set a global variable if it does not exist.
     *
     * @param string $key
     * @param mixed $value
     * @param ValidatorInterface|null $validator
     */
    public static function setNx(string $key, mixed $value, ValidatorInterface $validator = null);

    /**
     * Get a global variable.
     *
     * @param string $key
     * @return mixed
     */
    public static function get(string $key): mixed;

    /**
     * Get multiple global variables.
     *
     * @param array|null $keys
     * @return array
     */
    public static function mGet(array $keys = null): array;

    /**
     * Get the class name.
     *
     * @return string
     */
    public static function getClass(): string;
}
