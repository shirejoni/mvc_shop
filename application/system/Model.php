<?php


namespace App\system;


use App\Lib\Registry;

class Model
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function __get($name)
    {
        return $this->registry->{$name};
    }


}