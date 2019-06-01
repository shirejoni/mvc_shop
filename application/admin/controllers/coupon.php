<?php

namespace App\Admin\Controller;

use App\lib\Response;
use App\system\Controller;

/**
 * @property Response Response
 */
class ControllerCoupon extends Controller {
    public function index() {

    }

    public function add() {
        $data = [];
        $messages = [];
        $error = false;
        $coupon_types = $this->Config->get('coupon_type');
        if(isset($this->Request->post['coupon-post'])) {

        }else {
            foreach ($coupon_types as $index => $coupon_type) {
                $data['CouponTypes'][] = array(
                    'index' => $index,
                    'value' => $coupon_type,
                );
            }
            $this->Response->setOutPut($this->render('coupon/add', $data));
        }
    }
}