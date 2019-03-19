<?php


namespace App\lib;


class Router
{
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function dispatch() {
        $uri = $this->registry->Application->getUri();

        $action = new Action($uri);
        $action->execute($this->registry);
    }


}