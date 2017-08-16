<?php


class LitExtension_Onestepcheckout_Model_Login_Validator extends Varien_Object
{    
    protected $_userEmail;
    
    protected $_userPassword;
    
    protected $_userFirstName;
    
    protected $_userLastName;
    
    protected $_userNewsletter;
    
    protected $_userId;
    
    protected $_result;
    
    public function _construct() 
    {
        parent::_construct();
    }
    
    protected function setEmail($email = '')
    {
        if (!Zend_Validate::is($email, 'EmailAddress'))
        {
            $this->_result .= 'wrongemail,';
        }
        else
        {
            $this->_userEmail = $email;
        }
    }
    
    protected function setSinglePassword($password){
        $sanitizedPassword = str_replace(array('\'', '%', '\\', '/', ' '), '', $password);
        
        if (strlen($sanitizedPassword) > 16 || $sanitizedPassword != trim($password))
        {
            $this->_result .= 'wrongpw,';
        }
        
        $this->_userPassword = $sanitizedPassword;
    }
    
    public function getResult()
    {
        return $this->_result;
    }
}
?>