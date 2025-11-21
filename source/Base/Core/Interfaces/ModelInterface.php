<?php

namespace Source\Base\Core\Interfaces;

use Source\Base\Builders\PdoQueryBuilder\Interfaces\PdoQueryBuilderInterface;

/**
 * The ModelInterface defines the contract for the models in your application. *
 */
interface ModelInterface
{
    /**
     * Method for setting model column values.
     *
     * @param array $options Associative array of model column values.
     */
    public function setColumns(array $options = []): void;

    /**
     * Method for getting the table name for the model.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Static method to get the table name for the model.
     *
     * @return string
     */
    public static function getTableStatic(): string;
}
