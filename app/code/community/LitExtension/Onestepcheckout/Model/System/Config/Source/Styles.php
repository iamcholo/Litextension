<?php

class LitExtension_Onestepcheckout_Model_System_Config_Source_Styles{

    public function toOptionArray()
    {
        return array(
            array('value' => 'orange', 'label'=>Mage::helper('adminhtml')->__('Orange')),
            array('value' => 'green', 'label'=>Mage::helper('adminhtml')->__('Green')),
        );

    }
}