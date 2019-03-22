<?php

namespace App\Admin\Controller;

use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 */
class ControllerLogin extends Controller {

    public function index() {
        $data = [];
        $messages = [];
        $error = false;
        if(!empty($this->Request->post['email']) && !empty($this->Request->post['password'])) {
            $email = $this->Request->post['email'];
            $password = $this->Request->post['password'];
            if(!Validate::emailValid($email)) {
                $error = true;
                $messages[] = 'ایمیل معتبر نمی باشد!';
            }
            if(!Validate::passwordValid($password)) {
                $error = true;
                $messages[] = 'رمزعبور معتبر نمی باشد!';
            }

            if(!$error) {
                $json = array(
                    'status' => 1,
                    'messages' => ['ورود با موفقیت انجام شد']
                );
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