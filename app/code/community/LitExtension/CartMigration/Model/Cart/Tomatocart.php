<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Tomatocart
    extends LitExtension_CartMigration_Model_Cart{

    const CONFIGURABLE_PRODUCT = false;

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
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE products_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customers_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orders_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE reviews_id > {$this->_notice['reviews']['id_src']}"
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
                "languages" => "SELECT cfg.*, lg.* FROM _DBPRF_configuration AS cfg LEFT JOIN _DBPRF_languages AS lg ON lg.code = cfg.configuration_value WHERE cfg.configuration_key = 'DEFAULT_LANGUAGE'",
                "currencies" => "SELECT cfg.*, cur.* FROM _DBPRF_configuration AS cfg LEFT JOIN _DBPRF_currencies AS cur ON cur.code = cfg.configuration_value WHERE cfg.configuration_key = 'DEFAULT_CURRENCY'"
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['languages_id']) ? $object['languages']['0']['languages_id'] : 1;
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['currencies_id']) ? $object['currencies']['0']['currencies_id'] : 1;
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_languages",
                "currencies" => "SELECT * FROM _DBPRF_currencies",
                "orders_status" => "SELECT * FROM _DBPRF_orders_status WHERE language_id = '{$this->_notice['config']['default_lang']}'"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach($obj['languages'] as $language_row){
            $lang_id = $language_row['languages_id'];
            $lang_name = $language_row['name'] . "(" . $language_row['code'] . ")";
            $language_data[$lang_id] = $lang_name;
        }
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['currencies_id'];
            $currency_name = $currency_row['title'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['orders_status_id'];
            $order_status_name = $order_status_row['orders_status_name'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['config_support']['customer_group_map'] = false;
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
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE products_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customers_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orders_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE reviews_id > {$this->_notice['reviews']['id_src']}"
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
            'tax_rates' => "SELECT tr.*, gz.*
                                    FROM _DBPRF_tax_rates AS tr
                                        LEFT JOIN _DBPRF_geo_zones AS gz ON gz.geo_zone_id = tr.tax_zone_id
                                    WHERE tr.tax_class_id IN {$tax_id_con}"
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
            'zones_to_geo_zones' => "SELECT ztgz.*, z.*, c.*
                                              FROM _DBPRF_zones_to_geo_zones AS ztgz
                                                  LEFT JOIN _DBPRF_zones AS z ON z.zone_id = ztgz.zone_id
                                                  LEFT JOIN _DBPRF_countries AS c ON c.countries_id = ztgz.zone_country_id
                                              WHERE ztgz.geo_zone_id IN {$tax_zone_query}"
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
        foreach($taxRates as $tax_rate){
            $taxZone = $this->getListFromListByField($taxesExt['object']['zones_to_geo_zones'], 'geo_zone_id', $tax_rate['geo_zone_id']);
            foreach($taxZone as $tax_zone){
                if(!$tax_zone['countries_iso_code_2']){
                    continue ;
                }
                $tax_rate_data = array();
                $code = $tax['tax_class_title'] . "-" . $tax_rate['geo_zone_name'] . "-". $tax_zone['countries_name'];
                if($tax_zone['zone_name']){
                    $code .= "-". $tax_zone['zone_name'];
                }
                $tax_rate_data['code'] = $this->createTaxRateCode($code);
                $tax_rate_data['tax_country_id'] = $tax_zone['countries_iso_code_2'];
                if(!$tax_zone['zone_id']){
                    $tax_rate_data['tax_region_id'] = 0;
                } else {
                    $tax_rate_data['tax_region_id'] = $this->getRegionId($tax_zone['zone_name'], $tax_zone['countries_iso_code_2']);
                }
                $tax_rate_data['zip_is_range'] = 0;
                $tax_rate_data['tax_postcode'] = "*";
                $tax_rate_data['rate'] = $tax_rate['tax_rate'];
                $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                if($tax_rate_ipt['result'] == 'success'){
                    $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
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
            'categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$cat_id_con}"
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
        $cat_name = $this->getRowValueFromListByField($catDesc, 'language_id', $this->_notice['config']['default_lang'], 'categories_name');
        $cat_data['name'] = $cat_name ? $cat_name : " ";
        $cat_data['meta_title'] = $catDesc[0]['categories_page_title'];
        $cat_data['meta_keywords'] = $catDesc[0]['categories_meta_keywords'];
        $cat_data['meta_description'] = $catDesc[0]['categories_meta_description'];
        $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  $category['categories_image'], 'catalog/category');
        if($category['categories_image'] && $img_path){
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['categories_status'] ==1)?1:0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_id => $store_id){
            $store_data = array();
            $store_name = $this->getRowValueFromListByField($catDesc, 'language_id', $lang_id, 'categories_name');
            if($lang_id != $this->_notice['config']['default_lang'] && $store_name){
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_name;
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
        $query = "SELECT * FROM _DBPRF_products WHERE products_id > {$id_src} ORDER BY products_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'products_id');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'products_description' => "SELECT * FROM _DBPRF_products_description WHERE products_id IN {$pro_ids_query}",
            'products_images' => "SELECT * FROM _DBPRF_products_images WHERE products_id IN {$pro_ids_query}",
            'products_to_categories' => "SELECT * FROM _DBPRF_products_to_categories WHERE products_id IN {$pro_ids_query}",
            'products_attributes' => "SELECT attr.products_id,attr.products_attributes_values_id,attr.language_id,
                                      attr.value AS val,value.products_attributes_groups_id,value.module,value.name,value.value,groups.products_attributes_groups_name
                                      FROM  _DBPRF_products_attributes AS attr
                                      LEFT JOIN  _DBPRF_products_attributes_values AS value ON ( attr.products_attributes_values_id = value.products_attributes_values_id ) 
                                      LEFT JOIN  _DBPRF_products_attributes_groups AS groups ON ( value.products_attributes_groups_id = groups.products_attributes_groups_id ) WHERE products_id IN {$pro_ids_query}",
            'specials' => "SELECT * FROM _DBPRF_specials WHERE products_id IN {$pro_ids_query}",
            'products_cross_sell' => "SELECT * FROM _DBPRF_products_xsell WHERE products_id IN {$pro_ids_query} OR xsell_products_id IN {$pro_ids_query}",
            'product_variation' => "SELECT * FROM  _DBPRF_products_variants AS variants
                                    LEFT JOIN  _DBPRF_products_variants_entries AS variants_entries ON ( variants.products_variants_id = variants_entries.products_variants_id ) 
                                    LEFT JOIN  _DBPRF_products_variants_values AS variants_values ON ( variants_entries.products_variants_values_id = variants_values.products_variants_values_id )
                                    LEFT JOIN _DBPRF_products_variants_groups AS variants_groups ON ( variants_groups.products_variants_groups_id = variants_entries.products_variants_groups_id )
                                    LEFT JOIN _DBPRF_products_images AS variants_images ON ( variants.products_images_id = variants_images.id ) "
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
        $productOptionIds = $this->duplicateFieldValueFromList($productsExt['object']['products_attributes'], 'products_attributes_values_id');
        $AttributeGroups = $this->duplicateFieldValueFromList($productsExt['object'][''], $field);
        $ext_rel_query = array(
            'products_options' => "SELECT * FROM _DBPRF_products_attributes WHERE products_id IN {$product_option_ids_query}",
            'products_options_values' => "SELECT * FROM _DBPRF_products_attributes_values WHERE products_attributes_values_id IN {$product_option_ids_query}",
            'customization_fields' => "SELECT * 
                                        FROM  _DBPRF_customization_fields AS field
                                        LEFT JOIN  _DBPRF_customization_fields_description AS description ON ( field.customization_fields_id = description.customization_fields_id )"
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

    public function convertProduct($product, $productsExt){
        if(LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        //Migrate attribute type text
        $pro_data = $this->customAttr($product, $productsExt,$pro_data,1,'text');
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $variants = $this->getListFromListByField($productsExt['object']['product_variation'],'products_id', $product['products_id']);
        if($variants){
            $type_id = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $config_data = $this->_importChildrenProduct($product, $productsExt, $variants);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data = array_merge($config_data['data'], $pro_data);
            
        }
        $pro_data = array_merge($this->_convertProduct($product, $productsExt, $type_id), $pro_data);
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
        //Migrate attribute to attribute
        $this->customAttr($product, $productsExt,1, $product_mage_id, 'pull_down_menu');
        //Migrate customizations to custom option
        $proCustom= $this->getListFromListByField($productsExt['object']['customization_fields'], 'products_id', $product['products_id']);
        if($proCustom && !self::CONFIGURABLE_PRODUCT){
            $opt_data = array();
            $proOptId = $this->duplicateFieldValueFromList($proCustom, 'customization_fields_id');
            foreach($proOptId as $pro_opt_id){
                $proOptVal = $this->getListFromListByField($productsExt['object']['customization_fields'],'customization_fields_id', $pro_opt_id);
                if(!$proOptVal){
                    continue ;
                }
                $name = $this->getRowValueFromListByField($proOptVal, 'languages_id', $this->_notice['config']['default_lang'], 'name');
                $type = $this->getRowValueFromListByField($proOptVal, 'languages_id', $this->_notice['config']['default_lang'], 'type');
                $require = $this->getRowValueFromListByField($proOptVal, 'languages_id', $this->_notice['config']['default_lang'], 'is_required');
                if($type == 1){
                    $option = array(
                        'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_TEXT,
                        'type' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_FIELD,
                        'is_require' => $require,
                        'title' => $name
                        );
                }else{
                    $option = array(
                        'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_FILE,
                        'type' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_FILE,
                        'is_require' => $require,
                        'title' => $name
                    );
                }
                $opt_data[] = $option;
            }
            $this->importProductOption($product_mage_id, $opt_data);
        }
        $products_links = Mage::getModel('catalog/product_link_api');
        $proCross = $this->getListFromListByField($productsExt['object']['products_cross_sell'], 'products_id', $product['products_id']);
        if($proCross){
            foreach($proCross as $pro_cross){
                if($pro_id_cross = $this->getMageIdProduct($pro_cross['xsell_products_id'])){
                    $products_links->assign("cross_sell", $product_mage_id, $pro_id_cross);
                }else{
                    continue;
                }
            }
        }
        $proSrc = $this->getListFromListByField($productsExt['object']['xsell_products_id'], 'xsell_products_id', $product['products_id']);
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
     * function migrate attribute
     *
     * @return string
     */
    protected function customAttr($product,$productsExt,$pro_data,$product_mage_id,$type){
        $notice = $this->getNotice();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $proAttrs = $this->getListFromListByField($productsExt['object']['products_attributes'], 'products_id', $product['products_id']);
        //Migrate Attribute
        if($type == 'pull_down_menu'){
            $dataOpts = array();
            $proAttrs = $this->getListFromListByField($proAttrs,'module','pull_down_menu');
            if($proAttrs){
                foreach ($proAttrs as $proAttr){
                    $attr_code = $proAttr['name'];
                    $attr_type = 'select';
                      $value = array();
                      $attr_values = explode(',',$proAttr['value']);
                      $option = "option_";
                      $count = 1;
                      foreach($attr_values as $v){
                          if($proAttr['val'] == $count) $value_index = $option.$count;
                          $_value = array($v);
                          $value[$option.$count] = $_value;
                          $count++;
                      }
                    $attr = array(
                      'entity_type_id'                => $entity_type_id,
                      'attribute_set_id'              => $notice['config']['attribute_set_id'],
                      'attribute_code'                => $attr_code,
                      'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
                      'frontend_input'                => $attr_type,
                      'frontend_label'                => array($attr_code),
                      'option'                        => array(
                          'value' => $value
                      )
                  );
                  $optAttrDataImport = $this->_process->attribute($attr);
                  $optAttrDataImport = $this->_process->attribute($attr);
                  $dataTMP = array(
                              'attribute_id' => $optAttrDataImport['attribute_id'],
                              'value_index' => $optAttrDataImport['option_ids'][$value_index],
                              'is_percent' => 0,
                          );
                  $dataOpts[] = $dataTMP;
                }
                foreach ($dataOpts as $dataAttribute) {
                    $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $product_mage_id, $dataAttribute['value_index']);
                }
            }
        }else
            {
                $proAttrs = $this->getListFromListByField($proAttrs,'module','text_field');
                $_pro_data = array();
                foreach ($proAttrs as $proAttr){
                    $attr_code = $proAttr['name'];
                    $attr_type = 'text';
                    $attr = array(
                    'entity_type_id'                => $entity_type_id,
                    'attribute_set_id'              => $notice['config']['attribute_set_id'],
                    'attribute_code'                => $attr_code,
                    'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
                    'frontend_input'                => $attr_type,
                    'frontend_label'                => array($attr_code),
                    'option'                        => array(
                        'value' => array(
                            )
                        )
                    );
                    $optAttrDataImport = $this->_process->attribute($attr);
                    $_pro_data[$attr_code] = $proAttr['val'];
                    $pro_data = array_merge($_pro_data,$pro_data);
                }
                return $pro_data;
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
            'customers_info' => "SELECT * FROM _DBPRF_customers_info WHERE customers_info_id IN {$customer_ids_query}",
            'address_book' => "SELECT ab.*, c.*, z.*
                                            FROM _DBPRF_address_book AS ab
                                                LEFT JOIN _DBPRF_countries AS c ON ab.entry_country_id = c.countries_id
                                                LEFT JOIN _DBPRF_zones AS z ON ab.entry_zone_id = z.zone_id
                                            WHERE ab.customers_id IN {$customer_ids_query}"
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
        return array();
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
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['customers_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['customers_email_address'];
        $cus_data['firstname'] = $customer['customers_firstname'];
        $cus_data['lastname'] = $customer['customers_lastname'];
        $cus_data['gender'] = ($customer['customers_gender'] == 'm')? 1 : 2;
        $cus_data['dob'] = $customer['customers_dob'];
        $cus_data['created_at'] = ($customer['date_account_created'])? $customer['date_account_created'] : '';
        $cus_data['is_subscribed'] = $customer['customers_newsletter'];
        $cus_data['group_id'] = 1;
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
        $cusAdd = $this->getListFromListByField($customersExt['object']['address_book'], 'customers_id', $customer['customers_id']);
        if($cusAdd){
            foreach($cusAdd as $cus_add){
                $address = array();
                $address['firstname'] = $cus_add['entry_firstname'];
                $address['lastname'] = $cus_add['entry_lastname'];
                $address['country_id'] = $cus_add['countries_iso_code_2'];
                $address['street'] = $cus_add['entry_street_address']."\n".$cus_add['entry_suburb'];
                $address['postcode'] = $cus_add['entry_postcode'];
                $address['city'] = $cus_add['entry_city'];
                $address['telephone'] = $customer['customers_telephone'];
                $address['company'] = $cus_add['entry_company'];
                $address['fax'] = $customer['customers_fax'];
                if($cus_add['entry_zone_id'] != 0){
                    $region_id = $this->getRegionId($cus_add['zone_name'], $cus_add['countries_iso_code_2']);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['zone_name'];
                } else {
                    $address['region'] = $cus_add['entry_state'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success' && $cus_add['address_book_id'] == $customer['customers_default_address_id']){
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
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'orders_id');
        $bilCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'billing_country');
        $delCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'delivery_country');
        $countries = array_unique(array_merge($bilCountry, $delCountry));
        $bilState = (array) $this->duplicateFieldValueFromList($orders['object'], 'billing_state');
        $delState = (array) $this->duplicateFieldValueFromList($orders['object'], 'delivery_state');
        $states = array_unique(array_merge($bilState, $delState));
        $states = $this->_flitArrayNum($states);
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $countries_query = $this->arrayToInCondition($countries);
        $states_query = $this->arrayToInCondition($states);
        $ext_query = array(
            'orders_products' => "SELECT * FROM _DBPRF_orders_products WHERE orders_id IN {$order_ids_query}",
            'orders_products_attributes' => "SELECT * FROM _DBPRF_orders_products_attributes WHERE orders_id IN {$order_ids_query}",
            'orders_status_history' => "SELECT * FROM _DBPRF_orders_status_history WHERE orders_id IN {$order_ids_query} ORDER BY orders_status_history_id ASC",
            'orders_total' => "SELECT * FROM _DBPRF_orders_total WHERE orders_id IN {$order_ids_query}",
            'currencies' => "SELECT currencies_id, code FROM _DBPRF_currencies",
            'countries' => "SELECT * FROM _DBPRF_countries WHERE countries_name IN {$countries_query}",
            'zones' => "SELECT * FROM _DBPRF_zones WHERE zone_id IN {$states_query}"
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

        $address_billing = $this->getNameFromString($order['billing_name']);
        $address_billing['company'] = $order['billing_company'];
        $address_billing['email']   = $order['customers_email_address'];
        $address_billing['street']  = $order['billing_street_address']."\n".$order['billing_suburb'];
        $address_billing['city'] = $order['billing_city'];
        $address_billing['postcode'] = $order['billing_postcode'];
        $bil_country = $this->getRowValueFromListByField($ordersExt['object']['countries'], 'countries_name', $order['billing_country'], 'countries_iso_code_2');
        $address_billing['country_id'] = $bil_country;
        if(is_numeric($order['billing_state'])){
            $billing_state = $this->getRowValueFromListByField($ordersExt['object']['zones'], 'zone_id', $order['billing_state'], 'zone_name');
            if(!$billing_state){
                $billing_state = $order['billing_state'];
            }
        } else{
            $billing_state = $order['billing_state'];
        }
        $billing_region_id = $this->getRegionId($billing_state, $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $billing_state;
        }
        $address_billing['telephone'] = $order['customers_telephone'];

        $address_shipping = $this->getNameFromString($order['delivery_name']);
        $address_shipping['company'] = $order['delivery_company'];
        $address_shipping['email']   = $order['customers_email_address'];
        $address_shipping['street']  = $order['delivery_street_address']."\n".$order['delivery_suburb'];
        $address_shipping['city'] = $order['delivery_city'];
        $address_shipping['postcode'] = $order['delivery_postcode'];
        $del_country = $this->getRowValueFromListByField($ordersExt['object']['countries'], 'countries_name', $order['delivery_country'], 'countries_iso_code_2');
        $address_shipping['country_id'] = $del_country;
        if(is_numeric($order['delivery_state'])){
            $shipping_state = $this->getRowValueFromListByField($ordersExt['object']['zones'], 'zone_id', $order['delivery_state'], 'zone_name');
            if(!$shipping_state){
                $shipping_state = $order['billing_state'];
            }
        } else{
            $shipping_state = $order['delivery_state'];
        }
        $shipping_region_id = $this->getRegionId($shipping_state, $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $shipping_state;
        }
        $address_shipping['telephone'] = $order['customers_telephone'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['orders_products'], 'orders_id', $order['orders_id']);
        $orderProOpt = $this->getListFromListByField($ordersExt['object']['orders_products_attributes'], 'orders_id', $order['orders_id']);
        $carts = array();
        $order_subtotal = $order_tax_amount = 0;

        if( is_array($orderPro) && count($orderPro) > 0) {
            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getMageIdProduct($order_pro['products_id']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_pro['products_name'];
                $cart['sku'] = $order_pro['products_model'];
                $cart['price'] = $order_pro['final_price'];
                $cart['original_price'] = $order_pro['final_price'];
                $cart['tax_amount'] = ($order_pro['products_tax'] * $order_pro['final_price'] /100) * $order_pro['products_quantity'];
                $cart['tax_percent'] = $order_pro['products_tax'];
                $cart['qty_ordered'] = $order_pro['products_quantity'];
                $cart['row_total'] = $order_pro['final_price'] * $order_pro['products_quantity'];
                $order_subtotal += $cart['row_total'];
                $order_tax_amount += $cart['tax_amount'];
                if($orderProOpt){
                    $listOpt = $this->getListFromListByField($orderProOpt, 'orders_products_id', $order_pro['orders_products_id']);
                    if($listOpt){
                        $product_opt = array();
                        foreach($listOpt as $list_opt){
                            $option = array(
                                'label' => $list_opt['products_options'],
                                'value' => $list_opt['products_options_values'],
                                'print_value' => $list_opt['products_options_values'],
                                'option_id' => 'option_'.$list_opt['orders_products_attributes_id'],
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
        }
        $customer_id = $this->getMageIdCustomer($order['customers_id']);
        $customer_name = $this->getNameFromString($order['customers_name']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['orders_status_history'], 'orders_id', $order['orders_id']);
        $order_status = $orderStatus[0];
        $order_status_id = $order_status['orders_status_id'];
        $orderTotal = $this->getListFromListByField($ordersExt['object']['orders_total'], 'orders_id', $order['orders_id']);
        $ot_shipping = $this->getRowValueFromListByField($orderTotal, 'class', 'shipping-flat_flat', 'value');
        $ot_shipping_desc = $this->getRowValueFromListByField($orderTotal, 'class', 'shipping-flat_flat', 'title');
        $ot_subtotal = $this->getRowValueFromListByField($orderTotal, 'class', 'sub_total', 'value');
        $ot_total = $this->getRowValueFromListByField($orderTotal, 'class', 'total', 'value');
        $ot_tax = $this->getRowValueFromListByField($orderTotal, 'class', 'ot_tax', 'value');
        $ot_discount = $this->getRowFromListByField($orderTotal, 'class','coupon', 'value');
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
        $order_data['customer_email'] = $order['customers_email_address'];
        $order_data['customer_firstname'] = $customer_name['firstname'];
        $order_data['customer_lastname'] = $customer_name['lastname'];
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status']){
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order_subtotal);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $ot_shipping;
        $order_data['base_shipping_amount'] = $ot_shipping;
        $order_data['base_shipping_invoiced'] = $ot_shipping;
        $order_data['shipping_description'] = rtrim($ot_shipping_desc, ':');
        if($ot_tax){
            $order_data['tax_amount'] = $order_tax_amount;
            $order_data['base_tax_amount'] = $order_tax_amount;
        }
        $order_data['discount_amount'] = $ot_discount;
        $order_data['base_discount_amount'] = $ot_discount;
        $order_data['grand_total'] = $this->incrementPriceToImport($ot_total);
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
        foreach($orderStatus as $key => $order_status){
            $order_status_data = array();
            $order_status_id = $order_status['orders_status_id'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if($order_status_data['status']){
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if($key == 0){
                $order_status_data['comment'] = "<b>Reference order #".$order['orders_id']."</b><br /><b>Payment method: </b>".$order['payment_method']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order_status['comments'];
            } else {
                $order_status_data['comment'] = $order_status['comments'];
            }
            $order_status_data['is_customer_notified'] = 1;
            $order_status_data['updated_at'] = $order_status['date_added'];
            $order_status_data['created_at'] = $order_status['date_added'];
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
        $query = "SELECT * FROM _DBPRF_reviews WHERE reviews_id > {$id_src} LIMIT {$limit}";
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
        return $review['reviews_id'];
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
                'msg' => $this->consoleWarning("Review Id = {$review['reviews_id']} import failed. Error: Product Id = {$review['products_id']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$review['languages_id']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        if(isset($review['reviews_status'])){
            $data['status_id'] = ($review['reviews_status'] == 0)? 3 : 1;
        }else{
            $data['status_id'] = 1;
        }
        $data['title'] = " ";
        $data['detail'] = $review['reviews_text'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['customers_id']))? $this->getMageIdCustomer($review['customers_id']) : null;
        $data['nickname'] = $review['customers_name'];
        $data['rating'] = $review['reviews_rating'];
        $data['created_at'] = $review['date_added'];
        $data['review_id_import'] = $review['reviews_id'];
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

    protected function _importChildrenProduct($product, $productsExt, $variation_combinations){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = array();
        $dataOpts = array();
        $count = 0;
        foreach($variation_combinations as $variation){
            $dataOpts = array();
            $option_collection = '';
            $attribute_name = $variation['products_variants_groups_name'];
            $attribute_code = $this->joinTextToKey($attribute_name,27, '_');
            $option_name = $variation['products_variants_values_name'];
            $opt_attr_data = array(
                'entity_type_id'                => $entity_type_id,
                'attribute_set_id'              => $this->_notice['config']['attribute_set_id'],
                'attribute_code'                => $attribute_code,
                'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'frontend_input'                => 'select',
                'frontend_label'                => array($attribute_name),
                'option'                        => array(
                    'value' => array(
                        'option_0' => array($option_name)
                    )
                )
            );
            $result[] = $opt_attr_data;
            $optAttrDataImport = $this->_process->attribute($opt_attr_data);
            if (!$optAttrDataImport) {
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$product['productid']} import failed. Error: Product attribute could not create!")
                );
            }
            $dataTMP['attribute_id'] = $optAttrDataImport['attribute_id'];
            $dataTMP['value_index'] = $optAttrDataImport['option_ids']['option_0'];
            $dataTMP['is_percent'] = 0;
            $dataOpts[] = $dataTMP;
            if ($option_name){
                $option_collection = $option_collection . ' - ' . $option_name;
            }
            $data_variation = array(
            'option_collection' => $option_collection,
            'object' => $variation
            );
            $convertPro = $this->_convertProduct($product, $productsExt, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, true, $data_variation);
            $pro_import = $this->_process->product($convertPro);
            if ($pro_import['result'] !== 'success'){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$product['productid']} import failed. Error: Product children could not create!")
                );
            }
            foreach ($dataOpts as $dataAttribute) {
                $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
            }
            $dataChildes[$pro_import['mage_id']] = $dataOpts;
        }
        if($dataChildes){
            $result = $this->_createConfigProductData($dataChildes);
        }
        return array(
            'result' => 'success',
            'data' => $result
        );
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return array
     */
    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array()){
        $pro_data = $categories = array();
        $proDesc = $this->getListFromListByField($productsExt['object']['products_description'], 'products_id', $product['products_id']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'language_id', $this->_notice['config']['default_lang']);
        $proImg = $this->getListFromListByField($productsExt['object']['products_images'], 'products_id', $product['products_id']);
        if($proImg){
            foreach($proImg as $gallery){
                if($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery['image'], 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '') ;
                }
                if($gallery['default_flag'] ==1){
                    $product['products_image'] = $gallery['image'];
                    $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['products_image'], 'catalog/product', false, true);
                    $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                }
            }
        }
        if(!$is_variation_pro){
            $pro_data['price'] = $product['products_price'] ? $product['products_price'] : 0;
            $proSpecial = $this->getRowFromListByField($productsExt['object']['specials'], 'products_id', $product['products_id']);
            if($proSpecial){
                $pro_data['special_price'] =  $proSpecial['specials_new_products_price'];
                $pro_data['special_from_date'] = $this->_cookSpecialDate($proSpecial['specials_date_added']);
                $pro_data['special_to_date'] = $this->_cookSpecialDate($proSpecial['expires_date']);
            }
            $pro_data['name'] = $pro_desc_def['products_name'];
            $pro_data['sku'] = $this->createProductSku($product['products_sku'], $this->_notice['config']['languages']);
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $pro_data['weight']   = $product['products_weight'] ? $product['products_weight']: 0 ;
            $pro_data['stock_data'] = array(
            'is_in_stock' => $product['products_status'],
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['products_quantity'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['products_quantity'] < 1)? 0 : 1,
            'qty' => $product['products_quantity']
            );
            $pro_data['status'] = ($product['products_status']== 1)? 1 : 2;
        }else{
            $pro_data['name'] = $pro_desc_def['products_name'] . $data_variation['option_collection'];
            $pro_data['sku'] = $this->createProductSku($data_variation['object']['products_sku'], $this->_notice['config']['languages']);
            $pro_data['price'] = $data_variation['object']['products_price'];
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $pro_data['weight']   = $data_variation['object']['products_weight'] ? $data_variation['object']['products_weight']: 0 ;
            $pro_data['stock_data'] = array(
            'is_in_stock' => $data_variation['object']['products_status'],
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['products_quantity'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['products_quantity'] < 1)? 0 : 1,
            'qty' => $data_variation['object']['products_quantity']
            );
            if($data_variation['object']['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $data_variation['object']['image'], 'catalog/product', false, true)){
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
            }
            $pro_data['status'] = ($data_variation['object']['products_status']== 1)? 1 : 2;
        }
        $proCat = $this->getListFromListByField($productsExt['object']['products_to_categories'], 'products_id', $product['products_id']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['categories_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_desc_def['products_short_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['description'] = $this->changeImgSrcInText($pro_desc_def['products_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $pro_desc_def['products_page_title'];
        $pro_data['meta_keyword'] = $pro_desc_def['products_meta_keywords'];
        $pro_data['meta_description'] = $pro_desc_def['products_meta_description'];
        $pro_data['tags'] = $pro_desc_def['products_tags'];
        
        if($product['products_tax_class_id'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['products_tax_class_id'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['products_date_added'];
        
        if($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturers_id'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        if($product['products_image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['products_image'], 'catalog/product', false, true)){
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
        }
        
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_id => $store_id){
            if($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'language_id', $lang_id)){
                $store_data = array();
                if($is_variation_pro){
                    $store_data['name'] = $store_data_change['products_name'] . $data_variation['option_collection'];
                }else{
                    $store_data['name'] = $store_data_change['products_name'];
                }
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['products_description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['store_id'] = $store_id;
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
        return $pro_data;
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
        $result = array();
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
        $result['attribute_label'] = $attribute->getFrontEndLabel();
        $result['attribute_code'] = $attribute->getAttributeCode();
        $options = $attribute->getSource()->getAllOptions(false);
        if($options){
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
        }
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
        $query = "SELECT * FROM _DBPRF_products ORDER BY products_id ASC";
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
        $query = "SELECT r.*, rd.* FROM _DBPRF_reviews AS r LEFT JOIN _DBPRF_reviews_description AS rd ON rd.reviews_id = r.reviews_id ORDER BY r.reviews_id ASC";
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