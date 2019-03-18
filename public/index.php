<?php

define("DS", DIRECTORY_SEPARATOR);
define("ROOT_PATH", dirname(__DIR__));
define("APP_PATH", ROOT_PATH . DS . 'application');

require_once APP_PATH . DS . 'config/constants.php';
require_once ROOT_PATH . DS . 'vendor/autoload.php';

$application = new \App\System\Application();
