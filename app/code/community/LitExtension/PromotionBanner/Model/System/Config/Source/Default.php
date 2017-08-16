<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_System_Config_Source_Default {

    public function toOptionArray() {
        $data = array();
        $data['border_width'] = 0;
        $data['border_color'] = "FFFFFF";
        $data['shadow_color'] = "000000";
        $data['autohide'] = 0;
        return $data;
    }

}

