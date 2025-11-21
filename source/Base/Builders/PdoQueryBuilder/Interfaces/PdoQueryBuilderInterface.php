<?php

namespace Source\Base\Builders\PdoQueryBuilder\Interfaces;

use Source\Base\Core\Interfaces\BuilderInterface;

/**
 * Interface PdoQueryBuilderInterface
 *
 * This interface defines the contract for the PdoQueryBuilder.
 *
 * @package Source\Builders\Interfaces
 */
interface PdoQueryBuilderInterface extends BuilderInterface
{
    /**
     * Method to set the table name.
     *
     * @param string $table_name The name of the table.
     */
    public function setTable(string $table_name): void;

    /**
     * Method to set the SELECT statement of the query.
     *
     * @param array $columns The columns to select.
     */
    public function select(array $columns): PdoQueryBuilderInterface;

    /**
     * Method to set the FROM clause of the query.
     *
     * @param string $table_name The table name.
     */
    public function from(string $table_name): PdoQueryBuilderInterface;

    /**
     * Method to set the WHERE clause of the query.
     *
     * @param array $conditions The conditions for the WHERE clause.
     */
    public function where(array $conditions): PdoQueryBuilderInterface;

    /**
     * Method to set the JOIN clause of the query.
     *
     * @param string $table The table name to join.
     * @param string $type The type of join.
     */
    public function join(string $table, string $type): PdoQueryBuilderInterface;

    /**
     * Method to set the GROUP BY clause of the query.
     *
     * @param string $condition The condition for the GROUP BY clause.
     */
    public function groupBy(string $condition): PdoQueryBuilderInterface;

    /**
     * Method to set the ORDER BY clause of the query.
     *
     * @param string $condition The condition for the ORDER BY clause.
     * @param string $by The order direction (ASC or DESC).
     */
    public function orderBy(string $condition, string $by): PdoQueryBuilderInterface;

    /**
     * Method to set the LIMIT clause of the query.
     *
     * @param string $limit The limit value.
     */
    public function limit(string $limit): PdoQueryBuilderInterface;

    /**
     * Method to set the OFFSET clause of the query.
     *
     * @param int $offset The offset value.
     */
    public function offset(int $offset): PdoQueryBuilderInterface;

    /**
     * Method to set the INSERT INTO clause of the query.
     *
     * @param array $conditions The conditions for the INSERT INTO clause.
     */
    public function insert(array $conditions): PdoQueryBuilderInterface;

    /**
     * Method to set the UPDATE clause of the query.
     *
     * @param array $conditions The conditions for the UPDATE clause.
     */
    public function update(array $conditions): PdoQueryBuilderInterface;

    /**
     * Method to set the DELETE clause of the query.
     *
     * @param string $table_name The name of the table.
     */
    public function delete(string $table_name): PdoQueryBuilderInterface;

    /**
     * Method to get the SQL query string.
     *
     * @return string The SQL query string.
     */
    public function build(): string;
}