<?php
/**
 * @project     AjaxLogin
 * @package     LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxLogin_Block_Adminhtml_Renderer_File extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if (!$currentData = $row->getData($this->getColumn()->getIndex())) {
            return 'No Image File';
        }
        $downloadUrl = Mage::helper('ajaxlogin')->getAttributeFileUrl($currentData, true);

        return '<a href="'. $downloadUrl .'" target="_blank">
            <img src="'. $downloadUrl .'" style="width: 70px;"></a>';
    }
}