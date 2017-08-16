<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Slide_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();
        $this->_blockGroup = 'leimageslider';
        $this->_controller = 'adminhtml_slide';
        $this->_removeButton('save');
        $this->_removeButton('back');
        $this->_addButton('backgroup', array(
            'label'     => Mage::helper('leimageslider')->__('Back To Group'),
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/leimageslider_group/edit', array('id' => $this->getRequest()->getParam('group'), 'slide' => true)) . '\')',
            'class'     => 'back',
        ), -1, 1);
        $this->_updateButton('delete', 'label', Mage::helper('leimageslider')->__('Delete Image'));
        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('leimageslider')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
                ), -100);
        if($this->getRequest()->getParam('group')){
            $this->_addButton('saveandnext', array(
                'label' => Mage::helper('leimageslider')->__('Save And Back To Group'),
                'onclick' => 'saveAndBackGroup()',
                'class' => 'save',
            ), -100);
        }
        $this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/group/".$this->getRequest()->getParam('group')."');
			}
			function saveAndBackGroup(){
				editForm.submit($('edit_form').action+'next/edit/group/".$this->getRequest()->getParam('group')."/');
			}
		";
    }

    public function getHeaderText() {
        if (Mage::registry('leimageslider_data') && Mage::registry('leimageslider_data')->getId()) {
            return Mage::helper('leimageslider')->__("Edit image '%s'", $this->htmlEscape(Mage::registry('leimageslider_data')->getTitle()));
        } else {
            return Mage::helper('leimageslider')->__('Add Image');
        }
    }

}