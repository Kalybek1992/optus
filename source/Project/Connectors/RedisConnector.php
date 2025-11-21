<?php

namespace Source\Project\Connectors;

use Source\Base\Connectors\AbstractRedisConnector;

/**
 * Class RedisConnector
 *
 * This class is responsible for connecting to a Redis server and
 * providing access to the Redis functionality.
 * @method static zRangeByScore(string $string, int|string $product_id, int|string $product_id1)
 * @method static zScore(string $string, int|string $queue)
 * @method static hKeys(string $string)
 */
final class RedisConnector extends AbstractRedisConnector
{
    /**
     * @var object|null
     */
    public static ?object $connector = null;
}

