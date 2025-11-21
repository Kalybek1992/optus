<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base;

/**
 * Abstract Class Operators
 * This class serves as a base class for various SQL operators.
 * It defines a common method signature for generating SQL query strings.
 *
 * 
 * @package Source\Builders\PdoOperators\Base
 */
abstract class Operators
{
    /**
     * @var array
     * Holds the conditions to be used in the SQL query.
     */
    protected array $conditions = [];
    /**
     * Abstract method getQuery
     * All derived classes are required to implement this method.
     * This method is designed to return a SQL query string based on the provided alias name.
     *
     * @param string $alias_name The alias name to be used in the SQL query.
     * @return string The generated SQL query string.
     */
    public abstract function getQuery(string $alias_name): string;

    /**
     * Method getClass
     * This method extracts the class name from the fully qualified class name.
     * It is designed to help derived classes in generating SQL query strings based on class name.
     *
     * @return string|null The extracted class name or null if extraction fails.
     */
    protected static function getClass(): ?string
    {
        $explode_class = explode('\\', static::class);

        return lcfirst(end($explode_class));
    }
}
