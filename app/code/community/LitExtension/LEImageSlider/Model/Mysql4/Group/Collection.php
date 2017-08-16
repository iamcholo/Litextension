<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_Mysql4_Group_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    protected $_joinedFields = array();

    public function _construct() {
        parent::_construct();
        $this->_init('leimageslider/group');
        $this->_map['fields']['store'] = 'store_table.store_id';
    }

    protected function _toOptionArray($valueField = 'leimageslider_group_id', $labelField = 'sort_order', $additional = array()) {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

    protected function _toOptionHash($valueField = 'leimageslider_group_id', $labelField = 'sort_order') {
        return parent::_toOptionHash($valueField, $labelField);
    }

    public function addStoreFilter($store, $withAdmin = true) {
        if (!isset($this->_joinedFields['store'])) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }
            if (!is_array($store)) {
                $store = array($store);
            }
            if ($withAdmin) {
                $store[] = Mage_Core_Model_App::ADMIN_STORE_ID;
            }
            $this->addFilter('store', array('in' => $store), 'public');
            $this->_joinedFields['store'] = true;
        }
        return $this;
    }

    protected function _renderFiltersBefore() {
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                    array('store_table' => $this->getTable('leimageslider/leimageslider_group_store')), 'main_table.leimageslider_group_id = store_table.leimageslider_group_id', array()
            )->group('main_table.leimageslider_group_id');

            $this->_useAnalyticFunction = true;
        }
        return parent::_renderFiltersBefore();
    }

    public function getSelectCountSql() {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);
        return $countSelect;
    }

}