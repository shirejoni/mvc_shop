<?php

namespace App\Admin\Controller;

use App\lib\Action;
use App\lib\Config;
use App\system\Controller;

/**
 * @property Config Config
 */
class ControllerInitLogin extends Controller {
    public function index() {
        if(isset($_SESSION['user'])) {
            if($_SESSION['login_time_expiry'] < time()) {
                unset($_SESSION['user'], $_SESSION['login_time'], $_SESSION['login_time_expiry'], $_SESSION['user_agent'], $_SESSION['ip']);
                $action = new Action('login/index');
                return $action;
            }else {
                $_SESSION['login_time_expiry'] = time() + $this->Config->get('max_time_inactive_session_time');
            }

            if($_SESSION['user_agent'] != $_SERVER['HTTP_USER_AGENT'] || $_SESSION['ip'] != get_ip_address()) {
                unset($_SESSION['user'], $_SESSION['login_time'], $_SESSION['login_time_expiry'], $_SESSION['user_agent'], $_SESSION['ip']);
                $action = new Action('login/index');
                return $action;
            }
        }
    }
}
