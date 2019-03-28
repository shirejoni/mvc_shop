<?php

namespace App\Admin\Controller;

use App\lib\Response;
use App\system\Controller;

/**
 * @property Response Response
 */
class ControllerCkfinder extends Controller {

    public function ckfinder() {
        require_once LIB_PATH . DS . 'ckfinder/connector.php';
    }

    public function index() {
        $this->Response->setOutPut($this->render('ckfinder/index'));
    }

}