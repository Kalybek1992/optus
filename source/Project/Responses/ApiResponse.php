<?php

namespace Source\Project\Responses;

use Exception;
use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Exceptions\ResponseException;
use Source\Base\Core\Response;

class ApiResponse extends Response
{
    /**
     * @var string|null
     */
    public ?string $api_version = '1.0';

    /**
     * @desc versions api
     */
    public const array API_VERSIONS = [
        '1.0'
    ];

    /**
     * @desc type versions
     */
    public const array TYPE_VERSIONS = [
        'json'
    ];

    public ?object $json_defined_answer = null;

    /**
     * @param string|null $api_version
     * @param string $lang
     * @throws ResponseException
     */
    public function __construct(?string $api_version = '1.0', string $lang = 'en')
    {
        $this->api_version = in_array($api_version, static::API_VERSIONS)
            ? $api_version
            : static::API_VERSIONS[0];

        try {
            $this->json_defined_answer = json_decode(file_get_contents(
                    Path::RESOURCES_DIR .
                    "api/$lang/" .
                    ceil($this->api_version) . '.json')
            );

            if ($this->json_defined_answer) {
                parent::__construct(
                    json_encode([
                        'status' => 'error',
                        'value' => $this->json_defined_answer->default
                    ]),
                    400,
                    ['Content-Type' => 'text/json']
                );
            }
        } catch (Exception $e) {
            throw new ResponseException( "No file /resources/api/$lang/" . ceil($this->api_version) . '.json');
        }
    }


    /**
     * @param array|null $values
     * @return self
     */
    protected function setApiBody(?array $values): self
    {
        return $this->setBody(json_encode($values));
    }

    /**
     * @param array|null $values
     * @return self
     */
    public function setErrorBody(?array $values): self
    {
        $this->setStatusCode(400);

        return $this->setApiBody(array_merge(['status' => 'error'], $values));
    }

    /**
     * @param array|null $values
     * @return self
     */
    public function setOkBody(?array $values = []): self
    {
        $this->setStatusCode(200);

        return $this->setApiBody(array_merge(['status' => 'ok'], $values));
    }

    /**
     * @param string $key_name
     * @return self
     */
    public function setDefinedAnswer(string $key_name): self
    {
        return $this->setErrorBody(
            ['value' =>  $this->json_defined_answer->$key_name ??  $this->json_defined_answer->default]
        );
    }
}