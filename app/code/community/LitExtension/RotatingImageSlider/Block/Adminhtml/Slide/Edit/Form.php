<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Block_Adminhtml_Slide_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        
        $rotatingimagesliderId = (int) $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
                )
        );
        $form->setUseContainer(true);
        $form->setHtmlIdPrefix('rotatingimageslider_');
        $form->setFieldNameSuffix('rotatingimageslider');
        $this->setForm($form);
        $fieldset = $form->addFieldset('rotatingimageslider_form', array('legend' => Mage::helper('rotatingimageslider')->__('Rotatingimageslider')));
        $fieldset->addType('image_le', Mage::getConfig()->getHelperClassName('rotatingimageslider/form_image'));
        $group_option = Mage::getModel('rotatingimageslider/system_config_source_group')->toOptionArray();
        
        $fieldset->addField('name', 'text', array(
            'label' => Mage::helper('rotatingimageslider')->__('Name '),
            'name' => 'name',
            'required' => true,
            'class' => 'required-entry',
        ));

        if ($rotatingimagesliderId != null) {
            $fieldset->addField('image', 'image_le', array(
                'label' => Mage::helper('rotatingimageslider')->__('Image'),
                'name' => 'image',
                'after_element_html' => '<p class="note">' . Mage::helper('rotatingimageslider')->__('Extension of file as jpg, jpeg , png ') . '</p>',
            ));
        } else {
            $fieldset->addField('image', 'image_le', array(
                'label' => Mage::helper('rotatingimageslider')->__('Image'),
                'name' => 'image',
                'required' => true,
                'class' => 'required-entry',
                'after_element_html' => '<p class="note">' . Mage::helper('rotatingimageslider')->__('Extension of file as jpg, jpeg , png ') . '</p>',
            ));
        }

        $fieldset->addField('link', 'text', array(
            'label' => Mage::helper('rotatingimageslider')->__('Link'),
            'name' => 'link',
            'after_element_html' => '<p class="note">' . Mage::helper('rotatingimageslider')->__('Example: http://example.com ') . '</p>'
        ));
        
        
        $field = $fieldset->addField('store_id', 'multiselect', array(
            'name' => 'stores[]',
            'label' => Mage::helper('rotatingimageslider')->__('Store Views'),
            'title' => Mage::helper('rotatingimageslider')->__('Store Views'),
            'required' => true,
            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
        ));
        
        $fieldset->addField('group_id', 'select', array(
            'label' => Mage::helper('rotatingimageslider')->__('Group'),
            'name' => 'group_id',
            'values' => $group_option,
        ));
        
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('rotatingimageslider')->__('Status'),
            'name' => 'status',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('rotatingimageslider')->__('Enabled'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('rotatingimageslider')->__('Disabled'),
                ),
            ),
        ));
        
        $fieldset->addField('image_tmp', 'text', array(
            'name' => 'image_tmp',
            'style' => 'display : none;',
            'readonly' => true,
        ));
        
        if (Mage::getSingleton('adminhtml/session')->getRotatingimagesliderData()) {
            $data = Mage::getSingleton('adminhtml/session')->getRotatingimagesliderData();
            $data['image_tmp'] = $data['image'];
            $form->setValues($data);
            Mage::getSingleton('adminhtml/session')->setRotatingimagesliderData(null);
        } elseif (Mage::registry('current_rotatingimageslider')) {
            $data = Mage::registry('current_rotatingimageslider')->getData();
            $data['image_tmp'] = $data['image'];
            $form->setValues($data);
        }
        return parent::_prepareForm();
    }

}