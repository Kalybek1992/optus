<?php
namespace Source\Base\Connectors;

use RedisException;
use Source\Base\Connectors\Base\BaseLogicConnector;
use Redis;
use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;

/**
 * Class AbstractRedisConnector
 *
 * This class serves as the base class for Redis connectors. It provides functionality to establish and manage
 * connections to a Redis server.
 *
 * @method static keys(string $string)
 * @method static get(string $string)
 * @method static set(string $string, false|int $param)
 * @method static exists(string $string)
 * @method static incr(string $string)
 * @method static mSet(array $param)
 * @method static mGet($keys)
 * @method static del(array|string $keys)
 * @method static hGet(string $string, string $mail_id)
 * @method static rPopLPush(string $string, string $string1)
 * @method static zPopMin(string $string, int $int)
 * @method static zAdd(string $string, int|array $time, int|string $i)
 * @method static select(int $int)
 * @method static hDel(string $string, mixed|null $email_id)
 * @method static sAdd(string $string, false|string $json_encode)
 * @method static zCard(string $string)
 * @method static hSet(string $string, int|string $key, mixed $value)
 * @method static rPop(string $string)
 * @method static lPush(string $string, $proxy)
 * @method static zPopMax(string $string)
 * @method static zRem(string $string, mixed|null $activation_id)
 * @method static hExists(string $string, mixed|null $activation_id)
 * @method static expire(string $string, int $int)
 * @method static setNx(string $string, int $int)
 * @method static sPop(string $string, int $int)
 * @method static sRem(string $string, string $str)
 * @method static hMSet(string $string, $settings)
 * @method static rPush(string $key, string $key1)
 * @method static setEx(string $string, int $int, int $int1)
 * @method static zRange(string $string, int $int, int $int1, bool $true)
 * @method static publish(string $string, $task_id)
 * @method static sIsMember(string $key_log, $id)
 * @method static hGetAll(string $string)
 * @method static lRange(string $string)
 * @method static hIncrBy(string $string, mixed $id, int $param)
 * @method static sCard(string $string)
 * @method static ttl(string $string)
 * @method static incrBy(string|null $key, int $int)
 * @method static hLen(string $string)
 * @method static hSetNx(string $string, int|string $key, mixed $value)
 */
abstract class AbstractRedisConnector extends BaseLogicConnector
{
    public static ?string $config_name = 'Redis';

    /**
     * Holds the Redis connection instance.
     *
     * @var object|null
     */
    public static ?object $connector = null;

    /**
     * Returns the Redis connection instance, creates a new connection if one doesn't exist.
     * Optionally, a specific database can be selected.
     *
     * The database number to select.
     * @param int $db_number
     * @return object|null The Redis connection instance.
     * @throws RedisException
     */
    public static function getConnector(int $db_number = 0): ?object
    {
        // Check if a connection already exists
        if (static::$connector == null) {
            static::$connector = static::newConnect($db_number);
        }
        // Return the Redis connection instance
        return static::$connector;
    }

    /**
     * @param int $db_number
     * @return Redis|null
     * @throws RedisException
     */
    protected static function newConnect(int $db_number = 0): ?Redis
    {
        // Call parent initializeData method (if any logic is present there)
        parent::initializeData();
        // Create a new Redis instance
        $connector = new Redis();

        // Check if a UNIX socket connection is required
        if (static::$sock ?? false) {
            // Connect to Redis using a UNIX socket
            $connector->pconnect(static::$sock);
        } else {
            // Connect to Redis using hostname and port
            $connector->connect(static::$host, static::$port);
        }
        // Authenticate with Redis if a password is set
        if (static::$password) {
            $connector->auth(['pass' => static::$password]);
        }

        // Select a specific Redis database if $db is set
        if ($db_number) {
            $connector->select($db_number);
        }

        // Return the Redis connection instance
        return $connector;
    }

    /**
     * Closes the Redis connection and sets the connector to null.
     */
    public static function close(): void
    {
        // Check if a connection exists
        static::$connector?->close();

        // Set the connector to null
        static::$connector = null;
    }

    /**
     * Allows for static calls to Redis methods via the Redis connection instance.
     *
     * @param string $name The name of the method being called.
     * @param $arguments array|null The arguments passed to the method.
     * @return mixed The result of the Redis method call.
     * @throws RedisException
     */
    public static function __callStatic(string $name, ?array $arguments)
    {
        // Ensure a connection exists
        static::getConnector();

        if (Config::DEBUG && (Config::DEBUG != 'false')) {
            Logger::log("Redis Method $name arguments " . json_encode($arguments), 'redis_queries');
        }
        // Call the Redis method via the Redis connection instance
        return static::$connector->$name(...$arguments);
    }
}

