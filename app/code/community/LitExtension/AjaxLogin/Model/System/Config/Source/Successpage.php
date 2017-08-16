<?php
/**
 * @project     AjaxLogin
 * @package     LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_Model_System_Config_Source_Successpage{

    public function toOptionArray() {
        return array(
            array('value' => 'currentpage', 'label' => Mage::helper('adminhtml')->__('Stay on Current')),
            array('value' => 'accountpage', 'label' => Mage::helper('adminhtml')->__('My Account Page')),
        );
    }

}
