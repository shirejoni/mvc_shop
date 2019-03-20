<?php

namespace App\Admin\Controller;

use App\lib\Response;
use App\system\Controller;

/**
 * @property Response Response
 */
class ControllerLogin extends Controller {

    public function index() {
        $data = [];
        $this->Response->setOutPut($this->render("login/index", $data));
    }

}