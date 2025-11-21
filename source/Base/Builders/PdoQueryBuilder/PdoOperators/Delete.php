<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;

class Delete extends Operators
{
    /**
     * Method to generate a query string for the DELETE operation.
     *
     * @param string|null $alias_name Alias for the table, defaults to null
     */
    public function getQuery(?string $alias_name): string
    {
        return Regex::deleteQuery($alias_name);
    }
}
