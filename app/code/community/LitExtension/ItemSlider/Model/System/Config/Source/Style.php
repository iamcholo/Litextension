<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_System_Config_Source_Style {

    public function toOptionArray() {
        return array(
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('Style 1')),
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Style 2')),
        );
    }

}
