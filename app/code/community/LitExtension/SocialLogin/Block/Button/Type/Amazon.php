<?php
/**
 * @project     SocialLogin
 * @package     LitExtension_SocialLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_SocialLogin_Block_Button_Type_Amazon extends LitExtension_SocialLogin_Block_Button_Type{

    protected $_class = 'ico-az';
    protected $_title = 'Amazon';
    protected $_name = 'amazon';
    protected $_width = 800;
    protected $_height = 500;
    protected $_disconnect = 'le_sociallogin/amazon/disconnect';

    public function __construct($name = null, $class = null,$title=null){
        parent::__construct();

        $this->client = Mage::getSingleton('le_sociallogin/amazon_client');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userInfo = Mage::registry('le_sociallogin_amazon_userinfo');

        if(!($redirect = Mage::getSingleton('customer/session')->getBeforeAuthUrl())) {
            $redirect = Mage::helper('core/url')->getCurrentUrl();
        }

        // Redirect uri
        Mage::getSingleton('core/session')->setAmazonRedirect($redirect);

    }

}