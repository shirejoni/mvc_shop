<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Address extends Model
{
    public function getProvinces() {
        $this->Database->query("SELECT * FROM province");
        if($this->Database->hasRows()) {
            return $this->Database->getRows();
        }
        return false;
    }

    public function getProvinceCities($province_id) {
        $this->Database->query("SELECT * FROM city WHERE province_id = :pID", array(
            'pID'   => $province_id
        ));
        if($this->Database->hasRows()) {
            return $this->Database->getRows();
        }
        return false;
    }

    public function getCityByID($city_id) {
        $this->Database->query("SELECT * FROM city WHERE city_id = :cID", array(
            'cID'   => $city_id
        ));
        if($this->Database->hasRows()) {
            return $this->Database->getRow();
        }
        return false;
    }


    public function insertAddress($data) {
        $this->Database->query("INSERT INTO address (first_name, last_name, customer_id, address, province_id, city_id, zip_code) VALUES 
        (:aFName, :aLName, :aCID, :aAddress, :aPID, :aCityID, :aZipCode)", array(
            'aFName'    => $data['first_name'],
            'aLName'    => $data['last_name'],
            'aCID'      => $data['customer_id'],
            'aCityID'   => $data['city_id'],
            'aAddress'  => $data['address'],
            'aPID'      => $data['province_id'],
            'aZipCode'  => $data['zip_code']
        ));
        $address_id = $this->Database->insertId();
        return $address_id;
    }

    public function getAddressByCustomerID($customer_id) {
        $this->Database->query("SELECT * FROM address WHERE customer_id = :cID", array(
            'cID'   => $customer_id
        ));
        if($this->Database->hasRows()) {
            return $this->Database->getRows();
        }
        return false;
    }

    public function getAddressByID($address_id) {
        $this->Database->query("SELECT * FROM address WHERE address_id = :aID", array(
            'aID'   => $address_id
        ));
        if($this->Database->hasRows()) {
            return $this->Database->getRow();
        }
        return false;
    }
    public function deleteAddressByID($address_id) {
        $this->Database->query("DELETE FROM address WHERE address_id = :aID", array(
            'aID'   => $address_id,
        ));
        return $this->Database->numRows();
    }
}