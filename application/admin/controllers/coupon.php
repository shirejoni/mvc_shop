<?php

namespace App\Admin\Controller;

use App\lib\Action;
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
        $data = [];
        /** @var Coupon $Coupon */
        $Coupon = $this->load("Coupon", $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getLanguageID();
        $data['Coupons'] = $Coupon->getCoupons();
        $this->Response->setOutPut($this->render('coupon/index', $data));
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

    public function status() {
        if(isset($this->Request->post['coupon_id']) && isset($this->Request->post['coupon_status'])) {
            $coupon_id = (int) $this->Request->post['coupon_id'];
            $coupon_status = (int) $this->Request->post['coupon_status'] == 1 ? 1 : 0;
            /** @var Coupon $Coupon */
            $Coupon = $this->load("Coupon", $this->registry);
            $json = [];
            if($coupon_id &&  $product = $Coupon->getCoupon($coupon_id)) {
                $Coupon->editCoupon($coupon_id, array(
                    'status'    => $coupon_status
                ));
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
            }else {
                $json['status'] = 0;
                $json['messages'] = [$this->Language->get('error_done')];
            }
            $this->Response->setOutPut(json_encode($json));
            return;
        }
        return new Action('error/notFound', 'web');
    }

    public function delete() {
        if(!empty($this->Request->post['coupons_id'])) {
            $json = [];
            /** @var Coupon $Coupon */
            $Coupon = $this->load('Coupon', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['coupons_id'] as $coupon_id) {
                $coupon = $Coupon->getCoupon((int) $coupon_id);
                if($coupon && (int) $coupon_id) {
                    $Coupon->deleteCoupon((int) $coupon_id);
                }else {
                    $error = true;
                }
            }
            if($error) {
                $this->Database->db->rollBack();
                $json['status'] = 0;
                $json['messages'] = [$this->Language->get('error_done')];
            }else {
                $this->Database->db->commit();
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $data['Coupons'] = $Coupon->getCoupons();
                $json['data'] = $this->render('coupon/coupons_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

    public function edit() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->get[0])) {
            $coupon_id = (int) $this->Request->get[0];
            /** @var Coupon $Coupon */
            $Coupon = $this->load("Coupon", $this->registry);
            $coupon = $Coupon->getCoupon($coupon_id);
            $coupon_types = $this->Config->get('coupon_type');
            if($coupon_id && $coupon) {
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
                        if($data['name'] == $coupon['name']) {
                            unset($data['name']);
                        }
                        if($data['code'] == $coupon['code']) {
                            unset($data['code']);
                        }
                        if($data['discount'] == $coupon['discount']) {
                            unset($data['discount']);
                        }
                        if($data['type'] == $coupon['type']) {
                            unset($data['type']);
                        }
                        if($data['date_start'] == $coupon['date_start']) {
                            unset($data['date_start']);
                        }
                        if($data['date_end'] == $coupon['date_end']) {
                            unset($data['date_end']);
                        }
                        if($data['minimum_price'] == $coupon['minimum_price']) {
                            unset($data['minimum_price']);
                        }
                        if($data['count'] == $coupon['count']) {
                            unset($data['count']);
                        }
                        if(count($data)) {
                            $Coupon->editCoupon($category_id, $data);
                        }
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
                    /** @var Product $Product */
                    $Product = $this->load("Product", $this->registry);
                    foreach ($coupon['products_id'] as $index => $value) {
                        $product = $Product->getProduct($value);
                        $coupon['products_id'][$index] = $product;
                    }
                    /** @var Category $Category */
                    $Category = $this->load("Category", $this->registry);
                    foreach ($coupon['categories_id'] as $index => $value) {
                        $category = $Category->getCategory($value);
                        $coupon['categories_id'][$index] = $category;
                    }
                    require_once LIB_PATH . '/jdate/jdf.php';
                    $coupon['date_start'] = jdate("Y-m-d", $coupon['date_start'], '','', 'en');
                    $coupon['date_end'] = jdate("Y-m-d", $coupon['date_end'], '','', 'en');
                    $data['Coupon'] = $coupon;
                    $this->Response->setOutPut($this->render('coupon/edit', $data));
                }
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }
}