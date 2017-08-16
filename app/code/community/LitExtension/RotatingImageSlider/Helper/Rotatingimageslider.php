<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Helper_Rotatingimageslider extends Mage_Core_Helper_Abstract {

    public function getUseBreadcrumbs() {
        return Mage::getStoreConfigFlag('rotatingimageslider/rotatingimageslider/breadcrumbs');
    }

}