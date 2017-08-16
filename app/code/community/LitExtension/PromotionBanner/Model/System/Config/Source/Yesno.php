<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Yesno {

    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('promotionbanner')->__('Yes')),
            array('value' => '0', 'label' => Mage::helper('promotionbanner')->__('No')),
        );
    }

}
