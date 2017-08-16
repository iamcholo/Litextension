<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Slides_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form{

	protected function _prepareForm(){
		$form = new Varien_Data_Form();
		$form->setHtmlIdPrefix('itemslider_');
		$form->setFieldNameSuffix('itemslider');
		$this->setForm($form);
		$fieldset = $form->addFieldset('itemslider_form', array('legend'=>Mage::helper('itemslider')->__('General Infomation')));

		$fieldset->addField('slide_name', 'text', array(
			'label' => Mage::helper('itemslider')->__('Slider Name'),
			'name'  => 'slide_name',
			'required'  => true,
			'class' => 'required-entry',

		));

		$fieldset->addField('status', 'select', array(
			'label' => Mage::helper('itemslider')->__('Status'),
			'name'  => 'status',
			'values'=> array(
				array(
					'value' => 1,
					'label' => Mage::helper('itemslider')->__('Enabled'),
				),
				array(
					'value' => 0,
					'label' => Mage::helper('itemslider')->__('Disabled'),
				),
			),
		));

		if (Mage::getSingleton('adminhtml/session')->getItemsliderData()){
			$form->setValues(Mage::getSingleton('adminhtml/session')->getItemsliderData());
			Mage::getSingleton('adminhtml/session')->setItemsliderData(null);
		}
		elseif (Mage::registry('current_itemslider')){
			$form->setValues(Mage::registry('current_itemslider')->getData());
		}
		return parent::_prepareForm();
	}
}