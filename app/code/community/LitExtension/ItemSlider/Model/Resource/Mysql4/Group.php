<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_Resource_Mysql4_Group extends Mage_Core_Model_Mysql4_Abstract{

	public function _construct(){
		$this->_init('itemslider/itemslider', 'entity_id');
	}
	

	public function lookupStoreIds($itemsliderId){
		$adapter = $this->_getReadAdapter();
		$select  = $adapter->select()
			->from($this->getTable('itemslider/itemslider_store'), 'store_id')
			->where('itemslider_id = ?',(int)$itemsliderId);
		return $adapter->fetchCol($select);
	}

	protected function _getLoadSelect($field, $value, $object){
		$select = parent::_getLoadSelect($field, $value, $object);
		if ($object->getStoreId()) {
			$storeIds = array(Mage_Core_Model_App::ADMIN_STORE_ID, (int)$object->getStoreId());
			$select->join(
				array('itemslider_itemslider_store' => $this->getTable('itemslider/itemslider_store')),
				$this->getMainTable() . '.entity_id = itemslider_itemslider_store.itemslider_id',
				array()
			)
			->where('itemslider_itemslider_store.store_id IN (?)', $storeIds)
			->order('itemslider_itemslider_store.store_id DESC')
			->limit(1);
		}
		return $select;
	}

}