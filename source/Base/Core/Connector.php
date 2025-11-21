<?php

namespace Source\Base\Core;

use LogicException;
use Source\Base\Core\Interfaces\ConnectorInterface;
use Source\Base\GlobalVariables\Env;

/**
 * Abstract Connector class.
 *
 * Provides base functionality for establishing connections.
 *
 */
abstract class Connector implements ConnectorInterface
{
    /**
     * Connector instance.
     *
     * @var object|null
     */
    protected static ?object $connector = null;

    /**
     * Full Class Name.
     *
     * @var string|null
     */
    protected static ?string $full_class_name;

    /**
     * Prefix for environment variables.
     *
     * @var string|null
     */
    protected static ?string $connector_name = null;

    /**
     * Connection type.
     *
     * @var string|null
     */
    protected static ?string $type;

    /**
     * Database name.
     *
     * @var string|null
     */
    protected static ?string $name;

    /**
     * Host address.
     *
     * @var string|null
     */
    protected static ?string $host;

    /**
     * Port number.
     *
     * @var string|null
     */
    protected static ?string $port;

    /**
     * Username for connection.
     *
     * @var string|null
     */
    protected static ?string $user;

    /**
     * Password for connection.
     *
     * @var string|null
     */
    protected static ?string $password;

    /**
     * Socket path.
     *
     * @var string|null
     */
    protected static ?string $sock = null;

    /**
     * VariablesMiddlewares to be set for connection.
     *
     * @var array
     */
    protected static array $vars = [
        'type',
        'name',
        'host',
        'port',
        'user',
        'password',
        'sock'
    ];

    /**
     * Sets the connector name based on the class name.
     *
     * @return void
     */
    protected static function setConnectorName(): void
    {
        static::$full_class_name = static::class;
        $exploded_class = explode('\\', static::$full_class_name);

        $replaced = str_replace(
            'Connector',
            '',
            $exploded_class[count($exploded_class)-1]
        );

        $preg_split = preg_split(
            '#([A-Z][a-z]*)#',
            $replaced,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
//        var_dump($preg_split);

        static::$connector_name = strtoupper(implode('_', $preg_split));
    }

    /**
     * Singleton constructor.
     */
    public function __construct() { }

    /**
     * Prevents cloning of the instance.
     */
    public function __clone() { }

    /**
     * Prevents unserialization of the instance.
     *
     * @throws LogicException
     */
    public function __wakeup()
    {
        throw new LogicException("Cannot unserialize a singleton.");
    }
}
