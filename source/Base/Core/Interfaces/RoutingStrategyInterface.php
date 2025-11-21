<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface RoutingStrategyInterface
 * @package Source\Base\Interfaces
 */
interface RoutingStrategyInterface
{
    /**
     * Parses the URL and returns the controller class and function.
     *
     * @param string $url
     * @return array
     */
    public function parseUrl(string $url): array;
}
