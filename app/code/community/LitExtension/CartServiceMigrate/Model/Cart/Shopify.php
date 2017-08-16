<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartServiceMigrate_Model_Cart_Shopify
    extends LitExtension_CartServiceMigrate_Model_Cart {

    protected $_api_url = null;

    public function __construct(){
        parent::__construct();
    }
    
    public function importManufacturer($data, $manufacturer, $manufacturersExt) {
        if(LitExtension_CartServiceMigrate_Model_Custom::MANUFACTURER_IMPORT){
            return $this->_custom->importManufacturerCustom($this, $data, $manufacturer, $manufacturersExt);
        }
        $id_src = $this->getManufacturerId($manufacturer, $manufacturersExt);
        $manufacturerIpt = $this->_process->manufacturer($data);
        if($manufacturerIpt['result'] == "success"){
            $id_desc = $manufacturerIpt['mage_id'];
            $this->manufacturerSuccess($id_src, $id_desc, $manufacturer['name']);
        } else {
            $manufacturerIpt['result'] = "warning";
            $msg = "Manufacturer Id = " . $id_src . " import failed. Error: " . $manufacturerIpt['msg'];
            $manufacturerIpt['msg'] = $this->consoleWarning($msg);
        }
        return $manufacturerIpt;
    }

    /**
     * Get info of api for user config
     */
    public function getApiData(){
        return array(
            'api_key' => "API Key",
            'password' => "Password"
        );
    }

    /**
     * Process and get data use for config display
     *
     * @return array : Response as success or error with msg
     */
    public function displayConfig(){
        $parent = parent::displayConfig();
        if($parent['result'] != "success"){
            return $parent;
        }
        $response = array();
        $api_shop = $this->api('/admin/shop.json');
        if(!$api_shop){
            return array(
                'result' => 'warning',
                'elm' => '#error-api'
            );
        }
        $shop = json_decode($api_shop, 1);
        $currency = isset($shop['shop']['currency']) ? $shop['shop']['currency'] : "USD";
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data = array(
            1 => "Default Language"
        );
        $order_status_data = array(
            'pending' => "Pending",
            'authorized' => "Authorized",
            'partially_paid' => "Partially Paid",
            'paid' => "Paid",
            'partially_refunded' => "Partially Refunded",
            'refunded' => "Refunded",
            'voided' => "Voided"
        );
        $this->_notice['config']['api_data'] = $this->getApiData();
        $this->_notice['config']['import_support']['taxes'] = false;
        $this->_notice['config']['import_support']['reviews'] = false;
        $this->_notice['config']['import_support']['manufacturers'] = false;
        $this->_notice['config']['config_support']['country_map'] = false;
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['default_currency'] = $currency;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = array($currency => $currency);
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $response['result'] = 'success';
        return $response;
    }

    /**
     * Save config of use in config step to notice
     */
    public function displayConfirm($params){
        $parent = parent::displayConfirm($params);
        if($parent['result'] != "success"){
            return $parent;
        }
        return array(
            'result' => "success"
        );
    }

    /**
     * Get data for import display
     *
     * @return array : Response as success or error with msg
     */
    public function displayImport(){
        $parent = parent::displayImport();
        if($parent['result'] != "success"){
            return $parent;
        }
        $recent = $this->getRecentNotice();
        if($recent){
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews');
            foreach($types as $type){
                if($this->_notice['config']['add_option']['add_new'] || !$this->_notice['config']['import'][$type]){
                    $this->_notice[$type]['id_src'] = $recent[$type]['id_src'];
                    $this->_notice[$type]['imported'] = $recent[$type]['imported'];
                }
            }
        }
        $list_products = $this->api('/admin/products.json');
        $custom_collections_api = $this->api('/admin/custom_collections/count.json');
        $smart_collection_api = $this->api('/admin/smart_collections/count.json');
        $products_api = $this->api('/admin/products/count.json');
        $customers_api = $this->api('/admin/customers/count.json');
        $orders_api = $this->api('/admin/orders/count.json?status=any');
        if(!$custom_collections_api || !$list_products || !$smart_collection_api || !$products_api || !$customers_api || !$orders_api){
            return array(
                'result' => 'error',
                'msg' => "Could not get data from shopify"
            );
        }
        $vendors = json_decode($list_products, 1);
        $custom_collections = json_decode($custom_collections_api, 1);
        $smart_collections = json_decode($smart_collection_api, 1);
        $products = json_decode($products_api, 1);
        $customers = json_decode($customers_api, 1);
        $orders = json_decode($orders_api, 1);
        $vendors_unique = $this->duplicateFieldValueFromList($vendors['products'], 'vendor');
        $vendors_unique = array_filter($vendors_unique);
        $ven_count = count($vendors_unique);
        $cat_cus_count = isset($custom_collections['count']) ? $custom_collections['count'] : 0;
        $cat_smrt_count = isset($smart_collections['count']) ? $smart_collections['count'] : 0;
        $cat_count = $cat_cus_count + $cat_smrt_count;
        $pro_count = isset($products['count']) ? $products['count'] : 0;
        $cus_count = isset($customers['count']) ? $customers['count'] : 0;
        $ord_count = isset($orders['count']) ? $orders['count'] : 0;
        $totals = array(
            'taxes' => 1,
            'manufacturers' => $ven_count,
            'categories' => $cat_count,
            'products' => $pro_count,
            'customers' => $cus_count,
            'orders' => $ord_count,
            'reviews' => 0
        );
        $iTotal = $this->_limitDemoModel($totals);
        foreach($iTotal as $type => $total){
            $this->_notice[$type]['total'] = $total;
        }
        $this->_notice['categories']['custom_count'] = $cat_cus_count;
        $this->_notice['categories']['smart_count'] = $cat_smrt_count;
        $this->_notice['taxes']['time_start'] = time();
        if(!$this->_notice['config']['add_option']['add_new']){
            $delete = $this->deleteTable(self::TABLE_IMPORT, array(
                'domain' => $this->_cart_url
            ));
            if(!$delete){
                return $this->errorDatabase(true);
            }
        }
        return array(
            'result' => 'success'
        );
    }

    /**
     * Config currency
     */
    public function configCurrency(){
        $parent = parent::configCurrency();
        if($parent['result'] != "success"){
            return $parent;
        }
        return array(
            'result' => "success"
        );
    }

    /**
     * Process before import taxes
     */
    public function prepareImportTaxes(){
        parent::prepareImportTaxes();
        $tax_cus = $this->getTaxCustomerDefault();
        if ($tax_cus['result'] == 'success') {
            $this->taxCustomerSuccess(1, $tax_cus['mage_id']);
        }
    }

    /**
     * Get data of table convert to tax rule
     *
     * @return array
     */
    public function getTaxesMain(){
        $taxes = array(
            'id' => '1',
            'code' => 'Tax Rule Shopify'
        );
        return array(
            'result' => "success",
            'data' => array($taxes)
        );
    }

    /**
     * Get data relation use for import tax rule
     *
     * @param array $taxes
     * @return array
     */
    public function getTaxesExtMain($taxes){
        $taxRates = $this->api('/admin/countries.json');
        if(!$taxRates){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Shopify")
            );
        }
        $tax_rates = json_decode($taxRates, 1);
        return array(
            'result' => "success",
            'data' => $tax_rates['countries']
        );
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax
     * @param array $taxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt){
        return $tax['id'];
    }

    /**
     * Convert source data to data for import
     *
     * @param array $tax
     * @param array $taxesExt
     * @return array
     */
    public function convertTax($tax, $taxesExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::TAX_CONVERT){
            return $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        }
        $tax_cus_ids = $tax_pro_ids = $tax_rate_ids = array();
        if ($tax_cus_default = $this->getIdDescTaxCustomer(1)) {
            $tax_cus_ids[] = $tax_cus_default;
        }
        $tax_pro_data = array(
            'class_name' => 'Product Tax Class Shopify'
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess(1, $tax_pro_ipt['mage_id']);
        }
        foreach ($taxesExt as $tax_rate) {
            if ($tax_rate['tax'] == '0' || $tax_rate['code'] == '*') {
                continue;
            }
            $tax_rate_data = array();
            $code = $tax_rate['code'] . '-All States-' . $tax_rate['tax_name'];
            $tax_rate_data['code'] = $this->createTaxRateCode($code);
            $tax_rate_data['tax_country_id'] = $tax_rate['code'];
            $tax_rate_data['tax_region_id'] = 0;
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = "*";
            $tax_rate_data['rate'] = $tax_rate['tax'];
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if ($tax_rate_ipt['result'] == 'success') {
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['code']);
        $tax_rule_data['tax_customer_class'] = $tax_cus_ids;
        $tax_rule_data['tax_product_class'] = $tax_pro_ids;
        $tax_rule_data['tax_rate'] = $tax_rate_ids;
        $tax_rule_data['priority'] = 0;
        $tax_rule_data['position'] = 0;
        $custom = $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        if ($custom) {
            $tax_rule_data = array_merge($tax_rule_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $tax_rule_data
        );
    }

    /**
     * Process after import success one row of tax main
     *
     * @param int $tax_mage_id
     * @param array $data
     * @param array $tax
     * @param array $taxesExt
     * @return boolean
     */
    public function afterSaveTax($tax_mage_id, $data, $tax, $taxesExt){
        if(parent::afterSaveTax($tax_mage_id, $data, $tax, $taxesExt)){
            return ;
        }

    }

    /**
     * Process before import manufacturers
     */
    public function prepareImportManufacturers(){
        parent::prepareImportManufacturers();
        $man_attr = $this->getManufacturerAttributeId($this->_notice['config']['attribute_set_id']);
        if ($man_attr['result'] == 'success') {
            $this->manAttrSuccess(1, $man_attr['mage_id']);
        }
    }

    /**
     * Get data for convert to manufacturer option
     *
     * @return array
     */
    public function getManufacturersMain(){
        $imported = $this->_notice['manufacturers']['imported'];
        $limit = $this->_notice['setting']['manufacturers'];
        $page = floor($imported/$limit) + 1;
        $list_products = $this->api('/admin/products.json?page=' . $page . '&limit=' . $limit);
        if(!$list_products){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Shopify")
            );
        }
        $products = json_decode($list_products, 1);
        $vendors_unique = $this->duplicateFieldValueFromList($products['products'], 'vendor');
        $vendors_unique = array_filter($vendors_unique);
        $vendors = array();
        foreach ($vendors_unique as $id => $value) {
            $vendors[] = array(
                'id' => $id,
                'name' => $value
            );
        }
        return array(
            'result' => "success",
            'data' => $vendors
        );
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers
     * @return array
     */
    public function getManufacturersExtMain($manufacturers){
        return false;
    }

    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer
     * @param array $manufacturersExt
     * @return int
     */
    public function getManufacturerId($manufacturer, $manufacturersExt){
        return $manufacturer['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer
     * @param array $manufacturersExt
     * @return array
     */
    public function convertManufacturer($manufacturer, $manufacturersExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::MANUFACTURER_CONVERT){
            return $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        }
        $man_attr_id = $this->getIdDescManAttr(1);
        if (!$man_attr_id) {
            return array(
                'result' => 'error',
                'msg' => $this->consoleError("Could not create manufacturer attribute!")
            );
        }
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id
        );
        $manufacturer_data['value']['option'] = array(
            0 => $manufacturer['name']
        );
        $custom = $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        if ($custom) {
            $manufacturer_data = array_merge($manufacturer_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $manufacturer_data
        );
    }

    /**
     * Process after one manufacturer import successful
     *
     * @param int $manufacturer_mage_id
     * @param array $data
     * @param array $manufacturer
     * @param array $manufacturersExt
     * @return boolean
     */
    public function afterSaveManufacturer($manufacturer_mage_id, $data, $manufacturer, $manufacturersExt){
        if(parent::afterSaveManufacturer($manufacturer_mage_id, $data, $manufacturer, $manufacturersExt)){
            return ;
        }
    }

    /**
     * Process before import categories
     */
    public function prepareImportCategories(){
        parent::prepareImportCategories();
    }

    /**
     * Get data of main table use import category
     *
     * @return array
     */
    public function getCategoriesMain(){
        $imported = $this->_notice['categories']['imported'];
        $limit = $this->_notice['setting']['categories'];
        if ($this->_notice['categories']['imported'] < $this->_notice['categories']['custom_count']) {
            $page = floor($imported/$limit) + 1;
            $collections = $this->api('/admin/custom_collections.json?page=' . $page . '&limit=' . $limit);
            if(!$collections){
                return array(
                    'result' => "error",
                    'msg' => $this->consoleError("Could not get data from Shopify")
                );
            }
            $cusCollection = json_decode($collections, 1);
            $allCollection = $cusCollection['custom_collections'];
        } else {
            $smrt_imported = $this->_notice['categories']['imported'] - $this->_notice['categories']['custom_count'];
            $page = floor($smrt_imported/$limit) + 1;
            $collection = $this->api('/admin/smart_collections.json?page=' . $page . '&limit=' . $limit);
            if(!$collection){
                return array(
                    'result' => "error",
                    'msg' => $this->consoleError("Could not get data from Shopify")
                );
            }
            $smartCollection = json_decode($collection, 1);
            $allCollection = $smartCollection['smart_collections'];
        }
        return array(
            'result' => "success",
            'data' => $allCollection
        );
    }

    /**
     * Get data relation use for import categories
     *
     * @param array $categories
     * @return array
     */
    public function getCategoriesExtMain($categories){
        return false;
    }

    /**
     * Get primary key of source category
     *
     * @param array $category
     * @param array $categoriesExt
     * @return int
     */
    public function getCategoryId($category, $categoriesExt){
        return $category['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $category
     * @param array $categoriesExt
     * @return array
     */
    public function convertCategory($category, $categoriesExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::CATEGORY_CONVERT){
            return $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        }
        $cat_parent_id = $this->_notice['config']['root_category_id'];
        $cat_data = array();
        $cat_data['name'] = $category['title'];
        $cat_data['description'] = $category['body_html'];
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['published_at'] ? 1 : 0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        if (isset($category['image']['src'])) {
            $real_path = preg_replace("/\?.+/", "", $category['image']['src']);
            $occ = explode("/", $real_path);
            $image = array_pop($occ);
            $url = implode("/", $occ);
            $image_path = $this->downloadImage($url, $image, 'catalog/category');
            if ($image_path) {
                $cat_data['image'] = $image_path;
            }
        }
        if($this->_seo){
            $seo = $this->_seo->convertCategorySeo($this, $category, $categoriesExt);
            if($seo){
                $cat_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        if($custom){
            $cat_data = array_merge($cat_data, $custom);
        }
        return array(
            'result' => "success",
            'data' => $cat_data
        );
    }

    /**
     * Process after one category import successful
     *
     * @param int $category_mage_id
     * @param array $data
     * @param array $category
     * @param array $categoriesExt
     * @return boolean
     */
    public function afterSaveCategory($category_mage_id, $data, $category, $categoriesExt){
        if(parent::afterSaveCategory($category_mage_id, $data, $category, $categoriesExt)){
            return ;
        }
    }

    /**
     * Process before import products
     */
    public function prepareImportProducts(){
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    /**
     * Get data of main table use for import product
     *
     * @return array
     */
    public function getProductsMain(){
        $imported = $this->_notice['products']['imported'];
        $limit = $this->_notice['setting']['products'];
        $page = floor($imported/$limit) + 1;
        $products = $this->api('/admin/products.json?page=' . $page . '&limit=' . $limit);
        if(!$products){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Shopify")
            );
        }
        $productsPage = json_decode($products, 1);
        return array(
            'result' => "success",
            'data' => $productsPage['products']
        );
    }

    /**
     * Get data relation use for import product
     *
     * @param array $products
     * @return array
     */
    public function getProductsExtMain($products){
        $extend = array();
        foreach ($products['data'] as $product) {
//            $now_time = microtime(true);
//            $duration = $this->_getDurationTime($now_time);
//            if ($duration === false) {
//                return array(
//                    'result' => "error",
//                    'msg' => $this->consoleError("Could not get duration Time")
//                );
//            }
//            if ($duration < 500000) {
//                usleep(500000 - $duration);
//            }
            $meta = $this->api("/admin/products/" . $product['id'] . "/metafields.json");
            $cat_cus = $this->api('/admin/custom_collections.json?product_id=' . $product['id']);
            $cat_smart = $this->api('/admin/smart_collections.json?product_id=' . $product['id']);
//            $start_time = microtime(true);
//            $this->_setStartTime($start_time);
            if (!$meta || !$cat_cus || !$cat_smart) {
                return array(
                    'result' => "error",
                    'msg' => $this->consoleError("Could not get data from Shopify")
                );
            }
            $meta_to_array = json_decode($meta, 1);
            $cat_to_array_cus = json_decode($cat_cus, 1);
            $cat_to_array_smart = json_decode($cat_smart, 1);
            $extend[$product['id']]['meta'] = $meta_to_array['metafields'];
            $extend[$product['id']]['custom_category'] = $cat_to_array_cus['custom_collections'];
            $extend[$product['id']]['smart_category'] = $cat_to_array_smart['smart_collections'];
        }
        return array(
            'result' => "success",
            'data' => $extend
        );
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product
     * @param array $productsExt
     * @return int
     */
    public function getProductId($product, $productsExt){
        return $product['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $product
     * @param array $productsExt
     * @return array
     */
    public function convertProduct($product, $productsExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $result = array();
        $count_children = count($product['variants']);
        if ($count_children == 1) {
            $result['type_id'] = 'simple';
        } elseif ($count_children > 1) {
            $result['type_id'] = 'configurable';
            $config_data = $this->_importChildrenProduct($product, $productsExt, $product['variants']);
            if (!$config_data) {
                return array(
                    'result' => 'warning',
                    'msg' => $this->consoleWarning("Product ID = {$product['id']} import failed. Error: Product's child could not create!"),
                );
            }
            $result = array_merge($config_data, $result);
        }
        $result = array_merge($this->_convertProduct($product, $productsExt), $result);
        return array(
            'result' => 'success',
            'data' => $result
        );
    }

    protected function _convertProduct($product, $productsExt){
        $pro_data = array();
        $categories = array();
        if (isset($product['id'])) {
            if ($pro_to_cat = $productsExt['data']['main'][$product['id']]['custom_category']) {
                foreach ($pro_to_cat as $collection) {
                    $cat_id = $this->getIdDescCategory($collection['id']);
                    if ($cat_id) {
                        $categories[] = $cat_id;
                    }
                }
            }
            if ($pro_to_cat_smart = $productsExt['data']['main'][$product['id']]['smart_category']) {
                foreach ($pro_to_cat_smart as $collection) {
                    $cat_id = $this->getIdDescCategory($collection['id']);
                    if ($cat_id) {
                        $categories[] = $cat_id;
                    }
                }
            }
        }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_data['url_key'] = $product['handle'];
        if ($product['variants'][0]['sku']) {
            $pro_data['sku'] = $this->createProductSku($product['variants'][0]['sku'], $this->_notice['config']['languages']);
        } else {
            $pro_data['sku'] = $this->createProductSku($product['handle'], $this->_notice['config']['languages']);
        }
        $pro_data['name'] = $product['title'];
        $pro_data['description'] = $this->changeImgSrcInText($product['body_html'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['body_html'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['price'] = $product['variants'][0]['price'] ? $product['variants'][0]['price'] : 0;
        $pro_data['weight'] = $product['variants'][0]['grams'] ? $product['variants'][0]['grams']/1000 : 0;
        if (!isset($product['id'])) {
            $pro_data['status'] = 1;
        } else {
            $pro_data['status'] = $product['published_at'] ? 1 : 2;
        }
        if ($product['variants'][0]['taxable']) {
            $pro_data['tax_class_id'] = ($this->getIdDescTaxProduct(1))? $this->getIdDescTaxProduct(1) : 0;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $create_at = $this->_convertDateTime($product['variants'][0]['created_at']);
        $pro_data['created_at'] = $create_at;
        $pro_data['visibility'] = isset($product['id']) ? 4 : 1;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'backorders' => ($product['variants'][0]['inventory_policy'] == 'continue') ? 1 : 0,
            'manage_stock' => ($product['variants'][0]['inventory_management']) ? 1 : 0,
            'use_config_manage_stock' => ($product['variants'][0]['inventory_management']) ? 1 : 0,
            'qty' => $product['variants'][0]['inventory_management'] ? $product['variants'][0]['inventory_quantity'] : 999,
        );
        if ($manufacture_mage_id = $this->_getIdDescManufacturerByName($product['vendor'])) {
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        if (isset($product['id'])) {
            if (isset($product['image']['src'])) {
                $real_path = preg_replace("/\?.+/", "", $product['image']['src']);
                $occ = explode("/", $real_path);
                $image = array_pop($occ);
                $url = implode("/", $occ);
                $image_path = $this->downloadImage($url, $image, 'catalog/product', false, true);
                if ($image_path) {
                    $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                }
            }
            if (isset($product['images'])) {
                foreach ($product['images'] as $image) {
                    if ($image['id'] == $product['image']['id'])
                        continue;
                    $real_path = preg_replace("/\?.+/", "", $image['src']);
                    $occ = explode("/", $real_path);
                    $image_gal = array_pop($occ);
                    $url = implode("/", $occ);
                    if ($image_path = $this->downloadImage($url, $image_gal, 'catalog/product', false, true)) {
                        $pro_data['image_gallery'][] = array('path' => $image_path, 'label' => '');
                    }
                }
            }
        } elseif (isset($product['variants'][0]['image_id']) && $product['variants'][0]['image_id']) {
            foreach ($product['images'] as $image) {
                if ($image['id'] == $product['variants'][0]['image_id']) {
                    $real_path = preg_replace("/\?.+/", "", $image['src']);
                    $occ = explode("/", $real_path);
                    $image_gal = array_pop($occ);
                    $url = implode("/", $occ);
                    if ($image_path = $this->downloadImage($url, $image_gal, 'catalog/product', false, true)) {
                        $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                    }
                }
            }
        }
        //$pro_data['meta_keyword'] = $product['tags'];
        if (isset($product['id'])) {
            if ($productsExt['data']['main'][$product['id']]['meta']) {
                foreach ($productsExt['data']['main'][$product['id']]['meta'] as $metafields) {
                    if ($metafields['key'] == 'description_tag') {
                        $pro_data['meta_description'] = $metafields['value'];
                    } elseif ($metafields['key'] == 'title_tag') {
                        $pro_data['meta_title'] = $metafields['value'];
                    }
                }
            } else {
                $pro_data['meta_title'] = $product['title'];
            }
        }
        if ($this->_seo && isset($product['id'])) {
            $seo = $this->_seo->convertProductSeo($this, $product, $productsExt);
            if ($seo) {
                $pro_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product, $productsExt);
        if ($custom) {
            $pro_data = array_merge($pro_data, $custom);
        }
        return $pro_data;
    }

    /**
     * Process after one product import successful
     *
     * @param int $product_mage_id
     * @param array $data
     * @param array $product
     * @param array $productsExt
     * @return boolean
     */
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt){
        if(parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)){
            return ;
        }
        if ($product['vendor']) {
            $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
            $attr_import = $this->_manufacturerImport($product['vendor'], $entity_type_id);
            $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
            if ($attr_after) {
                $this->setProAttrSelect($entity_type_id, $attr_after['attribute_id'], $product_mage_id, $attr_after['option_ids']['option_0']);
            }
        }
        $tags = explode(",", trim($product['tags']));
        foreach ($tags as $name) {
            if ($name) {
                $this->_addProductTag($name, $product_mage_id, null, $this->_notice['config']['languages'][$this->_notice['config']['default_lang']]);
            }
        }
    }

    /**
     * Process before import import customers
     */
    public function prepareImportCustomers(){
        parent::prepareImportCustomers();
    }

    /**
     * Get data of main table use for import customer
     *
     * @return array
     */
    public function getCustomersMain(){
        $imported = $this->_notice['customers']['imported'];
        $limit = $this->_notice['setting']['customers'];
        $page = floor($imported/$limit) + 1;
        $customers = $this->api('/admin/customers.json?page=' . $page . '&limit=' . $limit);
        if(!$customers){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Shopify")
            );
        }
        $customer_list = json_decode($customers, 1);
        return array(
            'result' => "success",
            'data' => $customer_list['customers']
        );
    }

    /**
     * Get data relation use for import customer
     *
     * @param array $customers
     * @return array
     */
    public function getCustomersExtMain($customers){
        return false;
    }

    /**
     * Get primary key of source customer main
     *
     * @param array $customer
     * @param array $customersExt
     * @return int
     */
    public function getCustomerId($customer, $customersExt){
        return $customer['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $customer
     * @param array $customersExt
     * @return array
     */
    public function convertCustomer($customer, $customersExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::CUSTOMER_CONVERT){
            return $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        }
        $cus_data = array();
        if ($this->_notice['config']['add_option']['pre_cus']) {
            $cus_data['id'] = $customer['id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['is_subscribed'] = $customer['accepts_marketing'] ? 1 : 0;
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['first_name'];
        $cus_data['lastname'] = $customer['last_name'] ? $customer['last_name'] : $customer['first_name'];
        $cus_data['created_at'] = $this->_convertDateTime($customer['created_at']);
        $cus_data['updated_at'] = $this->_convertDateTime($customer['updated_at']);
        $cus_data['group_id'] = 1;
        $custom = $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        if ($custom) {
            $cus_data = array_merge($cus_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $cus_data
        );
    }

    /**
     * Process after one customer import successful
     *
     * @param int $customer_mage_id
     * @param array $data
     * @param array $customer
     * @param array $customersExt
     * @return boolean
     */
    public function afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt){
        if(parent::afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt)){
            return ;
        }
        foreach ($customer['addresses'] as $address_src) {
            if (!$address_src['address1'] && !$address_src['address2']) {
                continue;
            }
            $address = array();
            $address['firstname'] = $address_src['first_name'] ? $address_src['first_name'] : $address_src['last_name'];
            $address['lastname'] = $address_src['last_name'] ? $address_src['last_name'] : $address_src['first_name'];
            $address['country_id'] = $address_src['country_code'];
            $address['street'] = $address_src['address1'] . "\n" . $address_src['address2'];
            $address['postcode'] = $address_src['zip'];
            $address['city'] = $address_src['city'];
            $address['telephone'] = $address_src['phone'];
            $address['company'] = $address_src['company'];
            if ($address_src['province_code']) {
                $address['region_id'] = $this->getRegionId($address_src['province'], $address_src['province_code']);
                $address['region'] = $address_src['province'];
            } else {
                $address['region'] = $address_src['province'];
            }
            $address_ipt = $this->_process->address($address, $customer_mage_id);
            if ($address_ipt['result'] == 'success' && $address_src['default']) {
                try {
                    $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                    $cus->setDefaultBilling($address_ipt['mage_id']);
                    $cus->setDefaultShipping($address_ipt['mage_id']);
                    $cus->save();
                } catch (Exception $e) {
                    
                }
            }
        }
    }

    /**
     * Process before import orders
     */
    public function prepareImportOrders(){
        parent::prepareImportOrders();
    }

    /**
     * Get data use for import order
     *
     * @return array
     */
    public function getOrdersMain(){
        $imported = $this->_notice['orders']['imported'];
        $limit = $this->_notice['setting']['orders'];
        $page = floor($imported/$limit) + 1;
        $orders = $this->api('/admin/orders.json?status=any&page=' . $page . '&limit=' . $limit);
        if(!$orders){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Shopify")
            );
        }
        $order_list = json_decode($orders, 1);
        return array(
            'result' => "success",
            'data' => $order_list['orders']
        );
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders
     * @return array
     */
    public function getOrdersExtMain($orders){
        return false;
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order
     * @param array $ordersExt
     * @return int
     */
    public function getOrderId($order, $ordersExt){
        return $order['order_number'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order
     * @param array $ordersExt
     * @return array
     */
    public function convertOrder($order, $ordersExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();
        
        $bill_addr = $order['billing_address'];
        $address_billing['firstname'] = $bill_addr['first_name'];
        $address_billing['lastname'] = $bill_addr['last_name'];
        $address_billing['company'] = $bill_addr['company'];
        $address_billing['email'] = $order['email'];
        $address_billing['street'] = $bill_addr['address1'] . "\n" . $bill_addr['address2'];
        $address_billing['city'] = $bill_addr['city'];
        $address_billing['postcode'] = $bill_addr['zip'];
        $address_billing['telephone'] = $bill_addr['phone'];
        $address_billing['country_id'] = $bill_addr['country_code'];
        $address_billing['region'] = $bill_addr['province'];
        $address_billing['region_id'] = $bill_addr['province_code'];
        
        $ship_addr = $order['shipping_address'];
        $address_shipping['firstname'] = $ship_addr['first_name'];
        $address_shipping['lastname'] = $ship_addr['last_name'];
        $address_shipping['company'] = $ship_addr['company'];
        $address_shipping['email'] = $order['email'];
        $address_shipping['street'] = $ship_addr['address1'] . "\n" . $ship_addr['address2'];
        $address_shipping['city'] = $ship_addr['city'];
        $address_shipping['postcode'] = $ship_addr['zip'];
        $address_shipping['telephone'] = $ship_addr['phone'];
        $address_shipping['country_id'] = $ship_addr['country_code'];
        $address_shipping['region'] = $ship_addr['province'];
        $address_shipping['region_id'] = $ship_addr['province_code'];
        
        $carts = array();
        foreach ($order['line_items'] as $item) {
            $cart = array();
            $product_id = $this->getIdDescProduct($item['product_id']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $tax_amount = $tax_percent = 0;
            if ($item['taxable'] && $item['tax_lines']) {
                foreach ($item['tax_lines'] as $tax) {
                    $tax_amount += $tax['price'];
                    $tax_percent += $tax['rate'];
                }
            }
            $cart['type_id'] = 'simple';
            $cart['name'] = $item['name'];
            $cart['sku'] = $item['sku'];
            $cart['price'] = $item['price'];
            $cart['original_price'] = $item['price'];
            $cart['tax_amount'] = $tax_amount;
            $cart['tax_percent'] = $tax_percent * 100;
            $cart['discount_amount'] = 0;
            $cart['qty_ordered'] = $item['quantity'];
            $cart['row_total'] = $item['price'] * $item['quantity'];
            $carts[] = $cart;
        }
        
        $order_data = array();
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $customer_id = $this->getIdDescCustomer($order['customer']['id']);
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['customer']['email'];
        $order_data['customer_firstname'] = $order['customer']['first_name'];
        $order_data['customer_lastname'] = $order['customer']['last_name'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order['financial_status']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['subtotal_price']);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $shipping_amount = 0;
        foreach ($order['shipping_lines'] as $ship) {
            $shipping_amount += $ship['price'];
        }
        $order_data['shipping_amount'] = $shipping_amount;
        $order_data['base_shipping_amount'] = $shipping_amount;
        $order_data['base_shipping_invoiced'] = $shipping_amount;
        $order_data['shipping_description'] = $order['shipping_lines'][0]['title'];
        if ($order['total_tax']) {
            $order_data['tax_amount'] = $order['total_tax'];
            $order_data['base_tax_amount'] = $order['total_tax'];
        }
        $order_data['discount_amount'] = $order['total_discounts'];
        $order_data['base_discount_amount'] = $order['total_discounts'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['total_price']);
        $order_data['base_grand_total'] = $order_data['grand_total'];
        $order_data['base_total_invoiced'] = $order_data['grand_total'];
        $order_data['total_paid'] = $order_data['grand_total'];
        $order_data['base_total_paid'] = $order_data['grand_total'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $order['currency'];
        $order_data['global_currency_code'] = $order['currency'];
        $order_data['store_currency_code'] = $order['currency'];
        $order_data['order_currency_code'] = $order['currency'];
        $order_data['created_at'] = $this->_convertDateTime($order['created_at']);
        $order_data['updated_at'] = $this->_convertDateTime($order['updated_at']);

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['order_number'];
        $custom = $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => "success",
            'data' => $data
        );
    }

    /**
     * Process after one order save successful
     *
     * @param int $order_mage_id
     * @param array $data
     * @param array $order
     * @param array $ordersExt
     * @return boolean
     */
    public function afterSaveOrder($order_mage_id, $data, $order, $ordersExt){
        if(parent::afterSaveOrder($order_mage_id, $data, $order, $ordersExt)){
            return ;
        }
        foreach ($order['note_attributes'] as $key => $order_status) {
            $order_status_data = array();
            $order_status_id = $order['financial_status'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if ($key == 0) {
                $order_status_data['comment'] = "<b>Reference order #" . $order['order_number'] . "</b><br /><b>Payment method: </b>" . $order['processing_method'] . "<br /><b>Shipping method: </b> " . $order['shipping_lines'][0]['title'] . "<br /><br />" . $order_status['value'];
            } else {
                $order_status_data['comment'] = $order_status['value'];
            }
            $order_status_data['is_customer_notified'] = 1;
            $order_status_data['updated_at'] = $this->_convertDateTime($order['created_at']);
            $order_status_data['created_at'] = $this->_convertDateTime($order['created_at']);
            $this->_process->ordersComment($order_mage_id, $order_status_data);
        }
        $order_status_data = array();
        $order_status_id = $order['financial_status'];
        $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
        if ($order_status_data['status']) {
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['comment'] = $order['note'];
        $order_status_data['is_customer_notified'] = 0;
        $order_status_data['updated_at'] = $this->_convertDateTime($order['created_at']);
        $order_status_data['created_at'] = $this->_convertDateTime($order['created_at']);
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }

    /**
     * Process before import reviews
     */
    public function prepareImportReviews(){
        parent::prepareImportReviews();
    }

    /**
     * Get main data use for import review
     *
     * @return array
     */
    public function getReviewsMain(){
        return array(
            'result' => "success",
            'data' => array()
        );
    }

    /**
     * Get relation data use for import reviews
     *
     * @param array $reviews
     * @return array
     */
    public function getReviewsExtMain($reviews){
        return false;
    }

    /**
     * Get primary key of source review main
     *
     * @param array $review
     * @param array $reviewsExt
     * @return int
     */
    public function getReviewId($review, $reviewsExt){
        return false;
    }

    /**
     * Convert source data to data import
     *
     * @param array $review
     * @param array $reviewsExt
     * @return array
     */
    public function convertReview($review, $reviewsExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::REVIEW_CONVERT){
            return $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        }
        return array(
            'result' => "success",
            'data' => array()
        );
    }

    /**
     * Process after one review save successful
     *
     * @param int $review_mage_id
     * @param array $data
     * @param array $review
     * @param array $reviewsExt
     * @return boolean
     */
    public function afterSaveReview($review_mage_id, $data, $review, $reviewsExt){
        if(parent::afterSaveReview($review_mage_id, $data, $review, $reviewsExt)){
            return ;
        }
    }

    /**
     * TODO : Extend function
     */

    public function api($path){
        usleep(500000);
        $api_url = $this->getApiUrl();
        $url = $api_url . $path;
        return $this->request($url);
    }

    public function getApiUrl(){
        if(!$this->_api_url){
            $this->_api_url = $this->_createApiUrl();
        }
        return $this->_api_url;
    }

    protected function _createApiUrl(){
        $url = parse_url($this->_cart_url);
        $api_key = trim($this->_notice['config']['api']['api_key']);
        $password = trim($this->_notice['config']['api']['password']);
        $api_url = 'https://' . $api_key . ':' . $password . '@' . $url['host'];
        if(isset($url['path'])){
            $api_url .= $url['path'];
        }
        return $api_url;
    }
    protected function _importChildrenProduct($product, $productsExt, $children) {
        $result = false;
        $dataChildes = $pro_data_2 = array();
        $attrMage = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attribute_set_id = $this->_notice['config']['attribute_set_id'];
        foreach ($product['options'] as $option) {
            $attribute_name = $option['name'];
            foreach ($children as $child) {
                $option_name = $child['option' . $option['position']];
                $attr_import = $this->_makeAttributeImport($attribute_name, $option_name, $entity_type_id, $attribute_set_id);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if (!$attr_after) {
                    return false;
                }
                $attr_after['option_label'] = $option_name;
                $attrMage[$attr_after['attribute_id']]['attribute_label'] = $attribute_name;
                $attrMage[$attr_after['attribute_id']]['attribute_code'] = $attr_after['attribute_code'];
                $attrMage[$attr_after['attribute_id']]['values'][$attr_after['option_ids']['option_0']] = $attr_after;
                $pro_data_2[$child['id']][$attr_after['attribute_id']] = $attr_after['option_ids']['option_0'];
            }
        }
        //unset($product['image']);
        //unset($product['images']);
        unset($product['id']);
        $product_title = $product['title'];
        foreach ($children as $key => $child) {
            unset($product['variants']);
            unset($product['title']);
            $pro_data = array();
            $pro_data['type_id'] = 'simple';
            $product['variants'] = array(0 => $child);
            $name = '';
            foreach ($product['options'] as $row) {
                $name .= '-' . $child['option' . $row['position']];
            }
            $product['title'] = $product_title . $name;
            $pro_data = array_merge($this->_convertProduct($product, $productsExt), $pro_data);
            $pro_import = $this->product($pro_data);
            if ($pro_import['result'] !== 'success') {
                return false;
            }
            $optionValues = $pro_data_2[$child['id']];
            if (!empty($optionValues)) {
                foreach ($optionValues as $attr => $optionValue) {
                    $dataTMP['attribute_id'] = $attr;
                    $dataTMP['value_index'] = $optionValue;
                    $dataTMP['is_percent'] = 0;
                    $dataChildes[$pro_import['mage_id']][] = $dataTMP;
                    $this->setProAttrSelect($entity_type_id, $dataTMP['attribute_id'], $pro_import['mage_id'], $dataTMP['value_index']);
                }
            }
        }
        if ($dataChildes && $attrMage)
            $result = $this->_createConfigProductData($dataChildes, $attrMage);
        return $result;
    }
    
    protected function _createConfigProductData($dataChildes, $attrMage) {
        $attribute_config = array();
        $result['configurable_products_data'] = $dataChildes;
        foreach ($attrMage as $key => $attribute) {
            $dad = array(
                'label' => $attribute['attribute_label'],
                'attribute_id' => $key,
                'attribute_code' => $attribute['attribute_code'],
                'frontend_label' => $attribute['attribute_label'],
                'html_id' => 'config_super_product__attribute_'.$key,
            );
            $values = array();
            foreach($attribute['values'] as $option) {
                $child = array(
                    'attribute_id' => $key,
                    'is_percent' => 0,
                    'pricing_value' => '',
                    'label' => $option['option_label'],
                    'value_index' => $option['option_ids']['option_0'],
                );
                $values[] = $child;
            }
            $dad['values'] = $values;
            $attribute_config[] = $dad;
        }
        $result['configurable_attributes_data'] = $attribute_config;
        $result['can_save_configurable_attributes'] = 1;
        $result['affect_product_custom_options'] = 1;
        $result['affect_configurable_product_attributes'] = 1;
        return $result;
    }

    protected function _makeAttributeImport($attribute, $option, $entity_type_id, $attribute_set_id) {
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $this->joinTextToKey($attribute, 30, "_"),
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => 'select',
            'frontend_label' => array($attribute),
            'is_visible_on_front' => 1,
            'is_global' => 1,
            'is_configurable' => true,
            'option' => array(
                'value' => array('option_0' => array($option))
            )
        );
        $edit = array(
            'is_global' => 1,
            'is_configurable' => true,
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
        return $result;
    }
    
    protected function _manufacturerImport($option, $entity_type_id) {
        $attribute_set_id = $this->_notice['config']['attribute_set_id'];
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => 'manufacturer',
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => 'select',
            'frontend_label' => array('Manufacturer'),
            'is_visible_on_front' => 1,
            'is_global' => 1,
            'is_configurable' => false,
            'option' => array(
                'value' => array('option_0' => array($option))
            )
        );
        $edit = array();
        $result['config'] = $config;
        $result['edit'] = $edit;
        return $result;
    }
    
    protected function _getIdDescManufacturerByName($name) {
        $result = $this->selectTableRow(self::TABLE_IMPORT, array(
            'domain' => $this->_cart_url,
            'type' => self::TYPE_MANUFACTURER,
            'value' => $name
        ));
        if(!$result){
            return false;
        }
        return (isset($result['id_desc'])) ? $result['id_desc'] : false;
    }
    
    protected function _addProductTag($tag_name, $product_id, $customer_id, $store_id) {
        try {
            $tag = Mage::getModel('tag/tag');
            $tag->loadByName($tag_name);
            if (!$tag->getId()) {
                $tag->setName($tag_name)
                        ->setFirstCustomerId($customer_id)
                        ->setFirstStoreId($store_id)
                        ->setStatus($tag->getApprovedStatus())
                        ->save();
            }
            $relation = $tag->saveRelation($product_id, $customer_id, $store_id);
        } catch (Exception $ex) {

        }
    }


    protected function _setStartTime($time) {
        $session = Mage::getSingleton('admin/session');
        $session->unsLeCSTime();
        $session->setLeCSTime($time);
    }
    
    protected function _getDurationTime($time) {
        $session = Mage::getSingleton('admin/session');
        $last = $session->getLeCSTime();
        if (!$last) {
            return false;
        }
        $duration = $time - $last;
        return (float)$duration*1000000;
    }


    ########################################## Override #############################################
    
    public function importProduct($data, $product, $productsExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::PRODUCT_IMPORT){
            return $this->_custom->importProductCustom($this, $data, $product, $productsExt);
        }
        $id_src = $this->getProductId($product, $productsExt);
        $productIpt = $this->product($data);
        if($productIpt['result'] == "success"){
            $id_desc = $productIpt['mage_id'];
            $this->productSuccess($id_src, $id_desc);
        } else {
            $productIpt['result'] = "warning";
            $msg = "Product Id = " . $id_src . " import failed. Error: " . $productIpt['msg'];
            $productIpt['msg'] = $this->consoleWarning($msg);
        }
        return $productIpt;
    }
    
    public function product($data){
        $response = $multi_store = $image_import_path = $galleries = $seo_url = array();
        if(isset($data['multi_store'])){
            $multi_store = $data['multi_store'];
            unset($data['multi_store']);
        }
        if(isset($data['image_import_path'])){
            $image_import_path = $data['image_import_path'];
            unset($data['image_import_path']);
        }
        if(isset($data['image_gallery'])){
            $galleries = $data['image_gallery'];
            unset($data['image_gallery']);
        }
        if(isset($data['seo_url'])){
            $seo_url = $data['seo_url'];
            unset($data['seo_url']);
        }
        try{
            $_product = Mage::getModel("catalog/product");
            $_product->addData($data);
            if ($data['type_id'] == 'bundle') {
                Mage::register('product', $_product);
            }
            if($image_import_path && isset($image_import_path['path']) && file_exists($image_import_path['path'])){
                $_product->addImageToMediaGallery($image_import_path['path'] ,array('thumbnail', 'small_image', 'image'), true, false, $image_import_path['label']);
            }
            if($galleries){
                foreach($galleries as $gallery){
                    if(file_exists($gallery['path'])){
                        $_product->addImageToMediaGallery($gallery['path'], array(), true, false, $gallery['label']);
                    }
                }
            }
            $_product->save();
            $product_id = $_product->getId();
            if($seo_url){
                foreach($seo_url as $key => $url){
                    $urlRewrite = Mage::getModel("core/url_rewrite");
                    $urlRewrite->addData($url);
                    $urlRewrite
                        ->setIsSystem(0)
                        ->setIdPath('cm_product/' . $product_id . "-" . $key)
                        ->setTargetPath('catalog/product/view/id/'.$product_id);
                    try{
                        $urlRewrite->save();
                    } catch(LitExtension_CartServiceMigrate_Exception $e){
                        if(LitExtension_CartServiceMigrate_Model_Custom::DEV_MODE){
                            Mage::log($e->getMessage(), null, 'LitExtension_CartServiceMigrate.log');
                        }
                    } catch(Exception $e){
                        if(LitExtension_CartServiceMigrate_Model_Custom::DEV_MODE){
                            Mage::log($e->getMessage(), null, 'LitExtension_CartServiceMigrate.log');
                        }
                    }
                }
            }
            if ($data['type_id'] == 'bundle') {
                Mage::unregister('product');
            }

            $response['result'] = "success";
            $response['mage_id'] = $product_id;
        } catch(LitExtension_CartServiceMigrate_Exception $e){
            $response['result'] = "error";
            $response['msg'] = $e->getMessage();
        }catch(Exception $e){
            $response['result'] = "error";
            $response['msg'] = $e->getMessage();
        }
        if($response['result'] == "success" && $multi_store && !empty($multi_store)){
            foreach($multi_store as $store_data){
                try{
                    $product = Mage::getModel("catalog/product")->setStoreId($store_data['store_id'])->load($response['mage_id']);
                    $product->addData($store_data);
                    $product->save();
                } catch(LitExtension_CartServiceMigrate_Exception $e){
                    if(LitExtension_CartServiceMigrate_Model_Custom::DEV_MODE){
                        Mage::log($e->getMessage(), null, 'LitExtension_CartServiceMigrate.log');
                    }
                } catch(Exception $e){
                    if(LitExtension_CartServiceMigrate_Model_Custom::DEV_MODE){
                        Mage::log($e->getMessage(), null, 'LitExtension_CartServiceMigrate.log');
                    }
                }
            }
        }
        return $response;
    }
    
    public function getRegionId($name , $code){
        $result = null;
        $regions = Mage::getModel('directory/region')
            ->getCollection()
            ->addFieldToFilter('default_name', $name)
            ->addFieldToFilter('code', $code)
            ->getFirstItem();
        if($regions->getId()){
            $result = $regions->getId();
        } else{
            $result = 0;
        }
        return $result;
    }
    
    public function changeImgSrcInText($html, $img_des){
        $a = array("\u003C", "\u003E");
	$b = array("<",">");
	$new_html = str_replace($a, $b, $html);
        if(!$img_des){ return $new_html;}
        $links = array();
        preg_match_all('/<img[^>]+>/i', $new_html, $img_tags);
        foreach ($img_tags[0] as $img) {
            preg_match("/(src=[\"'](.*?)[\"'])/", $img, $src);
            $split = preg_split("/[\"']/", $src[0]);
            $links[] = $split[1];
        }
        $links = $this->_filterArrayValueDuplicate($links);
        foreach($links as $link){
            if($new_link = $this->_getImgDesUrlImport($link)){
                $html = str_replace($link, $new_link, $html);
            }
        }
        return $html;
    }
    
    protected function _getImgDesUrlImport($url){
        $result = false;
        $insert_extension = false;
        $url = parse_url($url);
        if(isset($url['host'])){
            $scheme = isset($url['scheme']) ? $url['scheme'] : "http";
            $host = $scheme . '://' . $url['host'];
            $path = substr($url['path'],1);
            if(isset($url['query'])){
                $insert_extension = $url['query'];
            }
        } else {
            $ext = explode("?", $url['path']);
            if (isset($ext[1])) {
                $insert_extension = $ext[1];
            }
            $real_path = preg_replace("/\?.+/", "", $url['path']);
            $occ = explode("/", $real_path);
            $path = array_pop($occ);
            $host = "http:" . implode("/", $occ);
        }
        if($path_import = $this->downloadImage($host, $path, 'wysiwyg', false, false, false, $insert_extension)){
            $result = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'wysiwyg/' . $path_import;
        }
        return $result;
    }
    
    protected function _convertDateTime($time) {
        $time_utc0 = @date('Y-m-d H:i:s', strtotime($time));
        return $time_utc0;
        //return str_replace("T", " ",substr($time, 0, 19));
    }
}