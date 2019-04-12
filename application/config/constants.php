<?php

define("PUB_PATH", ROOT_PATH . DS . 'public');
define("ADMIN_PATH", APP_PATH . DS . 'admin');
define("WEB_PATH", APP_PATH . DS . 'web');
define("LIB_PATH", APP_PATH . DS . 'lib');
define("SYSTEM_PATH", APP_PATH . DS . 'system');
define("MODEL_PATH", APP_PATH . DS . 'model');
define("LANGUAGE_PATH", APP_PATH . DS . 'language');
define("ASSETS_PATH", PUB_PATH . DS . 'assets');

define("MODEL_NAMESPACE", 'App\\Model');
define("DB_SERVER", "localhost");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_NAME", "myshop");
define("DEBUG_MODE", true);// DEVELOPMENT True Production False

define("URL", "http://myshop.test/");
define("ADMIN_URL", "http://myshop.test/admin/");
define("ASSETS_URL", "http://myshop.test/assets/");
define("LOGIN_STATUS_FORM_LOGIN", '1');
define("ADMIN_ALIAS_NAME", "admin");
define("MAIN_CONFIG_FILE", 'config');

define('DEFAULT_LANGUAGE_DIR', 'fa');
define('DEFAULT_LANGUAGE_CODE', 'fa');
define("CKFINDER_ROUTE", 'ckfinder/ckfinder');