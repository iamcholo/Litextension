<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Group_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('leimageslider_');
        $form->setFieldNameSuffix('leimageslider');
        $this->setForm($form);
        $fieldset = $form->addFieldset('leimageslider_form', array('legend' => Mage::helper('leimageslider')->__('General Information')));
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig();

        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Title'),
            'name' => 'title',
            'required' => true,
            'class' => 'required-entry',
        ));
        $field = $fieldset->addField('store_id', 'multiselect', array(
            'name' => 'stores[]',
            'label' => Mage::helper('leimageslider')->__('Store Views'),
            'title' => Mage::helper('leimageslider')->__('Store Views'),
            'required' => true,
            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
        ));

        $fieldset->addField('description', 'editor', array(
            'label' => Mage::helper('leimageslider')->__('Description'),
            'name' => 'description',
            'config' => $wysiwygConfig,
            'wysiwyg' => false,
            'style' => 'width:420px; height:200px;',
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
                        
        if (Mage::getSingleton('adminhtml/session')->getLeimagesliderData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getLeimagesliderData());
            Mage::getSingleton('adminhtml/session')->setLeimagesliderData(null);
        } elseif (Mage::registry('current_leimageslider')) {
            $form->setValues(Mage::registry('current_leimageslider')->getData());
        }
        return parent::_prepareForm();
    }

}