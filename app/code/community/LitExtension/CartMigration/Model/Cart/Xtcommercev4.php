<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Xtcommercev4
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_class WHERE tax_class_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturers_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categories_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE (products_master_model IS NULL OR products_master_model = '') AND products_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customers_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orders_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_products_reviews WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
        $default_cfg = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_config_1 WHERE config_key = '_STORE_LANGUAGE'",
                "currencies" => "SELECT * FROM _DBPRF_config_1 WHERE config_key = '_STORE_CURRENCY'"
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['config_value']) ? $object['languages']['0']['config_value'] : 1;
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['config_value']) ? $object['currencies']['0']['config_value'] : 1;
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_languages",
                "currencies" => "SELECT * FROM _DBPRF_currencies",
                "orders_status" => "SELECT * FROM _DBPRF_system_status AS sys_stat
                                    LEFT JOIN _DBPRF_system_status_description AS sys_stat_des ON sys_stat_des.status_id = sys_stat.status_id
                                    WHERE sys_stat.status_class = 'order_status' AND sys_stat_des.language_code = '{$this->_notice['config']['default_lang']}'",
                "customers_status_description" => "SELECT * FROM _DBPRF_customers_status_description WHERE language_code = '{$this->_notice['config']['default_lang']}'"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach($obj['languages'] as $language_row){
            $lang_code = $language_row['code'];
            $lang_name = $language_row['name'] . "(" . $language_row['code'] . ")";
            $language_data[$lang_code] = $lang_name;
        }
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['currencies_id'];
            $currency_name = $currency_row['title'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['status_id'];
            $order_status_name = $order_status_row['status_name'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        foreach($obj['customers_status_description'] as $cus_status_row){
            $cus_status_id = $cus_status_row['customers_status_id'];
            $cus_status_name = $cus_status_row['customers_status_name'];
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
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews');
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_class WHERE tax_class_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturers_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categories_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE (products_master_model IS NULL OR products_master_model = '') AND products_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customers_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orders_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_products_reviews WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
            'query' => "SELECT * FROM _DBPRF_currencies"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){
                $currency_id = $currency['currencies_id'];
                $currency_value = $currency['code'];
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
        $query = "SELECT * FROM _DBPRF_tax_class WHERE tax_class_id > {$id_src} ORDER BY tax_class_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_class_id');
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'tax_rates' => "SELECT * FROM _DBPRF_tax_rates WHERE tax_class_id IN {$tax_id_con}"
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
        $taxZoneIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rates'], 'tax_zone_id');
        $tax_zone_query = $this->arrayToInCondition($taxZoneIds);
        $ext_rel_query = array(
            'zones_to_countries' => "SELECT * FROM _DBPRF_countries WHERE zone_id IN {$tax_zone_query}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt){
        return $tax['tax_class_id'];
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
            'class_name' => $tax['tax_class_title']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['tax_class_id'], $tax_pro_ipt['mage_id']);
        }
        $taxRates = $this->getListFromListByField($taxesExt['object']['tax_rates'], 'tax_class_id', $tax['tax_class_id']);
        if($taxRates){
            foreach($taxRates as $tax_rate){
                $taxZone = $this->getListFromListByField($taxesExt['object']['zones_to_countries'], 'zone_id', $tax_rate['tax_zone_id']);
                if($taxZone){
                    foreach($taxZone as $tax_zone){
                        if(!$tax_zone['countries_iso_code_2']){
                            continue ;
                        }
                        $tax_rate_data = array();
                        $code = $tax['tax_class_title'] . "-" . $tax_zone['countries_iso_code_2'];
                        $tax_rate_data['code'] = $this->createTaxRateCode($code);
                        $tax_rate_data['tax_country_id'] = $tax_zone['countries_iso_code_2'];
                        $tax_rate_data['tax_region_id'] = 0;
                        $tax_rate_data['zip_is_range'] = 0;
                        $tax_rate_data['tax_postcode'] = "*";
                        $tax_rate_data['rate'] = $tax_rate['tax_rate'];
                        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                        if($tax_rate_ipt['result'] == 'success'){
                            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                        }
                    }
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['tax_class_title']);
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
            $this->manAttrSuccess(1, $man_attr['mage_id']);
        }
    }

    /**
     * Query for get data for convert to manufacturer option
     *
     * @return string
     */
    protected function _getManufacturersMainQuery(){
        $id_src = $this->_notice['manufacturers']['id_src'];
        $limit = $this->_notice['setting']['manufacturers'];
        $query = "SELECT * FROM _DBPRF_manufacturers WHERE manufacturers_id > {$id_src} ORDER BY manufacturers_id ASC LIMIT {$limit}";
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
        return $manufacturer['manufacturers_id'];
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
            0 => $manufacturer['manufacturers_name']
        );
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $manufacturer['manufacturers_name'];
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
        $query = "SELECT * FROM _DBPRF_categories WHERE categories_id > {$id_src} ORDER BY categories_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$cat_id_con}",
            'categories_meta_key' => "SELECT * FROM _DBPRF_seo_url WHERE link_id IN {$cat_id_con} AND link_type = 2"
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
        return $category['categories_id'];
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
        if($category['parent_id'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['parent_id']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['parent_id']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['categories_id']} import failed. Error: Could not import parent category id = {$category['parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['categories_description'], 'categories_id', $category['categories_id']);
        $cat_name = $this->getRowValueFromListByField($catDesc, 'language_code', $this->_notice['config']['default_lang'], 'categories_name');
        $cat_data['name'] = $cat_name ? $cat_name : " ";
        $cat_data['description'] = $this->getRowValueFromListByField($catDesc, 'language_code', $this->_notice['config']['default_lang'], 'categories_description');
        if($des_bottom = $this->getRowValueFromListByField($catDesc, 'language_code', $this->_notice['config']['default_lang'], 'categories_description_bottom')){
            $cat_data['description'] = $cat_data['description'].'<p>'.$des_bottom.'</p>';
        }
        $cat_meta_keys = $this->getListFromListByField($categoriesExt['object']['categories_meta_key'], 'link_id', $category['categories_id']);
        $cat_data['meta_title'] = $this->getRowValueFromListByField($cat_meta_keys, 'language_code', $this->_notice['config']['default_lang'], 'meta_title');
        $cat_data['meta_keywords'] =  $this->getRowValueFromListByField($cat_meta_keys, 'language_code', $this->_notice['config']['default_lang'], 'meta_keywords');
        $cat_data['meta_description'] = $this->getRowValueFromListByField($cat_meta_keys, 'language_code', $this->_notice['config']['default_lang'], 'meta_description');
        if($category['categories_image'] && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), 'org/'.$category['categories_image'], 'catalog/category')){
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['categories_status'];
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_code => $store_id){
            $store_data = array();
            $store_name = $this->getRowValueFromListByField($catDesc, 'language_code', $lang_code, 'categories_name');
            if($lang_code != $this->_notice['config']['default_lang'] && $store_name){
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_name;
                $store_data['description'] = $this->getRowValueFromListByField($catDesc, 'language_code', $lang_code, 'categories_description');
                if($store_des_bottom = $this->getRowValueFromListByField($catDesc, 'language_code', $lang_code, 'categories_description_bottom')){
                    $store_data['description'] = $store_data['description'].'<p>'.$store_des_bottom.'</p>';
                }
                $store_data['meta_title'] = $this->getRowValueFromListByField($cat_meta_keys, 'language_code', $lang_code, 'meta_title');
                $store_data['meta_keywords'] =  $this->getRowValueFromListByField($cat_meta_keys, 'language_code', $lang_code, 'meta_keywords');
                $store_data['meta_description'] = $this->getRowValueFromListByField($cat_meta_keys, 'language_code', $lang_code, 'meta_description');
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
        $this->_notice['extend']['website_ids']= $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    /**
     * Query for get data of main table use for import product
     *
     * @return string
     */
    protected function _getProductsMainQuery(){
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT * FROM _DBPRF_products
                    WHERE (products_master_model IS NULL OR products_master_model = '') AND products_id > {$id_src} ORDER BY products_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products){
        $productModels = $this->duplicateFieldValueFromList($products['object'], 'products_model');
        $pro_models_query = $this->arrayToInCondition($productModels);
        $ext_query = array(
            'products_children' => "SELECT * FROM _DBPRF_products WHERE products_master_model IN {$pro_models_query}",

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
        $allProIds = $this->duplicateFieldValueFromList(array_merge($products['object'], $productsExt['object']['products_children']), 'products_id');
        $all_pro_ids_query = $this->arrayToInCondition($allProIds);
        $ext_rel_query = array(
            'option_attribute_des' => "SELECT * FROM _DBPRF_plg_products_attributes_description",
            'products_description' => "SELECT * FROM _DBPRF_products_description WHERE products_id IN {$all_pro_ids_query}",
            'products_to_categories' => "SELECT * FROM _DBPRF_products_to_categories WHERE products_id IN {$all_pro_ids_query}",
            'option_to_product' => "SELECT pro_to_attr.*, pr_attr.attributes_model FROM _DBPRF_plg_products_to_attributes AS pro_to_attr
                                    LEFT JOIN _DBPRF_plg_products_attributes AS pr_attr ON pr_attr.attributes_id = pro_to_attr.attributes_parent_id
                                    WHERE pro_to_attr.products_id IN {$all_pro_ids_query}",
            'product_special_price' => "SELECT * FROM _DBPRF_products_price_special WHERE products_id IN {$all_pro_ids_query}",
            'products_price_group_1' => "SELECT * FROM _DBPRF_products_price_group_1 WHERE products_id IN {$all_pro_ids_query}",
            'products_price_group_2' => "SELECT * FROM _DBPRF_products_price_group_2 WHERE products_id IN {$all_pro_ids_query}",
            'products_price_group_3' => "SELECT * FROM _DBPRF_products_price_group_3 WHERE products_id IN {$all_pro_ids_query}",
            'products_price_group_all' => "SELECT * FROM _DBPRF_products_price_group_all WHERE products_id IN {$all_pro_ids_query}",
            'product_galleries' => "SELECT ml.*, md.*
                                    FROM _DBPRF_media_link AS ml
                                    LEFT JOIN _DBPRF_media AS md ON md.id = ml.m_id
                                    WHERE ml.link_id IN {$all_pro_ids_query}",
            'product_seo' => "SELECT * FROM _DBPRF_seo_url AS seo WHERE seo.link_id IN {$all_pro_ids_query} AND seo.link_type = 1",
            'products_cross_sell' => "SELECT * FROM _DBPRF_products_cross_sell WHERE products_id IN {$all_pro_ids_query} OR products_id_cross_sell IN {$all_pro_ids_query}"

        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return int
     */
    public function getProductId($product, $productsExt){
        return $product['products_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return array
     */
    public function convertProduct($product, $productsExt){
        if(LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        if($product['products_master_flag'] == 1){
            $children_product = $this->getListFromListByField($productsExt['object']['products_children'], 'products_master_model', $product['products_model']);
            if($children_product){
                $type_id = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
                $config_data = $this->_importChildrenProduct($product, $children_product, $productsExt);
                if($config_data['result'] != 'success'){
                    return $config_data;
                }
                $pro_data = array_merge($config_data['data'], $pro_data);
            }
        }
        $pro_data = array_merge($this->_convertProduct($product, $productsExt, $visibility, $type_id),$pro_data);
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }

    protected function _convertProduct($product, $productsExt, $visibility, $type_id){
        $pro_data = array();
        $pro_data['type_id'] = $type_id;
        $categories = array();
        $proCat = $this->getListFromListByField($productsExt['object']['products_to_categories'], 'products_id', $product['products_id']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['categories_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['price'] = $product['products_price'];
        $sp_price_list = $this->getListFromListByField($productsExt['object']['product_special_price'], 'products_id', $product['products_id']);
        $special_price = $this->_getSpecialPriceProductFromList($sp_price_list);
        $pro_data['special_price'] = $special_price['specials_price'];
        $pro_data['special_from_date'] = $this->_cookSpecialDate($special_price['date_available']);
        $pro_data['special_to_date'] = $this->_cookSpecialDate($special_price['date_expired']);

        $tierPrices = $grandPrices_guest = $grandPrices_new_customer = $grandPrices_merchant = $grandPrices_all = array();
        $grandPrices_guest = $this->getListFromListByField($productsExt['object']['products_price_group_1'], 'products_id', $product['products_id']);
        $grandPrices_new_customer = $this->getListFromListByField($productsExt['object']['products_price_group_2'], 'products_id', $product['products_id']);
        $grandPrices_merchant = $this->getListFromListByField($productsExt['object']['products_price_group_3'], 'products_id', $product['products_id']);
        $grandPrices_all = $this->getListFromListByField($productsExt['object']['products_price_group_all'], 'products_id', $product['products_id']);
        if($grandPrices_guest) $tierPrices = array_merge($tierPrices,$this->_createGrandPriceProduct($grandPrices_guest, 0));
        if($grandPrices_new_customer) $tierPrices = array_merge($tierPrices,$this->_createGrandPriceProduct($grandPrices_new_customer, 1));
        if($grandPrices_merchant) $tierPrices = array_merge($tierPrices,$this->_createGrandPriceProduct($grandPrices_merchant, 2));
        if($grandPrices_all) $tierPrices = array_merge($tierPrices,$this->_createGrandPriceProduct($grandPrices_all, 32000));
        $pro_data['tier_price'] = $tierPrices;

        $list_des = $this->getListFromListByField($productsExt['object']['products_description'], 'products_id', $product['products_id']);
        $des_default = $this->getRowFromListByField($list_des, 'language_code', $this->_notice['config']['default_lang']);
        $sku = $product['products_model'];
        if(!$sku) $sku = $des_default['products_name'];
        $pro_data['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];

        $pro_data['category_ids'] = $categories;
        $pro_data['name'] = $des_default['products_name'] ? $des_default['products_name'] : ' ';
        $pro_data['description'] = $this->changeImgSrcInText($des_default['products_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($des_default['products_short_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['weight']   =  ($product['products_weight'])? $product['products_weight'] : 0;
        $pro_data['status'] = ($product['products_status']== 1)? 1 : 2;
        if($product['products_tax_class_id'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['products_tax_class_id'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['date_added'];
        $pro_data['visibility'] = $visibility;
        $pro_data['stock_data'] = array(
            'is_in_stock' =>  1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['products_quantity'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['products_quantity'] < 1)? 0 : 1,
            'qty' => $product['products_quantity'],
        );
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        if($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturers_id'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        if($product['products_image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'org/'.$product['products_image'], 'catalog/product', false, true)){
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => ' ');
        }
        $productImages = $this->getListFromListByField($productsExt['object']['product_galleries'], 'link_id', $product['products_id']);
        if($productImages){
            foreach($productImages as $gallery){
                if($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'org/'.$gallery['file'], 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => ' ') ;
                }
            }
        }
        $list_meta = $this->getListFromListByField($productsExt['object']['product_seo'], 'link_id', $product['products_id']);
        $meta_default = $this->getRowFromListByField($list_meta, 'language_code', $this->_notice['config']['default_lang']);
        $pro_data['meta_title'] = $meta_default['meta_title'] ? $meta_default['meta_title'] : ' ';
        $pro_data['meta_keyword'] = $meta_default['meta_keywords'] ? $meta_default['meta_keywords'] : ' ';
        $pro_data['meta_description'] = $meta_default['meta_description'] ? $meta_default['meta_description'] : ' ';
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $key => $value){
            if($key != $this->_notice['config']['default_lang']){
                $store_data = array();
                if($store_data_change = $this->getRowFromListByField($list_des, 'language_code', $key)){
                    if($store_data_change['products_name']) $store_data['name'] = $store_data_change['products_name'];
                    if($store_data_change['products_description']) $store_data['description'] = $this->changeImgSrcInText($store_data_change['products_description'], $this->_notice['config']['add_option']['img_des']);
                    if($store_data_change['products_short_description']) $store_data['short_description'] = $this->changeImgSrcInText($store_data_change['products_short_description'], $this->_notice['config']['add_option']['img_des']);
                }
                if($store_meta_change = $this->getRowFromListByField($list_meta, 'language_code', $key)){
                    if($store_meta_change['meta_title']) $store_data['meta_title'] = $store_meta_change['meta_title'];
                    if($store_meta_change['meta_keywords']) $store_data['meta_keyword'] = $store_meta_change['meta_keywords'];
                    if($store_meta_change['meta_description']) $store_data['meta_description'] = $store_meta_change['meta_description'];
                }
                if(!empty($store_data)){
                    $store_data['store_id'] = $value;
                    $multi_store[] = $store_data;
                }
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
        return $pro_data;
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
        $products_links = Mage::getModel('catalog/product_link_api');
        $proCross = $this->getListFromListByField($productsExt['object']['products_cross_sell'], 'products_id', $product['products_id']);
        if($proCross){
            foreach($proCross as $pro_cross){
                if($pro_id_cross = $this->getMageIdProduct($pro_cross['products_id_cross_sell'])){
                    $products_links->assign("cross_sell", $product_mage_id, $pro_id_cross);
                }else{
                    continue;
                }
            }
        }
        $proSrc = $this->getListFromListByField($productsExt['object']['products_cross_sell'], 'products_id_cross_sell', $product['products_id']);
        if($proSrc){
            foreach($proSrc as $pro_src){
                if($proSrcId = $this->getMageIdProduct($pro_src['products_id'])){
                    $products_links->assign("cross_sell", $proSrcId, $product_mage_id);
                }else{
                    continue;
                }
            }
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
        $query = "SELECT * FROM _DBPRF_customers WHERE customers_id > {$id_src} ORDER BY customers_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'customers_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            "customer_address" => "SELECT * FROM _DBPRF_customers_addresses WHERE customers_id IN {$customer_ids_query}"
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
        $states_ids = $this->duplicateFieldValueFromList($customersExt['object']['customer_address'], 'customers_federal_state_code');
        $statesIds_in_query = $this->arrayToInCondition($states_ids);
        $ext_rel_query = array(
            "states" => "SELECT * FROM _DBPRF_federal_states WHERE states_id IN {$statesIds_in_query}"
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
        return $customer['customers_id'];
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
        $customer_address = $this->getRowFromListByField($customersExt['object']['customer_address'], 'customers_id', $customer['customers_id']);
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['customers_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['customers_email_address'];
        $cus_data['taxvat'] = $customer['customers_vat_id'];
        $cus_data['firstname'] = $customer_address['customers_firstname'];
        $cus_data['lastname'] = $customer_address['customers_lastname'];
        $cus_data['gender'] = ($customer_address['customers_gender'] == 'm')? 1 : 2;
        $cus_data['dob'] = $customer_address['customers_dob'];
        $cus_data['created_at'] = $this->_datetimeToDate($customer['date_added']);
        $cus_data['is_subscribed'] = isset($customer['nl2go_newsletter_status']) ? $customer['nl2go_newsletter_status'] : '';
        $customers_status = $customer['customers_status'];
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$customers_status]) ? $this->_notice['config']['customer_group'][$customers_status] : 1;
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
            return ;
        }
        $this->_importCustomerRawPass($customer_mage_id, $customer['customers_password']);
        $cusAdd = $this->getListFromListByField($customersExt['object']['customer_address'], 'customers_id', $customer['customers_id']);
        if($cusAdd){
            $check_default = false;
            foreach($cusAdd as $cus_add){
                if(in_array($cus_add, array('payment', 'shipping'))){
                    $check_default = true;
                }
            }
            foreach($cusAdd as $key => $cus_add){
                $address = array();
                $address['firstname'] = $cus_add['customers_firstname'];
                $address['lastname'] = $cus_add['customers_lastname'];
                $address['country_id'] = $cus_add['customers_country_code'];
                $address['street'] = $cus_add['customers_street_address'];
                $address['postcode'] = $cus_add['customers_postcode'];
                $address['city'] = $cus_add['customers_city'];
                $address['telephone'] = $cus_add['customers_phone'];
                $address['company'] = $cus_add['customers_company'];
                $address['fax'] = $cus_add['customers_fax'];
                if(isset($cus_add['customers_federal_state_code'])){
                    $states_code = $this->getRowValueFromListByField($customersExt['object']['states'], 'states_id', $cus_add['customers_federal_state_code'], 'states_code');
                    $address['region_id'] = $this->_getRegionIdByCode($states_code, $address['country_id']);
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success'){
                    if($check_default){
                        try{
                            $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                            if($cus_add['address_class'] == 'payment') $cus->setDefaultBilling($address_ipt['mage_id']);
                            if($cus_add['address_class'] == 'shipping') $cus->setDefaultShipping($address_ipt['mage_id']);
                            $cus->save();
                        }catch (Exception $e){}
                    }else{
                        if($key == 0){
                            try{
                                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                                $cus->setDefaultBilling($address_ipt['mage_id']);
                                $cus->setDefaultShipping($address_ipt['mage_id']);
                                $cus->save();
                            }catch (Exception $e){}
                        }
                    }
                }
            }
        }
    }

    /**
     * Query for get data use for import order
     *
     * @return string
     */
    protected function _getOrdersMainQuery(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_orders WHERE orders_id > {$id_src} ORDER BY orders_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders){
        $bilStates = $this->duplicateFieldValueFromList($orders['object'], 'billing_federal_state_code');
        $delStates = $this->duplicateFieldValueFromList($orders['object'], 'delivery_federal_state_code');
        $stateIds_in_query = $this->arrayToInCondition(array_unique(array_merge($bilStates, $delStates)));
        $customers = $this->duplicateFieldValueFromList($orders['object'], 'customers_id');
        $customers_in_query = $this->arrayToInCondition($customers);
        $order_ids = $this->duplicateFieldValueFromList($orders['object'], 'orders_id');
        $order_ids_in_query = $this->arrayToInCondition($order_ids);
        $ext_query = array(
            "states" => "SELECT * FROM _DBPRF_federal_states WHERE states_id IN {$stateIds_in_query}",
            "customer" => "SELECT * FROM _DBPRF_customers_addresses WHERE customers_id IN {$customers_in_query}",
            "order_products" => "SELECT op.*, p.products_master_model FROM _DBPRF_orders_products AS op
                                    LEFT JOIN _DBPRF_products AS p ON op.products_id = p.products_id
                                    WHERE orders_id IN {$order_ids_in_query}",
            "orders_status_history" => "SELECT * FROM _DBPRF_orders_status_history WHERE orders_id IN {$order_ids_in_query}",
            "orders_total" => "SELECT * FROM _DBPRF_orders_total WHERE orders_id IN {$order_ids_in_query}",
            "orders_stats" => "SELECT * FROM _DBPRF_orders_stats WHERE orders_id IN {$order_ids_in_query}"
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
        $pro_order_ids = $this->duplicateFieldValueFromList($ordersExt['object']['order_products'], 'products_id');
        $proOrder_ids_in_query = $this->arrayToInCondition($pro_order_ids);
        $parent_pro_ids = $this->duplicateFieldValueFromList($ordersExt['object']['order_products'], 'products_master_model');
        $parent_pro_ids_in_query = $this->arrayToInCondition($parent_pro_ids);
        $ext_rel_query = array(
            "parent_products" => "SELECT * FROM _DBPRF_products AS pr
                                    LEFT JOIN _DBPRF_products_description AS pr_des ON pr_des.products_id = pr.products_id
                                    AND pr_des.language_code = '{$this->_notice['config']['default_lang']}'
                                    WHERE products_model IN {$parent_pro_ids_in_query}
                                    AND products_master_flag = 1",
            "option_to_product" => "SELECT * FROM _DBPRF_plg_products_to_attributes WHERE products_id IN {$proOrder_ids_in_query}",
            "option_attribute_des" => "SELECT * FROM _DBPRF_plg_products_attributes_description WHERE language_code = '{$this->_notice['config']['default_lang']}'",
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return int
     */
    public function getOrderId($order, $ordersExt){
        return $order['orders_id'];
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

        $address_billing['firstname'] = $order['billing_firstname'];
        $address_billing['lastname'] = $order['billing_lastname'];
        $address_billing['company'] = $order['billing_company'];
        $address_billing['email']   = $order['customers_email_address'];
        $address_billing['street']  = $order['billing_street_address'];
        $address_billing['city'] = $order['billing_city'];
        $address_billing['postcode'] = $order['billing_postcode'];
        $address_billing['country_id'] = $order['billing_country_code'];
        $address_billing['telephone'] = $order['billing_phone'];
        if(isset($order['billing_federal_state_code'])){
            $bill_region_code = $this->getRowValueFromListByField($ordersExt['object']['states'], 'states_id', $order['billing_federal_state_code'], 'states_code');
            if($bill_region_id = $this->_getRegionIdByCode($bill_region_code, $address_billing['country_id'])){
                $address_billing['region_id'] = $bill_region_id;
            }else{
                $address_billing['region'] = $order['billing_federal_state_code'];
            }
        }
        $address_billing['save_in_address_book'] = true;

        $address_shipping['firstname'] = $order['delivery_firstname'];
        $address_shipping['lastname'] = $order['delivery_lastname'];
        $address_shipping['company'] = $order['delivery_company'];
        $address_shipping['email']   = $order['customers_email_address'];
        $address_shipping['street']  = $order['delivery_street_address'];
        $address_shipping['city'] = $order['delivery_city'];
        $address_shipping['postcode'] = $order['delivery_postcode'];
        $address_shipping['country_id'] = $order['delivery_country_code'];
        $address_shipping['telephone'] = $order['delivery_phone'];
        if(isset($order['delivery_federal_state_code'])){
            $del_region_code = $this->getRowValueFromListByField($ordersExt['object']['states'], 'states_id', $order['delivery_federal_state_code'], 'states_code');
            if($del_region_id = $this->_getRegionIdByCode($del_region_code, $address_shipping['country_id'])){
                $address_shipping['region_id'] = $del_region_id;
            }else{
                $address_shipping['region'] = $order['delivery_federal_state_code'];
            }
        }
        $address_shipping['save_in_address_book'] = true;

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $order_data = array();
        $order_data['store_id'] = $store_id;
        $customer_mage_id = $this->getMageIdCustomer($order['customers_id']);
        if($customer_mage_id){
            $order_data['customer_id'] = $customer_mage_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $detail_customer = $this->getRowFromListByField($ordersExt['object']['customer'], 'customers_id', $order['customers_id']);
        $order_data['customer_email'] = $order['customers_email_address'];
        $order_data['customer_firstname'] = $detail_customer['customers_firstname'] ? $detail_customer['customers_firstname'] : $address_billing['firstname'];
        $order_data['customer_lastname'] = $detail_customer['customers_lastname'] ? $detail_customer['customers_lastname'] : $address_billing['lastname'];
        $status_create = $order['orders_status'];
        $order_data['status'] = $this->_notice['config']['order_status'][$status_create];
        $order_data['state'] =  $this->getOrderStateByStatus($order_data['status']);
        $orderProducts = $this->getListFromListByField($ordersExt['object']['order_products'], 'orders_id', $order['orders_id']);
        $carts = array();
        $order_data['tax_amount'] = $order_data['subtotal'] = $order_data['shipping_amount'] = 0;
        if($orderProducts){
            foreach($orderProducts as $item){
                $cart = array();
                if($item['products_master_model']){
                    $parent_product = $this->getRowFromListByField($ordersExt['object']['parent_products'], 'products_model', $item['products_master_model']);
                    $product_id = $this->getMageIdProduct($parent_product['products_id']);
                    if($product_id){
                        $cart['product_id'] = $product_id;
                    }
                    $cart['name'] = $parent_product['products_name'];
                    $cart['sku'] = $parent_product['products_model'];
                    $option_to_products = $this->getListFromListByField($ordersExt['object']['option_to_product'], 'products_id', $item['products_id']);
                    $data_option = array();
                    if($option_to_products){
                        foreach($option_to_products as $opt_pro){
                            $data_option[] = $this->_getDataOptionChildProduct($opt_pro, $ordersExt['object']['option_attribute_des'], true);
                        }
                    }
                    if($data_option){
                        $cart['product_options'] = serialize($this->_createProductOrderOption($data_option));
                    }
                }else{
                    $product_id = $this->getMageIdProduct($item['products_id']);
                    if($product_id){
                        $cart['product_id'] = $product_id;
                    }
                    $cart['name'] = $item['products_name'];
                    $cart['sku'] = $item['products_model'];
                }
                $cart['price'] = $item['products_price'];
                $cart['original_price'] = $item['products_price'];
                $cart['tax_amount'] = ($item['products_price'] * $item['products_tax'])/100;
                $cart['tax_percent'] = $item['products_tax'];
                $cart['qty_ordered'] = $item['products_quantity'];
                $cart['row_total'] = ($item['products_price'] + $cart['tax_amount']) * $cart['qty_ordered'];

                $order_data['tax_amount'] += $cart['tax_amount'] * $cart['qty_ordered'];
                $order_data['subtotal'] +=  $cart['row_total'];
                $carts[]= $cart;
            }
        }
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_total = $this->getListFromListByField($ordersExt['object']['orders_total'],'orders_id', $order['orders_id']);
        $ot_shipping = $this->getListFromListByField($order_total, 'orders_total_key', 'shipping');
        if($ot_shipping){
            foreach($ot_shipping as $shipping_am){
                $tax_shipping = ($shipping_am['orders_total_price'] * $shipping_am['orders_total_tax'])/100;
                $order_data['shipping_amount'] += $shipping_am['orders_total_price'] + $tax_shipping;
            }
        }
        $order_data['base_shipping_amount'] =  $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order_data['shipping_amount'];
        $order_data['shipping_description'] = isset($ot_shipping[0]['orders_total_name']) ? $ot_shipping[0]['orders_total_name'] : '';
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = 0;
        $order_data['base_discount_amount'] = 0;
        $orders_stats_price = $this->getRowFromListByField($ordersExt['object']['orders_stats'], 'orders_id', $order['orders_id']);
        $order_data['grand_total'] = $this->incrementPriceToImport($orders_stats_price['orders_stats_price']);
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
        $order_data['created_at'] = $order['date_purchased'];

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['orders_id'];
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
    public function afterSaveOrder($order_mage_id, $data, $order, $ordersExt){
        if(parent::afterSaveOrder($order_mage_id, $data, $order, $ordersExt)){
            return ;
        }
        $orderStatus = $this->getListFromListByField($ordersExt['object']['orders_status_history'], 'orders_id', $order['orders_id']);
        if($orderStatus){
            foreach($orderStatus as $key => $order_status){
                $order_status_data = array();
                $order_status_id = $order_status['orders_status_id'];
                $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
                if($order_status_data['status']){
                    $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
                }
                if($key == 0){
                    $order_status_data['comment'] = "<b>Reference order #".$order['orders_id']."</b><br /><b>Payment method: </b>".$order['payment_code']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order_status['comments'];
                } else {
                    $order_status_data['comment'] = $order_status['comments'];
                }
                $order_status_data['is_customer_notified'] = 1;
                $order_status_data['updated_at'] = $order_status['date_added'];
                $order_status_data['created_at'] = $order_status['date_added'];
                $this->_process->ordersComment($order_mage_id, $order_status_data);
            }
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
        $query = "SELECT * FROM _DBPRF_products_reviews AS pr WHERE pr.review_id > {$id_src} ORDER BY pr.review_id ASC LIMIT {$limit}";
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
        return $review['review_id'];
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
        $product_mage_id = $this->getMageIdProduct($review['products_id']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['review_id']} import failed. Error: Product Id = {$review['products_id']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['review_status'] == 0)? 3 : 1;
        $data['title'] = $review['review_title'];
        $data['detail'] = $review['review_text'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['customers_id']))? $this->getMageIdCustomer($review['customers_id']) : null;
        $data['nickname'] = ' ';
        $data['rating'] = $review['review_rating'];
        $data['created_at'] = $review['review_date'];
        $data['review_id_import'] = $review['review_id'];
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
    protected function _createProductOrderOption($data){
        $result = array();
        foreach($data as $attribute){
            $option = array(
                'label' => $attribute['attribute_name'],
                'value' => $attribute['option_name'],
                'print_value' => $attribute['option_name'],
                'option_id' => 'option_pro',
                'option_type' => 'drop_down',
                'option_value' => 0,
                'custom_view' => false
            );
            $result[] = $option;
        }
        return array('options' => $result);
    }

    protected function _getRegionIdByCode($region_code, $country_code){
        $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
        $regionId = $regionModel->getId();
        if($regionId) return $regionId;
        return false;
    }

    protected function _importChildrenProduct($parent_product, $children_products, $productsExt){
        $result = array();
        $dataValueAttr = array();
        $dataChildes = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        foreach($children_products as $children){
            $valueAttrPro = $attribute_opt = array();
            $options_to_child = $this->getListFromListByField($productsExt['object']['option_to_product'], 'products_id', $children['products_id']);
            if($options_to_child){
                foreach($options_to_child as $option){
                    $attribute_opt[$option['attributes_parent_id']] = $option;
                }
            }
            foreach($attribute_opt as $attribute){
                $data_option = $this->_getDataOptionChildProduct($attribute, $productsExt['object']['option_attribute_des']);
                if(!$data_option){
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$parent_product['products_id']} import failed. Error: Product attribute could not create!")
                    );
                }
                $attr_import = $this->_makeAttributeImport($data_option['attribute_name'], $data_option['attribute_code'], $data_option['option_name'], $entity_type_id, $this->_notice['config']['attribute_set_id'], $data_option['option_multi_store'], $data_option['attribute_multi_store']);
                if(!$attr_import){
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$parent_product['products_id']} import failed. Error: Product attribute could not create!")
                    );
                }
                $dataOptAfterImport = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if(!$dataOptAfterImport){
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$parent_product['products_id']} import failed. Error: Product attribute could not create!")
                    );
                }
                $cookOptAfterImport[$dataOptAfterImport['attribute_id']] = $dataOptAfterImport['option_ids']['option_0'];
                $valueAttrPro = array_replace_recursive($valueAttrPro,$cookOptAfterImport);
            }
            $dataValueAttr[$children['products_id']] = $valueAttrPro;
        }
        $dataValueAttr = $this->_createDataOptionValue($dataValueAttr);
        foreach($children_products as $child){
            $optionValues = $dataValueAttr[$child['products_id']];
            $convertPro = $this->_convertProduct($child, $productsExt, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
            $pro_import = $this->_process->product($convertPro);
            if($pro_import['result'] !== 'success'){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent_product['products_id']} import failed. Error: Product children could not create!")
                );
            }
            $this->productSuccess($child['products_id'], $pro_import['mage_id']);
            if(!empty($optionValues)){
                foreach($optionValues as $attr => $optionValue){
                    $dataTMP['attribute_id'] = $attr;
                    $dataTMP['value_index'] =  $optionValue;
                    $dataTMP['is_percent'] = 0;
                    $dataChildes[$pro_import['mage_id']][] = $dataTMP;
                    $this->setProAttrSelect($entity_type_id, $dataTMP['attribute_id'], $pro_import['mage_id'], $dataTMP['value_index']);
                }
            }
        }
        if($dataChildes){
            $result = $this->_createConfigProductData($dataChildes);
        }
        return array(
            'result' => 'success',
            'data' => $result
        );
    }

    protected function _createGrandPriceProduct($grandPrices, $mage_group_customer){
        $tierPrices = array();
        foreach($grandPrices as $customer_price){
            $tierPrices[] = array(
                'website_id'  => 0,
                'cust_group'  => $mage_group_customer,
                'price_qty'   => $customer_price['discount_quantity'],
                'price'       => $customer_price['price']
            );
        }
        return $tierPrices;
    }

    protected function _getSpecialPriceProductFromList($list_sp_prices){
        $date = date('Y-m-d H:i:s');
        if($list_sp_prices && !empty($list_sp_prices)){
            foreach($list_sp_prices as $product_price){
                if($product_price['date_expired'] >= $date) return $product_price;
            }
            return $list_sp_prices[0];
        }
        return false;
    }

    protected function _createConfigProductData($dataChildes){
        $attribute_config = array();
        $result['configurable_products_data'] = $dataChildes;
        foreach (reset($dataChildes) as $attribute) {
            $detailAttr = $this->_getDetailAttributeById($attribute['attribute_id']);
            $attr_configurable['label'] = $detailAttr['attribute_label'];
            $attr_configurable['value'] = $detailAttr['option'];
            $attr_configurable['attribute_id'] = $attribute['attribute_id'];
            $attr_configurable['attribute_code'] = $detailAttr['attribute_code'];
            $attr_configurable['frontend_label'] = $detailAttr['attribute_label'];
            $attr_configurable['html_id'] = 'config_super_product__attribute_0';
            $attribute_config[] = $attr_configurable;
        }
        $result['configurable_attributes_data'] = $attribute_config;
        $result['can_save_configurable_attributes'] = 1;
        $result['affect_product_custom_options'] = 1;
        $result['affect_configurable_product_attributes'] = 1;
        return $result;
    }

    protected function _getDetailAttributeById($id){
        $result = false;
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
        $result['attribute_label'] = $attribute->getFrontEndLabel();
        $result['attribute_code'] = $attribute->getAttributeCode();
        $options = $attribute->getSource()->getAllOptions(false);
        foreach($options as $option){
            if($option['label']){
                $response['value_index'] = $option['value'];
                $response['label'] = $option['label'];
                $response['is_percent'] = 0;
                $response['pricing_value'] = 0;
                $response['attribute_id'] = $id;
                $result['option'][] = $response;
            }
        }
        return $result;
    }

    protected function _createDataOptionValue($dataValueAttr){
        foreach($dataValueAttr as $product_id => $dataValueOptions){
            foreach($dataValueAttr as $dataProTwo){
                foreach($dataProTwo as $attr_two => $value_two){
                    $check = 0;
                    foreach($dataValueOptions as $attr_one => $value_one){
                        if($attr_two == $attr_one){$check = 1; break;}
                    }
                    if($check == 0) $dataValueAttr[$product_id][$attr_two] = $value_two;
                }
            }
        }
        return $dataValueAttr;
    }

    protected function _getDataOptionChildProduct($option_to_child, $option_des_list, $only_des_list_default = false){
        $result = false;
        if($only_des_list_default){
            $result['option_name'] = $this->getRowValueFromListByField($option_des_list, 'attributes_id', $option_to_child['attributes_id'], 'attributes_name');
            $result['attribute_name'] = $this->getRowValueFromListByField($option_des_list, 'attributes_id', $option_to_child['attributes_parent_id'], 'attributes_name');
            return $result;
        }
        $opt_list = $this->getListFromListByField($option_des_list, 'attributes_id', $option_to_child['attributes_id']);
        $opt_default = $this->getRowValueFromListByField($opt_list, 'language_code', $this->_notice['config']['default_lang'], 'attributes_name');
        $attr_list = $this->getListFromListByField($option_des_list, 'attributes_id', $option_to_child['attributes_parent_id']);
        $attr_default = $this->getRowValueFromListByField($attr_list, 'language_code', $this->_notice['config']['default_lang'], 'attributes_name');
        $result['attribute_name'] = $attr_default;
        $result['attribute_code'] = $option_to_child['attributes_model'] ? $option_to_child['attributes_model'] : $this->joinTextToKey($result['attribute_name'], false, '_');
        if(!$result['attribute_code']) return false;
        $result['option_name'] = $opt_default;
        $result['option_store_id'] = '0';
        foreach($this->_notice['config']['languages'] as $lang_code => $store_id){
            $tmp_opt = $tmp_attr = array();
            if($lang_code == $this->_notice['config']['default_lang']){
                $tmp_opt['option_store_id'] = $store_id;
                $tmp_opt['option_name'] = $opt_default;
                $tmp_attr['attribute_store_id'] = $store_id;
                $tmp_attr['attribute_name'] = $attr_default;
                $result['attribute_multi_store'][] = $tmp_attr;
                $result['option_multi_store'][] = $tmp_opt;
            }else{
                if($store_opt_change = $this->getRowValueFromListByField($opt_list, 'language_code', $lang_code, 'attributes_name')){
                    $tmp_opt['option_store_id'] = $store_id;
                    $tmp_opt['option_name'] = $store_opt_change;
                    $result['option_multi_store'][] = $tmp_opt;
                }
                if($store_attr_change = $this->getRowValueFromListByField($attr_list, 'language_code', $lang_code, 'attributes_name')){
                    $tmp_attr['attribute_store_id'] = $store_id;
                    $tmp_attr['attribute_name'] = $store_attr_change;
                    $result['attribute_multi_store'][] = $tmp_attr;
                }
            }
        }
        return $result;
    }

    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_categories WHERE categories_id = {$parent_id}"
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

    protected function _makeAttributeImport($attribute_name, $attribute_code, $option_name, $entity_type_id, $attribute_set_id,  $opt_multi_store = false, $attr_multi_store = false){
        $multi_option = $multi_attr = $result = array();
        $multi_option[0] = $option_name;
        $multi_attr[0] = $attribute_name;
        if($opt_multi_store){
            foreach($opt_multi_store as $opt_store){
                $multi_option[$opt_store['option_store_id']] = $opt_store['option_name'];
            }
        }
        if($attr_multi_store){
            foreach($attr_multi_store as $attr_store){
                $multi_attr[$attr_store['attribute_store_id']] = $attr_store['attribute_name'];
            }
        }
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attribute_code,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => 'select',
            'frontend_label' => $multi_attr,
            'is_visible_on_front' => 1,
            'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'is_configurable' => true,
            'option' => array(
                'value' => array('option_0' => $multi_option)
            )
        );
        $edit = array(
            'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'is_configurable' => true,
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
        if(empty($result['config'])) return false;
        return $result;
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
        $query = "SELECT * FROM _DBPRF_tax_class ORDER BY tax_class_id ASC";
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
        $query = "SELECT * FROM _DBPRF_manufacturers ORDER BY manufacturers_id ASC";
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
        $query = "SELECT * FROM _DBPRF_categories ORDER BY categories_id ASC";
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
        $query = "SELECT * FROM _DBPRF_products
                    WHERE (products_master_model IS NULL OR products_master_model = '') ORDER BY products_id ASC";
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
        $query = "SELECT * FROM _DBPRF_customers ORDER BY customers_id ASC";
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
        $query = "SELECT * FROM _DBPRF_orders ORDER BY orders_id ASC";
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
        $query = "SELECT * FROM _DBPRF_products_reviews ORDER BY pr.review_id ASC";
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