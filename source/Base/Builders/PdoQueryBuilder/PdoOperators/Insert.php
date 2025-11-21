<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;

/**
 * Class Insert is used to create an INSERT INTO clause for SQL queries.
 */
class Insert extends Operators
{
    /**
     * @var array
     */
    protected array $conditions;

    /**
     * Insert constructor.
     *
     * @param array $conditions The conditions for inserting.
     */
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Method to generate a query string for the INSERT INTO clause.
     *
     * @param string|null $alias_name Alias name for the table, defaults to null.
     * @return string Query string for the INSERT INTO clause.
     */
    public function getQuery(?string $alias_name = null): string
    {
        return Regex::insertQuery($this->conditions, $alias_name);
    }
}
