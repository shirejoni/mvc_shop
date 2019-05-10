<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\model\Product;
use App\system\Controller;

class ControllerProduct extends Controller {

    public function index() {
        if(isset($this->data['params'][0])) {
            $product_id = (int) $this->data['params'][0];
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            if($product_id && $product_info = $Product->getProductComplete($product_id)) {
                var_dump($product_info);
                return;
            }
        }

        return new Action('error/notFound', 'web');
    }

}