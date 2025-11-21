<?php

namespace Source\Base\Model;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use Source\Base\Builders\PdoQueryBuilder\PdoQueryBuilder;
use Source\Base\Core\Logger;
use Source\Base\Core\Model;
use Source\Project\Connectors\PdoConnector;

abstract class ModelPdo extends Model
{
    public array $variables = [];
    /**
     * Creates a new PdoQueryBuilder object for building database queries.
     *
     * @return PdoQueryBuilder
     */
    public static function newQueryBuilder(): PdoQueryBuilder
    {

        return new PdoQueryBuilder(static::getTableStatic(), static::class);
    }

    /**
     * Finds and returns a model from the data.
     *
     * @param array $what_select
     * @param array $where_select
     * @param bool $is_first
     * @param bool $is_array
     * @return mixed
     * @throws Exception
     */
    public static function select(array $what_select, array $where_select, bool $is_first = true, bool $is_array = false): mixed
    {
        //Logger::log("kali", 'user').die();

        $query_builder = static::newQueryBuilder()
            ->select($what_select)
            ->where($where_select);


        if ($is_first) {
            $query_builder->limit(1);
        }

        return PdoConnector::execute($query_builder, $is_array ? PDO::FETCH_ASSOC : PDO::FETCH_CLASS);
    }

    /**
     * @param array $what_update
     * @param array $where_update
     * @param int $count
     * @param $for_test
     * @return false|int|array
     * @throws \Exception
     */
    public static function update(array $what_update, array $where_update, int $count = 1, $for_test = false): false|int|array
    {

        $query_builder = static::newQueryBuilder()
            ->update($what_update)
            ->where($where_update)
            ->limit($count);


        return PdoConnector::execute($query_builder);
    }

    /**
     * @param array $what_insert
     * @return mixed
     * @throws Exception
     */
    public static function insert(array $what_insert): mixed
    {
        $query_builder = static::newQueryBuilder()
            ->insert($what_insert);

        return PdoConnector::execute($query_builder);
    }

    /**
     * @param array $what_delete
     * @param int $count
     * @return mixed
     * @throws Exception
     */
    public static function delete(array $what_delete, int $count = 1): mixed
    {
        $query_builder = static::newQueryBuilder()
            ->delete()
            ->where($what_delete)
            ->limit($count);

        return PdoConnector::execute($query_builder);
    }

    #[NoReturn] public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name): mixed
    {
        return $this->variables[$name] ?? null;
    }
}