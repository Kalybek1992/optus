<?php
namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;

class OrderBy extends Operators
{
    protected string $by;
    protected string $condition;

    public function __construct(string $condition, string $by = 'ASC')
    {
        $this->condition = $condition;
        $this->by = strtoupper($by);
    }

    /**
     * @param string|null $alias_name
     * @return string
     */
    public function getQuery(string $alias_name = null): string
    {
        return ' ORDER BY ' . $this->condition . ' ' . $this->by;
    }
}
