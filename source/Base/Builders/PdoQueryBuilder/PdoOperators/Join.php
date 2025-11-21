<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators;

use Exception;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Operators;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;

/**
 * Class Join represents a JOIN clause in a SQL query.
 */
class Join extends Operators
{
    /**
     * @var string The type of JOIN (INNER, LEFT, RIGHT, etc.).
     */
    protected string $join_type;

    protected ?string $table_name = null;

    /**
     * @var On|null|mixed An On object representing the ON conditions for the JOIN.
     */
    public mixed $on_object = null;

    /**
     * Join constructor.
     *
     * @param string $table_name The name of the table to join.
     * @param string $type The type of JOIN (INNER, LEFT, RIGHT, etc.).
     * @param array $conditions The conditions for the ON clause, defaults to an empty array.
     * @param string $operand The operand for multiple conditions (AND, OR), defaults to 'AND'.
     * @throws Exception
     */
    public function __construct(string $table_name, string $type, array $conditions = [], string $operand = 'AND')
    {
        $this->table_name = $table_name;
        $this->join_type = $type;

        if ($conditions != []) {
            $this->addAnotherCondition($conditions, $operand);
        }
    }

    /**
     * Adds another condition to the ON clause.
     *
     * @param array $conditions The conditions to add.
     * @param string $operand The operand for multiple conditions (AND, OR), defaults to 'AND'.
     * @throws Exception
     */
    public function addAnotherCondition(array $conditions = [], string $operand = 'AND'): void
    {
        if (!$this->on_object) {
            $this->on_object = new On($conditions, $operand);
        } else {
            $this->on_object->addAnotherOne($conditions, $operand);
        }
    }

    /**
     * Generates a query string for the JOIN clause.
     *
     * @param string $alias_name The alias name for the table.
     * @return string The generated query string for the JOIN clause.
     */
    public function getQuery(string $alias_name): string
    {
        $result = Regex::joinQuery($this->table_name, $this->join_type);

        if ($this->on_object) {
            $result .= $this->on_object->getQuery($alias_name);
        }

        return $result;
    }
}
