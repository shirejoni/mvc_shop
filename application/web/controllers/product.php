<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Response;
use App\model\Category;
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
            /** @var Category $Category */
            $Category = $this->load("Category", $this->registry);
            if($product_id && $product_info = $Product->getProductComplete($product_id)) {
                $data['Breadcrumbs'] = [];
                $data['Breadcrumbs'][] = array(
                    'text'  => $this->Language->get('home_page'),
                    'link'  => URL,
                    'active'=> 0
                );
                $category = $Product->getCategory($product_id);
                $categories = $Category->getCategoryInfoInPath($category['category_id']);
                foreach ($categories as $cat) {
                    $data['Breadcrumbs'][] = array(
                        'text'  => $cat['name'],
                        'link'  => URL . 'category/' . $cat['category_id'],
                        'active'=> 0
                    );
                }
                $data['Breadcrumbs'][] = array(
                    'text'  => $product_info['name'],
                    'link'  => '#',
                    'active'=> 1
                );
                $product_info['category_name'] = $category['name'];
                $product_info['category_id'] = $category['category_id'];

                $data['Product'] = $product_info;
                $this->Response->setOutPut($this->render('product/index', $data));
                return;
            }
        }

        return new Action('error/notFound', 'web');
    }

}