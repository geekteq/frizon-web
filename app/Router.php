<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('GET', $pattern, $controller, $method);
    }

    public function post(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('POST', $pattern, $controller, $method);
    }

    public function put(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('PUT', $pattern, $controller, $method);
    }

    public function delete(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('DELETE', $pattern, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $pattern, string $controller, string $method): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method'     => $httpMethod,
            'pattern'    => '#^' . $regex . '$#',
            'controller' => $controller,
            'action'     => $method,
        ];
    }

    public function dispatch(string $method, string $uri, PDO $pdo, array $config): void
    {
        // Support PUT/DELETE via _method field in POST forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Treat HEAD like GET and suppress the response body.
        $isHeadRequest = $method === 'HEAD';
        if ($isHeadRequest) {
            $method = 'GET';
            ob_start();
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $controllerFile = __DIR__ . '/Controllers/' . $route['controller'] . '.php';
                require_once $controllerFile;

                $controller = new $route['controller']($pdo, $config);
                $controller->{$route['action']}($params);

                if ($isHeadRequest) {
                    ob_end_clean();
                }
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 — Sidan hittades inte</h1>';

        if ($isHeadRequest) {
            ob_end_clean();
        }
    }
}
