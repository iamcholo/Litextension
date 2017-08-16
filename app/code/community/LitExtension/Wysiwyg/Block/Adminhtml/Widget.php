<?php

class LitExtension_Wysiwyg_Block_Adminhtml_Widget extends Mage_Widget_Block_Adminhtml_Widget{

    public function __construct()
    {
        parent::__construct();

        if(Mage::getStoreConfig('lewysiwyg/general/enable') == true ){
            $this->_addButton('review', array(
                'label'     => Mage::helper('adminhtml')->__('Preview Widget'),
                'type'      => 'button',
                'class'     => 'review',
                'onclick'   => 'wWidget.previewWidget()',
                'id'        => 'button_review_widget'
            ), 0);
        }
        $this->_formScripts[] = 'wWidget = new WysiwygWidget.Widget('
            . '"widget_options_form", "select_widget_type", "widget_options", "'
            . $this->getUrl('*/*/loadOptions') .'", "' . $this->getRequest()->getParam('widget_target_id') . '",
            "'. $this->getUrl('lewysiwyg/widget/preview/') .'"
            );';
    }
}