<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartServiceMigrate_Model_System_Config_Source_Type{

    public function toOptionArray() {
        return array(
            array('value' => 'americommerce', 'label' => Mage::helper('lecsmg')->__('AmeriCommerce')),
            array('value' => 'shopify', 'label' => Mage::helper('lecsmg')->__('Shopify')),
            array('value' => 'bigcommerce', 'label' => Mage::helper('lecsmg')->__('Bigcommerce')),
            array('value' => '3dcart', 'label' => Mage::helper('lecsmg')->__('3dcart')),
        );
    }
}