<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class   LitExtension_CartMigration_Model_Cart_Cubecartv6 extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_class WHERE id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE cat_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_inventory WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE customer_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_order_summary WHERE cart_order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this;
        }
        foreach($data['object'] as $type => $row){
            $count = $this->arrayToCount($row);
            $this->_notice[$type]['new'] = $count;
        }
        return $this;
    }

     /**
     * Process and get data use for config display
     *
     * @return array : Response as success or error with msg
     */
    public function displayConfig(){
        $response = array();
        $default_cfg = $this->_getDataImport($this->_getUrlConnector('query'),array(
            'serialize' => true,
            'query' => serialize(array(
                'config' => "SELECT * FROM _DBPRF_config WHERE name = 'config'",
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if($object && $object['config']){
            $config_data = json_decode(base64_decode($object['config'][0]['array']));
            $config_data = (array)$config_data;
            $this->_notice['config']['default_lang'] = isset($config_data['default_language']) ? $config_data['default_language'] : 'en-GB';
            $this->_notice['config']['default_currency'] = isset($config_data['default_currency']) ? $config_data['default_currency'] : 'USD';
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'),array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT * FROM _DBPRF_currency WHERE active = 1 ",
                "languages" => "SELECT * FROM _DBPRF_config WHERE name = 'languages'",
                "customer_group" => "SELECT * FROM _DBPRF_customer_group"
            ))
        ));

        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }

        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");

        $langSrc = '';
        if($obj['languages']) {
            $langSrc = $obj['languages'][0]['array'];
        }
        $langSrc = json_decode(base64_decode($langSrc));
        if ($langSrc) {
            foreach ($langSrc as $language_code => $language_row) {
                $lang_id = $language_code;
                $lang_name = $language_code;
                $language_data[$lang_id] = $lang_name;
            }
        }
        foreach($obj['currencies'] as $currency_row){
            $currency_id = $currency_row['currency_id'];
            $currency_name = $currency_row['code'];
            $currency_data[$currency_id] = $currency_name;
        }
        
        $order_status_data = array(
            1 => "Pending",
            2 => "Process",
            3 => "Complete",
            4 => "Declined",
            5 => "Failed",
            6 => "Cancelled"
        );
        foreach($obj['customer_group'] as $customer_group){
            $cus_status_id = $customer_group['group_id'];
            $cus_status_name = $customer_group['group_name'];
            $customer_group_data[$cus_status_id] = $cus_status_name;
        }
        
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['customer_group_data'] = $customer_group_data;
        $response['result'] = 'success';
        return $response;
    }
    
    /**
     * Save config of use in config step to notice
     */
    
    public function displayConfirm($params){
        parent::displayConfirm($params);
        return array(
            'result' => 'success'
        );
    }

    /**
     * Get data for import display
     *
     * @return array : Response as success or error with msg
     */
    
    public function displayImport(){
        $recent = $this->getRecentNotice();
        if($recent){
            $types = array('taxes','manufacturers','categories','products','customers','orders','reviews');
            foreach($types as $type){
                if($this->_notice['config']['add_option']['add_new'] || !$this->_notice['config']['import'][$type]){
                    $this->_notice[$type]['id_src'] = $recent[$type]['id_src'];
                    $this->_notice[$type]['imported'] = 0;
                    $this->_notice[$type]['error'] = 0;
                    $this->_notice[$type]['point'] = 0;
                    $this->_notice[$type]['finish'] = 0;
                }
            }
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_class WHERE id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE cat_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_inventory WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE customer_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_order_summary WHERE cart_order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $totals = array();
        foreach($data['object'] as $type => $row){
            $count = $this->arrayToCount($row);
            $totals[$type] = $count;
        }
        $iTotal = $this->_limitDemoModel($totals);
        foreach($iTotal as $type => $total){
            $this->_notice[$type]['total'] = $total;
        }
        $this->_notice['taxes']['time_start'] = time();
        if(!$this->_notice['config']['add_option']['add_new']){
            $delete = $this->_deleteLeCaMgImport($this->_notice['config']['cart_url']);
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
        parent::configCurrency();
        $allowCur = $this->_notice['config']['currencies'];
        $allow_cur = implode(',', $allowCur);
        $this->_process->currencyAllow($allow_cur);
        $default_cur = $this->_notice['config']['currencies'][$this->_notice['config']['default_currency']];
        $this->_process->currencyDefault($default_cur);
        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_currency"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){
                $currency_id = $currency['currency_id'];
                $currency_value = $currency['value'];
                $currency_mage = $this->_notice['config']['currencies'][$currency_id];
                $data[$currency_mage] = $currency_value;
            }
            $this->_process->currencyRate(array(
                $default_cur => $data
            ));
        }
        return ;
    }
    
    /**
     * Process before import taxes
     */
    
     public function prepareImportTaxes(){
        parent::prepareImportTaxes();
        $tax_cus = $this->getTaxCustomerDefault();
        if($tax_cus['result'] == 'success'){
            $this->taxCustomerSuccess(1, $tax_cus['mage_id']);
        }
    }
    /**
     * Query for get data of table convert to tax rule
     *
     * @return string
     */

    protected function _getTaxesMainQuery(){
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = "SELECT * FROM _DBPRF_tax_class WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */

    protected function _getTaxesExtQuery($taxes){
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'id');
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'tax_rates' => "SELECT tr.*, gc.*, gz.*, gz.abbrev, gz.name as zone_name
                            FROM _DBPRF_tax_rates AS tr
                              LEFT JOIN _DBPRF_geo_country AS gc ON gc.numcode = tr.country_id
                              LEFT JOIN _DBPRF_geo_zone AS gz ON gz.id = tr.county_id
                            WHERE tr.type_id IN {$tax_id_con}"
        );
        return $ext_query;
    }
    
    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @param array $taxesExt : Data of connector return for query in function getTaxesExtQuery
     * @return array
     */

    protected function _getTaxesExtRelQuery($taxes, $taxesExt){
        return false;
    }
    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */
    
    public  function getTaxId($tax, $taxesExt){
        return $tax['id'];
    }
    
    /**
     * Convert source data to data for import
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return array
     */

    public function convertTax($tax, $taxesExt){
        if(LitExtension_CartMigration_Model_Custom::TAX_CONVERT){
            return $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        }
        $tax_cus_ids = $tax_pro_ids = $tax_rate_ids = array();
        if($tax_cus_default = $this->getMageIdTaxCustomer(1)){
            $tax_cus_ids[] = $tax_cus_default;
        }
        $tax_pro_data = array(
            'class_name' => $tax['tax_name']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] ==  'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id'],$tax_pro_ipt['mage_id']);
        }

        $taxRates = $this->getListFromListByField($taxesExt['object']['tax_rates'],'type_id',$tax['id']);
        foreach($taxRates as $tax_rate){
                $tax_rate_data = array();
                $code = $tax['tax_name'] . "-" . $tax_rate['tax_percent'];
                $tax_rate_data['code'] = $this->createTaxRateCode($code);
                $tax_rate_data['tax_country_id'] = $tax_rate['iso'];
                if(!$tax_rate['county_id']){
                    $tax_rate_data['tax_region_id'] = 0;
                } else {
                    $tax_rate_data['tax_region_id'] = $this->getRegionId($tax_rate['zone_name'], $tax_rate['iso']);
                }
                $tax_rate_data['zip_is_range'] = 0;
                $tax_rate_data['tax_postcode'] = "*";
                $tax_rate_data['rate'] = $tax_rate['tax_percent'];
                $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                if($tax_rate_ipt['result'] == 'success'){
                    $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['tax_name']);
        $tax_rule_data['tax_customer_class'] = $tax_cus_ids;
        $tax_rule_data['tax_product_class'] = $tax_pro_ids;
        $tax_rule_data['tax_rate'] = $tax_rate_ids;
        $tax_rule_data['priority'] = 0;
        $tax_rule_data['position'] = 0;
        $custom = $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        if($custom){
            $tax_rule_data = array_merge($tax_rule_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $tax_rule_data
        );
    }
    
    /**
     * Process before import manufacturers
     */
    
    public function prepareImportManufacturers(){
        parent::prepareImportManufacturers();
        $man_attr = $this->getManufacturerAttributeId($this->_notice['config']['attribute_set_id']);
        if($man_attr['result'] == 'success'){
            $this->manAttrSuccess(1,$man_attr['mage_id']);
        }
    }
    
    /**
     * Query for get data for convert to manufacturer option
     *
     * @return string
     */

    protected function _getManufacturersMainQuery(){
        parent::prepareImportManufacturers();
        $id_src = $this->_notice['manufacturers']['id_src'];
        $limit = $this->_notice['setting']['manufacturers'];
        $query = "SELECT * FROM _DBPRF_manufacturers WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @return array
     */
    
    protected function _getManufacturersExtQuery($manufacturers){
        return array();
    }
    
    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @param array $manufacturersExt : Data of connector return for query function getManufacturersExtQuery
     * @return array
     */

    protected function _getManufacturersExtRelQuery($manufacturers, $manufacturersExt){
        return array();
    }
    
    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return int
     */

    public function getManufacturerId($manufacturer, $manufacturersExt){
        return $manufacturer['id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return array
     */

    public function convertManufacturer($manufacturer, $manufacturersExt){
        if(LitExtension_CartMigration_Model_Custom::MANUFACTURER_CONVERT){
            return $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        }
        $man_attr_id = $this->getMageIdManAttr(1);
        if(!$man_attr_id){
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
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $manufacturer['name'];
        }
        $custom = $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        if($custom){
            $manufacturer_data = array_merge($manufacturer_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $manufacturer_data
        );
    }
    
    /**
     * Query for get data of main table use import category
     *
     * @return string
     */
    protected function _getCategoriesMainQuery(){
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $query = "SELECT * FROM _DBPRF_category WHERE cat_id > {$id_src} ORDER BY cat_id ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */

    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'],'cat_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'categories_description' => "SELECT * FROM _DBPRF_category_language WHERE cat_id IN {$cat_id_con}",
            'categories_images' => "SELECT * FROM _DBPRF_filemanager"
        );
        return $ext_query;
    }
    
     /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @param array $categoriesExt : Data of connector return for query function getCategoriesExtQuery
     * @return array
     */

    protected function _getCategoriesExtRelQuery($categories, $categoriesExt){
        return array();
    }
    
    /**
     * Get primary key of source category
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return int
     */

    public function getCategoryId($category, $categoriesExt){
        return $category['cat_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return array
     */

    public function convertCategory($category, $categoriesExt){
        if(LitExtension_CartMigration_Model_Custom::CATEGORY_CONVERT){
            return $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        }
        if($category['cat_parent_id'] == 0 || $category['cat_parent_id'] == $category['cat_id']){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['cat_parent_id']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['cat_parent_id']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['cat_id']} import failed. Error: Could not import parent category id = {$category['cat_parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['categories_description'], 'cat_id', $category['cat_id']);
        //$cat_dess = $this->getRowFromListByField($catDesc, 'language', $this->_notice['config']['default_lang']);
        
        $cat_data['name'] = $category['cat_name'] ? $category['cat_name'] : " ";
        $cat_data['description'] = $category['cat_desc'];
        $cat_data['meta_title'] = $category['seo_meta_title'];
        $cat_data['meta_keywords'] = $category['seo_meta_keywords'];
        $cat_data['meta_description'] = $category['seo_meta_description'];
        
        $catImg = $this->getRowFromListByField($categoriesExt['object']['categories_images'], 'file_id', $category['cat_image']);
        if($catImg){
                $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  $catImg['filename'], 'catalog/category');
                $cat_data['image'] = $img_path;
        }


        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['status'];
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_id => $store_id){
            if($lang_id != $this->_notice['config']['default_lang']){
                $store_data = array();
                $store_def = $this->getRowFromListByField($catDesc, 'language', $lang_id);
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_def['cat_name'];
                $store_data['description'] = $store_def['cat_desc'];
                $store_data['meta_title'] = $store_def['seo_meta_title'];
                $store_data['meta_keywords'] = $store_def['seo_meta_keywords'];
                $store_data['meta_description'] = $store_def['seo_meta_description'];
                $multi_store[] = $store_data;
            }
        }
        $cat_data['multi_store'] = $multi_store;
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
            'result' => 'success',
            'data' => $cat_data
        );
    }
    
    /**
     * Process before import products
     */
    
     public function prepareImportProducts(){
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }
    
     /**
     * Query for get data of main table use for import product
     *
     * @return string
     */

    protected function _getProductsMainQuery(){
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT * FROM _DBPRF_inventory WHERE product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
        return $query;
    }
    
     /* Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */

    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'product_id');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'inventory_language' => "SELECT * FROM _DBPRF_inventory_language WHERE product_id IN {$pro_ids_query}",
            'products_images' => "SELECT ii.*, f.* FROM _DBPRF_image_index AS ii
                                                  LEFT JOIN _DBPRF_filemanager AS f ON f.file_id = ii.file_id
                                            WHERE ii.product_id IN {$pro_ids_query}",
            'category_index' => "SELECT * FROM _DBPRF_category_index WHERE product_id IN {$pro_ids_query}",
            'pricing_quantity' => "SELECT * FROM _DBPRF_pricing_quantity WHERE product_id IN {$pro_ids_query}",
            'pricing_group' => "SELECT * FROM _DBPRF_pricing_group WHERE product_id IN {$pro_ids_query}",
            'option_assign' => "SELECT * FROM _DBPRF_option_assign WHERE product IN {$pro_ids_query}",
        );
        return $ext_query;
    }
    
    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @param array $productsExt : Data of connector return for query function getProductsExtQuery
     * @return array
     */

    protected function _getProductsExtRelQuery($products, $productsExt){
        $productOptionIds = $this->duplicateFieldValueFromList($productsExt['object']['option_assign'], 'option_id');
        $productOptionValueIds = $this->duplicateFieldValueFromList($productsExt['object']['option_assign'], 'value_id');
        $product_option_ids_query = $this->arrayToInCondition($productOptionIds);
        $product_option_value_ids_query = $this->arrayToInCondition($productOptionValueIds);
        $ext_rel_query = array(
            'option_group' => "SELECT * FROM _DBPRF_option_group WHERE option_id IN {$product_option_ids_query}",
            'option_value' => "SELECT * FROM _DBPRF_option_value WHERE value_id IN {$product_option_value_ids_query}"
        );
        return $ext_rel_query;
    }
    
    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return int
     */
    
    public function getProductId($product, $productsExt){
        return $product['product_id'];
    }
    
     /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return array
     */

    public function convertProduct($product, $productsExt){
        if(LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $categories = array();
        $proCat = $this->getListFromListByField($productsExt['object']['category_index'], 'product_id', $product['product_id']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['cat_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $proDesc = $this->getListFromListByField($productsExt['object']['inventory_language'], 'product_id', $product['product_id']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'language',$this->_notice['config']['default_lang']);
        $pro_data = array();
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['sku'] = $this->createProductSku($product['product_code'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['name'];
        $pro_data['description'] = $product['description'];//$this->changeImgSrcInText(html_entity_decode($pro_desc_def['description']), $this->_notice['config']['add_option']['img_des']);
        //$pro_data['short_description'] = $product['description_short'];//$this->changeImgSrcInText(html_entity_decode($pro_desc_def['description_short']), $this->_notice['config']['add_option']['img_des']);
        if(isset($product['description_short'])){
            $pro_data['short_description'] = $product['description_short'];
        }
        $pro_data['price'] = $product['price'] ? $product['price'] : 0;
        $pro_data['special_price'] = ($product['sale_price'] != 0.00) ? $product['sale_price'] : '';

        $proPriQty = $this->getListFromListByField($productsExt['object']['pricing_quantity'], 'product_id', $product['product_id']);
        if($proPriQty) {
            foreach ($proPriQty as $row) {
                if($row['quantity'] <= 1) continue;
                $value = array(
                    'website_id' => 0,
                    'cust_group' => isset($this->_notice['config']['customer_group'][$row['group_id']]) ? $this->_notice['config']['pricing_group'][$row['group_id']] : Mage_Customer_Model_Group::CUST_GROUP_ALL,
                    'price_qty' => $row['quantity'],
                    'price' => $row['price']
                );
                $tier_prices[] = $value;
            }
            $pro_data['tier_price'] = $tier_prices;
        }
        
//        $specialPrice = $this->getRowFromListByField($proPriQty, 'quantity', '1');
//        if($specialPrice){
//            $pro_data['special_price'] = $specialPrice['price'];
//            $pro_data['special_from_date'] = '';
//            $pro_data['special_to_date'] = '';
//        }
        $pro_data['weight']   = $product['product_weight'] ? $product['product_weight']: 0 ;
        $pro_data['status'] = ($product['status']== 1)? 1 : 2;
        if($product['tax_type'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['tax_type'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['date_added'];
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $product['use_stock_level'],
            'use_config_manage_stock' => 0,
            'qty' => $product['stock_level']
        );
        if($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturer'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        $proImg = $this->getListFromListByField($productsExt['object']['products_images'], 'product_id', $product['product_id']);
        if($proImg){
            foreach($proImg as $key => $gallery){
                if($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery['filename'], 'catalog/product', false, true)){
                    if ($gallery['main_img'] == 1) {
                        $pro_data['image_import_path'] = array('path' => $gallery_path, 'label' => '');
                    } else {
                        $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
                    }
                }
            }
        }
        $pro_data['meta_title'] = $product['seo_meta_title'];
        $pro_data['meta_keyword'] = $product['seo_meta_keywords'];
        $pro_data['meta_description'] = $product['seo_meta_description'];
        $multi_store = array();
        
        foreach($this->_notice['config']['languages'] as $lang_id => $store_id){
            if($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'language', $lang_id)){
                $store_data = array();
                $store_data['name'] = $store_data_change['name'];
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['store_id'] = $store_id;
                //$store_data['short_description'] = $this->changeImgSrcInText(html_entity_decode($store_data_change['description']), $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = $store_data_change['seo_meta_title'];
                $store_data['meta_keyword'] = $store_data_change['seo_meta_keywords'];
                $store_data['meta_description'] = $store_data_change['seo_meta_description'];
                $multi_store[] = $store_data;
            }
        }
        $pro_data['multi_store'] = $multi_store;
        if($this->_seo){
            $seo = $this->_seo->convertProductSeo($this, $product, $productsExt);
            if($seo){
                $pro_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product, $productsExt);
        if($custom){
            $pro_data = array_merge($pro_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }
    
    /**
     * Process after one product import successful
     *
     * @param int $product_mage_id : Id of product save successful to magento
     * @param array $data : Data of function convertProduct
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return boolean
     */

    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt){
        if(parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)){
            return ;
        }
        $proAttr = $this->getListFromListByField($productsExt['object']['option_assign'], 'product', $product['product_id']);
        if($proAttr){
            $opt_data = array();
            $proOptId = $this->duplicateFieldValueFromList($proAttr, 'option_id');
            foreach($proOptId as $pro_opt_id){
                $proOpt = $this->getListFromListByField($productsExt['object']['option_group'], 'option_id', $pro_opt_id);
                $proOptVal = $this->getListFromListByField($proAttr,'option_id', $pro_opt_id);
                if(!$proOpt){
                    continue;
                }
                $type = $this->getRowValueFromListByField($proOpt, 'option_id', $pro_opt_id, 'option_type');
                $type_import = $this->_getOptionTypeByTypeSrc($type);
                
                $option = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($type_import),
                    'type' => $type_import,
                    'is_require' => $this->getRowValueFromListByField($proOpt, 'option_id', $pro_opt_id, 'option_required'),
                    'title' => $this->getRowValueFromListByField($proOpt, 'option_id', $pro_opt_id, 'option_name'),
                );
                if($type_import != 'drop_down' && $type_import != 'radio'){
                    $option['price'] = $proOptVal[0]['option_price'];
                    $option['price_type'] = 'fixed';
                    $opt_data[] = $option;
                    continue;
                }
                $values = array();
                foreach($proOptVal as $pro_opt_val){
                    $proVal = $this->getRowFromListByField($productsExt['object']['option_value'], 'value_id', $pro_opt_val['value_id']);
                    $pro_attr_val = $this->getRowFromListByField($proAttr, 'value_id', $proVal['value_id']);
                    $value = array(
                        'option_type_id' => -1,
                        'title' => $proVal['value_name'],
                        'price' => $pro_attr_val['option_price'],
                        'price_type' => 'fixed'
                    );
                    $values[] = $value;
                }
                $option['values'] = $values;
                $opt_data[] = $option;
            }
            $this->importProductOption($product_mage_id, $opt_data);
        }
    }
    
    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    
    protected function _getCustomersMainQuery(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_customer WHERE customer_id > {$id_src} ORDER BY customer_id ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    
    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'customer_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'address_book' => "SELECT * FROM _DBPRF_addressbook WHERE customer_id IN {$customer_ids_query}",
            'customer_membership' => "SELECT * FROM _DBPRF_customer_membership",
        );
        return $ext_query;
    }
    
     /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @param array $customersExt : Data of connector return for query function getCustomersExtQuery
     * @return array
     */

    protected function _getCustomersExtRelQuery($customers, $customersExt){
        $countryIds = $this->duplicateFieldValueFromList($customersExt['object']['address_book'], 'country');
        $country_ids_query = $this->arrayToInCondition($countryIds);
        $countyIds = $this->duplicateFieldValueFromList($customersExt['object']['address_book'], 'state');
        $county_ids_query = $this->arrayToInCondition($countyIds);
        $ext_rel_query = array(
            'geo_country' => "SELECT * FROM _DBPRF_geo_country WHERE numcode IN {$country_ids_query}",
            'geo_zone' => "SELECT * FROM _DBPRF_geo_zone WHERE id IN {$county_ids_query}"
        );
        return $ext_rel_query;
    }
    
    /**
     * Get primary key of source customer main
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return int
     */

    public function getCustomerId($customer, $customersExt){
        return $customer['customer_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return array
     */

    public function convertCustomer($customer, $customersExt){
        if(LitExtension_CartMigration_Model_Custom::CUSTOMER_CONVERT){
            return $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        }
       // $info = $this->getRowFromListByField($customersExt['object']['customers_info'], 'customers_info_id', $customer['customers_id']);
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['customer_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['prefix'] = $customer['title'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['first_name'];
        $cus_data['lastname'] = $customer['last_name'];
        $cus_data['gender'] = "";
        $cus_data['dob'] = '';
        $cus_data['created_at'] = date("Y-m-d H:i:s", $customer['registered']);
        $cus_data['is_subscribed'] = 0;
        $groupsCustomer = $this->getRowFromListByField($customersExt['object']['customer_membership'], 'customer_id', $customer['customer_id']);
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$groupsCustomer['group_id']]) ? $this->_notice['config']['customer_group'][$groupsCustomer['group_id']] : 1; 
        $custom = $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        if($custom){
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
     * @param int $customer_mage_id : Id of customer import to magento
     * @param array $data : Data of function convertCustomer
     * @param array $customer : One row of object function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return boolean
     */

    public function afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt){
        if(parent::afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt)){
            return;
        }
        $this->_importCustomerRawPass($customer_mage_id, $customer['password'] . ":" . $customer['salt']);
        $cusAdd = $this->getListFromListByField($customersExt['object']['address_book'], 'customer_id', $customer['customer_id']);
        if($cusAdd){
            foreach($cusAdd as $cus_add){
                $address = array();
                $address['prefix'] = $cus_add['title'];
                $address['firstname'] = $cus_add['first_name'];
                $address['lastname'] = $cus_add['last_name'];
                $country_iso = $this->getRowValueFromListByField($customersExt['object']['geo_country'], 'numcode' , $cus_add['country'], 'iso');
                $address['country_id'] = $country_iso;
                $address['street'] = $cus_add['line1']."\n".$cus_add['line2'];
                $address['postcode'] = $cus_add['postcode'];
                $address['city'] = $cus_add['town'];
                $address['telephone'] = $customer['phone'];
                $address['company'] = $cus_add['company_name'];
                $address['fax'] = '';
                if(is_numeric($cus_add['state'])){
                    $state_src = $this->getRowValueFromListByField($customersExt['object']['geo_zone'] , 'id' , $cus_add['state'], 'name');
                }else{
                    $state_src = $cus_add['state'];
                }
                if($state_src){
                    $region_id = $this->getRegionId($state_src, $country_iso);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $state_src;
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success' && $cus_add['billing']){
                    try{
                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                        $cus->setDefaultBilling($address_ipt['mage_id']);
                        $cus->setDefaultShipping($address_ipt['mage_id']);
                        $cus->save();
                    }catch (Exception $e){
                        
                    }
                }
            }
        }
    }
    
    
    /**
     * Get data use for import order
     *
     * @return array : Response of connector
     */
    
    
    public function _getOrdersMainQuery(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM (SELECT *,cast( REPLACE(cart_order_id,'-','') AS UNSIGNED ) as order_id FROM _DBPRF_order_summary) cc WHERE cc.order_id > {$id_src} ORDER BY cc.order_id LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */

    protected function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'],'cart_order_id');
        $bilCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'country');
        $delCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'country_d');
        $countries = array_unique(array_merge($bilCountry, $delCountry));
        $bilState = (array) $this->duplicateFieldValueFromList($orders['object'], 'state');
        $delState = (array) $this->duplicateFieldValueFromList($orders['object'], 'state_d');
        $states = array_unique(array_merge($bilState, $delState));
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $countries_query = $this->arrayToInCondition($countries);
        $states_query = $this->arrayToInCondition($states);
        $ext_query = array(
            'order_inventory' => "SELECT * FROM _DBPRF_order_inventory WHERE cart_order_id IN {$order_ids_query}",
            'order_history'   => "SELECT * FROM _DBPRF_order_history WHERE cart_order_id IN {$order_ids_query}",
            'order_notes'   => "SELECT * FROM _DBPRF_order_notes WHERE cart_order_id IN {$order_ids_query}",
            'currency' => "SELECT * FROM _DBPRF_currency",
            'order_tax' => "SELECT * FROM _DBPRF_order_order_tax WHERE cart_order_id IN {$order_ids_query}",
            'geo_country' => "SELECT * FROM _DBPRF_geo_country WHERE name IN {$countries_query}",
            'geo_zone' => "SELECT * FROM _DBPRF_geo_zone WHERE id IN {$states_query}"
        );
        return $ext_query;
    }
    
    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @param array $ordersExt : Data of connector return for query function getOrdersExtQuery
     * @return array
     */

    protected function _getOrdersExtRelQuery($orders, $ordersExt){
        return array();
    }
    
    /**
     * Get primary key of source order main
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return int
     */
    
    public function getOrderId($order, $ordersExt){
        return $order['order_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return array
     */

    public function convertOrder($order, $ordersExt){
        if(LitExtension_CartMigration_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();

        $address_billing = $this->getNameFromString($order['title']);
        $address_billing['firstname'] = $order['first_name'];
        $address_billing['lastname'] = $order['last_name'];
        $address_billing['company'] = $order['company_name'];
        $address_billing['email']   = $order['email'];
        $address_billing['street']  = $order['line1']."\n".$order['line2'];
        $address_billing['city'] = $order['town'];
        $address_billing['postcode'] = $order['postcode'];
        $bil_country = $this->getRowValueFromListByField($ordersExt['object']['geo_country'], 'name', $order['country'], 'iso');
        $address_billing['country_id'] = $bil_country;
        if(is_numeric($order['state'])){
            $billing_state = $this->getRowValueFromListByField($ordersExt['object']['geo_zone'], 'id', $order['state'], 'name');
            if(!$billing_state){
                $billing_state = $order['state'];
            }
        } else{
            $billing_state = $order['state'];
        }
        $billing_region_id = $this->getRegionId($billing_state, $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $billing_state;
        }
        $address_billing['telephone'] = $order['phone'];

        $address_shipping = $this->getNameFromString($order['title_d']);
        $address_shipping['firstname'] = $order['first_name_d'];
        $address_shipping['lastname'] = $order['last_name_d'];
        $address_shipping['company'] = $order['company_name_d'];
        $address_shipping['email']   = $order['email'];
        $address_shipping['street']  = $order['line1_d']."\n".$order['line2_d'];
        $address_shipping['city'] = $order['town_d'];
        $address_shipping['postcode'] = $order['postcode_d'];
        $del_country = $this->getRowValueFromListByField($ordersExt['object']['geo_country'], 'name', $order['country_d'], 'iso');
        $address_shipping['country_id'] = $del_country;
        if(is_numeric($order['state_d'])){
            $shipping_state = $this->getRowValueFromListByField($ordersExt['object']['geo_zone'], 'id', $order['state_d'], 'name');
            if(!$shipping_state){
                $shipping_state = $order['state'];
            }
        } else{
            $shipping_state = $order['state_d'];
        }
        $shipping_region_id = $this->getRegionId($shipping_state, $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $shipping_state;
        }
        $address_shipping['telephone'] = $order['phone'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['order_inventory'], 'cart_order_id', $order['cart_order_id']);
        //$orderProOpt = $this->getListFromListByField($ordersExt['object']['orders_products_attributes'], 'orders_id', $order['orders_id']);
        $carts = array();
        $order_subtotal = 0;

            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getMageIdProduct($order_pro['product_id']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_pro['name'];
                $cart['sku'] = $order_pro['product_code'];
                $cart['price'] = $order_pro['price'];
                $cart['original_price'] = $order_pro['price'];
                $cart['tax_amount'] =  0;
                $cart['tax_percent'] = 0;
                $cart['qty_ordered'] = $order_pro['quantity'];
                $cart['row_total'] = $order_pro['price'] * $order_pro['quantity'];
                $order_subtotal += $cart['row_total'];
                //$order_tax_amount += $cart['tax_amount'];

                if($order_pro['product_options']){
                    $listOpt = unserialize($order_pro['product_options']);
                    if($listOpt){
                        $product_opt = array();
                        foreach($listOpt as $key => $list_opt){
                            $partOption = explode(":", $list_opt);
                            if (count($partOption) < 2) continue;
                            $option = array(
                                'label' => $partOption[0],
                                'value' => $partOption[1],
                                'print_value' => $partOption[1],
                                'option_id' => 'option_'.$key,
                                'option_type' => 'drop_down',
                                'option_value' => 0,
                                'custom_view' => false
                            );
                            $product_opt[] = $option;
                        }
                        $cart['product_options'] = serialize(array('options' => $product_opt));
                    }
                }
                $carts[]= $cart;
            }

        $customer_id = $this->getMageIdCustomer($order['customer_id']);
        //$customer_name = $this->getNameFromString($order['customers_name']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['order_history'], 'cart_order_id', $order['cart_order_id']);
        $order_status = $orderStatus[0];
        $order_status_id = $order_status['status'];

       // $orderTotal = $this->getListFromListByField($ordersExt['object']['orders_total'], 'orders_id', $order['orders_id']);

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['email'];
        $order_data['customer_firstname'] = $order['first_name'];
        $order_data['customer_lastname'] = $order['last_name'];
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status']){
            $order_data['status'] = $this->_notice['config']['order_status'][$order['status']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order_subtotal);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['shipping'];
        $order_data['base_shipping_amount'] = $order['shipping'];
        $order_data['base_shipping_invoiced'] = $order['shipping'];
        if(isset($order['ship_product'])){
            $order_data['shipping_description'] = $order['ship_method']. "-" .$order['ship_product']. "-" .$order['ship_date']. "-" .$order['ship_tracking'];
        }else{
            $order_data['shipping_description'] = $order['ship_method']. "-" .$order['ship_date']. "-" .$order['ship_tracking'];
        }
        $order_tax_amount = $order['total_tax']; //$this->getRowValueFromListByField($ordersExt['object']['order_tax'], 'cart_order_id', $order['cart_order_id'], 'amount');
        if($order_tax_amount){
            $order_data['tax_amount'] = $order_tax_amount;
            $order_data['base_tax_amount'] = $order_tax_amount;
        }
        $order_data['discount_amount'] = $order['discount'];
        $order_data['base_discount_amount'] = $order['discount'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['total']);
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
        //$order_data['created_at'] = date("Y-m-d H:i:s",$order['order_date']);
        if(isset($order['order_date'])){
            $order_data['created_at'] = date("Y-m-d H:i:s",$order['order_date']);
        }
        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['cart_order_id'];
        $custom = $this->_custom->convertOrderCustom($this, $order, $ordersExt);
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
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return boolean
     */

    public function afterSaveOrder($order_mage_id, $data, $order, $ordersExt) {
        if (parent::afterSaveOrder($order_mage_id, $data, $order, $ordersExt)) {
            return;
        }
        $orderHistory = $this->getListFromListByField($ordersExt['object']['order_history'], 'cart_order_id', $order['cart_order_id']);
        $orderNotes = $this->getListFromListByField($ordersExt['object']['order_notes'], 'cart_order_id' , $order['cart_order_id']);
        
        $orderStatus = array();
        $state = '';
        foreach ($orderHistory as $id => $order_history){
            if($id == 0){
                $state = $order_history['status'];
            }
            $orderStatus[$order_history['updated']]['stt'] = $order_history['status'];
        }
        
        foreach ($orderNotes as $order_notes) {
            $timestamp = strtotime($order_notes['time']);
            $orderStatus[$timestamp]['history']['admin'] = $order_notes['admin_id'];
            $orderStatus[$timestamp]['history']['content'] = $order_notes['content'];
        }
        ksort($orderStatus);
        $i = 0;
        foreach ($orderStatus as $key => $order_status) {
            if(isset($order_status['stt'])){
                $state = $order_status['stt'];
            }
//            if(isset($order_status['stt'])){
//                continue;
//            }
            $order_status_data = array();
            $order_status_id = $state;
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if ($i == 0) {
                $order_status_data['comment'] = "<b>Reference order # " . $order['id'] ."<b>Customer Comments " . $order['customer_comments'] . 
                                "<b>Note to customer" . $order['note_to_customer'] .
                                    "<br /><b>Shipping method: </b> " . $order['ship_method'] . "<br />" 
                        . $order_status['history']['content'];
            } else {
                $order_status_data['comment'] = $order_status['history']['content'];
            }
            
            $i++;
            $order_status_data['is_customer_notified'] = ($order_status['history']['admin']) ? 0 : 1;
            $order_status_data['updated_at'] = date("Y-m-d H:i:s", $key);
            $order_status_data['created_at'] = date("Y-m-d H:i:s", $key);
            $this->_process->ordersComment($order_mage_id, $order_status_data);
        }
    }


   /**
     * Query for get main data use for import review
     *
     * @return string
     */

    protected function _getReviewsMainQuery(){
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $query = "SELECT * FROM _DBPRF_reviews WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    
    protected function _getReviewsExtQuery($reviews){
        return array();
    }
    
    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @param array $reviewsExt : Data of connector return for query function getReviewsExtQuery
     * @return array
     */

    protected function _getReviewsExtRelQuery($reviews, $reviewsExt){
        return array();
    }
    
    /**
     * Get primary key of source review main
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return int
     */
    
    
    public function getReviewId($review, $reviewsExt){
        return $review['id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return array
     */

    public function convertReview($review, $reviewsExt){
        if(LitExtension_CartMigration_Model_Custom::REVIEW_CONVERT){
            return $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        }
        $product_mage_id = $this->getMageIdProduct($review['product_id']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Reviews Id = {$review['id']} import failed. Error : Product Id {$review['product_id']} not imported!")
            );
        }
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        if(isset($review['approved'])){
            $data['status_id'] = ($review['approved'] == 0) ? 3 : 1;
        }else{
            $data['status_id'] = 1;
        }
        $data['title'] = $review['title'];
        $data['detail'] = $review['review'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['customer_id'])) ? $this->getMageIdCustomer($review['customer_id']) : null;
        $data['nickname'] = $review['name'];
        $data['rating'] = $review['rating'];
        $data['created_at'] = date("Y-m-d H:i:s", $review['time']);
        $data['review_id_import'] = $review['id'];

        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

   ############################################################ Extend function ##################################

    /**
     * Import parent category if not exists by id
     */
    
    
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_category WHERE cat_id = {$parent_id}"
        ));
        if(!$categories || $categories['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $categoriesExt = $this->getCategoriesExt($categories);
        if($categoriesExt['result'] != 'success'){
            return $categoriesExt;
        }
        $category = $categories['object'][0];
        $convert = $this->convertCategory($category, $categoriesExt);
        if($convert['result'] != 'success'){
            return array(
                'result' => 'warning',
            );
        }
        $data = $convert['data'];
        $category_ipt = $this->_process->category($data);
        if($category_ipt['result'] == 'success'){
            $this->categorySuccess($parent_id, $category_ipt['mage_id']);
            $this->afterSaveCategory($category_ipt['mage_id'], $data, $category, $categoriesExt);
        } else {
            $category_ipt['result'] = 'warning';
        }
        return $category_ipt;
    }

    /**
     * Get array value is number in array 2D
     */
    protected function _flitArrayNum($array){
        $data = array();
        foreach($array as $value){
            if(is_numeric($value)){
                $data[] = $value;
            }
        }
        return $data;
    }
    
    protected function _getOptionTypeByTypeSrc($type_name) {
        $types = array(
            '0' => 'drop_down',
            '1' => 'field',
            '4' => 'radio',
            '2' => 'area',
        );
        return isset($types[$type_name]) ? $types[$type_name] : false;
    }

    /**
     * TODO: CRON
     */

    public function getAllTaxes()
    {
        if(!$this->_notice['config']['import']['taxes']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_tax_class ORDER BY id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllManufacturers()
    {
        if(!$this->_notice['config']['import']['manufacturers']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_manufacturers ORDER BY id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllCategories()
    {
        if(!$this->_notice['config']['import']['categories']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_category ORDER BY cat_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllProducts()
    {
        if(!$this->_notice['config']['import']['products']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_inventory ORDER BY product_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllCustomers()
    {
        if(!$this->_notice['config']['import']['customers']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_customer ORDER BY customer_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllOrders()
    {
        if(!$this->_notice['config']['import']['orders']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM (SELECT *,cast( REPLACE(cart_order_id,'-','') AS UNSIGNED ) as order_id FROM _DBPRF_order_summary) ORDER BY cc.order_id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

    public function getAllReviews()
    {
        if(!$this->_notice['config']['import']['reviews']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_reviews ORDER BY id ASC";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        return $data;
    }

}

