<?php

/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ACRegistration_Model_Resource_Mysql4_Acregistration_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    protected $_joinedFields = array();

    public function _construct() {
        parent::_construct();
        $this->_init('acregistration/acregistration');
    }

    protected function _toOptionArray($valueField = 'acr_id',  $additional = array()) {
        return parent::_toOptionArray($valueField, $additional);
    }

    protected function _toOptionHash($valueField = 'acr_id') {
        return parent::_toOptionHash($valueField);
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