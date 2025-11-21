<?php

namespace Source\Project\Connectors;

use Source\Base\Connectors\AbstractMemcacheConnector;

final class MemcacheConnector extends AbstractMemcacheConnector
{
    /**
     * @var object|null
     */
    public static ?object $connector = null;
}

