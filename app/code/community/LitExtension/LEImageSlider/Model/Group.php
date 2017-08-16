<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_Group extends Mage_Core_Model_Abstract {

    const ENTITY = 'leimageslider_group';
    const CACHE_TAG = 'leimageslider_group';

    protected $_eventPrefix = 'leimageslider_group';
    protected $_eventObject = 'group';

    public function _construct() {
        parent::_construct();
        $this->_init('leimageslider/group');
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

    public function getLeimagesliderUrl() {
        if ($this->getUrlKey()) {
            return Mage::getUrl('', array('_direct' => $this->getUrlKey()));
        }
        return Mage::getUrl('leimageslider/group/view', array('id' => $this->getId()));
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

    public function checkUrlKey($urlKey, $active = true) {
        return $this->_getResource()->checkUrlKey($urlKey, $active);
    }

}