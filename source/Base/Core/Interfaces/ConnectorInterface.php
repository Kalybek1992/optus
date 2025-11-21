<?php

namespace Source\Base\Core\Interfaces;

use LogicException;

/**
 * Interface for the Connector class.
 *
 * Provides the necessary contract for establishing connections.
 *
 */
interface ConnectorInterface
{
    /**
     * Initializes the connection settings.
     *
     * @return void
     */
    public static function initializeData(): void;

    /**
     * Singleton constructor logic.
     */
    public function __construct();

    /**
     * Prevents cloning of the instance.
     */
    public function __clone();

    /**
     * Prevents unserialization of the instance.
     *
     * @throws LogicException
     */
    public function __wakeup();
}
