<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Model_Slide extends Mage_Core_Model_Abstract {

    const ENTITY = 'rotatingimageslider_slide';
    const CACHE_TAG = 'rotatingimageslider_slide';

    protected $_eventPrefix = 'rotatingimageslider_slide';
    protected $_eventObject = 'slide';

    public function _construct() {
        parent::_construct();
        $this->_init('rotatingimageslider/slide');
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

    public function getRotatingimagesliderUrl() {
        return Mage::getUrl('rotatingimageslider/slide/view', array('id' => $this->getId()));
    }

    protected function _afterSave() {
        return parent::_afterSave();
    }

}