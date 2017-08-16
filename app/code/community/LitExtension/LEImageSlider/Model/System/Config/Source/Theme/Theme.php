<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_System_Config_Source_Theme_Theme {

    public function toOptionArray() {
        return array(
            array('value' => 'bar', 'label' => Mage::helper('leimageslider')->__('Bar')),
            array('value' => 'dark', 'label' => Mage::helper('leimageslider')->__('Dark')),
            array('value' => 'default', 'label' => Mage::helper('leimageslider')->__('Default')),
            array('value' => 'light', 'label' => Mage::helper('leimageslider')->__('Light')),
        );
    }

}
