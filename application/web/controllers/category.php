<?php


namespace App\Web\Controller;

use App\lib\Action;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\model\Category;
use App\model\Filter;
use App\model\Manufacturer;
use App\model\Product;
use App\system\Controller;

/**
 * @property Request Request
 * @property Response Response
 * @property Database Database
 * */
class ControllerCategory extends Controller {

    public function index()
    {
        if(isset($this->data['params'][0])) {
            $category_id = (int) $this->data['params'][0];
            /** @var Category $Category */
            $Category = $this->load('Category', $this->registry);
            /** @var Manufacturer $Manufacturer */
            $Manufacturer = $this->load('Manufacturer', $this->registry);
            /** @var Filter $Filter */
            $Filter = $this->load('Filter', $this->registry);
            $data = [];
            $data['category_id'] = $category_id;
            if($category_id && $category = $Category->getCategory($category_id)) {
                if(isset($this->Request->get['manufacturers']) && is_array($this->Request->get['manufacturers'])) {
                    $data['manufacturers_id'] = [];
                    foreach ($this->Request->get['manufacturers'] as $filter_data) {
                        if((int) $filter_data && $manufacturer = $Manufacturer->getManufacturerByID((int) $filter_data)) {
                            $data['manufacturers_id'][] = $manufacturer['manufacturer_id'];
                        }
                    }
                }
                if(isset($this->Request->get['filters']) && is_array($this->Request->get['filters'])) {
                    $data['filters'] = [];
                    foreach ($this->Request->get['filters'] as $filter_data) {
                        list($filter_group_id, $filter_id) = explode('-', $filter_data);
                        if((int) $filter_group_id && $filter_group = $Filter->getFilterGroup((int) $filter_group_id)) {
                            if(isset($data['filters'][$filter_group_id])) {
                                $data['filters'][$filter_group_id] = [];
                                $data['filters'][$filter_group_id][] = $filter_id;
                            }else {
                                $data['filters'][$filter_group_id][] = $filter_id;
                            }
                        }
                    }
                }
                if(isset($this->Request->get['min'])) {
                    $data['min'] =(int) $this->Request->get['min'];
                }

                if(isset($this->Request->get['max'])) {
                    $data['max'] =(int) $this->Request->get['max'];
                }
                /** @var Product $Product */
                $Product = $this->load('Product', $this->registry);
                $products = $Product->getProductsComplete($data);
                if($products) {
                    $data['Breadcrumbs'] = [];
                    $data['Breadcrumbs'][] = array(
                        'text'  => $this->Language->get('home_page'),
                        'link'  => URL,
                        'active'=> 0
                    );
                    $category = $Category->getCategory($category_id);
                    $categoryFilters = $Category->getCategoryFilters($category_id);
                    if($categoryFilters) {
                        $data['CategoryFilters'] = $categoryFilters;
                    }
                    $categories = $Category->getCategoryInfoInPath($category['category_id']);
                    foreach ($categories as $cat) {
                        $data['Breadcrumbs'][] = array(
                            'text'  => $cat['name'],
                            'link'  => URL . 'category/' . $cat['category_id'],
                            'active'=> $cat['category_id'] === $category['category_id'] ? 1 : 0
                        );
                    }
                    $Image = $this->load("Image", $this->registry);
                    $minimum_price = 0;
                    $maximum_price = 0;
                    foreach ($products as &$product) {
                        if(is_file(ASSETS_PATH . DS . substr($product['image'], strlen(ASSETS_URL)))) {
                            $product['image'] = ASSETS_URL . $Image->resize(substr($product['image'], strlen(ASSETS_URL)), 400, 400);
                        }
                        if($minimum_price === 0 || (!empty($product['special']) && $minimum_price > $product['special']) || $minimum_price > $product['price']) {
                            $minimum_price = !empty($product['special']) && $product['special'] < $minimum_price && $product['special'] < $product['price'] ? $product['special'] : $product['price'];
                        }
                        if($maximum_price === 0 || (!empty($product['special']) && $maximum_price < $product['special']) || $maximum_price < $product['price']) {
                            $maximum_price = !empty($product['special']) && $product['special'] < $maximum_price && $product['special'] > $product['price'] ? $product['special'] : $product['price'];
                        }
                    }
                    var_dump($products);
                    var_dump($minimum_price);
                    var_dump($maximum_price);
                }
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}