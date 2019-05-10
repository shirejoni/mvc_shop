<?php

namespace App\Admin\Controller;

use App\lib\Action;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Attribute;
use App\model\Category;
use App\model\Filter;
use App\model\Image;
use App\model\Length;
use App\model\Manufacturer;
use App\model\Option;
use App\model\Product;
use App\model\Stock;
use App\model\Weight;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 * @property Product Product
 */
class ControllerProductProduct extends Controller {

    public function index() {
        $data = [];
        /** @var Product $Product */
        $Product = $this->load('Product', $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $products = $Product->getProducts(array(
            'language_id'   => $this->Language->getLanguageID(),
            'order'         => 'DESC'
        ));
        /** @var Image $Image */
        $Image = $this->load("Image", $this->registry);
        $data['Products'] = [];
        foreach ($products as $product) {
            if(is_file(ASSETS_PATH . DS . substr($product['image'], strlen(ASSETS_URL)))) {
                $image = ASSETS_URL . $Image->resize(substr($product['image'], strlen(ASSETS_URL)), 200, 200);
            }else {
                $image = ASSETS_URL . $Image->resize(substr('img/no-image.jpg', 200 ,200));
            }
            $special = '';
            $productSpecials = $Product->getProductSpecials($product['product_id']);
            foreach ($productSpecials as $productSpecial) {
                if($productSpecial['date_start'] < time() AND $productSpecial['date_end'] > time()) {
                    $special = $productSpecial['price'];
                }
            }
            $data['Products'][] = array(
                'product_id'    => $product['product_id'],
                'name'          => $product['name'],
                'image'         => $image,
                'sort_order'    => $product['sort_order'],
                'price'         => $product['price'],
                'special'       => $special,
                'status'        => $product['status']
            );
        }
        $this->Response->setOutPut($this->render('product/product/index', $data));
    }

    public function add() {
        $data = [];
        $messages = [];
        $error = false;
        /** @var Stock $Stock */
        $Stock = $this->load("Stock", $this->registry);
        /** @var Weight $Weight */
        $Weight = $this->load("weight", $this->registry);
        /** @var Length $Length */
        $Length = $this->load("Length", $this->registry);
        $DefaultLanguageID = $this->Language->getDefaultLanguageID();
        if(isset($this->Request->post['product-post'])) {

            /** @var Manufacturer $Manufacturer */
            $Manufacturer = $this->load("Manufacturer", $this->registry);
            /** @var Category $Category */
            $Category = $this->load("Category", $this->registry);
            /** @var Filter $Filter */
            $Filter = $this->load("Filter", $this->registry);
            /** @var Option $Option */
            $Option = $this->load("Option", $this->registry);
            /** @var Attribute $Attribute */
            $Attribute = $this->load("Attribute", $this->registry);
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);

            foreach ($this->Language->getLanguages() as $language) {
                if(!empty($this->Request->post['product-name-' . $language['language_id']])) {
                    $data['product_descriptions'][$language['language_id']]['name'] = $this->Request->post['product-name-' . $language['language_id']];
                }
                if(!empty($this->Request->post['product-description-' . $language['language_id']])) {
                    $data['product_descriptions'][$language['language_id']]['description'] = $this->Request->post['product-description-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['product-name-' . $DefaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_name_empty');
            }
            if(empty($this->Request->post['product-description-' . $DefaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_description_empty');
            }

            if(!empty($this->Request->post['product-price'])) {
                $data['product-price'] = (int) $this->Request->post['product-price'];
            }else {
                $data['product-price'] = 0;
            }

            require_once LIB_PATH . DS . 'jdate/jdf.php';
            if(!empty($this->Request->post['product-date'])) {
                $parts = explode('/', $this->Request->post['product-date']);
                if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                    $data['product-date'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                }
            }
            if(!isset($data['product-date'])) {
                $data['product-date'] = time();
            }
            if(!empty($this->Request->post['product-quantity'])) {
                $data['product-quantity'] = (int) $this->Request->post['product-quantity'];
            }else {
                $data['product-quantity'] = 0;
            }
            if(!empty($this->Request->post['product-min-quantity-per-order'])) {
                $data['product-min-quantity-per-order'] = (int) $this->Request->post['product-min-quantity-per-order'];
            }else {
                $data['product-min-quantity-per-order'] = 1;
            }
            if(!empty($this->Request->post['product-stock-status'])) {
                $stock_status_id = (int) $this->Request->post['product-stock-status'];
                if($stock_status_id && $Stock->getStock($stock_status_id)) {
                    $data['stock_status_id'] = $stock_status_id;
                }
            }
            if(!isset($data['stock_status_id'])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_stock_status_not_selected');
            }
            if(!empty($this->Request->post['product-weight-unit'])) {
                $weight_unit_id = (int) $this->Request->post['product-weight-unit'];
                if($weight_unit_id && $Weight->getWeight($weight_unit_id)) {
                    $data['weight_id'] = $weight_unit_id;
                }
            }
            if(!isset($data['weight_id'])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_weight_unit_not_selected');
            }
            if(!empty($this->Request->post['product-length-unit'])) {
                $length_id = (int) $this->Request->post['product-length-unit'];
                if($length_id && $Length->getLength($length_id)) {
                    $data['length_id'] = $length_id;
                }
            }
            if(!isset($data['length_id'])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_length_not_selected');
            }
            if(!empty($this->Request->post['product-weight'])) {
                $data['weight'] = (float) $this->Request->post['product-weight'];
            }else {
                $data['weight'] = 0;
            }
            if(!empty($this->Request->post['product-height'])) {
                $data['height'] = (float) $this->Request->post['product-height'];
            }else {
                $data['height'] = 0;
            }
            if(!empty($this->Request->post['product-length'])) {
                $data['length'] = (float) $this->Request->post['product-length'];
            }else {
                $data['length'] = 0;
            }
            if(!empty($this->Request->post['product-width'])) {
                $data['width'] = (float) $this->Request->post['product-width'];
            }else {
                $data['width'] = 0;
            }
            if(!empty($this->Request->post['product-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['product-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }
            if(!empty($this->Request->post['product-manufacturer-id'])) {
                $manufacturer_id = (int)$this->Request->post['product-manufacturer-id'];
                if($manufacturer_id && $Manufacturer->getManufacturerByID($manufacturer_id)) {
                    $data['manufacturer_id'] = $manufacturer_id;
                }
            }
            if(!isset($data['manufacturer_id'])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_manufacturer_not_selected');
            }
            $data['categories_id'] = [];
            if(!empty($this->Request->post['product-categories-id']) && count($this->Request->post['product-categories-id']) > 0) {
                foreach ($this->Request->post['product-categories-id'] as $category_id) {
                    if((int) $category_id && $Category->getCategory((int) $category_id)) {
                        $data['categories_id'][] = $category_id;
                    }
                }
            }
            if(count($data['categories_id']) == 0) {
                $error = true;
                $messages[] = $this->Language->get('error_product_category_not_selected');
            }
            $data['filters_id'] = [];
            if(!empty($this->Request->post['product-filters-id']) && count($this->Request->post['product-filters-id']) > 0) {
                foreach ($this->Request->post['product-filters-id'] as $filter_id) {
                    if((int) $filter_id && $Filter->getFilterItem((int) $filter_id)) {
                        $data['filters_id'][] = $filter_id;
                    }
                }
            }
            if(count($data['filters_id']) == 0) {
                $error = true;
                $messages[] = $this->Language->get('error_product_filter_not_selected');
            }
            $data['attributes'] = [];
            if(!empty($this->Request->post['product-attributes']) && count($this->Request->post['product-attributes']) > 0) {
                foreach ($this->Request->post['product-attributes'] as $product_attribute) {
                    $attribute_id = isset($product_attribute['attribute-id']) ? (int) $product_attribute['attribute-id'] : 0;
                    if($attribute_id && $Attribute->getAttribute($attribute_id)) {

                        $attributeDetail = [];
                        $attributeDetail['attribute_id'] = $attribute_id;
                        $attributeDetail['names'] = [];
                        foreach ($this->Language->getLanguages() as $language) {
                            if(!empty($product_attribute['attribute-value-' . $language['language_id']])) {
                                $attributeDetail['names'][$language['language_id']] = $product_attribute['attribute-value-' . $language['language_id']];
                            }
                        }
                        if(!empty($product_attribute['attribute-value-' . $DefaultLanguageID])) {
                            $data['attributes'][] = $attributeDetail;
                        }
                    }
                }
            }
            $data['options'] = [];
            if(!empty($this->Request->post['product-options']) && count($this->Request->post['product-options']) > 0) {
                foreach ($this->Request->post['product-options'] as $product_option) {
                    $option_id  = isset($product_option['option-id']) ? (int) $product_option['option-id'] : 0;

                    if($option_id && $optionGroup = $Option->getOptionGroup($option_id)) {
                        $optionGroupData = [];
                        $optionGroupData['option_group_id'] = $option_id;
                        $optionGroupData['required'] = isset($product_option['required']) ? (int) $product_option['required'] : 0;
                        $optionItemData = [];
                        $optionItems = [];
                        foreach ($Option->getOptionItems() as $optionItem) {
                            $optionItems[] = $optionItem['option_item_id'];
                        }
                        if(isset($product_option['option-items'])) {

                            foreach ($product_option['option-items'] as $option_item) {
                                if(in_array($option_item['option-item-id'], $optionItems)) {
                                    $optionItemData['option_item_id'] = $option_item['option-item-id'];
                                    if(isset($option_item['quantity']) && $option_item['quantity'] < $data['product-quantity']) {
                                        $optionItemData['quantity'] = $option_item['quantity'];
                                    }else {
                                        $optionItemData['quantity'] = 0;
                                    }
                                    $optionItemData['subtract'] = isset($option_item['effect-on-quantity']) ? (int) $option_item['effect-on-quantity'] : 0;
                                    $optionItemData['price_sign'] = isset($option_item['price-sign']) && $option_item['price-sign'] == "-" ? "-" : "+";
                                    $optionItemData['weight_sign'] = isset($option_item['weight-sign']) && $option_item['weight-sign'] == "-" ? "-" : "+";
                                    if(isset($option_item['price']) && (int) $option_item['price'] > 0) {
                                        $optionItemData['price'] = (int) $option_item['price'];
                                    }else {
                                        $optionItemData['price'] = 0;
                                    }
                                    if(isset($option_item['weight']) && (int) $option_item['weight'] > 0) {
                                        $optionItemData['weight'] = (int) $option_item['weight'];
                                    }else {
                                        $optionItemData['weight'] = 0;
                                    }
                                    $optionGroupData['option_items'][] = $optionItemData;
                                }
                            }
                            $data['options'][] = $optionGroupData;
                        }
                    }else {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_option_group_invalid_id');
                    }
                }
            }

            $data['specials'] = [];
            if(!empty($this->Request->post['product-specials']) && count($this->Request->post['product-specials']) > 0) {
                $priority = [];
                foreach ($this->Request->post['product-specials'] as $product_special) {
                    if(isset($product_special['priority']) && !in_array($product_special['priority'], $priority)) {
                        $productSpecial = [];
                        $productSpecial['priority'] = $product_special['priority'];
                        $priority[] = $product_special['priority'];
                        if(isset($product_special['price'])) {
                            $productSpecial['price'] = (int) $product_special['price'];
                        }else {
                            $productSpecial['price'] = 0;
                        }
                        if(!empty($product_special['date-start'])) {
                            $parts = explode('/', $product_special['date-start']);
                            if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                                $productSpecial['date_start'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                            }
                        }
                        if(!isset($productSpecial['date_start'])) {
                            $productSpecial['date_start'] = time();
                        }
                        if(!empty($product_special['date-end'])) {
                            $parts = explode('/', $product_special['date-end']);
                            if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                                $productSpecial['date_end'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                            }
                        }
                        if(!isset($productSpecial['date_end'])) {
                            $productSpecial['date_end'] = time();
                        }
                        $data['specials'][] = $productSpecial;
                    }
                }
            }

            $data['images'] = [];
            if(!empty($this->Request->post['product-images']) && count($this->Request->post['product-images']) > 0) {
                foreach ($this->Request->post['product-images'] as $product_image) {
                    if(isset($product_image['src']) && Validate::urlValid($product_image['src'])) {

                        $image = array(
                            'src'   => $product_image['src'],
                            'sort_order'    => isset($product_image['sort_order']) ? $product_image['sort_order'] : 0,
                        );
                        if(isset($product_image['default']) && $product_image['default'] == 'true') {
                            $data['image'] = $product_image['src'];
                        }
                        $data['images'][] = $image;
                    }
                }
            }
            if(empty($data['image'])) {
                $error = true;
                $messages[] = $this->Language->get('error_product_default_image');
            }

            $json = [];
            if(!$error) {
                $data['time_added'] = time();
                $data['time_updated'] = time();
                if(!$data['sort_order']) {
                    $rows = $Product->getProducts(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $Product->insertProduct($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'product/product/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json = array(
                    'status'  => 0,
                    'messages'=> $messages
                );
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
            $data['StocksStatus'] = $Stock->getStocks();
            $data['Weights'] = $Weight->getWeights();
            $data['Lengths'] = $Length->getLengths();

            $this->Response->setOutPut($this->render('product/product/add', $data));
        }
    }

    public function delete() {
        if(!empty($this->Request->post['products_id'])) {
            $json = [];
            /** @var Product $Product */
            $Product = $this->load('Product', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['products_id'] as $product_id) {
                $productGroup = $Product->getProduct((int) $product_id);
                if($productGroup && (int) $product_id) {
                    $Product->deleteProduct((int) $product_id);
                }else {
                    $error = true;
                }
            }
            if($error) {
                $this->Database->db->rollBack();
                $json['status'] = 0;
                $json['messages'] = [$this->Language->get('error_done')];
            }else {
                $this->Database->db->commit();
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $products = $Product->getProducts(array(
                    'language_id'   => $this->Language->getLanguageID(),
                    'order'         => 'DESC'
                ));
                /** @var Image $Image */
                $Image = $this->load("Image", $this->registry);
                $data['Products'] = [];
                foreach ($products as $product) {
                    if(is_file(ASSETS_PATH . DS . substr($product['image'], strlen(ASSETS_URL)))) {
                        $image = ASSETS_URL . $Image->resize(substr($product['image'], strlen(ASSETS_URL)), 200, 200);
                    }else {
                        $image = ASSETS_URL . $Image->resize(substr('img/no-image.jpg', 200 ,200));
                    }
                    $special = '';
                    $productSpecials = $Product->getProductSpecials($product['product_id']);
                    foreach ($productSpecials as $productSpecial) {
                        if($productSpecial['date_start'] < time() AND $productSpecial['date_end'] > time()) {
                            $special = $productSpecial['price'];
                        }
                    }
                    $data['Products'][] = array(
                        'product_id'    => $product['product_id'],
                        'name'          => $product['name'],
                        'image'         => $image,
                        'sort_order'    => $product['sort_order'],
                        'price'         => $product['price'],
                        'special'       => $special,
                        'status'        => $product['status']
                    );
                }
                $json['data'] = $this->render('product/product/product_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

    public function status() {
        if(isset($this->Request->post['product_id']) && isset($this->Request->post['product_status'])) {
            $product_id = (int) $this->Request->post['product_id'];
            $product_status = (int) $this->Request->post['product_status'] == 1 ? 1 : 0;
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            $json = [];
            if($product_id &&  $product = $Product->getProduct($product_id)) {
                $Product->editProduct($product_id, array(
                    'status'    => $product_status
                ));
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
            }else {
                $json['status'] = 0;
                $json['messages'] = [$this->Language->get('error_done')];
            }
            $this->Response->setOutPut(json_encode($json));
            return;
        }
        return new Action('error/notFound', 'web');
    }

    public function edit() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->get[0])) {
            $product_id = (int) $this->Request->get[0];
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            /** @var Manufacturer $Manufacturer */
            $Manufacturer = $this->load("Manufacturer", $this->registry);
            /** @var Category $Category */
            $Category = $this->load("Category", $this->registry);
            /** @var Filter $Filter */
            $Filter = $this->load("Filter", $this->registry);
            /** @var Attribute $Attribute */
            $Attribute = $this->load("Attribute", $this->registry);
            /** @var Option $Option */
            $Option = $this->load("Option", $this->registry);
            /** @var Stock $Stock */
            $Stock = $this->load("Stock", $this->registry);
            /** @var Weight $Weight */
            $Weight = $this->load("weight", $this->registry);
            /** @var Length $Length */
            $Length = $this->load("Length", $this->registry);
            if($product_id && $productTotal = $Product->getProduct($product_id, 'all')) {
                $productInfo = [];
                foreach ($productTotal as $pRow) {
                    $productInfo['product_descriptions'][$pRow['language_id']] = array(
                        'name'  => $pRow['name'],
                        'description'   => $pRow['description']
                    );
                }
                require_once LIB_PATH . DS . 'jdate/jdf.php';
                $productInfo['product_id'] = $productTotal[0]['product_id'];
                $productInfo['quantity'] = $productTotal[0]['quantity'];
                $productInfo['stock_status_id'] = $productTotal[0]['stock_status_id'];
                $productInfo['image'] = $productTotal[0]['image'];
                $productInfo['manufacturer_id'] = $productTotal[0]['manufacturer_id'];
                $productInfo['price'] = $productTotal[0]['price'];
                $productInfo['date_available'] = jdate('Y-m-d', $productTotal[0]['date_available'], '', '', 'en');
                $productInfo['date_available_time'] = $productTotal[0]['date_available'];
                $productInfo['weight'] = $productTotal[0]['weight'];
                $productInfo['weight_id'] = $productTotal[0]['weight_id'];
                $productInfo['height'] = $productTotal[0]['height'];
                $productInfo['width'] = $productTotal[0]['width'];
                $productInfo['length'] = $productTotal[0]['length'];
                $productInfo['length_id'] = $productTotal[0]['length_id'];
                $productInfo['minimum'] = $productTotal[0]['minimum'];
                $productInfo['status'] = $productTotal[0]['status'];
                $productInfo['views'] = $productTotal[0]['views'];
                $productInfo['sort_order'] = $productTotal[0]['sort_order'];
                $manufacturer = $Manufacturer->getManufacturerByID($productInfo['manufacturer_id']);
                $productInfo['manufacturer_name'] = $manufacturer['name'];
                $categories_id = $Product->getProductCategories($product_id);
                $productInfo['categories'] = [];
                foreach ($categories_id as $category_id) {
                    $category = $Category->getCategory($category_id);
                    $productInfo['categories'][$category_id] = array(
                        'category_id'   => $category_id,
                        'name'          => $category['name']
                    );
                }
                $filters_id = $Product->getProductFilters($product_id);
                $productInfo['filters'] = [];
                foreach ($filters_id as $filter_id) {
                    $filter = $Filter->getFilterItem($filter_id);
                    $productInfo['filters'][$filter_id] = array(
                        'filter_id'   => $filter_id,
                        'group_name'   => $filter['group_name'],
                        'name'          => $filter['name']
                    );
                }
                $attributes = $Product->getProductAttributes($product_id);
                $productInfo['attributes'] = [];

                foreach ($attributes as $attribute) {
                    $attr = $Attribute->getAttribute($attribute['attribute_id']);
                    $productInfo['attributes'][$attribute['attribute_id']] = array(
                        'attribute_id'  => $attribute['attribute_id'],
                        'name'          => $attr['name'],
                        'group_name'          => $attr['attribute_group_name'],
                        'values'        => $attribute['values']
                    );
                }
                $productInfo['options'] = $Product->getProductOptions($product_id);
                foreach ($productInfo['options'] as $option ) {
                    $data['Options'][$option['option_group_id']] = $Option->getOptionGroup($option['option_group_id']);
                    $data['Options'][$option['option_group_id']]['option_items'] = $Option->getOptionItems();
                }
                $productSpecials = $Product->getProductSpecials($product_id);
                $productInfo['specials'] = [];
                foreach ($productSpecials as $productSpecial) {
                    $productInfo['specials'][] = array(
                        'product_special_id'        => $productSpecial['product_special_id'],
                        'price'                     => $productSpecial['price'],
                        'priority'                  => $productSpecial['priority'],
                        'date_start'                => jdate('Y-m-d', $productSpecial['date_start'], '', '', 'en'),
                        'date_end'                => jdate('Y-m-d', $productSpecial['date_end'], '', '', 'en'),
                    );
                }
                $productImages = $Product->getProductImages($product_id);
                /** @var Image $Image */
                $Image = $this->load("Image", $this->registry);
                $productInfo['images'] = [];
                foreach ($productImages as $productImage) {
                    if(is_file(ASSETS_PATH . DS . substr($productImage['image'], strlen(ASSETS_URL)))) {
                        $image = ASSETS_URL . $Image->resize(substr($productImage['image'], strlen(ASSETS_URL)), 200, 200);
                    }else {
                        $image = ASSETS_URL . $Image->resize('img/no-image.jpg', 200 ,200);
                    }
                    $productInfo['images'][] = array(
                        'src'       => $image,
                        'image'       => $productImage['image'],
                        'sort_order'=> $productImage['sort_order'],
                        'default'   => $productImage['image'] == $productInfo['image'] ? 1 : 0,
                    );
                }

                $DefaultLanguageID = $this->Language->getDefaultLanguageID();
                if(isset($this->Request->post['product-post'])) {
                    foreach ($this->Language->getLanguages() as $language) {
                        if(!empty($this->Request->post['product-name-' . $language['language_id']])) {
                            $data['product_descriptions'][$language['language_id']]['name'] = $this->Request->post['product-name-' . $language['language_id']];
                        }
                        if(!empty($this->Request->post['product-description-' . $language['language_id']])) {
                            $data['product_descriptions'][$language['language_id']]['description'] = $this->Request->post['product-description-' . $language['language_id']];
                        }
                    }
                    if(empty($this->Request->post['product-name-' . $DefaultLanguageID])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_name_empty');
                    }
                    if(empty($this->Request->post['product-description-' . $DefaultLanguageID])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_description_empty');
                    }

                    if(!empty($this->Request->post['product-price'])) {
                        $data['product-price'] = (int) $this->Request->post['product-price'];
                    }else {
                        $data['product-price'] = 0;
                    }

                    require_once LIB_PATH . DS . 'jdate/jdf.php';
                    if(!empty($this->Request->post['product-date'])) {
                        $parts = explode('/', $this->Request->post['product-date']);
                        if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                            $data['product-date'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                        }
                    }
                    if(!isset($data['product-date'])) {
                        $data['product-date'] = time();
                    }
                    if(!empty($this->Request->post['product-quantity'])) {
                        $data['product-quantity'] = (int) $this->Request->post['product-quantity'];
                    }else {
                        $data['product-quantity'] = 0;
                    }
                    if(!empty($this->Request->post['product-min-quantity-per-order'])) {
                        $data['product-min-quantity-per-order'] = (int) $this->Request->post['product-min-quantity-per-order'];
                    }else {
                        $data['product-min-quantity-per-order'] = 1;
                    }
                    if(!empty($this->Request->post['product-stock-status'])) {
                        $stock_status_id = (int) $this->Request->post['product-stock-status'];
                        if($stock_status_id && $Stock->getStock($stock_status_id)) {
                            $data['stock_status_id'] = $stock_status_id;
                        }
                    }
                    if(!isset($data['stock_status_id'])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_stock_status_not_selected');
                    }
                    if(!empty($this->Request->post['product-weight-unit'])) {
                        $weight_unit_id = (int) $this->Request->post['product-weight-unit'];
                        if($weight_unit_id && $Weight->getWeight($weight_unit_id)) {
                            $data['weight_id'] = $weight_unit_id;
                        }
                    }
                    if(!isset($data['weight_id'])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_weight_unit_not_selected');
                    }
                    if(!empty($this->Request->post['product-length-unit'])) {
                        $length_id = (int) $this->Request->post['product-length-unit'];
                        if($length_id && $Length->getLength($length_id)) {
                            $data['length_id'] = $length_id;
                        }
                    }
                    if(!isset($data['length_id'])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_length_not_selected');
                    }
                    if(!empty($this->Request->post['product-weight'])) {
                        $data['weight'] = (float) $this->Request->post['product-weight'];
                    }else {
                        $data['weight'] = 0;
                    }
                    if(!empty($this->Request->post['product-height'])) {
                        $data['height'] = (float) $this->Request->post['product-height'];
                    }else {
                        $data['height'] = 0;
                    }
                    if(!empty($this->Request->post['product-length'])) {
                        $data['length'] = (float) $this->Request->post['product-length'];
                    }else {
                        $data['length'] = 0;
                    }
                    if(!empty($this->Request->post['product-width'])) {
                        $data['width'] = (float) $this->Request->post['product-width'];
                    }else {
                        $data['width'] = 0;
                    }
                    if(!empty($this->Request->post['product-sort-order'])) {
                        $data['sort_order'] = (int) $this->Request->post['product-sort-order'];
                    }else {
                        $data['sort_order'] = 0;
                    }
                    if(!empty($this->Request->post['product-manufacturer-id'])) {
                        $manufacturer_id = (int)$this->Request->post['product-manufacturer-id'];
                        if($manufacturer_id && $Manufacturer->getManufacturerByID($manufacturer_id)) {
                            $data['manufacturer_id'] = $manufacturer_id;
                        }
                    }
                    if(!isset($data['manufacturer_id'])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_manufacturer_not_selected');
                    }
                    $data['categories_id'] = [];
                    if(!empty($this->Request->post['product-categories-id']) && count($this->Request->post['product-categories-id']) > 0) {
                        foreach ($this->Request->post['product-categories-id'] as $category_id) {
                            if((int) $category_id && $Category->getCategory((int) $category_id)) {
                                $data['categories_id'][] = $category_id;
                            }
                        }
                    }
                    if(count($data['categories_id']) == 0) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_category_not_selected');
                    }
                    $data['filters_id'] = [];
                    if(!empty($this->Request->post['product-filters-id']) && count($this->Request->post['product-filters-id']) > 0) {
                        foreach ($this->Request->post['product-filters-id'] as $filter_id) {
                            if((int) $filter_id && $Filter->getFilterItem((int) $filter_id)) {
                                $data['filters_id'][] = $filter_id;
                            }
                        }
                    }
                    if(count($data['filters_id']) == 0) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_filter_not_selected');
                    }
                    $data['attributes'] = [];
                    if(!empty($this->Request->post['product-attributes']) && count($this->Request->post['product-attributes']) > 0) {
                        foreach ($this->Request->post['product-attributes'] as $product_attribute) {
                            $attribute_id = isset($product_attribute['attribute-id']) ? (int) $product_attribute['attribute-id'] : 0;
                            if($attribute_id && $Attribute->getAttribute($attribute_id)) {

                                $attributeDetail = [];
                                $attributeDetail['attribute_id'] = $attribute_id;
                                $attributeDetail['names'] = [];
                                foreach ($this->Language->getLanguages() as $language) {
                                    if(!empty($product_attribute['attribute-value-' . $language['language_id']])) {
                                        $attributeDetail['names'][$language['language_id']] = $product_attribute['attribute-value-' . $language['language_id']];
                                    }
                                }
                                if(!empty($product_attribute['attribute-value-' . $DefaultLanguageID])) {
                                    $data['attributes'][] = $attributeDetail;
                                }
                            }
                        }
                    }
                    $data['options'] = [];
                    if(!empty($this->Request->post['product-options']) && count($this->Request->post['product-options']) > 0) {
                        foreach ($this->Request->post['product-options'] as $product_option) {
                            $option_id  = isset($product_option['option-id']) ? (int) $product_option['option-id'] : 0;

                            if($option_id && $optionGroup = $Option->getOptionGroup($option_id)) {
                                $optionGroupData = [];
                                $optionGroupData['option_group_id'] = $option_id;
                                $optionGroupData['required'] = isset($product_option['required']) ? (int) $product_option['required'] : 0;
                                $optionItemData = [];
                                $optionItems = [];
                                foreach ($Option->getOptionItems() as $optionItem) {
                                    $optionItems[] = $optionItem['option_item_id'];
                                }
                                if(isset($product_option['option-items'])) {

                                    foreach ($product_option['option-items'] as $option_item) {
                                        if(in_array($option_item['option-item-id'], $optionItems)) {
                                            $optionItemData['option_item_id'] = $option_item['option-item-id'];
                                            if(isset($option_item['quantity']) && $option_item['quantity'] < $data['product-quantity']) {
                                                $optionItemData['quantity'] = $option_item['quantity'];
                                            }else {
                                                $optionItemData['quantity'] = 0;
                                            }
                                            $optionItemData['subtract'] = isset($option_item['effect-on-quantity']) ? (int) $option_item['effect-on-quantity'] : 0;
                                            $optionItemData['price_sign'] = isset($option_item['price-sign']) && $option_item['price-sign'] == "-" ? "-" : "+";
                                            $optionItemData['weight_sign'] = isset($option_item['weight-sign']) && $option_item['weight-sign'] == "-" ? "-" : "+";
                                            if(isset($option_item['price']) && (int) $option_item['price'] > 0) {
                                                $optionItemData['price'] = (int) $option_item['price'];
                                            }else {
                                                $optionItemData['price'] = 0;
                                            }
                                            if(isset($option_item['weight']) && (int) $option_item['weight'] > 0) {
                                                $optionItemData['weight'] = (int) $option_item['weight'];
                                            }else {
                                                $optionItemData['weight'] = 0;
                                            }
                                            $optionGroupData['option_items'][] = $optionItemData;
                                        }
                                    }
                                    $data['options'][] = $optionGroupData;
                                }
                            }else {
                                $error = true;
                                $messages[] = $this->Language->get('error_product_option_group_invalid_id');
                            }
                        }
                    }

                    $data['specials'] = [];
                    if(!empty($this->Request->post['product-specials']) && count($this->Request->post['product-specials']) > 0) {
                        $priority = [];
                        foreach ($this->Request->post['product-specials'] as $product_special) {
                            if(isset($product_special['priority']) && !in_array($product_special['priority'], $priority)) {
                                $productSpecial = [];
                                $productSpecial['priority'] = $product_special['priority'];
                                $priority[] = $product_special['priority'];
                                if(isset($product_special['price'])) {
                                    $productSpecial['price'] = (int) $product_special['price'];
                                }else {
                                    $productSpecial['price'] = 0;
                                }
                                if(!empty($product_special['date-start'])) {
                                    $parts = explode('/', $product_special['date-start']);
                                    if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                                        $productSpecial['date_start'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                                    }
                                }
                                if(!isset($productSpecial['date_start'])) {
                                    $productSpecial['date_start'] = time();
                                }
                                if(!empty($product_special['date-end'])) {
                                    $parts = explode('/', $product_special['date-end']);
                                    if(count($parts) == 3 && jcheckdate($parts[1], $parts[2], $parts[0])){
                                        $productSpecial['date_end'] = jmktime(0,0,0, $parts[1], $parts[2], $parts[0]);
                                    }
                                }
                                if(!isset($productSpecial['date_end'])) {
                                    $productSpecial['date_end'] = time();
                                }
                                $data['specials'][] = $productSpecial;
                            }
                        }
                    }

                    $data['images'] = [];
                    if(!empty($this->Request->post['product-images']) && count($this->Request->post['product-images']) > 0) {
                        foreach ($this->Request->post['product-images'] as $product_image) {
                            if(isset($product_image['src']) && Validate::urlValid($product_image['src'])) {

                                $image = array(
                                    'src'   => $product_image['src'],
                                    'sort_order'    => isset($product_image['sort_order']) ? $product_image['sort_order'] : 0,
                                );
                                if(isset($product_image['default']) && $product_image['default'] == 'true') {
                                    $data['image'] = $product_image['src'];
                                }
                                $data['images'][] = $image;
                            }
                        }
                    }
                    if(empty($data['image'])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_product_default_image');
                    }

                    $json = [];
                    if(!$error) {
                        $delete = [];
                        $add = [];
                        if ($data['sort_order'] == 0) {
                            $rows = $Product->getProducts(array(
                                'sort' => 'sort_order',
                                'order' => 'DESC',
                                'language_id' => $DefaultLanguageID
                            ));
                            $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                            $data['sort_order'] = $oldSortOrder + 1;
                        }
                        if ($productInfo['price'] == $data['product-price']) {
                            unset($data['product-price']);
                        }
                        if ($productInfo['sort_order'] == $data['sort_order']) {
                            unset($data['sort_order']);
                        }
                        if ($productInfo['date_available_time'] == $data['product-date']) {
                            unset($data['product-date']);
                        }
                        if ($productInfo['quantity'] == $data['product-quantity']) {
                            unset($data['product-quantity']);
                        }
                        if ($productInfo['minimum'] == $data['product-min-quantity-per-order']) {
                            unset($data['product-min-quantity-per-order']);
                        }
                        if ($productInfo['stock_status_id'] == $data['stock_status_id']) {
                            unset($data['stock_status_id']);
                        }
                        if ($productInfo['weight'] == $data['weight']) {
                            unset($data['weight']);
                        }
                        if ($productInfo['length_id'] == $data['length_id']) {
                            unset($data['length_id']);
                        }
                        if ($productInfo['weight_id'] == $data['weight_id']) {
                            unset($data['weight_id']);
                        }
                        if ($productInfo['width'] == $data['width']) {
                            unset($data['width']);
                        }
                        if ($productInfo['height'] == $data['height']) {
                            unset($data['height']);
                        }
                        if ($productInfo['length'] == $data['length']) {
                            unset($data['length']);
                        }
                        if ($productInfo['manufacturer_id'] == $data['manufacturer_id']) {
                            unset($data['manufacturer_id']);
                        }
                        if ($productInfo['image'] == $data['image']) {
                            unset($data['image']);
                        }
                        foreach ($this->Language->getLanguages() as $language) {
                            if(isset($data['product_descriptions'][$language['language_id']]['name'])
                                && isset($productInfo['product_descriptions'][$language['language_id']]['name'])
                                && $data['product_descriptions'][$language['language_id']]['name'] == $productInfo['product_descriptions'][$language['language_id']]['name'] ) {
                                unset($data['product_descriptions'][$language['language_id']]['name']);
                            }else if(isset($productInfo['product_descriptions'][$language['language_id']]['name'])
                                && !isset($data['product_descriptions'][$language['language_id']]['name'])) {
                                $delete['product_descriptions'][$language['language_id']]['name'] = $productInfo['product_descriptions'];
                            }else if (!isset($productInfo['product_descriptions'][$language['language_id']]['name'])
                                && isset($data['product_descriptions'][$language['language_id']]['name'])) {
                                $add['product_descriptions'][$language['language_id']]['name'] =  $data['product_descriptions'][$language['language_id']]['name'];
                                unset($data['product_descriptions'][$language['language_id']]['name']);
                            }
                            if(isset($data['product_descriptions'][$language['language_id']])
                                && isset($productInfo['product_descriptions'][$language['language_id']]['description'])
                                && $data['product_descriptions'][$language['language_id']]['description'] == $productInfo['product_descriptions'][$language['language_id']]['description'] ) {
                                unset($data['product_descriptions'][$language['language_id']]['description']);
                            }else if(isset($productInfo['product_descriptions'][$language['language_id']]['description'])
                                && !isset($data['product_descriptions'][$language['language_id']]['description'])) {
                                $delete['product_descriptions'][$language['language_id']]['description'] = $productInfo['product_descriptions'];
                            }else if (!isset($productInfo['product_descriptions'][$language['language_id']]['description'])
                                && isset($data['product_descriptions'][$language['language_id']]['description'])) {
                                $add['product_descriptions'][$language['language_id']]['description'] =  $data['product_descriptions'][$language['language_id']]['description'];
                                unset($data['product_descriptions'][$language['language_id']]['description']);
                            }

                            if(empty($data['product_descriptions'][$language['language_id']])) {
                                unset($data['product_descriptions'][$language['language_id']]);
                            }
                        }
                        $json['data'] = $data;
                        $json['add'] = $add;
                        $json['delete'] = $delete;
                        $json['productInfo'] = $productInfo;
                        if(count($data['product_descriptions']) == 0) {
                            unset($data['product_descriptions']);
                        }

                        if(count($data) > 0) {
                            $Product->editProduct($productInfo['product_id'], $data);
                        }
                        if(count($add) > 0) {
                            $Product->insertProduct($add, $productInfo['product_id']);
                        }
                        if(count($delete) > 0) {
                            $Product->deleteProduct($productInfo['product_id'], $delete);
                        }
                        $json['status'] = 1;
                        $json['messages'] = [$this->Language->get('success_message')];
                        $json['redirect'] = ADMIN_URL . 'product/product/index?token=' . $_SESSION['token'];
                    }
                    if($error) {
                        $json = array(
                            'status'  => 0,
                            'messages'=> $messages
                        );
                    }
                    $this->Response->setOutPut(json_encode($json));
                    return;
                }else {
                    $data['Languages'] = $this->Language->getLanguages();
                    $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
                    $data['StocksStatus'] = $Stock->getStocks();
                    $data['Weights'] = $Weight->getWeights();
                    $data['Lengths'] = $Length->getLengths();
                    $data['Product'] = $productInfo;
//                    print_r($data['Product']);
                    $this->Response->setOutPut($this->render('product/product/edit', $data));
                    return;
                }
            }
        }
        return new Action('error/notFound', 'web');
    }

}