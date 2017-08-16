<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_Mysql4_Slide extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('leimageslider/leimageslider', 'leimageslider_id');
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object) {
        return parent::_afterLoad($object);
    }

    protected function _getLoadSelect($field, $value, $object) {
        return parent::_getLoadSelect($field, $value, $object);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object) {

        return parent::_afterSave($object);
    }
}