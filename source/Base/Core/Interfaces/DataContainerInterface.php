<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface RequestInterface
 * Provides the necessary contract for managing rules.
 *
 * @package Source\Base\Interfaces
 */
interface DataContainerInterface
{
    static function set($name, $mixed): void;

    static function get($name): mixed;
}
