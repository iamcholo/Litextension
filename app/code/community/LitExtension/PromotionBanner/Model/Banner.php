<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_Banner extends Mage_Core_Model_Abstract {

    const ENTITY = 'promotionbanner_banner';
    const CACHE_TAG = 'promotionbanner_banner';

    protected $_eventPrefix = 'promotionbanner_banner';
    protected $_eventObject = 'banner';

    public function _construct() {
        parent::_construct();
        $this->_init('promotionbanner/banner');
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