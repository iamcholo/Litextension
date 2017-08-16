<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxCart_Model_System_Config_Source_DropEffect {

    public function toOptionArray() {
        return array(
            array('value' => 'explode', 'label' => Mage::helper('leajct')->__('Explode')),
            array('value' => 'puff', 'label' => Mage::helper('leajct')->__('Puff')),
            array('value' => 'shrink', 'label' => Mage::helper('leajct')->__('Shrink')),
        );
    }

}
