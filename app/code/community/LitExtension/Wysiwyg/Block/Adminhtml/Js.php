<?php
class LitExtension_Wysiwyg_Block_Adminhtml_Js extends Mage_Adminhtml_Block_Template
{

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('le_wysiwyg/js.phtml');
    }
}