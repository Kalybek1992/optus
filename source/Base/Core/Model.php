<?php

namespace Source\Base\Core;

use Source\Base\Builders\PdoQueryBuilder\PdoQueryBuilder;
use Source\Base\Core\Interfaces\ModelInterface;

/**
 * Abstract class Model that implements the ModelInterface interface.
 * Provides basic functionality for working with models.
 *
 */
abstract class Model implements ModelInterface
{
    /**
     * @var string|null Table name for the model
     */
    public ?string $table = null;

    /**
     * @var string|null Static table name for the model
     */
    public static ?string $static_table = null;

    /**
     * Class constructor. Sets the table name and initial values of the model columns.
     *
     * @param array $options Associative array of initial model column values.
     */
    public function __construct(array $options = [])
    {
        $this->getTable();
        $this->setColumns($options);
    }

    /**
     * Sets the values of the model columns.
     *
     * @param array $options Associative array of model column values.
     */
    public function setColumns(array $options = []): void
    {
        if ($options != []) {
            foreach ($options as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Gets the table name for the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        if (!$this->table) {
            $this->table = static::getTableStatic();
        }
        return $this->table;
    }


    /**
     * Gets the static table name for the model.
     *
     * @return string
     */
    public static function getTableStatic(): string
    {
        if (static::$static_table) {
            return static::$static_table;
        }

        $explodedClass = explode('\\', static::class);
        return strtolower($explodedClass[count($explodedClass)-1]) . 's';
    }
}
