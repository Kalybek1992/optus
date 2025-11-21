<?php

namespace Source\Base\Core\Exceptions;

use Exception;

/**
 * CustomException class for handling exceptions in routing.
 */
class CustomException extends Exception
{
    /**
     * Constructor override to provide custom message and code.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * Custom string representation of an object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [$this->code]: $this->message\n";
    }
}
