<?php


namespace App\model;


use App\Lib\Database;
use App\Lib\Registry;
use App\system\Model;

/**
 * @property Database Database
 */
class User extends Model
{
    private $user_id;
    private $email;
    private $user_group_id;
    private $first_name;
    private $last_name;
    private $image;
    private $code;
    private $ip;
    private $status;
    private $rows = [];



    public function getUserByEmail($email) {
        $this->Database->query("SELECT * FROM users WHERE email = :email", array(
            'email' => $email
        ));
        $row = $this->Database->getRow();
        if($row) {
            $this->user_id = $row['user_id'];
            $this->email = $row['email'];
            $this->user_group_id = $row['user_group_id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->image = $row['image'];
            $this->code = $row['code'];
            $this->ip = $row['ip'];
            $this->status = $row['status'];
            $this->rows[] = $row;
            return $row;
        }else {
            return false;
        }

    }

    public function login($option = array()) {
        if(!empty($this->user_id) && !empty($this->email)) {
            if(isset($option['ip']) && $this->edit($this->user_id, ['ip' => $option['ip']])) {
                $this->ip = $option['ip'];
            }
            $_SESSION['user'] = [];
            session_regenerate_id();
            $_SESSION['user'] = array(
                'user_id'   => $this->user_id,
                'email'     => $this->email,
                'status'    => $this->status
            );
            return true;
        }else {
            throw new \Exception("You Should First Get User Data form Database and then login");
        }
    }

    public function edit($user_id, $data) {
        $sql = "UPDATE users SET ";
        $query = [];
        $params = [];
        if(isset($data['email'])) {
            $query[] = 'email = :uEmail';
            $params['uEmail'] = $data['email'];
        }
        if(isset($data['password'])) {
            $query[] = 'password = :uPassword';
            $params['uPassword'] = $data['password'];
        }
        if(isset($data['user_group_id'])) {
            $query[] = 'user_group_id = :uGID';
            $params['uGID'] = $data['user_group_id'];
        }
        if(isset($data['first_name'])) {
            $query[] = 'first_name = :uFirstName';
            $params['uFirstName'] = $data['first_name'];
        }
        if(isset($data['last_name'])) {
            $query[] = 'last_name = :uLastName';
            $params['uLastName'] = $data['last_name'];
        }
        if(isset($data['image'])) {
            $query[] = 'image = :uImage';
            $params['uImage'] = $data['image'];
        }
        if(isset($data['code'])) {
            $query[] = 'code = :uCode';
            $params['uCode'] = $data['code'];
        }
        if(isset($data['ip'])) {
            $query[] = 'ip = :uIp';
            $params['uIp'] = $data['ip'];
        }
        if(isset($data['status'])) {
            $query[] = 'status = :uStatus';
            $params['uStatus'] = $data['status'];
        }
        if(isset($data['date_updated'])) {
            $query[] = 'date_updated = :uDUpdated';
            $params['uDUpdated'] = $data['date_updated'];
        }
        $sql .= implode(' , ', $query);
        $sql .= " WHERE user_id = :uID ";
        $params['uID'] = $user_id;
        $this->Database->query($sql, $params);
        if($this->Database->numRows() > 0) {
            return true;
        }else {
            return false;
        }
    }

}