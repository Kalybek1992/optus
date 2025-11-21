<?php

namespace Source\Base\Builders\PdoQueryBuilder;

use Exception;
use Source\Base\Builders\PdoQueryBuilder\Interfaces\PdoQueryBuilderInterface;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\{GroupBy};
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\{Operators};
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base\Regex;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Delete;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\From;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Insert;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Join;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Limit;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Offset;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\OrderBy;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Select;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Update;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Where;
use Source\Base\Core\{Builder};
use Source\Base\Core\Logger;

/**
 * Class PdoQueryBuilder
 *
 * This class is a builder for constructing PDO queries in PHP.
 * It provides methods for constructing different parts of a query, such as SELECT, INSERT, UPDATE, DELETE, etc.
 *
 * Usage:
 *
 * // Create a new instance of the builder
 * $builder = new PdoQueryBuilder($table, $class);
 *
 * // Set transaction isolation level
 * $builder->transactionMode($mode);
 *
 * // Start a new transaction
 * $builder->transactionStart();
 *
 * // Add a SELECT operation
 * $builder->select($columns);
 *
 * // Set table name for the query
 * $builder->from($table_name);
 *
 * // Add a WHERE condition
 * $builder->where($conditions, $operand);
 *
 * // Add a JOIN operation
 * $builder->join($table, $type);
 *
 * // Add an ON condition for the JOIN operation
 * $builder->on($conditions, $operand);
 *
 * // Add a LIMIT clause to the query
 * $builder->limit($limit);
 *
 * // Add an ORDER BY clause to the query
 * $builder->orderBy($condition, $by);
 *
 * // Add a GROUP BY clause to the query
 * $builder->groupBy($condition);
 *
 * // Add an OFFSET clause to the query
 * $builder->offset($offset);
 *
 * // Build the query
 * $query = $builder->build();
 *
 * // Commit the transaction
 * $builder->transactionCommit();
 *
 * // Execute the query using PDO
 * $stmt = $pdo->prepare($query);
 * $stmt->execute();
 *
 * Represents a query builder for constructing PDO queries.
 */
class PdoQueryBuilder extends Builder implements PdoQueryBuilderInterface
{
    /**
     * @var string|null
     */
    protected ?string $table_name = null;
    /**
     * @var string|null
     */
    protected ?string $table_alias = null;
    /**
     * @var array
     */
    protected array $operators = [];
    /**
     * @var array
     */
    protected array $condition_values = [];
    /**
     * @var string|null
     */
    protected ?string $main_class = null;

    /**
     * @var array
     */
    protected array $query = [];

    /**
     * @var string
     */
    protected string $end_query = '';

    /**
     * PdoQueryBuilder constructor.
     * @param string|null $table
     * @param string|null $class
     */
    public function __construct(?string $table, ?string $class)
    {
        $this->main_class = $class;
        $this->setTable($table);
        $this->table_alias = $table;
    }

    /**
     * Sets the transaction isolation mode for the database connection.
     *
     * @param string $mode The transaction isolation mode to be set. Defaults to 'READ UNCOMMITTED'.
     * @return $this The current instance of PdoQueryBuilder.
     */
    public function transactionMode(string $mode = 'READ UNCOMMITTED'): PdoQueryBuilder
    {
        $this->query[] = 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $mode . ";\n";

        return $this;
    }

    /**
     * Starts a new database transaction.
     *
     * This method appends the "START TRANSACTION;" statement to the query array.
     *
     * @return PdoQueryBuilder Returns the instance of the PdoQueryBuilder class.
     */
    public function transactionStart(): PdoQueryBuilder
    {
        $this->query[] = "START TRANSACTION;\n";

        return $this;
    }

    /**
     * Commits the current database transaction.
     *
     * This method sets the end query to "\nCOMMIT;". The end query will be executed during query execution.
     *
     * @return PdoQueryBuilder Returns the instance of the PdoQueryBuilder class.
     */
    public function transactionCommit(): PdoQueryBuilder
    {
        $this->end_query = "\nCOMMIT;";

        return $this;
    }

    /**
     * @param array $columns
     * @return self
     */
    public function select(array $columns = []): self
    {

        $this->operators[$this->operationName(Select::class)] = new Select($columns);

        if ($this->table_name != null) {
            $this->from($this->table_name);
        }

        return $this;
    }

    /**
     * Inserts records into a database table.
     *
     * This method adds an Insert object to the operators array and sets the table name if provided.
     * The Insert object holds the conditions for the insert operation.
     *
     * @param array|null $conditions (optional) The conditions for the insert operation.
     * @param string|null $table_name (optional) The name of the table to insert records into.
     * @return self Returns the instance of the current class.
     */
    public function insert(array $conditions = null, string $table_name = null): self
    {
        if ($table_name != null) {
            $this->setTable($table_name);
        }

        $this->operators[$this->operationName(Insert::class)] = new Insert($conditions);

        return $this;
    }

    /**
     * Sets up a delete operation on the specified table.
     *
     * If a table name is provided, it sets the table on which the delete operation will be performed.
     *
     * @param string|null $table_name The name of the table on which the delete operation will be performed. Defaults to null.
     *
     * @return self Returns the instance of the class.
     */
    public function delete(string $table_name = null): self
    {
        if ($table_name != null) {
            $this->setTable($table_name);
        }

        $this->operators[$this->operationName(Delete::class)] = new Delete();

        return $this;
    }

    /**
     * Adds an update operation to the query builder.
     *
     * This method creates a new instance of the Update class with the given conditions
     * and adds it to the operators array with the operation name.
     *
     * @param array $conditions The update conditions.
     * @return self Returns an instance of the current class.
     */
    public function update(array $conditions): self
    {

        $this->operators[$this->operationName(Update::class)] = new Update($conditions);

        return $this;
    }

    /**
     * Sets the "FROM" clause of the SQL query to the specified table.
     *
     * This method sets the "FROM" clause of the SQL query to the given table name and creates a new instance of the From class.
     * It also updates the table alias and returns the current object for method chaining.
     *
     * @param string $table_name The name of the table to set in the "FROM" clause.
     * @return self Returns the instance of the current class.
     */
    public function from(string $table_name): self
    {
        $this->setTable($table_name);
        $link = &$this->operators[$this->operationName(From::class)];

        $link = new From();
        $this->table_alias = $table_name;

        return $this;
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * This method creates and adds a new instance of the Where class to the operators array.
     * If an instance of the Where class already exists, it will append the new conditions to it.
     *
     * @param array $conditions An array of conditions for the WHERE clause.
     * @param string $operand (optional) The operand to use for combining multiple conditions. Defaults to 'AND'.
     * @return self Returns the instance of the class.
     * @throws Exception
     */
    public function where(array $conditions, string $operand = 'AND'): self
    {
        if (isset($this->operators[$this->operationName(Where::class)])) {
            $this->operators[$this->operationName(Where::class)]->addAnotherOne($conditions, $operand);
        } else {
            $this->operators[$this->operationName(Where::class)] = new Where($conditions, $operand);

        }

        return $this;
    }


    /**
     * Performs a left join operation on the specified table.
     *
     * This method internally calls the join method with the specified table and 'left' as the join type.
     *
     * @param string $table The name of the table to perform the left join on.
     * @return self Returns the instance of the current class.
     * @throws Exception
     */
    public function leftJoin(string $table): self
    {
        return $this->join($table, 'left');
    }

    /**
     * Performs a right join on the specified table.
     *
     * This method delegates the join operation to the `join` method with the join type set to "right".
     *
     * @param string $table The name of the table to perform the right join on.
     * @return self Returns the instance of the class.
     * @throws Exception
     */
    public function rightJoin(string $table): self
    {
        return $this->join($table, 'right');
    }

    /**
     * Adds an inner join clause to the query.
     *
     * This method calls the join() method to add an inner join clause to the query.
     *
     * @param string $table The table name to join.
     * @return self Returns the instance of the class.
     * @throws Exception
     */
    public function innerJoin(string $table): self
    {
        return $this->join($table);
    }

    /**
     * Joins a table to the query.
     *
     * This method adds a join statement to the query by creating a new Join object and
     * appending it to the operators array with the given table and type.
     *
     * @param string $table The name of the table to join.
     * @param string $type The type of join to perform. Defaults to 'INNER'.
     * @return self Returns the instance of the current class.
     * @throws Exception
     */
    public function join(string $table, string $type = 'INNER'): self
    {
        $this->operators[$this->operationName(Join::class)][] = new Join($table, $type);

        return $this;
    }

    /**
     * Adds additional conditions to the join operation.
     *
     * This method adds the given conditions to the last join operation in the operators array.
     *
     * @param array $conditions The conditions to be added.
     * @param string $operand The operand used for joining the conditions. Defaults to 'AND'.
     * @return self Returns the instance of the current class.
     */
    public function on(array $conditions, string $operand = 'AND'): self
    {
        $link = &$this->operators[$this->operationName(Join::class)];
        $link[count($link) - 1]->addAnotherCondition($conditions, $operand);

        return $this;
    }

    /**
     * @var string|null
     */
    public ?string $duplicate_string = null;

    public function onDuplicate(string $method, array $array): self
    {
        $this->duplicate_string = " ON DUPLICATE KEY " . $method . ' ' . implode(" , ", $array) . " ";
        return $this;
    }

    /**
     * Sets the limit for the query.
     *
     * This method sets the limit for the query by assigning a new instance of the Limit class to the operators array.
     *
     * @param string $limit The limit value for the query.
     * @return self Returns the instance of the current class.
     */
    public function limit(string $limit): self
    {
        $this->operators[$this->operationName(Limit::class)] = new Limit($limit);
        return $this;
    }

    /**
     * Sets the ORDER BY condition for the query.
     *
     * This method creates a new OrderBy instance containing the given condition and order direction, and adds it to the operators array.
     *
     * @param string $condition The condition to be used in the ORDER BY clause.
     * @param string $by The order direction (optional). Defaults to 'ASC'.
     * @return self Returns the instance of the class.
     */
    public function orderBy(string $condition, string $by = 'ASC'): self
    {
        $this->operators[$this->operationName(OrderBy::class)] = new OrderBy($condition, $by);
        return $this;
    }

    /**
     * Adds a "GROUP BY" clause to the query.
     *
     * This method appends a new GroupBy object to the operators array
     * which represents the "GROUP BY" clause in the SQL query.
     *
     * @param string $condition The condition to group the query by.
     * @return self Returns the instance of the current class.
     */
    public function groupBy(string $condition): self
    {
        $this->operators[$this->operationName(GroupBy::class)] = new GroupBy($condition);
        return $this;
    }

    /**
     * Sets the offset for the query result.
     *
     * This method sets the offset of the query result by creating a new instance of the Offset class
     * and assigning it to the operators array using the operation name as the key.
     *
     * @param int $offset The offset value to set.
     * @return self Returns the instance of the current class.
     */
    public function offset(int $offset): self
    {
        $this->operators[$this->operationName(Offset::class)] = new Offset($offset);
        return $this;
    }

    /**
     * Builds the final query string.
     *
     * This method iterates through the operators array and concatenates the query
     * generated by each operator. It also merges the condition values from each operator
     * into the condition_values array.
     *
     * @return string The final query string.
     */
    public function build(): string
    {
        $result = '';

        foreach ($this->operators as $operator_object) {
            if (is_array($operator_object)) {
                foreach ($operator_object as $value) {
                    $result .= $value->getQuery($this->table_alias);

                    if (!empty($value->condition_values)) {
                        $this->condition_values += $value->condition_values;
                    } elseif (!empty($value->on_object->condition_values)) {
                        $this->condition_values += $value->on_object->condition_values;
                    }
                }
            } else {
                $result .= $operator_object->getQuery($this->table_alias);

                if (isset($operator_object->condition_values)) {
                    $this->condition_values = array_merge(
                        $this->condition_values,
                        $operator_object->condition_values
                    );
                } elseif (isset($operator_object->on_object->condition_values)) {
                    $this->condition_values = array_merge(
                        $this->condition_values,
                        $operator_object->on_object->condition_values
                    );
                }
            }
        }

        return ($this->query == [] ? '' : implode(' ', $this->query)) .
            Regex::delSpace($result) . ($this->duplicate_string ?: '') .  ' ;' . $this->end_query;
    }

    /**
     * @param string $class
     * @return string
     */
    private function operationName(string $class): string
    {
        $explode = explode('\\', $class);

        return strtoupper(end($explode));
    }

    /**
     * @param string $table_name
     */
    public function setTable(string $table_name): void
    {
        $this->table_name = $table_name;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function getOperator(string $class): bool
    {
        foreach ($this->operators as $operator) {
            if (get_class($operator) === $class) {
                return true;
            }
        }

        return false;
    }

    public function getConditions(): array
    {
        return $this->condition_values;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->main_class;
    }
}
