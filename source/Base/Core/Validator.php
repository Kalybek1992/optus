<?php

namespace Source\Base\Core;

use InvalidArgumentException;
use Source\Base\Core\Interfaces\ValidatorInterface;

/**
 * Class Validator
 *
 * This class provides data validation based on specified rules.
 *
 * @package Source\Base\Core
 */
class Validator implements ValidatorInterface
{
    public const string STRING = 'string';

    public const string NUMERIC = 'numeric';

    public const string INT = 'int';

    public const string FLOAT = 'float';

    public const string ARRAY = 'array';
    /**
     * Supported validation types.
     *
     * @var array|string[]
     */
    const array TYPES = [
        'string',
        'int',
        'float',
        'numeric',
        'array'
    ];

    /**
     * Validates a parameter based on specified rules.
     *
     * @param mixed|null $param The parameter to validate.
     * @param string|null $rules The validation rules in the format 'type|length_rules|regex_rules'.
     * @return bool True if the parameter is valid, false otherwise.
     */
    public static function validate(mixed $param = null, ?string $rules = null): bool
    {
        if (empty($rules) || empty($param)) {
            return false;
        }

        $rulesArray = explode('|', $rules);
        $type = $rulesArray[0] ?? '';

        if (!in_array($type, self::TYPES)) {
            return false;
        }

        $lengthRules = $rulesArray[1] ?? null;
        $regexRules = $rulesArray[2] ?? null;

        return self::validateRegex($param, $regexRules) &&
            self::validateLengthAndValue($param, $lengthRules) &&
            self::validateType($param, $type);
    }


    /**
     * Validates regular expression rules.
     *
     * @param mixed $param The parameter to validate.
     * @param string|null $regexRules The regular expression rules.
     * @return bool True if all regex rules pass, false otherwise.
     */
    private static function validateRegex(mixed $param, ?string $regexRules): bool
    {
        if (!$regexRules) {
            return true;
        }

        foreach (explode('&', $regexRules) as $regex) {
            if (!preg_match('#' . $regex . '#', $param)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates length and value rules.
     *
     * @param mixed $param The parameter to validate.
     * @param string|null $lengthRules The length and value rules.
     * @return bool True if all length and value rules pass, false otherwise.
     */
    private static function validateLengthAndValue(mixed $param, ?string $lengthRules): bool
    {
        if (!$lengthRules) {
            return true;
        }

        $strlen = mb_strlen($param);

        foreach (explode('&', $lengthRules) as $rule) {
            list($key, $value) = explode(':', $rule);
            if (!self::checkLengthAndValueRule($param, $strlen, $key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks a single length or value rule.
     *
     * @param mixed $param The parameter to validate.
     * @param int $strlen The string length of the parameter.
     * @param string $key The rule key.
     * @param string $value The rule value.
     * @return bool True if the rule passes, false otherwise.
     * @throws InvalidArgumentException if an invalid rule is provided.
     */
    private static function checkLengthAndValueRule(mixed $param, int $strlen, string $key, string $value): bool
    {
        return match ($key) {
            'min_length' => $strlen >= $value,
            'max_length' => $strlen <= $value,
            'min' => $param >= $value,
            'max' => $param <= $value,
            default => throw new InvalidArgumentException('Invalid rule: ' . $key),
        };
    }

    /**
     * Validates the type of the parameter.
     *
     * @param mixed $param The parameter to validate.
     * @param string $type The expected type.
     * @return bool True if the parameter is of the expected type, false otherwise.
     */
    private static function validateType(mixed $param, string $type): bool
    {
        $function = 'is_' . $type;
        return $function($param);
    }

    /**
     * Removes potential injection characters from a parameter.
     *
     * @param string|null $param The parameter to clean.
     * @return string|null The cleaned parameter.
     */
    public static function deleteInjection(?string $param = null): ?string
    {
        return $param ? trim(preg_replace(['/[\'"`\\\]/ui', '#\s+#'], ['', ''], urldecode($param))) : null;
    }

    public static function getValidateRule(string $validate_type = self::STRING, ?int $min = null, ?int $max = null, string $regex = null): string
    {
        /**
         * @desc array params
         */
        $implode[] = $validate_type;
        $limits = [];
        /**
         * @desc bool params
         */
        $is_int_type = $validate_type !== self::STRING;
        $is_min = $min !== null;
        $is_max = $max !== null;

        if ($is_min) {
            if ($is_int_type) {
                $limits[] = "min:$min";
            } else {
                $limits[] = "min_length:$min";
            }
        }

        if ($is_max) {
            if ($is_int_type) {
                $limits[] = "max:$max";
            } else {
                $limits[] = "max_length:$min";
            }
        }

        if (count($limits) > 0) {
            $implode[] = implode('&', $limits);
        }

        if ($regex) {
            $implode[] = $regex;
        }

        return implode('|', $implode);
    }
}
