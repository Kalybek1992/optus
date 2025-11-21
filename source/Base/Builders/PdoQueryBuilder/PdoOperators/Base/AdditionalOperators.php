<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base;

use Exception;

/**
 * Class AdditionalOperators
 * This class is designed to handle additional operators for SQL queries.
 * It extends the base Operators class to inherit its properties and methods.
 *
 * @package Source\Builders\PdoOperators\Base
 */
class AdditionalOperators extends Operators
{
    /**
     * @var array
     * An array to hold the operands used in the SQL query.
     */
    protected array $operand = [];

    /**
     * @var string
     * A string to hold the name of the regex function used for processing conditions.
     */
    protected string $regex_function;

    /**
     * @var array
     * An array to hold the conditions used in the SQL query.
     */
    protected array $conditions = [];

    /**
     * @var array
     * An array to hold the values associated with the conditions in the SQL query.
     */
    public array $condition_values = [];

    /**
     * AdditionalOperators constructor.
     * Initializes the object with the provided conditions and operand.
     *
     * @param array $conditions The conditions for the SQL query.
     * @param string $operand The operand for the SQL query.
     * @throws Exception Throws an exception if an error occurs.
     */
    public function __construct(array $conditions, string $operand = 'AND')
    {
        $this->addAnotherOne($conditions, $operand);
    }

    /**
     * Adds another set of conditions to the SQL query.
     *
     * @param array $conditions The conditions to add.
     * @param string $operand The operand to use with the conditions.
     * @return $this Returns the current object for method chaining.
     * @throws Exception Throws an exception if an error occurs.
     */
    public function addAnotherOne(array $conditions, string $operand = 'AND') : self
    {
        $this->operand[] = $operand;
        $this->addConditions($conditions);
        return $this;
    }

    /**
     * Adds the provided conditions to the conditions array.
     *
     * @param array $conditions The conditions to add.
     * @throws Exception Throws an exception if the conditions array is empty.
     */
    protected function addConditions(array $conditions): void
    {
        if ($conditions == []) {
            throw new Exception('Bad conditions array');
        }
        $this->conditions[] = $conditions;
    }

    /**
     * Generates the SQL query string based on the provided alias name.
     *
     * @param string $alias_name The alias name to use in the SQL query.
     * @return string Returns the generated SQL query string or null if an error occurs.
     */
    public function getQuery(string $alias_name): string
    {
        $result_conditions = [];

        foreach ($this->conditions as $value) {
            $conditions = [];
            foreach ($value as $key_condition => $condition) {
                if (str_contains($key_condition, '?')) {
                    $conditions[] = Regex::fullCondition($key_condition, $alias_name);
                    $this->condition_values[] = $condition;
                } else {
                    $conditions[] = Regex::fullCondition($condition, $alias_name);
                }
            }
            $result_conditions[] = $conditions;
        }

        $function = static::getClass() . 'Query';

        return Regex::$function($result_conditions, $this->operand);
    }
}
