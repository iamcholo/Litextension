<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Itemslider extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct(){
		$this->_controller 		= 'adminhtml_itemslider';
		$this->_blockGroup 		= 'itemslider';
		$this->_headerText 		= Mage::helper('itemslider')->__('Manage Slider Tabs');
		$this->_addButtonLabel 	= Mage::helper('itemslider')->__('Add Slider Tab');
		parent::__construct();
	}
}