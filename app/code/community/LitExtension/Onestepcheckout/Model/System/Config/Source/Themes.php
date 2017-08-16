<?php

class LitExtension_Onestepcheckout_Model_System_Config_Source_Themes{

    public function toOptionArray()
    {
        return array(
            array('value' => 'light', 'label'=>Mage::helper('adminhtml')->__('Light')),
            array('value' => 'flat', 'label'=>Mage::helper('adminhtml')->__('Flat')),
        );

    }
}
