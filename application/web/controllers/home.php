<?php

namespace App\Web\Controller;

use App\system\Controller;

class ControllerHome extends Controller {

    public function index() {
        $data = array(
            'URL'   => URL,
        );
        echo $this->render('home/index', $data);
    }

    public function about() {
        echo "Web Home/about @controller: ControllerHome @line: 12";
    }
}