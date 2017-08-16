<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_System_Config_Source_Type {

    public function toOptionArray() {
        return array(
            array('value' => 'oscommerce', 'label' => Mage::helper('lecmui')->__('osCommerce')),
            array('value' => 'zencart', 'label' => Mage::helper('lecmui')->__('ZenCart')),
            array('value' => 'virtuemart', 'label' => Mage::helper('lecmui')->__('VirtueMart')),
            array('value' => 'woocommerce', 'label' => Mage::helper('lecmui')->__('WooCommerce')),
            array('value' => 'xtcommerce', 'label' => Mage::helper('lecmui')->__('xt:Commerce')),
            array('value' => 'opencart', 'label' => Mage::helper('lecmui')->__('OpenCart')),
            array('value' => 'xcart', 'label' => Mage::helper('lecmui')->__('X-Cart')),
            array('value' => 'prestashop', 'label' => Mage::helper('lecmui')->__('PrestaShop')),
            array('value' => 'wpecommerce', 'label' => Mage::helper('lecmui')->__('WP eCommerce')),
            array('value' => 'loaded', 'label' => Mage::helper('lecmui')->__('Loaded Commerce')),
            array('value' => 'cscart', 'label' => Mage::helper('lecmui')->__('Cs Cart')),
            array('value' => 'magento', 'label' => Mage::helper('lecmui')->__('Magento')),
            array('value' => 'interspire', 'label' => Mage::helper('lecmui')->__('Interspire')),
        );
    }
}