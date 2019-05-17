<?php

namespace App\Web\Controller;

use App\lib\Request;
use App\lib\Response;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 */
class ControllerUser extends Controller {

    public function index() {
        $this->Response->setOutPut($this->render('user/index'));
    }


    public function edit() {
        //
    }
}