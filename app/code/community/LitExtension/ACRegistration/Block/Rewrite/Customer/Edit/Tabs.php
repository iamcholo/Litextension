<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_ACRegistration_Block_Rewrite_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Customer_Edit_Tabs
{
    private $parent;

    protected function _prepareLayout()
    {
        //get all existing tabs
        $this->parent = parent::_prepareLayout();
        //add new tab
        $this->addTab('customattributes', array(
            'label' => Mage::helper('catalog')->__('Custom Attributes'),
            'title' => Mage::helper('catalog')->__('Custom Attributes'),
            'content'   => $this->getLayout()->
                createBlock('acregistration/rewrite_customer_edit_tab_account')
                ->initCustomForm()
                ->toHtml(),
            'active'    => Mage::registry('current_customer')->getId() ? false : true
        ));
        return $this->parent;
    }
}