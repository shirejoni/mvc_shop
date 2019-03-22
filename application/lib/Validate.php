<?php


namespace App\lib;


class Validate
{
    public static function emailValid($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function passwordValid($password) {
        return preg_match('/^[a-zA-Z0-9!@\#\$%&\*\-_\.]{8,64}$/', $password);
    }

}