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

}