<?php
/**
 * @project     Wysiwyg
 * @package     LitExtension_Wysiwyg
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_Wysiwyg_Model_System_Config_Source_Layout {

    public function toOptionArray() {
        return array(
            array('value' => 'simple', 'label' => Mage::helper('adminhtml')->__('Simple')),
            array('value' => 'full', 'label' => Mage::helper('adminhtml')->__('Full')),
        );
    }

}
