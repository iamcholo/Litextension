<?php
/**
 * @project     AjaxSearch
 * @package     LitExtension_AjaxSearch
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxSearch_Model_System_Config_Source_Info
{

    public function toOptionArray()
    {
        return array(
            array('value' => 'shortdecs', 'label' => Mage::helper('ajaxsearch')->__('Short Description')),
            array('value' => 'price', 'label' => Mage::helper('ajaxsearch')->__('Price')),
        );
    }

    public function toArray()
    {
        return array(
            'shortdecs' => Mage::helper('adminhtml')->__('Short Description'),
            'price' => Mage::helper('adminhtml')->__('Price'),
        );
    }

}
