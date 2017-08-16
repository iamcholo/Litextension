<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Xcart
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT pro.productid AS product_id, pri.quantity AS qty, pri.price AS price FROM _DBPRF_products AS pro
                    LEFT JOIN _DBPRF_pricing AS pri ON pri.productid = pro.productid AND pri.variantid = 0 AND pri.quantity = 1 AND pri.membershipid  = 0";
        if($this->_notice['config']['start_date']){
            $query .= " WHERE `add_date` > '" . strtotime($this->_notice['config']['start_date']) . "'";
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