<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Cart;
use App\lib\Request;
use App\lib\Response;
use App\model\Category;
use App\model\Option;
use App\model\Product;
use App\system\Controller;
/**
 * @property Request Request
 * @property Response Response
 * */
class ControllerCheckoutCart extends Controller {


    public function add() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->post['cart-post'])) {
            $product_id = isset($this->Request->post['product-id']) && (int) $this->Request->post['product-id'] ? (int) $this->Request->post['product-id'] : 0;
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            if($product_id && $product = $Product->getProduct($product_id, $this->Language->getDefaultLanguageID())) {
                if(isset($this->Request->post['quantity']) && $this->Request->post['quantity'] >= $product['minimum']) {
                    $data['quantity'] = (int) $this->Request->post['quantity'];
                }else {
                    $data['quantity'] = $product['minimum'];
                }
                $data['product_id'] = $product_id;
                if(isset($this->Request->post['options'])) {
                    $productPostOptions = $this->Request->post['options'];
                }else {
                    $productPostOptions = [];
                }
                $data['product_option'] = [];
                foreach ($Product->getProductOptions($product_id) as $productOption) {
                    if($productOption['required'] && !isset($productPostOptions[$productOption['product_option_id']])) {
                        $error = true;
                        /** @var Option $Option */
                        $Option = $this->load("Option", $this->registry);
                        $option = $Option->getOptionGroup($productOption['option_group_id']);
                        $messages[] = $this->Language->get('error_product_option_empty') . "[" . $option['name'] ."]";
                    }

                    if(isset($productPostOptions[$productOption['product_option_id']]) && !isset($productOption['option_items'][$productPostOptions[$productOption['product_option_id']]])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_done');
                    }
                    if(!$error) {
                        $data['product_option'][$productOption['product_option_id']] = $productPostOptions[$productOption['product_option_id']];
                    }
                }
                $json = [];
                if(!$error) {
                    if(isset($_SESSION['session_old_id'])) {
                        $session_old_id = $_SESSION['session_old_id'];
                    }else {
                        $session_old_id = false;
                    }
                    $Cart = new Cart($this->registry, $session_old_id);
                    $this->registry->Cart = $Cart;
                    $Cart->add($data['product_id'], $data['quantity'], $data['product_option']);
                    $json['status'] = 1;
                    $json['messages'] = [$this->Language->get('success_message')];
                }
                if($error) {
                    $json['status'] = 0;
                    $json['messages'] = $messages;
                }
                $this->Response->setOutPut(json_encode($json));
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

    public function info() {
        if($this->Customer && isset($_SESSION['session_old_id'])) {
            /** @var Cart $Cart */
            $Cart = new Cart($this->registry, $_SESSION['session_old_id']);
        }else {
            /** @var Cart $Cart */
            $Cart = new Cart($this->registry);
        }
        /** @var Product $Product */
        $Product = $this->load("Product", $this->registry);
        $products = $Cart->getProducts($Product);
        var_dump($products);

    }

}