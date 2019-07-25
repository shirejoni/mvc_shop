<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Request;
use App\lib\Response;
use App\model\Customer;
use App\model\Product;
use App\system\Controller;

/**
 * @property Request Request
 * @property Customer Customer
 * @property Response Response
 * */
class ControllerFavorite extends Controller {

    public function toggle() {
        if(isset($this->Request->post['product_id'])) {
            if(!($this->Customer && $this->Customer->getCustomerId())) {
                $this->Response->setOutPut(json_encode([
                    'status'    => 0,
                    'messages'  => [$this->Language->get('error_favorite_customer_is_not_login')],
                ]));
                return;
            }
            $customerFavoriteProducts = $this->Customer->getCustomerFavoriteProducts($this->Customer->getCustomerId());
            /** @var Product $Product */
            $Product = $this->load('Product', $this->registry);
            $product = $Product->getProduct($this->Request->post['product_id']);
            if($product) {
                if(in_array($product['product_id'], $customerFavoriteProducts)) {
                    $this->Customer->deleteFavoriteProduct($this->Customer->getCustomerId(), $product['product_id']);
                }else {
                    $this->Customer->insertFavoriteProduct($this->Customer->getCustomerId(), $product['product_id']);
                }
                $json = [];
                $json['status'] = 1;
                $json['messages']   = [$this->Language->get('success_message')];
                $this->Response->setOutPut(json_encode($json));
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}