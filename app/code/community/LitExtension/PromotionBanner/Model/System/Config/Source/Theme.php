<?php

/**
 * @project     PromotionBanner
 * @package	    LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Theme {

    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('promotionbanner')->__('Icon')),
            array('value' => '2', 'label' => Mage::helper('promotionbanner')->__('Text')),
        );
    }

}
