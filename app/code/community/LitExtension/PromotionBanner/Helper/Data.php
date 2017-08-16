<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Helper_Data extends Mage_Core_Helper_Abstract {
   
    public function getBannersUrl() {
        return Mage::getUrl('promotionbanner/banner/index');
    }

}