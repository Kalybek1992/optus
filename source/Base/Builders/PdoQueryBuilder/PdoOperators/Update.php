<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;

/**
 * Class Update
 * This class is responsible for generating SQL UPDATE query string based on given conditions.
 */
class Update extends Operators
{
    /**
     * @var array
     * Holds the values to be used in the SQL query.
     */
    protected array $condition_values = [];
    /**
     * Update constructor.
     * Initializes the conditions and table name for the Update query.
     *
     * @param array $conditions The conditions for the Update query.
     */
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Get Query
     * Generates and returns the SQL UPDATE query string.
     *
     * @param string|null $alias_name Optional alias for the table name.
     * @return string The generated SQL UPDATE query string.
     */
    public function getQuery(string $alias_name = null): string
    {
        $conditions = [];

        foreach ($this->conditions as $key_condition => $condition) {
            if (str_contains($key_condition, '?')) {
                $conditions[] = $key_condition;
                $this->condition_values[] = $condition;
            } else {
                $conditions[] = $condition;
            }
        }

        return Regex::updateQuery($conditions, $alias_name);
    }
}
