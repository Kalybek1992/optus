<?php
namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;

class From extends Operators
{
    /**
     * @var array
     */
    protected array $additional_tables = [];

    /**
     * Constructor for the form_query class.
     *
     * @param array $additional_tables Array of additional tables, defaults to an empty array
     */
    public function __construct(array $additional_tables = [])
    {
        $this->additional_tables = $additional_tables;
    }

    /**
     * Method to generate a query string for the FROM clause.
     *
     * @param string $alias_name Alias name for the main table, defaults to null
     * @return string Query string for the FROM clause
     */
    public function getQuery(string $alias_name): string
    {
        return ' FROM ' . $alias_name . implode(',', $this->additional_tables);
    }
}
