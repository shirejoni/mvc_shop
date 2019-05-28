<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Request;
use App\lib\Response;
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
                }
                $json = [];
                if(!$error) {


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

}