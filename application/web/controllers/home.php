<?php

namespace App\Web\Controller;

use App\system\Controller;

class ControllerHome extends Controller {

    public function index() {
        $name = "Hossein";
        echo $this->render('home/index', ["Name"    => $name]);
    }

    public function about() {
        echo "Web Home/about @controller: ControllerHome @line: 12";
    }
}