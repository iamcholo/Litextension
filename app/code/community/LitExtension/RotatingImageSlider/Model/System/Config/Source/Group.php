<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Model_System_Config_Source_Group {
    
    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('rotatingimageslider')->__('Group 1')),
            array('value' => '2', 'label' => Mage::helper('rotatingimageslider')->__('Group 2')),
            array('value' => '3', 'label' => Mage::helper('rotatingimageslider')->__('Group 3')),
            array('value' => '4', 'label' => Mage::helper('rotatingimageslider')->__('Group 4')),
        );
    }
}


