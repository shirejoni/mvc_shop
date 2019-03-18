<?php

namespace App\System;

class Application {
    private const ADMIN_ALIAS = ADMIN_ALIAS_NAME;
    private $isAdminRequested = false;
    private $uri = '/';
    private $url;

    public function __construct()
    {
        $this->processURL();

        if($this->isAdminRequested) {
            require_once ADMIN_PATH . DS .'config/admin_constants.php';
        }else {
            require_once WEB_PATH . DS . 'config/web_constants.php';
        }

        require_once CONTROLLER_PATH . DS . 'home.php';
        var_dump($this);

    }

    private function processURL()
    {
        $_GET['url'] = isset($_GET['url']) ? filter_var(trim($_GET['url'], "/"), FILTER_SANITIZE_URL) : "";
        $url = explode("/", $_GET['url']);

        // TODO : check isset Language in url
        if($url[0] == self::ADMIN_ALIAS) {
            $this->isAdminRequested = true;
            array_shift($url);
        }

        $sUrl = $_GET['url'];
        // TODO : process for language Request in url

        if($this->isAdminRequested) {
            $sUrl = substr($sUrl, strlen(self::ADMIN_ALIAS) + 1);
        }

        $this->uri = !empty($sUrl) ? $sUrl : $this->uri;
        $this->url = trim(URL,"/") . $_SERVER['REQUEST_URI'];

    }


}