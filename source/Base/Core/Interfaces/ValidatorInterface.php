<?php

namespace Source\Base\Core\Interfaces;

interface ValidatorInterface
{
    /**
     * Validates a parameter based on specified rules.
     *
     * @param mixed|null $param The parameter to validate.
     * @param string|null $rules The validation rules.
     * @return bool True if the parameter is valid, false otherwise.
     */
    public static function validate(mixed $param = null, ?string $rules = null): bool;

    /**
     * Removes potential injection characters from a parameter.
     *
     * @param string|null $param The parameter to clean.
     * @return string|null The cleaned parameter.
     */
    public static function deleteInjection(?string $param = null): ?string;
}
