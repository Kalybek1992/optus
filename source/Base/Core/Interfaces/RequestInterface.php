<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface RequestInterface
 * Provides the necessary contract for managing rules.
 *
 * @package Source\Base\Interfaces
 */
interface RequestInterface
{
    /**
     * @return string|null
     */
    public function getMethod(): ?string;

    /**
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setAttribute(string $name, string $value): void;

    /**
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function getAttribute(string $name, string $default = null): ?string;

    /**
     * @param $header
     * @return string|null
     */
    public function getHeader($header): ?string;

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getQueryParam(string $key, string $default = null): string | array;

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getParsedBodyParam(string $key, string $default = null): string | array;

    /**
     * @param $data
     * @return RequestInterface
     */
    public function withParsedBody($data): RequestInterface;

    /**
     * @param $key
     * @return mixed
     */
    public function getFile($key): mixed;

    /**
     * @param $key
     * @return string|null
     */
    public function getCookie($key): ?string;

    /**
     * @param string $key
     * @param string|null $default
     * @return string
     */
    public function getServerParam(string $key, string $default = null): string;



    /**
     * @param string $key
     * @param string|null $default
     * @return string
     */
    public function getSessionData(string $key, string $default = null): string;
}
