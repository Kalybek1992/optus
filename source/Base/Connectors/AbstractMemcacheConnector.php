<?php

namespace Source\Base\Connectors;

use Exception;
use Memcache;
use Source\Base\Connectors\Base\BaseLogicConnector;

/**
 * Class AbstractMemcacheConnector
 *
 * This class serves as a base for connecting and interacting with the Memcache server.
 * It provides methods for managing the Memcache connection, creating a new connection,
 * closing the connection, and allowing static calls to Memcache methods.
 *
 * @method static delete(array|string|string[]|null $key)
 * @method static get(array|string|string[]|null $key)
 * @method static set(mixed $value_data, array|string|string[]|null $key)
 */
abstract class AbstractMemcacheConnector extends BaseLogicConnector
{
    public static ?string $config_name = 'Memcache';

    /**
     * Holds the Memcache connection instance.
     *
     * @var object|null
     */
    public static ?object $connector = null;

    /**
     * Returns the Memcache connection instance, creates a new connection if one doesn't exist.
     *
     * @return object|null The Memcache connection instance.
     */
    public static function getConnector(): ?object
    {
        // Call parent getConnect method (if any logic is present there)
        parent::initializeData();

        // Check if a connection already exists
        if (static::$connector == null) {
            // Create a new connection
            static::$connector = static::newConnect();
        }

        // Return the Memcache connection instance
        return static::$connector;
    }

    /**
     * Closes the Memcache connection and sets the connector to null.
     */
    public static function close(): void
    {
        // Close the Memcache connection
        static::$connector?->close();

        // Set the connector to null
        static::$connector = null;
    }

    /**
     * Creates a new Memcache connection.
     *
     * @return object The new Memcache connection instance.
     */
    public static function newConnect(): object
    {
        // Create a new Memcache instance
        $memcache = new Memcache();

        // Connect to Memcache using hostname and port
        $memcache->pconnect(static::$host, static::$port);

        // Return the new Memcache connection instance
        return $memcache;
    }

    /**
     * Allows for static calls to Memcache methods via the Memcache connection instance.
     *
     * @param string $name The name of the method being called.
     * @param $arguments array|null The arguments passed to the method.
     * @return mixed The result of the Memcache method call.
     * @throws Exception Throws an exception if the method call fails.
     */
    public static function __callStatic(string $name, ?array $arguments)
    {
        // Ensure a connection exists
        static::getConnector();

        // Call the Memcache method via the Memcache connection instance
        return static::$connector->$name(...$arguments);
    }
}
