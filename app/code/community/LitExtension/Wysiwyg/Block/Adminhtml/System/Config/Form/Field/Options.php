<?php

class LitExtension_Wysiwyg_Block_Adminhtml_System_Config_Form_Field_Options extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_addRowButtonHtml = array();
    protected $_removeRowButtonHtml = array();


    protected function _prepareToRender()
    {
        $this->addColumn('title', array(
            'label'    => Mage::helper('adminhtml')->__('Format Title'),
            'style'    => 'width: 180px',
            'class' =>  'required-entry'
        ));
        $this->addColumn('elementtype', array(
            'label'    => Mage::helper('adminhtml')->__('Element Type'),
            'style'    => 'width: 120px',
            'class' =>  'required-entry'
        ));
        $this->addColumn('elementvalue', array(
            'label'    => Mage::helper('adminhtml')->__('Elements'),
            'style'    => 'width: 120px',
            'class' =>  'required-entry'
        ));
        $this->addColumn('class', array(
            'label'    => Mage::helper('adminhtml')->__('Class'),
            'style'    => 'width: 120px'
        ));
        $this->addColumn('style', array(
            'label'    => Mage::helper('adminhtml')->__('Style'),
            'style'    => 'width: 180px'
        ));
        $this->_addAfter = false;
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = parent::_getElementHtml($element);
        return '<div id="' . $element->getHtmlId() . '">' . $html . '</div>';
    }
}
