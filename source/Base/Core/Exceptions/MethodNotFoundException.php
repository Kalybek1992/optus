<?php

namespace Source\Base\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Class MethodNotFoundException
 * Thrown when a method is not found within a class.
 *
 * @package Source\Base\Exceptions
 */
class MethodNotFoundException extends Exception
{
    /**
     * MethodNotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
