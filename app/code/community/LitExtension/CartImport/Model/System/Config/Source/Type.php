<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_System_Config_Source_Type{

    public function toOptionArray() {
        return array(
            array('value' => 'volusion', 'label' => Mage::helper('lecaip')->__('Volusion')),            
            array('value' => 'mivamerchant', 'label' => Mage::helper('lecaip')->__('Miva Merchant')),
            array('value' => 'amazonstore', 'label' => Mage::helper('lecaip')->__('Amazon Store')),
        	array('value' => 'yahoostore', 'label' => Mage::helper('lecaip')->__('Yahoo Store/Aabaco')),
        	array('value' => 'nopcommerce', 'label' => Mage::helper('lecaip')->__('nopCommerce')),
            array('value' => 'squarespace', 'label' => Mage::helper('lecaip')->__('Squarespace')),
            array('value' => 'weebly', 'label' => Mage::helper('lecaip')->__('Weebly')),
        );
    }
}