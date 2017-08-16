<?php
/**
 * @project     SocialLogin
 * @package     LitExtension_SocialLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_SocialLogin_Block_Button_Type_Paypal extends LitExtension_SocialLogin_Block_Button_Type{

    protected $_class = 'ico-pp';
    protected $_title = 'Paypal';
    protected $_name = 'paypal';
    protected $_width = 400;
    protected $_height = 600;
    protected $_disconnect = 'le_sociallogin/paypal/disconnect';

    public function __construct($name = null, $class = null,$title=null){
        parent::__construct();

        $this->client = Mage::getSingleton('le_sociallogin/paypal_client');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userInfo = Mage::registry('le_sociallogin_paypal_userinfo');

        if(!($redirect = Mage::getSingleton('customer/session')->getBeforeAuthUrl())) {
            $redirect = Mage::helper('core/url')->getCurrentUrl();
        }

        // Redirect uri
        Mage::getSingleton('core/session')->setPaypalRedirect($redirect);

    }

}