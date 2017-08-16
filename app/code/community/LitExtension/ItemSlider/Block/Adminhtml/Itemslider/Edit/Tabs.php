<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Itemslider_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs{

	public function __construct(){
		parent::__construct();
		$this->setId('itemslider_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('itemslider')->__('Slider Tab Infomation'));
	}

	protected function _beforeToHtml(){
		$this->addTab('form_itemslider', array(
			'label'		=> Mage::helper('itemslider')->__('General Infomation'),
			'title'		=> Mage::helper('itemslider')->__('General Infomation'),
			'content' 	=> $this->getLayout()->createBlock('itemslider/adminhtml_itemslider_edit_tab_form')->toHtml(),
		));

		return parent::_beforeToHtml();
	}
}