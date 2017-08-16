<?php

class LitExtension_Onestepcheckout_Model_System_Config_Source_Layout{

    public function toOptionArray()
    {
         return array(
            array('value' => '2col', 'label'=>Mage::helper('adminhtml')->__('2 Columns')),
            array('value' => '3col', 'label'=>Mage::helper('adminhtml')->__('3 Columns')),
        );

    }
}
