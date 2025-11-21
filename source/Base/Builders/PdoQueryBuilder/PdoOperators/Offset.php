<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;

/**
 * Class Offset represents an OFFSET clause in a SQL query.
 */
class Offset extends Operators
{
    /**
     * @var int The offset value for the SQL query.
     */
    protected int $offset;

    /**
     * Offset constructor.
     *
     * @param int $offset The offset value.
     */
    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * Generates a query string for the OFFSET clause.
     *
     * @param string $alias_name The alias name for the table, not used in this method.
     * @return string The generated query string for the OFFSET clause.
     */
    public function getQuery(string $alias_name): string
    {
        return ' OFFSET ' . $this->offset;
    }
}
