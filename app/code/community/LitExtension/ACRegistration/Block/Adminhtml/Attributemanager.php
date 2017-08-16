<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_ACRegistration_Block_Adminhtml_Attributemanager  extends Mage_Adminhtml_Block_Template
{
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('le_acregistration/index.phtml');
        $this->getAttributemanager();
    }

    public function getAttributemanager()
    {
        if (!$this->hasData('attributemanager/index')) {
            $this->setData('attributemanager/index', Mage::registry('attributemanager/index'));
        }

        return $this->getData('attributemanager/index');
    }
}
