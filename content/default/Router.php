<?php defined('_DOIT') or die('Restricted access');

class Router {
    private static $routes = [];
    private static $currentRoute = null;
    private static $parameters = [];
    
    /**
     * Register a GET route
     */
    public static function get($pattern, $callback, $name = null) {
        self::addRoute('GET', $pattern, $callback, $name);
    }
    
    /**
     * Register a POST route
     */
    public static function post($pattern, $callback, $name = null) {
        self::addRoute('POST', $pattern, $callback, $name);
    }
    
    /**
     * Register any HTTP method route
     */
    public static function any($pattern, $callback, $name = null) {
        self::addRoute('ANY', $pattern, $callback, $name);
    }
    
    /**
     * Add route to routes array
     */
    private static function addRoute($method, $pattern, $callback, $name = null) {
        self::$routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
            'name' => $name
        ];
    }
    
    /**
     * Dispatch the current request
     */
    public static function dispatch() {
        global $t_mp, $_COOKIE;
        
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = self::getCurrentUri();
        
        foreach (self::$routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $requestMethod) {
                continue;
            }
            
            $pattern = $route['pattern'];
            $callback = $route['callback'];
            
            // Convert route pattern to regex
            $regex = self::patternToRegex($pattern);
            
            if (preg_match($regex, $requestUri, $matches)) {
                // Remove full match from matches
                array_shift($matches);
                
                // Store current route info
                self::$currentRoute = $route;
                self::$parameters = $matches;
                
                // Execute callback
                if (is_callable($callback)) {
                    return call_user_func_array($callback, $matches);
                } elseif (is_string($callback)) {
                    // If callback is a file path, include it
                    if (file_exists($callback)) {
                        return include $callback;
                    }
                }
                
                return true;
            }
        }
        
        return false; // No route matched
    }
    
    /**
     * Get current URI from global $t_mp
     */
    private static function getCurrentUri() {
        global $t_mp, $_COOKIE;
        
        $lang = $_COOKIE['lang'] ?? 'ro';
        $uri = '/' . $lang;
        
        if (isset($t_mp[2]) && !empty($t_mp[2])) {
            $uri .= '/' . $t_mp[2];
            
            if (isset($t_mp[3]) && !empty($t_mp[3])) {
                $uri .= '/' . $t_mp[3];
                
                if (isset($t_mp[4]) && !empty($t_mp[4])) {
                    $uri .= '/' . $t_mp[4];
                }
            }
        }
        
        return $uri;
    }
    
    /**
     * Convert route pattern to regex
     */
    private static function patternToRegex($pattern) {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);
        
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^\/]+)', $pattern);
        
        // Convert {param?} to optional named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', '([^\/]*)', $pattern);
        
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Generate URL for named route
     */
    public static function route($name, $parameters = []) {
        foreach (self::$routes as $route) {
            if ($route['name'] === $name) {
                $url = $route['pattern'];
                
                // Replace parameters in URL
                foreach ($parameters as $key => $value) {
                    $url = str_replace('{' . $key . '}', $value, $url);
                    $url = str_replace('{' . $key . '?}', $value, $url);
                }
                
                // Remove optional parameters that weren't provided
                $url = preg_replace('/\{[^}]+\?\}/', '', $url);
                
                return $url;
            }
        }
        
        return '#';
    }
    
    /**
     * Get current route parameters
     */
    public static function getParameters() {
        return self::$parameters;
    }
    
    /**
     * Get current route info
     */
    public static function getCurrentRoute() {
        return self::$currentRoute;
    }
    
    /**
     * Check if current route matches pattern
     */
    public static function is($pattern) {
        if (!self::$currentRoute) {
            return false;
        }
        
        return self::$currentRoute['pattern'] === $pattern;
    }
    
    /**
     * Redirect to URL
     */
    public static function redirect($url, $code = 302) {
        header("Location: $url", true, $code);
        exit;
    }
    
    /**
     * Get all registered routes
     */
    public static function getRoutes() {
        return self::$routes;
    }
}
