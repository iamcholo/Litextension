<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Cubecart
    extends LitExtension_CustomerPassword_Model_Type {

    public function validatePassword($customer, $username, $string, $encrypted) {
        $part = explode(":", $encrypted);
        $md5 = $part[0];
        $salt = isset($part[1]) ? $part[1] : '';
        if (!empty($salt)) {
            if (md5(md5($salt) . md5($string)) == $md5) {
                return true;
            } elseif (md5($this->sanitizeVar($string)) == $md5) {
                return true;
            } elseif (hash('whirlpool', $salt.$string.$salt) == $md5) {
                return true;
            }
        } else {
            if (md5($this->sanitizeVar($string)) == $md5) {
                return true;
            }
        }
        return false;
    }
    
    public function sanitizeVar($text) {
        $text = htmlspecialchars($text, ENT_COMPAT);
        return $text;
    }

}
