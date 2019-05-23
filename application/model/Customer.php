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
            'cID'   => $customer_id
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
            return $row;

        }
        return false;
    }

    public function
    getCustomerByEmail($email) {
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
            return $row;
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
            return $row;
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

    public function login() {
        if(!empty($this->customer_id) && !empty($this->email)) {

            $_SESSION['customer'] = [];
            $_SESSION['session_old_id'] = session_id();
            session_regenerate_id();
            $_SESSION['customer'] = array(
                'customer_id'   => $this->customer_id,
                'email'     => $this->email,
                'status'    => $this->status
            );
            return true;
        }else {
            throw new \Exception("You Should First Get User Data form Database and then login");
        }
    }

    public function edit($customer_id, $data) {
        $sql = "UPDATE customer SET ";
        $query = [];
        $params = [];
        if(isset($data['email'])) {
            $query[] = 'email = :cEmail';
            $params['cEmail'] = $data['email'];
        }
        if(isset($data['password'])) {
            $query[] = 'password = :uPassword';
            $params['uPassword'] = $data['password'];
        }
        if(isset($data['mobile'])) {
            $query[] = 'mobile = :cMobile';
            $params['cMobile'] = $data['mobile'];
        }
        if(isset($data['language_id'])) {
            $query[] = 'language_id = :lID';
            $params['lID'] = $data['language_id'];
        }
        if(isset($data['first_name'])) {
            $query[] = 'first_name = :cFirstName';
            $params['cFirstName'] = $data['first_name'];
        }
        if(isset($data['last_name'])) {
            $query[] = 'last_name = :cLastName';
            $params['cLastName'] = $data['last_name'];
        }
        if(isset($data['cart'])) {
            $query[] = 'cart = :cCart';
            $params['cCart'] = json_encode($data['cart']);
        }
        if(isset($data['wishlist'])) {
            $query[] = 'wishlist = :cWishlist';
            $params['cWishlist'] = json_encode($data['wishlist']);
        }
        if(isset($data['newsletter'])) {
            $query[] = 'newsletter = :cNewsLetter';
            $params['cNewsLetter'] = $data['newsletter'];
        }
        if(isset($data['address_id'])) {
            $query[] = 'address_id = :cAddressID';
            $params['cAddressID'] = $data['address_id'];
        }
        if(isset($data['status'])) {
            $query[] = 'status = :cStatus';
            $params['cStatus'] = $data['status'];
        }
        if(isset($data['token'])) {
            $query[] = 'token = :cToken';
            $params['cToken'] = $data['token'];
        }

        if(isset($data['code'])) {
            $query[] = 'code = :cCode';
            $params['cCode'] = $data['code'];
        }
        $sql .= implode(' , ', $query);
        $sql .= " WHERE customer_id = :cID ";
        $params['cID'] = $customer_id;
        $this->Database->query($sql, $params);
        if($this->Database->numRows() > 0) {
            return true;
        }else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @return mixed
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return mixed
     */
    public function getWishlist()
    {
        return $this->wishlist;
    }

    /**
     * @return mixed
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @return mixed
     */
    public function getAddressId()
    {
        return $this->address_id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->date_added;
    }


}