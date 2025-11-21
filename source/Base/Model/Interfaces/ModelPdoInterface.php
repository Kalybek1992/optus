<?php

namespace Source\Base\Model\Interfaces;

use Exception;
use Source\Base\Builders\PdoQueryBuilder\Interfaces\PdoQueryBuilderInterface;

interface ModelPdoInterface
{
    /**
     * Method for creating a new PdoQueryBuilder object for building database queries.
     *
     * @return PdoQueryBuilderInterface
     */
    public static function newQueryBuilder(): PdoQueryBuilderInterface;

    /**
     * @param array $what_select
     * @param array $where_select
     * @param bool $is_first
     * @param bool $is_array
     * @return mixed
     */
    public static function select(array $what_select, array $where_select, bool $is_first = true, bool $is_array = true): mixed;

    /**
     * @param array $what_update
     * @param array $where_update
     * @param int $count
     * @return array|false|int
     * @throws Exception
     */
    public static function update(array $what_update, array $where_update, int $count = 1): false|int|array;

    /**
     * @param array $what_insert
     * @return mixed
     * @throws Exception
     */
    public static function insert(array $what_insert): mixed;

    /**
     * @param array $what_delete
     * @param int $count
     * @return mixed
     */
    public static function delete(array $what_delete, int $count = 1): mixed;
}