<?php


namespace App\lib;


class Config
{
    private $data = [];

    public function get($value) {
        return isset($this->data[$value]) ? $this->data[$value] : null;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function load($file_name) {
        $file = CONFIG_PATH . DS . $file_name . '.php';
        if(file_exists($file)) {
            $_ = [];
            require $file;
            $this->data = array_merge($this->data, $_);
        }else {
            throw new \Exception("Config file couldn't found {$file_name}");
        }
    }

}