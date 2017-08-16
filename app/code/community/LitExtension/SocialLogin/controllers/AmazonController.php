<?php
/**
 * @project     SocialLogin
 * @package     LitExtension_SocialLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_SocialLogin_AmazonController extends Mage_Core_Controller_Front_Action
{
    protected $referer = null;
    protected $flag = null;

    public function _loginFinalize(){
        $this->_redirectUrl(Mage::getUrl('customer/account'));
    }

    public function requestAction()
    {
        $client = Mage::getSingleton('le_sociallogin/amazon_client');
        // CSRF protection
        Mage::getSingleton('core/session')->setAmazonCsrf($csrf = md5(uniqid(rand(), TRUE)));
        $client->setState($csrf);

        if(!($client->isEnabled())) {
            Mage::helper('le_sociallogin')->redirect404($this);
        }
        $mainw_protocol = $this->getRequest()->getParam('mainw_protocol');
        Mage::getSingleton('core/session')->setIsSecure($mainw_protocol);
        $this->_redirectUrl($client->createRequestUrl());
    }

    public function redirectAction(){
        $this->_loginFinalize();
    }

    public function connectAction()
    {
        try {
            //var_dump($_SERVER['REQUEST_URL']);
            $this->_connectCallback();
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        if (!empty($this->referer)) {
            if(empty($this->flag)){
                $this->_loginFinalize();
            }else{
                echo '
                <script type="text/javascript">
                    window.close();
                </script>
                ';
            }

        } else {
            //Mage::helper('le_sociallogin')->redirect404($this);
            $this->_loginFinalize();
        }
    }

    public function disconnectAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        try {
            $this->_disconnectCallback($customer);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        if (!empty($this->referer)) {
            $this->_redirectUrl($this->referer);
        } else {
            Mage::helper('le_sociallogin')->redirect404($this);
        }
    }

    protected function _disconnectCallback(Mage_Customer_Model_Customer $customer)
    {
        $this->referer = Mage::getUrl('le_sociallogin/account/amazon');

        Mage::helper('le_sociallogin/amazon')->disconnect($customer);

        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected your %s account from our store account.', $this->__('Amazon'))
            );
    }

    protected function _connectCallback()
    {
        $errorCode = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('access_token');
        if (!($errorCode || $code) ) {
            // Direct route access - deny
            return;
        }

        $this->referer = Mage::getSingleton('core/session')
            ->getAmazonRedirect();

        if ($errorCode) {
            // amazon API read light - abort
            if ($errorCode === 'access_denied') {
                $this->flag = "noaccess";
                echo '<script type="text/javascript">window.close();</script>';
            }
            return;
        }

        if ($code) {
            $attributeModel = Mage::getModel('eav/entity_attribute');
            $attributegId = $attributeModel->getIdByCode('customer', 'le_sociallogin_aid');
            $attributegtoken = $attributeModel->getIdByCode('customer', 'le_sociallogin_atoken');
            if($attributegId == false || $attributegtoken == false){
                echo "Attribute `le_sociallogin_aid` or `le_sociallogin_atoken` not exist !";
                exit();
            }
            // Amazon API green light - proceed
            $client = Mage::getSingleton('le_sociallogin/amazon_client');

            $userInfo = $client->api('/userinfo');
//            $token = $client->getAccessToken();

            $customersByAmazonId = Mage::helper('le_sociallogin/amazon')
                ->getCustomersByAmazonId($userInfo->user_id);

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                // Logged in user
                if ($customersByAmazonId->count()) {
                    // Amazon account already connected to other account - deny
                    Mage::getSingleton('core/session')
                        ->addNotice(
                            $this->__('Your %s account is already connected to one of our store accounts.', $this->__('Amazon'))
                        );

                    return;
                }

                // Connect from account dashboard - attach
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                Mage::helper('le_sociallogin/amazon')->connectByAmazonId(
                    $customer,
                    $userInfo->user_id
//                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('Your %1$s account is now connected to your store account. You can now login using our %1$s Connect button or using store account credentials you will receive to your email address.', $this->__('Amazon'))
                );

                return;
            }

            if ($customersByAmazonId->count()) {
                // Existing connected user - login
                $customer = $customersByAmazonId->getFirstItem();

                Mage::helper('le_sociallogin/amazon')->loginByCustomer($customer);

                Mage::getSingleton('core/session')
                    ->addSuccess(
                        $this->__('You have successfully logged in using your %s account.', $this->__('Amazon'))
                    );

                return;
            }

            $customersByEmail = Mage::helper('le_sociallogin/amazon')
                ->getCustomersByEmail($userInfo->email);

            if ($customersByEmail->count()) {
                // Email account already exists - attach, login
                $customer = $customersByEmail->getFirstItem();

                Mage::helper('le_sociallogin/amazon')->connectByAmazonId(
                    $customer,
                    $userInfo->user_id
//                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('We have discovered you already have an account at our store. Your %s account is now connected to your store account.', $this->__('Amazon'))
                );

                return;
            }

            // New connection - create, attach, login
            if (empty($userInfo->name)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your %s first name. Please try again.', $this->__('Amazon'))
                );
            }


            Mage::helper('le_sociallogin/amazon')->connectByCreatingAccount(
                $userInfo->email,
                $userInfo->name,
                'amz',
                $userInfo->user_id
//                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your %1$s account is now connected to your new user accout at our store. Now you can login using our %1$s Connect button or using store account credentials you will receive to your email address.', $this->__('Amazon'))
            );
        }
    }

}