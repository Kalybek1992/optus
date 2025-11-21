<?php

namespace Source\Base\Core;

use Source\Base\Core\Interfaces\RequestInterface;
use Source\Base\LogicManagers\MiddlewareManager;
use Source\Project\Controllers\ErrorController;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;


/**
 *
 */
abstract class Router
{
    protected ?string $method = null;

    protected ?string $url = null;

    public function route(RequestInterface $request): array|null|string
    {
        $this->setRequestData($request);


        if (!MiddlewareManager::validateRequest($this->method, $this->url, $request)) {
            $method = RequestDC::get('method');
            if ($method == 'GET') {
                $error_controller = new ErrorController();
                return $error_controller->errorPage();
            }
            return ['status' => 'error', 'value' => 'Invalid parameters validate'];
        }

        if ($request->getMethod() == 'GET') {
            $params = $request->getQueryParams();
        } else {
            $params = $request->getParsedBodyParams();
        }

        foreach ($params as $key => $value) {
            VariablesDC::set($key, $value);
        }

        if (!MiddlewareManager::runMiddlewares($this->method, $this->url)) {
            return ['status' => 'error', 'value' => MiddlewareManager::errorGet() ?: 'some_error'];
        }


        $controller_class = $this->getControllerClass($this->url);
        $controller_function = $this->getControllerFunction($this->url);
        $controller_class = Controller::getClass($controller_class);
        $controller = new $controller_class();

        return $controller->$controller_function($request);
    }

    protected abstract function getControllerClass(string $url): ?string;

    protected abstract function getControllerFunction(string $url): ?string;

    protected abstract function setRequestData(RequestInterface $request);

}