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
                /** @var Product $Product */
                $Product = $this->load('Product', $this->registry);
                var_dump($Product->getProductsComplete($data));
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}