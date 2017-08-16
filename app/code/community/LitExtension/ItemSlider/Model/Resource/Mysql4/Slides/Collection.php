<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_Resource_Mysql4_Slides_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    protected $_joinedFields = array();

    public function _construct() {
        parent::_construct();
        $this->_init('itemslider/slides');
    }

    protected function _toOptionArray($valueField = 'slide_id', $labelField = 'sort_order', $additional = array()) {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

    protected function _toOptionHash($valueField = 'slide_id', $labelField = 'sort_order') {
        return parent::_toOptionHash($valueField, $labelField);
    }

    protected function _renderFiltersBefore() {
        return parent::_renderFiltersBefore();
    }

    public function getSelectCountSql() {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);
        return $countSelect;
    }

    public function addEnableFilter($status){
        $this->getSelect()
            ->where('main_table.status = ?', $status);
        return $this;
    }

}