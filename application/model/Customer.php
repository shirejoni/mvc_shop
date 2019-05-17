<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 * @property Language Language
 */
class Customer extends Model
{
    private $customer_id;
    private $first_name;
    private $last_name;
    private $email;
    private $mobile;
    private $cart;
    private $wishlist;
    private $newsletter;
    private $address_id;
    private $status;
    private $token;
    private $code;
    private $date_added;
    private $language_id;

    public function getCustomerByID($customer_id) {
        $this->Database->query("SELECT * FROM customer WHERE customer_id = :cID", array(
            'customer_id'   => $customer_id
        ));
        if($this->Database->hasRows()) {
            $row = $this->Database->getRow();
            $this->customer_id = $row['customer_id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->mobile = $row['mobile'];
            $this->language_id = $row['language_id'];
            $this->cart = json_decode($row['cart']);
            $this->wishlist = json_decode($row['wishlist']);
            $this->token = $row['token'];
            $this->code = $row['code'];
            $this->status = $row['status'];
            $this->address_id = $row['address_id'];
            $this->newsletter = $row['newsletter'];
            $this->date_added = $row['date_added'];
        }
        return false;
    }

    public function getCustomerByEmail($email) {
        $this->Database->query("SELECT * FROM customer WHERE email = :email", array(
            'email'   => $email
        ));
        if($this->Database->hasRows()) {
            $row = $this->Database->getRow();
            $this->customer_id = $row['customer_id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->mobile = $row['mobile'];
            $this->language_id = $row['language_id'];
            $this->cart = json_decode($row['cart']);
            $this->wishlist = json_decode($row['wishlist']);
            $this->token = $row['token'];
            $this->code = $row['code'];
            $this->status = $row['status'];
            $this->address_id = $row['address_id'];
            $this->newsletter = $row['newsletter'];
            $this->date_added = $row['date_added'];
        }
        return false;
    }

    public function getCustomerByMobile($mobile) {
        $this->Database->query("SELECT * FROM customer WHERE mobile = :mobile", array(
            'mobile'   => $mobile
        ));
        if($this->Database->hasRows()) {
            $row = $this->Database->getRow();
            $this->customer_id = $row['customer_id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->mobile = $row['mobile'];
            $this->language_id = $row['language_id'];
            $this->cart = json_decode($row['cart']);
            $this->wishlist = json_decode($row['wishlist']);
            $this->token = $row['token'];
            $this->code = $row['code'];
            $this->status = $row['status'];
            $this->address_id = $row['address_id'];
            $this->newsletter = $row['newsletter'];
            $this->date_added = $row['date_added'];
        }
        return false;
    }

    public function insertCustomer($data) {
        $this->Database->query("INSERT INTO customer (first_name, last_name, email, mobile, password, language_id, cart, wishlist, newsletter, address_id, status, date_added) VALUES 
        (:cFName, :cLName, :cEmail, :cMobile, :cPassword, :cLanguageID, :cCart, :cWishlist, :cNewsLetter, :cAddressID, :cStatus, :cDAdded)", array(
            'cFName'    => $data['first_name'],
            'cLName'    => $data['last_name'],
            'cEmail'    => $data['email'],
            'cMobile'    => $data['mobile'],
            'cPassword'    => $data['password'],
            'cLanguageID'   => $this->Language->getLanguageID(),
            'cCart'     => json_encode([]),
            'cWishlist' => json_encode([]),
            'cNewsLetter'   => 1,
            'cAddressID'    => 0,
            'cStatus'       => 1,
            'cDAdded'       => time()
        ));
        return $this->Database->insertId();
    }

}