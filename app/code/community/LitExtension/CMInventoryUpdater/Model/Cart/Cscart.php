<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Cscart
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT p.product_id AS product_id, p.amount AS qty, pp.price AS price FROM _DBPRF_products as p, _DBPRF_product_prices as pp WHERE p.product_id = pp.product_id AND pp.percentage_discount = '0' AND pp.lower_limit = '1'";
        if($this->_notice['config']['start_date']){
            $timestamp = strtotime($this->_notice['config']['start_date']);
            $query .= " AND (p.updated_timestamp > '{$timestamp}' OR p.product_id IN (SELECT distinct product_id FROM _DBPRF_order_details as od, _DBPRF_orders as o WHERE od.order_id = o.order_id AND o.timestamp > '{$timestamp}'))";
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