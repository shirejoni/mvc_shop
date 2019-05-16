<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Response;
use App\model\Category;
use App\model\Option;
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
            /** @var Option $Option */
            $Option = $this->load("Option", $this->registry);
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
                switch ($product_info['stock_status_id']) {
                    case '1' :
                        $product_info['stock_status_class'] = "green";
                        break;
                    case '2' :
                        $product_info['stock_status_class'] = "red";
                    default:
                        $product_info['stock_status_class'] = 'yellow';
                }
                $attributes = [];
                foreach ($Product->getAttributes($product_id) as $attribute) {
                   if(!isset($attributes[$attribute['attribute_group_id']])) {
                       $attributes[$attribute['attribute_group_id']] = array(
                           'attribute_group_id' => $attribute['attribute_group_id'],
                           'name'               => $attribute['attribute_group_name']
                       );
                   }
                   $attributes[$attribute['attribute_group_id']]['attributes'][] = array(
                       'attribute_id'   => $attribute['attribute_id'],
                       'name'           => $attribute['name'],
                       'value'          => $attribute['value']
                   );
                }
                $product_info['attributes'] = $attributes;
                foreach ($Product->getProductOptions($product_id) as $option_group) {
                    $productOption = [];
                    $productOption['product_option_id'] = $option_group['product_option_id'];
                    $productOption['option_group_id'] = $option_group['option_group_id'];
                    $productOption['required'] = $option_group['required'];
                    $productOption['option_type'] = $option_group['option_type'];
                    $productOption['name'] = $option_group['name'];
                    $Option->getOptionGroup($option_group['option_group_id']);
                    $productOptionValues = [];
                    $option_items = $Option->getOptionItems();
                    foreach ($option_group['option_items'] as $productOptionValue) {
                        if($productOptionValue['subtract'] != 0 || $productOptionValue['quantity'] > 0) {
                            $productOptionValues[] = array(
                                'name'      => $productOptionValue['name'],
                                'price'     => $productOptionValue['price'],
                                'price_sign'=> $productOptionValue['price_sign'],
                                'image'     => $option_items[$productOptionValue['option_item_id']]['image'],
                                'option_group_id'   => $option_items[$productOptionValue['option_item_id']]['option_group_id'],
                                'product_option_value_id'   => $productOptionValue['product_option_value_id']
                            );
                        }
                    }
                    $productOption['option_items'] = $productOptionValues;
                    $product_info['options'][$option_group['sort_order']] = $productOption;
                }

                $data['Product'] = $product_info;
                $this->Response->setOutPut($this->render('product/index', $data));
                return;
            }
        }

        return new Action('error/notFound', 'web');
    }

}