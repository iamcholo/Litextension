<?php

class LitExtension_Onestepcheckout_Model_System_Config_Source_Login{

    public function toOptionArray()
    {
        return array(
            array('value' => 'no', 'label'=>Mage::helper('adminhtml')->__('No')),
            array('value' => 'top', 'label'=>Mage::helper('adminhtml')->__('Top')),
            array('value' => 'bottom', 'label'=>Mage::helper('adminhtml')->__('Bottom')),
            array('value' => 'inbox', 'label'=>Mage::helper('adminhtml')->__('In Box')),
        );

    }
}
