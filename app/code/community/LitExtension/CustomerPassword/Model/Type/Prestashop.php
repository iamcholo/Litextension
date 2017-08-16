<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Prestashop
    extends LitExtension_CustomerPassword_Model_Type {

    public function validatePassword($customer, $username, $string, $encrypted) {
        $part = explode(":", $encrypted);
        $pass = $part[0];
        $cookie_key = isset($part[1]) ? $part[1] : '';
        if (!$cookie_key) {
            return false;
        }
        if (md5($cookie_key . $string) == $pass) {
            return true;
        }
        return false;
    }
}