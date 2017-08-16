<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Block_Adminhtml_Index
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Convert result of function toOptionArray to option of element select
     */
    protected function _convertOptions($options, $value){
        $html = '';
        if($options){
            foreach($options as $option){
                $html .='<option value="'.$option['value'].'"';
                if($option['value'] == $value){
                    $html .= 'selected="selected"';
                }
                $html .= '>'.$option['label'].'</option>';
            }
        }
        return $html;
    }

    /**
     * Get select cart type
     */
    protected function getCartsOption($value){
        $carts = Mage::getModel('lecmui/system_config_source_type')->toOptionArray();
        return $this->_convertOptions($carts, $value);
    }

    /**
     * Get name cart type show in select
     */
    protected function getCartTypeByValue($value){
        $carts = Mage::getModel('lecmui/system_config_source_type')->toOptionArray();
        foreach($carts as $cart){
            if($cart['value'] == $value){
                return $cart['label'];
            }
        }
        return "No Cart";
    }
}