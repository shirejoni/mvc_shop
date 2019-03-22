<?php


namespace App\lib;


class Request
{
    public $get;
    public $post;

    public function __construct(Registry $registry, $uri, $controller_path)
    {
        $this->post = $_POST;
        $parts = explode('/', $uri);
        $file = $controller_path;
        foreach($parts as $part) {
            $file .= DS . $part;
            if(is_dir($file)) {
                array_shift($parts);
            }else if(is_file($file . '.php')) {
                array_shift($parts);
                array_shift($parts);
                break;
            }
        }
        $this->get = array_merge($_GET, $parts);

    }


}