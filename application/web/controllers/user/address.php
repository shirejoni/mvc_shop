<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Address;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 */
class ControllerUserAddress extends Controller {

    public function index() {
        $data = [];
        /** @var Address $Address */
        $Address = $this->load("Address", $this->registry);
        $customer_id = $_SESSION['customer']['customer_id'];
        $data['Addresses'] = $Address->getAddressByCustomerID($customer_id);
        $this->Response->setOutPut($this->render('user/address/index', $data));
    }

    public function add() {
        $data = [];
        $messages = [];
        $error = false;
        /** @var Address $Address */
        $Address = $this->load("Address", $this->registry);
        if(isset($this->Request->post['address-post'])) {
            if(!empty($this->Request->post['address-first-name'])) {
                $data['first_name'] = $this->Request->post['address-first-name'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_first_name_empty');
            }
            if(!empty($this->Request->post['address-last-name'])) {
                $data['last_name'] = $this->Request->post['address-last-name'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_last_name_empty');
            }
            if(!empty($this->Request->post['address-address'])) {
                $data['address'] = $this->Request->post['address-address'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_address_empty');
            }
            if(!empty($this->Request->post['address-zip-code']) && Validate::zipCodeValid($this->Request->post['address-zip-code'])) {
                $data['zip_code'] = $this->Request->post['address-zip-code'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_zip_code_empty');
            }
            if(isset($this->Request->post['address-province-id']) && isset($this->Request->post['address-city-id'])) {
                $city_id = (int) $this->Request->post['address-city-id'];
                $province_id = (int) $this->Request->post['address-province-id'];
                if($city_id && $province_id && $city = $Address->getCityByID($city_id)) {
                    if($city['province_id'] == $province_id) {
                        $data['province_id'] = $province_id;
                        $data['city_id']    = $city_id;
                    }else {
                        $error = true;
                        $messages[] = $this->Language->get('error_province_empty');
                    }
                }else {
                    $error = true;
                    $messages[] = $this->Language->get('error_city_empty');
                }
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_city_empty');
            }
            $json = [];
            if(!$error) {
                $data['customer_id'] = $_SESSION['customer']['customer_id'];
                $Address->insertAddress($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = URL . 'user/address/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            $data['Provinces'] = $Address->getProvinces();
            $this->Response->setOutPut($this->render("user/address/add", $data));
        }
    }

    public function getcity() {
        if(isset($this->Request->post['address-post']) && isset($this->Request->post['province_id'])) {
            $province_id = (int) $this->Request->post['province_id'];
            if($province_id) {
                /** @var Address $Address */
                $Address = $this->load("Address", $this->registry);
                $cities = $Address->getProvinceCities($province_id);
                $json = [];
                if($cities) {
                    $json = array(
                        'status'    => 1,
                        'cities'    => $cities
                    );
                }else {
                    $json = array(
                        'status' => 0,
                        'messages'  => [$this->Language->get('error_done')]
                    );
                }
                $this->Response->setOutPut(json_encode($json));
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

    public function delete() {
        if(!empty($this->Request->post['addresses_id'])) {
            $json = [];
            /** @var Address $Address */
            $Address = $this->load('Address', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['addresses_id'] as $address_id) {
                $address = $Address->getAddressByID((int) $address_id);
                if($address && (int) $address_id) {
                    $Address->deleteAddressByID((int) $address_id);
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
                $customer_id = $_SESSION['customer']['customer_id'];
                $data['Addresses'] = $Address->getAddressByCustomerID($customer_id);
                $json['data'] = $this->render('user/address/addresses_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

}