<?php

/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_System_Config_Source_Linktype {

    public function toOptionArray() {
        return array(
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Same page')),
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('New page')),
        );
    }

}
