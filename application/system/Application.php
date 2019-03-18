<?php

namespace App\System;

class Application {
    private $uri = '/';
    private $url;

    public function __construct()
    {
        $this->processURL();
        var_dump($this);
    }

    private function processURL()
    {
        $_GET['url'] = isset($_GET['url']) ? filter_var(trim($_GET['url'], "/"), FILTER_SANITIZE_URL) : "";
        $url = explode("/", $_GET['url']);

        // TODO : check isset Language and Admin Requested in url
        $sUrl = $_GET['url'];
        // TODO : process for language Request and Admin Request in url
        $this->uri = !empty($sUrl) ? $sUrl : $this->uri;
        $this->url = trim(URL,"/") . $_SERVER['REQUEST_URI'];

    }


}