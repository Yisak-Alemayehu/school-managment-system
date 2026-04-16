<?php
/**
 * Simple Router
 */

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    public function post(string $path, callable $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    public function dispatch(?string $method = null, ?string $uri = null): void
    {
        $method = strtoupper($method ?? $_SERVER['REQUEST_METHOD']);
        
        // Determine URI: strip base path (landing-page subfolder)
        $uri = $uri ?? $_SERVER['REQUEST_URI'];
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Strip the script directory from URI for subfolder support
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptDir !== '/' && $scriptDir !== '\\') {
            $uri = substr($uri, strlen($scriptDir));
        }
        $uri = trim($uri, '/');

        // Exact match
        if (isset($this->routes[$method][$uri])) {
            call_user_func($this->routes[$method][$uri]);
            return;
        }

        // Pattern matching with parameters
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func_array($handler, $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        include __DIR__ . '/../views/errors/404.php';
    }
}
