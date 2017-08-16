<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Easing {

    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('promotionbanner')->__('easeInQuad')),
            array('value' => '2', 'label' => Mage::helper('promotionbanner')->__('easeOutQuad')),
            array('value' => '3', 'label' => Mage::helper('promotionbanner')->__('easeInOutQuad')),
            array('value' => '4', 'label' => Mage::helper('promotionbanner')->__('easeInCubic')),
            array('value' => '5', 'label' => Mage::helper('promotionbanner')->__('easeOutCubic')),
            array('value' => '6', 'label' => Mage::helper('promotionbanner')->__('easeInOutCubic')),
            array('value' => '7', 'label' => Mage::helper('promotionbanner')->__('easeInQuart')),
            array('value' => '8', 'label' => Mage::helper('promotionbanner')->__('easeOutQuart')),
            array('value' => '9', 'label' => Mage::helper('promotionbanner')->__('easeInOutQuart')),
            array('value' => '10', 'label' => Mage::helper('promotionbanner')->__('easeInQuint')),
            array('value' => '11', 'label' => Mage::helper('promotionbanner')->__('easeOutQuint')),
            array('value' => '12', 'label' => Mage::helper('promotionbanner')->__('easeInOutQuint')),
            array('value' => '13', 'label' => Mage::helper('promotionbanner')->__('easeInSine')),
            array('value' => '14', 'label' => Mage::helper('promotionbanner')->__('easeOutSine')),
            array('value' => '15', 'label' => Mage::helper('promotionbanner')->__('easeInOutSine')),
            array('value' => '16', 'label' => Mage::helper('promotionbanner')->__('easeInExpo')),
            array('value' => '17', 'label' => Mage::helper('promotionbanner')->__('easeOutExpo')),
            array('value' => '18', 'label' => Mage::helper('promotionbanner')->__('easeInOutExpo')),
            array('value' => '19', 'label' => Mage::helper('promotionbanner')->__('easeInCirc')),
            array('value' => '20', 'label' => Mage::helper('promotionbanner')->__('easeOutCirc')),
            array('value' => '21', 'label' => Mage::helper('promotionbanner')->__('easeInOutCirc')),
            array('value' => '22', 'label' => Mage::helper('promotionbanner')->__('easeInElastic')),
            array('value' => '23', 'label' => Mage::helper('promotionbanner')->__('easeOutElastic')),
            array('value' => '24', 'label' => Mage::helper('promotionbanner')->__('easeInOutElastic')),
            array('value' => '25', 'label' => Mage::helper('promotionbanner')->__('easeInBack')),
            array('value' => '26', 'label' => Mage::helper('promotionbanner')->__('easeOutBack')),
            array('value' => '27', 'label' => Mage::helper('promotionbanner')->__('easeInOutBack')),
            array('value' => '28', 'label' => Mage::helper('promotionbanner')->__('easeInBounce')),
            array('value' => '29', 'label' => Mage::helper('promotionbanner')->__('easeOutBounce')),
            array('value' => '30', 'label' => Mage::helper('promotionbanner')->__('easeInOutBounce')),
        );
    }

}
