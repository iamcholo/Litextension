<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Block_Adminhtml_Slide_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();
        $this->_blockGroup = 'rotatingimageslider';
        $this->_controller = 'adminhtml_slide';
        $this->_updateButton('save', 'label', Mage::helper('rotatingimageslider')->__('Save slide'));
        $this->_updateButton('delete', 'label', Mage::helper('rotatingimageslider')->__('Delete slide'));
        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('rotatingimageslider')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
                ), -100);
        $this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
    }

    public function getHeaderText() {
        if (Mage::registry('rotatingimageslider_data') && Mage::registry('rotatingimageslider_data')->getId()) {
            return Mage::helper('rotatingimageslider')->__("Edit slide '%s'", $this->htmlEscape(Mage::registry('rotatingimageslider_data')->getName()));
        } else {
            return Mage::helper('rotatingimageslider')->__('Add slide');
        }
    }

}