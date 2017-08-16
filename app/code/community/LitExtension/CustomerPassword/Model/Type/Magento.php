<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Magento
    extends LitExtension_CustomerPassword_Model_Type {

    public function validatePassword($customer, $username, $string, $encrypted) {
        $hashArr = explode(':', $encrypted);
        switch (count($hashArr)) {
            case 1:
                return $this->hash($string) === $encrypted;
            case 2:
                return $this->hash($hashArr[1] . $string) === $hashArr[0] || $this->hash256($hashArr[1] . $string) === $hashArr[0];
        }
        return false;
    }
	
	public function hash($data)
    {
        return md5($data);
    }
	
	public function hash256($data)
	{
        return hash('sha256', $data);
    }

}
