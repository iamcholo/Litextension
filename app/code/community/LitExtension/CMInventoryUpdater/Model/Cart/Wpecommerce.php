<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Wpecommerce
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT p.ID AS product_id, pm_qty.meta_value AS qty, pm_price.meta_value AS price FROM _DBPRF_posts AS p
                    LEFT JOIN _DBPRF_postmeta AS pm_qty ON pm_qty.post_id  = p.ID AND pm_qty.meta_key = '_wpsc_stock'
                    LEFT JOIN _DBPRF_postmeta AS pm_price ON pm_price.post_id  = p.ID AND pm_price.meta_key = '_wpsc_price'
                     WHERE p.post_type = 'wpsc-product' AND p.post_status = 'publish' AND p.post_parent = 0";
        if($this->_notice['config']['start_date']){
            $query .= " AND p.post_modified > '" . $this->_notice['config']['start_date'] . "'";
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