<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Customer;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Customer Customer
 */
class ControllerUser extends Controller {

    public function index() {
        $this->Response->setOutPut($this->render('user/index'));
    }


    public function edit() {
        $data = [];
        $error = false;
        $messages = [];
        if($this->Customer) {

            if(isset($this->Request->post['customer-post'])) {
                if(empty($this->Request->post['customer-first-name'])) {
                    $error = true;
                    $messages[] = $this->Language->get('error_first_name_empty');
                }else if($this->Customer->getFirstName() != $this->Request->post['customer-first-name']) {
                    $data['first_name'] = $this->Request->post['customer-first-name'];
                }
                if(empty($this->Request->post['customer-last-name'])) {
                    $error = true;
                    $messages[] = $this->Language->get('error_last_name_empty');
                }else if($this->Customer->getFirstName() != $this->Request->post['customer-last-name']) {
                    $data['last_name'] = $this->Request->post['customer-last-name'];
                }
                if(empty($this->Request->post['customer-mobile']) || !Validate::mobileValid($this->Request->post['customer-mobile'])) {
                    $error = true;
                    $messages[] = $this->Language->get('error_mobile_empty');
                }else if($this->Customer->getMobile() != $this->Request->post['customer-mobile']) {
                    $data['mobile'] = $this->Request->post['customer-mobile'];
                }
                $json = [];
                if(!$error) {
                    $this->Customer->edit($this->Customer->getCustomerId(), $data);
                    $json['status'] = 1;
                    $json['messages'] = [$this->Language->get('success_message')];
                    $json['redirect'] = URL . 'user/index';
                }
                if($error) {
                    $json['status'] = 1;
                    $json['messages'] = $messages;
                }
                $this->Response->setOutPut(json_encode($json));
            }else {
                $data['Customer'] = $this->Customer;
                $this->Response->setOutPut($this->render('user/edit', $data));
            }
            return;
        }
        return new Action('error/notFound', 'web');
    }
}