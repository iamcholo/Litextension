<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_Mysql4_Group extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('leimageslider/leimageslider_group', 'leimageslider_group_id');
    }

    public function lookupStoreIds($leimagesliderId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getTable('leimageslider/leimageslider_group_store'), 'store_id')
                ->where('leimageslider_group_id = ?', (int) $leimagesliderId);
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
                            array('leimageslider_leimageslider_group_store' => $this->getTable('leimageslider/leimageslider_group_store')), $this->getMainTable() . '.leimageslider_group_id = leimageslider_leimageslider_group_store.leimageslider_group_id', array()
                    )
                    ->where('leimageslider_leimageslider_group_store.store_id IN (?)', $storeIds)
                    ->order('leimageslider_leimageslider_group_store.store_id DESC')
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
        $table = $this->getTable('leimageslider/leimageslider_group_store');
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);
        if ($delete) {
            $where = array(
                'leimageslider_group_id = ?' => (int) $object->getId(),
                'store_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }
        if ($insert) {
            $data = array();
            foreach ($insert as $storeId) {
                $data[] = array(
                    'leimageslider_group_id' => (int) $object->getId(),
                    'store_id' => (int) $storeId
                );
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return parent::_afterSave($object);
    }

    public function checkUrlKey($urlKey, $storeId, $active = true) {
        $stores = array(Mage_Core_Model_App::ADMIN_STORE_ID, $storeId);
        $select = $this->_initCheckUrlKeySelect($urlKey, $stores);
        if (!is_null($active)) {
            $select->where('e.status = ?', $active);
        }
        $select->reset(Zend_Db_Select::COLUMNS)
                ->columns('e.leimageslider_group_id')
                ->limit(1);

        return $this->_getReadAdapter()->fetchOne($select);
    }

    protected function _initCheckUrlKeySelect($urlKey, $store) {
        $select = $this->_getReadAdapter()->select()
                ->from(array('e' => $this->getMainTable()))
                ->join(
                        array('es' => $this->getTable('leimageslider/leimageslider_group_store')), 'e.leimageslider_group_id = es.leimageslider_group_id', array())
                ->where('e.url_key = ?', $urlKey)
                ->where('es.store_id IN (?)', $store);
        return $select;
    }

    public function getIsUniqueUrlKey(Mage_Core_Model_Abstract $object) {
        if (Mage::app()->isSingleStoreMode() || !$object->hasStores()) {
            $stores = array(Mage_Core_Model_App::ADMIN_STORE_ID);
        } else {
            $stores = (array) $object->getData('stores');
        }
        $select = $this->_initCheckUrlKeySelect($object->getData('url_key'), $stores);
        if ($object->getId()) {
            $select->where('e.leimageslider_group_id <> ?', $object->getId());
        }
        if ($this->_getWriteAdapter()->fetchRow($select)) {
            return false;
        }
        return true;
    }

    protected function isNumericUrlKey(Mage_Core_Model_Abstract $object) {
        return preg_match('/^[0-9]+$/', $object->getData('url_key'));
    }

    protected function isValidUrlKey(Mage_Core_Model_Abstract $object) {
        return preg_match('/^[a-z0-9][a-z0-9_\/-]+(\.[a-z0-9_-]+)?$/', $object->getData('url_key'));
    }

}