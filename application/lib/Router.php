<?php


namespace App\lib;


class Router
{
    private $registry;
    private $preRoutes = [];
    private $routes = [];
    private $funcRoutes = [];
    private $runRoutes = [];
    /**
     * @var bool
     */
    private $isResponsed = false;
    private $data = [];

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function dispatch() {
        if(count($this->preRoutes) > 0) {
            foreach ($this->preRoutes as $preRoute) {
                /** @var Action $preAction */
                $preAction = $preRoute['action'];
                $result = $preAction->execute($this->registry);
                if($result instanceof Action) {
                    $action = $result;
                }
            }
        }
        if(empty($action)) {
            $uri = $this->registry->Application->getUri();
            $method = $this->getRequestMethod();
            if(isset($this->routes[$method])) {
                $this->handle($this->routes[$method], $uri);
            }
            foreach ($this->funcRoutes as $funcRoute) {
                $result = call_user_func_array($funcRoute['fn'], $funcRoute['params']);
                if($result instanceof Action) {
                    $action = $result;
                }
                if($funcRoute['mainOutPut']) {
                    $this->isResponsed = true;
                }
            }
            if(empty($action) || !isset($action) ) {

                foreach ($this->runRoutes as $runRoute) {
                    /** @var Action $runAction */
                    $runAction = $runRoute['action'];
                    foreach ($this->data as $key => $value) {
                        $runAction->setData($key, $value);
                    }
                    if(!$this->isResponsed) {
                        $result = $runAction->execute($this->registry);
                        if($result instanceof Action) {
                            $action = $result;
                        }else if(is_array($result)) {
                            $this->data = array_merge($this->data, $result);
                        }
                        if($runRoute['mainOutPut']) {
                            $this->isResponsed = true;
                        }
                    }
                }

                if(!$this->isResponsed && !isset($action)) {
                    if($uri == "" || $uri == "/") {
                        $uri = $this->registry->Config->get('default_route');// TODO: Set default uri with Config class
                    }
                    $action = new Action($uri);
                    if(!$action->getMethod()) {
                        $action->setMethod('index');
                    }
                    if(!$action->isStatus()) {
                        $action = new Action("error/notFound", "web"); // TODO: set Error uri with Config Class
                    }
                }
            }

        }
        if(!empty($action)) {
            foreach ($this->data as $key => $value) {
                $action->setData($key, $value);
            }
            while($action instanceof Action) {
                $action = $action->execute($this->registry, array(
                    'error_route' => "error/notFound",
                    "error_pre_route" => "web"
                ));
            }
        }
    }

    public function addPreRoute(Action $action, array $params = [], $mainOutPut = false) {
        $this->preRoutes[] = array(
            'action'    => $action,
            'params'    => $params,
            'mainOutPut'=> $mainOutPut
        );
    }

    public function get($pattern, $fn, $preRoute = false, $mainOutPut = false) {
        $this->match("GET", $pattern, $fn, $preRoute, $mainOutPut);
    }

    public function post($pattern, $fn, $preRoute = false, $mainOutPut = false) {
        $this->match("POST", $pattern, $fn, $preRoute, $mainOutPut);
    }

    public function all($pattern, $fn, $preRoute = false, $mainOutPut = false) {
        $this->match("GET|POST", $pattern, $fn, $preRoute, $mainOutPut);
    }

    public function match($methods, $pattern, $fn, $preRoute = false, $mainOutPut = false) {
        $pattern = rtrim($pattern, '/');
        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = array(
                'pattern'   => $pattern,
                'fn'        => $fn,
                'preRoute'  => $preRoute ? $preRoute : '',
                'mainOutPut'=> $mainOutPut,
            );
        }

    }

    private function handle($routes, $uri, $quitAfterRun = false)
    {
        // Counter to keep track of the number of routes we've handled
        $numHandled = 0;
        // Loop all routes
        foreach ($routes as $route) {
            // Replace all curly braces matches {} into word patterns (like Laravel)
            $route['pattern'] = preg_replace('/\/{(.*?)}/', '/(.*?)', $route['pattern']);
            // we have a match!

            if (preg_match_all('#^' . $route['pattern'] . '$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {
                // Rework matches to only contain the matches, not the orig string
                $matches = array_slice($matches, 1);
                // Extract the matched URL parameters (and only the parameters)
                $params = array_map(function ($match, $index) use ($matches) {
                    // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                    if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                        return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    } // We have no following parameters: return the whole lot
                    return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                }, $matches, array_keys($matches));

                // Call the handling function with the URL parameters if the desired input is callable
                $this->invoke($route['fn'], $route['preRoute'], $route['mainOutPut'], $params);
                ++$numHandled;
                // If we need to quit, then quit
                if ($quitAfterRun) {
                    break;
                }
            }
        }
        // Return the number of routes handled
        return $numHandled;
    }
    private function invoke($fn, $preRoute, $mainOutPut, $params) {
        if(is_callable($fn)) {
            $this->funcRoutes[] = array(
                'fn'    => $fn,
                'mainOutPut' => $mainOutPut,
                'params'    => $params
            );

        }else {
            $route = $fn;
            $action = new Action($route, $preRoute);
            if($action->isStatus()) {
                $action->setData('params', $params);
                $this->runRoutes[] = array(
                    'action'    =>  $action,
                    'mainOutPut'=> $mainOutPut,
                );
            }
        }
    }
    public function getRequestMethod()
    {
        // Take the method as found in $_SERVER
        $method = $_SERVER['REQUEST_METHOD'];
        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
            $method = 'GET';
        }
        // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                $method = $headers['X-HTTP-Method-Override'];
            }
        }
        return $method;
    }
    public function getRequestHeaders()
    {
        $headers = [];
        // If getallheaders() is available, use that
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // getallheaders() can return false if something went wrong
            if ($headers !== false) {
                return $headers;
            }
        }
        // Method getallheaders() not available or went wrong: manually extract 'm
        foreach ($_SERVER as $name => $value) {
            if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
                $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }


}