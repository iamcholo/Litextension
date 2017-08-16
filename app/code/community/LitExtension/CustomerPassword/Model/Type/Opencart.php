<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Opencart
    extends LitExtension_CustomerPassword_Model_Type {

    public function validatePassword($customer, $username, $string, $encrypted) {
        $part = explode(":", $encrypted);
        $sha1 = $part[0];
        $salt = isset($part[1]) ? $part[1] : '';
        if (!empty($salt)) {
            if (sha1($salt . sha1($salt . sha1($string))) == $sha1) {
                return true;
            } elseif (md5($string) == $sha1) {
                return true;
            }
        } else {
            if (md5($string) == $sha1) {
                return true;
            }
        }
        return false;
    }

}
