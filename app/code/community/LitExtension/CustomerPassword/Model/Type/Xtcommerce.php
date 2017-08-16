<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Xtcommerce
    extends LitExtension_CustomerPassword_Model_Type{

    public function validatePassword($customer, $username, $password, $pw_hash){
        $check = ($pw_hash == md5($password));
        return $check;
    }

}