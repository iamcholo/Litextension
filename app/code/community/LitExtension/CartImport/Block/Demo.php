<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Block_Demo
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset{

    /**
     * Show notice if is demo mode
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '';
        if(LitExtension_CartImport_Model_Custom::DEMO_MODE){
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS).'litextension/cartimport/demo.js';
            $html .= '<script type="text/javascript" src="' . $url . '"></script>';
            $html .= '<p style="font-size: 14px;border: 1px solid #000000; padding: 10px; background: #F1F1F1;">In demo mode:<br /> - Entity (csv, taxes, manufacturers, categories, products, customers, orders, reviews) per batch limit 1 - 10. </p><br />';
        }
        return $html;
    }
}