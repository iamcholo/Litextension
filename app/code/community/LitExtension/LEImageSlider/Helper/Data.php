<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getLeimageslidersUrl() {
        return Mage::getUrl('leimageslider/leimageslider/index');
    }

}