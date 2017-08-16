<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Group_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        $this->setId('leimageslider_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('leimageslider')->__('Group Information'));
    }

    protected function _beforeToHtml() {

        $this->addTab('form_leimageslider', array(
            'label' => Mage::helper('leimageslider')->__('General Information'),
            'title' => Mage::helper('leimageslider')->__('General Information'),
            'content' => $this->getLayout()->createBlock('leimageslider/adminhtml_group_edit_tab_form')->toHtml(),
        ));
        
        $this->addTab('form_general_leimageslider', array(
            'label' => Mage::helper('leimageslider')->__('Style Setting'),
            'title' => Mage::helper('leimageslider')->__('Style Setting'),
            'content' => $this->getLayout()->createBlock('leimageslider/adminhtml_group_edit_tab_general')->toHtml(),
        ));
        $show_slide = false;
        if($this->getRequest()->getParam('slide')){
            $show_slide = true;
        }
        if($this->getRequest()->getParam('id')){
            $content = $this->getLayout()->createBlock('leimageslider/adminhtml_slide_grid')->toHtml();
        } else {
            $content = $this->__('Please save group first.');
        }

        $this->addTab('form_slide_leimageslider', array(
            'label' => Mage::helper('leimageslider')->__('Images'),
            'title' => Mage::helper('leimageslider')->__('Images'),
            'content' => $content,
            'active' => $show_slide,
        ));
        
        return parent::_beforeToHtml();
    }

}