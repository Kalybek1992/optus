<?php

namespace Source\Project\Routers;

use Source\Project\Viewer\ApiViewer;

class IndexRouter
{
    /**
     * @param string $uri
     * @return array
     */
    public static function route(string $uri): array|string
    {
        $namespace = 'Source\\Project\\Controllers\\';

        $explode = explode("/", $uri);
        $class = $explode[0];

        $class_name = $namespace . ucfirst($class) . "Controller";
        $method = preg_replace("#\?.*#", "", $explode[1] ?? '');

        if (class_exists($class_name)) {
            $all_methods = get_class_methods($class_name);

            foreach ($all_methods as $existing_method) {
                if (strtolower($existing_method) == strtolower($method)) {

                    $controller = new $class_name();

                    return $controller->$existing_method();
                }
            }
        }

       return ApiViewer::getErrorBody(['value' => 'bad_method']);
    }
}
