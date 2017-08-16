<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ACRegistration_Block_Adminhtml_Renderer_Multiselect extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $columnData = '';
        $value = $row->getData($this->getColumn()->getIndex());
        if ($value)
        {
            $value = explode(',', $value);
        }
        foreach ($this->getColumn()->getOptions() as $val => $label)
        {
            if (is_array($value) && in_array($val, $value))
            {
                $columnData .= $label . ', ';
            }
        }
        if ($columnData)
        {
            $columnData = substr($columnData, 0, -2);
        }
        return $columnData;
    }
}