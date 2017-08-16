<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Prestashopv16
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT p.id_product AS product_id, sa.quantity AS qty, p.price AS price FROM _DBPRF_product as p, _DBPRF_stock_available as sa WHERE sa.id_product = p.id_product AND sa.id_product_attribute = '0'";
        if($this->_notice['config']['start_date']){
            $query .= " AND (p.date_upd > '{$this->_notice['config']['start_date']}' OR p.id_product IN (SELECT distinct product_id FROM _DBPRF_order_detail as od, _DBPRF_orders as o WHERE od.id_order = o.id_order AND o.date_upd > '{$this->_notice['config']['start_date']}'))";
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