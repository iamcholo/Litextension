<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Cart_Amazonstore
    extends LitExtension_CartImport_Model_Cart
{
    const AMZ_PRO = 'lecaip_amazon_product';
    const AMZ_ORD = 'lecaip_amazon_order';

    protected $_demo_limit = array(
        'exchangeRates' => 100,
        'taxes' => 100,
        'manufacturers' => 100,
        'categories' => 100,
        'products' => 100,
        'optionCategories' => 100,
        'options' => 100,
        'kits' => 100,
        'kitLinks' => 100,
        'customers' => 100,
        'orders' => 100,
        'orderDetails' => 100,
        'reviews' => 0
    );

    public function __construct()
    {
        parent::__construct();
    }

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
            self::AMZ_PRO,
            self::AMZ_ORD,
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
            $name = $upload_name . '.txt';
        } else {
            $name = $upload_name . '.csv';
        }
        return $name;
    }

    public function getUploadInfo($up_msg)
    {
        $files = array_filter($this->_notice['config']['files']);
        if (!empty($files)) {
            $this->_notice['config']['config_support']['currency_map'] = false;
            $this->_notice['config']['config_support']['country_map'] = false;
            $this->_notice['config']['config_support']['order_status_map'] = false;
            if (!$this->_notice['config']['files']['products']) {
                $this->_notice['config']['import_support']['products'] = false;
            }
            if (!$this->_notice['config']['files']['orders']) {
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
            }
            foreach ($files as $type => $upload) {
                if ($upload) {
                    $func_construct = $type . "TableConstruct";
                    $construct = $this->$func_construct();
                    $validate = isset($construct['validation']) ? $construct['validation'] : false;
                    if ($type != 'orders') {
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
                    else {
                        $xml_file = Mage::getBaseDir('media'). self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.txt';
                        $readTxt = $this->readTxt($xml_file, 0 , 1, false);
                        if ($readTxt['result'] == 'success') {
                            foreach ($readTxt['data'] as $item) {
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
        $merchantID = '';
        $html = file_get_contents($this->_cart_url);
        $id_pos = strpos($html, 'merchantId');
        if ($id_pos) {
            $str_replace = substr($html, $id_pos + 10, 15);
            $merchantID = str_replace('"', '', $str_replace);
            $merchantID = str_replace(',', '', $merchantID);
        }
        if ($merchantID != ''){
            $this->_notice['config']['merchantID'] = $merchantID;
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
            'productsTableConstruct',
            'ordersTableConstruct',
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
        $this->_notice['csv_import']['function'] = '_storageCsvProducts';
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

    public function _storageCsvProducts()
    {
        return $this->storageCsvByType('products', 'orders', false, false, array('id'));
    }

    public function _storageCsvOrders()
    {
        return $this->storageCsvByType('orders', 'orders', false, true);
    }

    public function productsTableConstruct()
    {
        return array(
            'table' => self::AMZ_PRO,
            'rows' => array(
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'sku'    => 'VARCHAR(255)',
                'item_name' => 'TEXT',
                'our_price' => 'TEXT',
                'product_type' => 'VARCHAR(255)',
                'product_description' => 'TEXT',
                'color_name' => 'TEXT',
                'discounted_price' => 'TEXT',
                'discounted_price_end_date' => 'TEXT',
                'discounted_price_start_date' => 'TEXT',
                'item_classification' => 'TEXT',
                'item_weight' => 'TEXT',
                'item_weight_UOM' => 'VARCHAR(255)',
                'item_type_keyword' => 'TEXT',
                'offer_inventory_leadtime' => 'TEXT',
                'offer_inventory_quantity' => 'TEXT',
                'model' => 'TEXT',
                'offering_start_date' => 'TEXT',
                'size_name' => 'VARCHAR(255)',
                'variation_theme_id' => 'TEXT',
                'website_shipping_weight' => 'VARCHAR(255)',
                'website_shipping_weight_UOM' => 'VARCHAR(255)'
            )
        );
    }

    public function ordersTableConstruct()
    {
        return array(
            'table' => self::AMZ_ORD,
            'rows' => array(
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'amazon_order_id' => 'TEXT',
                'merchant_order_id' => 'TEXT',
                'purchase_date' => 'TEXT',
                'last_updated_date' => 'TEXT',
                'order_status' => 'TEXT',
                'fulfillment_channel' => 'TEXT',
                'sales_channel' => 'TEXT',
                'order_channel' => 'TEXT',
                'url' => 'TEXT',
                'ship_service_level' => 'TEXT',
                'product_name' => 'TEXT',
                'sku' => 'TEXT',
                'asin' => 'TEXT',
                'item_status' => 'TEXT',
                'quantity' => 'TEXT',
                'currency' => 'TEXT',
                'item_price' => 'TEXT',
                'item_tax'  => 'TEXT',
                'shipping_price'  => 'TEXT',
                'shipping_tax'  => 'TEXT',
                'gift_wrap_price'  => 'TEXT',
                'gift_wrap_tax'  => 'TEXT',
                'item_promotion_discount' => 'TEXT',
                'ship_promotion_discount' => 'TEXT',
                'ship_city'  => 'TEXT',
                'ship_state' => 'TEXT',
                'ship_postal_code'  => 'TEXT',
                'ship_country'  => 'TEXT',
                'promotion_ids'  => 'TEXT',
            )
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
        $order_status_data = array(
            'Pending',
            'Shipped',
            'Cancelled',
        );
        $currency_data = array();
        $currency_table = $this->getTableName(self::AMZ_ORD);
        $currency_query = "SELECT DISTINCT `currency` FROM {$currency_table} WHERE folder = '{$this->_folder}'";
        $currencies = $this->readQuery($currency_query);
        if($currencies['result'] != 'success'){
            return $this->errorDatabase();
        }
        $key = 0;
        foreach($currencies['data'] as $currency){
            $value = $currency['currency'];
            $currency_data[$key] = $value;
            $key++;
        }
        $country_data = array();
        $country_query = "SELECT DISTINCT `ship_country` FROM {$currency_table} WHERE folder = '{$this->_folder}'";
        $countries = $this->readQuery($country_query);
        if ($countries['result'] != 'success'){
            return $this->errorDatabase();
        }
        $key = 0;
        foreach ($countries['data'] as $country){
            $value = $country['ship_country'];
            $country_data[$key] = $value;
            $key++;
        }
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $languages_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['country_data'] = $country_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['import_support']['customers'] = false;
        $this->_notice['config']['import_support']['taxes'] = false;
        $this->_notice['config']['import_support']['manufacturers'] = false;
        $this->_notice['config']['import_support']['categories'] = false;
        $this->_notice['config']['import_support']['reviews'] = false;
        $this->_notice['config']['import_support']['orders'] = true;
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
        $product_table = $this->getTableName(self::AMZ_PRO);
        $order_table = $this->getTableName(self::AMZ_ORD);
        $queries = array(
            'products' => "SELECT COUNT(1) AS count FROM (SELECT a.*, b.count1 FROM `" . $product_table . "` a INNER JOIN (SELECT item_name, count(item_name) as count1 FROM `". $product_table ."` GROUP BY item_name) b ON a.item_name = b.item_name
                            WHERE (a.item_classification <> 'variation_parent' AND b.count1 = 1) OR (a.item_classification = 'variation_parent' AND b.count1 > 1)) as PRODUCT WHERE folder = '" . $this->_folder . "'",
            'orders' => "SELECT COUNT(1) AS count FROM `" .$order_table. "` WHERE folder = '" . $this->_folder . "'",
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

    public function configCurrency()
    {
        return array(
            'result' => 'success'
        );
    }

    public function prepareImportProducts(){
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids']= $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    public function getProducts()
    {
        //delete_cate
        // if($this->_notice['config']['root_category_id']) {
        //     $collection = Mage::getModel('catalog/category')
        //         ->getCollection()
        //         ->addFieldToFilter('parent_id', $this->_notice['config']['root_category_id'])
        //         ->setPageSize($this->_notice['clear_info']['limit'])
        //         ->setCurPage(1);
        //     if (!count($collection)) {
        //         //return;
        //     } else {
        //         foreach ($collection as $category) {
        //             $category->delete();
        //         }
        //     }
        // }
        //end
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $prd_table = $this->getTableName(self::AMZ_PRO);
        $query = "SELECT * FROM (SELECT a.*, b.count1 FROM `" . $prd_table . "` a INNER JOIN (SELECT item_name, count(item_name) as count1 FROM `". $prd_table ."` GROUP BY item_name) b ON a.item_name = b.item_name
        WHERE (a.item_classification <> 'variation_parent' AND b.count1 = 1) OR (a.item_classification = 'variation_parent' AND b.count1 > 1)) AS PRODUCT WHERE folder = '".$this->_folder."' AND id > " . $id_src . " ORDER BY id ASC LIMIT " . $limit;
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
            return $this->errorDatabase(true);
        }
        return $result;
    }

    public function getProductId($product)
    {
        return $product['id'];
    }

    public function checkProductImport($product)
    {
        $product_code = $product['id'];
        return $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $product_code);
    }

    public function convertProduct($product)
    {
        if(LitExtension_CartImport_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product);
        }
        $pro_has_child = $this->_checkProductHasChild($product);
        if ($pro_has_child){
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
        if ($pro_convert['result'] != 'success') {
            return $pro_convert;
        }
        $pro_data = array_merge($pro_data, $pro_convert['data']);
        return array(
            'result' => "success",
            'data' => $pro_data
        );
    }

    protected function _convertProduct($product)
    {
        $pro_data = $category_ids = array();
        //cate_product
        // $cat_data['name'] = $product['product_type'];
        // $cat_data['description']  = strtolower($product['product_type']);
        // $cat_data['meta_title'] = '';
        // $cat_data['meta_keywords'] = '';
        // $cat_data['meta_description'] =  strtolower($product['product_type']);
        // $cat_parent_id = $this->_notice['config']['root_category_id'];
        // $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        // $cat_data['path'] = $pCat->getPath();
        // $cat_data['is_active'] = true;
        // $cat_data['is_anchor'] = 0;
        // $cat_data['include_in_menu'] = 1;
        // $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        // $cat_data['position'] = '';
        // $cat_id = $this->_process->category($cat_data);
        //end
        // if ($cat_id['mage_id'] != '' || $cat_id['mage_id'] != 0){
        //     $category_ids[] = $cat_id['mage_id'];
        // }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = str_replace('"', '', $product['sku']);
        $pro_data['name'] = $product['item_name'];
        $pro_data['description'] = $product['product_description'];
        $pro_data['short_description'] = '';
        $pro_data['meta_keyword'] = $product['item_type_keyword'];
        $pro_data['weight'] = $product['item_weight'];
        $pro_data['status'] = 1;
        $pro_data['created_at'] = date('Y-m-d H:i:s', strtotime($product['offering_start_date']));
        $pro_data['updated_at'] = date('Y-m-d H:i:s', strtotime($product['offering_start_date']));
        $qty = 0;
        if ($product['offer_inventory_quantity'] == ''){
            $manage_stock = 0;
        } else {
            $manage_stock = 1;
            $qty = $product['offer_inventory_quantity'];
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $manage_stock,
            'use_config_manage_stock' => 0,
            'qty' => $qty,
        );
        $merchantID = $this->_notice['config']['merchantID'];
        if ($merchantID != ''){
            $cart_url = ltrim($this->_cart_url, '/');
            $url_prd = $cart_url . '/api/product/msku/' . $merchantID . '/' . str_replace('"', '', $product['sku']);
            $img_path = $this->_getImgDesUrlImport($url_prd);
            if ($img_path){
                $pro_data['image_import_path'] = array('path' => $img_path, 'label' => '');
            }
        }
        $child_prd = $this->selectTable(self::AMZ_PRO,
            array(
                'folder' => $this->_folder,
                'item_name' => $product['item_name'],
                'model' => $product['model'],
                'item_classification' => 'base_product'
            )
        );
        if (!empty($child_prd)) {
            foreach ($child_prd as $child) {
                $pro_data['price'] = $child['our_price'] ? $child['our_price'] : 0;
                if ($child['discounted_price']) {
                    $pro_data['special_price'] = $child['discounted_price'];
                }
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product);
        if ($custom) {
            $pro_data = array_merge($pro_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }

    public function importProduct($data, $product)
    {
        if(LitExtension_CartImport_Model_Custom::PRODUCT_IMPORT){
            return $this->_custom->importProductCustom($this, $data, $product);
        }
        $id_src = $this->getProductId($product);
        $productIpt = $this->_process->product($data);
        $product_code = $product['id'];
        if($productIpt['result'] == 'success'){
            $id_desc = $productIpt['mage_id'];
            $this->productSuccess($id_src, $id_desc, $product_code);
        } else {
            $productIpt['result'] = 'warning';
            $msg = "Product ID = {$product_code} import failed. Error: " . $productIpt['msg'];
            $productIpt['msg'] = $this->consoleWarning($msg);
        }
        return $productIpt;
    }

    public function afterSaveProduct($product_mage_id, $data, $product)
    {
        if (parent::afterSaveProduct($product_mage_id, $data, $product)) {
            return;
        }
        if ($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE){
            $check_prd = $this->selectTable(self::AMZ_PRO,
                array(
                    'folder' => $this->_folder,
                    'item_name' => $product['item_name'],
                    'model' => $product['model'],
                )
            );
            $types = array(
                'DROPDOWN' => 'drop_down',
            );
            $option_data = array();
            $display_type = 'DROPDOWN';
            $proAttrSrc = explode('/', $product['variation_theme_id']);
            foreach ($proAttrSrc as $pro_attr) {
                $opt_data = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($types[$display_type]),
                    'type' => $types[$display_type],
                    'is_require' => 1,
                    'title' => $pro_attr,
                );
                $opt_data['values'] = array();
                if (count($check_prd) >= 2) {
                    foreach ($check_prd as $child_product) {
                        $value = array();
                        if ($child_product['item_classification'] != 'variation_parent') {
                            if ($child_product['size_name'] != '') {
                                $value = array(
                                    'option_type_id' => -1,
                                    'title' => strip_tags($child_product['size_name']),
                                    'price' => $child_product['discounted_price'] ? $child_product['our_price'] : $child_product['discounted_price'],
                                    'price_type' => 'fixed',
                                );
                            }
                            if ($child_product['color_name'] != '') {
                                $value = array(
                                    'option_type_id' => -1,
                                    'title' => strip_tags($child_product['color_name']),
                                    'price' => $child_product['discounted_price'] ? $child_product['our_price'] : $child_product['discounted_price'],
                                    'price_type' => 'fixed',
                                );
                            }
                        }
                        $opt_data['values'][] = $value;
                    }
                    $option_data[] = $opt_data;
                }
                if ($option_data) {
                    $this->importProductOption($product_mage_id, $option_data);
                }
            }
        }
    }

    public function getOrders()
    {
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $order_table = $this->getTableName(self::AMZ_ORD);
        $query = "SELECT * FROM {$order_table} WHERE folder = '{$this->_folder}' AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    public function getOrderId($order)
    {
        return $order['id'];
    }

    public function convertOrder($order)
    {
        if(LitExtension_CartImport_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order);
        }
        $data = $address_billing = $address_shipping = $carts = array();
        $address_billing['firstname'] = '';
        $address_billing['lastname'] = '';
        $address_billing['company'] = '';
        $address_billing['email'] = '';
        $address_billing['country_id'] = $order['ship_country'];
        $address_billing['street'] = '';
        $address_billing['postcode'] = $order['ship_postal_code'];
        $address_billing['city'] = $order['ship_city'];
        $address_billing['telephone'] = '';
        $address_billing['fax'] = '';
        if($order['ship_state']){
            $bil_region_id = false;
            if(strlen($order['ship_state']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($order['ship_state'], $order['ship_country']);
                if($region->getId()){
                    $bil_region_id = $region->getId();
                }
            } else {
                $bil_region_id = $this->getRegionId($order['ship_state'], $order['ship_country']);
            }
            if($bil_region_id){
                $address_billing['region_id'] = $bil_region_id;
            }
            $address_billing['region'] = $order['ship_state'];
        } else {
            $address_billing['region_id'] = 0;
        }

        $address_shipping['firstname'] ='';
        $address_shipping['lastname'] = '';
        $address_shipping['company'] = '';
        $address_shipping['email'] = '';
        $address_shipping['country_id'] = $order['ship_country'];
        $address_shipping['street'] = '';
        $address_shipping['postcode'] = $order['ship_postal_code'];
        $address_shipping['city'] = $order['ship_city'];
        $address_shipping['telephone'] = '';
        $address_shipping['fax'] = '';
        if($order['ship_state']){
            $ship_region_id = false;
            if(strlen($order['ship_state']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($order['ship_state'], $order['ship_country']);
                if($region->getId()){
                    $ship_region_id = $region->getId();
                }
            } else {
                $ship_region_id = $this->getRegionId($order['ship_state'], $order['ship_country']);
            }
            if($ship_region_id){
                $address_shipping['region_id'] = $ship_region_id;
            }
            $address_shipping['region'] = $order['ship_state'];
        } else {
            $address_shipping['region_id'] = 0;
        }
        $cart = array();
        $product_id = $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $order['sku']);
        if ($product_id) {
            $cart['product_id'] = $product_id;
        } else {
            $cart['product_id'] = '';
        }
        $price_product = 0;
        if ($order['quantity'] != '' && $order['quantity'] != 0){
            $price_product = $order['item_price'] / $order['quantity'];
        }
        $total = $order['item_price'] + $order['item_tax'] + $order['shipping_price'] + $order['shipping_tax'];
        $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $cart['name'] = $order['product_name'];
        $cart['sku'] = $order['sku'];
        $cart['price'] = $price_product;
        $cart['original_price'] = $price_product;
        $cart['qty_ordered'] = $order['quantity'];
        $cart['row_total'] = $order['item_price'] + $order['item_tax'];
        $carts[] = $cart;

        $order_status_map = array(
            'Pending' => 'pending',
            'Shipped' => 'complete',
            'Cancelled' => 'canceled'
        );
        foreach ($order_status_map as $order_vls => $order_wp){
            if ($order['order_status'] == $order_vls)
                $order_status_id = $order_wp;
        }
        $discount_amount = '';
        if ($order['item_promotion_discount'] != ''){
            $discount_amount = $order['item_promotion_discount'] + $order['ship_promotion_discount'];
        }
        $tax_amount = $order['item_tax'];
        $store_id = $this->_notice['config']['languages'][1];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $ship_amount = $order['shipping_price'] + $order['shipping_tax'];
        $sub_total = $order['item_price'];

        $order_data = array();
        $order_data['store_id'] = $store_id;
        $order_data['customer_is_guest'] = true;
        $order_data['customer_email'] = '';
        $order_data['customer_firstname'] = '';
        $order_data['customer_lastname'] = '';
        $order_data['customer_group_id'] = 1;
        $order_data['status'] = $order_status_id;
        $order_data['state'] = $this->getOrderStateByStatus($order_status_id);
        $order_data['subtotal'] = $this->incrementPriceToImport($sub_total);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $ship_amount;
        $order_data['base_shipping_amount'] = $ship_amount;
        $order_data['base_shipping_invoiced'] = $ship_amount;
        $order_data['shipping_tax_amount'] = $order['shipping_tax'];
        $order_data['shipping_description'] = "Shipping";
        $order_data['tax_amount'] = $tax_amount;
        $order_data['base_tax_amount'] = $tax_amount;
        $order_data['discount_amount'] = $discount_amount;
        $order_data['base_discount_amount'] = $discount_amount;
        $order_data['grand_total'] = $this->incrementPriceToImport($total);
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
        $order_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['last_updated_date']));

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['amazon_order_id'];
        $custom = $this->_custom->convertOrderCustom($this, $order);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

    public function afterSaveOrder($order_id_desc, $convert, $order)
    {

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

    protected function _checkProductHasChild($product)
    {
        $check_prd = $this->selectTable(self::AMZ_PRO, array(
            'folder' => $this->_folder,
            'item_name' => $product['item_name'],
            'model' => $product['model'],
        ));
        if (count($check_prd) >= 2 && $product['item_classification'] == 'variation_parent') {
            return true;
        }
        return false;
    }

    protected function _importChildrenProduct($parent){
        $product_code = $parent['id'];
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attrIpt = $this->_importAttribute($parent);
        if ($attrIpt['result'] != 'success') {
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Product Code = {$product_code} import failed. Error: Product attribute could not be created!")
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
        $childProduct = $this->selectTable(self::AMZ_PRO, array(
            'folder' => $this->_folder,
            'item_name' => $parent['item_name'],
            'model' => $parent['model'],
        ));
        if (count($childProduct) >= 2) {
            foreach ($childProduct as $pro_child) {
                if ($pro_child['item_classification'] != 'variation_parent') {
                    $pro_child_ipt_id = $this->getIdDescProduct($pro_child['id']);
                    if ($pro_child_ipt_id) {
                        // do nothing
                    } else {
                        $pro_child_data = array(
                            'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                        );
                        $pro_child_convert = $this->_convertProduct($pro_child);
                        if ($pro_child_convert['result'] != 'success') {
                            return array(
                                'result' => "warning",
                                'msg' => $this->consoleWarning("Product ID = {$product_code} import failed. Error: Product children could not create!(Error code: Product children data not found.)")
                            );
                        }
                        $pro_child_data = array_merge($pro_child_convert['data'], $pro_child_data);
                        $pro_child_ipt = $this->_process->product($pro_child_data);
                        if ($pro_child_ipt['result'] != 'success') {
                            return array(
                                'result' => "warning",
                                'msg' => $this->consoleWarning("Product ID = {$product_code} import failed. Error: Product children could not create!(Error code: " . $pro_child_ipt['msg'] . ". )")
                            );
                        }
                        $this->productSuccess($pro_child['id'], $pro_child_ipt['mage_id'], str_replace('"', '', $pro_child['sku']));
                        $pro_child_ipt_id = $pro_child_ipt['mage_id'];
                        $pro_attr_data = array();
                        $size_code = 0; $color_code = 1;
                        if (isset($attrIpt['data'][$size_code]['data'])) {                        	
                            $pro_child_attr = $attrIpt['data'][$size_code]['data'];
                            if ($pro_child['size_name'] != '') {
                                $key = 'option_size';
                                $opt_id = $pro_child_attr['option_ids'][$key];
                                $attr_id = $pro_child_attr['attribute_id'];
                                $this->setProAttrSelect($entity_type_id, $attr_id, $pro_child_ipt_id, $opt_id);
                                $pro_attr_data = array(
                                    'label' => isset($pro_child_attr['label']) ? $pro_child_attr['label'] : " ",
                                    'attribute_id' => $attr_id,
                                    'value_index' => $opt_id,
                                );
                            }
                        }
                    	if (isset($attrIpt['data'][$color_code]['data'])) {                        	
                            $pro_child_attr = $attrIpt['data'][$color_code]['data'];
                            if ($pro_child['color_name'] != '') {
                                $key = 'option_color';
                                $opt_id = $pro_child_attr['option_ids'][$key];
                                $attr_id = $pro_child_attr['attribute_id'];
                                $this->setProAttrSelect($entity_type_id, $attr_id, $pro_child_ipt_id, $opt_id);
                                $pro_attr_data = array(
                                    'label' => isset($pro_child_attr['label']) ? $pro_child_attr['label'] : " ",
                                    'attribute_id' => $attr_id,
                                    'value_index' => $opt_id,
                                );
                            }
                        }
                        $configurable_products_data[$pro_child_ipt_id][] = $pro_attr_data;                        
                    }
                }
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
            foreach($option_ids as $opt_key => $opt_value){
                $value = array(
                    'label' => isset($attr_ipt[$opt_key]) ? $attr_ipt[$opt_key] : " ",
                    'attribute_id' => $attr_ipt['data']['attribute_id'],
                    'value_index' => $opt_value,
                    'is_percent' => 0,
                    'pricing_value' =>  ($pro_child['discounted_price'] != '') ? $pro_child['discounted_price'] : $pro_child['our_price'],
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
        $childProduct = $this->selectTable(self::AMZ_PRO, array(
            'folder' => $this->_folder,
            'item_name' => $product['item_name'],
            'model' => $product['model'],
        ));
        if (count ( $childProduct ) >= 2) {
			$variation_attr = explode ( '/', $product ['variation_theme_id'] );
			foreach ( $childProduct as $child_product ) {
				if ($child_product ['item_classification'] != 'variation_parent') {
					$child_size_code = 0;
					$child_color_code = 1;
					$attr_save = $attr_data = array ();
					if ($child_product ['size_name'] != '') {
						$attr_data = array (
								'entity_type_id' => $entity_type_id,
								'attribute_code' => 'size',
								'attribute_set_id' => $this->_notice ['config'] ['attribute_set_id'],
								'frontend_input' => 'select',
								'frontend_label' => array (
										'Size' 
								),
								'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
								'is_configurable' => true,
								'apply_to' => array () 
						);
						$attr_save ['label'] = 'Size';
						$values = $opt_label = array ();
						$key = 'option_size';
						$values [$key] = array (
								0 => $child_product ['size_name'] 
						);
						$opt_label [$key] = $child_product ['size_name'];
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
						$data [$child_size_code] = $attr_save;
					}
					if ($child_product['color_name'] != ''){
						$attr_data = array (
								'entity_type_id' => $entity_type_id,
								'attribute_code' => 'color',
								'attribute_set_id' => $this->_notice ['config'] ['attribute_set_id'],
								'frontend_input' => 'select',
								'frontend_label' => array (
										'Color'
								),
								'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
								'is_configurable' => true,
								'apply_to' => array ()
						);
						$attr_save ['label'] = 'Color';
						$values = $opt_label = array ();
						$key = 'option_color';
						$values [$key] = array (
								0 => $child_product ['color_name']
						);
						$opt_label [$key] = $child_product ['color_name'];
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
						$data [$child_color_code] = $attr_save;
					}
				}
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

    protected function _getTablesTmp()
    {
        return array(
            self::AMZ_PRO,
            self::AMZ_ORD,
        );
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
            $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($next);
            $this->_notice['csv_import']['msg'] = '';
            $this->_notice['csv_import']['count'] = 0;
            return $this->_notice['csv_import'];
        }
        $start = $this->_notice['csv_import']['count'];
        $demo = $this->_limitDemoModel($type);
        if ($type == 'orders') {
            $csv_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.txt';
            $readCsv = $this->readTxt($csv_file, $start, $this->_notice['setting']['csv'], $demo);
        } else {
            $csv_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
            $readCsv = $this->readCsv($csv_file, $start, $this->_notice['setting']['csv'], $demo);
        }
        if($readCsv['result'] != 'success'){
            $readCsv['msg'] = $this->consoleError($readCsv['msg']);
            return $readCsv;
        }
        $allowData = array();
        $fn_construct = $type . 'TableConstruct';
        $table = $this->$fn_construct();
        if(LitExtension_CartImport_Model_Custom::CSV_IMPORT){
            $allowData = $this->_custom->storageCsvCustom($this);
        }
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
                        'msg' => $this->consoleError('Could not import data to database.')
                    );
                }
            }
        }
        if($readCsv['finish']){
            if($finish){
                $this->_notice['csv_import']['result'] = 'success';
            } else {
                $this->_notice['csv_import']['result'] = 'process';
            }
            $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($success);
            $this->_notice['csv_import']['msg'] = $this->consoleSuccess("Finish importing " . $type);
            $this->_notice['csv_import']['count'] = 0;
            return $this->_notice['csv_import'];
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($type);
        $this->_notice['csv_import']['msg'] = '';
        $this->_notice['csv_import']['count'] = $readCsv['count'];
        return $this->_notice['csv_import'];
    }

    public function readTxt($file_path, $start, $limit = 10, $total = false)
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
            $txt = fopen($file_path, 'r');
            $end = $start + $limit;
            $csv_title = "";
            $data = array();
            while (!feof($txt)) {
                if ($total && $count > $total) {
                    $finish = true;
                    break;
                }
                if ($count > $end) {
                    break;
                }
                $line = fgetcsv($txt, 0, "\t");
                if ($count == 0) {
                    $csv_title = $line;
                }
                if ($start < $count && $count <= $end) {
                    $csv_title = str_replace('-', '_', $csv_title);
                    $data[] = array(
                        'title' => $csv_title,
                        'row' => $line
                    );
                }
                $count++;
            }
            fclose($txt);
            if (!$finish && ($count - 1) < $end) {
                $finish = true;
            }
            return array(
                'result' => 'success',
                'data' => $data,
                'count' => $end,
                'finish' => $finish
            );
        } catch (Exception $e) {
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }

    protected function getUrlImage($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response){
            $data = json_decode($response, true);
            return $data['assets']['link'];
        } else {
            return '';
        }
    }
}