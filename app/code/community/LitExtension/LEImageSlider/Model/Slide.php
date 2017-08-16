<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_Slide extends Mage_Core_Model_Abstract {

    const ENTITY = 'leimageslider_slide';
    const CACHE_TAG = 'leimageslider_slide';

    protected $_eventPrefix = 'leimageslider_slide';
    protected $_eventObject = 'slide';

    public function _construct() {
        parent::_construct();
        $this->_init('leimageslider/slide');
    }

    protected function _beforeSave() {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        }
        $this->setUpdatedAt($now);
        return $this;
    }

    public function getContent() {
        $content = $this->getData('content');
        $helper = Mage::helper('cms');
        $processor = $helper->getBlockTemplateProcessor();
        $html = $processor->filter($content);
        return $html;
    }

    protected function _afterSave() {
        return parent::_afterSave();
    }

}