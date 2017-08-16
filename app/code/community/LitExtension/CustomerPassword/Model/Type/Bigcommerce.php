<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CustomerPassword_Model_Type_Bigcommerce
    extends LitExtension_CustomerPassword_Model_Type
{
    public function validatePassword($customer, $username, $password, $pw_hash){
        $data = array(
            'login_email' => $username,
            'login_pass' => $password,
        );
        $cart_url = Mage::getStoreConfig('lecupd/general/url');
        $check = $this->_request($cart_url, $data);
        return $check;
    }

    protected function _checkHeader($header) {
        preg_match('/Location:(.+)/', $header, $match);
        if ($match) {
            if (!strpos($match[1], 'login')) {
                return true;
            }
        }
        return false;
    }

    protected function _request($url, $data = array()) {
        $options = http_build_query($data);
        $ch = curl_init($url . '/login.php?action=check_login');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response && $this->_checkHeader($response)) {
            return true;
        }
        return false;
    }
}