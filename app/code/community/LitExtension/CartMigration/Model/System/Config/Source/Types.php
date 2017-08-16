<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_System_Config_Source_Types {

    public function toOptionArray() {
        return array(
            array('value' => 'marketpress', 'label' => Mage::helper('lecamg')->__('MarketPress')),
            array('value' => 'oxideshop', 'label' => Mage::helper('lecamg')->__('Oxid-eShop')),
            array('value' => 'oscommerce', 'label' => Mage::helper('lecamg')->__('osCommerce')),
            array('value' => 'zencart', 'label' => Mage::helper('lecamg')->__('ZenCart')),
            array('value' => 'virtuemart', 'label' => Mage::helper('lecamg')->__('VirtueMart')),
            array('value' => 'woocommerce', 'label' => Mage::helper('lecamg')->__('WooCommerce')),
            array('value' => 'xtcommerce', 'label' => Mage::helper('lecamg')->__('xt:Commerce/Veyton')),
            array('value' => 'opencart', 'label' => Mage::helper('lecamg')->__('OpenCart')),
            array('value' => 'xcart', 'label' => Mage::helper('lecamg')->__('X-Cart')),
            array('value' => 'prestashop', 'label' => Mage::helper('lecamg')->__('PrestaShop')),
            array('value' => 'wpecommerce', 'label' => Mage::helper('lecamg')->__('WP eCommerce')),
            array('value' => 'loaded', 'label' => Mage::helper('lecamg')->__('CreLoaded/Loaded 7')),
            array('value' => 'cscart', 'label' => Mage::helper('lecamg')->__('Cs Cart')),
            array('value' => 'magento', 'label' => Mage::helper('lecamg')->__('Magento')),
            array('value' => 'interspire', 'label' => Mage::helper('lecamg')->__('Interspire')),
            array('value' => 'cubecart', 'label' => Mage::helper('lecamg')->__('CubeCart')),
            array('value' => 'ubercart', 'label' => Mage::helper('lecamg')->__('UberCart')),
            array('value' => 'drupalcommerce', 'label' => Mage::helper('lecamg')->__('Drupal Commerce')),
            array('value' => 'tomatocart', 'label' => Mage::helper('lecamg')->__('Tomato Cart')),
            array('value' => 'pinnaclecart', 'label' => Mage::helper('lecamg')->__('Pinnacle Cart')),
        );
    }
}