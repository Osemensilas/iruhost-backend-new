<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function get(string $path, array|callable $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    public function post(string $path, array|callable $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    public function put(string $path, array|callable $callback) {
        $this->routes['PUT'][$path] = $callback;
    }

    public function delete(string $path, array|callable $callback) {
        $this->routes['DELETE'][$path] = $callback;
    }

    public function resolve(string $requestUri, string $method) {
        // Clean up the URI
        $requestUri = parse_url($requestUri, PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/');
        
        // If empty, set to root
        if (empty($requestUri)) {
            $requestUri = '/';
        }

        // Log for debugging
        error_log("Router - Method: $method, URI: $requestUri");

        // First, check for exact match
        if (isset($this->routes[$method][$requestUri])) {
            error_log("Router - Exact match found");
            return $this->executeCallback($this->routes[$method][$requestUri], []);
        }

        // Then check for dynamic routes
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $callback) {
                // Convert route pattern {param} to regex
                $pattern = $this->convertRouteToRegex($route);
                
                error_log("Router - Testing pattern: $pattern against URI: $requestUri");

                if (preg_match($pattern, $requestUri, $matches)) {
                    // Remove the full match, keep only captured groups
                    array_shift($matches);
                    
                    error_log("Router - Match found! Parameters: " . json_encode($matches));
                    return $this->executeCallback($callback, $matches);
                }
            }
        }

        // No route found
        error_log("Router - No route matched for: $method $requestUri");
        http_response_code(404);
        echo json_encode([
            'error' => 'Route not found',
            'method' => $method,
            'uri' => $requestUri,
            'available_routes' => array_keys($this->routes[$method] ?? [])
        ]);
        exit;
    }

    private function convertRouteToRegex(string $route) {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $route);
        
        // Replace {param} with capture group
        // Allows: letters, numbers, hyphens, underscores
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $pattern);
        
        // Add start and end anchors
        return '/^' . $pattern . '$/';
    }

    private function executeCallback(array|callable $callback, array $params) {
        try {
            if (is_array($callback)) {
                [$controllerClass, $method] = $callback;
                
                // Check if controller class exists
                if (!class_exists($controllerClass)) {
                    error_log("Router - Controller class not found: $controllerClass");
                    http_response_code(500);
                    echo json_encode(['error' => 'Controller not found', 'controller' => $controllerClass]);
                    exit;
                }
                
                // Instantiate controller
                $controller = new $controllerClass();
                
                // Check if method exists
                if (!method_exists($controller, $method)) {
                    error_log("Router - Method not found: $method in $controllerClass");
                    http_response_code(500);
                    echo json_encode(['error' => 'Method not found', 'method' => $method]);
                    exit;
                }
                
                // Call the method with parameters
                return call_user_func_array([$controller, $method], $params);
            }
            
            // If callback is a closure
            return call_user_func_array($callback, $params);
            
        } catch (\Exception $e) {
            error_log("Router - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}