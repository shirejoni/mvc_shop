<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Cart;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\model\Coupon;
use App\model\Customer;
use App\system\Controller;

/**
 * @property Request Request
 * @property Response Response
 * @property Customer Customer
 * @property Database Database
 * */
class ControllerCheckoutCoupon extends Controller {

    public function applycoupon()
    {
        $data = [];
        $messages = [];
        $error = false;
        if (!empty($this->Request->post['code']) && $this->Customer) {
            $coupon_key = $this->Request->post['code'];
        } else if (isset($_SESSION['customer']['coupon']) && $this->Customer) {
            $coupon_key = $_SESSION['customer']['coupon']['code'];
            $coupon = $_SESSION['customer']['coupon'];
        }
        if (isset($coupon_key) && $coupon_key) {
            /** @var Coupon $Coupon */
            $Coupon = $this->load("Coupon", $this->registry);
            if (!isset($coupon)) {
                $coupon = $Coupon->getCouponByKey($coupon_key);
            }
            if ($coupon && $coupon['status'] == 1) {
                $Product = $this->load("Product", $this->registry);
                $old_session_id = isset($_SESSION['old_session_id']) ? $_SESSION['old_session_id'] : false;
                $Cart = new Cart($this->registry, $old_session_id);
                $products = $Cart->getProducts($Product);
                $total = 0;
                $off_price = 0;
                if ($products) {
                    $total = 0;
                    foreach ($products as $product) {
                        $total += $product['total'];
                        if (in_array($product['product_id'], $coupon['products_id'])) {
                            if ($coupon['discount'] && $coupon['type'] == "percentage") {
                                $off_price += ($coupon['discount'] * $product['total']) / 100;
                            }
                            continue;
                        }
                        foreach ($coupon['categories_id'] as $category_id) {
                            $this->Database->query("SELECT * FROM product_category WHERE category_id = :cID AND product_id = :pID", array(
                                'cID' => $category_id,
                                'pID' => $product['product_id']
                            ));
                            if ($this->Database->hasRows()) {
                                if ($coupon['discount'] && $coupon['type'] == "percentage") {
                                    $off_price += ($coupon['discount'] * $product['total']) / 100;
                                }
                                break;
                            }
                        }
                    }
                    if ($coupon['type'] == 'fixed_amount') {
                        $off_price = $coupon['discount'];
                    }
                    if ($coupon['minimum_price'] > $total) {
                        $error = true;
                        $messages[] = str_replace('{{MINIMUM_PRICE}}', $coupon['minimum_price'], $this->Language->get('error_off_code_minimum_price'));
                    }
                    $json = [];
                    if (!$error) {
                        $coupon['off_price'] = $off_price;
                        $_SESSION['customer']['coupon'] = $coupon;
                        $json['status'] = 1;
                        $json['total'] = $total;
                        $json['off_price'] = $off_price;
                        $json['payment_price'] = $total - $off_price;
                        $json['off_price_formatted'] = number_format($json['off_price']);
                        $json['payment_price_formatted'] = number_format($json['payment_price']);
                        $json['total_formatted'] = number_format($json['total']);
                        $json['messages'] = [$this->Language->get('success_message')];
                    }
                    if ($error) {
                        $json['status'] = 0;
                        $json['messages'] = $messages;
                    }
                } else {
                    $json = array(
                        'status' => 0,
                        'messages' => [$this->Language->get('error_no_such_off_code')]
                    );
                }
            }else {
                $json = array(
                    'status' => 0,
                    'messages' => [$this->Language->get('error_no_such_off_code')]
                );
            }
            $this->Response->setOutPut(json_encode($json));
            return;
        }
        return new Action('error/notFound', 'web');
    }
}