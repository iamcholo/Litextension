<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Model_System_Config_Source_Position {
    
    public function toOptionArray() {
        return array(
            array('value' => 'left', 'label' => Mage::helper('rotatingimageslider')->__('Left')),
            array('value' => 'right', 'label' => Mage::helper('rotatingimageslider')->__('Right')),
        );
    }
}


