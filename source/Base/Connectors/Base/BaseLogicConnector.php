<?php

namespace Source\Base\Connectors\Base;

use RuntimeException;
use Source\Base\Core\Connector;

abstract class BaseLogicConnector extends Connector
{
    /**
     * @var string|null
     */
    public static ?string $config_name = null;
    /**
     * Sets connection variables.
     *
     * @return void
     */
    public static function initializeData(): void
    {
        static::setConnectorName();
        static::setConnectionVariables();
    }

    /**
     * Sets the connection variables from the environment.
     *
     * @return void
     */
    protected static function setConnectionVariables(): void
    {
        foreach (static::$vars as $var) {
            $env_var_name = strtoupper(static::$connector_name . '_' . strtoupper($var));

            $path = 'Source\\Base\\Constants\\Settings\\' . ucfirst(static::$config_name);
            $env_var_value = $path::{$env_var_name};

            if ($env_var_value === false) {
                throw new RuntimeException("Environment variable $env_var_name not found.");
            }

            static::$$var = $env_var_value;
        }
    }
}
