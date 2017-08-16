<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

require_once Mage::getModuleDir('Model', 'Mage_Customer') . DS . 'Model' . DS . 'Customer.php';

class LitExtension_CustomerPassword_Model_Customer
    extends Mage_Customer_Model_Customer{

    public function authenticate($login, $password){
        $this->loadByEmail($login);
        if ($this->getConfirmation() && $this->isConfirmationRequired()) {
            throw Mage::exception('Mage_Core', Mage::helper('customer')->__('This account is not confirmed.'),
                self::EXCEPTION_EMAIL_NOT_CONFIRMED
            );
        }
        if (!$this->validatePassword($password)) {
            $check = false;
            $cart_type = Mage::getStoreConfig('lecupd/general/type');
            if($cart_type){
                $model_name = 'lecupd/type_' . $cart_type;
                $model = @Mage::getModel($model_name);
                if($model){
                    $check = $model->run($this, $login, $password);
                }
            }
            if(!$check){
                throw Mage::exception('Mage_Core', Mage::helper('customer')->__('Invalid login or password.'),
                    self::EXCEPTION_INVALID_EMAIL_OR_PASSWORD
                );
            }
        }
        Mage::dispatchEvent('customer_customer_authenticated', array(
            'model'    => $this,
            'password' => $password,
        ));

        return true;
    }
}