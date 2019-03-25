<?php

namespace App\System;

use App\lib\Action;
use App\lib\Config;
use App\Lib\Database;
use App\Lib\Registry;
use App\lib\Request;
use App\lib\Response;
use App\lib\Router;
use App\Lib\Session;
use App\model\Language;

class Application {
    private const ADMIN_ALIAS = ADMIN_ALIAS_NAME;
    private $isAdminRequested = false;
    private $uri = '/';
    private $url;
    private $registry;
    private $language_id = false;
    private $requestedUrl;


    public function __construct()
    {
        require_once SYSTEM_PATH . DS . 'common_function.php';
        $this->registry = new Registry();
        $this->registry->Application = $this;
        $Response = new Response();
        $this->registry->Response = $Response;
        $this->registry->Database = new Database(DB_SERVER, DB_NAME, DB_USER, DB_PASSWORD);
        $this->registry->Language = new Language($this->registry);
        $this->processURL();
        session_set_save_handler(new Session($this->registry->Database));
        session_start(array(
            'use_strict_mode' => '1',
            'cookie_httponly' => '1'
        ));

        if(!$this->language_id) {
            // TODO: Load Language ID with Cookie and Session
        }

        if($this->isAdminRequested) {
            require_once ADMIN_PATH . DS .'config/admin_constants.php';
        }else {
            require_once WEB_PATH . DS . 'config/web_constants.php';
        }
        $Config = new Config();
        $this->registry->Config = $Config;
        $Config->load(MAIN_CONFIG_FILE);

        $Router = new Router($this->registry);
        $this->registry->Router = $Router;
        $preActions = $Config->get('pre_actions');
        if(count($preActions) > 0) {
            foreach ($preActions as $preAction) {

                $Router->addPreRoute(new Action($preAction));
            }
        }

        $this->registry->Request = new Request($this->registry, $this->uri, CONTROLLER_PATH);
        if($this->language_id) {
            $this->registry->Language->setLanguageByID($this->language_id);
        }


        $this->registry->Language->load($Config->get('default_language_file_path'));

        $Router->dispatch();

        $Response->outPut();

    }

    private function processURL()
    {
        $_GET['url'] = isset($_GET['url']) ? filter_var(trim($_GET['url'], "/"), FILTER_SANITIZE_URL) : "";
        $url = explode("/", $_GET['url']);

        $languages = $this->registry->Language->getLanguages();
        if(array_key_exists($url[0], $languages)) {
            $this->language_id = $languages[$url[0]]['language_id'];
            $languageCode = array_shift($url);
        }
        if($url[0] == self::ADMIN_ALIAS) {
            $this->isAdminRequested = true;
            array_shift($url);
        }

        $sUrl = $_GET['url'];

        if(isset($languageCode)) {
            $sUrl = substr($sUrl, strlen($languageCode) + 1);
        }
        if($this->isAdminRequested) {
            $sUrl = substr($sUrl, strlen(self::ADMIN_ALIAS) + 1);
        }

        $this->uri = !empty($sUrl) ? $sUrl : $this->uri;
        $this->url = URL . $_GET['url'];
        $this->requestedUrl = trim(URL,"/") . $_SERVER['REQUEST_URI'];

    }

    /**
     * @return bool
     */
    public function isAdminRequested(): bool
    {
        return $this->isAdminRequested;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getRequestedUrl()
    {
        return $this->requestedUrl;
    }

}