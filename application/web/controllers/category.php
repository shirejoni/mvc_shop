<?php


namespace App\Web\Controller;

use App\lib\Action;
use App\model\Category;
use App\model\Product;
use App\system\Controller;

class ControllerCategory extends Controller {

    public function index()
    {
        if(isset($this->data['params'][0])) {
            $category_id = (int) $this->data['params'][0];
            /** @var Category $Category */
            $Category = $this->load('Category', $this->registry);
            if($category_id && $category = $Category->getCategory($category_id)) {
                /** @var Product $Product */
                $Product = $this->load('Product', $this->registry);
                var_dump($Product->getProductsComplete([
                    'category_id'   => $category_id
                ]));
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}