<?php

class LitExtension_Onestepcheckout_Model_Adminhtml_System_Config_Source_Fieldtype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'checkbox', 'label' => Mage::helper('adminhtml')->__('Checkbox')),
            array('value' => 'text', 'label' => Mage::helper('adminhtml')->__('Text')),
            array('value' => 'textarea', 'label' => Mage::helper('onestepcheckout')->__('Textarea')),
            array('value' => 'select', 'label' => Mage::helper('adminhtml')->__('Select'))
        );
    }
}
