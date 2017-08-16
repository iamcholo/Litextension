<?php

class LitExtension_CustomerPassword_Model_Type_Nopcommerce
    extends LitExtension_CustomerPassword_Model_Type
{
    public function validatePassword($customer, $username, $password, $pw_hash)
    {
        $check = false;
        $hash = $this->convertHashToEncryptAndSalt($pw_hash);
        if(!$hash){
            return $check;
        }
        $encrypt = $hash['encrypt'];
        $salt = $hash['salt'];
        $password_hash = $this->hashPassword($password, $salt);
        return $encrypt == $password_hash;
    }

    public function hashPassword($password, $salt)
    {
        $concat = $password . $salt;
        return strtoupper(sha1($concat));
    }

    public function convertHashToEncryptAndSalt($hash)
    {
        $split = explode(':', $hash);
        $encrypt = isset($split[0]) ? $split[0] : false;
        $salt = isset($split[1]) ? $split[1] : false;
        if(!$encrypt || !$salt){
            return false;
        }
        return array(
            'encrypt' => $encrypt,
            'salt' => $salt,
        );
    }

    /**
     * Password hash in db save with construct: Password:PasswordSalt
     * Code hash password of nopCommerce in file: Libraries\Nop.Services\Security\EncryptionService.cs
     */
}
