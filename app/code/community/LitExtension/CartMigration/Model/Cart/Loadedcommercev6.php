<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Loadedcommercev6 extends LitExtension_CartMigration_Model_Cart {
    
    const CAT_URL = "cat_url";

    public function __construct() {
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_rates WHERE tax_rates_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturers_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categories_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE products_parent_id = 0 AND products_id > {$this->_notice['products']['id_src']}",
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
    public function displayConfig() {
        $response = array();
        $default_cfg = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                "languages" => "SELECT cfg.*, lg.* FROM _DBPRF_configuration AS cfg LEFT JOIN _DBPRF_languages AS lg ON lg.code = cfg.configuration_value WHERE cfg.configuration_key = 'DEFAULT_LANGUAGE'",
                "currencies" => "SELECT cfg.*, cur.* FROM _DBPRF_configuration AS cfg LEFT JOIN _DBPRF_currencies AS cur ON cur.code = cfg.configuration_value WHERE cfg.configuration_key = 'DEFAULT_CURRENCY'"
            ))
        ));
        if (!$default_cfg || $default_cfg['result'] != 'success') {
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
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach ($obj['languages'] as $language_row) {
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
    public function displayConfirm($params) {
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
    public function displayImport() {
        $recent = $this->getRecentNotice();
        if ($recent) {
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews');
            foreach ($types as $type) {
                if ($this->_notice['config']['add_option']['add_new'] || !$this->_notice['config']['import'][$type]) {
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_rates WHERE tax_rates_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturers_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categories_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE products_parent_id = 0 AND products_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customers_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orders_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE reviews_id > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $totals = array();
        foreach ($data['object'] as $type => $row) {
            $count = $this->arrayToCount($row);
            $totals[$type] = $count;
        }
        $iTotal = $this->_limitDemoModel($totals);
        foreach ($iTotal as $type => $total) {
            $this->_notice[$type]['total'] = $total;
        }
        $this->_notice['taxes']['time_start'] = time();
        if (!$this->_notice['config']['add_option']['add_new']) {
            $delete = $this->_deleteLeCaMgImport($this->_notice['config']['cart_url']);
            if (!$delete) {
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
    public function configCurrency() {
        parent::configCurrency();
        $allowCur = $this->_notice['config']['currencies'];
        $allow_cur = implode(',', $allowCur);
        $this->_process->currencyAllow($allow_cur);
        $default_cur = $this->_notice['config']['currencies'][$this->_notice['config']['default_currency']];
        $this->_process->currencyDefault($default_cur);
        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_currencies"
        ));
        if ($currencies && $currencies['result'] == 'success') {
            $data = array();
            foreach ($currencies['object'] as $currency) {
                $currency_id = $currency['currencies_id'];
                $currency_value = $currency['value'];
                $currency_mage = $this->_notice['config']['currencies'][$currency_id];
                $data[$currency_mage] = $currency_value;
            }
            $this->_process->currencyRate(array(
                $default_cur => $data
            ));
        }
        return;
    }

    /**
     * Process before import taxes
     */
    public function prepareImportTaxes() {
        parent::prepareImportTaxes();
        $tax_cus = $this->getTaxCustomerDefault();
        if ($tax_cus['result'] == 'success') {
            $this->taxCustomerSuccess(1, $tax_cus['mage_id']);
        }
    }

    /**
     * Query for get data of table convert to tax rule
     *
     * @return string
     */
    protected function _getTaxesMainQuery() {
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = "SELECT * FROM _DBPRF_tax_rates WHERE tax_rates_id > {$id_src} ORDER BY tax_rates_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes) {
        $taxClassIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_class_id');
        $tax_class_id_con = $this->arrayToInCondition($taxClassIds);
        $taxZoneIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_zone_id');
        $tax_zone_query = $this->arrayToInCondition($taxZoneIds);
        $ext_query = array(
            'tax_class' => "SELECT * FROM _DBPRF_tax_class WHERE tax_class_id IN {$tax_class_id_con}",
            'zones_to_geo_zones' => "SELECT gz.*, ztgz.*, z.zone_name, c.*
                                              FROM _DBPRF_geo_zones AS gz
                                                  LEFT JOIN _DBPRF_zones_to_geo_zones AS ztgz ON ztgz.geo_zone_id = gz.geo_zone_id
                                                  LEFT JOIN _DBPRF_zones AS z ON z.zone_id = ztgz.zone_id
                                                  LEFT JOIN _DBPRF_countries AS c ON c.countries_id = ztgz.zone_country_id
                                              WHERE gz.geo_zone_id IN {$tax_zone_query}"
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
    protected function _getTaxesExtRelQuery($taxes, $taxesExt) {
        return array();
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt) {
        return $tax['tax_rates_id'];
    }

    /**
     * Convert source data to data for import
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return array
     */
    public function convertTax($tax, $taxesExt) {
        if (LitExtension_CartMigration_Model_Custom::TAX_CONVERT) {
            return $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        }
        $tax_cus_ids = $tax_pro_ids = $tax_rate_ids = array();
        if ($tax_cus_default = $this->getMageIdTaxCustomer(1)) {
            $tax_cus_ids[] = $tax_cus_default;
        }
        $taxClass = $this->getRowFromListByField($taxesExt['object']['tax_class'], 'tax_class_id', $tax['tax_class_id']);
        $tax_pro_data = array(
            'class_name' => $taxClass['tax_class_title']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($taxClass['tax_class_id'], $tax_pro_ipt['mage_id']);
        }
        $taxZone = $this->getListFromListByField($taxesExt['object']['zones_to_geo_zones'], 'geo_zone_id', $tax['tax_zone_id']);
        foreach ($taxZone as $tax_zone) {
            if (!$tax_zone['countries_iso_code_2']) {
                continue;
            }
            $tax_rate_data = array();
            $zone = $tax_zone['zone_name'];
            if ($tax_zone['zone_id'] == 0) {
                $zone = 'All States';
            }
            $code = $tax_zone['countries_iso_code_2'] . "-" . $zone;
            $tax_rate_data['code'] = $this->createTaxRateCode($code);
            $tax_rate_data['tax_country_id'] = $tax_zone['countries_iso_code_2'];
            if ($tax_zone['zone_id'] == 0) {
                $tax_rate_data['tax_region_id'] = 0;
            } else {
                $tax_rate_data['tax_region_id'] = $this->getRegionId($tax_zone['zone_name'], $tax_zone['countries_iso_code_2']);
            }
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = "*";
            $tax_rate_data['rate'] = $tax['tax_rate'];
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if ($tax_rate_ipt['result'] == 'success') {
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($taxClass['tax_class_title'] . " - " . $tax['tax_description']);
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
     * Process before import manufacturers
     */
    public function prepareImportManufacturers() {
        parent::prepareImportManufacturers();
        $man_attr = $this->getManufacturerAttributeId($this->_notice['config']['attribute_set_id']);
        if ($man_attr['result'] == 'success') {
            $this->manAttrSuccess(1, $man_attr['mage_id']);
        }
    }

    /**
     * Query for get data for convert to manufacturer option
     *
     * @return string
     */
    protected function _getManufacturersMainQuery() {
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
    protected function _getManufacturersExtQuery($manufacturers) {
        return array();
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @param array $manufacturersExt : Data of connector return for query function getManufacturersExtQuery
     * @return array
     */
    protected function _getManufacturersExtRelQuery($manufacturers, $manufacturersExt) {
        return array();
    }

    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return int
     */
    public function getManufacturerId($manufacturer, $manufacturersExt) {
        return $manufacturer['manufacturers_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return array
     */
    public function convertManufacturer($manufacturer, $manufacturersExt) {
        if (LitExtension_CartMigration_Model_Custom::MANUFACTURER_CONVERT) {
            return $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        }
        $man_attr_id = $this->getMageIdManAttr(1);
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
            0 => $manufacturer['manufacturers_name']
        );
        foreach ($this->_notice['config']['languages'] as $store_id) {
            $manufacturer['value']['option'][$store_id] = $manufacturer['manufacturers_name'];
        }
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
     * Query for get data of main table use import category
     *
     * @return string
     */
    protected function _getCategoriesMainQuery() {
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
    protected function _getCategoriesExtQuery($categories) {
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
    protected function _getCategoriesExtRelQuery($categories, $categoriesExt) {
        return array();
    }

    /**
     * Get primary key of source category
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return int
     */
    public function getCategoryId($category, $categoriesExt) {
        return $category['categories_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return array
     */
    public function convertCategory($category, $categoriesExt) {
        if (LitExtension_CartMigration_Model_Custom::CATEGORY_CONVERT) {
            return $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        }
        if ($category['parent_id'] == 0 || $category['parent_id'] == $category['categories_id']) {
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['parent_id']);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($category['parent_id']);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
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
        $cat_lang = $this->getRowFromListByField($catDesc, 'language_id', $this->_notice['config']['default_lang']);
        $cat_data['name'] = $cat_lang['categories_name'] ? $cat_lang['categories_name'] : " ";
        $cat_data['description'] = $cat_lang['categories_description'];
        $cat_data['meta_title'] = $cat_lang['categories_head_title_tag'];
        $cat_data['meta_description'] = $cat_lang['categories_head_desc_tag'];
        $cat_data['meta_keywords'] = $cat_lang['categories_head_keywords_tag'];
        if ($category['categories_image'] && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $category['categories_image'], 'catalog/category')) {
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = 1;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $store_data = array();
            $store_lang = $this->getRowFromListByField($catDesc, 'language_id', $lang_id);
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_lang) {
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_lang['categories_name'];
                $store_data['description'] = $store_lang['categories_description'];
                $store_data['meta_title'] = $store_lang['categories_head_title_tag'];
                $store_data['meta_description'] = $store_lang['categories_head_desc_tag'];
                $store_data['meta_keywords'] = $store_lang['categories_head_keywords_tag'];
                $multi_store[] = $store_data;
            }
        }
        $cat_data['multi_store'] = $multi_store;
        if ($this->_seo) {
            $seo = $this->_seo->convertCategorySeo($this, $category, $categoriesExt);
            if ($seo) {
                $cat_data['seo_url'] = $seo;
                $this->_catUrlSuccess($category['categories_id'], 0, $seo[0]['request_path']);
            }
        }
        $custom = $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        if ($custom) {
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
    public function prepareImportProducts() {
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    /**
     * Query for get data of main table use for import product
     *
     * @return string
     */
    protected function _getProductsMainQuery() {
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT * FROM _DBPRF_products WHERE products_parent_id = 0 AND products_id > {$id_src} ORDER BY products_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products) {
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'products_id');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        //$parentIds = $this->duplicateFieldValueFromList($products['object'], 'products_parent_id');
        //$parent_ids_query = $this->arrayToInCondition($parentIds);
        $ext_query = array(
            'products_to_categories' => "SELECT * FROM _DBPRF_products_to_categories WHERE products_id IN {$pro_ids_query}",
            'specials' => "SELECT * FROM _DBPRF_specials WHERE product_ids IN {$pro_ids_query} AND status = 1",
            'products_attributes' => "SELECT * FROM _DBPRF_products_attributes WHERE products_id IN {$pro_ids_query}",
            'children_product' => "SELECT * FROM _DBPRF_products WHERE products_parent_id IN {$pro_ids_query}",
            'products_xsell' => "SELECT * FROM _DBPRF_products_xsell WHERE products_id IN {$pro_ids_query} OR xsell_id IN {$pro_ids_query}"
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
    protected function _getProductsExtRelQuery($products, $productsExt) {
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'products_id');
        $childIds = $this->duplicateFieldValueFromList($productsExt['object']['children_product'], 'products_id');
        $allProduct = array_merge($productIds, $childIds);
        $all_pro_id = $this->arrayToInCondition($allProduct);
        $productOptionIds = $this->duplicateFieldValueFromList($productsExt['object']['products_attributes'], 'options_id');
        $productOptionValueIds = $this->duplicateFieldValueFromList($productsExt['object']['products_attributes'], 'options_values_id');
        $product_option_ids_query = $this->arrayToInCondition($productOptionIds);
        $product_option_value_ids_query = $this->arrayToInCondition($productOptionValueIds);
        $ext_rel_query = array(
            'products_description' => "SELECT * FROM _DBPRF_products_description WHERE products_id IN {$all_pro_id}",
            'products_options' => "SELECT * FROM _DBPRF_products_options WHERE products_options_id IN {$product_option_ids_query}",
            'products_options_text' => "SELECT * FROM _DBPRF_products_options_text WHERE products_options_text_id IN {$product_option_ids_query}",
            'products_options_values' => "SELECT * FROM _DBPRF_products_options_values WHERE products_options_values_id IN {$product_option_value_ids_query}",
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
    public function getProductId($product, $productsExt) {
        return $product['products_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return array
     */
    public function convertProduct($product, $productsExt) {
        if (LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $result = array();
        $children_product = $this->getListFromListByField($productsExt['object']['children_product'], 'products_parent_id', $product['products_id']);
        if (!$children_product) {
            $result['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        } else {
            $result['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $config_data = $this->_importChildrenProduct($product, $children_product, $productsExt);
            if (!$config_data) {
                return array(
                    'result' => 'warning',
                    'msg' => $this->consoleWarning("Product ID = {$product['products_id']} import failed. Error: Product attribute ccould not create!"),
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

    protected function _convertProduct($product, $productsExt, $parent_id = null) {
        if (LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        if (!$parent_id) {
            $categories = array();
            $proCat = $this->getListFromListByField($productsExt['object']['products_to_categories'], 'products_id', $product['products_id']);
            if ($proCat) {
                foreach ($proCat as $pro_cat) {
                    $cat_id = $this->getMageIdCategory($pro_cat['categories_id']);
                    if ($cat_id) {
                        $categories[] = $cat_id;
                    }
                }
            }
            $pro_data['category_ids'] = $categories;
        }
        $proDesc = $this->getListFromListByField($productsExt['object']['products_description'], 'products_id', $product['products_id']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'language_id', $this->_notice['config']['default_lang']);
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        if ($product['products_model']) {
            $pro_data['sku'] = $this->createProductSku($product['products_model'], $this->_notice['config']['languages']);
        } else {
            $pro_data['sku'] = $this->createProductSku($pro_desc_def['products_name'], $this->_notice['config']['languages']);
        }
        $pro_data['name'] = $pro_desc_def['products_name'];
        $pro_data['description'] = $this->changeImgSrcInText($pro_desc_def['products_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_desc_def['products_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['price'] = $product['products_price'] ? $product['products_price'] : 0;
        if (!$parent_id) {
            $proSpecial = $this->getRowFromListByField($productsExt['object']['specials'], 'products_id', $product['products_id']);
            if ($proSpecial) {
                $pro_data['special_price'] = $proSpecial['specials_new_products_price'];
                $pro_data['special_from_date'] = $proSpecial['specials_date_added'];
                $pro_data['special_to_date'] = $proSpecial['expires_date'];
            }
        }
        $pro_data['weight'] = $product['products_weight'] ? $product['products_weight'] : 0;
        $pro_data['status'] = ($product['products_status'] == 1) ? 1 : 2;
        if($parent_id) {
            $pro_data['status'] = 1;
        }
        if ($product['products_tax_class_id'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['products_tax_class_id'])) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['products_date_added'];
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['products_quantity'] < 1) ? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['products_quantity'] < 1) ? 0 : 1,
            'qty' => $product['products_quantity']
        );
        if ($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturers_id'])) {
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        if (!$parent_id) {
            if ($product['products_image'] && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['products_image'], 'catalog/product', false, true)) {
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
            }
            for ($i = 1; $i < 7; $i++) {
                if ($product['products_image_xl_' . $i] && $gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['products_image_xl_' . $i], 'catalog/product', false, true)) {
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
                }
            }
            if ($product['products_image_med'] && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['products_image_med'], 'catalog/product', false, true)) {
                $pro_data['image_gallery'][] = array('path' => $image_path, 'label' => '');
            }
            if ($product['products_image_lrg'] && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['products_image_lrg'], 'catalog/product', false, true)) {
                $pro_data['image_gallery'][] = array('path' => $image_path, 'label' => '');
            }
        }
        $pro_data['meta_title'] = $pro_desc_def['products_head_title_tag'] ? $pro_desc_def['products_head_title_tag'] : ' ';
        $pro_data['meta_keyword'] = $pro_desc_def['products_head_keywords_tag'] ? $pro_desc_def['products_head_keywords_tag'] : ' ';
        $pro_data['meta_description'] = $pro_desc_def['products_head_desc_tag'] ? $pro_desc_def['products_head_desc_tag'] : ' ';
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'language_id', $lang_id)) {
                $store_data = array();
                $store_data['name'] = $store_data_change['products_name'];
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['products_description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['short_description'] = $this->changeImgSrcInText($store_data_change['products_description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = $store_data_change['products_head_title_tag'];
                $store_data['meta_keyword'] = $store_data_change['products_head_keywords_tag'];
                $store_data['meta_description'] = $store_data_change['products_head_desc_tag'];
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
        }
        $pro_data['multi_store'] = $multi_store;
        if ($this->_seo) {
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
     * @param int $product_mage_id : Id of product save successful to magento
     * @param array $data : Data of function convertProduct
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return boolean
     */
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt) {
        if (parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)) {
            return;
        }
        $proAttr = $this->getListFromListByField($productsExt['object']['products_attributes'], 'products_id', $product['products_id']);
        if ($proAttr) {
            $opt_data = array();
            $opt_data_store = array();
            $proOptId = $this->duplicateFieldValueFromList($proAttr, 'options_id');
            foreach ($proOptId as $pro_opt_id) {
                $proOpt = $this->getListFromListByField($productsExt['object']['products_options_text'], 'products_options_text_id', $pro_opt_id);
                $proOptVal = $this->getListFromListByField($proAttr, 'options_id', $pro_opt_id);
                if (!$proOpt) {
                    continue;
                }
                $option = array(
                    'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT,
                    'type' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
                    'is_require' => 1,
                    'title' => $this->getRowValueFromListByField($proOpt, 'language_id', $this->_notice['config']['default_lang'], 'products_options_name')
                );
                foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                    if ($lang_id == $this->_notice['config']['default_lang']) {
                        continue;
                    }
                    $option_store[$lang_id] = array(
                        'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT,
                        'type' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
                        'is_require' => 1,
                        'title' => $this->getRowValueFromListByField($proOpt, 'language_id', $lang_id, 'products_options_name')
                    );
                }
                $values = array();
                $value_stores = array();
                if ($proOptVal) {
                    foreach ($proOptVal as $pro_opt_val) {
                        $proVal = $this->getListFromListByField($productsExt['object']['products_options_values'], 'products_options_values_id', $pro_opt_val['options_values_id']);
                        $value = array(
                            'option_type_id' => -1,
                            'title' => $this->getRowValueFromListByField($proVal, 'language_id', $this->_notice['config']['default_lang'], 'products_options_values_name'),
                            'price' => $pro_opt_val['price_prefix'] . $pro_opt_val['options_values_price'],
                            'price_type' => 'fixed',
                        );
                        $values[] = $value;
                        foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                            if ($lang_id == $this->_notice['config']['default_lang']) {
                                continue;
                            }
                            $value_store = array(
                                'option_type_id' => -1,
                                'title' => $this->getRowValueFromListByField($proVal, 'language_id', $lang_id, 'products_options_values_name'),
                                'price' => $pro_opt_val['price_prefix'] . $pro_opt_val['options_values_price'],
                                'price_type' => 'fixed',
                            );
                            $value_stores[$lang_id][] = $value_store;
                        }
                    }
                }
                foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                    if ($lang_id == $this->_notice['config']['default_lang']) {
                        continue;
                    }
                    $option_store[$lang_id]['values'] = (isset($value_stores[$lang_id])) ? $value_stores[$lang_id] : '';
                    $opt_data_store[$lang_id][] = $option_store[$lang_id];
                }
                $option['values'] = (isset($values)) ? $values : '';
                $opt_data[] = $option;
            }
            $this->importProductOption($product_mage_id, $opt_data);
            if (count($this->_notice['config']['languages']) > 1) {
                foreach ($this->_notice['config']['languages'] as $key => $val) {
                    if ($key == $this->_notice['config']['default_lang']) {
                        continue;
                    }
                    $this->_updateProductOptionStoreView($product_mage_id, $opt_data_store[$key], $opt_data, $val);
                }
            }
        }
        //Related product
        $relateProducts = $this->getListFromListByField($productsExt['object']['products_xsell'], 'products_id', $product['products_id']);
        if ($relateProducts) {
            $relate_products = $this->duplicateFieldValueFromList($relateProducts, 'xsell_id');
            $this->setProductRelation($product_mage_id, $relate_products, 5);
        }
        
        $relateRProducts = $this->getListFromListByField($productsExt['object']['products_xsell'], 'xsell_id', $product['products_id']);
        if ($relateRProducts) {
            $relate_products = $this->duplicateFieldValueFromList($relateRProducts, 'products_id');
            $this->setProductRelation($relate_products, $product_mage_id, 5);
        }
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery() {
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
    protected function _getCustomersExtQuery($customers) {
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'customers_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'customers_info' => "SELECT * FROM _DBPRF_customers_info WHERE customers_info_id IN {$customer_ids_query}",
            'address' => "SELECT a.*, c.*, z.*
                                            FROM _DBPRF_address_book AS a
                                                LEFT JOIN _DBPRF_countries AS c ON a.entry_country_id = c.countries_id
                                                LEFT JOIN _DBPRF_zones AS z ON a.entry_zone_id = z.zone_id
                                            WHERE a.customers_id IN {$customer_ids_query}"
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
    protected function _getCustomersExtRelQuery($customers, $customersExt) {
        return array();
    }

    /**
     * Get primary key of source customer main
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return int
     */
    public function getCustomerId($customer, $customersExt) {
        return $customer['customers_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return array
     */
    public function convertCustomer($customer, $customersExt) {
        if (LitExtension_CartMigration_Model_Custom::CUSTOMER_CONVERT) {
            return $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        }
        $cus_data = array();
        if ($this->_notice['config']['add_option']['pre_cus']) {
            $cus_data['id'] = $customer['customers_id'];
        }
        $date_create = $this->getRowValueFromListByField($customersExt['object']['customers_info'], 'customers_info_id', $customer['customers_id'], 'customers_info_date_account_created');
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['customers_email_address'];
        $cus_data['firstname'] = $customer['customers_firstname'];
        $cus_data['lastname'] = $customer['customers_lastname'];
        $cus_data['created_at'] = ($date_create) ? $date_create : '0000-00-00 00:00:00';
        $cus_data['is_subscribed'] = $customer['customers_newsletter'];
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
     * @param int $customer_mage_id : Id of customer import to magento
     * @param array $data : Data of function convertCustomer
     * @param array $customer : One row of object function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return boolean
     */
    public function afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt) {
        if (parent::afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt)) {
            return;
        }
        $this->_importCustomerRawPass($customer_mage_id, $customer['customers_password']);
        $cusAdd = $this->getListFromListByField($customersExt['object']['address'], 'customers_id', $customer['customers_id']);
        if ($cusAdd) {
            foreach ($cusAdd as $cus_add) {
                $address = array();
                $address['firstname'] = $cus_add['entry_firstname'];
                $address['lastname'] = $cus_add['entry_lastname'];
                $address['country_id'] = $cus_add['countries_iso_code_2'];
                $address['street'] = html_entity_decode($cus_add['entry_street_address']);
                $address['postcode'] = $cus_add['entry_postcode'];
                $address['city'] = html_entity_decode($cus_add['entry_city']);
                $address['telephone'] = $cus_add['entry_telephone'];
                $address['company'] = $cus_add['entry_company'];
                $address['fax'] = $cus_add['entry_fax'];
                if ($cus_add['zone_id'] != 0) {
                    $region_id = $this->getRegionId($cus_add['zone_name'], $cus_add['countries_iso_code_2']);
                    if ($region_id) {
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['zone_name'];
                } else {
                    $address['region'] = $cus_add['entry_street_address'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if ($address_ipt['result'] == 'success' && $cus_add['address_book_id'] == $customer['customers_default_address_id']) {
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
    }

    /**
     * Get data use for import order
     *
     * @return array : Response of connector
     */
    protected function _getOrdersMainQuery() {
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_orders WHERE orders_id > {$id_src} ORDER BY orders_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    protected function _getOrdersExtQuery($orders) {
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'orders_id');
        $countries = $this->duplicateFieldValueFromList($orders['object'], 'delivery_country');
        $states = $this->duplicateFieldValueFromList($orders['object'], 'delivery_state');
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $country_name_query = $this->arrayToInCondition($countries);
        $state_name_query = $this->arrayToInCondition($states);
        $ext_query = array(
            'orders_products' => "SELECT * FROM _DBPRF_orders_products WHERE orders_id IN {$order_ids_query}",
            'orders_products_attributes' => "SELECT * FROM _DBPRF_orders_products_attributes WHERE orders_id IN {$order_ids_query}",
            'orders_status_history' => "SELECT *  FROM _DBPRF_orders_status_history WHERE orders_id IN {$order_ids_query} ORDER BY orders_status_history_id DESC",
            'orders_total' => "SELECT * FROM _DBPRF_orders_total WHERE orders_id IN {$order_ids_query}",
            'countries' => "SELECT * FROM _DBPRF_countries WHERE countries_name IN {$country_name_query}",
            'zones' => "SELECT * FROM _DBPRF_zones WHERE zone_name IN {$state_name_query}",
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
    protected function _getOrdersExtRelQuery($orders, $ordersExt) {
        return array();
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return int
     */
    public function getOrderId($order, $ordersExt) {
        return $order['orders_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return array
     */
    public function convertOrder($order, $ordersExt) {
        if (LitExtension_CartMigration_Model_Custom::ORDER_CONVERT) {
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();

        $billing_name = $this->_getPartName($order['billing_name']);
        $address_billing['firstname'] = $billing_name['firstname'];
        $address_billing['lastname'] = $billing_name['lastname'];
        $address_billing['company'] = $order['billing_company'];
        $address_billing['street'] = $order['billing_street_address'];
        $address_billing['city'] = $order['billing_city'];
        $address_billing['postcode'] = $order['billing_postcode'];
        $bill_country = $this->getRowValueFromListByField($ordersExt['object']['countries'], 'countries_name', $order['billing_country'], 'countries_iso_code_2');
        $address_billing['country_id'] = $bill_country;
        $billing_region_id = $this->getRegionId($order['billing_state'], $address_billing['country_id']);
        if ($billing_region_id) {
            $address_billing['region_id'] = $billing_region_id;
        } else {
            $address_billing['region'] = $order['billing_state'];
        }

        $shipping_name = $this->_getPartName($order['delivery_name']);
        $address_shipping['firstname'] = $shipping_name['firstname'];
        $address_shipping['lastname'] = $shipping_name['lastname'];
        $address_shipping['company'] = $order['delivery_company'];
        $address_shipping['street'] = $order['delivery_street_address'];
        $address_shipping['city'] = $order['delivery_city'];
        $address_shipping['postcode'] = $order['delivery_postcode'];
        $ship_country = $this->getRowValueFromListByField($ordersExt['object']['countries'], 'countries_name', $order['delivery_country'], 'countries_iso_code_2');
        $address_shipping['country_id'] = $ship_country;
        $shipping_region_id = $this->getRegionId($order['delivery_state'], $address_shipping['country_id']);
        if ($shipping_region_id) {
            $address_shipping['region_id'] = $shipping_region_id;
        } else {
            $address_shipping['region'] = $order['delivery_state'];
        }

        $orderPro = $this->getListFromListByField($ordersExt['object']['orders_products'], 'orders_id', $order['orders_id']);
        $orderProOpt = $this->getListFromListByField($ordersExt['object']['orders_products_attributes'], 'orders_id', $order['orders_id']);
        $carts = array();
        $orderTotal = $this->getListFromListByField($ordersExt['object']['orders_total'], 'orders_id', $order['orders_id']);
        $ot_coupon = $this->getRowValueFromListByField($orderTotal, 'class', 'ot_coupon', 'value');
        $discount_amount = 0;
        if ($ot_coupon) {
            $discount_amount -= $ot_coupon;
        }
        $discount = abs($discount_amount);
        foreach ($orderPro as $order_pro) {
            $cart = array();
            $product_id = $this->getMageIdProduct($order_pro['products_id']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $cart['name'] = $order_pro['products_name'];
            $cart['sku'] = $order_pro['products_model'];
            $cart['price'] = $order_pro['final_price'];
            $cart['original_price'] = $order_pro['products_price'];
            $cart['tax_amount'] = $order_pro['final_price'] * $order_pro['products_quantity'] * $order_pro['products_tax'] / 100;
            $cart['tax_percent'] = $order_pro['products_tax'];
            $cart['discount_amount'] = '';
            $cart['qty_ordered'] = $order_pro['products_quantity'];
            $cart['row_total'] = $order_pro['final_price'] * $order_pro['products_quantity'];
            if ($orderProOpt) {
                $listOpt = $this->getListFromListByField($orderProOpt, 'orders_products_id', $order_pro['orders_products_id']);
                if ($listOpt) {
                    $product_opt = array();
                    $options = array();
                    foreach ($listOpt as $list_opt) {
                        $option = array(
                            'label' => $list_opt['products_options'],
                            'value' => $list_opt['products_options_values'],
                            'print_value' => $list_opt['products_options_values'],
                            'option_id' => 'option_' . $list_opt['products_options_values_id'],
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $options[] = $option;
                    }
                    $product_opt = array('options' => $options);
                    $cart['product_options'] = serialize($product_opt);
                }
            }
            $carts[] = $cart;
        }

        $customer_id = $this->getMageIdCustomer($order['customers_id']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['orders_status_history'], 'orders_id', $order['orders_id']);
        $order_status = $orderStatus[0];
        $order_status_id = $order_status['orders_status_id'];
        $ot_shipping = $this->getRowValueFromListByField($orderTotal, 'class', 'ot_shipping', 'value');
        $ot_shipping_desc = $this->getRowValueFromListByField($orderTotal, 'class', 'ot_shipping', 'title');
        $ot_subtotal = $this->getRowValueFromListByField($orderTotal, 'class', 'ot_subtotal', 'value');
        $ot_total = $this->getRowValueFromListByField($orderTotal, 'class', 'ot_total', 'value');
        $ot_tax = $this->_getOrderTaxValueFromListByCode($orderTotal, 'ot_tax');
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $customer_name = $this->_getPartName($order['customers_name']);
        $order_data['customer_email'] = $order['customers_email_address'];
        $order_data['customer_firstname'] = $customer_name['firstname'];
        $order_data['customer_lastname'] = $customer_name['lastname'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($ot_subtotal);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $ot_shipping;
        $order_data['base_shipping_amount'] = $ot_shipping;
        $order_data['base_shipping_invoiced'] = $ot_shipping;
        $order_data['shipping_description'] = $ot_shipping_desc;
        if ($ot_tax) {
            $order_data['tax_amount'] = $ot_tax;
            $order_data['base_tax_amount'] = $ot_tax;
        }
        $order_data['discount_amount'] = $discount_amount;
        $order_data['base_discount_amount'] = $discount_amount;
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
        if ($custom) {
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
        $orderStatus = $this->getListFromListByField($ordersExt['object']['orders_status_history'], 'orders_id', $order['orders_id']);
        $orderStatus = array_reverse($orderStatus);
        foreach ($orderStatus as $key => $order_status) {
            $order_status_data = array();
            $order_status_id = $order_status['orders_status_id'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if ($key == 0) {
                $order_status_data['comment'] = "<b>Reference order #" . $order['orders_id'] . "</b><br /><b>Payment method: </b>" . $order['payment_method'] . "<br /><b>Shipping method: </b> " . $data['order']['shipping_description'] . "<br /><br />" . $order_status['comments'];
            } else {
                $order_status_data['comment'] = $order_status['comments'];
            }
            $order_status_data['is_customer_notified'] = ($order_status['customer_notified']) ? 1 : 0;
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
    protected function _getReviewsMainQuery() {
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $query = "SELECT * FROM _DBPRF_reviews WHERE reviews_id > {$id_src} ORDER BY reviews_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews) {
        $reviewIds = $this->duplicateFieldValueFromList($reviews['object'], 'reviews_id');
        $review_ids_query = $this->arrayToInCondition($reviewIds);
        $ext_query = array(
            'reviews_description' => "SELECT * FROM _DBPRF_reviews_description WHERE reviews_id IN {$review_ids_query}",
        );
        return $ext_query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @param array $reviewsExt : Data of connector return for query function getReviewsExtQuery
     * @return array
     */
    protected function _getReviewsExtRelQuery($reviews, $reviewsExt) {
        return array();
    }

    /**
     * Get primary key of source review main
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return int
     */
    public function getReviewId($review, $reviewsExt) {
        return $review['reviews_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return array
     */
    public function convertReview($review, $reviewsExt) {
        if (LitExtension_CartMigration_Model_Custom::REVIEW_CONVERT) {
            return $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        }
        $product_mage_id = $this->getMageIdProduct($review['products_id']);
        if (!$product_mage_id) {
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['reviews_id']} import failed. Error: Product Id = {$review['products_id']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $review_lang = $this->getListFromListByField($reviewsExt['object']['reviews_description'], 'reviews_id', $review['reviews_id']);
        $review_text = $this->getRowValueFromListByField($review_lang, 'languages_id', $this->_notice['config']['default_lang'], 'reviews_text');
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = 1;
        $data['title'] = " ";
        $data['detail'] = $review_text;
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['customers_id'])) ? $this->getMageIdCustomer($review['customers_id']) : null;
        $data['nickname'] = $review['customers_name'];
        $data['rating'] = $review['reviews_rating'];
        $data['created_at'] = $review['date_added'];
        $data['review_id_import'] = $review['reviews_id'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if ($custom) {
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
    protected function _importCategoryParent($parent_id) {
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_categories WHERE categories_id = {$parent_id}"
        ));
        if (!$categories || $categories['result'] != 'success') {
            return $this->errorConnector(true);
        }
        $categoriesExt = $this->getCategoriesExt($categories);
        if ($categoriesExt['result'] != 'success') {
            return $categoriesExt;
        }
        $category = $categories['object'][0];
        $convert = $this->convertCategory($category, $categoriesExt);
        if ($convert['result'] != 'success') {
            return array(
                'result' => 'warning',
            );
        }
        $data = $convert['data'];
        $category_ipt = $this->_process->category($data);
        if ($category_ipt['result'] == 'success') {
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
    protected function _flitArrayNum($array) {
        $data = array();
        foreach ($array as $value) {
            if (is_numeric($value)) {
                $data[] = $value;
            }
        }
        return $data;
    }

    protected function _updateProductOptionStoreView($product_id, $options, $pre_options, $store_id) {
        $mage_product = Mage::getModel('catalog/product')->load($product_id);
        $mage_option = $mage_product->getProductOptionsCollection();
        if (isset($mage_option)) {
            foreach ($mage_option as $o) {
                $title = $o->getTitle();
                $cos = array();
                $co = array();
                foreach ($pre_options as $key => $pre_option) {
                    if ($title == $pre_option['title']) {
                        $o->setProduct($mage_product);
                        $o->setTitle($options[$key]['title']);
                        $o->setType($pre_option['type']);
                        $o->setIsRequire($pre_option['is_require']);
                        if ($options[$key]['values']) {
                            $option_value = $o->getValuesCollection();
                            foreach ($option_value as $v) {
                                $value_title = $v->getTitle();
                                foreach ($pre_option['values'] as $k => $pre_value) {
                                    if ($value_title == $pre_value['title']) {
                                        $v->setTitle($options[$key]['values'][$k]['title']);
                                        $v->setStoreId($store_id);
                                        $v->setOption($o)->save();
                                        $cos[] = $v->toArray($co);
                                    }
                                }
                            }
                        }
                    }
                }
                $o->setData("values", $cos)
                        ->setStoreId($store_id)
                        ->save();
            }
        }
    }

    protected function _getOrderTaxValueFromListByCode($list, $code) {
        $result = 0;
        if ($list) {
            foreach ($list as $row) {
                if ($row['class'] == $code) {
                    $result += $row['value'];
                }
            }
        }
        return $result;
    }

    protected function _getPartName($name) {
        $fullname = array();
        $parts = explode(" ", $name);
        $fullname['lastname'] = array_pop($parts);
        $fullname['firstname'] = implode(" ", $parts);
        return $fullname;
    }

    protected function _importChildrenProduct($parent, $children, $productsExt) {
        $result = false;
        $child_attr_2 = $dataChildes = $attrMage = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attribute_set_id = $this->_notice['config']['attribute_set_id'];
        $store_view = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL;
        $option_name = '';
        foreach ($children as $value) {
            if ($value['products_model']) {
                $option_name = $value['products_model'];
                break;
            }
        }
        foreach ($children as $child) {
            $all_attr_value_2 = array();
            $value_name = $this->getRowValueFromListByField($productsExt['object']['products_description'], 'products_id', $child['products_id'], 'products_name');
            $attr_import = $this->_makeAttributeImport($option_name, $value_name, $entity_type_id, $attribute_set_id, $store_view);
            $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
            if (!$attr_after) {
                var_dump($attr_import);
                return false;
            }
            $attr_after['option_label'] = $value_name;
            $price = (float) $child['products_price'] - (float) $parent['products_price'];
            $attr_after['price_value'] = ($price > 0) ? $price : '0';
            $attrMage[$attr_after['attribute_id']]['attribute_label'] = $option_name;
            $attrMage[$attr_after['attribute_id']]['attribute_code'] = $attr_after['attribute_code'];
            $attrMage[$attr_after['attribute_id']]['values'][$attr_after['option_ids']['option_0']] = $attr_after;
            $pro_data_2[$attr_after['attribute_id']] = $attr_after['option_ids']['option_0'];
            $all_attr_value_2 = array_replace_recursive($all_attr_value_2, $pro_data_2);
            $child_attr_2[$child['products_id']] = $all_attr_value_2;
        }
        foreach ($children as $row) {
            $name = $this->getRowValueFromListByField($productsExt['object']['products_description'], 'products_id', $parent['products_id'], 'products_name');
            $name .= ' - ' . $this->getRowValueFromListByField($productsExt['object']['products_description'], 'products_id', $row['products_id'], 'products_name');
            $product = array(
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                'name' => $name,
            );
            $product = array_merge($this->_convertProduct($parent, $productsExt, $parent['products_id']), $product);
            $pro_import = $this->_process->product($product);
            if ($pro_import['result'] !== 'success') {
                print_r($parent['products_id']);
                return false;
            }
            $optionValues = $child_attr_2[$row['products_id']];
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

    protected function _makeAttributeImport($attribute, $option, $entity_type_id, $attribute_set_id, $store_view) {
        $attr_des = array();
        $attr_des[] = $attribute;
        $attr_name = $this->joinTextToKey($attr_des[0], 27, '_');
        if (in_array($attr_name, Mage::getModel('catalog/product')->getReservedAttributes())) {
            $attr_name = $attr_name . '123';
        }
        $opt_des = array();
        $opt_des[] = $option;
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attr_name,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => 'select',
            'frontend_label' => $attr_des,
            'is_visible_on_front' => 1,
            'is_global' => $store_view,
            'is_configurable' => true,
            'option' => array(
                'value' => array('option_0' => $opt_des)
            )
        );
        $edit = array(
            'is_global' => $store_view,
            'is_configurable' => true,
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
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
                'html_id' => 'config_super_product__attribute_' . $key,
            );
            $values = array();
            foreach ($attribute['values'] as $option) {
                $child = array(
                    'attribute_id' => $key,
                    'is_percent' => 0,
                    'pricing_value' => $option['price_value'],
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
    
    protected function _catUrlSuccess($id_import, $mage_id, $value = false) {
        return $this->_insertLeCaMgImport(self::CAT_URL, $id_import, $mage_id, 1, $value);
    }

    public function _getCatUrl($id_import) {
        $result = $this->_selectLeCaMgImport(array(
            'domain' => $this->_cart_url,
            'type' => self::CAT_URL,
            'id_import' => $id_import
        ));
        if (!$result) {
            return false;
        }
        return $result['value'];
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
        $query = "SELECT * FROM _DBPRF_tax_rates ORDER BY tax_rates_id ASC";
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
        $query = "SELECT * FROM _DBPRF_products WHERE products_parent_id = 0 ORDER BY products_id ASC";
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
        $query = "SELECT * FROM _DBPRF_reviews ORDER BY reviews_id ASC";
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
