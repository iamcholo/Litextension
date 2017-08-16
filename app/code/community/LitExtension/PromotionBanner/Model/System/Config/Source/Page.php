<?php
/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Page {

    public function toOptionArray() {

        return array(
            array('value' => '1', 'label' => Mage::helper('promotionbanner')->__('Home Page')),
            array('value' => '2', 'label' => Mage::helper('promotionbanner')->__('Product View')),
            array('value' => '3', 'label' => Mage::helper('promotionbanner')->__('Catalog View')),
            array('value' => '4', 'label' => Mage::helper('promotionbanner')->__('CMS Page')),
            array('value' => '5', 'label' => Mage::helper('promotionbanner')->__('Customer Area')),
            array('value' => '6', 'label' => Mage::helper('promotionbanner')->__('Checkout')),
            array('value' => '7', 'label' => Mage::helper('promotionbanner')->__('Cart')),
        );
    }

}
