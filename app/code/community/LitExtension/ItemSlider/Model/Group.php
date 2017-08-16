<?php

/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_Group extends Mage_Core_Model_Abstract {

    const ENTITY = 'itemslider_group';
    const CACHE_TAG = 'itemslider_group';

    protected $_eventPrefix = 'itemslider_group';
    protected $_eventObject = 'group';

    public function _construct() {
        parent::_construct();
        $this->_init('itemslider/group');
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

    public function getProducts() { //$page_num
        return Mage::getModel('catalog/product')->getCollection()
                        ->addFieldToFilter('entity_id', array('in' => $this->getItemIdsArray()))
                        ->addFieldToFilter('status', array('eq' => '1'))
                        ->addAttributeToSelect("*");
    }

    public function getCategories() {
        return Mage::getModel('catalog/category')->getCollection()
                        ->addAttributeToSelect("*")
                        ->addFieldToFilter('entity_id', array('in' => $this->getItemIdsArray()))
                        ->addFieldToFilter('is_active', array('eq' => '1'));
    }

    private function getItemIdsArray() {
        $items = explode(",", trim($this->getData("item_ids")));
        $ret = array();
        foreach ($items as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $ret[] = $item;
            }
        }
        return $ret;
    }

}