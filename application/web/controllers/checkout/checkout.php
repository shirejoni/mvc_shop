<?php

namespace App\Web\Controller;

use App\lib\Response;
use App\model\Customer;
use App\system\Controller;

/**
 * @property Response Response
 * @property Customer Customer
 */
class ControllerCheckoutCheckout extends Controller {

    public function index() {
        $data = [];
        if($this->Customer && $this->Customer->getCustomerId()) {
            header("location:" . URL . 'checkout/cart');
            exit();
        }
        $data['checkoutProcess'] = array(
            ['ورود', true],
            ['مرسوله', false],
            ['آدرس', false],
            ['پرداخت', false],
            ['پایان', false],

        );
        $this->Response->setOutPut($this->render('checkout/register-login', $data));
    }


    public function cart() {
        $data = [];
        $data['checkoutProcess'] = array(
            ['ورود', false],
            ['مرسوله', true],
            ['آدرس', false],
            ['پرداخت', false],
            ['پایان', false],

        );
        $this->Response->setOutPut($this->render('checkout/cart', $data));
    }
}