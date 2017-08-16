<?php
/**
 * @project     AjaxCart
 * @package     LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxCart_Model_System_Config_Source_Theme
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'simple', 'label' => Mage::helper('leajct')->__('Simple')),
            array('value' => 'plastic', 'label' => Mage::helper('leajct')->__('Plastic')),
        );
    }
}