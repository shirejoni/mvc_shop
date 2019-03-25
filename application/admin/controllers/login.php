<?php

namespace App\Admin\Controller;

use App\lib\Config;
use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Language;
use App\model\User;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Language Language
 * @property Config Config
 */
class ControllerLogin extends Controller {

    public function index() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($_SESSION['user']) && !empty($_SESSION['user']['email']) && $_SESSION['login_status'] == LOGIN_STATUS_FORM_LOGIN) {
            $token = generateToken();
            $_SESSION['token'] = $token;
            $_SESSION['login_time_expiry'] = time() + $this->Config->get('max_time_inactive_session_time');
            header("location:" . ADMIN_URL . '?token=' . $token);
            exit();
        }
        if(!empty($this->Request->post['email']) && !empty($this->Request->post['password'])) {
            $email = $this->Request->post['email'];
            $password = $this->Request->post['password'];
            if(!Validate::emailValid($email)) {
                $error = true;
                $messages[] = $this->Language->get('error_invalid_email');
            }
            if(!Validate::passwordValid($password)) {
                $error = true;
                $messages[] = $this->Language->get('error_invalid_password');
            }

            if(!$error) {
                /** @var User $User */
                $User = $this->load('User', $this->registry);
                if($result = $User->getUserByEmail($email)) {
                    if(password_verify($password, $result['password'])) {
                        $ip = get_ip_address();
                        $option = [];
                        // TODO : get Valid IP with validate_ip in production
                        if($ip) {
                            $option['ip'] = $ip;
                        }
                        $User->login($option);
                        $token = generateToken();
                        $_SESSION['token'] = $token;
                        $_SESSION['token_time_expiry'] = time() + $this->Config->get('token_max_life_time');
                        $_SESSION['login_status'] = LOGIN_STATUS_FORM_LOGIN;
                        $_SESSION['login_time'] = time();
                        $_SESSION['login_time_expiry'] = time() + $this->Config->get('max_time_inactive_session_time');
                        $_SESSION['ip'] = $ip;
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                        $json = array(
                            'status' => 1,
                            'messages' => [$this->Language->get('success_message')],
                            'redirect' => $this->Application->getUrl() . '?token=' . $token,
                        );
                    }else {
                        $error = true;
                        $messages[] = $this->Language->get('error_no_such_user');
                    }
                }else {
                    $error = true;
                    $messages[] = $this->Language->get('error_no_such_user');
                }
            }

            if($error) {
                $json = array(
                    'status' => 0,
                    'messages' => $messages
                );
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            $this->Response->setOutPut($this->render("login/index", $data));
        }
    }

}