<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;

class Select extends Operators
{
    /**
     * @param array $conditions
     */
    public function __construct(array $conditions = [])
    {
        $this->conditions = ($conditions == [] ? ['*'] : $conditions);
    }

    /**
     * @param string|null $alias_name
     * @return string
     */
    public function getQuery(?string $alias_name = null): string
    {
        $conditions = [];

        foreach ($this->conditions as $condition) {
            $conditions[] = str_contains($condition, '.') ? $condition : Regex::fullCondition($condition, $alias_name);
        }

        return Regex::selectQuery($conditions, $alias_name);
    }
}
