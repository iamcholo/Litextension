<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Position {

    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('promotionbanner')->__('Top-Left')),
            array('value' => '2', 'label' => Mage::helper('promotionbanner')->__('Top-Center')),
            array('value' => '3', 'label' => Mage::helper('promotionbanner')->__('Top-Right')),
            array('value' => '4', 'label' => Mage::helper('promotionbanner')->__('Middle-Left')),
            array('value' => '5', 'label' => Mage::helper('promotionbanner')->__('Center')),
            array('value' => '6', 'label' => Mage::helper('promotionbanner')->__('Middle-Right')),
            array('value' => '7', 'label' => Mage::helper('promotionbanner')->__('Bottom-Left')),
            array('value' => '8', 'label' => Mage::helper('promotionbanner')->__('Bottom-Center')),
            array('value' => '9', 'label' => Mage::helper('promotionbanner')->__('Bottom-Right')),
        );
    }

}
