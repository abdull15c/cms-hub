<?php
namespace Src\Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch($uri, $method) {
        $uri = strtok($uri, '?');
        
        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\//', '\\/', $route['path']);
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $pattern);
            $pattern = '/^' . $pattern . '$/';
            
            if ($route['method'] == $method && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $controllerName = "Src\\Controllers\\" . $route['controller'];
                $controller = new $controllerName();
                call_user_func_array([$controller, $route['action']], array_values($params));
                return;
            }
        }
        
        // CUSTOM 404 VIEW
        http_response_code(404);
        // We manually include header/footer for the 404 view
        $langCode = \Src\Services\SessionService::get('lang', 'ru');
        $lang = [];
        $langFile = __DIR__ . '/../../languages/' . $langCode . '.php';
        if(file_exists($langFile)) $lang = require $langFile;
        $t = static function (string $key, string $default = '') use ($lang) {
            return $lang[$key] ?? $default;
        };
        
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/errors/404.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }
}
