<?php

namespace App\Core;

class Router {
    private $routes = [];

    public function add(string $method, string $path, array $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get(string $path, array $handler) {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler) {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri) {
        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $route['path']);
                $pattern = "#^" . $pattern . "$#";

                if (preg_match($pattern, $path, $matches)) {
                    $handler = $route['handler'];
                    $controllerName = $handler[0];
                    $methodName = $handler[1];

                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    $controller = new $controllerName();
                    
                    $request = new Request();
                    $response = new Response();

                    return call_user_func_array([$controller, $methodName], array_merge([$request, $response], array_values($params)));
                }
            }
        }

        $response = new Response();
        return $response->json(['error' => 'Not Found'], 404);
    }
}
