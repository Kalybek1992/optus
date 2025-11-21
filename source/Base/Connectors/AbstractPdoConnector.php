<?php

namespace Source\Base\Connectors;

use PDO;
use Source\Base\Builders\PdoQueryBuilder\PdoOperators\Select;
use Source\Base\Builders\PdoQueryBuilder\PdoQueryBuilder;
use Source\Base\Connectors\Base\BaseLogicConnector;
use Source\Base\Constants\Settings\Config;
use Source\Base\Core\Logger;

/**
 * Represents an abstract class for PDO connector.
 *
 * Class AbstractPdoConnector extends BaseLogicConnector
 */
abstract class AbstractPdoConnector extends BaseLogicConnector
{
    public static ?string $config_name = 'Db';
    /**
     * Holds the PDO connection instance.
     *
     * @var object|null
     */
    public static ?object $connector = null;

    /**
     * Returns the PDO connection instance, creates a new connection if one doesn't exist.
     *
     * @param int $db_number
     * @return object|null
     */
    public static function getConnector(int $db_number = 0): ?object
    {
        // Check if a connection already exists
        if (empty(static::$connector)) {
            // Call parent initializeConnection method
            parent::initializeData();

            // Check if a UNIX socket connection is required
            if (static::$sock) {
                // Create a new PDO connection using a UNIX socket
                static::$connector = new PDO(
                    sprintf(
                        static::$type . ':unix_socket=%s;dbname=%s',
                        static::$sock,
                        static::$name
                    ),
                    static::$user,
                    static::$password
                );
            } else {
                // Create a new PDO connection using hostname and dbname
                static::$connector = new PDO(
                    sprintf(static::$type . ":dbname=%s;host=%s",
                        static::$name,
                        static::$host
                    ),
                    static::$user,
                    static::$password
                );
            }

            // Uncomment the line below if you want to set a specific PDO attribute
            // static::$connector->setAttribute(\PDO::ATTR_ERRMODE);
        }
        // Return the PDO connection instance
        return static::$connector;
    }

    /**
     * @param PdoQueryBuilder $query
     * @param int $type
     * @return mixed
     */
    public static function execute(PdoQueryBuilder $query, int $type = PDO::FETCH_CLASS): mixed
    {
        static::getConnector();

        if (Config::DEBUG && (Config::DEBUG != 'false')) {
            Logger::log("Pdo Query - " . $query->build(), 'pdo_queries');
        }

        $statement = static::$connector->prepare($query->build());
        $statement->execute($query->getConditions());

        $count = $statement->rowCount();
        $class = $query->getClass();

        if (!$query->getOperator(Select::class)) {
            return $count;
        }

        if (PDO::FETCH_CLASS == $type) {

            return $statement->fetchAll($type, $class);
        }

        return $statement->fetchAll($type);
    }
}
