<?php


namespace App\lib;


class Router
{
    private $registry;
    private $preRoutes = [];

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
            if($uri == "" || $uri == "/") {
                $uri = "home/index";// TODO: Set default uri with Config class
            }
            $action = new Action($uri);
            if(!$action->isStatus()) {
                $action = new Action("error/notFound", "web"); // TODO: set Error uri with Config Class
            }
        }
        do {
            $action = $action->execute($this->registry, array(
                'error_route' => "error/notFound",
                "error_pre_route" => "web"
            ));
        }while($action instanceof Action);
    }

    public function addPreRoute(Action $action, array $params = [], $mainOutPut = false) {
        $this->preRoutes[] = array(
            'action'    => $action,
            'params'    => $params,
            'mainOutPut'=> $mainOutPut
        );
    }


}