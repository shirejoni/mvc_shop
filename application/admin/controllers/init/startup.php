<?php

namespace App\Admin\Controller;

use App\lib\Config;
use App\Lib\Database;
use App\system\Controller;

/**
 * @property Database Database
 * @property Config Config
 */
class ControllerInitStartup extends Controller {
    public function init() {
        $configs = $this->Database->getRows("SELECT * FROM config");

        foreach ($configs as $config) {
            if($config['serialized'] == 1) {
                $this->Config->set($config['key'], unserialize($config['value']));
            }else {
                $this->Config->set($config['key'], $config['value']);
            }
        }
    }
}
