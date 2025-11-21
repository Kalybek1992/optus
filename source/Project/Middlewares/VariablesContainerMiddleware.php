<?php

namespace Source\Project\Middlewares;

use Override;
use Source\Base\Core\Middleware;
use Source\Base\Core\Request;
use Source\Project\DataContainers\VariablesDC;

class VariablesContainerMiddleware extends Middleware
{
    /**
     * @var Request
     */
    protected Request $request;
    /**
     * @var VariablesDC|null
     */
    public ?VariablesDC $container;

    /**
     * @param Request $request
     * @param VariablesDC $container
     */
    public function __construct(VariablesDC &$container, Request &$request)
    {
        $this->request = $request;
        $this->container = $container;
    }

    /**
     * @param callable $next
     * @return bool
     */
    #[Override] public function handle(callable $next): bool
    {
        if ($this->request->getMethod() == 'GET') {
            $params = $this->request->getQueryParams();
        } else {
            $params = $this->request->getParsedBodyParams();
        }

        foreach ($params as $key => $value) {
            $this->container->{$key} = $value;
        }

        return $next();
    }
}