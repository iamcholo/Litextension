<?php

/**
 * @project     PromotionBanner
 * @package	    LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Heighttype {

    public function toOptionArray() {
        return array(
            array('value' => '0', 'label' => Mage::helper('promotionbanner')->__('Fixed Height')),
            array('value' => '1', 'label' => Mage::helper('promotionbanner')->__('Min Height')),
        );
    }

}
