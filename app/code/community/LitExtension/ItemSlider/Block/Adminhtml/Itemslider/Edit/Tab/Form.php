<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Itemslider_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form{	

	protected function _prepareForm(){
		$form = new Varien_Data_Form();
		$form->setHtmlIdPrefix('itemslider_');
		$form->setFieldNameSuffix('itemslider');
		$this->setForm($form);
		$fieldset = $form->addFieldset('itemslider_form', array('legend'=>Mage::helper('itemslider')->__('General Infomation')));
        $slides_options = Mage::getModel('itemslider/system_config_source_slides')->toOptionArray();

		$fieldset->addField('group_name', 'text', array(
			'label' => Mage::helper('itemslider')->__('Slider Tab Name'),
			'name'  => 'group_name',
			'required'  => true,
			'class' => 'required-entry',

		));

        $fieldset->addField('slide_id', 'select', array(
            'label' => Mage::helper('itemslider')->__('Slider'),
            'name' => 'slide_id',
            'values' => $slides_options,
            'required' => true,
        ));

		$fieldset->addField('item_type', 'select', array(
			'label' => Mage::helper('itemslider')->__('Item Type'),
			'name'  => 'item_type',

			'values'=> array(
				array(
					'value' => 1,
					'label' => Mage::helper('itemslider')->__('Categories'),
				),
				array(
					'value' => 0,
					'label' => Mage::helper('itemslider')->__('Products'),
				),
			),
		));

		$fieldset->addField('item_ids', 'text', array(
			'label' => Mage::helper('itemslider')->__('Item Ids'),
			'name'  => 'item_ids',
			'required'  => true,
			'class' => 'required-entry',
            'after_element_html' => '<br/><small>Product or Category Ids, commas separated, example: 101,102,105</small>',
		));

		$fieldset->addField('enable_link', 'select', array(
			'label' => Mage::helper('itemslider')->__('Enable Link'),
			'name'  => 'enable_link',

			'values'=> array(
				array(
					'value' => 1,
					'label' => Mage::helper('itemslider')->__('Yes'),
				),
				array(
					'value' => 0,
					'label' => Mage::helper('itemslider')->__('No'),
				),
			),
		));
        $fieldset->addField('tabs_order', 'text', array(
            'label' => Mage::helper('itemslider')->__('Order'),
            'name'  => 'tabs_order',
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