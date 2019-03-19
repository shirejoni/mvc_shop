<?php

namespace App\Lib;

class Registry
{
    private $data = [];

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : false;
    }


    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function has($name) {
        return isset($this->data[$name]);
    }


}