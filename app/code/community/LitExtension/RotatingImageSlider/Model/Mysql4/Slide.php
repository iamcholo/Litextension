<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Model_Mysql4_Slide extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('rotatingimageslider/rotatingimageslider', 'rotatingimageslider_id');
    }

    public function lookupStoreIds($rotatingimagesliderId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getTable('rotatingimageslider/rotatingimageslider_store'), 'store_id')
                ->where('rotatingimageslider_id = ?', (int) $rotatingimagesliderId);
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
                            array('rotatingimageslider_rotatingimageslider_store' => $this->getTable('rotatingimageslider/rotatingimageslider_store')), $this->getMainTable() . '.rotatingimageslider_id = rotatingimageslider_rotatingimageslider_store.rotatingimageslider_id', array()
                    )
                    ->where('rotatingimageslider_rotatingimageslider_store.store_id IN (?)', $storeIds)
                    ->order('rotatingimageslider_rotatingimageslider_store.store_id DESC')
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
        $table = $this->getTable('rotatingimageslider/rotatingimageslider_store');
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);
        if ($delete) {
            $where = array(
                'rotatingimageslider_id = ?' => (int) $object->getId(),
                'store_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }
        if ($insert) {
            $data = array();
            foreach ($insert as $storeId) {
                $data[] = array(
                    'rotatingimageslider_id' => (int) $object->getId(),
                    'store_id' => (int) $storeId
                );
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return parent::_afterSave($object);
    }

}