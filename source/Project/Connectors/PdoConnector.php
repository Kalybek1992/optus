<?php

namespace Source\Project\Connectors;

use Source\Base\Connectors\AbstractPdoConnector;

final class PdoConnector extends AbstractPdoConnector
{
    /**
     * @var object|null
     */
    public static ?object $connector = null;
}

