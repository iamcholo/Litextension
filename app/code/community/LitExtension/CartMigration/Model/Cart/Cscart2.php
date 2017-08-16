<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Cscart2 extends LitExtension_CartMigration_Model_Cart {

    public function __construct() {
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_taxes WHERE tax_id > {$this->_notice['taxes']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE user_id > {$this->_notice['customers']['id_src']} AND user_type = 'C'",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_discussion_posts WHERE post_id > {$this->_notice['reviews']['id_src']}",
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
                "languages" => "SELECT cfg.*, lg.* FROM _DBPRF_settings AS cfg LEFT JOIN _DBPRF_languages AS lg ON lg.lang_code = cfg.value WHERE cfg.option_name = 'customer_default_language'",
                "currencies" => "SELECT * FROM _DBPRF_currencies WHERE is_primary = 'Y'",
            ))
        ));
        if (!$default_cfg || $default_cfg['result'] != 'success') {
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['lang_code']) ? $object['languages']['0']['lang_code'] : 1;
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['currency_id']) ? $object['currencies']['0']['currency_id'] : $object['currencies']['0']['currency_code'];
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_languages",
                "currencies" => "SELECT * FROM _DBPRF_currencies",
                "orders_status" => "SELECT * FROM _DBPRF_status_descriptions WHERE lang_code = '{$this->_notice['config']['default_lang']}' AND type = 'O'",
                "usergroup_descriptions" => "SELECT ud.usergroup_id, ud.usergroup, u.type from _DBPRF_usergroup_descriptions AS ud
                                              LEFT JOIN `cscart_usergroups` AS u on u.usergroup_id = ud.usergroup_id
                                              WHERE ud.lang_code = '{$this->_notice['config']['default_lang']}'"
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach ($obj['languages'] as $language_row) {
            $lang_id = $language_row['lang_code'];
            $lang_name = $language_row['name'] . "(" . $language_row['lang_code'] . ")";
            $language_data[$lang_id] = $lang_name;
        }
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = isset($currency_row['currency_id']) ? $currency_row['currency_id'] : $currency_row['currency_code'];
            $currency_name = $currency_row['currency_code'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['status'];
            $order_status_name = $order_status_row['description'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        foreach($obj['usergroup_descriptions'] as $group_pricing_row){
            $group_pricing_id = $group_pricing_row['usergroup_id'];
            $group_pricing_name = $group_pricing_row['usergroup'];
            $customer_group_data[$group_pricing_id] = $group_pricing_name;
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
            $types = array('taxes', 'categories', 'products', 'customers', 'orders', 'reviews');
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_taxes WHERE tax_id > {$this->_notice['taxes']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE user_id > {$this->_notice['customers']['id_src']} AND user_type = 'C'",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_discussion_posts WHERE post_id > {$this->_notice['reviews']['id_src']}"
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
                $currency_id = isset($currency['currency_id']) ? $currency['currency_id'] : $currency['currency_code'];
                $currency_value = $currency['coefficient'];
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
        $query = "SELECT * FROM _DBPRF_taxes WHERE tax_id > {$id_src} ORDER BY tax_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import tax rule
     *
     * @param array $taxes : Data of function getTaxesMain
     * @return array : Response of connector
     */
    public function getTaxesExt($taxes) {
        $taxesExt = array(
            'result' => 'success'
        );
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_id');
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'tax_descriptions' => "SELECT * FROM _DBPRF_tax_descriptions WHERE tax_id IN {$tax_id_con}",
            'tax_rates' => "SELECT * FROM _DBPRF_tax_rates WHERE tax_id IN {$tax_id_con}"
        );
        $cus_ext_query = $this->_custom->getTaxesExtQueryCustom($this, $taxes);
        if ($cus_ext_query) {
            $ext_query = array_merge($ext_query, $cus_ext_query);
        }
        if ($ext_query) {
            $taxesExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
                'serialize' => true,
                "query" => serialize($ext_query)
            ));
            if (!$taxesExt || $taxesExt['result'] != 'success') {
                return $this->errorConnector(true);
            }
            $desIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rates'], 'destination_id');
            $des_id_query = $this->arrayToInCondition($desIds);
            $ext_rel_query = array(
                'destination_descriptions' => "SELECT * FROM _DBPRF_destination_descriptions WHERE destination_id IN {$des_id_query}",
                'destination_elements' => "SELECT * FROM _DBPRF_destination_elements WHERE destination_id IN {$des_id_query}",
            );
            $cus_ext_rel_query = $this->_custom->getTaxesExtRelQueryCustom($this, $taxes, $taxesExt);
            if ($cus_ext_rel_query) {
                $ext_rel_query = array_merge($ext_rel_query, $cus_ext_rel_query);
            }
            if ($ext_rel_query) {
                $taxesExtRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    "query" => serialize($ext_rel_query)
                ));
                if (!$taxesExtRel || $taxesExtRel['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $taxesExt = $this->_syncResultQuery($taxesExt, $taxesExtRel);
            }
            $stateIds = $this->duplicateFieldValueFromList($taxesExt['object']['destination_elements'], 'element');
            $stateIds_num = $this->_flitArrayNum($stateIds);
            $states_query = $this->arrayToInCondition($stateIds_num);
            $ext_rel_rel_query = array(
                'states' => "SELECT s.*, sd.* FROM _DBPRF_states as s LEFT JOIN _DBPRF_state_descriptions as sd ON s.state_id = sd.state_id WHERE s.state_id IN {$states_query} AND sd.lang_code = '{$this->_notice['config']['default_lang']}'",
            );
            if ($ext_rel_rel_query) {
                $taxesExtRelRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    "query" => serialize($ext_rel_rel_query)
                ));
                if (!$taxesExtRelRel || $taxesExtRelRel['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $taxesExt = $this->_syncResultQuery($taxesExt, $taxesExtRelRel);
            }
        }
        return $taxesExt;
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt) {
        return $tax['tax_id'];
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
        $taxClass = $this->getRowFromListByField($taxesExt['object']['tax_descriptions'], 'tax_id', $tax['tax_id']);
        $tax_pro_data = array(
            'class_name' => $taxClass['tax']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($taxClass['tax_id'], $tax_pro_ipt['mage_id']);
        }
        $taxRates = $this->getListFromListByField($taxesExt['object']['tax_rates'], 'tax_id', $tax['tax_id']);
        foreach ($taxRates as $tax_rate) {
            $taxZone = $this->getListFromListByField($taxesExt['object']['destination_elements'], 'destination_id', $tax_rate['destination_id']);
            foreach ($taxZone as $tax_zone) {
                if ($tax_zone['element_type'] != 'C' && $tax_zone['element_type'] != 'S') {
                    continue;
                }
                $tax_rate_data = array();
                if ($tax_zone['element_type'] == 'C') {
                    $zone = 'All States';
                    $country_code = $tax_zone['element'];
                } else {
                    $states = $this->getRowFromListByField($taxesExt['object']['states'], 'state_id', $tax_zone['element']);
                    $zone = $states['code'];
                    $country_code = $states['country_code'];
                }
                $code = $country_code . "-" . $zone . "-" . $tax_zone['destination_id'];
                $tax_rate_data['code'] = $this->createTaxRateCode($code);
                $tax_rate_data['tax_country_id'] = $country_code;
                if ($tax_zone['element_type'] == 'C') {
                    $tax_rate_data['tax_region_id'] = 0;
                } else {
                    $tax_rate_data['tax_region_id'] = $this->getRegionId($states['state'], $country_code);
                }
                $tax_rate_data['zip_is_range'] = 0;
                $tax_rate_data['tax_postcode'] = "*";
                $tax_rate_data['rate'] = $tax_rate['rate_value'];
                $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                if ($tax_rate_ipt['result'] == 'success') {
                    $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($taxClass['tax']);
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
        return array();
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
        return;
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return array
     */
    public function convertManufacturer($manufacturer, $manufacturersExt) {
        return array();
    }

    /**
     * Query for get data of main table use import category
     *
     * @return string
     */
    protected function _getCategoriesMainQuery() {
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $query = "SELECT * FROM _DBPRF_categories WHERE category_id > {$id_src} ORDER BY category_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories) {
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'category_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'category_descriptions' => "SELECT * FROM _DBPRF_category_descriptions WHERE category_id IN {$cat_id_con}",
            'images_links' => "SELECT i.*, il.* FROM _DBPRF_images_links as il LEFT JOIN _DBPRF_images as i ON i.image_id = il.detailed_id WHERE il.object_type = 'category' AND il.object_id IN {$cat_id_con}"
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
        return $category['category_id'];
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
        if ($category['parent_id'] == 0 || $category['parent_id'] == $category['category_id']) {
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
                        'msg' => $this->consoleWarning("Category Id = {$category['category_id']} import failed. Error: Could not import parent category id = {$category['parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['category_descriptions'], 'category_id', $category['category_id']);
        $cat_lang = $this->getRowFromListByField($catDesc, 'lang_code', $this->_notice['config']['default_lang']);
        $cat_data['name'] = $cat_lang['category'];
        $cat_data['description'] = $cat_lang['description'];
        $cat_data['meta_title'] = $cat_lang['page_title'];
        $cat_data['meta_keywords'] = $cat_lang['meta_keywords'];
        $cat_data['meta_description'] = $cat_lang['meta_description'];
        $cat_img = $this->getRowFromListByField($categoriesExt['object']['images_links'], 'object_id', $category['category_id']);
        if ($cat_img) {
            if ($this->_convertVersion($this->_notice['config']['cart_version'], 2) >= 210) {
                $cat_img_path = $this->_getImagePath($cat_img['detailed_id'], $cat_img['image_path']);
            } else {
                $cat_img_path = $cat_img['image_path'];
            }
            if ($img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $cat_img_path, 'catalog/category')) {
                $cat_data['image'] = $img_path;
            }
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['status'] == 'A') ? 1 : 0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $store_data = array();
            $store_lang = $this->getRowFromListByField($catDesc, 'lang_code', $lang_id);
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_lang) {
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_lang['category'];
                $store_data['description'] = $store_lang['description'];
                $store_data['meta_title'] = $store_lang['page_title'];
                $store_data['meta_keywords'] = $store_lang['meta_keywords'];
                $store_data['meta_description'] = $store_lang['meta_description'];
                $multi_store[] = $store_data;
            }
        }
        $cat_data['multi_store'] = $multi_store;
        if ($this->_seo) {
            $seo = $this->_seo->convertCategorySeo($this, $category, $categoriesExt);
            if ($seo) {
                $cat_data['seo_url'] = $seo;
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
        $query = "SELECT * FROM _DBPRF_products WHERE product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products) {
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'product_id');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'product_descriptions' => "SELECT * FROM _DBPRF_product_descriptions WHERE product_id IN {$pro_ids_query}",
            'product_features_values' => "SELECT * FROM _DBPRF_product_features_values WHERE product_id IN {$pro_ids_query} AND lang_code = '{$this->_notice['config']['default_lang']}'",
            'products_categories' => "SELECT * FROM _DBPRF_products_categories WHERE product_id IN {$pro_ids_query}",
            'images_links' => "SELECT i.*, il.* FROM _DBPRF_images_links as il LEFT JOIN _DBPRF_images as i ON i.image_id = il.detailed_id WHERE il.object_type = 'product' AND il.object_id IN {$pro_ids_query}",
            'product_options' => "SELECT * FROM _DBPRF_product_options WHERE product_id IN {$pro_ids_query} OR product_id = 0",
            'product_prices' => "SELECT * FROM _DBPRF_product_prices WHERE product_id IN {$pro_ids_query}",
            'product_global_option_links' => "SELECT * FROM _DBPRF_product_global_option_links WHERE product_id IN {$pro_ids_query}"
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
        $productOptionIds = $this->duplicateFieldValueFromList($productsExt['object']['product_options'], 'option_id');
        $productFeatureIds = $this->duplicateFieldValueFromList($productsExt['object']['product_features_values'], 'feature_id');
        $productVariantIds = $this->duplicateFieldValueFromList($productsExt['object']['product_features_values'], 'variant_id');
        $product_option_ids_query = $this->arrayToInCondition($productOptionIds);
        $product_feature_ids_query = $this->arrayToInCondition($productFeatureIds);
        $product_variant_ids_query = $this->arrayToInCondition($productVariantIds);
        $ext_rel_query = array(
            'product_features_descriptions' => "SELECT * FROM _DBPRF_product_features_descriptions WHERE feature_id IN {$product_feature_ids_query}",
            'product_feature_variant_descriptions' => "SELECT * FROM _DBPRF_product_feature_variant_descriptions WHERE variant_id IN {$product_variant_ids_query}",
            'product_options_descriptions' => "SELECT * FROM _DBPRF_product_options_descriptions WHERE option_id IN {$product_option_ids_query}",
            'product_option_variants' => "SELECT pov.*, povd.* FROM _DBPRF_product_option_variants as pov LEFT JOIN _DBPRF_product_option_variants_descriptions as povd ON povd.variant_id = pov.variant_id WHERE pov.option_id IN {$product_option_ids_query}",
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
        return $product['product_id'];
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
        $pro_data = array();
        // Attribute
        $attr_pro = $this->getListFromListByField($productsExt['object']['product_features_values'], 'product_id', $product['product_id']);
        if ($attr_pro) {
            $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
            $attribute_set_id = $this->_notice['config']['attribute_set_id'];
            $store_view = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE;
            foreach ($attr_pro as $row) {
                $attr = $this->getListFromListByField($productsExt['object']['product_features_descriptions'], 'feature_id', $row['feature_id']);
                if ($row['variant_id']) {
                    $attr_val = $this->getListFromListByField($productsExt['object']['product_feature_variant_descriptions'], 'variant_id', $row['variant_id']);
                } else {
                    $attr_val = $row['value'] ? $row['value'] : $row['value_int'];
                }
                $attr_import = $this->_makeAttributeImport($attr, $attr_val, $entity_type_id, $attribute_set_id, $store_view);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if (!$attr_after) {
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$product['product_id']} import failed. Error: Product attribute could not create!")
                    );
                }
                $pro_data[$attr_after['attribute_code']] = is_array($attr_val) ? $attr_after['option_ids']['option_0'] : $attr_val;
            }
        }
        //end Attr
        $categories = array();
        $proCat = $this->getListFromListByField($productsExt['object']['products_categories'], 'product_id', $product['product_id']);
        if ($proCat) {
            foreach ($proCat as $pro_cat) {
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if ($cat_id) {
                    $categories[] = $cat_id;
                }
            }
        }
        $proDesc = $this->getListFromListByField($productsExt['object']['product_descriptions'], 'product_id', $product['product_id']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'lang_code', $this->_notice['config']['default_lang']);
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        if ($product['product_code']) {
            $pro_data['sku'] = $this->createProductSku($product['product_code'], $this->_notice['config']['languages']);
        } else {
            $pro_data['sku'] = $this->createProductSku('product-sku', $this->_notice['config']['languages']);
        }
        $pro_data['name'] = $pro_desc_def['product'];
        $pro_data['description'] = $this->changeImgSrcInText($pro_desc_def['full_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_desc_def['short_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['tags'] = $pro_desc_def['search_words'];
        $pro_data['meta_title'] = $pro_desc_def['page_title'];
        $pro_data['meta_keyword'] = $pro_desc_def['meta_keywords'];
        $pro_data['meta_description'] = $pro_desc_def['meta_description'];
        $price_all = $this->getListFromListByField($productsExt['object']['product_prices'], 'product_id', $product['product_id']);
        $price_final = $this->getRowValueFromListByField($price_all, 'lower_limit', '1', 'price');
        $pro_data['price'] = $price_final ? $price_final : 0;
        $proTier = $this->_getListFromListByFieldOver($price_all, 'lower_limit', '1');
        if ($proTier) {
            foreach ($proTier as $row) {
                $price_tier = $row['price'];
                $value = array(
                    'website_id' => 0,
                    'cust_group' => Mage_Customer_Model_Group::CUST_GROUP_ALL,
                    'price_qty' => $row['lower_limit'],
                    'price' => $price_tier
                );
                $tier_prices[] = $value;
            }
            $pro_data['tier_price'] = $tier_prices;
        }
        $pro_data['weight'] = $product['weight'] ? $product['weight'] : 0;
        $pro_data['status'] = ($product['status'] == 'A') ? 1 : 2;
        if ($product['tax_ids'] && $tax_pro_id = $this->getMageIdTaxProduct($product['tax_ids'])) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = date("Y-m-d H:i:s", $product['timestamp']);
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['amount'] < 1) ? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['amount'] < 1) ? 0 : 1,
            'qty' => $product['amount']
        );
        $proImg = $this->getListFromListByField($productsExt['object']['images_links'], 'object_id', $product['product_id']);
        if ($proImg) {
            $image_main = $this->getRowFromListByField($proImg, 'type', 'M');
            if ($image_main) {
                if ($this->_convertVersion($this->_notice['config']['cart_version'], 2) >= 210) {
                    $image_pro = $this->_getImagePath($image_main['detailed_id'], $image_main['image_path']);
                } else {
                    $image_pro = $image_main['image_path'];
                }
                if ($image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_pro, 'catalog/product', false, true)) {
                    $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                }
            }
            foreach ($proImg as $gallery) {
                if ($gallery['type'] == 'M') continue;
                if ($this->_convertVersion($this->_notice['config']['cart_version'], 2) >= 210) {
                    $image_gallery = $this->_getImagePath($gallery['detailed_id'], $gallery['image_path']);
                } else {
                    $image_gallery = $gallery['image_path'];
                }
                if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_gallery, 'catalog/product', false, true)) {
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
                }
            }
        }
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'lang_code', $lang_id)) {
                $store_data = array();
                $store_data['name'] = $store_data_change['product'];
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['full_description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['short_description'] = $this->changeImgSrcInText($store_data_change['short_description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = $store_data_change['page_title'];
                $store_data['meta_keyword'] = $store_data_change['meta_keywords'];
                $store_data['meta_description'] = $store_data_change['meta_description'];
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
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt) {
        if (parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)) {
            return;
        }
        $globalOptions = $this->getListFromListByField($productsExt['object']['product_global_option_links'], 'product_id', $product['product_id']);
        $global_option = $this->duplicateFieldValueFromList($globalOptions, 'option_id');
        $proAttrGlobal = $this->getListFromListByListField($productsExt['object']['product_options'], 'option_id', $global_option);
        $proAttr = $this->getListFromListByField($productsExt['object']['product_options'], 'product_id', $product['product_id']);
        $proAttrAll = array_merge($proAttrGlobal, $proAttr);
        if ($proAttrAll) {
            $opt_data = array();
            $opt_data_store = array();
            foreach ($proAttrAll as $pro_opt) {
                $proOpt = $this->getListFromListByField($productsExt['object']['product_options_descriptions'], 'option_id', $pro_opt['option_id']);
                $proOptValLang = $this->getListFromListByField($productsExt['object']['product_option_variants'], 'option_id', $pro_opt['option_id']);
                $proOptVal = $this->getListFromListByField($proOptValLang, 'lang_code', $this->_notice['config']['default_lang']);
                if (!$proOpt) {
                    continue;
                }
                $type_import = $this->_getOptionTypeByTypeSrc($pro_opt['option_type']);
                $option = array(
                    'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT,
                    'type' => $type_import,
                    'is_require' => $pro_opt['required'] == 'Y' ? 1 : 0,
                    'title' => $this->getRowValueFromListByField($proOpt, 'lang_code', $this->_notice['config']['default_lang'], 'option_name')
                );
                foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                    if ($lang_id == $this->_notice['config']['default_lang']) {
                        continue;
                    }
                    $option_store[$lang_id] = array(
                        'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT,
                        'type' => $type_import,
                        'is_require' => $pro_opt['required'] == 'Y' ? 1 : 0,
                        'title' => $this->getRowValueFromListByField($proOpt, 'lang_code', $lang_id, 'option_name')
                    );
                }
                $values = array();
                $value_stores = array();
                if ($proOptVal) {
                    foreach ($proOptVal as $pro_opt_val) {
                        $proVal = $this->getListFromListByField($productsExt['object']['product_option_variants'], 'variant_id', $pro_opt_val['variant_id']);
                        if ($pro_opt_val['modifier_type'] == 'A') {
                            $price_add = $pro_opt_val['modifier'];
                        } else {
                            $price_add = $data['price'] * $pro_opt_val['modifier'] / 100;
                        }
                        $value = array(
                            'option_type_id' => -1,
                            'title' => $this->getRowValueFromListByField($proVal, 'lang_code', $this->_notice['config']['default_lang'], 'variant_name'),
                            'price' => $price_add,
                            'price_type' => 'fixed',
                        );
                        $values[] = $value;
                        foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                            if ($lang_id == $this->_notice['config']['default_lang']) {
                                continue;
                            }
                            $value_store = array(
                                'option_type_id' => -1,
                                'title' => $this->getRowValueFromListByField($proVal, 'lang_code', $lang_id, 'variant_name'),
                                'price' => $price_add,
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
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery() {
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_users WHERE user_id > {$id_src} AND user_type = 'C' ORDER BY user_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers) {
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'user_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'user_profiles' => "SELECT * FROM _DBPRF_user_profiles WHERE user_id IN {$customer_ids_query}",
            'usergroup_links' => "SELECT * FROM _DBPRF_usergroup_links WHERE user_id IN {$customer_ids_query} AND status = 'A'"
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
        $billIds = (array) $this->duplicateFieldValueFromList($customersExt['object']['user_profiles'], 'b_state');
        $shipIds = (array) $this->duplicateFieldValueFromList($customersExt['object']['user_profiles'], 's_state');
        $stateIds = array_unique(array_merge($billIds, $shipIds));
        $state_ids_query = $this->arrayToInCondition($stateIds);
        $ext_rel_query = array(
            'states' => "SELECT s.*, sd.* FROM _DBPRF_states as s LEFT JOIN _DBPRF_state_descriptions as sd ON s.state_id = sd.state_id WHERE sd.lang_code = '{$this->_notice['config']['default_lang']}' AND s.code IN {$state_ids_query}",
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
    public function getCustomerId($customer, $customersExt) {
        return $customer['user_id'];
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
            $cus_data['id'] = $customer['user_id'];
        }
        $usergroup_id = $this->getRowValueFromListByField($customersExt['object']['usergroup_links'], 'user_id', $customer['user_id'], 'usergroup_id');
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['firstname'];
        $cus_data['lastname'] = $customer['lastname'];
        $cus_data['created_at'] = date("Y-m-d H:i:s", $customer['timestamp']);
        $cus_data['is_subscribed'] = 0;
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$usergroup_id]) ? $this->_notice['config']['customer_group'][$usergroup_id] : 1;
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['password']);
        $cusAdd = $this->getRowFromListByField($customersExt['object']['user_profiles'], 'user_id', $customer['user_id']);
        if ($cusAdd) {
            $signal = array('b_', 's_');
            foreach ($signal as $sign) {
                $address = array();
                $address['firstname'] = $cusAdd[$sign . 'firstname'];
                $address['lastname'] = $cusAdd[$sign . 'lastname'];
                $address['country_id'] = $cusAdd[$sign . 'country'];
                $address['street'] = $cusAdd[$sign . 'address'] . "\n" . $cusAdd[$sign . 'address_2'];
                $address['postcode'] = $cusAdd[$sign . 'zipcode'];
                $address['city'] = $cusAdd[$sign . 'city'];
                $address['telephone'] = isset($cusAdd[$sign . 'phone']) ? $cusAdd[$sign . 'phone'] : '';
                $state = $this->getRowFromListByField($customersExt['object']['states'], 'code', $cusAdd[$sign . 'state']);
                if ($state) {
                    $region_id = $this->getRegionId($state['state'], $cusAdd[$sign . 'country']);
                    if ($region_id) {
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $state['state'];
                } else {
                    $address['region'] = $cusAdd[$sign . 'address_2'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if ($address_ipt['result'] == 'success') {
                    try {
                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                        if ($sign == 'b_') {
                            $cus->setDefaultBilling($address_ipt['mage_id']);
                        }
                        if ($sign == 's_') {
                            $cus->setDefaultShipping($address_ipt['mage_id']);
                        }
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
        $query = "SELECT * FROM _DBPRF_orders WHERE order_id > {$id_src} ORDER BY order_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    protected function _getOrdersExtQuery($orders) {
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'order_id');
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $billIds = (array) $this->duplicateFieldValueFromList($orders['object'], 'b_state');
        $shipIds = (array) $this->duplicateFieldValueFromList($orders['object'], 's_state');
        $stateIds = array_unique(array_merge($billIds, $shipIds));
        $state_ids_query = $this->arrayToInCondition($stateIds);
        $paymentIds = $this->duplicateFieldValueFromList($orders['object'], 'payment_id');
        $payment_ids_query = $this->arrayToInCondition($paymentIds);
        $ext_query = array(
            'order_details' => "SELECT * FROM _DBPRF_order_details WHERE order_id IN {$order_ids_query}",
            'order_data' => "SELECT * FROM _DBPRF_order_data WHERE order_id IN {$order_ids_query}",
            'states' => "SELECT s.*, sd.* FROM _DBPRF_states as s LEFT JOIN _DBPRF_state_descriptions as sd ON s.state_id = sd.state_id WHERE sd.lang_code = '{$this->_notice['config']['default_lang']}' AND s.code IN {$state_ids_query}",
            'payment_descriptions' => "SELECT * FROM _DBPRF_payment_descriptions WHERE payment_id IN {$payment_ids_query}",
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
        $extras = $this->duplicateFieldValueFromList($ordersExt['object']['order_details'], 'extra');
        $extra_ids = $this->_getOptionDetail($extras);
        $option_ids_query = $this->arrayToInCondition($extra_ids['option']);
        $value_ids_query = $this->arrayToInCondition($extra_ids['value']);
        $products = $this->duplicateFieldValueFromList($ordersExt['object']['order_details'], 'product_id');
        $product_ids_query = $this->arrayToInCondition($products);
        $ext_rel_query = array(
            'product_descriptions' => "SELECT * FROM _DBPRF_product_descriptions WHERE product_id IN {$product_ids_query} AND lang_code = '{$this->_notice['config']['default_lang']}'",
            'product_options_descriptions' => "SELECT * FROM _DBPRF_product_options_descriptions WHERE option_id IN {$option_ids_query} AND lang_code = '{$this->_notice['config']['default_lang']}'",
            'product_option_variants_descriptions' => "SELECT * FROM _DBPRF_product_option_variants_descriptions WHERE variant_id IN {$value_ids_query} AND lang_code = '{$this->_notice['config']['default_lang']}'",
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
    public function getOrderId($order, $ordersExt) {
        return $order['order_id'];
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

        $address_billing['firstname'] = $order['b_firstname'];
        $address_billing['lastname'] = $order['b_lastname'];
        $address_billing['company'] = $order['company'];
        $address_billing['street'] = $order['b_address'] . "\n" . $order['b_address_2'];
        $address_billing['city'] = $order['b_city'];
        $address_billing['postcode'] = $order['b_zipcode'];
        $address_billing['country_id'] = $order['b_country'];
        if ($order['b_state']) {
            $state_bill = $this->getRowFromListByField($ordersExt['object']['states'], 'code', $order['b_state']);
            $billing_region_id = $this->getRegionId($state_bill['state'], $order['b_country']);
        }
        if ($billing_region_id) {
            $address_billing['region_id'] = $billing_region_id;
        } else {
            $address_billing['region'] = $state_bill['state'];
        }
        $address_billing['telephone'] = isset($order['b_phone']) ? $order['b_phone'] : $order['phone'];

        $address_shipping['firstname'] = $order['s_firstname'];
        $address_shipping['lastname'] = $order['s_lastname'];
        $address_shipping['company'] = $order['company'];
        $address_shipping['street'] = $order['s_address'] . "\n" . $order['s_address_2'];
        $address_shipping['city'] = $order['s_city'];
        $address_shipping['postcode'] = $order['s_zipcode'];
        $address_shipping['country_id'] = $order['s_country'];
        if ($order['s_state']) {
            $state_ship = $this->getRowFromListByField($ordersExt['object']['states'], 'code', $order['s_state']);
            $shipping_region_id = $this->getRegionId($state_ship['state'], $address_shipping['country_id']);
        }
        if ($shipping_region_id) {
            $address_shipping['region_id'] = $shipping_region_id;
        } else {
            $address_shipping['region'] = $state_ship['state'];
        }
        $address_shipping['telephone'] = isset($order['s_phone']) ? $order['s_phone'] : $order['phone'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['order_details'], 'order_id', $order['order_id']);
        $orderDatas = $this->getListFromListByField($ordersExt['object']['order_data'], 'order_id', $order['order_id']);
        $shipping_zone = $this->getRowValueFromListByField($orderDatas, 'type', 'L', 'data');
        $shipping_zone = @unserialize($shipping_zone);
        $tax_zone = $this->getRowValueFromListByField($orderDatas, 'type', 'T', 'data');
        $tax_zone = @unserialize($tax_zone);
        $carts = array();
        foreach ($orderPro as $order_pro) {
            $cart = array();
            $product_id = $this->getMageIdProduct($order_pro['product_id']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $extra = unserialize($order_pro['extra']);
            $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $cart['name'] = isset($extra['product']) ? $extra['product'] : $this->getRowValueFromListByField($ordersExt['object']['product_descriptions'], 'product_id', $order_pro['product_id'], 'product');
            $cart['sku'] = strtolower(str_replace(" ", "-", $order_pro['product_code']));
            $cart['price'] = $order_pro['price'];
            $cart['original_price'] = $order_pro['price'];
            $tax_amount = 0;
            foreach ($tax_zone as $tax_id => $tax_data) {
                foreach ($tax_data['applies'] as $_id => $value) {
                    if (strpos($_id, 'P_') !== false && strpos($_id, $order_pro['item_id']) !== false) {
                        $tax_amount += $value;
                    }
                }
            }
            $cart['tax_amount'] = $tax_amount;
            //$cart['tax_percent'] = $tax_amount / ($order_pro['price'] * $order_pro['amount']);
            $cart['tax_percent'] = '0';
            $cart['discount_amount'] = '0';
            $cart['qty_ordered'] = $order_pro['amount'];
            $cart['row_total'] = $order_pro['price'] * $order_pro['amount'];
            if ($extra['product_options']) {
                $product_opt = array();
                $options = array();
                foreach ($extra['product_options'] as $option => $value) {
                    $option_name = $this->getRowValueFromListByField($ordersExt['object']['product_options_descriptions'], 'option_id', $option, 'option_name');
                    $value_name = $this->getRowValueFromListByField($ordersExt['object']['product_option_variants_descriptions'], 'variant_id', $value, 'variant_name');
                    $option = array(
                        'label' => $option_name,
                        'value' => $value_name,
                        'print_value' => $value_name,
                        'option_id' => 'option_' . $option,
                        'option_type' => 'drop_down',
                        'option_value' => 0,
                        'custom_view' => false
                    );
                    $options[] = $option;
                }
                $product_opt = array('options' => $options);
                $cart['product_options'] = serialize($product_opt);
            }
            $carts[] = $cart;
        }

        $customer_id = $this->getMageIdCustomer($order['user_id']);
        $order_status_id = $order['status'];
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
        $order_data['customer_email'] = $order['email'];
        $order_data['customer_firstname'] = $order['firstname'];
        $order_data['customer_lastname'] = $order['lastname'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['subtotal']);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['shipping_cost'];
        $order_data['base_shipping_amount'] = $order['shipping_cost'];
        $order_data['base_shipping_invoiced'] = $order['shipping_cost'];
        $shipping_first = reset($shipping_zone);
        $order_data['shipping_description'] = $shipping_first['shipping'];
        if ($tax_zone) {
            $tax_sum = 0;
            if (is_array($tax_zone)) {
                foreach ($tax_zone as $v) {
                    if ($v['price_includes_tax'] == 'Y') {
                        continue;
                    }
                    if (!empty($v['tax_subtotal'])) {
                        $tax_sum += $v['tax_subtotal'];
                    }
                }
            }
            $tax_sum = round((double) $tax_sum, 2);
            $order_data['tax_amount'] = $tax_sum;
            $order_data['base_tax_amount'] = $tax_sum;
        }
        $order_data['discount_amount'] = $order['subtotal_discount'] + $order['discount'];
        $order_data['base_discount_amount'] = $order['subtotal_discount'] + $order['discount'];
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
        $order_data['created_at'] = date("Y-m-d H:i:s", $order['timestamp']);

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['order_id'];
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
        $payment_method = $this->getRowValueFromListByField($ordersExt['object']['payment_descriptions'], 'payment_id', $order['payment_id'], 'payment');
        for ($i = 0; $i < 2; $i++) {
            $order_status_data = array();
            $order_status_id = $order['status'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if ($i == 0) {
                $order_status_data['comment'] = "<b>Reference order #" . $order['order_id'] . "</b><br /><b>Payment method: </b>" . $payment_method . "<br /><b>Shipping method: </b> " . $data['order']['shipping_description'] . "<br /><br />" . $order['notes'];
            } else {
                $order_status_data['comment'] = $order['details'];
            }
            $order_status_data['is_customer_notified'] = ($i == 0) ? 1 : 0;
            $order_status_data['updated_at'] = date("Y-m-d H:i:s", $order['timestamp']);
            $order_status_data['created_at'] = date("Y-m-d H:i:s", $order['timestamp']);
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
        $query = "SELECT * FROM _DBPRF_discussion_posts WHERE post_id > {$id_src} ORDER BY post_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews) {
        $postIds = $this->duplicateFieldValueFromList($reviews['object'], 'post_id');
        $post_ids_query = $this->arrayToInCondition($postIds);
        $ext_query = array(
            'discussion_messages' => "SELECT dm.*, d.* FROM _DBPRF_discussion_messages as dm LEFT JOIN _DBPRF_discussion as d ON d.thread_id = dm.thread_id WHERE dm.post_id IN {$post_ids_query}",
            'discussion_rating' => "SELECT * FROM _DBPRF_discussion_rating WHERE post_id IN {$post_ids_query}",
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
        return $review['post_id'];
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
        $review_detail = $this->getRowFromListByField($reviewsExt['object']['discussion_messages'], 'post_id', $review['post_id']);
        $product_id = $review_detail['object_id'];
        $product_mage_id = $this->getMageIdProduct($product_id);
        if (!$product_mage_id) {
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['post_id']} import failed. Error: Product Id = {$product_id} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['status'] != 'A') ? 3 : 1;
        $data['title'] = " ";
        $data['detail'] = $review_detail['message'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['user_id'])) ? $this->getMageIdCustomer($review['user_id']) : null;
        $data['nickname'] = $review['name'];
        $rating = $this->getRowValueFromListByField($reviewsExt['object']['discussion_rating'], 'post_id', $review['post_id'], 'rating_value');
        $data['rating'] = $rating;
        $data['created_at'] = date("Y-m-d H:i:s", $review['timestamp']);
        $data['review_id_import'] = $review['post_id'];
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
            'query' => "SELECT * FROM _DBPRF_categories WHERE category_id = {$parent_id}"
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

    protected function _makeAttributeImport($attribute, $option, $entity_type_id, $attribute_set_id, $store_view) {
        $attr_des = array();
        $attr_des[] = $this->getRowValueFromListByField($attribute, 'lang_code', $this->_notice['config']['default_lang'], 'description');
        $attr_name = $this->joinTextToKey($attr_des[0], 30, '_');
        $opt_des = array();
        $opt_des[] = is_array($option) ? $this->getRowValueFromListByField($option, 'lang_code', $this->_notice['config']['default_lang'], 'variant') : "";
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id == $this->_notice['config']['default_lang']) {
                continue;
            }
            $opt_des[$store_id] = is_array($option) ? $this->getRowValueFromListByField($option, 'lang_code', $lang_id, 'variant') : "";
            $attr_des[$store_id] = $this->getRowValueFromListByField($attribute, 'lang_code', $lang_id, 'description');
        }
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attr_name,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => is_array($option) ? 'select' : 'text',
            'frontend_label' => $attr_des,
            'is_visible_on_front' => 1,
            'is_global' => $store_view,
            'is_configurable' => false,
            'option' => array(
                'value' => is_array($option) ? array('option_0' => $opt_des) : array()
            )
        );
        $edit = array(
            'is_global' => $store_view,
            'is_configurable' => false,
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
        return $result;
    }

    protected function _getListFromListByFieldOver($list, $field, $value) {
        if (!$list) {
            return false;
        }
        $result = array();
        foreach ($list as $row) {
            if ($row[$field] > $value) {
                $result[] = $row;
            }
        }
        return $result;
    }

    protected function _getImagePath($image_id, $image_name) {
        $path = floor($image_id / 1000);
        $image_path = $path . '/' . $image_name;
        return $image_path;
    }

    protected function _getOptionDetail($extras) {
        $options['option'] = array();
        $options['value'] = array();
        foreach ($extras as $extra) {
            $extra = unserialize($extra);
            if ($extra['product_options']) {
                foreach ($extra['product_options'] as $option => $value) {
                    $options['option'][] = $option;
                    $options['value'][] = $value;
                }
            }
        }
        return $options;
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
        $query = "SELECT * FROM _DBPRF_taxes ORDER BY tax_id ASC";
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
        return array(
            'result' => 'success',
            'object' => array()
        );
    }

    public function getAllCategories()
    {
        if(!$this->_notice['config']['import']['categories']){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $query = "SELECT * FROM _DBPRF_categories ORDER BY category_id ASC";
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
        $query = "SELECT * FROM _DBPRF_products ORDER BY product_id ASC";
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
        $query = "SELECT * FROM _DBPRF_users ORDER BY user_id ASC";
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
        $query = "SELECT * FROM _DBPRF_orders ORDER BY order_id ASC";
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
        $query = "SELECT * FROM _DBPRF_discussion_posts ORDER BY post_id ASC";
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
	
	protected function _getOptionTypeByTypeSrc($type_name) {
        $types = array(
            'S' => 'drop_down',
            'I' => 'field',
            'R' => 'radio',
            'C' => 'checkbox',
            'T' => 'area',
        );
        return isset($types[$type_name]) ? $types[$type_name] : false;
    }

}
