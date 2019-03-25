<?php

namespace App\Admin\Controller;

use App\lib\Response;
use App\system\Controller;

/**
 * @property Response Response
 */
class ControllerHome extends Controller {

    public function index()
    {
        $this->Response->setOutPut($this->render("home/index"));
    }
}