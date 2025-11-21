<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface RulesInterface
 * Provides the necessary contract for managing rules.
 *
 * @package Source\Base\Interfaces
 */
interface ResponseInterface
{
    public function setStatusCode($code): ResponseInterface;

    public function getStatusCode();

    public function setHeader($name, $value): ResponseInterface;

    public function getHeader($name);

    public function setBody($body): ResponseInterface;
    public function getBody();

    public function send();

    public function sendExit();
}
