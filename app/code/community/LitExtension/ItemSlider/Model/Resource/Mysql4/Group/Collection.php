<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_Resource_Mysql4_Group_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
	protected $_joinedFields = array();

	public function _construct(){
		parent::_construct();
		$this->_init('itemslider/group');
		$this->_map['fields']['store'] = 'store_table.store_id';
	}

	protected function _toOptionArray($valueField='entity_id', $labelField='group_name', $additional=array()){
		return parent::_toOptionArray($valueField, $labelField, $additional);
	}

	protected function _toOptionHash($valueField='entity_id', $labelField='group_name'){
		return parent::_toOptionHash($valueField, $labelField);
	}

	public function addStoreFilter($store, $withAdmin = true){
		if (!isset($this->_joinedFields['store'])){
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

	protected function _renderFiltersBefore(){
		if ($this->getFilter('store')) {
			$this->getSelect()->join(
				array('store_table' => $this->getTable('itemslider/itemslider_store')),
				'main_table.entity_id = store_table.itemslider_id',
				array()
			)->group('main_table.entity_id');
			/*
			 * Allow analytic functions usage because of one field grouping
			 */
			$this->_useAnalyticFunction = true;
		}
		return parent::_renderFiltersBefore();
	}

	public function getSelectCountSql(){
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