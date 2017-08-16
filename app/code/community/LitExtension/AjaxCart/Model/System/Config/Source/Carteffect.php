<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxCart_Model_System_Config_Source_CartEffect {

    public function toOptionArray() {
        return array(
            array('value' => 'none', 'label' => Mage::helper('leajct')->__('None')),
            array('value' => 'slideDown', 'label' => Mage::helper('leajct')->__('Slide')),
            array('value' => 'fadeIn', 'label' => Mage::helper('leajct')->__('Opacity')),
            array('value' => 'blink', 'label' => Mage::helper('leajct')->__('Blink')),
        );
    }

}
