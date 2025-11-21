<?php

namespace Source\Base\HttpRequests;

use Source\Base\Core\HttpRequest;

/**
 * Class ProxyHttpRequest
 *
 * Represents a Proxy HTTP request.
 */
class ProxyHttpRequest extends HttpRequest
{
    /**
     * @desc Типы прокси
     *
     * @const int PROXY_TYPE_HTTP
     */
    const string PROXY_TYPE_HTTP = 'http';
    /**
     * @const int PROXY_TYPE_HTTPS
     */
    const string PROXY_TYPE_HTTPS = 'https';
    /**
     * @const int PROXY_TYPE_SOCKS5
     */
    const string PROXY_TYPE_SOCKS5 = 'socks5';
    /**
     * @const array MAPPING_PROXY_TYPE_CURL
     */
    const array MAPPING_PROXY_TYPE_CURL = [
        self::PROXY_TYPE_HTTPS => CURLPROXY_HTTPS,
        self::PROXY_TYPE_SOCKS5 => 7,
        self::PROXY_TYPE_HTTP => CURLPROXY_HTTP
    ];
    /**
     * @const array MAPPING_ENCODING
     */
    const array MAPPING_ENCODING = [
        'base64' => 'base64_decode'
    ];

    /**
     * @var string|null
     */
    protected ?string $proxy_pass = null;
    /**
     * @var string|null
     */
    protected ?string $proxy_ip = null;

    protected ?string $proxy_type = null;


    /**
     * Sets the proxy configuration for the request.
     *
     * @param string $proxy_ip The IP address:port of the proxy server.
     * @param string|null $proxy_password The login:password to authenticate with the proxy server. Default is null.
     * @param mixed $type The type of proxy server. Default is null.
     * @return void
     */
    public function setProxy(string $proxy_ip, ?string $proxy_password = null, mixed $type = null): void
    {


        if ($proxy_ip) {
            $this->proxy_ip = $proxy_ip;
            $this->proxy_type = is_numeric($type)
                ? $type
                : self::MAPPING_PROXY_TYPE_CURL[self::PROXY_TYPE_SOCKS5];
//            var_dump($this->proxy_type);
        }

        if ($proxy_password) {
            $this->proxy_pass = $proxy_password;
        }
    }
}