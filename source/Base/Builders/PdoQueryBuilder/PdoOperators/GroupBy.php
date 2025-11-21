<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;

/**
 * Class GroupBy is used to create a GROUP BY clause for SQL queries.
 */
class GroupBy extends Operators
{
    /**
     * @var string The condition for grouping.
     */
    protected string $condition;

    /**
     * GroupBy constructor.
     *
     * @param string $condition The condition for grouping.
     */
    public function __construct(string $condition)
    {
        $this->condition = $condition;
    }

    /**
     * Method to generate a query string for the GROUP BY clause.
     *
     * @param string|null $alias_name Alias name for the main table, defaults to null.
     * @return string Query string for the GROUP BY clause.
     */
    public function getQuery(string $alias_name = null): string
    {
        $explode = explode(',', $this->condition);

        foreach ($explode as $key => $value) {
            if (!str_contains($value, '.')) {
                $explode[$key] = Regex::groupByFull($alias_name, $value);
            }
        }

        return ' GROUP BY ' . implode(',', $explode);
    }
}
