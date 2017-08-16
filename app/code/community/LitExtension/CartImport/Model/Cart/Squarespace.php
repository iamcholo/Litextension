<?php
/**
 * @project: CartImport
* @author : LitExtension
* @url    : http://litextension.com
* @email  : litextension@gmail.com
*/

class LitExtension_CartImport_Model_Cart_Squarespace
extends LitExtension_CartImport_Model_Cart
{
    const SS_CAT = 'lecaip_squarespace_category';
    const SS_PRO = 'lecaip_squarespace_product';
    const SS_CUS = 'lecaip_squarespace_customer';
    const SS_ORD = 'lecaip_squarespace_order';
    const SS_ORD_ID = 'lecaip_squarespace_order_id';
    
    public function getListUpload()
    {
        $upload = array(
            array('value' => 'products', 'label' => "Products"),
            array('value' => 'orders', 'label' => "Orders"),
        );
        return $upload;
    }
    
    public function clearPreSection()
    {
        $tables = array(
            self::SS_CAT,
            self::SS_CUS,
            self::SS_ORD,
            self::SS_ORD_ID,
            self::SS_PRO
        );
        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name}";
            $this->writeQuery($query);
        }
    }        
    
    public function getAllowExtensions()
    {
        return array('csv', 'txt');
    }
    
    public function getUploadFileName($upload_name)
    {
        $name = '';
        if ($upload_name == 'orders') {
            $name = $upload_name . '.csv';
        } else {
            $name = $upload_name . '.txt';
        }
        return $name;
    }
    
    public function getUploadInfo($up_msg)
    {
        $files = array_filter($this->_notice['config']['files']);
        if (!empty($files)) {
            $this->_notice['config']['config_support']['currency_map'] = false;
            $this->_notice['config']['config_support']['country_map'] = false;
            $this->_notice['config']['import_support']['taxes'] = false;
            $this->_notice['config']['import_support']['manufacturers'] = false;
            $this->_notice['config']['import_support']['reviews'] = false;
            if (!$this->_notice['config']['files']['products']) {
                $this->_notice['config']['import_support']['categories'] = false;
                $this->_notice['config']['import_support']['products'] = false;
            }
            if (!$this->_notice['config']['files']['orders']) {
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['customers'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
            }
            foreach ($files as $type => $upload) {
                if ($upload) {
                    $func_construct = $type . "TableConstruct";
                    $construct = $this->$func_construct();
                    $validate = isset($construct['validation']) ? $construct['validation'] : false;
                    if ($type == 'orders') {
                        $csv_file = Mage::getBaseDir('media'). self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
                        $readCsv = $this->readCsv($csv_file, 0, 1, false);
                        if ($readCsv['result'] == 'success') {
                            foreach ($readCsv['data'] as $item) {
                                if ($validate) {
                                    foreach ($validate as $row) {
                                        if (!in_array($row, $item['title'])) {
                                            $up_msg[$type] = array(
                                                'elm' => '#ur-' . $type,
                                                'msg' => "<div class='uir-warning'> File uploaded has incorrect structure</div>"
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }                    
                }
            }
            $this->_notice['csv_import']['function'] = '_setupStorageCsv';
        }        
        return array(
            'result' => 'success',
            'msg' => $up_msg
        );
    }
    
    public function storageCsv()
    {
        if(LitExtension_CartImport_Model_Custom::CSV_STORAGE){
            return $this->_custom->storageCsvCustom($this);
        }
        $function = $this->_notice['csv_import']['function'];
        if(!$function){
            return array(
                'result' => 'success',
                'msg' => ''
            );
        }
        return $this->$function();
    }
    
    public function _setupStorageCsv()
    {
        $custom_setup = $this->_custom->storageCsvCustom($this);
        if($custom_setup && $custom_setup['result'] == 'error'){
            return $custom_setup;
        }
        $setup = true;
        $tableDrop = $this->getListTableDrop();
        foreach ($tableDrop as $table_drop) {
            $this->dropTable($table_drop);
        }
        $tables = $queries = array();
        $creates = array(
            'categoriesTableConstruct',
            'productsTableConstruct',
            'customersTableConstruct',
            'ordersTableConstruct',
            'orderidsTableConstruct',
        );
        foreach ($creates as $create) {
            $tables[] = $this->$create();
        }
        foreach ($tables as $table) {
            $table_query = $this->arrayToCreateSql($table);
            if ($table_query['result'] != 'success') {
                $table_query['msg'] = $this->consoleError($table_query['msg']);
                return $table_query;
            }
            $queries[] = $table_query['query'];
        }
        foreach ($queries as $query) {
            if (!$this->writeQuery($query)) {
                $setup = false;
            }
        }
        if ($setup) {
            //
        } else {
            return array(
                'result' => 'error',
                'msg' => $this->consoleError("Could not created table to storage data.")
            );
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_clearStorageCsv';
        $this->_notice['csv_import']['msg'] = "";
        return $this->_notice['csv_import'];
    }
    
    public function _clearStorageCsv()
    {
        $tables = $this->_getTablesTmp();
        $folder = $this->_folder;
        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
            $this->writeQuery($query);
        }
        $this->_notice['csv_import']['function'] = 'storageCsvProducts';
        return array(
            'result' => 'process',
            'msg' => ''
        );
    }
    
    public function getListTableDrop(){
        $tables = $this->_getTablesTmp();
        $custom = $this->_custom->getListTableDropCustom($tables);
        $result = $custom ? $custom : $tables;
        return $result;
    }
    
    public function storageCsvProducts()
    {
        return $this->storageCsvByType('products', 'orders', false, false, array('product_id'));
    }
    
    public function storageCsvOrders()
    {
        return $this->storageCsvByType('orders', 'orders', false, true);
    }
    
    public function categoriesTableConstruct()
    {
        return array(
            'table' => self::SS_CAT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'name' => 'TEXT',
                'code' => 'TEXT',
            ),
        );
    }
    
    public function productsTableConstruct()
    {
        return array(
            'table' => self::SS_PRO,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'product_id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'productType' => 'TEXT',
                'id' => 'TEXT',
                'websiteId' => 'TEXT',
                'url' => 'TEXT',
                'visibility' => 'TEXT',
                'name' => 'TEXT',
                'description' => 'TEXT',
                'images' => 'TEXT',
                'additionalInfo' => 'TEXT',
                'featuredProduct' => 'TEXT',
                'tags' => 'TEXT',
                'categories' => 'TEXT',
                'variantAttributeNames' => 'TEXT',
                'variants' => 'TEXT',
            ),
            'validation' => array('product_id')
        );
    }
    
    public function customersTableConstruct()
    {
        return array(
            'table' => self::SS_CUS,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'customer_id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'Created_at' => 'TEXT',
                'Order_ID' => 'TEXT',
                'Email' => 'TEXT',
                'Created_at' => 'TEXT',
                'Billing_Name' => 'TEXT',
                'Billing_Address1' => 'TEXT',
                'Billing_Address2' => 'TEXT',
                'Billing_City' => 'TEXT',
                'Billing_Zip' => 'TEXT',
                'Billing_Province' => 'TEXT',
                'Billing_Country' => 'TEXT',
                'Billing_Phone' => 'TEXT',
                'Shipping_Name' => 'TEXT',
                'Shipping_Address1' => 'TEXT',
                'Shipping_Address2' => 'TEXT',
                'Shipping_City' => 'TEXT',
                'Shipping_Zip' => 'TEXT',
                'Shipping_Province' => 'TEXT',
                'Shipping_Country' => 'TEXT',
            ),
        );
    }
    
    public function ordersTableConstruct()
    {
        return array(
            'table' => self::SS_ORD,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'Order_ID' => 'TEXT',
                'Email' => 'TEXT',
                'Financial_Status' => 'TEXT',
                'Paid_at' => 'TEXT',
                'Fulfillment_Status' => 'TEXT',
                'Fulfilled_at' => 'TEXT',
                'Currency' => 'TEXT',
                'Subtotal' => 'TEXT',
                'Shipping' => 'TEXT',
                'Taxes' => 'TEXT',
                'Amount_Refunded' => 'TEXT',
                'Total' => 'TEXT',
                'Discount_Code' => 'TEXT',
                'Discount_Amount' => 'TEXT',
                'Shipping_Method' => 'TEXT',
                'Created_at' => 'TEXT',
                'Lineitem_quantity' => 'TEXT',
                'Lineitem_name' => 'TEXT',
                'Lineitem_price' => 'TEXT',
                'Lineitem_sku' => 'TEXT',
                'Lineitem_variant' => 'TEXT',
                'Lineitem_requires_shipping' => 'TEXT',
                'Lineitem_taxable' => 'TEXT',
                'Lineitem_fulfillment_status' => 'TEXT',
                'Billing_Name' => 'TEXT',
                'Billing_Address1' => 'TEXT',
                'Billing_Address2' => 'TEXT',
                'Billing_City' => 'TEXT',
                'Billing_Zip' => 'TEXT',
                'Billing_Province' => 'TEXT',
                'Billing_Country' => 'TEXT',
                'Billing_Phone' => 'TEXT',
                'Shipping_Name' => 'TEXT',
                'Shipping_Address1' => 'TEXT',
                'Shipping_Address2' => 'TEXT',
                'Shipping_City' => 'TEXT',
                'Shipping_Zip' => 'TEXT',
                'Shipping_Province' => 'TEXT',
                'Shipping_Country' => 'TEXT',
                'Cancelled_at' => 'TEXT',
                'Private_Notes' => 'TEXT',
                'Payment_Method' => 'TEXT',
                'Payment_Reference' => 'TEXT',
            ),
            'validation' => array('Order_ID')
        );
    }
    
    public function orderidsTableConstruct(){
        return array(
            'table' => self::SS_ORD_ID,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'Order_ID' => 'TEXT',
            ),
        );
    }
    
    public function displayConfig()
    {
        $parent = parent::displayConfig();
        if ($parent['result'] != "success") {
            return $parent;
        }
        $response = array();
        $category_data = array("Root category");
        $attribute_data = array("Root attribute set");
        $languages_data = array(1 => "Default language");
        $currency_data = $order_status_data = $country_data = array();
        if ($this->_notice['config']['files']['orders']) {
            $order_table = $this->getTableName(self::SS_ORD);
            $order_status_query = "SELECT Fulfillment_Status FROM " . $order_table . " WHERE folder = '{$this->_folder}' GROUP BY Fulfillment_Status";
            $order_status = $this->readQuery($order_status_query);
            if ($order_status['result'] == 'success' && $order_status['data']) {
                foreach ($order_status['data'] as $order_status_row) {
                    if ($order_status_row['Fulfillment_Status'] != '') {
                        $order_status_id = $order_status_row['Fulfillment_Status'];
                        $order_status_name = $order_status_row['Fulfillment_Status'];
                        $order_status_data[$order_status_id] = $order_status_name;
                    }
                }
            }
            $currency_query = "SELECT DISTINCT(Currency) FROM " . $order_table . " WHERE folder = '{$this->_folder}'";
            $currency_result = $this->readQuery($currency_query);
            if ($currency_result['result'] == 'success' && $currency_result['data']) {
                foreach ($currency_result['data'] as $currency_row) {
                    if ($currency_row['Currency'] != '') {
                        $currency_id = strtolower($currency_row['Currency']);
                        $currency_name = $currency_row['Currency'];
                        $currency_data[$currency_id] = $currency_name;
                    } else {
                        $this->_notice['src']['support']['currency_map'] = false;
                    }
                }
            }
        } else {
            $this->_notice['src']['support']['order_status_map'] = false;
        }
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $languages_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['country_data'] = $country_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;       
        $response['result'] = 'success';
        return $response;
    }
    
    public function displayConfirm($params)
    {
        parent::displayConfirm($params);
        return array(
            'result' => 'success'
        );
    }
    
    public function displayImport()
    {
        $category_table = $this->getTableName(self::SS_CAT);
        $product_table = $this->getTableName(self::SS_PRO);
        $customer_table = $this->getTableName(self::SS_CUS);
        $order_table = $this->getTableName(self::SS_ORD_ID);
        $queries = array(
            'categories' => "SELECT COUNT(1) AS count FROM `" . $category_table . "` WHERE folder = '" . $this->_folder . "'",
            'products' => "SELECT COUNT(1) AS count FROM `" . $product_table . "` WHERE folder = '" . $this->_folder . "'",
            'customers' => "SELECT COUNT(1) AS count FROM `" . $customer_table . "` WHERE folder = '" . $this->_folder . "'",
            'orders' => "SELECT COUNT(1) AS count FROM `" . $order_table . "` WHERE folder = '" . $this->_folder . "'",
        );
        $data = array();
        foreach ($queries as $type => $query) {
            $read = $this->readQuery($query);
            if ($read['result'] != 'success') {
                return $this->errorDatabase();
            }
            $count = $this->arrayToCount($read['data'], 'count');
            $data[$type] = $count;
        }
    
        $data = $this->_limit($data);
        foreach ($data as $type => $count) {
            $this->_notice[$type]['total'] = $count;
        }       
        if(LitExtension_CartImport_Model_Custom::CLEAR_IMPORT){
            $del = $this->deleteTable(self::TABLE_IMPORT, array(
                'folder' => $this->_folder
            ));
            if(!$del){
                return $this->errorDatabase();
            }
        }
        return array(
            'result' => 'success'
        );
    }
    
    public function getCategories(){
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $cat_table = $this->getTableName(self::SS_CAT);
        $query = "SELECT * FROM {$cat_table} WHERE folder = '{$this->_folder}' AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }        
        return $result;
    }
    
    public function getCategoryId($category){
        return $category['id'];
    }
    
    public function convertCategory($category){
        if(LitExtension_CartImport_Model_Custom::CATEGORY_CONVERT){
            return $this->_custom->convertCategoryCustom($this, $category);
        }
        $cat_parent_id = $this->_notice['config']['root_category_id'];
        
        $cat_data = array();
        $cat_data['name'] = $category['name'];
        $cat_data['meta_title'] = $category['name'];
        $cat_data['meta_keywords'] = $category['code'];
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();        
        $cat_data['is_active'] = true;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;        
        $custom = $this->_custom->convertCategoryCustom($this, $category);
        if($custom){
            $cat_data = array_merge($cat_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $cat_data
        );
    }
    
    public function prepareImportProducts(){
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids']= $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }
    
    public function getProducts(){
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $product_table = $this->getTableName(self::SS_PRO);
        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }        
        return $result;
    }
    
    public function getProductId($product){
        return $product['product_id'];
    }
    
    public function checkProductImport($product){
        $product_code = $product['id'];
        return $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $product_code);
    }
    
    public function convertProduct($product){
        if(LitExtension_CartImport_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product);
        }
        $pro_has_child = $this->_checkProductHasChild($product);
        if($pro_has_child){
            $config_data = $this->_importChildrenProduct($product);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $pro_data = array_merge($pro_data, $config_data['data']);
        } else {
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        }
        $pro_convert = $this->_convertProduct($product);
        if($pro_convert['result'] != 'success'){
            return $pro_convert;
        }
        $pro_data = array_merge($pro_data, $pro_convert['data']);
        return array(
            'result' => "success",
            'data' => $pro_data
        );
    }
    
    public function importProduct($data, $product){
        if(LitExtension_CartImport_Model_Custom::PRODUCT_IMPORT){
            return $this->_custom->importProductCustom($this, $data, $product);
        }
        $id_src = $this->getProductId($product);
        $productIpt = $this->_process->product($data);
        if($productIpt['result'] == 'success'){
            $id_desc = $productIpt['mage_id'];
            $this->productSuccess($id_src, $id_desc, $product['id']);
        } else {
            $productIpt['result'] = 'warning';
            $msg = "Product id = {$product['id']} import failed. Error: " . $productIpt['msg'];
            $productIpt['msg'] = $this->consoleWarning($msg);
        }
        return $productIpt;
    }
    
    public function afterSaveProduct($product_mage_id, $data, $product){
        if(parent::afterSaveProduct($product_mage_id, $data, $product)){
            return ;
        }
        if($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $product['optionids']){
        }
    }
    
    public function configCurrency()
    {
        return array(
            'result' => 'success'
        );
    }
    
    protected function _checkProductHasChild($product){
        $variantAttributeNames = unserialize($product['variantAttributeNames']);
        if ($variantAttributeNames){
            return true;
        }
        return false;
    }
    
    protected function _convertProduct($product){        
        $visibility = unserialize($product['visibility']);
        $images = unserialize($product['images']);
        $variants = unserialize($product['variants']);
        $main_product = ! empty($variants) ? $variants[0] : array();
        $categories = unserialize($product['categories']);
        $pro_data = $category_ids = array();
        if ($categories){
            foreach ($categories as $category){
                $cat_src = $this->selectTable(self::SS_CAT, array(
                    'folder' => $this->_folder,
                    'name' => $category
                ));                
                $category_id_desc = $this->getIdDescCategory($cat_src['id']);
                if($category_id_desc){
                    $category_ids[] = $category_id_desc;
                }
            }
        }

        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = $product['id'];
        $pro_data['name'] = $product['name'];
        $pro_data['description'] = $this->changeImgSrcInText($product['description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['weight'] = isset($main_product['shippingWeight']) ? $main_product['shippingWeight']['value'] : '';
        $pro_data['status'] = 1;
        $pro_data['price'] = isset($main_product['price']) ? $main_product['price']['decimalValue'] : 0;
        $pro_data['tax_class_id'] = 0;
        if($main_product['onSale']){
            $pro_data['special_price'] = $main_product['salePrice']['decimalValue'];
        }
        $pro_data['create_at'] = isset($visibility['visibleOn']) ? date('Y-m-d H:i:s', strtotime($visibility['visibleOn'])) : date('Y-m-d H:i:s');
        $pro_data['visibility'] = ($visibility['state'] == 'VISIBLE') ? Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE : Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $manage_stock = true;
        if($main_product['stock']['unlimited'] || $main_product['stock']['quantity'] == ''){
            $manage_stock = false;
        }
        if($this->_notice['config']['add_option']['stock'] && $main_product['stock']['quantity'] < 1){
            $manage_stock = false;
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $manage_stock,
            'use_config_manage_stock' => $manage_stock,
            'qty' => $main_product['stock']['quantity'] ? $main_product['stock']['quantity'] : 0,
            'backorders' => 1
        );        
        if ($images) {
            foreach ($images as $key => $image) {
                $image_convert = $this->urlImage($image['url']);
                if ($image_convert) {
                    $img_path = $this->downloadImage($image_convert['domain'], $image_convert['path'], 'catalog/product', false, true);
                    if ($img_path) {
                        if ($key == 0){
                            $pro_data['image_import_path'] = array('path' => $img_path, 'label' => $image['id']);
                        } else {
                            $pro_data['image_gallery'][] = array('path' => $img_path, 'label' => $image['id']);
                        }
                    }
                }
            }
        }
        
        $custom = $this->_custom->convertProductCustom($this, $product);
        if($custom){
            $pro_data = array_merge($pro_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }
    
    public function getCustomers(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $customer_table = $this->getTableName(self::SS_CUS);
        $query = "SELECT * FROM {$customer_table} WHERE folder = '{$this->_folder}' AND customer_id > {$id_src} ORDER BY customer_id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }
    
    public function getCustomerId($customer){
        return $customer['customer_id'];
    }
    
    public function convertCustomer($customer){
        if(LitExtension_CartImport_Model_Custom::CUSTOMER_CONVERT){
            return $this->_custom->convertCustomerCustom($this, $customer);
        }
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['customer_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['Email'];
        $fname = $lname = '';
        $cus_name = explode(' ', $customer['Billing_Name']);
        foreach ($cus_name as $key => $name){
            if ($key == 0){
                $fname = $name;
            } elseif ($key > 0){
                $lname .= $name;
            }
        }
        $cus_data['firstname'] = ($fname != '') ? $fname : 'null';
        $cus_data['lastname'] = ($lname != '') ? $lname : 'null';
        $cus_data['created_at'] = $customer['Created_at'] ? date('Y-m-d H:i:s', strtotime($customer['Created_at'])) : null;
        $cus_data['is_subscribed'] = 1;
        $cus_data['group_id'] = 1;
        $custom = $this->_custom->convertCustomerCustom($this, $customer);
        if($custom){
            $cus_data = array_merge($cus_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $cus_data
        );
    }
    
    public function afterSaveCustomer($customer_mage_id, $data, $customer){
        if(parent::afterSaveCustomer($customer_mage_id, $data, $customer)){
            return ;
        }
        $address = array();
        $fname = $lname = '';
        $cus_name = explode(' ', $customer['Billing_Name']);
        foreach ($cus_name as $key => $name){
            if ($key == 0){
                $fname = $name;
            } elseif ($key > 0){
                $lname .= $name;
            }
        }
        $address['firstname'] = ($fname != '') ? $fname : 'null';
        $address['lastname'] = ($lname != '') ? $lname : 'null';
        $country_cus_code = $this->getCountryCode($customer['Billing_Country']);
        $address['country_id'] = $country_cus_code;
        $address['street'] = $customer['Billing_Address1'] . "\n" . $customer['Billing_Address1'];
        $address['postcode'] = $customer['Billing_Zip'];
        $address['city'] = $customer['Billing_City'];
        $address['telephone'] = $customer['Billing_Phone'];       
        if($customer['state']){
            $region_id = false;
            if(strlen($customer['Billing_Province']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($customer['Billing_Province'], $country_cus_code);
                if($region->getId()){
                    $region_id = $region->getId();
                }
            } else {
                $region_id = $this->getRegionId($customer['Billing_Province'], $country_cus_code);
            }
            if($region_id){
                $address['region_id'] = $region_id;
            }
            $address['region'] = $customer['Billing_Province'];
        } else {
            $address['region_id'] = 0;
        }
        $address_ipt = $this->_process->address($address, $customer_mage_id);
        if($address_ipt['result'] == 'success'){
            try{
                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                $cus->setDefaultBilling($address_ipt['mage_id']);
                $cus->setDefaultShipping($address_ipt['mage_id']);
            }catch (Exception $e){}
        }
    }
    
    public function getOrders(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $order_table = $this->getTableName(self::SS_ORD_ID);
        $query = "SELECT * FROM {$order_table} WHERE folder = '{$this->_folder}' AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }
    
    public function getOrderId($order){
        return $order['id'];
    }
    
    public function convertOrder($order){
        if(LitExtension_CartImport_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order);
        }
        
        $ordDtl = $this->selectTable(self::SS_ORD, array(
            'folder' => $this->_folder,
            'Order_ID' => $order['Order_ID']
        ));
        $ord_dtl = $ordDtl[0];
        
        $cus_table = $this->getTableName(self::SS_CUS);
        $customers = $this->readQuery("SELECT * FROM {$cus_table} WHERE folder = '{$this->_folder}' AND Email = '{$ord_dtl['Email']}' ");
        if($customers['result'] != 'success' || empty($customers['data'])){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Order id = {$order['Order_ID']} import failed. Error: Customer not import!")
            );
        }        
        $customer = $customers['data'][0];
            
        $data = $address_billing = $address_shipping = $carts = array();
        
        $country_bill_code = $this->getCountryCode($ord_dtl['Billing_Country']);
        $fname = $lname = '';
        $cus_name = explode(' ', $ord_dtl['Billing_Name']);
        foreach ($cus_name as $key => $name){
            if ($key == 0){
                $fname = $name;
            } elseif ($key > 0){
                $lname .= $name;
            }
        }
        $address_billing['firstname'] = ($fname != '') ? $fname : 'null';
        $address_billing['lastname'] = ($lname != '') ? $lname : 'null';
        $address_billing['email'] = $ord_dtl['Email'];
        $address_billing['country_id'] = $country_bill_code;
        $address_billing['street'] = $ord_dtl['Billing_Address1'] . "\n" . $ord_dtl['Billing_Address2'];
        $address_billing['postcode'] = $ord_dtl['Billing_Zip'];
        $address_billing['city'] = $ord_dtl['Billing_City'];
        $address_billing['telephone'] = $ord_dtl['Billing_Phone'];
        if($ord_dtl['Billing_Province']){
            $bil_region_id = false;
            if(strlen($ord_dtl['Billing_Province']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($ord_dtl['Billing_Province'], $country_bill_code);
                if($region->getId()){
                    $bil_region_id = $region->getId();
                }
            } else {
                $bil_region_id = $this->getRegionId($ord_dtl['Billing_Province'], $country_bill_code);
            }
            if($bil_region_id){
                $address_billing['region_id'] = $bil_region_id;
            }
            $address_billing['region'] = $ord_dtl['Billing_Province'];
        } else {
            $address_billing['region_id'] = 0;
        }
        
        $country_ship_code = $this->getCountryCode($ord_dtl['Shipping_Country']);
        $fname = $lname = '';
        $cus_name = explode(' ', $ord_dtl['Shipping_Name']);
        foreach ($cus_name as $key => $name){
            if ($key == 0){
                $fname = $name;
            } elseif ($key > 0){
                $lname .= $name;
            }
        }
        $address_shipping['firstname'] = ($fname != '') ? $fname : 'null';
        $address_shipping['lastname'] = ($lname != '') ? $lname : 'null';
        $address_shipping['email'] = $ord_dtl['Email'];
        $address_shipping['country_id'] = $country_ship_code;
        $address_shipping['street'] = $ord_dtl['Shipping_Address1'] . "\n" . $ord_dtl['Shipping_Address2'];
        $address_shipping['postcode'] = $ord_dtl['Shipping_Zip'];
        $address_shipping['city'] = $ord_dtl['Shipping_City'];
        $address_shipping['telephone'] = $ord_dtl['Billing_Phone'];
        if($ord_dtl['Shipping_Province']){
            $ship_region_id = false;
            if(strlen($ord_dtl['Shipping_Province']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($ord_dtl['Shipping_Province'], $country_ship_code);
                if($region->getId()){
                    $ship_region_id = $region->getId();
                }
            } else {
                $ship_region_id = $this->getRegionId($ord_dtl['Shipping_Province'], $country_ship_code);
            }
            if($ship_region_id){
                $address_shipping['region_id'] = $ship_region_id;
            }
            $address_shipping['region'] = $ord_dtl['Shipping_Province'];
        } else {
            $address_shipping['region_id'] = 0;
        }
        $discount = array();
        if($ordDtl){
            foreach($ordDtl as $order_detail){
                if($order_detail['Discount_Code']){
                    $discount = $order_detail;
                    continue ;
                }
                $cart = array();
                $prd_src = $this->selectTable(self::SS_PRO, array(
                    'folder' => $this->_folder,
                    'name' => $order_detail['Lineitem_name']
                ));
                $prd_id_src = $prd_src[0]['id'];
                $product_id = $this->getIdDescProduct($prd_id_src);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_detail['Lineitem_name'];
                $cart['sku'] = $order_detail['Lineitem_sku'];
                $cart['price'] = $order_detail['Lineitem_price'];
                $cart['original_price'] = $order_detail['Lineitem_price'];
                $cart['qty_ordered'] = $order_detail['Lineitem_quantity'];
                $cart['row_total'] = $order_detail['Lineitem_price'] * $order_detail['Lineitem_quantity'];
                if($order_detail['Lineitem_variant']){
                    $product_opt = array();                    
                    $options = explode('/', $order_detail['Lineitem_variant']);
                    foreach($options as $key => $option){                        
                        $opt_data = array(
                            'label' => $option ? $$option : " ",
                            'value' => $option ? $option : " ",
                            'print_value' => $option ? $option : " ",
                            'option_id' => 'option_' . $key,
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $product_opt[] = $opt_data;
                    }
                    $cart['product_options'] = serialize(array('options' => $product_opt));
                }
                $carts[]= $cart;
            }
        }
    
        $customer_id = $this->getIdDescCustomer($customer['id']);        
        $tax_amount = $ord_dtl['Taxes'];
        $discount_amount = $discount ? abs($ord_dtl['Discount_Amount']) : 0;
        $store_id = $this->_notice['config']['languages'][1];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $ship_amount = $ord_dtl['Shipping'];
        $sub_total = $ord_dtl['Subtotal'];
    
        $order_data = array();
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$ord_dtl['Fulfillment_Status']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $ord_dtl['Email'];
        $fname = $lname = '';
        $cus_name = explode(' ', $ord_dtl['Billing_Name']);
        foreach ($cus_name as $key => $name){
            if ($key == 0){
                $fname = $name;
            } elseif ($key > 0){
                $lname .= $name;
            }
        }
        $order_data['customer_firstname'] = ($fname != '') ? $fname : 'null'; 
        $order_data['customer_lastname'] = ($lname != '') ? $lname : 'null';
        $order_data['customer_group_id'] = 1;        
        $order_data['subtotal'] = $this->incrementPriceToImport($sub_total);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $ship_amount;
        $order_data['base_shipping_amount'] = $ship_amount;
        $order_data['base_shipping_invoiced'] = $ship_amount;
        $order_data['shipping_description'] = "Shipping";
        $order_data['tax_amount'] = $tax_amount;
        $order_data['base_tax_amount'] = $tax_amount;
        $order_data['discount_amount'] = $discount_amount;
        $order_data['base_discount_amount'] = $discount_amount;
        $order_data['grand_total'] = $this->incrementPriceToImport($ord_dtl['Total']);
        $order_data['base_grand_total'] = $order_data['grand_total'];
        $order_data['base_total_invoiced'] = $order_data['grand_total'];
        $order_data['total_paid'] = $order_data['grand_total'];
        $order_data['base_total_paid'] = $order_data['grand_total'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $store_currency['base'];
        $order_data['global_currency_code'] = $store_currency['base'];
        $order_data['store_currency_code'] = $store_currency['base'];
        $order_data['order_currency_code'] = $store_currency['base'];
        $order_data['created_at'] = date('Y-m-d H:i:s', strtotime($ord_dtl['Created_at']));
    
        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $ord_dtl['Order_ID'];
        $custom = $this->_custom->convertOrderCustom($this, $order);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }
    
    /**
     * Process after one order save successful
     *
     * @param int $order_mage_id : Id of order import to magento
     * @param array $data : Data of function convertOrder
     * @param array $order : One row of object in function getOrders
     * @return boolean
     */
    public function afterSaveOrder($order_mage_id, $data, $order){
        if(parent::afterSaveOrder($order_mage_id, $data, $order)){
            return ;
        }
        $ordDtl = $this->selectTable(self::SS_ORD, array(
            'folder' => $order['folder'],
            'Order_ID' => $order['Order_ID']
        ));
        $ord_dtl = $ordDtl[0];
        $order_status_data = array();
        if ($this->_notice['config']['order_status']) {
            $order_status_data['status'] = $this->_notice['config']['order_status'][$ord_dtl['Fulfillment_Status']];
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }        
        $order_status_data['comment'] = "<b>Reference order #".$ord_dtl['Order_ID']."</b><br />";
        $order_status_data['comment'] .= "<b>Payment method Id: </b>".$ord_dtl['Payment_Method']."<br />";
        $order_status_data['comment'] .= "<b>Payment reference: </b> ".$ord_dtl['Payment_Reference']."<br />";
        $order_status_data['comment'] .= "<b>Shipping method Id: </b> ".$ord_dtl['Shipping_Method']."<br />";
        if ($ord_dtl['Private_Notes']){
            $order_status_data['comment'] .= "<b>Order Notes: </b>".$ord_dtl['Private_Notes'];
        }
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date('Y-m-d H:i:s', strtotime($ord_dtl['Paid_at']));
        $order_status_data['created_at'] = date('Y-m-d H:i:s', strtotime($ord_dtl['Created_at']));
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }
    
    protected function _convertProductChildren($product){
        $child_data = array();
        $child_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $child_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $child_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $child_data['sku'] = $product['sku'];
        $child_data['name'] = $product['sku'];
        $child_data['price'] = isset($product['price']) ? $product['price']['decimalValue'] : 0;
        $child_data['weight'] = isset($product['shippingWeight']) ? $product['shippingWeight']['value'] : '';
        $child_data['status'] = 1;
        $child_data['created_at'] = date('Y-m-d H:i:s');
        $child_data['updated_at'] = date('Y-m-d H:i:s');
        if($product['onSale']){
            $child_data['special_price'] = $product['salePrice']['decimalValue'];
        }
        $qty = 0;
        if ($product['stock']){
            if ($product['unlimited'] != ''){
                $manage_stock = 0;
            } else {
                $manage_stock = 1;
                $qty = $product['stock']['quantity'];
            }
            $child_data['stock_data'] = array(
                'is_in_stock' => 1,
                'manage_stock' => $manage_stock,
                'use_config_manage_stock' => 0,
                'qty' => $qty,
            );
        }
        $custom = $this->_custom->convertProductCustom($this, $product);
        if ($custom) {
            $child_data = array_merge($child_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $child_data
        );
    }
    
    protected function _importChildrenProduct($parent){        
        $data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attrIpt = $this->_importAttribute($parent);
        if ($attrIpt['result'] != 'success') {
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Product ID = {$parent['id']} import failed. Error: Product attribute could not be created!")
            );
        }
        if ($attrIpt['type'] == 'change') {
            return array(
                'result' => "success",
                'data' => array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
                )
            );
        }
        $configurable_products_data = $configurable_attributes_data = array();
        $variants = unserialize($parent['variants']);
        foreach ($variants as $key => $pro_child){
            $pro_child_ipt_id = $this->getIdDescByValue($pro_child['sku'], self::TYPE_PRODUCT);
            if ($pro_child_ipt_id) {
                // do nothing
            } else {
                $pro_child_data = array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                    'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                );
                $pro_child_convert = $this->_convertProductChildren($pro_child);
                if ($pro_child_convert['result'] != 'success') {
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product code = {$pro_child['sku']} import failed. Error: Product children could not create!(Error code: Product children data not found.)")
                    );
                }
                $pro_child_data = array_merge($pro_child_convert['data'], $pro_child_data);
                $pro_child_ipt = $this->_process->product($pro_child_data);
                if ($pro_child_ipt['result'] != 'success') {
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product code = {$pro_child['sku']} import failed. Error: Product children could not create!(Error code: " . $pro_child_ipt['msg'] . ". )")
                    );
                }
                $this->productSuccess('', $pro_child_ipt['mage_id'], $pro_child['sku']);
                $pro_child_ipt_id = $pro_child_ipt['mage_id'];
                $pro_attr_data = array();
                $key = 'option_' . $parent['id'];
                if (isset($attrIpt['data'][$key])) {
                    $pro_child_attr = $attrIpt['data'][$key];                    
                    $key_id = 'option_'.$pro_child['sku'];
                    $opt_id = $pro_child_attr['data']['option_ids'][$key_id];
                    $attr_id = $pro_child_attr['data']['attribute_id'];
                    $this->setProAttrSelect($entity_type_id, $attr_id, $pro_child_ipt_id, $opt_id);
                    $pro_attr_data = array(
                        'label' => isset($pro_child_attr['opt_label'][$key_id]) ? $pro_child_attr['opt_label'][$key_id] : " ",
                        'attribute_id' => $attr_id,
                        'value_index' => $opt_id
                    );
                }                
                $configurable_products_data[$pro_child_ipt_id][] = $pro_attr_data;
            }
        }
        foreach($attrIpt['data'] as $key => $attr_ipt){
            $attr_data = array(
                'label' => $attr_ipt['label'],
                'use_default' => 1,
                'attribute_id' => $attr_ipt['data']['attribute_id'],
                'attribute_code' => $attr_ipt['data']['attribute_code'],
                'frontend_label' => $attr_ipt['label'],
                'store_label' => $attr_ipt['label'],
                'html_id' => 'configurable__attribute_' . $key,
            );
            $option_ids = $attr_ipt['data']['option_ids'];
            $values = array();
            foreach ($option_ids as $opt_key => $opt_value) {                
                $value = array(
                    'label' => isset($attr_ipt['opt_label'][$opt_key]) ? $attr_ipt['opt_label'][$opt_key] : " ",
                    'attribute_id' => $attr_ipt['data']['attribute_id'],
                    'value_index' => $opt_value,
                    'is_percent' => 0,
                    'pricing_value' => ''
                );
                $values[] = $value;
            }
            $attr_data['values'] = $values;
            $configurable_attributes_data[] = $attr_data;
        }    
        $data = array(
            'configurable_products_data' => $configurable_products_data,
            'configurable_attributes_data' => $configurable_attributes_data,
            'can_save_configurable_attributes' => true,
        );
        return array(
            'result' => "success",
            'data' => $data
        );
    }
    
    protected function _importAttribute($product){
        $data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attributes = unserialize($product['variantAttributeNames']);
        $variants = unserialize($product['variants']);
        if ($attributes){            
            foreach ($attributes as $key => $attr_name){
                $attr_data = array (
                    'entity_type_id' => $entity_type_id,
                    'attribute_code' => strtolower(str_replace(' ', '_', $attr_name)),
                    'attribute_set_id' => $this->_notice ['config'] ['attribute_set_id'],
                    'frontend_input' => 'select',
                    'frontend_label' => array ( $attr_name ),
                    'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                    'is_configurable' => true,
                    'apply_to' => array ()
                );
                $attr_save ['label'] = $attr_name;
                $key_id = 'option_'.$product['id'];
                $values = $opt_label = array ();              
                foreach ($variants as $variant){
                    if ($variant['attributes']){
                        foreach ($variant['attributes'] as $attr_name_variant => $attr_value){
                            if (strtolower(str_replace(' ', '_', $attr_name_variant)) == strtolower(str_replace(' ', '_', $attr_name))){
                                $key = 'option_'.$variant['sku'];
                                $values [$key] = array (
                                    0 => $attr_value
                                );
                                $opt_label [$key] = $attr_value;
                            }
                        }
                    }
                }                
                $attr_data ['option'] ['value'] = $values;
                $attr_save ['opt_label'] = $opt_label;
                $attr_ipt = $this->_process->attribute ( $attr_data, array (
                    'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                    'is_configurable' => true,
                    'apply_to' => array ()
                ) );
                if (! $attr_ipt) {
                    return array (
                        'result' => "warning",
                        'msg' => ""
                    );
                }
                $attr_save ['data'] = $attr_ipt;
                $data [$key_id] = $attr_save;
            }
        }
        if(!$data){
            return array(
                'result' => 'success',
                'type' => 'change',
                'data' => array()
            );
        }
        return array(
            'result' => 'success',
            'type' => '',
            'data' => $data
        );
    }
    
    protected function _getLeCaIpImportIdDescByValue($type, $value){
        $result = $this->selectTableRow(self::TABLE_IMPORT, array(
            'folder' => $this->_folder,
            'type' => $type,
            'value' => $value
        ));
        if(!$result){
            return false;
        }
        return (isset($result['id_desc'])) ? $result['id_desc'] : false;
    }
    
    public function storageCsvByType($type, $next, $success = false, $finish = false, $unset = array())
    {
        if(!$success){
            $success = $next;
        }
        if(!$this->_notice['config']['files'][$type]){
            if($finish){
                $this->_notice['csv_import']['result'] = 'success';
            } else {
                $this->_notice['csv_import']['result'] = 'process';
            }
            $this->_notice['csv_import']['function'] = 'storageCsv' . ucfirst($next);
            $this->_notice['csv_import']['msg'] = '';
            $this->_notice['csv_import']['count'] = 0;
            return $this->_notice['csv_import'];
        }
        $start = $this->_notice['csv_import']['count'];
        $demo = $this->_limitDemoModel($type);
        if ($type == 'orders'){
            $csv_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
            $readCsv = $this->readCsv($csv_file, $start, $this->_notice['setting']['csv'], $demo);
            if($readCsv['result'] != 'success'){
                $readCsv['msg'] = $this->consoleMsgError($readCsv['msg']);
                return $readCsv;
            }
            $allowData = array();
            $fn_construct = $type . 'TableConstruct';
            $table = $this->$fn_construct();            
            $validation = false;
            if(!$allowData){
                $rows = $table['rows'];
                $validation = isset($table['validation']) ? $table['validation'] : false;
                if($unset){
                    $rows = $this->unsetListArray($unset, $rows);
                }
                $allowData = array_keys($rows);
                $custom_allow = $this->_custom->storageCsvCustom($this);
                if($custom_allow){
                    $allowData = array_merge($allowData, $custom_allow);
                }
            }
            foreach($readCsv['data'] as $item){
                $data = $this->syncCsvTitleRow($item['title'], $item['row']);
                if(!empty($data)){
                    if($validation){
                        foreach($validation as $column_name){
                            if(!isset($data[$column_name]) || !$data[$column_name]){
                                continue 2;
                            }
                        }
                    }
                    $data = $this->addConfigToArray($data);
                    $insert = $this->insertTable($table['table'], $data, $allowData);
                    if(!$insert){
                        return array(
                            'result' => 'error',
                            'msg' => $this->consoleMsgError('Could not import data to database.')
                        );
                    }
                    $check_cus = $this->selectTable(self::SS_CUS, array(
                        'folder' => $this->_folder,
                        'Email' => $data['Email']
                    ));
                    if (empty($check_cus)) {
                        $data_customer = array(
                            'folder' => $this->_folder,
                            'Email' => $data['Email'],
                            'Created_at' => $data['Created_at'],
                            'Order_ID' => $data['Order_ID'],
                            'Billing_Name' => $data['Billing_Name'],
                            'Billing_Address1' => $data['Billing_Address1'],
                            'Billing_Address2' => $data['Billing_Address2'],
                            'Billing_City' => $data['Billing_City'],
                            'Billing_Zip' => $data['Billing_Zip'],
                            'Billing_Province' => $data['Billing_Province'],
                            'Billing_Country' => $data['Billing_Country'],
                            'Billing_Phone' => $data['Billing_Phone'],
                            'Shipping_Name' => $data['Shipping_Name'],
                            'Shipping_Address2' => $data['Shipping_Address2'],
                            'Shipping_Address2' => $data['Shipping_Address2'],
                            'Shipping_City' => $data['Shipping_City'],
                            'Shipping_Zip' => $data['Shipping_Zip'],
                            'Shipping_Province' => $data['Shipping_Province'],
                            'Shipping_Country' => $data['Shipping_Country']
                        );
                        $this->insertTable(self::SS_CUS, $data_customer);
                    }
                    $check_ord_id = $this->selectTable(self::SS_ORD_ID, array(
                        'folder' => $this->_folder,
                        'Order_ID' => $data['Order_ID']
                    ));
                    if (empty($check_ord_id)){
                        $this->insertTable(self::SS_ORD_ID, array(
                            'folder' => $this->_folder,
                            'Order_ID' => $data['Order_ID']
                        ));
                    }
                }
            }
            if($readCsv['finish']){
                if($finish){
                    $this->_notice['csv_import']['result'] = 'success';
                } else {
                    $this->_notice['csv_import']['result'] = 'process';
                }
                $this->_notice['csv_import']['function'] = 'storageCsv' . ucfirst($success);
                $this->_notice['csv_import']['msg'] = $this->consoleSuccess("Finish import " . $type);
                $this->_notice['csv_import']['count'] = 0;
                return $this->_notice['csv_import'];
            }
            $this->_notice['csv_import']['result'] = 'process';
            $this->_notice['csv_import']['function'] = 'storageCsv' . ucfirst($type);
            $this->_notice['csv_import']['msg'] = '';
            $this->_notice['csv_import']['count'] = $readCsv['count'];
        } else {
            $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.txt';
            $readCsv = $this->readFile($_file, $start, $this->_notice['setting']['csv'], $demo);
            if ($readCsv['result'] != 'success') {
                $readCsv['msg'] = $this->consoleError($readCsv['msg']);
                return $readCsv;
            }
            $allowData = array();
            $fn_construct = $type . 'TableConstruct';
            $table = $this->$fn_construct();            
            $validation = false;
            if (! $allowData) {
                $rows = $table['rows'];
                $validation = isset($table['validation']) ? $table['validation'] : false;
                if ($unset) {
                    $rows = $this->unsetListArray($unset, $rows);
                }
                $allowData = array_keys($rows);
            }
            foreach ($readCsv['data'] as $item) {
                $data = $this->syncCsvTitleRow($item['title'], $item['row']);
                foreach ($data as $key => $value) {
                    if ($value == '') {
                        $data[$key] = NULL;
                    }
                }
                if (! empty($data)) {
                    $data = $this->addConfigToArray($data);
                    $insert = $this->insertTable($table['table'], $data, $allowData);
                    if(!$insert){
                        return array(
                            'result' => 'error',
                            'msg' => $this->consoleMsgError('Could not import data to database.')
                        );
                    }
                    if (!empty($data['categories'])){
                        $data_insert = array();
                        $categories = unserialize($data['categories']);
                        if ($categories) {
                            foreach ($categories as $category) {
                                $check_cat = $this->selectTable(self::SS_CAT, array(
                                    'folder' => $this->_folder,
                                    'name' => $category
                                ));
                                $data_insert = $this->addConfigToArray($data_insert);
                                $data_insert['name'] = $category;
                                $data_insert['code'] = strtolower($category);
                                if (empty($check_cat)) {
                                    $this->insertTable(self::SS_CAT, $data_insert);
                                }
                            }
                        }
                    }
                }
            }
            if ($readCsv['finish']){
                if($finish){
                    $this->_notice['csv_import']['result'] = 'success';
                } else {
                    $this->_notice['csv_import']['result'] = 'process';
                }
                $this->_notice['csv_import']['function'] = 'storageCsv' . ucfirst($success);
                $this->_notice['csv_import']['msg'] = $this->consoleSuccess("Finish import " . $type);
                $this->_notice['csv_import']['count'] = 0;
                return $this->_notice['csv_import'];
            }
            $this->_notice['csv_import']['result'] = 'process';
            $this->_notice['csv_import']['function'] = 'storageCsv' . ucfirst($type);
            $this->_notice['csv_import']['msg'] = '';
            $this->_notice['csv_import']['count'] = $readCsv['count'];
        }
        return $this->_notice['csv_import'];
    }
    
    protected function readFile($file_path, $start, $limit = 10, $total = false)
    {
        if (!is_file($file_path)) {
            return array(
                'result' => 'error',
                'msg' => 'Path not exists'
            );
        }
        try {
            $finish = false;
            $count = 0;
            $csv = fopen($file_path, 'r');
            $end = $start + $limit;
            $csv_title = "";
            $data = $readFile = array();
            while (!feof($csv)) {
                if ($total && $count > $total) {
                    $finish = true;
                    break;
                }
                if ($count > $end) {
                    break;
                }
                $line = fgetcsv($csv, 0, "\t");
                if ($count == 0) {
                    $csv_title = $line;
                }
                $readFile[] = array(
                    'title' => $csv_title,
                    'row' => $line
                );
                $count++;
            }
            fclose($csv);
            if (!$finish && ($count - 1) < $end) {
                $finish = true;
            }
            $products = json_decode($readFile[0]['row'][0], true);
            $i = 0;
            foreach ($products['results'] as $products) {
                $title = $row = array();
                foreach ($products as $key => $value) {
                    $title[$i] = $key;
                    $row[$i] = is_array($value) ? serialize($value) : $value;
                    $i++;
                }
                $data[] = array(
                    'title' => $title,
                    'row' => $row
                );
            }
            return array(
                'result' => 'success',
                'data' => $data,
                'count' => count($data),
                'finish' => $finish
            );
        } catch (Exception $e) {
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }       
    
    protected function urlImage($url)
    {
        $img_domain = 'https://static1.squarespace.com/static/';
        $img_path = str_replace($img_domain, '', $url);
        return array(
            'domain' => rtrim($img_domain, '/'),
            'path' => ltrim($img_path, '/')
        );
    }
    
    protected function _getTablesTmp()
    {
        return array(
            self::SS_CAT,
            self::SS_CUS,
            self::SS_ORD,
            self::SS_ORD_ID,
            self::SS_PRO
        );
    }
    
    public function getCountryCode($src) {
        $COUNTRY = array(
            'AF' => "Afghanistan",
            'AX' => "Aland Islands",
            'AL' => "Albania",
            'DZ' => "Algeria",
            'AS' => "American Samoa",
            'AD' => "Andorra",
            'AO' => "Angola",
            'AI' => "Anguilla",
            'AQ' => "Antarctica",
            'AG' => "Antigua and Barbuda",
            'AR' => "Argentina",
            'AM' => "Armenia",
            'AW' => "Aruba",
            'AP' => "Asia/Pacific Region",
            'AU' => "Australia",
            'AT' => "Austria",
            'AZ' => "Azerbaijan",
            'BS' => "Bahamas",
            'BH' => "Bahrain",
            'BD' => "Bangladesh",
            'BB' => "Barbados",
            'BY' => "Belarus",
            'BE' => "Belgium",
            'BZ' => "Belize",
            'BJ' => "Benin",
            'BM' => "Bermuda",
            'BT' => "Bhutan",
            'BO' => "Bolivia",
            'BQ' => "Bonaire, Saint Eustatius and Saba",
            'BA' => "Bosnia and Herzegovina",
            'BW' => "Botswana",
            'BR' => "Brazil",
            'IO' => "British Indian Ocean Territory",
            'BN' => "Brunei Darussalam",
            'BG' => "Bulgaria",
            'BF' => "Burkina Faso",
            'BI' => "Burundi",
            'KH' => "Cambodia",
            'CM' => "Cameroon",
            'CA' => "Canada",
            'CV' => "Cape Verde",
            'KY' => "Cayman Islands",
            'CF' => "Central African Republic",
            'TD' => "Chad",
            'CL' => "Chile",
            'CN' => "China",
            'CX' => "Christmas Island",
            'CC' => "Cocos (Keeling) Islands",
            'CO' => "Colombia",
            'KM' => "Comoros",
            'CG' => "Congo",
            'CD' => "Congo, The Democratic Republic of the",
            'CK' => "Cook Islands",
            'CR' => "Costa Rica",
            'CI' => "Cote D'Ivoire",
            'HR' => "Croatia",
            'CU' => "Cuba",
            'CW' => "Curacao",
            'CY' => "Cyprus",
            'CZ' => "Czech Republic",
            'DK' => "Denmark",
            'DJ' => "Djibouti",
            'DM' => "Dominica",
            'DO' => "Dominican Republic",
            'EC' => "Ecuador",
            'EG' => "Egypt",
            'SV' => "El Salvador",
            'GQ' => "Equatorial Guinea",
            'ER' => "Eritrea",
            'EE' => "Estonia",
            'ET' => "Ethiopia",
            'EU' => "Europe",
            'FK' => "Falkland Islands (Malvinas)",
            'FO' => "Faroe Islands",
            'FJ' => "Fiji",
            'FI' => "Finland",
            'FR' => "France",
            'GF' => "French Guiana",
            'PF' => "French Polynesia",
            'TF' => "French Southern Territories",
            'GA' => "Gabon",
            'GM' => "Gambia",
            'GE' => "Georgia",
            'DE' => "Germany",
            'GH' => "Ghana",
            'GI' => "Gibraltar",
            'GR' => "Greece",
            'GL' => "Greenland",
            'GD' => "Grenada",
            'GP' => "Guadeloupe",
            'GU' => "Guam",
            'GT' => "Guatemala",
            'GG' => "Guernsey",
            'GN' => "Guinea",
            'GW' => "Guinea-Bissau",
            'GY' => "Guyana",
            'HT' => "Haiti",
            'VA' => "Holy See (Vatican City State)",
            'HN' => "Honduras",
            'HK' => "Hong Kong",
            'HU' => "Hungary",
            'IS' => "Iceland",
            'IN' => "India",
            'ID' => "Indonesia",
            'IR' => "Iran, Islamic Republic of",
            'IQ' => "Iraq",
            'IE' => "Ireland",
            'IM' => "Isle of Man",
            'IL' => "Israel",
            'IT' => "Italy",
            'JM' => "Jamaica",
            'JP' => "Japan",
            'JE' => "Jersey",
            'JO' => "Jordan",
            'KZ' => "Kazakhstan",
            'KE' => "Kenya",
            'KI' => "Kiribati",
            'KP' => "Korea, Democratic People's Republic of",
            'KR' => "Korea, Republic of",
            'KW' => "Kuwait",
            'KG' => "Kyrgyzstan",
            'LA' => "Lao People's Democratic Republic",
            'LV' => "Latvia",
            'LB' => "Lebanon",
            'LS' => "Lesotho",
            'LR' => "Liberia",
            'LY' => "Libya",
            'LI' => "Liechtenstein",
            'LT' => "Lithuania",
            'LU' => "Luxembourg",
            'MO' => "Macau",
            'MK' => "Macedonia",
            'MG' => "Madagascar",
            'MW' => "Malawi",
            'MY' => "Malaysia",
            'MV' => "Maldives",
            'ML' => "Mali",
            'MT' => "Malta",
            'MH' => "Marshall Islands",
            'MQ' => "Martinique",
            'MR' => "Mauritania",
            'MU' => "Mauritius",
            'YT' => "Mayotte",
            'MX' => "Mexico",
            'FM' => "Micronesia, Federated States of",
            'MD' => "Moldova, Republic of",
            'MC' => "Monaco",
            'MN' => "Mongolia",
            'ME' => "Montenegro",
            'MS' => "Montserrat",
            'MA' => "Morocco",
            'MZ' => "Mozambique",
            'MM' => "Myanmar",
            'NA' => "Namibia",
            'NR' => "Nauru",
            'NP' => "Nepal",
            'NL' => "Netherlands",
            'NC' => "New Caledonia",
            'NZ' => "New Zealand",
            'NI' => "Nicaragua",
            'NE' => "Niger",
            'NG' => "Nigeria",
            'NU' => "Niue",
            'NF' => "Norfolk Island",
            'MP' => "Northern Mariana Islands",
            'NO' => "Norway",
            'OM' => "Oman",
            'PK' => "Pakistan",
            'PW' => "Palau",
            'PS' => "Palestinian Territory",
            'PA' => "Panama",
            'PG' => "Papua New Guinea",
            'PY' => "Paraguay",
            'PE' => "Peru",
            'PH' => "Philippines",
            'PN' => "Pitcairn Islands",
            'PL' => "Poland",
            'PT' => "Portugal",
            'PR' => "Puerto Rico",
            'QA' => "Qatar",
            'RE' => "Reunion",
            'RO' => "Romania",
            'RU' => "Russian Federation",
            'RW' => "Rwanda",
            'BL' => "Saint Barthelemy",
            'SH' => "Saint Helena",
            'KN' => "Saint Kitts and Nevis",
            'LC' => "Saint Lucia",
            'MF' => "Saint Martin",
            'PM' => "Saint Pierre and Miquelon",
            'VC' => "Saint Vincent and the Grenadines",
            'WS' => "Samoa",
            'SM' => "San Marino",
            'ST' => "Sao Tome and Principe",
            'SA' => "Saudi Arabia",
            'SN' => "Senegal",
            'RS' => "Serbia",
            'SC' => "Seychelles",
            'SL' => "Sierra Leone",
            'SG' => "Singapore",
            'SX' => "Sint Maarten (Dutch part)",
            'SK' => "Slovakia",
            'SI' => "Slovenia",
            'SB' => "Solomon Islands",
            'SO' => "Somalia",
            'ZA' => "South Africa",
            'GS' => "South Georgia and the South Sandwich Islands",
            'SS' => "South Sudan",
            'ES' => "Spain",
            'LK' => "Sri Lanka",
            'SD' => "Sudan",
            'SR' => "Suriname",
            'SJ' => "Svalbard and Jan Mayen",
            'SZ' => "Swaziland",
            'SE' => "Sweden",
            'CH' => "Switzerland",
            'SY' => "Syrian Arab Republic",
            'TW' => "Taiwan",
            'TJ' => "Tajikistan",
            'TZ' => "Tanzania, United Republic of",
            'TH' => "Thailand",
            'TL' => "Timor-Leste",
            'TG' => "Togo",
            'TK' => "Tokelau",
            'TO' => "Tonga",
            'TT' => "Trinidad and Tobago",
            'TN' => "Tunisia",
            'TR' => "Turkey",
            'TM' => "Turkmenistan",
            'TC' => "Turks and Caicos Islands",
            'TV' => "Tuvalu",
            'UG' => "Uganda",
            'UA' => "Ukraine",
            'AE' => "United Arab Emirates",
            'GB' => "United Kingdom",
            'US' => "United States",
            'UM' => "United States Minor Outlying Islands",
            'UY' => "Uruguay",
            'UZ' => "Uzbekistan",
            'VU' => "Vanuatu",
            'VE' => "Venezuela",
            'VN' => "Vietnam",
            'VG' => "Virgin Islands, British",
            'VI' => "Virgin Islands, U.S.",
            'WF' => "Wallis and Futuna",
            'YE' => "Yemen",
            'ZM' => "Zambia",
            'ZW' => "Zimbabwe"
        );
        $src = str_replace(' ', '', $src);
        foreach ($COUNTRY AS $code => $country) {
            $country_def = str_replace(' ', '', $country);
            $country_code = (strtolower($src) == strtolower($country_def)) ? $code : 'US';
        }
        return $country_code;
    }
}