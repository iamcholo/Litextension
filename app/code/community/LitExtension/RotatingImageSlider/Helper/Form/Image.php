<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Helper_Form_Image extends Varien_Data_Form_Element_Abstract {

    public function __construct($data) {
        parent::__construct($data);
        $this->setType('file');
    }

    public function getElementHtml() {
        $html = '';
        $html .= parent::getElementHtml();
        if ((string) $this->getValue()) {
            $url = $this->_getUrl();

            if (!preg_match("/^http\:\/\/|https\:\/\//", $url)) {
                $url = Mage::getBaseUrl('media') . $url;
            }

            $html .= '<br /><a href="' . $url . '"'
                    . ' onclick="imagePreview(\'' . $this->getHtmlId() . '_image\'); return false;">'
                    . '<img src="' . $url . '" id="' . $this->getHtmlId() . '_image" title="' . $this->getValue() . '"'
                    . ' alt="' . $this->getValue() . '" width="425" class="small-image-preview v-middle" />'
                    . '</a> ';
        }

        return $html;
    }

    protected function _getDeleteCheckbox() {
        $html = '';
        if ($this->getValue()) {
            $label = Mage::helper('core')->__('Delete Image');
            $html .= '<span class="delete-image">';
            $html .= '<input type="checkbox"'
                    . ' name="' . parent::getName() . '[delete]" value="1" class="checkbox"'
                    . ' id="' . $this->getHtmlId() . '_delete"' . ($this->getDisabled() ? ' disabled="disabled"' : '')
                    . '/>';
            $html .= '<label for="' . $this->getHtmlId() . '_delete"'
                    . ($this->getDisabled() ? ' class="disabled"' : '') . '> ' . $label . '</label>';
            $html .= $this->_getHiddenInput();
            $html .= '</span>';
        }

        return $html;
    }

    protected function _getHiddenInput() {
        return '<input type="hidden" name="' . parent::getName() . '[value]" value="' . $this->getValue() . '" />';
    }

    protected function _getUrl() {
        $url = false;
        if ($this->getValue()) {
            $url = Mage::helper('rotatingimageslider/image_rotatingimageslider')->getImageBaseUrl() . $this->getValue();
        }
        return $url;
    }

    public function getName() {
        return $this->getData('name');
    }

}

