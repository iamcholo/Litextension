<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_SimpleConfigurableProducts_Model_Catalog_Resource_Eav_Mysql4_Product_Indexer_Price
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price
{
    private function getChildIdsByParent($parentId)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('p' => $this->getTable('catalog/product')), array('entity_id'))
            ->join(
                array('pr' => $this->getTable('catalog/product_relation')),
                'pr.child_id=p.entity_id',
                array('p.type_id'))
            ->where('pr.parent_id=?', $parentId);
        return $read->fetchPairs($select);
    }

    private function getProductTypeById($id)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('pr' => $this->getTable('catalog/product_relation')), array('parent_id'))
            ->join(
                array('p' => $this->getTable('catalog/product')),
                'pr.parent_id=p.entity_id',
                array('p.type_id'))
            ->where('pr.parent_id=?', $id);
        $data = $read->fetchRow($select);
        return $data['type_id'];
    }

    public function catalogProductSave(Mage_Index_Model_Event $event)
    {
        $productId = $event->getEntityPk();
        $data = $event->getNewData();

        if (!isset($data['reindex_price'])) {
            return $this;
        }
        $this->cloneIndexTable(true);
        $this->_prepareWebsiteDateTable();

        $indexer = $this->_getIndexer($data['product_type_id']);
        $processIds = array($productId);

        if ($indexer->getIsComposite()) {
            if ($this->getProductTypeById($productId) == 'configurable') {
                $children = $this->getChildIdsByParent($productId);
                $processIds = array_merge($processIds, array_keys($children));
                $tierPriceIds = array_keys($children);
            } else {
                $tierPriceIds = $productId;
            }
            $this->_copyRelationIndexData($productId);
            $this->_prepareTierPriceIndex($tierPriceIds);
            $indexer->reindexEntity($productId);
        } else {
            $parentIds = $this->getProductParentsByChild($productId);

            if ($parentIds) {
                $processIds = array_merge($processIds, array_keys($parentIds));

                $siblingIds = array();
                foreach (array_keys($parentIds) as $parentId) {
                    $childIds = $this->getChildIdsByParent($parentId);
                    $siblingIds = array_merge($siblingIds, array_keys($childIds));
                }
                if(count($siblingIds)>0) {
                    $processIds = array_unique(array_merge($processIds, $siblingIds));
                }
                $this->_copyRelationIndexData(array_keys($parentIds), $productId);

                $this->_prepareTierPriceIndex($processIds);
                $indexer->reindexEntity($productId);

                $parentByType = array();
                foreach ($parentIds as $parentId => $parentType) {
                    $parentByType[$parentType][$parentId] = $parentId;
                }

                foreach ($parentByType as $parentType => $entityIds) {
                    $this->_getIndexer($parentType)->reindexEntity($entityIds);
                }

            } else {
                $this->_prepareTierPriceIndex($productId);
                $indexer->reindexEntity($productId);
            }
        }

        $this->_copyIndexDataToMainTable($processIds);

        return $this;
    }

}
