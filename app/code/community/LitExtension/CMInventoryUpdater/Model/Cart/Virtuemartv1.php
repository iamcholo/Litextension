<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Virtuemartv1
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT vp.product_id, vp.product_in_stock, vpp.product_price FROM _DBPRF_vm_product AS vp
                    LEFT JOIN _DBPRF_vm_product_price AS vpp ON vpp.product_id = vp.product_id
                    WHERE vp.product_parent_id = 0";
        if($this->_notice['config']['start_date']){
            $query .= " AND vp.mdate > '" . strtotime($this->_notice['config']['start_date']) . "'";
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