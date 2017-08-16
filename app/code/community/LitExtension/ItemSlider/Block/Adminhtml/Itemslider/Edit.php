<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Itemslider_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{

	public function __construct(){
		parent::__construct();
        $this->_objectId = 'id';
		$this->_blockGroup = 'itemslider';
		$this->_controller = 'adminhtml_itemslider';
		$this->_updateButton('save', 'label', Mage::helper('itemslider')->__('Save Slider Tab'));
		$this->_updateButton('delete', 'label', Mage::helper('itemslider')->__('Delete Slider Tab'));
		$this->_addButton('saveandcontinue', array(
			'label'		=> Mage::helper('itemslider')->__('Save And Continue Edit'),
			'onclick'	=> 'saveAndContinueEdit()',
			'class'		=> 'save',
		), -100);
		$this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
	}

	public function getHeaderText(){
		if( Mage::registry('itemslider_data') && Mage::registry('itemslider_data')->getId() ) {
			return Mage::helper('itemslider')->__("Edit Slider Tab '%s'", $this->htmlEscape(Mage::registry('itemslider_data')->getGroupName()));
		} 
		else {
			return Mage::helper('itemslider')->__('Add Slider Tab');
		}
	}
}