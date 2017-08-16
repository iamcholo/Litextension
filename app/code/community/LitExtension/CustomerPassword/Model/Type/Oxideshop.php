<?php

/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Oxideshop
    extends LitExtension_CustomerPassword_Model_Type {
    
    public function validatePassword($customer, $username, $password, $pw_hash){
        $part = explode(":", $pw_hash);
        if(!$part[1]){
            return false;
        }
        $password_hash = $part[0];
        $hash = $part[1];
        if($password_hash == hash('sha512', $password.$hash)){
            return true;
        }elseif($password_hash == md5($password . hex2bin($hash))){
            return true;
        }
        return false;
    }
}