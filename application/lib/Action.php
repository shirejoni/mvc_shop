<?php


namespace App\lib;


use mysql_xdevapi\Exception;

class Action
{
    private $controller_path;
    private $controller_namespace;
    private $method;
    private $file_path;
    private $route;
    private $status = false;

    public function __construct($route)
    {
        $this->controller_path = CONTROLLER_PATH;
        $this->controller_namespace = CONTROLLER_NAMESPACE;

        $parts = explode("/", $route);
        while ($parts) {
            $file = $this->controller_path . DS . implode(DS, $parts) . ".php";
            if(file_exists($file)) {
                $this->file_path = $file;
                $this->route = implode('/',$parts);
                break;
            }else {
                $this->method = array_pop($parts);
            }
        }

        if(!empty($this->route) && !empty($this->method)) {
            $this->status = true;
        }

    }

    public function execute($registry) {
        if(substr($this->method, 0, 2) == "__") {
            throw new \Exception("Calling Magic Method is not Allowed");
        }
        if(file_exists($this->file_path)) {
            require_once $this->file_path;
            $className = "\\" . $this->controller_namespace . "\\Controller";// Home
            preg_replace_callback("/[a-zA-Z0-9]+/", function ($matches) use (&$className) {
                $className .= ucfirst($matches[0]);
            }, $this->route);
            // TODO : Set Data
            $class = new $className($registry);
            if(method_exists($class, $this->method)) {
                return call_user_func_array([$class, $this->method], []);
            }else {
                throw new \Exception("No such Method found in controller {$this->route} : {$this->method}");
            }
        }else {
            throw new \Exception("No Such controller found {$this->route} : {$this->method}");
        }


    }


}