<?php

namespace App\Admin\Controller;

use App\lib\Action;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Manufacturer;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 */
class ControllerProductManufacturer extends Controller {

    public function add() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->post['manufacturer-post'])) {
            /** @var Manufacturer $Manufacturer */
            $Manufacturer = $this->load("Manufacturer", $this->registry);
            $languages = $this->Language->getLanguages();
            $defaultLanguageID = $this->Language->getDefaultLanguageID();
            foreach ($languages as $language) {
                if(!empty($this->Request->post['manufacturer-name-' . $language['language_id']])) {
                    $data['manufacturer_names'][$language['language_id']] = $this->Request->post['manufacturer-name-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['manufacturer-name-' . $defaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_manufacturer_name_empty');
            }

            if(!empty($this->Request->post['manufacturer-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['manufacturer-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }

            if(!empty($this->Request->post['manufacturer-url']) && Validate::manufacturerIndexValid($this->Request->post['manufacturer-url'])) {
                if(!empty($Manufacturer->getManufacturerByUrl($this->Request->post['manufacturer-url']))) {
                    $error = true;
                    $messages[] = $this->Language->get('error_manufacturer_exist');
                }else {
                    $data['url'] = $this->Request->post['manufacturer-url'];
                }
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_manufacturer_url_empty');
            }

            if(!empty($this->Request->post['manufacturer-image']) && Validate::urlValid($this->Request->post['manufacturer-image'])) {
                $data['image'] = $this->Request->post['manufacturer-image'];
            }else {
                $data['image'] = '';
            }

            $json = [];

            if(!$error) {

                if($data['sort_order'] == 0) {
                    $rows = $Manufacturer->getManufacturers(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $Manufacturer->insertManufacturer($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'product/manufacturer/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));


        }else {
            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
            $this->Response->setOutPut($this->render("product/manufacturer/add", $data));
        }
    }

    public function index() {
        $data = [];
        /** @var Manufacturer $Manufacturer */
        $Manufacturer = $this->load('Manufacturer', $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $data['Manufacturers'] = $Manufacturer->getManufacturers(array(
            'language_id'   => $this->Language->getLanguageID(),
        ));
        $this->Response->setOutPut($this->render('product/manufacturer/index', $data));
    }

    public function status() {
        if(isset($this->Request->post['manufacturer_id']) && isset($this->Request->post['manufacturer_status'])) {
            $manufacturer_id = (int) $this->Request->post['manufacturer_id'];
            $manufacturer_status = (int) $this->Request->post['manufacturer_status'] == 1 ? 1 : 0;
            /** @var Manufacturer $Manufacturer */
            $Manufacturer = $this->load("Manufacturer", $this->registry);
            $json = [];
            if($manufacturer_id &&  $manufacturer = $Manufacturer->getManufacturerByID($manufacturer_id)) {
                $Manufacturer->editManufacturer($manufacturer_id, array(
                    'status'    => $manufacturer_status
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

    public function delete() {
        if(!empty($this->Request->post['manufacturers_id'])) {
            $json = [];
            /** @var Manufacturer $Manufacturer */
            $Manufacturer = $this->load('Manufacturer', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['manufacturers_id'] as $manufacturer_id) {
                if((int) $manufacturer_id &&  $manufacturer = $Manufacturer->getManufacturerByID((int) $manufacturer_id)) {
                    $Manufacturer->deleteManufacturer((int) $manufacturer_id);
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
                $data['Manufacturers'] = $Manufacturer->getManufacturers(array(
                    'language_id'   => $this->Language->getLanguageID(),
                ));
                $json['data'] = $this->render('product/manufacturer/manufacturer_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

}