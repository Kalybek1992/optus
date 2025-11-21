<?php

namespace Source\Base\Builders\PdoQueryBuilder\PdoOperators\Base;

use Source\Base\Core\Logger;

/**
 * Class Regex
 * This class contains methods and properties for constructing SQL query strings.
 *
 * @package Source\Builders\PdoOperators\Base
 */
class Regex
{
    /**
     * @var string Template for SELECT query.
     */
    protected static string $select_layout = " SELECT {{conditions}} ";

    /**
     * @var string Template for WHERE clause.
     */
    protected static string $where_layout = " WHERE {{conditions}} ";

    /**
     * @var string Template for ON clause.
     */
    protected static string $on_layout = " ON {{conditions}} ";

    /**
     * @var string Template for DELETE query.
     */
    protected static string $delete_layout = /** @lang text */
        ' DELETE FROM {{table}} ';

    /**
     * @var string Template for JOIN clause.
     */
    protected static string $join_layout = " {{type}} JOIN {{table}} ";

    /**
     * @var string Template for UPDATE query.
     */
    protected static string $update_layout = /** @lang text */
        " UPDATE {{table}} SET {{conditions}} ";

    /**
     * @var string Template for INSERT query.
     */
    protected static string $insert_layout = /** @lang text */
        " INSERT INTO {{table}} {{keys_conditions}} {{values}} ";

    /**
     * @var string Regex pattern to skip certain values during processing.
     */
    protected static string $skip_values_regex = '#\Aas\Z|\(|"|`|\'|\Ain\Z|\Ais\Z|BINARY|\+|-|\)|\d|,|`|IS|NOT|NULL#sui';
    protected static string $skip_all_values = '#"|\'#';

    /**
     * Method to fully qualify condition with table alias.
     *
     * @param string $condition Condition string.
     * @param string $column Column name.
     * @return string Condition string with table alias.
     */
    public static function fullCondition(string $condition, string $column): string
    {
        // This method processes the condition string and qualifies any column names with the table alias.

        /**
         * @desc split $condition for operand compare
         * @var array $split_result
         */
        $split_result = preg_split('/[=+?<!]/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        $result = '';


        foreach ($split_result as $split_element) {
            /**
             * @desc if numeric or isset full column
             */

            if (preg_match(static::$skip_values_regex, strtolower(trim($split_element[0])))) {
                continue;
            }

            if (preg_match(static::$skip_all_values, strtolower(trim($split_element[0])))) {
                break;
            }

            if (is_numeric($split_element[0]) || str_contains($split_element[0], '.')) {
                continue;
            }
            $result .= ' ' . substr_replace($condition, $column . '.' . $split_element[0], $split_element[1], mb_strlen($split_element[0]));
        }

        /**
         * @return $result new condition
         */
        return $result == '' ? $condition : $result;
    }

    /**
     * Method to create an UPDATE query string.
     *
     * @param array $conditions Array of conditions.
     * @param string $table_name Table name.
     * @return string|null The constructed query string.
     */
    public static function updateQuery(array $conditions, string $table_name): string|null
    {
        // This method processes the conditions array and creates an UPDATE query string.
        $result = '';
        //var_dump($conditions);//die;
        foreach ($conditions as $key => $value) {

            $split = preg_split(
                '/\s*<*>*=\s*/',
                $value,
                2,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE
            );



            if (is_numeric($key)) {
                if (!($split[1] ?? null)){
                    die;
                }

                $value = preg_replace("#" . $split[1][0] . "#",
                    "'" .  preg_replace(
                        "/(?<!\\\\)'/",
                        "\\'",
                        $split[1][0]
                    ) . "'",
                    $value,
                   1);


                if (preg_match("/^\s*(.+?)\s*=\s*'(<NULL>)'\s*$/", $value, $matches)) {
                    // Например: supplier_id = '<NULL>'
                    $result .= ($result === '' ? '' : ', ') . "{$matches[1]} = NULL";
                }else{
                    $result .= ($result == '' ? $value : ', ' . $value);
                }

                //Logger::log(print_r($result, true), 'file_arr');

            } else {
                $key = preg_replace("#" . $split[1][0] . "#",
                    "'" .  preg_replace(
                        "/(?<!\\\\)'/",
                        "\\'",
                        $split[1][0]
                    ) . "'",
                    $key,
                    1);

                $result .= ($result == '' ? $key : ', ' . $key);
            }
        }

        return preg_replace(['/{{table}}/', '/{{conditions}}/'], [$table_name, $result], self::$update_layout);
    }

    /**
     *  Method to create an DELETE query string.
     *
     * @param string|null $table
     * @return string|null
     */
    public static function DeleteQuery(?string $table): string|null
    {
        return preg_replace('/{{table}}/', $table, self::$delete_layout);
    }

    /**
     * Method to create an INSERT query string.
     *
     * @param array|null $conditions
     * @param string|null $table
     * @return string|null
     */
    public static function insertQuery(?array $conditions, ?string $table): string|null
    {
        // This method processes the conditions array and creates an INSERT query string.
        $keys_conditions = [];
        $insert_array = [];

        if ($conditions) {
            if (isset($conditions[0])) {
                $keys_conditions = array_keys($conditions[0]);

                foreach ($conditions as $condition) {
                    foreach ($condition as $key => $value) {
                        $condition[$key] = "'" . preg_replace(
                                "/(?<!\\\\)'/",
                                "\\'", $value) . "'";
                    }
                    $insert_array[] = "(" . implode(',', $condition) . ")";
                }
            } else {
                $keys_conditions = array_keys($conditions);
                $insert_array[] = '(\'' . implode('\',\'', $conditions) . '\')';
            }
        }

        return preg_replace(
            ['/{{keys_conditions}}/', '/{{table}}/', '/{{values}}/'],
            [
                ($insert_array == [] ? '' : '( ' . implode(', ', $keys_conditions) . ' ) '),
                $table,
                ($keys_conditions != []
                    ? ' VALUES ' . str_replace(
                        '$', '\\$',
                        implode(' , ',
                            $insert_array == []
                                ? $keys_conditions
                                : $insert_array)
                    ) . ' '
                    : '')
            ],
            self::$insert_layout
        );
    }

    /**
     * Method to create an SELECT query string.
     *
     * @param array $conditions
     * @param string $table
     * @return string|null
     */
    public static function selectQuery(array $conditions, string $table): string|null
    {
        // This method processes the conditions array and creates an SELECT query string.

        return preg_replace(['/{{conditions}}/', '/{{table}}/'], [implode(',', $conditions), $table], self::$select_layout);
    }

    /**
     * Method to create an ON query string.
     *
     * @param array $conditions
     * @param array $operand
     * @return string|null
     */
    public static function onQuery(array $conditions, array $operand): string|null
    {
        // This method processes the conditions array and creates an ON query string.
        return preg_replace('/{{conditions}}/', self::additionalQuery($conditions, $operand), self::$on_layout);
    }

    /**
     * Method to create an WHERE query string.
     *
     * @param array $conditions
     * @param array $operand
     * @return string|null
     */
    public static function whereQuery(array $conditions, array $operand): string|null
    {

        // This method processes the conditions array and creates an WHERE query string.
        return preg_replace('/{{conditions}}/', self::additionalQuery($conditions, $operand), self::$where_layout);
    }

    /**
     * Helper method to construct additional query string for WHERE or ON clause.
     *
     * @param array $conditions
     * @param array $operand
     * @return string|null
     */
    private static function additionalQuery(array $conditions, array $operand): ?string
    {
        $result = '';

        foreach ($conditions as $key => $value) {
            $result .= ($result != '' ? 'AND' : '') . ' (' . implode(' ' . $operand[$key] . ' ', $value) . ') ';
        }

        return $result;
    }

    /**
     * Method to create an JOIN query string.
     *
     * @param string $table
     * @param string $type
     * @return string|null
     */
    public static function joinQuery(string $table, string $type): string|null
    {
        // This method processes the conditions array and creates an JOIN query string.

        return preg_replace(['/{{type}}/', '/{{table}}/'], [strtoupper($type), $table], self::$join_layout);
    }

    /**
     * Method to create an GroupBY query string.
     *
     * @param string $table
     * @param string $condition
     * @return string
     */
    public static function groupByFull(string $table, string $condition): string
    {
        // This method processes the conditions array and creates an GroupBY query string.

        $explode = explode(' ', $condition);
        foreach ($explode as $key => $value) {
            if (!str_contains($value, '.')) {
                $explode[$key] = $table . '.' . $value;
            }
        }

        return implode(' ', $explode);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function delSpace(string $string): string
    {
        return preg_replace('/\s{2,}/', ' ', $string);
    }
}

