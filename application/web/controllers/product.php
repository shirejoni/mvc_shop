<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Response;
use App\model\Product;
use App\system\Controller;

/**
 * @property Response Response
 */
class ControllerProduct extends Controller {

    public function index() {
        $data = [];
        if(isset($this->data['params'][0])) {
            $product_id = (int) $this->data['params'][0];
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            if($product_id && $product_info = $Product->getProductComplete($product_id)) {

                $this->Response->setOutPut($this->render('product/index', $data));
                return;
            }
        }

        return new Action('error/notFound', 'web');
    }

}