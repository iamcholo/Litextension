<?php

/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_System_Config_Source_Itemperpage {

    public function toOptionArray() {
        return array(
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('1')),
            array('value' => 2, 'label' => Mage::helper('adminhtml')->__('2')),
            array('value' => 3, 'label' => Mage::helper('adminhtml')->__('3')),
            array('value' => 4, 'label' => Mage::helper('adminhtml')->__('4')),
            array('value' => 5, 'label' => Mage::helper('adminhtml')->__('5')),
            array('value' => 6, 'label' => Mage::helper('adminhtml')->__('6')),
        );
    }

}
