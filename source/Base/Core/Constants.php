<?php

namespace  Source\Base\Core;

use ReflectionClass;

class Constants
{
    /**
     * @DESC REGEX for values keys
     */
    protected const string REGEX_KEY = '{{text}}';

    /**
     * Retrieves a Redis key based on the provided key name and values.
     *
     * @param string $const_value The name of the key constant.
     * @param mixed ...$values The values to append to the key.
     * @return string|null The Redis key with values appended, or null if the key constant is not found.
     */
    public static function getKey(string $const_value, ...$values): ?string
    {
        $reflected_class = new ReflectionClass(static::class);
        $constants = $reflected_class->getConstants();

        if ($in_array = in_array($const_value, $constants) && $values != []) {
            while (
                ($values[0] ?? false) &&
                $new_const = static::replaceTextInConstant($const_value, $values[0])
            ) {
                $const_value = $new_const;
                array_shift($values);
            }
        }

        if (!$in_array || str_contains($const_value, static::REGEX_KEY)) {
            return null;
        }

        return $const_value;
    }

    /**
     * Checks if the constant contains the provided text and if so, replaces it with the provided replacements.
     *
     * @param string $const_value The constant.
     * @param string $replacement The replacement text.
     * @return string|null The replaced constant value, or null if the key constant is not found or does not contain the text.
     */
    private static function replaceTextInConstant(string $const_value, string $replacement): ?string
    {
        if (str_contains($const_value, static::REGEX_KEY)) {
            return str_replace(static::REGEX_KEY, $replacement, $const_value);
        }

        return null;
    }
}