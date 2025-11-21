<?php

namespace Source\Base\Core;

/**
 * Class Error
 * Represents a custom error handler.
 *
 * 
 */
class Error
{
    /**
     * @var string|null
     */
    protected ?string $message = null;

    /**
     * @var int|null
     */
    protected ?int $code = null;

    /**
     * Error constructor.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * Get error message.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get error code.
     *
     * @return int|null
     */
    public function getCode(): ?int
    {
        return $this->code ?: 0;
    }
}
