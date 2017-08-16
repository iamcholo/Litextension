<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart_Virtuemartv2
    extends LitExtension_CMInventoryUpdater_Model_Cart
{
    public function update(){
        $query = "SELECT vp.virtuemart_product_id, vp.product_in_stock, vpp.product_price FROM _DBPRF_virtuemart_products AS vp
                    LEFT JOIN _DBPRF_virtuemart_product_prices AS vpp ON vpp.virtuemart_product_id = vp.virtuemart_product_id
                    WHERE vp.product_parent_id = 0";
        if($this->_notice['config']['start_date']){
            $query .= " AND vp.modified_on > '" . $this->_notice['config']['start_date'] . "'";
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