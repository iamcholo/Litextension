<?php


class LitExtension_Onestepcheckout_Model_Login_Ajaxlogin extends LitExtension_Onestepcheckout_Model_Login_Validator
{
    public function _construct()
    {
        parent::_construct();

        $this->setEmail($_POST['email']);
        $this->setSinglePassword($_POST['password']);

        if ($this->_result == '')
        {
            $this->loginUser();
        }
    }

    private function loginUser()
    {
        $session = Mage::getSingleton('customer/session');

        try
        {
            $session->login($this->_userEmail, $this->_userPassword);
            $customer = $session->getCustomer();

            $session->setCustomerAsLoggedIn($customer);

            $this->_result .= 'success';
        }
        catch(Exception $ex)
        {
            $this->_result .= 'wronglogin,';
        }
    }

    public function getResult()
    {
        return $this->_result;
    }
}

?>
