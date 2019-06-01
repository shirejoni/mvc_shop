<?php

namespace App\Admin\Controller;

use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Category;
use App\model\Coupon;
use App\model\Product;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
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
            if(!empty($this->Request->post['coupon-name'])) {
                $data['name'] = $this->Request->post['coupon-name'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_coupon_name_empty');
            }
            if(!empty($this->Request->post['coupon-code']) && Validate::couponValid($this->Request->post['coupon-code'])) {
                $data['code'] = $this->Request->post['coupon-code'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_coupon_code_empty');
            }
            if(!empty($this->Request->post['coupon-type']) && array_key_exists($this->Request->post['coupon-type'], $coupon_types)) {
                $data['type'] = $this->Request->post['coupon-type'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_coupon_type_empty');
            }
            if(!empty($this->Request->post['coupon-discount']) ) {
                $data['discount'] = (int) $this->Request->post['coupon-discount'];
            }else {
                $data['discount'] = 0;
            }
            if(!empty($this->Request->post['coupon-minimum-price']) ) {
                $data['minimum_price'] = (int) $this->Request->post['coupon-minimum-price'];
            }else {
                $data['minimum_price'] = 0;
            }
            require_once LIB_PATH . DS . 'jdate/jdf.php';
            if(!empty($this->Request->post['coupon-date-start'])) {
                $parts = explode('/', $this->Request->post['coupon-date-start']);
                if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                    $data['date_start'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                }
            }
            if(empty($data['date_start'])) {
                $error = true;
                $messages[] = $this->Language->get('error_coupon_date_start_empty');
            }
            if(!empty($this->Request->post['coupon-date-end'])) {
                $parts = explode('/', $this->Request->post['coupon-date-end']);
                if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                    $data['date_end'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                }
            }
            if(empty($data['date_end'])) {
                $error = true;
                $messages[] = $this->Language->get('error_coupon_date_end_empty');
            }
            if(!empty($this->Request->post['coupon-count']) ) {
                $data['count'] = (int) $this->Request->post['coupon-count'];
            }else {
                $data['count'] = 0;
            }
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            if(!empty($this->Request->post['coupon-products'])) {
                foreach ($this->Request->post['coupon-products'] as $product_id) {
                    $product = $Product->getProduct((int) $product_id);
                    if($product) {
                        $data['products_id'][] = (int) $product_id;
                    }
                }
            }
            /** @var Category $Category */
            $Category = $this->load("Category", $this->registry);
            if(!empty($this->Request->post['coupon-categories'])) {
                foreach ($this->Request->post['coupon-categories'] as $category_id) {
                    $category = $Category->getCategory((int) $category_id);
                    if($category) {
                        $data['categories_id'][] = (int) $category_id;
                    }
                }
            }
            $json = [];
            if(!$error) {
                /** @var Coupon $Coupon */
                $Coupon = $this->load("Coupon", $this->registry);
                $Coupon->insertCoupon($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'coupon/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
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