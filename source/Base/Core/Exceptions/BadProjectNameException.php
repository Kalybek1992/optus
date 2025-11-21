<?php

namespace Source\Base\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Class BadProjectNameException
 * Thrown when an invalid project name is encountered.
 *
 * @package Source\Base\Exceptions
 */
class BadProjectNameException extends Exception
{
    /**
     * BadProjectNameException constructor.
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
