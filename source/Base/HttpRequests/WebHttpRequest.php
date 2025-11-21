<?php

namespace Source\Base\HttpRequests;

use CurlHandle;
use Source\Base\Core\Logger;

/**
 * Class WebHttpRequest
 *
 * This class represents a web HTTP request.
 * It extends the ProxyHttpRequest class.
 */
class WebHttpRequest extends ProxyHttpRequest
{
    /**
     * The path for the cookie.
     *
     * @var string|null
     */
    protected ?string $cookie_path = null;

    protected ?bool $is_curl = false;

    /**
     * Sends a request to the specified URL with optional data.
     *
     * @param string|null $link The URL to send the request to. If null, the default URL will be used.
     * @param array|string|null $data The data to send with the request. If null, no data will be sent.
     * @param string|null $referrer The referrer value to include in the request headers. If null, no referrer will be included.
     * @param bool $need_header Whether the response headers should be included in the result.
     * @param bool $redirect Whether to follow redirects or not.
     *
     * @return string|null The response body if the request succeeded, otherwise null.
     */
    protected function request(string $link = null, array|string $data = null, string $referrer = null, bool $need_header = false, bool $redirect = true): string|null|CurlHandle
    {
        $this->clearOptions();
        $curl_options = [];

        if(str_contains($link, 'https')) { // если соединяемся с https
            $curl_options = [
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0
            ];
        }

        $curl_options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        $curl_options[CURLOPT_URL] =  $link ?: $this->link;

        if ($this->cookie_path  ?? false) {
            $curl_options[CURLOPT_COOKIEFILE] = $this->cookie_path;
            $curl_options[CURLOPT_COOKIEJAR] = $this->cookie_path;
        }

        if ($this->header ?? false) {
            $curl_options[CURLOPT_HTTPHEADER] = $this->header;
        }

        $curl_options[CURLOPT_ENCODING] =  "gzip,deflate,br";
        if($referrer ?? false) {
            $curl_options[CURLOPT_REFERER] = $referrer;
        }

        $curl_options[CURLOPT_HEADER] = $need_header;
        $curl_options[CURLOPT_TIMEOUT] = 30;
        if ($data) {
            $curl_options[CURLOPT_POSTFIELDS] = $data;
            $curl_options[CURLOPT_POST] = true;
        } else {
            $curl_options[CURLOPT_POST] = false;
        }
        $curl_options[CURLOPT_RETURNTRANSFER] = true;
        $curl_options[CURLOPT_FOLLOWLOCATION] = $redirect;

        if ($this->proxy_pass ?? false) {
            $curl_options[CURLOPT_PROXYUSERPWD] = $this->proxy_pass;
            $curl_options[CURLOPT_PROXYTYPE] = $this->proxy_type;
            $curl_options[CURLOPT_PROXY] = $this->proxy_ip;
        }

        $this->addOptions($curl_options);

        if ($this->is_curl) {
            return $this->getCurl();
        }

        $result = $this->exec();

        if ($this->cookie_path  ?? false) {
            chmod($this->cookie_path, 0777);
        }

        return $result;
    }

    /**
     * Sets the header value.
     *
     * @param array $header The header value to be set.
     *
     * @return void
     */
    public function setHeader(array $header): void
    {
        $this->header = $header;
    }

    /**
     * Sends a GET request to the specified URL.
     *
     * @param string|null $link The URL to send the GET request to.
     * @param string $referrer The referrer value to include in the request headers.
     * @param bool $need_header Whether the response headers should be included in the result.
     * @param bool $redirect Whether to follow redirects or not.
     *
     * @return string|null The response body if the request succeeded, otherwise null.
     */
    public function get(string $link = null, string $referrer = "", bool $need_header = false, bool $redirect = true): string|null|CurlHandle
    {
        return $this->request($link, null, $referrer, $need_header, $redirect);
    }

    /**
     * Sends a POST request to the specified URL.
     *
     * @param string|null $link The URL to send the POST request to.
     * @param array|string|null $data The data to be sent in the request body.
     * @param string $referrer The referrer value to include in the request headers.
     * @param bool $need_header Whether the response headers should be included in the result.
     * @param bool $redirect Whether to follow redirects or not.
     *
     * @return string|null The response body if the request succeeded, otherwise null.
     */
    public function post(string $link = null,array|string $data = null, string $referrer = "", bool $need_header = false, bool $redirect = true): string|null|CurlHandle
    {
        return $this->request($link, $data, $referrer, $need_header, $redirect);
    }

}
