<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type {

    public function run($customer, $username, $password){
        if($customer_id = $customer->getId()){
            $pw_hash = $customer->getPasswordHash();
            if(!$pw_hash){
                return false;
            }
            $check = $this->validatePassword($customer, $username, $password, $pw_hash);
            if($check){
                $customer->setPassword($password);
                try{
                    $customer->save();
                }catch (Exception $e){}
                return true;
            }
        }
        return false;
    }

    public function validatePassword($customer, $username, $password, $pw_hash){
        return false;
    }
}