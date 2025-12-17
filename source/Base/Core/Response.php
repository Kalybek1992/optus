<?php

namespace Source\Base\Core;

use JetBrains\PhpStorm\NoReturn;
use Source\Base\Core\Interfaces\ResponseInterface;


class Response implements ResponseInterface
{
    protected int $status_code;
    protected array $headers;
    protected mixed $body;

    public function __construct($body = '', $status_code = 200, $headers = [])
    {
        $this->status_code = $status_code;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function setStatusCode($code): Response
    {
        $this->status_code = $code;

        return $this;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function setHeader($name, $value): Response
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    public function setBody($body): Response
    {
        $this->body = $body;

        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return void
     */
    public function send(): void
    {
        // Отправляем код ответа
        http_response_code($this->status_code);

        // Отправляем заголовки
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Отправляем тело ответа
        echo $this->body;
    }

    /**
     * @return void
     */
    #[NoReturn] public function sendExit(): void
    {
        // Отправляем код ответа
        http_response_code($this->status_code);

        // Отправляем заголовки
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Отправляем тело ответа
        exit($this->body);
    }


    #[NoReturn] public function sendHtmlExit(int $statusCode = 200): void
    {
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setStatusCode($statusCode)
            ->setBody($this->body)
            ->sendExit();
    }
}
