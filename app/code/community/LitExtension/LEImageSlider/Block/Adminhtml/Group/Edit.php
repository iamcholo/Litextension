<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Group_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();
        $id = $this->getRequest()->getParam('id');
        $this->_blockGroup = 'leimageslider';
        $this->_controller = 'adminhtml_group';
        $this->_updateButton('save', 'label', Mage::helper('leimageslider')->__('Save Group'));
        $this->_updateButton('delete', 'label', Mage::helper('leimageslider')->__('Delete Group'));
        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('leimageslider')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
                ), -100);
        if(isset($id) && $id != null){
            $this->_addButton('newimage', array(
                'label'     => Mage::helper('leimageslider')->__('Add Image'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('*/leimageslider_slide/new', array('group' => $id)) . '\')',
                'class'     => 'add',
            ), -150);
        }
        $this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
    }

    public function getHeaderText() {
        if (Mage::registry('leimageslider_data') && Mage::registry('leimageslider_data')->getId()) {
            return Mage::helper('leimageslider')->__("Edit Group '%s'", $this->htmlEscape(Mage::registry('leimageslider_data')->getTitle()));
        } else {
            return Mage::helper('leimageslider')->__('Add Group');
        }
    }

}