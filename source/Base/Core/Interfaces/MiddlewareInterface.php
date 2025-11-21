<?php

namespace Source\Base\Core\Interfaces;

use Source\Base\Core\DataContainer;

/**
 * Interface for middleware classes.
 */
interface MiddlewareInterface
{
    /**
     * Handles the processing of middleware.
     *
     * @param callable $next The next middleware function.
     * @return bool|string The result of middleware processing.
     */
    public function handle(callable $next): bool|string;
}
