<?php

namespace Source\Base\Core;

use CurlHandle;
use Source\Base\Core\Error;

class HttpRequest
{
    /**
     * @var array|string
     */
    protected string|array $header;
    /**
     * @var string|null
     */
    protected ?string $method = null;

    /**
     * @var Error|null
     */
    public ?Error $error = null;
    
    /**
     * @var array|null
     */
    public ?array $curl_options = [];
    /**
     * @var string|null
     */
    protected ?string $link;

    /**
     * @var CurlHandle|null
     */
    protected ?CurlHandle $curl = null;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if ($data != []) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param string $link
     * @param array|null $header
     * @return void
     */
    public function set(string $link, array $header = null): void
    {
        $this->getMethodName();
        $this->link = $link;
        
        if ($header != null) {
            $this->header = $header;

            static::addOptions([
                CURLOPT_HTTPHEADER => $header
            ]);
        }
    }
    
    /**
     * @param array $curl_options
     */
    public function addOptions(array $curl_options): void
    {
        foreach ($curl_options as $key => $value) {
            $this->curl_options[$key] = $value;
        }
    }


    /**
     * @param bool $close_curl
     * @return string|null
     */
    public function exec(bool $close_curl = true): ?string
    {
        $response = curl_exec($this->getCurl());
        $this->error = new Error(curl_error($this->curl), curl_errno($this->curl));

        if ($close_curl) {
            curl_close($this->curl);
            $this->curl = null;
            $this->clearOptions();
        }

        return $response;
    }

    /**
     * @return false|resource
     */
    public function getCurl(): CurlHandle
    {
        if (!$this->curl) {
            $this->curl = curl_init();
        }
        curl_setopt_array($this->curl, $this->curl_options);

        return $this->curl;
    }

    /**
     * @desc clear curl_options array
     */
    public function clearOptions(): void
    {
        $this->curl_options = [];
    }

    /**
     * @return string|null
     */
    protected function getMethodName(): ?string
    {
        if (!$this->method) {
            $exploded_class = explode('\\', static::class);
            $this->method = strtoupper($exploded_class[count($exploded_class)-1]);
        }

        return $this->method;
    }
}

