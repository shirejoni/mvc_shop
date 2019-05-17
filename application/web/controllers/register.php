<?php

namespace App\Web\Controller;

use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Customer;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 */
class ControllerRegister extends Controller {

    public function index() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->post['register-post'])) {
            $first_name = isset($this->Request->post['first_name']) ? $this->Request->post['first_name'] : false;
            $last_name = isset($this->Request->post['last_name']) ? $this->Request->post['last_name'] : false;
            $email = isset($this->Request->post['email']) ? $this->Request->post['email'] : false;
            $password = isset($this->Request->post['password']) ? $this->Request->post['password'] : false;
            $mobile = isset($this->Request->post['mobile']) ? $this->Request->post['mobile'] : false;
            if(!$this->registry->has('Validate')) {
                $this->registry->Validate = new Validate();
            }
            /** @var Validate $Validate */
            $Validate = $this->Validate;
            if(!$email || !$Validate::emailValid($email)) {
                $error = true;
                $messages[] = $this->Language->get('error_email_invalid');
            }
            if(!$password || !$Validate::passwordValid($password)) {
                $error = true;
                $messages[] = $this->Language->get('error_password_invalid');
            }
            if(!$mobile || !$Validate::mobileValid($mobile)) {
                $error = true;
                $messages[] = $this->Language->get('error_mobile_empty');
            }
            if(empty($first_name)) {
                $error = true;
                $messages[] = $this->Language->get('error_first_name_empty');
            }
            if(empty($last_name)) {
                $error = true;
                $messages[] = $this->Language->get('error_last_name_empty');
            }
            $json = [];
            if(!$error) {
                /** @var Customer $Customer */
                $Customer = $this->load("Customer", $this->registry);
                if(!$Customer->getCustomerByEmail($email) && !$Customer->getCustomerByMobile($mobile)) {
                    $data['email'] = $email;
                    $data['first_name'] = $first_name;
                    $data['last_name'] = $last_name;
                    $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                    $data['mobile'] = $mobile;
                    $Customer->insertCustomer($data);
                    $json['status'] = 1;
                    $json['messages'] = [$this->Language->get('success_message')];
                    $json['redirect'] = URL . 'user/index';
                }else {
                    $error = true;
                    $messages[] = $this->Language->get('error_exist_such_user');
                }
            }
            if($error){
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            $this->Response->setOutPut($this->render('register/index'));
        }
    }

}