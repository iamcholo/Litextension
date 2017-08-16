<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Model_Cart
{
    protected $_resource = null;
    protected $_write = null;
    protected $_read = null;
    protected $_notice;
    protected $_cart_url;
    protected $_cart_token;

    public function __construct(){
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_write = $this->_resource->getConnection('core_write');
        $this->_read = $this->_resource->getConnection('core_read');
    }

    /**
     * TODO: Router
     */

    public function getCart($cart_type, $cart_version = null){
        if(!$cart_type){
            return "cart";
        }

        if($cart_type == 'oscommerce'){
            return "cart_oscommerce";
        }
        if($cart_type == 'zencart'){
            return 'cart_zencart';
        }
        if($cart_type == 'virtuemart'){
            if($this->_convertVersion($cart_version, 2) < 200){
                return 'cart_virtuemartv1';
            } else {
                return 'cart_virtuemartv2';
            }
        }
        if($cart_type == 'woocommerce'){
           return 'cart_woocommerce';
        }
        if($cart_type == 'wpecommerce'){
            return 'cart_wpecommerce';
        }
        if($cart_type == 'xtcommerce'){
            return 'cart_xtcommerce';
        }
        if($cart_type == 'xcart'){
            return 'cart_xcart';
        }
        if($cart_type == 'prestashop') {
            if($this->_convertVersion($cart_version, 2) > 149){
                return 'cart_prestashopv16';
            } else {
                return 'cart_prestashopv14';
            }
        }
        if($cart_type == 'opencart') {
            return 'cart_opencart';
        }
        if($cart_type == 'cscart') {
            return 'cart_cscart';
        }
        if($cart_type == 'loaded') {
            return 'cart_loadedcommerce';
        }
        if($cart_type == 'magento') {
            return 'cart_magento';
        }
        if($cart_type == 'interspire') {
            return 'cart_interspire';
        }
        return "cart";
    }

    /**
     * TODO: Notice
     */

    public function setNotice($notice){
        $this->_notice = $notice;
        $this->_cart_url = $notice['config']['cart_url'];
        $this->_cart_token = $notice['config']['cart_token'];
    }

    public function getNotice(){
        return $this->_notice;
    }

    public function getDefaultNotice(){
        return array(
            'config' => array(
                'cart_type' => '',
                'cart_url' => '',
                'cart_token' => '',
                'cart_version' => '',
                'table_prefix' => '',
                'charset' => '',
                'start_date' => ''
            )
        );
    }

    /**
     * TODO: Updater
     */

    public function prepareConfig($params){
        $this->_notice['config']['cart_type'] = $params['cart_type'];
        $this->_notice['config']['cart_url'] = trim(rtrim($params['cart_url'], '/'));
        $this->_notice['config']['cart_token'] = trim($params['cart_token']);
        $this->_notice['config']['start_date'] = trim($params['start_date']);
        $this->setNotice($this->_notice);
        if(strpos($this->_cart_url, 'magento_connector/connector.php') !== false){
            return array(
                'result' => 'error',
                'msg' => array(
                    $this->msgWarning("Can not reach connector!")
                )
            );
        }
        $checkConnector = $this->getDataImport($this->getUrlConnector('check'), array(), true);
        if(!$checkConnector){
            return array(
                'result' => 'error',
                'msg' => array(
                    $this->msgWarning("Can not reach connector!")
                )
            );
        }
        if($checkConnector['result'] != "success"){
            return array(
                'result' => 'error',
                'msg' => array(
                    $this->msgWarning("Token is not correct!")
                )
            );
        }
        $obj = $checkConnector['object'];
        if(!$this->_checkCartSync($obj['cms'], $this->_notice['config']['cart_type'])){
            return array(
                'result' => 'error',
                'msg' => array(
                    $this->msgWarning("Cart type is not correct!")
                )
            );
        }
        $this->_notice['config']['cart_version'] = $obj['version'];
        $this->_notice['config']['table_prefix'] = $obj['table_prefix'];
        $this->_notice['config']['charset'] = $obj['charset'];
        $this->_notice['config']['image_product'] = $obj['image_product'];
        $this->_notice['config']['image_category'] = $obj['image_category'];
        $this->_notice['config']['image_manufacturer'] = $obj['image_manufacturer'];
        return array(
            'result' => 'success',
            'msg' => array()
        );
    }

    /**
     * TODO: Connector
     */

    /**
     * Get url of source cart connector with action and token
     */
    public function getUrlConnector($action){
        $url = $this->_cart_url . '/magento_connector/connector.php?token=' . $this->_cart_token . '&action=' . $action;
        return $url;
    }

    /**
     * Get url of source cart with suffix
     */
    public function getUrlSuffix($suffix){
        $url = rtrim($this->_cart_url, '/') . '/' . ltrim($suffix, '/');
        return $url;
    }

    /**
     * Change _DBPRF_ to table prefix detect or custom config
     */
    protected function _addTablePrefix($data){
        if(isset($data['query'])){
            $prefix = $this->_notice['config']['table_prefix'];
            if(isset($data['serialize'])){
                $queries = unserialize($data['query']);
                $add = array();
                foreach($queries as $table => $query){
                    $change = str_replace('_DBPRF_', $prefix, $query);
                    $add[$table] = $change;
                }
                $data['query'] = serialize($add);
            } else {
                $query = $data['query'];
                $data['query'] = str_replace('_DBPRF_', $prefix, $query);
            }
        }
        return $data;
    }

    /**
     * Set charset for database convert
     */
    protected function _insertParamCharSet($data)
    {
        $charset = array('utf8', 'cp1251');
        if (in_array($this->_notice['config']['charset'], $charset)) {
            $data['char_set'] = 'utf8';
        }
        return $data;
    }

    public function request($url, $method = Zend_Http_Client::GET, $check = false, $params = array(), $config = array('timeout' => 6), $header = array()){
        $result = false;
        if($check && !$this->urlExists($url)){
            return $result;
        }
        $client = new Zend_Http_Client($url, $config);
        if($params){
            switch ($method) {
                case Zend_Http_Client::GET :
                    $client->setParameterGet($params);
                    break;
                case Zend_Http_Client::POST :
                    $client->setParameterPost($params);
                    break;
                case Zend_Http_Client::PUT :
                    $client->setParameterPost($params);
                    break;
                case Zend_Http_Client::DELETE :
                    $client->setParameterGet($params);
                    break;
                default:
                    $client->setParameterPost($params);
                    break;
            }
        }
        if($header){
            $client->setHeaders($header);
        }
        $response = $client->request($method);
        $result = $response->getBody();
        return $result;
    }

    public function getDataImport($url, $data = array(), $check = false){
        $result = false;
        $data = $this->_addTablePrefix($data);
        $data = $this->_insertParamCharSet($data);
        if($data){
            foreach($data as $key => $value){
                $data[$key] = base64_encode($value);
            }
        }
        $response = $this->request($url, Zend_Http_Client::POST, $check, $data);
        if($response){
            $result = unserialize(base64_decode($response));
        }
        return $result;
    }

    public function urlExists($url){
        $header = @get_headers($url, 1);
        if(!$header){
            return false;
        }
        $string = $header[0];
        if(strpos($string, "200")){
            return true;
        }
        return false;
    }

    /**
     * TODO: Database
     */

    /**
     * Convert array to string insert use in raw query
     *
     * @param array $data
     * @param array $allow_keys
     * @return array
     */
    public function arrayToInsertQueryObject($data, $allow_keys = array()){
        if(!$data){
            return false;
        }
        $items = array();
        $keys = array_keys($data);
        $data_allow = array();
        if(!$allow_keys){
            $items = $keys;
            $data_allow = $data;
        } else {
            foreach($keys as $key){
                if(in_array($key, $allow_keys)){
                    $items[] = $key;
                    $data_allow[$key] = $data[$key];
                }
            }
        }
        if(!$items){
            return false;
        }
        $row = '(' . implode(', ', $items) . ')';
        $value = '(:' . implode(', :', $items) . ')';
        return array(
            'row' => $row,
            'value' => $value,
            'data' => $data_allow
        );
    }

    /**
     * Convert array to string update use in raw query
     *
     * @param array $data
     * @param array $allow_keys
     * @return array
     */
    public function arrayToUpdateQueryObject($data, $allow_keys = array()){
        if(!$data){
            return false;
        }
        $items = array();
        $keys = array_keys($data);
        if(!$allow_keys){
            $allow_keys = $keys;
        }
        foreach($keys as $key){
            if(in_array($key, $allow_keys)){
                $set = $key . '= :' . $key;
                $items[] = $set;
            }
        }
        if(!$items){
            return false;
        }
        $set_query = implode(', ', $items);
        return $set_query;
    }

    /**
     * Convert array to where condition in mysql query
     */
    public function arrayToWhereCondition($array){
        if(empty($array)){
            return '1 = 1';
        }
        $data = array();
        foreach($array as $key => $value){
            $data[] = "`" . $key . "` = '" . $value . "'";
        }
        $result = implode(" AND ", $data);
        return $result;
    }

    /**
     * Get table in magento database with table prefix
     */
    public function getTableName($name){
        return $this->_resource->getTableName($name);
    }

    /**
     * Run write query with magento database
     */
    public function writeQuery($query, $bind = array()){
        try{
            $this->_write->query($query, $bind);
            return true;
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'LitExtension_CMInventoryUpdater.log');
            return false;
        }
    }

    /**
     * Run read query with magento database
     */
    public function readQuery($query, $bind= array()){
        try{
            $result = $this->_read->fetchAll($query, $bind);
            return array(
                'result' => 'success',
                'data' => $result
            );
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'LitExtension_CMInventoryUpdater.log');
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }

    /**
     * Get data from table by where condition
     *
     * @param string $table
     * @param array $where
     * @return array
     */
    public function selectTable($table, $where){
        $where_query = $this->arrayToWhereCondition($where);
        $table_name = $this->getTableName($table);
        $query = "SELECT * FROM " . $table_name . " WHERE " . $where_query;
        try{
            $result = $this->_read->fetchAll($query);
            return $result;
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'LitExtension_CMInventoryUpdater.log');
            return false;
        }
    }

    /**
     * Insert data with type array to table
     *
     * @param string $table
     * @param array $data
     * @param array $allow_keys
     * @return boolean
     */
    public function insertTable($table, $data, $allow_keys = array()){
        $obj = $this->arrayToInsertQueryObject($data, $allow_keys);
        if(!$obj){
            return false;
        }
        $row = $obj['row'];
        $value = $obj['value'];
        $data_allow = $obj['data'];
        $table_name = $this->getTableName($table);
        $query = "INSERT INTO " . $table_name . " " . $row . " VALUES " . $value;
        try{
            $this->_write->query($query, $data_allow);
            return true;
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'LitExtension_CMInventoryUpdater.log');
            return false;
        }
    }

    /**
     * Update data with type array to table by where condition
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @param array $allow_keys
     * @return boolean
     */
    public function updateTable($table, $data, $where, $allow_keys = array()){
        $set_query = $this->arrayToUpdateQueryObject($data, $allow_keys);
        if(!$set_query){
            return false;
        }
        $where_query = $this->arrayToWhereCondition($where);
        $table_name = $this->getTableName($table);
        $query = "UPDATE " . $table_name . " SET " . $set_query . " WHERE " . $where_query;
        try{
            $this->_write->query($query, $data);
            return true;
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'LitExtension_CMInventoryUpdater.log');
            return false;
        }
    }

    /**
     * Delete data from table by where condition
     *
     * @param string $table
     * @param array $where
     * @return boolean
     */
    public function deleteTable($table, $where){
        $where_query = $this->arrayToWhereCondition($where);
        $table_name = $this->getTableName($table);
        $query = "DELETE FROM " . $table_name . " WHERE " . $where_query;
        try{
            $this->_write->query($query);
            return true;
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'LitExtension_CMInventoryUpdater.log');
            return false;
        }
    }

    /**
     * Get one row of result select
     *
     * @param string $table
     * @param array $where
     * @return array
     */
    public function selectTableRow($table, $where){
        $result = $this->selectTable($table, $where);
        if(!$result){
            return false;
        }
        return (isset($result[0])) ? $result[0] : false;
    }

    /**
     * TODO: Magento
     */

    public function getResource(){
        if(!$this->_resource){
            $this->_resource = Mage::getSingleton('core/resource');
        }
        return $this->_resource;
    }

    public function getWriteConnect(){
        if(!$this->_write){
            $resource = $this->getResource();
            $this->_write = $resource->getConnection('core_write');
        }
        return $this->_write;
    }

    public function getReadConnect(){
        if(!$this->_read){
            $resource = $this->getResource();
            $this->_read = $resource->getConnection('core_read');
        }
        return $this->_read;
    }

    public function updateQty($product_id, $qty){
        $result = true;
        $pro_qty_exists = $this->selectTableRow('cataloginventory_stock_item', array(
            'product_id' => $product_id
        ));
        if(!$pro_qty_exists){
            return $result;
        }
        $update_qty_result = $this->updateTable('cataloginventory_stock_item', array(
            'qty' => $qty
        ), array(
            'product_id' => $product_id
        ));
        return $update_qty_result;
    }

    public function updatePrice($product_id, $price, $entityTypeId, $attr_price_id){
        $result = true;
        $pro_price_exists = $this->selectTableRow('catalog_product_entity_decimal', array(
            'entity_type_id' => $entityTypeId,
            'attribute_id' => $attr_price_id,
            'entity_id' => $product_id
        ));
        if(!$pro_price_exists){
            return $result;
        }
        $update_price_result = $this->updateTable('catalog_product_entity_decimal', array(
            'value' => $price
        ), array(
            'entity_type_id' => $entityTypeId,
            'attribute_id' => $attr_price_id,
            'entity_id' => $product_id
        ));
        return $update_price_result;
    }

    public function run($data){
        $attr_price_id = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'price')->getAttributeId();
        $entityTypeId = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $update_price_success = 0;
        $update_price_error = 0;
        $update_qty_success = 0;
        $update_qty_error = 0;
        foreach($data as $productRow){
            $pro_src_id = $productRow['product_id'];
            $pro_src_qty = $productRow['qty'];
            $pro_src_price = $productRow['price'];
            $proIpt = $this->selectTable(LitExtension_CartMigration_Model_Cart::TABLE_UPDATE, array(
                'domain' => $this->_cart_url,
                'id_import' => $pro_src_id
            ));
            if(!$proIpt){
                continue ;
            }
            foreach($proIpt as $pro_ipt){
                $mage_id = $pro_ipt['mage_id'];
                $update_qty_result = $this->updateQty($mage_id, $pro_src_qty);
                if($update_qty_result){
                    $update_qty_success++;
                } else {
                    $update_qty_error++;
                }
                $update_price_result = $this->updatePrice($mage_id, $pro_src_price, $entityTypeId, $attr_price_id);
                if($update_price_result){
                    $update_price_success++;
                } else {
                    $update_price_error++;
                }
            }
        }
        $msg = array();
        $msg[] = $this->msgSuccess("Price update: " . $update_price_success . " success, " . $update_price_error . " error.");
        $msg[] = $this->msgSuccess("Qty update: " . $update_qty_success . " success, " . $update_qty_error . " error.");
        return array(
            'result' => 'success',
            'msg' => $msg
        );
    }

    /**
     * TODO: Extends
     */

    public function msgSuccess($msg){
        $message = "<p class='ms-success'>" . $msg . "</p>";
        return $message;
    }

    public function msgWarning($msg){
        $message = "<p class='ms-warning'>" . $msg . "</p>";
        return $message;
    }

    /**
     * Check sync cart type select and cart type detect
     */
    protected function _checkCartSync($cms, $select) {
        $pos = strpos($select, $cms);
        if($pos === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Convert version from string to int
     *
     * @param string $v : String of version split by dot
     * @param int $num : number of result return
     * @return int
     */
    protected function _convertVersion($v, $num) {
        $digits = @explode(".", $v);
        $version = 0;
        if (is_array($digits)) {
            foreach ($digits as $k => $v) {
                if($k <= $num){
                    $version += (substr($v, 0, 1) * pow(10, max(0, ($num - $k))));
                }
            }
        }
        return $version;
    }
}