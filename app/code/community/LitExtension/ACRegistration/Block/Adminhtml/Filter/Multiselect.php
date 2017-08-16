<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ACRegistration_Block_Adminhtml_Filter_Multiselect extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select
{
    public function getCondition()
    {
        if (is_null($this->getValue())) {
            return null;
        }
        
        return array('or'=> array(
                 array('eq'   => $this->getValue()),
                 array('like' => '%,' . $this->getValue() . ''),
                 array('like' => '' . $this->getValue() . ',%'),
                 array('like' => '%,' . $this->getValue() . ',%')
               ));
    }
}