<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Model_Mysql4_Banner extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('promotionbanner/promotionbanner', 'promotionbanner_id');
    }

    public function lookupStoreIds($bannerId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getTable('promotionbanner/promotionbanner_store'), 'store_id')
                ->where('promotionbanner_id = ?', (int) $bannerId);
        return $adapter->fetchCol($select);
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object) {
        if ($object->getId()) {
            $stores = $this->lookupStoreIds($object->getId());
            $object->setData('store_id', $stores);
        }
        return parent::_afterLoad($object);
    }

    protected function _getLoadSelect($field, $value, $object) {
        $select = parent::_getLoadSelect($field, $value, $object);
        if ($object->getStoreId()) {
            $storeIds = array(Mage_Core_Model_App::ADMIN_STORE_ID, (int) $object->getStoreId());
            $select->join(
                            array('promotionbanner_store' => $this->getTable('promotionbanner/promotionbanner_store')), $this->getMainTable() . '.promotionbanner_id = promotionbanner_store.promotionbanner_id', array()
                    )
                    ->where('promotionbanner_store.store_id IN (?)', $storeIds)
                    ->order('promotionbanner_store.store_id DESC')
                    ->limit(1);
        }
        return $select;
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object) {
        $oldStores = $this->lookupStoreIds($object->getId());
        $newStores = (array) $object->getStores();
        if (empty($newStores)) {
            $newStores = (array) $object->getStoreId();
        }
        $table = $this->getTable('promotionbanner/promotionbanner_store');
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);
        if ($delete) {
            $where = array(
                'promotionbanner_id = ?' => (int) $object->getId(),
                'store_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }
        if ($insert) {
            $data = array();
            foreach ($insert as $storeId) {
                $data[] = array(
                    'promotionbanner_id' => (int) $object->getId(),
                    'store_id' => (int) $storeId
                );
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return parent::_afterSave($object);
    }

}