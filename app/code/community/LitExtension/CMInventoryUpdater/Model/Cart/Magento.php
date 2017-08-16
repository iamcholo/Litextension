<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Magento
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT p.entity_id AS product_id, s.qty AS qty, d.value AS price 
            FROM _DBPRF_catalog_product_entity as p, _DBPRF_eav_attribute as e, cataloginventory_stock_item as s, _DBPRF_catalog_product_entity_decimal as d 
            WHERE d.entity_id = p.entity_id AND s.product_id = p.entity_id AND e.attribute_id = d.attribute_id AND e.attribute_code = 'price'";
        if($this->_notice['config']['start_date']){
            $query .= " AND p.updated_at > '" . $this->_notice['config']['start_date'] . "'";
        }
        $productInfo = $this->getDataImport($this->getUrlConnector('query'), array(
            'query' => $query
        ));
        if(!$productInfo || $productInfo['result'] != 'success'){
            return array(
                'result' => 'error',
                'msg' => array(
                    $this->msgWarning("Could not get data from connector!")
                )
            );
        }
        return $this->run($productInfo['object']);
    }
}