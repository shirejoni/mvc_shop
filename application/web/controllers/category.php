<?php


namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Request;
use App\lib\Response;
use App\model\Category;
use App\model\Manufacturer;
use App\model\Product;
use App\system\Controller;

/**
 * @property Request Request
 * @property Response Response
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
            $data = [];
            $data['category_id'] = $category_id;
            if($category_id && $category = $Category->getCategory($category_id)) {
                if(isset($this->Request->get['manufacturers']) && is_array($this->Request->get['manufacturers'])) {
                    $data['manufacturers_id'] = [];
                    foreach ($this->Request->get['manufacturers'] as $manufacturer_id) {
                        if((int) $manufacturer_id && $manufacturer = $Manufacturer->getManufacturerByID((int) $manufacturer_id)) {
                            $data['manufacturers_id'][] = $manufacturer['manufacturer_id'];
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