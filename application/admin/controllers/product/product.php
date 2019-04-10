<?php

namespace App\Admin\Controller;

use App\lib\Response;
use App\model\Length;
use App\model\Stock;
use App\model\Weight;
use App\system\Controller;

/**
 * @property Response Response
 */
class ControllerProductProduct extends Controller {

    public function index() {

    }

    public function add() {
        $data = [];
        $messages = [];
        $error = true;
        /** @var Stock $Stock */
        $Stock = $this->load("Stock", $this->registry);
        /** @var Weight $Weight */
        $Weight = $this->load("weight", $this->registry);
        /** @var Length $Length */
        $Length = $this->load("Length", $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $data['StocksStatus'] = $Stock->getStocks();
        $data['Weights'] = $Weight->getWeights();
        $data['Lengths'] = $Length->getLengths();

        $this->Response->setOutPut($this->render('product/product/add', $data));

    }
}