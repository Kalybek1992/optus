<?php

namespace Source\Base\Core;

use Source\Base\Core\Error;

/**w
 * Class MultiCurl
 * @package Source\Addons
 */
class MultiCurl
{
    /**
     * @var mixed;
     */
    protected mixed $multi = null;

    /**
     * @var array
     */
    public array $errors = [];

    /**
     * @var array of curl resources
     */
    protected array $curl = [];

    /**
     * @var array of results
     */
    public array $results = [];

    /**
     * @param $curl resource|mixed of curl_init()
     * @param string $name
     */
    public function add(string $name, mixed $curl): void
    {
        if (!$this->multi) {
            $this->multi = curl_multi_init();
        }

        $this->curl[$name] = $curl;
        curl_multi_add_handle($this->multi, $curl);
    }

    /**
     * @desc curl_multi exec all curl 
     * @desc all result data valuable at @param class MultiCurl
     * @desc name @param = $name , which added with curl
     */
    public function exec(bool $close_curl = true): void
    {
        $running = 0;

        do {
            usleep(120000);
            curl_multi_exec($this->multi, $running);
        } while ($running);

        while ($a = curl_multi_info_read($this->multi)) {
            usleep(100);
            $key = array_search($a['handle'], $this->curl);
            $code = $a['result'];
            usleep(55000);

            $this->errors[$key] = new Error(curl_strerror($code), $code);
        }

        foreach ($this->curl as $key => $resource) {
            usleep(5);
            curl_multi_remove_handle($this->multi, $resource);
            usleep(55000);

            $this->results[$key] = curl_multi_getcontent($resource);
        }

        if ($close_curl) {
            curl_multi_close($this->multi);
            usleep(55000);

            $this->multi = null;
        }
    }

    public function unsetAll(): void
    {
        $this->results = [];
        $this->curl = [];
        $this->errors = [];
    }

    public function unset(string $key): void
    {
        unset($this->results[$key]);
        unset($this->errors[$key]);
    }

    /**
     * @param string $name
     * @return mixed
     * @desc all result data
     */
    public function __get(string $name)
    {
        try {
            return $this->results[$name];
        } finally {
            echo "Don't isset property $name in @var result";
        }
    }
}

