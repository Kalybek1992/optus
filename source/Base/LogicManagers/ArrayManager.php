<?php

namespace Source\Base\LogicManagers;

/**
 * Class ArrayManager
 *
 * The ArrayManager class provides methods for manipulating arrays.
 */
class ArrayManager
{
    /**
     * Returns an array of values extracted from a multidimensional array using a specified key.
     *
     * @param array $services The multidimensional array from which to extract the values.
     * @param string $key The key to extract the values with.
     * @param mixed $value A reference to an empty variable that will store the extracted values.
     *
     * @return void
     */
    static function getKeyValuesArray(array $services, string $key, mixed &$value): void
    {
        foreach ($services as $value) {
            $value[] = $value[$key];
        }
    }

    /**
     * @param array $current_array
     * @param array $new_array
     * @return array
     * [1 => 1, 2 => 2]
     * [2 => 3, 3 => 4]
     *
     * [1 => 1, 2 => 3, 3 => 4]
     */
    public static function replaceValues(array &$current_array, array $new_array): array
    {
        foreach ($new_array as $key => $value) {
            $current_array[$key] = $value;
        }

        return $current_array;
    }
}
