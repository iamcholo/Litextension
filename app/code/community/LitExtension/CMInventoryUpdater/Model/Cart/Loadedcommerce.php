<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Loadedcommerce
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT p.products_id AS product_id, p.products_quantity AS qty, p.products_price AS price FROM _DBPRF_products as p";
        if($this->_notice['config']['start_date']){
            $query .= " WHERE (p.products_last_modified > '{$this->_notice['config']['start_date']}' OR p.products_id IN (SELECT distinct products_id FROM _DBPRF_orders_products as od, _DBPRF_orders as o WHERE od.orders_id = o.orders_id AND o.date_purchased > '{$this->_notice['config']['start_date']}'))";
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