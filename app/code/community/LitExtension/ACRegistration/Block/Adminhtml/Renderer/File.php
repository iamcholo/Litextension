<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ACRegistration_Block_Adminhtml_Renderer_File extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if (!$currentData = $row->getData($this->getColumn()->getIndex())) {
            return 'No Image File';
        }
        $downloadUrl = Mage::helper('acregistration')->getAttributeFileUrl($currentData, true);

        return '<a href="'. $downloadUrl .'" target="_blank">
            <img src="'. $downloadUrl .'" style="width: 70px;"></a>';
    }
}