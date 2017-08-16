<?php
/**
 * @project     AjaxLogin
 * @package LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_Model_Ajaxregister extends LitExtension_AjaxLogin_Model_Validator
{
    public function _construct()
    {
        parent::_construct();

        $this->_result = '';
        $this->_userId = -1;

        if (isset($_POST['captcha'])) {
            $data = $_POST['captcha'];
            $this->setEmail($_POST['email']);
            $_captcha = Mage::getModel('customer/session')->getData('form-validate-captcha_word');
            if ($_captcha['data'] != $data) {
                $this->_result .= 'wrongcaptcha,';
            } elseif ($this->isEmailExist()) {
                $this->_result .= 'emailisexist,';
            }elseif($this->isnotEmail($this->_userEmail)== true){
                $this->_result .= 'isnotemail,';
            } else {
                $this->_result = 'success';
            }
        } else {
            $this->setEmail($_POST['email']);
            if ($this->isEmailExist()) {
                $this->_result .= 'emailisexist,';
            }elseif($this->isnotEmail($this->_userEmail)== true){
                $this->_result .= 'isnotemail,';
            }else {
                $this->_result = 'success';
            }
        }
    }
}

?>
