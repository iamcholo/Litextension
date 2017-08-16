<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getRotatingimageslidersUrl() {
        return Mage::getUrl('rotatingimageslider/rotatingimageslider/index');
    }

}