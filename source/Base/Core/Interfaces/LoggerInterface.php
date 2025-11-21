<?php

namespace Source\Base\Core\Interfaces;

/**
 * Describes the logging system.
 *
 * The message MUST be a string or an object that implements __toString().
 *
 * The message MAY contain placeholders of the form {foo}, where foo would be
 * replaced by the value of the context array element with the key "foo".
 *
 * The context array can contain arbitrary data. The only thing
 * the assumption allowed by developers is that
 * if an exception object is passed in the array to build a trace
 * stack, it MUST be in the array element with the "exception" key.
 *
 */
interface LoggerInterface
{
    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function emergency(string $message, array $context = null): void;

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function alert(string $message, array $context = null): void;

    /**
     *
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function critical(string $message, array $context = null): void;

    /**
     *
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function error(string $message, array $context = null): void;

    /**
     *
     * @param string $message
     * @param array|null $context
     * @return 