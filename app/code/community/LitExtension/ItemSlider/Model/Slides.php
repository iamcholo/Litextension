<?php

/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_Slides extends Mage_Core_Model_Abstract {

    const ENTITY = 'itemslider_slides';
    const CACHE_TAG = 'itemslider_slides';

    protected $_eventPrefix = 'itemslider_slides';
    protected $_eventObject = 'slides';

    public function _construct() {
        parent::_construct();
        $this->_init('itemslider/slides');
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

    protected function _afterSave() {
        return parent::_afterSave();
    }

}