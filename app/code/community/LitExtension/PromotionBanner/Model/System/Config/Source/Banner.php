<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Banner {

    public function toOptionArray() {
        $model = Mage::getModel('promotionbanner/banner');
        $collection = $model->getCollection();

        $data = array();
        foreach ($collection as $banner) {
            $data[$banner['promotionbanner_id']] = $banner['title'];
        }

        return $data;
    }

}
