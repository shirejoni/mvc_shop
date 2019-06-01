<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Config;
use App\lib\Request;
use App\system\Controller;

/**
 * @property Config Config
 * @property Request Request
 */
class ControllerInitLogin extends Controller {
    public function index() {

        $route = $this->Application->getUri();
        $ignore_route = array(
            'login/index',
            'login/forget',
            'login/reset',
            'checkout/index'
        );
        if(!in_array($route, $ignore_route) && empty($_SESSION['customer'])) {

            if(strpos($route, 'checkout') !== false) {

                $action = new Action('checkout/checkout/index');
            }else {
                $action = new Action('login/index');
            }
            return $action;
        }

        if(isset($_SESSION['customer'])) {
            if($_SESSION['login_time_expiry'] < time()) {
                unset($_SESSION['customer'], $_SESSION['login_time'], $_SESSION['login_time_expiry'], $_SESSION['user_agent'], $_SESSION['ip']);
                $action = new Action('login/index');
                return $action;
            }else {
                $_SESSION['login_time_expiry'] = time() + $this->Config->get('max_time_inactive_session_time');
            }

            if($_SESSION['user_agent'] != $_SERVER['HTTP_USER_AGENT'] || $_SESSION['ip'] != get_ip_address()) {
                unset($_SESSION['customer'], $_SESSION['login_time'], $_SESSION['login_time_expiry'], $_SESSION['user_agent'], $_SESSION['ip']);
                $action = new Action('login/index');
                return $action;
            }
        }

        $ignore_route = array(
            'login/index',
            'login/forget',
            'login/reset',
            'error/notFound',
            'error/permission',
            'checkout/index',
            'checkout/cart'
        );
//        if(!in_array($route, $ignore_route) && (!isset($_SESSION['token']) || empty($this->Request->get['token'])
//                || $_SESSION['token'] != $this->Request->get['token'] || !isset($_SESSION['token_time_expiry'])
//                || $_SESSION['token_time_expiry'] < time())) {
//            unset($_SESSION['customer'], $_SESSION['login_time'], $_SESSION['login_time_expiry'], $_SESSION['user_agent'], $_SESSION['ip']);
//            $action = new Action('login/index');
//            return $action;
//        }

        if(!isset($this->Request->post['post'])) {
            $token = generateToken();
            $_SESSION['token'] = $token;
            $_SESSION['token_time_expiry'] = time() + $this->Config->get('token_max_life_time');
        }

    }
}
