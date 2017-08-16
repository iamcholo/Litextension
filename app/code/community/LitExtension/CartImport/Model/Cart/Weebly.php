<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartImport_Model_Cart_Weebly
extends LitExtension_CartImport_Model_Cart
{
    const WBY_CAT = 'lecaip_weebly_category';
    const WBY_PRO = 'lecaip_weebly_product';
    const WBY_CUS = 'lecaip_weebly_customer';
    const WBY_ORD = 'lecaip_weebly_order';
    const WBY_ORD_ID = 'lecaip_weebly_order_id';

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
            self::WBY_CAT,
            self::WBY_CUS,
            self::WBY_ORD,
            self::WBY_ORD_ID,
            self::WBY_PRO,
        );
        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name}";
            $this->writeQuery($query);
        }
    }

    public function getAllowExtensions()
    {
        return array('csv');
    }

    public function getUploadFileName($upload_name)
    {
        //$name = $upload_name . '.csv';
        return $upload_name . '.csv';
    }

    public function getUploadInfo($up_msg){
        $files = array_filter($this->_notice['config']['files']);
        $this->_notice['config']['import_support']['manufacturers'] = false;
        $this->_notice['config']['import_support']['taxes'] = false;
        $this->_notice['config']['import_support']['reviews'] = false;
        if(!empty($files)){
            if(!$this->_notice['config']['files']['products']){
                $this->_notice['config']['import_support']['products'] = false;
                $this->_notice['config']['import_support']['categories'] = false;
            }
            if(!$this->_notice['config']['files']['orders']){
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
                $this->_notice['config']['import_support']['customers'] = false;
            }
            foreach($files as $type => $upload){
                if($upload){
                    $func_construct = $type . "TableConstruct";
                    $construct = $this->$func_construct();
                    $validate = isset($construct['validation']) ? $construct['validation'] : false;
                    $csv_file = Mage::getBaseDir('media'). self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
                    $readCsv = $this->readCsv($csv_file, 0, 1, false);
                    if($readCsv['result'] == 'success'){
                        foreach($readCsv['data'] as $item){
                            if($validate){
                                foreach($validate as $row){
                                    if(!in_array($row, $item['title'])){
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
            $this->_notice['csv_import']['function'] = '_setupStorageCsv';
        }
        return array(
            'result' => 'success',
            'msg' => $up_msg
        );
    }

    public function displayConfig(){
        $parent = parent::displayConfig();
        if($parent["result"] != "success"){
            return $parent;
        }
        $response = array();
        $category_data = array("Root category");
        $attribute_data = array("Root attribute set");
        $languages_data = array(1 => "Default language");
        $order_status_data = $currency_data = array();
        $this->_notice['config']['config_support']['order_status_map'] = false;
        $this->_notice['config']['config_support']['currency_map'] = false;
        if($this->_notice['config']['files']['orders']){
            $ord_table = $this->getTableName(self::WBY_ORD);
            $ord_status_query = "SELECT DISTINCT(Status) FROM {$ord_table} WHERE folder = '{$this->_folder}'";
            $ord_status = $this->readQuery($ord_status_query);
            if($ord_status['result'] != 'success' || !$ord_status['data']){
                return $this->errorDatabase();
            }
            foreach ($ord_status['data'] as $ord_status_row){
                if ($ord_status_row['Status'] != ''){
                    $ord_status_id = $ord_status_row['Status'];
                    $ord_status_value = $ord_status_row['Status'];
                    $order_status_data[$ord_status_id] = $ord_status_value;
                }
            }
            $ord_currency_query = "SELECT DISTINCT(Currency) FROM {$ord_table} WHERE folder = '{$this->_folder}'";
            $ord_currency = $this->readQuery($ord_currency_query);
            if($ord_currency['result'] != 'success' || !$ord_currency['data']){
                return $this->errorDatabase();
            }
            foreach ($ord_currency['data'] as $currency_row){
                if ($currency_row['Currency'] != '') {
                    $currency_id = $currency_row['Currency'];
                    $currency_value = $currency_row['Currency'];
                    $currency_data[$currency_id] = $currency_value;
                }
            }
            if ($order_status_data){
                $this->_notice['config']['config_support']['order_status_map'] = true;
            }
            if ($currency_data){
                $this->_notice['config']['config_support']['currency_map'] = true;
            }
        }
        $this->_notice['config']['config_support']['country_map'] = false;
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $languages_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $response['result'] = 'success';
        return $response;
    }

    public function displayConfirm($params){
        parent::displayConfirm($params);
        return array(
            'result' => 'success'
        );
    }

    public function displayImport(){
        $category_table = $this->getTableName(self::WBY_CAT);
        $product_table = $this->getTableName(self::WBY_PRO);
        $customer_table = $this->getTableName(self::WBY_CUS);
        $order_table = $this->getTableName(self::WBY_ORD_ID);
        $queries = array(
            'categories' => "SELECT COUNT(1) AS count FROM {$category_table} WHERE folder = '{$this->_folder}'",
            'products' => "SELECT COUNT(DISTINCT(PRODUCT_ID)) AS count FROM {$product_table} WHERE folder = '{$this->_folder}'",
            'customers' => "SELECT COUNT(1) AS count FROM {$customer_table} WHERE folder = '{$this->_folder}' AND Shipping_Email <> ''",
            'orders' => "SELECT COUNT(1) AS count FROM {$order_table} WHERE folder = '{$this->_folder}'",
        );
        $data = array();
        foreach($queries as $type => $query){
            $read = $this->readQuery($query);
            if($read['result'] != 'success'){
                return $this->errorDatabase();
            }
            $count = $this->arrayToCount($read['data'], 'count');
            $data[$type] = $count;
        }
        $data = $this->_limit($data);
        foreach($data as $type => $count){
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

    public function storageCsv(){
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

    public function configCurrency(){
        return array(
            'result' => 'success'
        );
    }

    public function getCategories(){
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $cat_table = $this->getTableName(self::WBY_CAT);
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
        $product_table = $this->getTableName(self::WBY_PRO);
        $query = "SELECT DISTINCT(PRODUCT_ID) FROM {$product_table} WHERE folder = '{$this->_folder}' AND PRODUCT_ID > {$id_src} ORDER BY PRODUCT_ID ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    public function getProductId($product){
        return $product['PRODUCT_ID'];
    }

    public function checkProductImport($product){
        $product_code = $product['PRODUCT_ID'];
        return $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $product_code);
    }

    public function convertProduct($product){
        if(LitExtension_CartImport_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product);
        }
        $pro_data = array();
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
            $this->productSuccess($id_src, $id_desc, $product['PRODUCT_ID']);
        } else {
            $productIpt['result'] = 'warning';
            $msg = "Product id = {$product['PRODUCT_ID']} import failed. Error: " . $productIpt['msg'];
            $productIpt['msg'] = $this->consoleWarning($msg);
        }
        return $productIpt;
    }

    public function afterSaveProduct($product_mage_id, $data, $product){
        if(parent::afterSaveProduct($product_mage_id, $data, $product)){
            return ;
        }
        if($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE){
        }
    }

    public function getCustomers(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $customer_table = $this->getTableName(self::WBY_CUS);
        $query = "SELECT * FROM {$customer_table} WHERE folder = '{$this->_folder}' AND id > {$id_src} AND Shipping_Email <> '' ORDER BY id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    public function getCustomerId($customer){
        return $customer['id'];
    }

    public function convertCustomer($customer){
        if(LitExtension_CartImport_Model_Custom::CUSTOMER_CONVERT){
            return $this->_custom->convertCustomerCustom($this, $customer);
        }
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['Shipping_Email'];
        $cus_data['firstname'] = $customer['Shipping_First_Name'];
        $cus_data['lastname'] = $customer['Shipping_Last_Name'];
        $cus_data['created_at'] = $customer['Date'] ? date('Y-m-d H:i:s', strtotime($customer['Date'])) : date('Y-m-d H:i:s');
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
        $address['firstname'] = $customer['Shipping_First_Name'];
        $address['lastname'] = $customer['Shipping_Last_Name'];
        $country_cus_code = $customer['Shipping_Country'];
        $address['country_id'] = $country_cus_code;
        $address['street'] = $customer['Shipping_Address'] . "\n" . $customer['Shipping_Address_2'];
        $address['postcode'] = $customer['Shipping_Postal_Code'];
        $address['city'] = $customer['Shipping_City'];
        $address['telephone'] = $customer['Shipping_Phone'];
        if($customer['Shipping_Region']){
            $region_id = false;
            if(strlen($customer['Shipping_Region']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($customer['Shipping_Region'], $country_cus_code);
                if($region->getId()){
                    $region_id = $region->getId();
                }
            } else {
                $region_id = $this->getRegionId($customer['Shipping_Region'], $country_cus_code);
            }
            if($region_id){
                $address['region_id'] = $region_id;
            }
            $address['region'] = $customer['Shipping_Region'];
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
        $order_table = $this->getTableName(self::WBY_ORD_ID);
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
        $ordDtl = $this->selectTable(self::WBY_ORD, array(
            'folder' => $this->_folder,
            'Order_ID' => $order['Order_ID']
        ));
        $ord_dtl = $ordDtl[0];

        $cus_table = $this->getTableName(self::WBY_CUS);
        $customers = $this->readQuery("SELECT * FROM {$cus_table} WHERE folder = '{$this->_folder}' AND Shipping_Email = '{$ord_dtl['Shipping_Email']}' ");
        if($customers['result'] != 'success' || empty($customers['data'])){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Order id = {$order['Order_ID']} import failed. Error: Customer not import!")
            );
        }
        $customer = $customers['data'][0];

        $data = $address_billing = $address_shipping = $carts = array();

        $country_bill_code = $ord_dtl['Billing_Country'] ? $ord_dtl['Billing_Country'] : $ord_dtl['Shipping_Country'];
        $fname = $lname = '';
        if ($ord_dtl['Billing_Name'] != '') {
            $cus_name = explode(' ', $ord_dtl['Billing_Name']);
            foreach ($cus_name as $key => $name) {
                if ($key == 0) {
                    $fname = $name;
                } elseif ($key > 0) {
                    $lname .= $name;
                }
            }
        }
        $address_billing['firstname'] = ($fname != '') ? $fname : $ord_dtl['Shipping_First_Name'];
        $address_billing['lastname'] = ($lname != '') ? $lname : $ord_dtl['Shipping_Last_Name'];
        $address_billing['email'] = $ord_dtl['Shipping_Email'];
        $address_billing['country_id'] = $country_bill_code;
        $address_billing['street'] = $ord_dtl['Billing_Address1'] . "\n" . $ord_dtl['Billing_Address2'];
        $address_billing['postcode'] = $ord_dtl['Billing_Postal_Code'];
        $address_billing['city'] = $ord_dtl['Billing_City'];
        $address_billing['telephone'] = $ord_dtl['Shipping_Phone'];
        if($ord_dtl['Billing_Region']){
            $bil_region_id = false;
            if(strlen($ord_dtl['Billing_Region']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($ord_dtl['Billing_Region'], $country_bill_code);
                if($region->getId()){
                    $bil_region_id = $region->getId();
                }
            } else {
                $bil_region_id = $this->getRegionId($ord_dtl['Billing_Region'], $country_bill_code);
            }
            if($bil_region_id){
                $address_billing['region_id'] = $bil_region_id;
            }
            $address_billing['region'] = $ord_dtl['Billing_Region'];
        } else {
            $address_billing['region_id'] = 0;
        }

        $country_ship_code = $ord_dtl['Shipping_Country'];
        $address_shipping['firstname'] = $ord_dtl['Shipping_First_Name'];
        $address_shipping['lastname'] = $ord_dtl['Shipping_First_Name'];
        $address_shipping['email'] = $ord_dtl['Shipping_Email'];
        $address_shipping['country_id'] = $country_ship_code;
        $address_shipping['street'] = $ord_dtl['Shipping_Address'] . "\n" . $ord_dtl['Shipping_Address_2'];
        $address_shipping['postcode'] = $ord_dtl['Shipping_Postal_Code'];
        $address_shipping['city'] = $ord_dtl['Shipping_City'];
        $address_shipping['telephone'] = $ord_dtl['Shipping_Phone'];
        if($ord_dtl['Shipping_Region']){
            $ship_region_id = false;
            if(strlen($ord_dtl['Shipping_Region']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($ord_dtl['Shipping_Region'], $country_ship_code);
                if($region->getId()){
                    $ship_region_id = $region->getId();
                }
            } else {
                $ship_region_id = $this->getRegionId($ord_dtl['Shipping_Region'], $country_ship_code);
            }
            if($ship_region_id){
                $address_shipping['region_id'] = $ship_region_id;
            }
            $address_shipping['region'] = $ord_dtl['Shipping_Region'];
        } else {
            $address_shipping['region_id'] = 0;
        }
        if($ordDtl){
            foreach($ordDtl as $key => $order_detail) {
                if ($key > 0) {
                    $cart = array();
                    $prd_src = $this->selectTable(self::WBY_PRO, array(
                        'folder' => $this->_folder,
                        'name' => $order_detail['Product_Name']
                    ));
                    $prd_id_src = $prd_src[0]['PRODUCT_ID'];
                    $product_id = $this->getIdDescProduct($prd_id_src);
                    if ($product_id) {
                        $cart['product_id'] = $product_id;
                    }
                    $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                    $cart['name'] = $order_detail['Product_Name'];
                    $cart['sku'] = $order_detail['Product_SKU'];
                    $cart['price'] = $order_detail['Product_Sale_Price'] ? $order_detail['Product_Sale_Price'] : $order_detail['Product_Price'];
                    $cart['original_price'] = $order_detail['Product_Price'];
                    $cart['qty_ordered'] = $order_detail['Product_Quantity'];
                    $cart['row_total'] = $order_detail['Product_Total_Price'];
//                    if ($order_detail['Product_Options']) {
//                        $product_opt = array();
//                        $options = explode(', ', $order_detail['Product_Options']);
//                        foreach ($options as $key => $option) {
//                            $opt_data = array(
//                                'label' => $option ? $option : " ",
//                                'value' => $option ? $option : " ",
//                                'print_value' => $option ? $option : " ",
//                                'option_id' => 'option_' . $key,
//                                'option_type' => 'drop_down',
//                                'option_value' => 0,
//                                'custom_view' => false
//                            );
//                            $product_opt[] = $opt_data;
//                        }
//                        $cart['product_options'] = serialize(array('options' => $product_opt));
//                    }
                    $carts[] = $cart;
                }
            }
        }

        $customer_id = $this->getIdDescCustomer($customer['id']);
        $tax_amount = $ord_dtl['Tax_Total'];
        $discount_amount = 0;
        $store_id = $this->_notice['config']['languages'][1];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $ship_amount = $ord_dtl['Shipping'];
        $sub_total = $ord_dtl['Subtotal'];

        $order_data = array();
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$ord_dtl['Status']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $ord_dtl['Shipping_Email'];
        $order_data['customer_firstname'] = $order_detail['Shipping_First_Name'];
        $order_data['customer_lastname'] = $order_detail['Shipping_Last_Name'];
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
        $order_data['created_at'] = date('Y-m-d H:i:s', strtotime($ord_dtl['Date']));

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

    public function afterSaveOrder($order_mage_id, $data, $order){
        if(parent::afterSaveOrder($order_mage_id, $data, $order)){
            return ;
        }
    }

    protected function _checkProductHasChild($product){
        $check_prd = $this->selectTable(self::WBY_PRO, array(
            'folder' => $this->_folder,
            'PRODUCT_ID' => $product['PRODUCT_ID']
        ));
        if (count($check_prd) >= 2){
            return true;
        }
        return false;
    }

    protected function _convertProduct($product){
        $child_product = $this->_selectChildProduct($product);
        $product_main = $child_product[0];
        $pro_data = $category_ids = array();
        if($product_main['CATEGORIES']){
            $categories = explode(',', $product_main['CATEGORIES']);
            foreach($categories as $category){
                $cat_src = $this->selectTable(self::WBY_CAT, array(
                    'folder' => $this->_folder,
                    'name' => $category
                ));
                if ($cat_src){
                    $category_id_desc = $this->getIdDescCategory($cat_src[0]['id']);
                    if($category_id_desc){
                        $category_ids[] = $category_id_desc;
                    }
                }
            }
        }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = '';
        $pro_data['name'] = $product_main['TITLE'];
        $pro_data['description'] = $this->changeImgSrcInText($product_main['DESCRIPTION'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['weight'] = $product_main['WEIGHT'];
        $pro_data['status'] = 1;
        $pro_data['price'] = $product_main['PRICE'] ? $product_main['PRICE'] : 0;
        $pro_data['tax_class_id'] = 0;
        if($product_main['SALE_PRICE']){
            $pro_data['special_price'] = $product_main['SALE_PRICE'];
        }
        $pro_data['create_at'] = date('Y-m-d H:i:s');
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $manage_stock = false;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $manage_stock,
            'use_config_manage_stock' => $manage_stock,
            'qty' => 0,
            'backorders' => 0
        );
        $img = $product_main['IMAGE'];
        if($img){
            $img = strtolower($img);
            $image_convert = $this->convertUrlToDownload($img, $this->_cart_url);
            if($image_convert){
                $img_path = $this->downloadImage($image_convert['domain'], $image_convert['path'], 'catalog/product', false, true);
                if($img_path){
                    $pro_data['image_import_path'] = array('path' => $img_path, 'label' => 'image_'.$product_main['PRODUCT_ID']);
                }
            }
        }
        if($this->_seo){
            $seo = $this->_seo->convertProductSeo($this, $product);
            if($seo){
                $pro_data['seo_url'] = $seo;
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

    protected function _convertProductChildren($product_main)
    {
        $pro_data = array();
        $name = $product_main['TITLE'];
        if ($product_main['OPTION1_NAME'] != ''){
            $name .= '-' . $product_main['OPTION1_NAME'] . '-' . $product_main['OPTION1_VALUE'];
        }
        if ($product_main['OPTION2_NAME'] != ''){
            $name .= '-' . $product_main['OPTION2_NAME'] . '-' . $product_main['OPTION2_VALUE'];
        }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['sku'] = $product_main['SKU'];
        $pro_data['name'] = $name;
        $pro_data['weight'] = $product_main['WEIGHT'];
        $pro_data['status'] = 1;
        $pro_data['price'] = $product_main['PRICE'] ? $product_main['PRICE'] : 0;
        $pro_data['tax_class_id'] = 0;
        if ($product_main['SALE_PRICE']) {
            $pro_data['special_price'] = $product_main['SALE_PRICE'];
        }
        $pro_data['create_at'] = date('Y-m-d H:i:s');
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $manage_stock = true;
        if ($this->_notice['config']['add_option']['stock'] && $product_main['TRACK_INVENTORY'] == 'false') {
            $manage_stock = false;
        }
        $manage_stock = false;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $manage_stock,
            'use_config_manage_stock' => $manage_stock,
            'qty' => $product_main['INVENTORY'],
            'backorders' => 0
        );
        $custom = $this->_custom->convertProductCustom($this, $product_main);
        if ($custom) {
            $pro_data = array_merge($pro_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }

    protected function _importChildrenProduct($parent){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $configurable_products_data = $configurable_attributes_data = array();
        $product_code = $parent['PRODUCT_ID'];
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
        $child_product = $this->_selectChildProduct($parent);
        if ($child_product){
            foreach ($child_product as $pro_child){
                $pro_child_ipt_id = $this->getIdDescProduct($pro_child['id']);
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
                    $this->productSuccess($pro_child['id'], $pro_child_ipt['mage_id'], $pro_child['SKU']);
                    $pro_child_ipt_id = $pro_child_ipt['mage_id'];
                    $pro_attr_data = array();
                    $option_1 = 1;$option_2 = 2;
                    if (isset($attrIpt['data'][$option_1]['data'])) {
                        $pro_child_attr = $attrIpt['data'][$option_1]['data'];
                        if ($pro_child['OPTION1_VALUE'] != '') {
                            $key = 'option_' . $pro_child['OPTION1_VALUE'];
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
                    if (isset($attrIpt['data'][$option_2]['data'])) {
                        $pro_child_attr = $attrIpt['data'][$option_2]['data'];
                        if ($pro_child['OPTION2_VALUE'] != '') {
                            $key = 'option_' . $pro_child['OPTION2_VALUE'];
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
                    'pricing_value' => '',
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

    protected function _importAttribute($product)
    {
        $data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $child_product = $this->_selectChildProduct($product);
        $table = $this->getTableName(self::WBY_PRO);
        $attr_save = array();
        if ($child_product[0]['OPTION1_NAME'] != '') {
            $attr_save = $attr_data = array();
            $attr_data = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' => strtolower(str_replace(' ', '-', $child_product[0]['OPTION1_NAME'])),
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => 'select',
                'frontend_label' => array(
                    $child_product[0]['OPTION1_NAME']
                ),
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            );
            $attr_save['label'] = $child_product[0]['OPTION1_NAME'];
            $values = $opt_label = array();
            $options_query = "SELECT DISTINCT(OPTION1_VALUE) FROM {$table} WHERE folder = '{$this->_folder}' AND OPTION1_NAME = '{$child_product[0]['OPTION1_NAME']}'";
            $options = $this->readQuery($options_query);
            if ($options['result'] == 'success' && $options['data']) {
                foreach ($options['data'] as $option_value) {
                    $key = 'option_' . $option_value['OPTION1_VALUE'];
                    $values[$key] = array(
                        0 => $option_value['OPTION1_VALUE']
                    );
                    $opt_label[$key] = $option_value['OPTION1_VALUE'];
                }
            }
            $attr_data['option']['value'] = $values;
            $attr_save['opt_label'] = $opt_label;
            $attr_ipt = $this->_process->attribute($attr_data, array(
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            ));
            if (! $attr_ipt) {
                return array(
                    'result' => "warning",
                    'msg' => ""
                );
            }
            $attr_save['data'] = $attr_ipt;
            $data[1] = $attr_save;
        }
        if ($child_product[0]['OPTION2_NAME'] != '') {
            $attr_save = $attr_data = array();
            $attr_data = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' =>  strtolower(str_replace(' ', '-', $child_product[0]['OPTION2_NAME'])),
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => 'select',
                'frontend_label' => array(
                    $child_product[0]['OPTION2_NAME']
                ),
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            );
            $attr_save['label'] = $child_product[0]['OPTION2_NAME'];
            $values = $opt_label = array();
            $options_query = "SELECT DISTINCT(OPTION2_VALUE) FROM {$table} WHERE folder = '{$this->_folder}' AND OPTION2_NAME = '{$child_product[0]['OPTION2_NAME']}'";
            $options = $this->readQuery($options_query);
            if ($options['result'] == 'success' && $options['data']) {
                foreach ($options['data'] as $option_value) {
                    $key = 'option_' . $option_value['OPTION2_VALUE'];
                    $values[$key] = array(
                        0 => $option_value['OPTION2_VALUE']
                    );
                    $opt_label[$key] = $option_value['OPTION2_VALUE'];
                }
            }
            $attr_data['option']['value'] = $values;
            $attr_save['opt_label'] = $opt_label;
            $attr_ipt = $this->_process->attribute($attr_data, array(
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            ));
            if (! $attr_ipt) {
                return array(
                    'result' => "warning",
                    'msg' => ""
                );
            }
            $attr_save['data'] = $attr_ipt;
            $data[2] = $attr_save;
        }
        if (!$data) {
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

    protected function _selectChildProduct($product){
        $child_prd = $this->selectTable(self::WBY_PRO, array(
            'folder' => $this->_folder,
            'PRODUCT_ID' => $product['PRODUCT_ID']
        ));
        return $child_prd;
    }

    protected function _setupStorageCsv()
    {
        $custom_setup = $this->_custom->storageCsvCustom($this);
        if ($custom_setup && $custom_setup['result'] == 'error') {
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
            'orderidsTableConstruct'
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
            if (! $this->writeQuery($query)) {
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

    public function getListTableDrop(){
        $tables = $this->_getTablesTmp();
        $custom = $this->_custom->getListTableDropCustom($tables);
        $result = $custom ? $custom : $tables;
        return $result;
    }

    public function categoriesTableConstruct(){
        return array(
            'table' => self::WBY_CAT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'name' => 'VARCHAR(255)',
                'code' => 'VARCHAR(255)',
            )
        );
    }

    public function productsTableConstruct()
    {
        return array(
            'table' => self::WBY_PRO,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'PRODUCT_ID' => 'TEXT',
                'TITLE' => 'VARCHAR(255)',
                'DESCRIPTION' => 'TEXT',
                'IMAGE' => 'TEXT',
                'CATEGORIES' => 'VARCHAR(255)',
                'TRACK_INVENTORY' => 'TEXT',
                'TAXABLE' => 'TEXT',
                'SKU' => 'TEXT',
                'PRICE' => 'TEXT',
                'SALE_PRICE' => 'TEXT',
                'INVENTORY' => 'TEXT',
                'WEIGHT' => 'TEXT',
                'PRODUCT_TYPE' => 'TEXT',
                'OPTION1_NAME' => 'VARCHAR(255)',
                'OPTION1_TYPE' => 'TEXT',
                'OPTION1_VALUE' => 'TEXT',
                'OPTION2_NAME' => 'TEXT',
                'OPTION2_TYPE' => 'TEXT',
                'OPTION2_VALUE' => 'TEXT',
            ),
            'validation' => array('PRODUCT_ID'),
        );
    }

    public function customersTableConstruct()
    {
        return array(
            'table' => self::WBY_CUS,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'Date' => 'TEXT',
                'Shipping_First_Name' => 'TEXT',
                'Shipping_Last_Name' => 'TEXT',
                'Shipping_Email' => 'TEXT',
                'Shipping_Address' => 'TEXT',
                'Shipping_Address_2' => 'TEXT',
                'Shipping_Postal_Code' => 'TEXT',
                'Shipping_City' => 'TEXT',
                'Shipping_Region' => 'TEXT',
                'Shipping_Country' => 'TEXT',
                'Shipping_Phone' => 'TEXT',
                'Billing_Name' => 'TEXT',
                'Billing_Address' => 'TEXT',
                'Billing_Address_2' => 'TEXT',
                'Billing_Postal_Code' => 'TEXT',
                'Billing_City' => 'TEXT',
                'Billing_Region' => 'TEXT',
                'Billing_Country' => 'TEXT',
            )
        );
    }

    public function ordersTableConstruct()
    {
        return array(
            'table' => self::WBY_ORD,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'Order_ID' => 'TEXT',
                'Date' => 'TEXT',
                'Status' => 'TEXT',
                'Currency' => 'TEXT',
                'Subtotal' => 'TEXT',
                'Shipping' => 'TEXT',
                'Tax_Total' => 'TEXT',
                'Tax_Rate' => 'TEXT',
                'Total' => 'TEXT',
                'Refunded_Amount' => 'TEXT',
                'Shipping_First_Name' => 'TEXT',
                'Shipping_Last_Name' => 'TEXT',
                'Shipping_Email' => 'TEXT',
                'Shipping_Address' => 'TEXT',
                'Shipping_Address_2' => 'TEXT',
                'Shipping_Postal_Code' => 'TEXT',
                'Shipping_City' => 'TEXT',
                'Shipping_Region' => 'TEXT',
                'Shipping_Country' => 'TEXT',
                'Shipping_Phone' => 'TEXT',
                'Billing_Name' => 'TEXT',
                'Billing_Address' => 'TEXT',
                'Billing_Address_2' => 'TEXT',
                'Billing_Postal_Code' => 'TEXT',
                'Billing_City' => 'TEXT',
                'Billing_Region' => 'TEXT',
                'Billing_Country' => 'TEXT',
                'Product_Id' => 'TEXT',
                'Product_SKU' => 'TEXT',
                'Product_Name' => 'TEXT',
                'Product_Options' => 'TEXT',
                'Product_Quantity' => 'TEXT',
                'Product_Price' => 'TEXT',
                'Product_Sale_Price' => 'TEXT',
                'Product_Total_Price' => 'TEXT',
                'Product_Taxable' => 'TEXT',
            ),
        );
    }

    public function orderidsTableConstruct(){
        return array(
            'table' => self::WBY_ORD_ID,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'Order_ID' => 'VARCHAR(255)',
                'Status' => 'VARCHAR(255)',
            ),
        );
    }

    protected function _clearStorageCsv(){
        $tables = $this->_getTablesTmp();
        $folder = $this->_folder;
        foreach($tables as $table){
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

    protected function _storageCsvProducts(){
        return $this->_storageCsvByType('products', 'orders', false, false, array('id'));
    }

    protected function _storageCsvOrders(){
        return $this->_storageCsvByType('orders', 'orders', false, true);
    }

    protected function _storageCsvByType($type, $next, $success = false, $finish = false, $unset = array()){
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
        $csv_file = Mage::getBaseDir('media'). self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
        $readCsv = $this->readCsv($csv_file, $start, $this->_notice['setting']['csv'], $demo);
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
                        'msg' => $this->consoleError('Could not import csv to database.')
                    );
                }
                if ($type == 'products'){
                    if ($data['CATEGORIES']){
                        $categories = explode(', ', $data['CATEGORIES']);
                        foreach ($categories as $category){
                            $check_cat = $this->selectTable(self::WBY_CAT, array(
                                'folder' => $this->_folder,
                                'name' => $category,
                            ));
                            if (empty($check_cat)){
                                $this->insertTable(self::WBY_CAT, array(
                                    'folder' => $this->_folder,
                                    'domain' => $this->_cart_url,
                                    'name' => $category,
                                    'code' => str_replace(' ', '', strtolower($category)),
                                ));
                            }
                        }
                    }
                }
                if ($type == 'orders'){
                    $check_cus = $this->selectTable(self::WBY_CUS, array(
                        'folder' => $this->_folder,
                        'Shipping_Email' => $data['Shipping_Email']
                    ));
                    if (empty($check_cus)){
                        $cus_data = array(
                            'folder' => $this->_folder,
                            'domain' => $this->_cart_url,
                            'Date' => $data['Date'],
                            'Shipping_First_Name' => $data['Shipping_First_Name'],
                            'Shipping_Last_Name' => $data['Shipping_Last_Name'],
                            'Shipping_Email' => $data['Shipping_Email'],
                            'Shipping_Address' => $data['Shipping_Address'],
                            'Shipping_Address_2' => $data['Shipping_Address_2'],
                            'Shipping_Postal_Code' => $data['Shipping_Postal_Code'],
                            'Shipping_City' => $data['Shipping_City'],
                            'Shipping_Region' => $data['Shipping_Region'],
                            'Shipping_Country' => $data['Shipping_Country'],
                            'Shipping_Phone' => $data['Shipping_Phone'],
                            'Billing_Name' => $data['Billing_Name'],
                            'Billing_Address' => $data['Billing_Address'],
                            'Billing_Address_2' => $data['Billing_Address_2'],
                            'Billing_Postal_Code' => $data['Billing_Postal_Code'],
                            'Billing_City' => $data['Billing_City'],
                            'Billing_Region' => $data['Billing_Region'],
                            'Billing_Country' => $data['Billing_Country'],
                        );
                        $this->insertTable(self::WBY_CUS, $cus_data);
                    }
                    $check_order_id = $this->selectTable(self::WBY_ORD_ID, array(
                        'folder' => $this->_folder,
                        'Order_ID' => $data['Order_ID']
                    ));
                    if (empty($check_order_id)){
                        $this->insertTable(self::WBY_ORD_ID, array(
                            'folder' => $this->_folder,
                            'domain' => $this->_cart_url,
                            'Order_ID' => $data['Order_ID'],
                            'Status' => $data['Status'],
                        ));
                    }
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

    public function readCsv($file_path, $start, $limit = 10, $total = false){
        if(!is_file($file_path)){
            return array(
                'result' => 'error',
                'msg' => 'Path not exists'
            );
        }
        try{
            $finish = false;
            $count = 0;
            $csv = fopen($file_path, 'r');
            $end = $start + $limit;
            $csv_title = "";
            $data = array();
            while (!feof($csv)){
                if($total && $count > $total){
                    $finish = true;
                    break ;
                }
                if($count > $end){
                    break ;
                }
                $line = fgetcsv($csv);
                if ($count == 0) {
                    $csv_title = $line;
                }
                if($start < $count && $count <= $end){
                    $data[] = array(
                        'title' => str_replace('#', 'ID', str_replace(' ', '_', $csv_title)),
                        'row' => $line
                    );
                }
                $count++;
            }
            fclose($csv);
            if(!$finish && ($count - 1) <$end){
                $finish = true;
            }
            return array(
                'result' => 'success',
                'data' => $data,
                'count' => $end,
                'finish' => $finish
            );
        } catch (Exception $e){
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
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

    protected function _getTablesTmp(){
        return array(
            self::WBY_CAT,
            self::WBY_CUS,
            self::WBY_ORD,
            self::WBY_ORD_ID,
            self::WBY_PRO,
        );
    }
}