<?php

namespace Source\Base\HttpRequests\Structures;

/**
 * Class Proxy
 *
 * Represents a proxy with various types and properties.
 */
class Proxy
{
    /**
     * @desc Типы прокси
     *
     * @const int PROXY_TYPE_HTTP
     */
    const int PROXY_TYPE_HTTP = 0;
    /**
     * @const int PROXY_TYPE_HTTPS
     */
    const int PROXY_TYPE_HTTPS = 1;
    /**
     * @const int PROXY_TYPE_SOCKS5
     */
    const int PROXY_TYPE_SOCKS5 = 7;

    /**
     * @var string|null
     */
    public ?string $address = null;
    /**
     * @var string|null
     */
    public ?string $upwd = null;

    /**
     * Construct a new instance of the class.
     *
     * @param string $proxy The proxy information to extract user, password, host, and port from.
     *                      The format of the proxy string should be 'user:password@host:port'.
     *
     * @return void
     */
    public function __construct(string $proxy)
    {
        list($proxy_user, $proxy_password, $proxy_host, $proxy_port) = preg_split('#[:@]#', $proxy, -1, PREG_SPLIT_NO_EMPTY);

        $this->address = "$proxy_host:$proxy_port";
        $this->upwd = "$proxy_user:$proxy_password";
    }
}