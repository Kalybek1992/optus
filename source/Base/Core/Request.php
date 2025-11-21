<?php

namespace Source\Base\Core;

use Source\Base\Core\Interfaces\RequestInterface;

class Request implements RequestInterface
{
    protected string $method;
    protected ?string $uri;
    protected mixed $headers;
    protected array $query_params;
    protected array $parsed_body;

    protected array $files;

    protected array $cookies;
    protected array $server_params;
    protected array $session_data;
    /**
     * @var array
     */
    protected array $attributes = [];

    public function __construct()
    {
        $this->method = strtoupper( $_SERVER['REQUEST_METHOD'] ?? null);
        $this->uri = $_SERVER['REQUEST_URI'] ?? null;
        $this->headers = getallheaders();
        $this->query_params = $_GET;
        //$this->parsed_body = $_POST;//$this->getRequestBody();
        $this->parsed_body = $this->getRequestBody();
        $this->uri = $_GET["action"] ?? $this->getRequestBody()["action"] ?? $_SERVER['REQUEST_URI'] ?? null;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->server_params = $_SERVER;
        $this->session_data = $_SESSION ?? [];
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getUrl(): ?string
    {
        preg_match('#/[^?]*?(?=\?|\Z|\s)#', $this->uri, $url);

        return strtolower($url[0] ?? '');
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function setAttribute($name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute($name, $default = null): ?string
    {
        return $this->attributes[$name] ?? $default;
    }

    public function getHeader($header): ?string
    {
        $header = strtolower($header);

        return $this->headers[$header] ?? null;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getQueryParam(string $key,  $default = []): string | array
    {
        return $this->query_params[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->query_params;
    }

    public function getParsedBodyParam($key, $default = []): string | array
    {
        return $this->parsed_body[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getParsedBodyParams(): array
    {
        return $this->parsed_body;
    }

    public function withParsedBody($data): Request
    {
        $new = clone $this;
        $new->parsed_body = $data;

        return $new;
    }

    public function getFile($key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function getCookie($key): ?string
    {
        return $this->cookies[$key] ?? null;
    }

    public function getServerParam($key, $default = null): string
    {
        return $this->server_params[$key] ?? $default;
    }

    public function getSessionData($key, $default = null): string
    {
        return $this->session_data[$key] ?? $default;
    }


    private function getRequestBody()
    {
        $contentType = $this->headers['Content-Type'] ?? '';

        // If Content-Type is application/json, decode the raw input
        if (stripos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            $jsonData = json_decode($rawInput, true);

            // If JSON is valid, return it as an associative array
            if (json_last_error() === JSON_ERROR_NONE) {
                return $jsonData;
            }

            // If JSON is invalid, handle the error (optional)
            //throw new \Exception('Invalid JSON payload');
        }

        // For non-JSON requests (like form data), return the regular $_POST array
        return $_POST;
    }
}
