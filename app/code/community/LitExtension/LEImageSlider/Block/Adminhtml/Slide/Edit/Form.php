<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Slide_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {

        $leimagesliderId = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $form->setUseContainer(true);
        $form->setHtmlIdPrefix('leimageslider_');
        $form->setFieldNameSuffix('leimageslider');
        $this->setForm($form);
        $fieldset = $form->addFieldset('leimageslider_form', array('legend' => Mage::helper('leimageslider')->__('General Information')));
        $fieldset->addType('image_le', Mage::getConfig()->getHelperClassName('leimageslider/form_image'));
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig();
        $group_options = Mage::getModel('leimageslider/system_config_source_group')->toOptionArray();

        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Title'),
            'name' => 'title',
            'required' => true,
            'class' => 'required-entry',
        ));
        if ($leimagesliderId != null) {
            $fieldset->addField('image', 'image_le', array(
                'label' => Mage::helper('leimageslider')->__('Image'),
                'name' => 'image',
                'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Extension of file as jpg, jpeg , png ') . '</p>',
            ));
        } else {
            $fieldset->addField('image', 'image_le', array(
                'label' => Mage::helper('leimageslider')->__('Image'),
                'name' => 'image',
                'required' => true,
                'class' => 'required-entry',
                'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Extension of file as jpg, jpeg , png ') . '</p>',
            ));
        }
        
        $fieldset->addField('link', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Link'),
            'name' => 'link',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Example: http://example.com ') . '</p>'
        ));

        $fieldset->addField('target', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Open Link in New Window'),
            'name' => 'target',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('leimageslider')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('leimageslider')->__('No'),
                ),
            ),
        ));

        $fieldset->addField('content', 'editor', array(
            'label' => Mage::helper('leimageslider')->__('Description'),
            'name' => 'content',
            'config' => $wysiwygConfig,
            'wysiwyg' => false,
            'style' => 'width:420px; height:200px;',
        ));
        $fieldset->addField('group_id', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Group'),
            'name' => 'group_id',
            'values' => $group_options,
            'required' => true,
            'disabled' => true,
        ));

        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Status'),
            'name' => 'status',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('leimageslider')->__('Enabled'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('leimageslider')->__('Disabled'),
                ),
            ),
        ));

        $fieldset->addField('image_tmp', 'text', array(
            'name' => 'image_tmp',
            'style' => 'display : none;',
            'readonly' => true,
        ));
        if (Mage::getSingleton('adminhtml/session')->getLeimagesliderData()) {
            $data = Mage::getSingleton('adminhtml/session')->getLeimagesliderData();
            if($this->getRequest()->getParam('group')){
                $data['group_id'] = $this->getRequest()->getParam('group');
            }
            $data['image_tmp'] = $data['image'];
            $form->setValues($data);
            Mage::getSingleton('adminhtml/session')->setLeimagesliderData(null);
        } elseif (Mage::registry('current_leimageslider')) {
            $data = Mage::registry('current_leimageslider')->getData();
            if($this->getRequest()->getParam('group')){
                $data['group_id'] = $this->getRequest()->getParam('group');
            }
            $data['image_tmp'] = $data['image'];
            $form->setValues($data);
        }
        return parent::_prepareForm();
    }

}