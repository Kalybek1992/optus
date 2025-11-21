<?php

namespace Source\Project\Routers;

use Source\Base\Core\Interfaces\RequestInterface;
use Source\Base\Core\Logger;
use Source\Base\Core\Router;
use Source\Project\DataContainers\RequestDC;

final class AppRouter extends Router
{
    #[\Override] protected function getControllerClass(string $url): ?string
    {
        preg_match('#(?<=/)[^?/]+?(?=/)#', $url, $url_controller_function);

        return ucfirst($url_controller_function[0] ?? 'Home') . 'Controller';
    }

    #[\Override] protected function getControllerFunction(string $url): ?string
    {
        preg_match('#(?<=/)[^/]+?(?=\Z)#', $url, $url_controller_function);

        return strtolower($url_controller_function[0] ?? 'homePage');
    }

    #[\Override] protected function setRequestData(RequestInterface $request): void
    {
        $this->method = $request->getMethod();
        $this->url = $request->getUrl();

        $auth_token = $request->getCookie('AuthToken');

        $lang = "RU";
        $file = $request->getFile('file');


        RequestDC::set('file', $file);
        RequestDC::set('lang', $lang);
        RequestDC::set('auth_token', $auth_token);

        RequestDC::set('method', $this->method);
        RequestDC::set('url', $this->url);
    }
}
  


