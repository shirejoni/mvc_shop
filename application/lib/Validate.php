<?php


namespace App\lib;


class Validate
{
    public static function emailValid($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function urlValid($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function manufacturerIndexValid($name) {
        return preg_match("/^[a-zA-Z0-9\-_]+$/", $name);
    }

    public static function passwordValid($password) {
        return preg_match('/^[a-zA-Z0-9!@\#\$%&\*\-_\.]{8,64}$/', $password);
    }

    public static function mobileValid($mobile) {
        return preg_match('/^09[0-9]{9}$/', $mobile);
    }

    public static function zipCodeValid($zipCode) {
        return preg_match('/^[0-9]{10}$/', $zipCode);
    }

}