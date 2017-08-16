<?php

/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CustomerPassword_Model_Type_Cscart
    extends LitExtension_CustomerPassword_Model_Type {
    
    public function validatePassword($customer, $username, $string, $encrypted) {
        $part = explode(" ", $encrypted);
        $password = $part[0];
        $salt = isset($part[1]) ? $part[1] : '';
        if (empty($salt)) {
            if (md5($string) == $password) {
                return true;
            }
        } else {
            if (md5(md5($string) . md5($salt)) == $password) {
                return true;
            }
        }
        return false;
    }
}