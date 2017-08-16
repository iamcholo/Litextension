<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Xtcommerce
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT products_id AS product_id, products_quantity AS qty, products_price AS price FROM _DBPRF_products";
        if($this->_notice['config']['start_date']){
            $query .= " WHERE `products_last_modified` > '" . $this->_notice['config']['start_date'] . "'";
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