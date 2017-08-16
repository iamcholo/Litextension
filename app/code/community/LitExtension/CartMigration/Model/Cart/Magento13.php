<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Magento13 extends LitExtension_CartMigration_Model_Cart {

    const TYPE_TAX_CUSTOMER_GROUP = 'tax_customer_group';

    public function __construct() {
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_calculation_rule WHERE tax_calculation_rule_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_eav_attribute as ea
                                                LEFT JOIN _DBPRF_eav_attribute_option as eao ON ea.attribute_id = eao.attribute_id
                                                WHERE attribute_code = 'manufacturer' AND eao.option_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_catalog_category_entity WHERE entity_id > {$this->_notice['categories']['id_src']} AND level > 1",
                'products' => "SELECT COUNT( 1 ) FROM _DBPRF_catalog_product_entity WHERE entity_id NOT IN
                    (SELECT product_id FROM _DBPRF_catalog_product_super_link UNION SELECT product_id FROM _DBPRF_catalog_product_bundle_selection UNION SELECT linked_product_id FROM catalog_product_link WHERE link_type_id = 3) AND entity_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer_entity WHERE entity_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_sales_order WHERE entity_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_review WHERE review_id > {$this->_notice['reviews']['id_src']}"
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

    public function importReview($data, $review, $reviewsExt) {
        if (LitExtension_CartMigration_Model_Custom::REVIEW_IMPORT) {
            return $this->_custom->importReviewCustom($this, $data, $review, $reviewsExt);
        }
        $id_src = $this->getReviewId($review, $reviewsExt);
        $reviewIpt = $this->review($data, $this->_notice['extend']['rating']);
        if ($reviewIpt['result'] == 'success') {
            $id_desc = $reviewIpt['mage_id'];
            $this->reviewSuccess($id_src, $id_desc);
        } else {
            $reviewIpt['result'] = 'warning';
            $msg = "Review Id = {$id_src} import failed. Error: " . $reviewIpt['msg'];
            $reviewIpt['msg'] = $this->consoleWarning($msg);
        }
        return $reviewIpt;
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
                "core_store" => "SELECT * FROM _DBPRF_core_store WHERE store_id != 0",
                "currencies" => "SELECT * FROM _DBPRF_core_config_data WHERE path = 'currency/options/default'",
                "eav_entity_type" => "SELECT * FROM _DBPRF_eav_entity_type",
            ))
        ));
        if (!$default_cfg || $default_cfg['result'] != 'success') {
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && ($object['core_store'] || $object['currencies'] || $object['eav_entity_type'])) {
            $this->_notice['config']['default_lang'] = $this->_getDefaultLanguage($object['core_store']);
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['value']) ? $object['currencies']['0']['value'] : 'USD';
            foreach ($object['eav_entity_type'] as $row) {
                $this->_notice['extend'][$row['entity_type_code']] = $row['entity_type_id'];
            }
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "core_store" => "SELECT * FROM _DBPRF_core_store WHERE code != 'admin'",
                "currencies" => "SELECT * FROM _DBPRF_core_config_data WHERE path = 'currency/options/allow'",
                "sales_order_status" => "SELECT DISTINCT value FROM _DBPRF_sales_order_entity_varchar WHERE attribute_id = (SELECT attribute_id FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['order_status_history']} AND attribute_code = 'status')",
                "customer_group" => "SELECT * FROM _DBPRF_customer_group",
                "eav_attribute_set" => "SELECT * FROM _DBPRF_eav_attribute_set WHERE entity_type_id = {$this->_notice['extend']['catalog_product']}",
                "core_store_group" => "SELECT a.entity_id, b.value FROM _DBPRF_catalog_category_entity a, _DBPRF_catalog_category_entity_varchar b, _DBPRF_eav_attribute c
                                            WHERE a.level = '1'
                                            AND b.entity_id = a.entity_id
                                            AND b.attribute_id = c.attribute_id
                                            AND b.store_id = 0
                                            AND c.attribute_code = 'name'
                                            AND c.entity_type_id = {$this->_notice['extend']['catalog_category']}"
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = $attribute_group_data = array();
        //$category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach ($obj['core_store'] as $language_row) {
            $lang_id = $language_row['store_id'];
            $lang_name = $language_row['name'] . "(" . $language_row['code'] . ")";
            $language_data[$lang_id] = $lang_name;
        }
        $currencies = explode(',', $obj['currencies'][0]['value']);
        foreach ($currencies as $currency_row) {
            $currency_id = $currency_row;
            $currency_name = $currency_row;
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['sales_order_status'] as $order_status_row) {
            $order_status_id = $order_status_row['value'];
            $order_status_name = ucwords(str_replace("_", " ", $order_status_row['value']));
            $order_status_data[$order_status_id] = $order_status_name;
        }
        foreach($obj['customer_group'] as $cus_status_row){
            $cus_status_id = $cus_status_row['customer_group_id'];
            $cus_status_name = $cus_status_row['customer_group_code'];
            $customer_group_data[$cus_status_id] = $cus_status_name;
        }
        foreach ($obj['eav_attribute_set'] as $attr_grp_row) {
            $attr_grp_id = $attr_grp_row['attribute_set_id'];
            $attr_grp_name = $attr_grp_row['attribute_set_name'];
            $attribute_group_data[$attr_grp_id] = $attr_grp_name;
        }
        foreach ($obj['core_store_group'] as $store) {
            $cat_id = $store['entity_id'];
            $cat_name = $store['value'];
            $category_data[$cat_id] = $cat_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_group_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['customer_group_data'] = $customer_group_data;
        //$this->_notice['config']['config_support']['customer_group_map'] = false;
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_calculation_rule WHERE tax_calculation_rule_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_eav_attribute as ea 
                                                LEFT JOIN _DBPRF_eav_attribute_option as eao ON ea.attribute_id = eao.attribute_id
                                                WHERE attribute_code = 'manufacturer' AND eao.option_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_catalog_category_entity WHERE entity_id > {$this->_notice['categories']['id_src']} AND level > 1",
                'products' => "SELECT COUNT( 1 ) FROM _DBPRF_catalog_product_entity WHERE entity_id NOT IN 
                    (SELECT product_id FROM _DBPRF_catalog_product_super_link UNION SELECT product_id FROM _DBPRF_catalog_product_bundle_selection UNION SELECT linked_product_id FROM catalog_product_link WHERE link_type_id = 3) AND entity_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer_entity WHERE entity_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_sales_order WHERE entity_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_review WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
            'query' => "SELECT dcr.* FROM _DBPRF_core_config_data as ccd LEFT JOIN _DBPRF_directory_currency_rate as dcr ON ccd.value = dcr.currency_from WHERE ccd.path = 'currency/options/base'"
        ));
        if ($currencies && $currencies['result'] == 'success') {
            $data = array();
            foreach ($currencies['object'] as $currency) {
                $currency_id = $currency['currency_to'];
                $currency_value = $currency['rate'];
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
    }

    /**
     * Query for get data of table convert to tax rule
     *
     * @return string
     */
    protected function _getTaxesMainQuery() {
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = "SELECT * FROM _DBPRF_tax_calculation_rule WHERE tax_calculation_rule_id > {$id_src} ORDER BY tax_calculation_rule_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes) {
        $taxRuleIds = $this->duplicateFieldValueFromList($taxes['object'], 'tax_calculation_rule_id');
        $tax_rule_id_con = $this->arrayToInCondition($taxRuleIds);
        $ext_query = array(
            'tax_calculation' => "SELECT * FROM _DBPRF_tax_calculation WHERE tax_calculation_rule_id IN {$tax_rule_id_con}",
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
        $taxRateIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_calculation'], 'tax_calculation_rate_id');
        $cusIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_calculation'], 'customer_tax_class_id');
        $proIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_calculation'], 'product_tax_class_id');
        $taxClassIds = array_merge($cusIds, $proIds);
        $tax_rate_id = $this->arrayToInCondition($taxRateIds);
        $tax_class_id = $this->arrayToInCondition($taxClassIds);
        $ext_rel_query = array(
            'tax_calculation_rate' => "SELECT * FROM _DBPRF_tax_calculation_rate WHERE tax_calculation_rate_id IN {$tax_rate_id}",
            'tax_class' => "SELECT * FROM _DBPRF_tax_class WHERE class_id IN {$tax_class_id}",
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
    public function getTaxId($tax, $taxesExt) {
        return $tax['tax_calculation_rule_id'];
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
        $tax_cus_mage = $tax_pro_mage = $tax_rate_ids = array();
        $taxRule = $this->getListFromListByField($taxesExt['object']['tax_calculation'], 'tax_calculation_rule_id', $tax['tax_calculation_rule_id']);
        $tax_cus_ids = $this->duplicateFieldValueFromList($taxRule, 'customer_tax_class_id');
        foreach ($tax_cus_ids as $tax_customer) {
            $cus_name = $this->getRowValueFromListByField($taxesExt['object']['tax_class'], 'class_id', $tax_customer, 'class_name');
            $cus_class = array(
                'class_name' => $cus_name
            );
            $cus_id = $this->_process->taxCustomer($cus_class);
            if ($cus_id['result'] == 'success') {
                $tax_cus_mage[] = $cus_id['mage_id'];
                $this->taxCustomerSuccess($tax_customer, $cus_id['mage_id']);
            }
        }
        $tax_pro_ids = $this->duplicateFieldValueFromList($taxRule, 'product_tax_class_id');
        foreach ($tax_pro_ids as $tax_product) {
            $pro_name = $this->getRowValueFromListByField($taxesExt['object']['tax_class'], 'class_id', $tax_product, 'class_name');
            $pro_class = array(
                'class_name' => $pro_name
            );
            $pro_id = $this->_process->taxProduct($pro_class);
            if ($pro_id['result'] == 'success') {
                $tax_pro_mage[] = $pro_id['mage_id'];
                $this->taxProductSuccess($tax_product, $pro_id['mage_id']);
            }
        }
        $taxRates = $this->duplicateFieldValueFromList($taxRule, 'tax_calculation_rate_id');
        foreach ($taxRates as $row) {
            $tax_rate = $this->getRowFromListByField($taxesExt['object']['tax_calculation_rate'], 'tax_calculation_rate_id', $row);
            $tax_rate_data = array();
            $tax_rate_data['code'] = $tax_rate['code'];
            $tax_rate_data['tax_country_id'] = $tax_rate['tax_country_id'];
            $tax_rate_data['tax_region_id'] = $tax_rate['tax_region_id'];
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = $tax_rate['tax_postcode'];
            $tax_rate_data['rate'] = $tax_rate['rate'];
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if ($tax_rate_ipt['result'] == 'success') {
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $tax['code'];
        $tax_rule_data['tax_customer_class'] = $tax_cus_mage;
        $tax_rule_data['tax_product_class'] = $tax_pro_mage;
        $tax_rule_data['tax_rate'] = $tax_rate_ids;
        $tax_rule_data['priority'] = $tax['priority'];
        $tax_rule_data['position'] = $tax['position'];
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
        $query = "SELECT eao.* FROM _DBPRF_eav_attribute as ea 
                                                LEFT JOIN _DBPRF_eav_attribute_option as eao ON ea.attribute_id = eao.attribute_id
                                                WHERE attribute_code = 'manufacturer' AND eao.option_id > {$id_src} ORDER BY eao.option_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @return array
     */
    protected function _getManufacturersExtQuery($manufacturers) {
        $manuOptIds = $this->duplicateFieldValueFromList($manufacturers['object'], 'option_id');
        $option_id = $this->arrayToInCondition($manuOptIds);
        $ext_query = array(
            'eav_attribute_option_value' => "SELECT * FROM _DBPRF_eav_attribute_option_value WHERE option_id IN {$option_id}",
        );
        return $ext_query;
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
        return $manufacturer['option_id'];
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
        $manu_names = $this->getListFromListByField($manufacturersExt['object']['eav_attribute_option_value'], 'option_id', $manufacturer['option_id']);
        $manu_store = array();
        foreach ($manu_names as $name) {
            $manu_store[$name['store_id']] = $name['value'];
        }
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id
        );
        $manufacturer_data['value']['option'] = array(
            0 => $manu_store['0']
        );
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $manufacturer_data['value']['option'][$store_id] = isset($manu_store[$lang_id]) ? $manu_store[$lang_id] : '';
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
        $query = "SELECT * FROM _DBPRF_catalog_category_entity WHERE entity_id > {$id_src} AND level > 1 ORDER BY entity_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories) {
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'entity_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'catalog_category_entity_datetime' => "SELECT * FROM _DBPRF_catalog_category_entity_datetime WHERE entity_id IN {$cat_id_con}",
            'catalog_category_entity_decimal' => "SELECT * FROM _DBPRF_catalog_category_entity_decimal WHERE entity_id IN {$cat_id_con}",
            'catalog_category_entity_int' => "SELECT * FROM _DBPRF_catalog_category_entity_int WHERE entity_id IN {$cat_id_con}",
            'catalog_category_entity_text' => "SELECT * FROM _DBPRF_catalog_category_entity_text WHERE entity_id IN {$cat_id_con}",
            'catalog_category_entity_varchar' => "SELECT * FROM _DBPRF_catalog_category_entity_varchar WHERE entity_id IN {$cat_id_con}",
            'eav_attribute' => "SELECT * FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['catalog_category']}",
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
        return $category['entity_id'];
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
        $parent_id = $this->_getCategoryParentId($category['path']);
        if ($category['level'] == 2) {
            if (isset($this->_notice['config']['cats'][$parent_id])) {
                $cat_parent_id = $this->_notice['config']['cats'][$parent_id];
            } else {
                $cat_parent_id = $this->_notice['config']['root_category_id'];
            }
        } else {
            $cat_parent_id = $this->getMageIdCategory($parent_id);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($parent_id);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['entity_id']} import failed. Error: Could not import parent category id = {$parent_id}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $attribute = array();
        foreach ($categoriesExt['object']['eav_attribute'] as $row) {
            $attribute[$row['attribute_code']] = $row['attribute_id'];
        }
        $cat_data = array();
        $varchar = $this->getListFromListByField($categoriesExt['object']['catalog_category_entity_varchar'], 'entity_id', $category['entity_id']);
        $text = $this->getListFromListByField($categoriesExt['object']['catalog_category_entity_text'], 'entity_id', $category['entity_id']);
        $int = $this->getListFromListByField($categoriesExt['object']['catalog_category_entity_int'], 'entity_id', $category['entity_id']);
        $decimal = $this->getListFromListByField($categoriesExt['object']['catalog_category_entity_decimal'], 'entity_id', $category['entity_id']);
        $datetime = $this->getListFromListByField($categoriesExt['object']['catalog_category_entity_datetime'], 'entity_id', $category['entity_id']);
        $names = $this->getListFromListByField($varchar, 'attribute_id', $attribute['name']);
        $cat_data['name'] = $this->getRowValueFromListByField($names, 'store_id', '0', 'value');
        $descriptions = $this->getListFromListByField($text, 'attribute_id', $attribute['description']);
        $cat_data['description'] = $this->getRowValueFromListByField($descriptions, 'store_id', '0', 'value');
        $is_actives = $this->getListFromListByField($int, 'attribute_id', $attribute['is_active']);
        $cat_data['is_active'] = $this->getRowValueFromListByField($is_actives, 'store_id', '0', 'value');
        $meta_titles = $this->getListFromListByField($varchar, 'attribute_id', $attribute['meta_title']);
        $cat_data['meta_title'] = $this->getRowValueFromListByField($meta_titles, 'store_id', '0', 'value');
        $meta_keywords = $this->getListFromListByField($text, 'attribute_id', $attribute['meta_keywords']);
        $cat_data['meta_keywords'] = $this->getRowValueFromListByField($meta_keywords, 'store_id', '0', 'value');
        $meta_descriptions = $this->getListFromListByField($text, 'attribute_id', $attribute['meta_description']);
        $cat_data['meta_description'] = $this->getRowValueFromListByField($meta_descriptions, 'store_id', '0', 'value');
		$url_store = $this->getListFromListByField($varchar, 'attribute_id', $attribute['url_key']);
        $cat_data['url_key'] = $this->getRowValueFromListByField($url_store, 'store_id', '0', 'value');
        $images = $this->getListFromListByField($varchar, 'attribute_id', $attribute['image']);
        $image_path = $this->getRowValueFromListByField($images, 'store_id', '0', 'value');
        if ($image_path && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $image_path, 'catalog/category')) {
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_anchor'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['is_anchor'], 'value');
        $cat_data['include_in_menu'] = 1;
        $display_modes = $this->getListFromListByField($varchar, 'attribute_id', $attribute['display_mode']);
        $cat_data['display_mode'] = $this->getRowValueFromListByField($display_modes, 'store_id', '0', 'value');
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $store_data = array();
            $store_data['store_id'] = $store_id;
            $store_data['name'] = $this->getRowValueFromListByField($names, 'store_id', $lang_id, 'value');
            $store_data['description'] = $this->getRowValueFromListByField($descriptions, 'store_id', $lang_id, 'value');
            $store_data['is_active'] = $this->getRowValueFromListByField($is_actives, 'store_id', $lang_id, 'value');
            $store_data['meta_title'] = $this->getRowValueFromListByField($meta_titles, 'store_id', $lang_id, 'value');
            $store_data['meta_keywords'] = $this->getRowValueFromListByField($meta_keywords, 'store_id', $lang_id, 'value');
            $store_data['meta_description'] = $this->getRowValueFromListByField($meta_descriptions, 'store_id', $lang_id, 'value');
            $store_data['image'] = $this->getRowValueFromListByField($images, 'store_id', $lang_id, 'value');
            $store_data['display_mode'] = $this->getRowValueFromListByField($display_modes, 'store_id', $lang_id, 'value');
            $multi_store[] = $store_data;
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
        /*$data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "customer_group" => "SELECT * FROM _DBPRF_customer_group",
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $obj = $data['object'];
        foreach ($obj['customer_group'] as $group_row) {
            $group_import = $this->_importCustomerGroup($group_row);
            if ($group_import['result'] == 'success') {
                $this->_taxCustomerGroupSuccess($group_row['customer_group_id'], $group_import['mage_id']);
            }
        }*/
    }

    /**
     * Query for get data of main table use for import product
     *
     * @return string
     */
    protected function _getProductsMainQuery() {
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT * FROM _DBPRF_catalog_product_entity WHERE entity_id NOT IN 
                    (SELECT product_id FROM _DBPRF_catalog_product_super_link UNION SELECT product_id FROM _DBPRF_catalog_product_bundle_selection UNION SELECT linked_product_id FROM catalog_product_link WHERE link_type_id = 3) AND entity_id > {$id_src} ORDER BY entity_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import product
     *
     * @param array $products : Data of function getProductsMain
     * @return array : Response of connector
     */
    public function getProductsExt($products) {
        $productsExt = array(
            'result' => 'success'
        );
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'entity_id');
        $pro_id_query = $this->arrayToInCondition($productIds);
        if  ($this->_convertVersionMagento($this->_notice['config']['cart_version'], 2) == 140) {
            $eav_query = "SELECT ea.*, cea.* FROM _DBPRF_eav_attribute ea LEFT JOIN _DBPRF_catalog_eav_attribute cea ON ea.attribute_id = cea.attribute_id WHERE ea.entity_type_id = {$this->_notice['extend']['catalog_product']}";
        } else {
            $eav_query = "SELECT * FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['catalog_product']}";
        }
        $ext_query = array(
            'catalog_product_super_attribute' => "SELECT * FROM _DBPRF_catalog_product_super_attribute WHERE product_id IN {$pro_id_query}",
            'eav_attribute' => $eav_query,
            'catalog_product_bundle_option' => "SELECT * FROM _DBPRF_catalog_product_bundle_option WHERE parent_id IN {$pro_id_query}",
            'catalog_product_bundle_selection' => "SELECT * FROM _DBPRF_catalog_product_bundle_selection WHERE parent_product_id IN {$pro_id_query}",
            'catalog_product_super_link' => "SELECT * FROM _DBPRF_catalog_product_super_link WHERE parent_id IN {$pro_id_query}",
            'tag_relation' => "SELECT * FROM _DBPRF_tag_relation WHERE product_id IN {$pro_id_query}",
            'catalog_product_link' => "SELECT * FROM _DBPRF_catalog_product_link WHERE product_id IN {$pro_id_query} OR linked_product_id IN {$pro_id_query}",
        );
        if ($this->_seo) {
            $seo_ext_query = $this->_seo->getProductsExtQuery($this, $products);
            if ($seo_ext_query) {
                $ext_query = array_merge($ext_query, $seo_ext_query);
            }
        }
        $cus_ext_query = $this->_custom->getProductsExtQueryCustom($this, $products);
        if ($cus_ext_query) {
            $ext_query = array_merge($ext_query, $cus_ext_query);
        }
        $productsExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize($ext_query)
        ));
        if (!$productsExt || $productsExt['result'] != 'success') {
            return $this->errorConnector(true);
        }
        $productBunldeChildIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_bundle_selection'], 'product_id');
        $productConfigChildIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_super_link'], 'product_id');
        $productGroupChild = $this->getListFromListByField($productsExt['object']['catalog_product_link'], 'link_type_id', '3');
        $productGroupChildIds = $this->duplicateFieldValueFromList($productGroupChild, 'linked_product_id');
        $productChildIds = array_merge($productBunldeChildIds, $productConfigChildIds, $productGroupChildIds);
        $pro_child_id = $this->arrayToInCondition($productChildIds);
        $productAllIds = array_merge($productIds, $productChildIds);
        $superAttrIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_super_attribute'], 'product_super_attribute_id');
        $pro_id_con = $this->arrayToInCondition($productAllIds);
        $super_attr_id = $this->arrayToInCondition($superAttrIds);
        $attrIds = $this->duplicateFieldValueFromList($productsExt['object']['eav_attribute'], 'attribute_id');
        $attr_id = $this->arrayToInCondition($attrIds);
        $bundleOptIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_bundle_option'], 'option_id');
        $bundle_opt_id = $this->arrayToInCondition($bundleOptIds);
        $tagIds = $this->duplicateFieldValueFromList($productsExt['object']['tag_relation'], 'tag_id');
        $tag_ids_query = $this->arrayToInCondition($tagIds);
        $ext_rel_query = array(
            'catalog_product_entity' => "SELECT * FROM _DBPRF_catalog_product_entity WHERE entity_id IN {$pro_child_id}",
            'catalog_product_entity_datetime' => "SELECT * FROM _DBPRF_catalog_product_entity_datetime WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_decimal' => "SELECT * FROM _DBPRF_catalog_product_entity_decimal WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_int' => "SELECT * FROM _DBPRF_catalog_product_entity_int WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_text' => "SELECT * FROM _DBPRF_catalog_product_entity_text WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_varchar' => "SELECT * FROM _DBPRF_catalog_product_entity_varchar WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_gallery' => "SELECT * FROM _DBPRF_catalog_product_entity_gallery WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_media_gallery' => "SELECT * FROM _DBPRF_catalog_product_entity_media_gallery WHERE entity_id IN {$pro_id_con}",
            'catalog_product_entity_tier_price' => "SELECT * FROM _DBPRF_catalog_product_entity_tier_price WHERE entity_id IN {$pro_id_con}",
            'catalog_product_option' => "SELECT * FROM _DBPRF_catalog_product_option WHERE product_id IN {$pro_id_con}",
            'catalog_category_product' => "SELECT * FROM _DBPRF_catalog_category_product WHERE product_id IN {$pro_id_con}",
            'catalog_product_super_attribute_label' => "SELECT * FROM _DBPRF_catalog_product_super_attribute_label WHERE product_super_attribute_id IN {$super_attr_id}",
            'catalog_product_super_attribute_pricing' => "SELECT * FROM _DBPRF_catalog_product_super_attribute_pricing WHERE product_super_attribute_id IN {$super_attr_id}",
            'catalog_product_bundle_option_value' => "SELECT * FROM _DBPRF_catalog_product_bundle_option_value WHERE option_id IN {$bundle_opt_id}",
            'cataloginventory_stock_item' => "SELECT * FROM _DBPRF_cataloginventory_stock_item WHERE product_id IN {$pro_id_con}",
            'tag' => "SELECT * FROM _DBPRF_tag WHERE tag_id IN {$tag_ids_query}",
            'eav_entity_attribute' => "SELECT * FROM _DBPRF_eav_entity_attribute WHERE attribute_id IN {$attr_id} AND entity_type_id = {$this->_notice['extend']['catalog_product']}",
            'catalog_product_website' => "SELECT * FROM _DBPRF_catalog_product_website WHERE product_id IN {$pro_id_con}",
            'core_store' => "SELECT * FROM _DBPRF_core_store WHERE code != 'admin'",
        );
        if ($this->_seo) {
            $seo_ext_rel_query = $this->_seo->getProductsExtRelQuery($this, $products, $productsExt);
            if ($seo_ext_rel_query) {
                $ext_rel_query = array_merge($ext_rel_query, $seo_ext_rel_query);
            }
        }
        $cus_ext_rel_query = $this->_custom->getProductsExtRelQueryCustom($this, $products, $productsExt);
        if ($cus_ext_rel_query) {
            $ext_rel_query = array_merge($ext_rel_query, $cus_ext_rel_query);
        }
        $productsExtRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize($ext_rel_query)
        ));
        if (!$productsExtRel || $productsExtRel['result'] != 'success') {
            return $this->errorConnector(true);
        }
        $productsExt = $this->_syncResultQuery($productsExt, $productsExtRel);
        /*
         * Product_Ext_Rel_Rel
         */
        $optionAttrIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_entity_int'], 'value');
        $option_attr_id = $this->arrayToInCondition($optionAttrIds);
        $galleryIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_entity_media_gallery'], 'value_id');
        $gallery_value_id = $this->arrayToInCondition($galleryIds);
        $optionCusIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_option'], 'option_id');
        $option_cus_id = $this->arrayToInCondition($optionCusIds);
        $attrGroupIds = $this->duplicateFieldValueFromList($productsExt['object']['eav_entity_attribute'], 'attribute_group_id');
        $attr_group_id = $this->arrayToInCondition($attrGroupIds);
        //Addition
        $multi = $this->getListFromListByField($productsExt['object']['eav_attribute'], 'frontend_input', 'multiselect');
        $multi_ids = $this->duplicateFieldValueFromList($multi, 'attribute_id');
        $all_option = array();
        if ($multi_ids) {
            $multi_opt = $this->getListFromListByListField($productsExt['object']['catalog_product_entity_varchar'], 'attribute_id', $multi_ids);
            foreach ($multi_opt as $row) {
                $new_options = explode(',', $row['value']);
                $all_option = array_merge($all_option, $new_options);
            }
        }
        $all_option_query = $this->arrayToInCondition($all_option);
        $ext_rel_rel_query = array(
            'eav_attribute_group' => "SELECT * FROM _DBPRF_eav_attribute_group WHERE attribute_group_id IN {$attr_group_id}",
            'eav_attribute_option_value' => "SELECT * FROM _DBPRF_eav_attribute_option_value WHERE option_id IN {$option_attr_id} OR option_id IN {$all_option_query}",
            'catalog_product_entity_media_gallery_value' => "SELECT * FROM _DBPRF_catalog_product_entity_media_gallery_value WHERE value_id IN {$gallery_value_id}",
            'catalog_product_option_title' => "SELECT * FROM _DBPRF_catalog_product_option_title WHERE option_id IN {$option_cus_id}",
            'catalog_product_option_price' => "SELECT * FROM _DBPRF_catalog_product_option_price WHERE option_id IN {$option_cus_id}",
            'catalog_product_option_type_value' => "SELECT a.*, b.*, c.price, c.price_type FROM _DBPRF_catalog_product_option_type_value as a 
                                                                                            LEFT JOIN _DBPRF_catalog_product_option_type_title as b ON a.option_type_id = b.option_type_id
                                                                                            LEFT JOIN _DBPRF_catalog_product_option_type_price as c ON b.option_type_id = c.option_type_id AND b.store_id = c.store_id
                                                                                            WHERE a.option_id IN {$option_cus_id}
                                                                                            ",
        );
        $productsExtRelRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize($ext_rel_rel_query)
        ));
        if (!$productsExtRelRel || $productsExtRelRel['result'] != 'success') {
            return $this->errorConnector(true);
        }
        $productsExt = $this->_syncResultQuery($productsExt, $productsExtRelRel);
        return $productsExt;
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return int
     */
    public function getProductId($product, $productsExt) {
        return $product['entity_id'];
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
        $result['type_id'] = $product['type_id'];
        if ($result['type_id'] == 'configurable') {
            $children = $this->getListFromListByField($productsExt['object']['catalog_product_super_link'], 'parent_id', $product['entity_id']);
            $children = $this->duplicateFieldValueFromList($children, 'product_id');
            if ($children) {
                $config_data = $this->_importChildrenConfigurableProduct($product, $children, $productsExt);
                if (!$config_data) {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Product ID = {$product['entity_id']} import failed. Error: Product's attribute ccould not create!"),
                    );
                }
                $result = array_merge($config_data, $result);
            }
        } elseif ($result['type_id'] == 'bundle') {
            $children = $this->getListFromListByField($productsExt['object']['catalog_product_bundle_selection'], 'parent_product_id', $product['entity_id']);
            $children = $this->duplicateFieldValueFromList($children, 'product_id');
            if ($children) {
                $bundle_data = $this->_importChildrenBundleProduct($product['entity_id'], $children, $productsExt);
                if (!$bundle_data) {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Product ID = {$product['entity_id']} import failed. Error: Product's child could not create!"),
                    );
                }
                $result = array_merge($bundle_data, $result);
            }
        }
        $result = array_merge($this->_convertProduct($product, $productsExt), $result);
        return array(
            'result' => 'success',
            'data' => $result
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
        $optionAll = $this->getListFromListByField($productsExt['object']['catalog_product_option'], 'product_id', $product['entity_id']);
        if ($optionAll) {
            $opt_data = array();
            foreach ($optionAll as $option) {
                $title = $this->getListFromListByField($productsExt['object']['catalog_product_option_title'], 'option_id', $option['option_id']);
                $optionIpt = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($option['type']),
                    'type' => $option['type'],
                    'is_require' => intval($option['is_require']),
                    'title' => $this->getRowValueFromListByField($title, 'store_id', '0', 'title'),
                    'sort_order' => $option['sort_order']
                );
                $values = array();
                $price = $this->getRowFromListByField($productsExt['object']['catalog_product_option_price'], 'option_id', $option['option_id']);
                $types = $this->getListFromListByField($productsExt['object']['catalog_product_option_type_value'], 'option_id', $option['option_id']);
                $types_default = $this->getListFromListByField($types, 'store_id', '0');
                if ($price) {
                    $optionIpt['price'] = $price['price'];
                    $optionIpt['price_type'] = $price['price_type'];
                    $optionIpt['sku'] = ($option['sku']) ? $option['sku'] : '';
                } elseif ($types_default) {
                    foreach ($types_default as $row) {
                        $value = array(
                            'option_type_id' => -1,
                            'title' => $row['title'],
                            'price' => $row['price'],
                            'price_type' => $row['price_type'],
                            'sku' => ($row['sku']) ? $row['sku'] : '',
                            'sort_order' => $row['sort_order']
                        );
                        $values[] = $value;
                    }
                }
                $optionIpt['values'] = ($values) ? $values : '';
                $opt_data[] = $optionIpt;
            }
            $this->importProductOption($product_mage_id, $opt_data);
        }
        if ($product['type_id'] == 'grouped') {
            $group = array();
            $relate_products = $this->getListFromListByField($productsExt['object']['catalog_product_link'], 'product_id', $product['entity_id']);
            $child_products = $this->getListFromListByField($relate_products, 'link_type_id', '3');
            foreach ($child_products as $child) {
                $check_imported = $this->getMageIdProduct($child['linked_product_id']);
                if ($check_imported) {
                    $group[] = $check_imported;
                } else {
                    $child_info = $this->getRowFromListByField($productsExt['object']['catalog_product_entity'], 'entity_id', $child['linked_product_id']);
                    $product_baby = array(
                        'type_id' => $child_info['type_id']
                    );
                    $product_baby = array_merge($this->_convertProduct($child_info, $productsExt), $product_baby);
                    $pro_import = $this->_process->product($product_baby);
                    if ($pro_import['result'] !== 'success') {
                        return false;
                    }
                    $this->productSuccess($child['linked_product_id'], $pro_import['mage_id']);
                    $group[] = $pro_import['mage_id'];
                }
            }
            $products_links = Mage::getModel('catalog/product_link_api');
            foreach ($group as $value) {
                $products_links->assign("grouped", $product_mage_id, $value);
            }
        }
        // Related product
        $linkProducts = $this->getListFromListByField($productsExt['object']['catalog_product_link'], 'product_id', $product['entity_id']);
        if ($linkProducts) {
            $relateProducts = $this->getListFromListByField($linkProducts, 'link_type_id', '1');
            $upsellProducts = $this->getListFromListByField($linkProducts, 'link_type_id', '4');
            $crosssellProducts = $this->getListFromListByField($linkProducts, 'link_type_id', '5');
            if ($relateProducts) {
                $relate_products = $this->duplicateFieldValueFromList($relateProducts, 'linked_product_id');
                $this->setProductRelation($product_mage_id, $relate_products, 1);
            }
            if ($upsellProducts) {
                $upsell_products = $this->duplicateFieldValueFromList($upsellProducts, 'linked_product_id');
                $this->setProductRelation($product_mage_id, $upsell_products, 4);
            }
            if ($crosssellProducts) {
                $crosssell_product = $this->duplicateFieldValueFromList($crosssellProducts, 'linked_product_id');
                $this->setProductRelation($product_mage_id, $crosssell_product, 5);
            }
        }
        //Revert
        $linkRProducts = $this->getListFromListByField($productsExt['object']['catalog_product_link'], 'linked_product_id', $product['entity_id']);
        if ($linkRProducts) {
            $relateProducts = $this->getListFromListByField($linkRProducts, 'link_type_id', '1');
            $upsellProducts = $this->getListFromListByField($linkRProducts, 'link_type_id', '4');
            $crosssellProducts = $this->getListFromListByField($linkRProducts, 'link_type_id', '5');
            if ($relateProducts) {
                $relate_products = $this->duplicateFieldValueFromList($relateProducts, 'product_id');
                $this->setProductRelation($relate_products, $product_mage_id, 1);
            }
            if ($upsellProducts) {
                $upsell_products = $this->duplicateFieldValueFromList($upsellProducts, 'product_id');
                $this->setProductRelation($upsell_products, $product_mage_id, 4);
            }
            if ($crosssellProducts) {
                $crosssell_product = $this->duplicateFieldValueFromList($crosssellProducts, 'product_id');
                $this->setProductRelation($crosssell_product, $product_mage_id, 5);
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
        $query = "SELECT * FROM _DBPRF_customer_entity WHERE entity_id > {$id_src} ORDER BY entity_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers) {
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'entity_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $groupIds = $this->duplicateFieldValueFromList($customers['object'], 'group_id');
        $group_cus_id = $this->arrayToInCondition($groupIds);
        $websiteIds = $this->duplicateFieldValueFromList($customers['object'], 'website_id');
        $web_ids = $this->arrayToInCondition($websiteIds);
        $ext_query = array(
            'core_store' => "SELECT * FROM _DBPRF_core_store WHERE code != 'admin' AND website_id IN {$web_ids}",
            'eav_attribute' => "SELECT * FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['customer']} OR entity_type_id = {$this->_notice['extend']['customer_address']}",
            'customer_entity_datetime' => "SELECT * FROM _DBPRF_customer_entity_datetime WHERE entity_id IN {$customer_ids_query}",
            'customer_entity_decimal' => "SELECT * FROM _DBPRF_customer_entity_decimal WHERE entity_id IN {$customer_ids_query}",
            'customer_entity_int' => "SELECT * FROM _DBPRF_customer_entity_int WHERE entity_id IN {$customer_ids_query}",
            'customer_entity_text' => "SELECT * FROM _DBPRF_customer_entity_text WHERE entity_id IN {$customer_ids_query}",
            'customer_entity_varchar' => "SELECT * FROM _DBPRF_customer_entity_varchar WHERE entity_id IN {$customer_ids_query}",
            'customer_address_entity' => "SELECT * FROM _DBPRF_customer_address_entity WHERE parent_id IN {$customer_ids_query}",
            'newsletter_subscriber' => "SELECT * FROM _DBPRF_newsletter_subscriber WHERE customer_id IN {$customer_ids_query}",
            'customer_group' => "SELECT * FROM _DBPRF_customer_group WHERE customer_group_id IN {$group_cus_id}",
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
        $addressIds = $this->duplicateFieldValueFromList($customersExt['object']['customer_address_entity'], 'entity_id');
        $address_id = $this->arrayToInCondition($addressIds);
        $ext_rel_query = array(
            'customer_address_entity_datetime' => "SELECT * FROM _DBPRF_customer_address_entity_datetime WHERE entity_id IN {$address_id}",
            'customer_address_entity_decimal' => "SELECT * FROM _DBPRF_customer_address_entity_decimal WHERE entity_id IN {$address_id}",
            'customer_address_entity_int' => "SELECT * FROM _DBPRF_customer_address_entity_int WHERE entity_id IN {$address_id}",
            'customer_address_entity_text' => "SELECT * FROM _DBPRF_customer_address_entity_text WHERE entity_id IN {$address_id}",
            'customer_address_entity_varchar' => "SELECT * FROM _DBPRF_customer_address_entity_varchar WHERE entity_id IN {$address_id}",
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
        return $customer['entity_id'];
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
        $attribute = array();
        foreach ($customersExt['object']['eav_attribute'] as $row) {
            if ($row['entity_type_id'] == $this->_notice['extend']['customer']) {
                $attribute[$row['attribute_code']] = $row['attribute_id'];
            }
        }
        $varchar = $this->getListFromListByField($customersExt['object']['customer_entity_varchar'], 'entity_id', $customer['entity_id']);
        $text = $this->getListFromListByField($customersExt['object']['customer_entity_text'], 'entity_id', $customer['entity_id']);
        $int = $this->getListFromListByField($customersExt['object']['customer_entity_int'], 'entity_id', $customer['entity_id']);
        $decimal = $this->getListFromListByField($customersExt['object']['customer_entity_decimal'], 'entity_id', $customer['entity_id']);
        $datetime = $this->getListFromListByField($customersExt['object']['customer_entity_datetime'], 'entity_id', $customer['entity_id']);
        if ($this->_notice['config']['add_option']['pre_cus']) {
            $cus_data['id'] = $customer['entity_id'];
        }
        $store_id = $this->getRowValueFromListByField($customersExt['object']['core_store'], 'website_id', $customer['website_id'], 'store_id');
        $cus_data['website_id'] = $store_id && isset($this->_notice['config']['languages'][$store_id]) ? $this->getWebsiteIdByStoreId($this->_notice['config']['languages'][$store_id]) : $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['is_active'] = $customer['is_active'];
        $cus_data['created_at'] = $customer['created_at'];
        $cus_data['updated_at'] = $customer['updated_at'];
        //$group_id = $this->getRowFromListByField($customersExt['object']['customer_group'], 'customer_group_id', $customer['group_id']);
        //$group_import = $this->_importCustomerGroup($group_id);
        if (isset($this->_notice['config']['customer_group'][$customer['group_id']])) {
            //$this->_taxCustomerGroupSuccess($group_id['customer_group_id'], $group_import['mage_id']);
            $cus_data['group_id'] = $this->_notice['config']['customer_group'][$customer['group_id']];
        } else {
            $cus_data['group_id'] = 1;
        }
        $subscribed = $this->getRowValueFromListByField($customersExt['object']['newsletter_subscriber'], 'customer_id', $customer['entity_id'], 'subscriber_status');
        $cus_data['is_subscribed'] = ($subscribed == '1') ? 1 : 0;
        $cus_data['prefix'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['prefix'], 'value');
        $cus_data['firstname'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['firstname'], 'value');
        $cus_data['middlename'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['middlename'], 'value');
        $cus_data['lastname'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['lastname'], 'value');
        $cus_data['suffix'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['suffix'], 'value');
        $cus_data['dob'] = $this->getRowValueFromListByField($datetime, 'attribute_id', $attribute['dob'], 'value');
        $cus_data['taxvat'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['taxvat'], 'value');
        $cus_data['password_hash'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['password_hash'], 'value');
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
        $attribute_cus = array();
        foreach ($customersExt['object']['eav_attribute'] as $row) {
            if ($row['entity_type_id'] == $this->_notice['extend']['customer']) {
                $attribute_cus[$row['attribute_code']] = $row['attribute_id'];
            }
        }
        $var_char = $this->getListFromListByField($customersExt['object']['customer_entity_varchar'], 'entity_id', $customer['entity_id']);
        $password = $this->getRowValueFromListByField($var_char, 'attribute_id', $attribute_cus['password_hash'], 'value');
        $this->_importCustomerRawPass($customer_mage_id, $password);
        $cusAdd = $this->getListFromListByField($customersExt['object']['customer_address_entity'], 'parent_id', $customer['entity_id']);
        if ($cusAdd) {
            $attribute = array();
            foreach ($customersExt['object']['eav_attribute'] as $row) {
                if ($row['entity_type_id'] == $this->_notice['extend']['customer_address']) {
                    $attribute[$row['attribute_code']] = $row['attribute_id'];
                }
            }
            $int = $this->getListFromListByField($customersExt['object']['customer_entity_int'], 'entity_id', $customer['entity_id']);
            $def_billing = $this->getRowValueFromListByField($int, 'attribute_id', $attribute_cus['default_billing'], 'value');
            $def_shipping = $this->getRowValueFromListByField($int, 'attribute_id', $attribute_cus['default_shipping'], 'value');
            foreach ($cusAdd as $row) {
                $address = array();
                $varchar = $this->getListFromListByField($customersExt['object']['customer_address_entity_varchar'], 'entity_id', $row['entity_id']);
                $text = $this->getListFromListByField($customersExt['object']['customer_address_entity_text'], 'entity_id', $row['entity_id']);
                $int = $this->getListFromListByField($customersExt['object']['customer_address_entity_int'], 'entity_id', $row['entity_id']);
                $decimal = $this->getListFromListByField($customersExt['object']['customer_address_entity_decimal'], 'entity_id', $row['entity_id']);
                $datetime = $this->getListFromListByField($customersExt['object']['customer_address_entity_datetime'], 'entity_id', $row['entity_id']);
                $address['prefix'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['prefix'], 'value');
                $address['firstname'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['firstname'], 'value');
                $address['middlename'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['middlename'], 'value');
                $address['lastname'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['lastname'], 'value');
                $address['suffix'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['suffix'], 'value');
                $address['country_id'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['country_id'], 'value');
                $address['street'] = $this->getRowValueFromListByField($text, 'attribute_id', $attribute['street'], 'value');
                $address['postcode'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['postcode'], 'value');
                $address['city'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['city'], 'value');
                $address['region'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['region'], 'value');
                $address['region_id'] = $this->getRegionId($address['region'], $address['country_id']);
                $address['telephone'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['telephone'], 'value');
                $address['company'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['company'], 'value');
                $address['fax'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['fax'], 'value');
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if ($address_ipt['result'] == 'success') {
                    $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                    if ($row['entity_id'] == $def_billing) {
                        $cus->setDefaultBilling($address_ipt['mage_id']);
                    }
                    if ($row['entity_id'] == $def_shipping) {
                        $cus->setDefaultShipping($address_ipt['mage_id']);
                    }
                    $cus->save();
                }
            }
        }
    }

    /**
     * Query for get data use for import order
     *
     * @return array : Response of connector
     */
    protected function _getOrdersMainQuery() {
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_sales_order WHERE entity_id > {$id_src} ORDER BY entity_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders) {
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'entity_id');
        $order_id_query = $this->arrayToInCondition($orderIds);
        $ext_query = array(
            'eav_attribute' => "SELECT * FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['order_address']} OR entity_type_id = {$this->_notice['extend']['order_payment']} OR entity_type_id = {$this->_notice['extend']['order_status_history']} OR entity_type_id = {$this->_notice['extend']['order']}",
            'sales_order_entity' => "SELECT * FROM _DBPRF_sales_order_entity WHERE parent_id IN {$order_id_query} ORDER BY entity_id ASC",
            'sales_flat_order_item' => "SELECT * FROM _DBPRF_sales_flat_order_item WHERE order_id IN {$order_id_query}",
            'sales_order_datetime' => "SELECT * FROM _DBPRF_sales_order_datetime WHERE entity_id IN {$order_id_query}",
            'sales_order_decimal' => "SELECT * FROM _DBPRF_sales_order_decimal WHERE entity_id IN {$order_id_query}",
            'sales_order_int' => "SELECT * FROM _DBPRF_sales_order_int WHERE entity_id IN {$order_id_query}",
            'sales_order_text' => "SELECT * FROM _DBPRF_sales_order_text WHERE entity_id IN {$order_id_query}",
            'sales_order_varchar' => "SELECT * FROM _DBPRF_sales_order_varchar WHERE entity_id IN {$order_id_query}",
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
        $orderEntityIds = $this->duplicateFieldValueFromList($ordersExt['object']['sales_order_entity'], 'entity_id');
        $entity_ids_query = $this->arrayToInCondition($orderEntityIds);
        $ext_rel_query = array(
            'sales_order_entity_datetime' => "SELECT * FROM _DBPRF_sales_order_entity_datetime WHERE entity_id IN {$entity_ids_query}",
            'sales_order_entity_decimal' => "SELECT * FROM _DBPRF_sales_order_entity_decimal WHERE entity_id IN {$entity_ids_query}",
            'sales_order_entity_int' => "SELECT * FROM _DBPRF_sales_order_entity_int WHERE entity_id IN {$entity_ids_query}",
            'sales_order_entity_text' => "SELECT * FROM _DBPRF_sales_order_entity_text WHERE entity_id IN {$entity_ids_query}",
            'sales_order_entity_varchar' => "SELECT * FROM _DBPRF_sales_order_entity_varchar WHERE entity_id IN {$entity_ids_query}",
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
        return $order['entity_id'];
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
        $attribute_address = array();
        foreach ($ordersExt['object']['eav_attribute'] as $row) {
            if ($row['entity_type_id'] == $this->_notice['extend']['order_address']) {
                $attribute_address[$row['attribute_code']] = $row['attribute_id'];
            }
        }
        $entity_ids = $this->getListFromListByField($ordersExt['object']['sales_order_entity'], 'parent_id', $order['entity_id']);
        $entity_addr = $this->getListFromListByField($entity_ids, 'entity_type_id', $this->_notice['extend']['order_address']);
        $bill_id = $entity_addr[0]['entity_id'];
        $ship_id = $entity_addr[1]['entity_id'];
        
        $varchar_bill = $this->getListFromListByField($ordersExt['object']['sales_order_entity_varchar'], 'entity_id', $bill_id);
        $text_bill = $this->getListFromListByField($ordersExt['object']['sales_order_entity_text'], 'entity_id', $bill_id);
        $int_bill = $this->getListFromListByField($ordersExt['object']['sales_order_entity_int'], 'entity_id', $bill_id);
        $decimal_bill = $this->getListFromListByField($ordersExt['object']['sales_order_entity_decimal'], 'entity_id', $bill_id);
        $datetime_bill = $this->getListFromListByField($ordersExt['object']['sales_order_entity_datetime'], 'entity_id', $bill_id);
        //$address_order = $this->getListFromListByField($ordersExt['object']['sales_flat_order_address'], 'parent_id', $order['entity_id']);
        //Billing address
        //$billing = $this->getRowFromListByField($address_order, 'address_type', 'billing');

        $address_billing['firstname'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['firstname'], 'value');
        $address_billing['middlename'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['middlename'], 'value');
        $address_billing['lastname'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['lastname'], 'value');
        $address_billing['company'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['company'], 'value');
        $address_billing['email'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['email'], 'value');
        $address_billing['street'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['street'], 'value');
        $address_billing['city'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['city'], 'value');
        $address_billing['postcode'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['postcode'], 'value');
        $address_billing['telephone'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['telephone'], 'value');
        $address_billing['country_id'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['country_id'], 'value');
        $address_billing['region'] = $this->getRowValueFromListByField($varchar_bill, 'attribute_id', $attribute_address['region'], 'value');
        $address_billing['region_id'] = $this->getRegionId($address_billing['region'], $address_billing['country_id']);

        //Shipping address
        $varchar_ship = $this->getListFromListByField($ordersExt['object']['sales_order_entity_varchar'], 'entity_id', $ship_id);
        $text_ship = $this->getListFromListByField($ordersExt['object']['sales_order_entity_text'], 'entity_id', $ship_id);
        $int_ship = $this->getListFromListByField($ordersExt['object']['sales_order_entity_int'], 'entity_id', $ship_id);
        $decimal_ship = $this->getListFromListByField($ordersExt['object']['sales_order_entity_decimal'], 'entity_id', $ship_id);
        $datetime_ship = $this->getListFromListByField($ordersExt['object']['sales_order_entity_datetime'], 'entity_id', $ship_id);
        //$shipping = $this->getRowFromListByField($address_order, 'address_type', 'shipping');
        $address_shipping['firstname'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['firstname'], 'value');
        $address_shipping['middlename'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['middlename'], 'value');
        $address_shipping['lastname'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['lastname'], 'value');
        $address_shipping['company'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['company'], 'value');
        $address_shipping['email'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['email'], 'value');
        $address_shipping['street'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['street'], 'value');
        $address_shipping['city'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['city'], 'value');
        $address_shipping['postcode'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['postcode'], 'value');
        $address_shipping['telephone'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['telephone'], 'value');
        $address_shipping['country_id'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['country_id'], 'value');
        $address_shipping['region'] = $this->getRowValueFromListByField($varchar_ship, 'attribute_id', $attribute_address['region'], 'value');
        $address_shipping['region_id'] = $this->getRegionId($address_shipping['region'], $address_shipping['country_id']);

        //Row product item
        $carts = array();
        $orderProducts = $this->getListFromListByField($ordersExt['object']['sales_flat_order_item'], 'order_id', $order['entity_id']);
        $i = 0;
        foreach ($orderProducts as $key => $row) {
            $cart = array();
            if (!$row['parent_item_id']) {
                $i = $key;
            }
            $product_id = $this->getMageIdProduct($row['product_id']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $cart['product_type'] = $row['product_type'];
            $cart['name'] = $row['name'];
            $cart['sku'] = $row['sku'];
            $cart['price'] = $row['price'];
            $cart['original_price'] = $row['original_price'];
            $cart['tax_amount'] = $row['tax_amount'];
            $cart['tax_percent'] = $row['tax_percent'];
            $cart['discount_amount'] = $row['discount_amount'];
            $cart['qty_ordered'] = $row['qty_ordered'];
            $cart['qty_canceled'] = $row['qty_canceled'];
            $cart['qty_invoiced'] = $row['qty_invoiced'];
            $cart['qty_refunded'] = $row['qty_refunded'];
            $cart['qty_shipped'] = $row['qty_shipped'];
            $cart['row_total'] = $row['row_total'];
            $cart['product_options'] = $row['product_options'];
//            $options = array();
//            if ($row['product_options']) {
//                $options = unserialize($row['product_options']);
//            }
//            if ($options) {
//                $opt_only = $attr_only = array();
//                if (isset($options['options'])) {
//                    $opt_only = array('options' => $options['options']);
//                }
//                if (isset($options['attributes_info'])) {
//                    $attr_only = array('attributes_info' => $options['attributes_info']);
//                }
//                $product_opt = serialize(array_merge($opt_only, $attr_only));
//                $cart['product_options'] = $product_opt;
//                if (isset($options['bundle_options'])) {
//                    $cart['bundle_options'] = serialize($options['bundle_options']);
//                }
//            }
            if (!$row['parent_item_id']) {
                $carts[$key] = $cart;
            } else {
                $carts[$i]['children_item'][] = $cart;
            }
        }
        $order_data = array();
        $store_id = ($order['store_id']) ? $this->_notice['config']['languages'][$order['store_id']] : $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $order_data['store_id'] = $store_id;
        //fix
        $attribute_order = array();
        foreach ($ordersExt['object']['eav_attribute'] as $row) {
            if ($row['entity_type_id'] == $this->_notice['extend']['order']) {
                $attribute_order[$row['attribute_code']] = $row['attribute_id'];
            }
        }
        $varchar_order = $this->getListFromListByField($ordersExt['object']['sales_order_varchar'], 'entity_id', $order['entity_id']);
        $text_order = $this->getListFromListByField($ordersExt['object']['sales_order_text'], 'entity_id', $order['entity_id']);
        $int_order = $this->getListFromListByField($ordersExt['object']['sales_order_int'], 'entity_id', $order['entity_id']);
        $decimal_order = $this->getListFromListByField($ordersExt['object']['sales_order_decimal'], 'entity_id', $order['entity_id']);
        $datetime_order = $this->getListFromListByField($ordersExt['object']['sales_order_datetime'], 'entity_id', $order['entity_id']);
        $customer_id = $this->getMageIdCustomer($order['customer_id']);
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['customer_email'], 'value');
        $order_data['customer_firstname'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['customer_firstname'], 'value');
        $order_data['customer_middlename'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['customer_middlename'], 'value');
        $order_data['customer_lastname'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['customer_lastname'], 'value');
        if (isset($this->_notice['config']['customer_group'][$this->getRowValueFromListByField($int_order, 'attribute_id', $attribute_order['customer_group_id'], 'value')])) {
            $order_data['customer_group_id'] = $this->_notice['config']['customer_group'][$this->getRowValueFromListByField($int_order, 'attribute_id', $attribute_order['customer_group_id'], 'value')];
        } else {
            $order_data['customer_group_id'] = 1;
        }
        $order_data['status'] = ($status = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['status'], 'value')) ? $this->_notice['config']['order_status'][$status] : $this->_notice['config']['order_status'][$order['status']];
        $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        $order_data['subtotal'] = $order['subtotal'];
        $order_data['base_subtotal'] = $order['base_subtotal'];
        $order_data['shipping_amount'] = $order['shipping_amount'];
        $order_data['base_shipping_amount'] = $order['base_shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order['base_shipping_invoiced'];
        $order_data['shipping_description'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['shipping_description'], 'value');
        $order_data['tax_amount'] = $order['tax_amount'];
        $order_data['base_tax_amount'] = $order['base_tax_amount'];
        $order_data['discount_amount'] = $order['discount_amount'];
        $order_data['base_discount_amount'] = $order['base_discount_amount'];
        $order_data['grand_total'] = $order['grand_total'];
        $order_data['base_grand_total'] = $order['base_grand_total'];
        $order_data['base_total_invoiced'] = $order['base_total_invoiced'];
        $order_data['total_paid'] = $order['total_paid'];
        $order_data['base_total_paid'] = $order['base_total_paid'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['base_currency_code'], 'value');
        $order_data['global_currency_code'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['global_currency_code'], 'value');
        $order_data['store_currency_code'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['store_currency_code'], 'value');
        $order_data['order_currency_code'] = $this->getRowValueFromListByField($varchar_order, 'attribute_id', $attribute_order['order_currency_code'], 'value');
        $order_data['created_at'] = $order['created_at'];
        $order_data['updated_at'] = $order['updated_at'];
        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['increment_id'];
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
        $attribute = array();
        foreach ($ordersExt['object']['eav_attribute'] as $row) {
            if ($row['entity_type_id'] == $this->_notice['extend']['order_status_history']) {
                $attribute[$row['attribute_code']] = $row['attribute_id'];
            }
        }
        $entity_ids = $this->getListFromListByField($ordersExt['object']['sales_order_entity'], 'parent_id', $order['entity_id']);
        $entity_status = $this->getListFromListByField($entity_ids, 'entity_type_id', $this->_notice['extend']['order_status_history']);
        
        foreach ($entity_status as $row) {
//            $varchar = $this->getListFromListByField($ordersExt['object']['sales_order_entity_varchar'], 'entity_id', $bill_id);
//            $text = $this->getListFromListByField($ordersExt['object']['sales_order_entity_text'], 'entity_id', $bill_id);
//            $int = $this->getListFromListByField($ordersExt['object']['sales_order_entity_int'], 'entity_id', $bill_id);
//            $decimal = $this->getListFromListByField($ordersExt['object']['sales_order_entity_decimal'], 'entity_id', $bill_id);
//            $datetime = $this->getListFromListByField($ordersExt['object']['sales_order_entity_datetime'], 'entity_id', $bill_id);
            $order_status_data = array();
            $order_status_data['status'] = $this->_notice['config']['order_status'][$this->getRowValueFromListByField($ordersExt['object']['sales_order_entity_varchar'], 'entity_id', $row['entity_id'], 'value')];
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            $order_status_data['comment'] = $this->getRowValueFromListByField($ordersExt['object']['sales_order_entity_text'], 'entity_id', $row['entity_id'], 'value');
            $order_status_data['is_customer_notified'] = $this->getRowValueFromListByField($ordersExt['object']['sales_order_entity_int'], 'entity_id', $row['entity_id'], 'value');
            $order_status_data['created_at'] = $row['created_at'];
            $order_status_data['updated_at'] = $row['created_at'];
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
        $query = "SELECT * FROM _DBPRF_review WHERE review_id > {$id_src} ORDER BY review_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews) {
        $reviewIds = $this->duplicateFieldValueFromList($reviews['object'], 'review_id');
        $review_id_query = $this->arrayToInCondition($reviewIds);
        $ext_query = array(
            'review_detail' => "SELECT * FROM _DBPRF_review_detail WHERE review_id IN {$review_id_query}",
            'rating_option_vote' => "SELECT * FROM _DBPRF_rating_option_vote WHERE review_id IN {$review_id_query}",
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
        return $review['review_id'];
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
        $product_mage_id = $this->getMageIdProduct($review['entity_pk_value']);
        if (!$product_mage_id) {
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['review_id']} import failed. Error: Product Id = {$review['entity_pk_value']} not imported!")
            );
        }
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $review_detail = $this->getRowFromListByField($reviewsExt['object']['review_detail'], 'review_id', $review['review_id']);
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = $review['status_id'];
        $data['title'] = $review_detail['title'];
        $data['detail'] = $review_detail['detail'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        if ($review_detail['customer_id']) {
            $data['customer_id'] = ($this->getMageIdCustomer($review_detail['customer_id'])) ? $this->getMageIdCustomer($review_detail['customer_id']) : null;
        }
        $data['nickname'] = $review_detail['nickname'];
        $rating_votes = $this->getListFromListByField($reviewsExt['object']['rating_option_vote'], 'review_id', $review['review_id']);
        $rating = array();
        if(is_array($rating_votes) && count($rating_votes)){
            foreach ($rating_votes as $row) {
                $rating[$row['rating_id']] = $row['option_id'];
            }
        }
        $data['rating'] = $rating;
        $data['created_at'] = $review['created_at'];
        $data['review_id_import'] = $review['review_id'];
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
            'query' => "SELECT * FROM _DBPRF_catalog_category_entity WHERE entity_id = {$parent_id}"
        ));
        if (!$categories || $categories['result'] != 'success') {
            return $this->errorConnector(true);
        }
        $categoriesExt = $this->getCategoriesExt($categories);
        if ($categoriesExt['result'] != 'success') {
            return $categoriesExt;
        }
        if (!$categories['object']) {
            return array(
                'result' => 'warning',
            );
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

    protected function _importChildrenConfigurableProduct($parent, $children, $productsExt) {
        $result = false;
        $attr_int = $dataChildes = $attrMage = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        //$attribute_set_id = $this->_notice['config']['attribute_set_id'];
        $attributeIds = $this->getListFromListByField($productsExt['object']['catalog_product_super_attribute'], 'product_id', $parent['entity_id']);
        $attributeIds = $this->duplicateFieldValueFromList($attributeIds, 'attribute_id');
        $used_attribute = array();
        foreach ($attributeIds as $row) {
            $attributeSets = $this->getListFromListByField($productsExt['object']['eav_entity_attribute'], 'attribute_id', $row);
            $attribute_set_src_ids = $this->duplicateFieldValueFromList($attributeSets, 'attribute_set_id');
            $attribute_set_id_desc = array($parent['attribute_set_id']);
            foreach ($attribute_set_src_ids as $attr_set) {
                if (isset($this->_notice['config']['attributes'][$attr_set])) {
                    $attribute_set_id_desc[] = $this->_notice['config']['attributes'][$attr_set];
                }
            }
            $attribute_set_id = array_unique($attribute_set_id_desc);
            //Group attr
//            $attribute_group_src_ids = $this->duplicateFieldValueFromList($attributeSets, 'attribute_group_id');
//            $attributeGroups = $this->getListFromListByListField($productsExt['object']['eav_attribute_group'], 'attribute_group_id', $attribute_group_src_ids);
//            foreach ($attributeGroups as $key => $grp) {
//                if (isset($this->_notice['config']['attributes'][$grp['attribute_set_id']])) {
//                    $attributeGroups[$key]['attribute_set_id'] = $this->_notice['config']['attributes'][$grp['attribute_set_id']];
//                } else {
//                    unset($attributeGroups[$key]);
//                }
//            }
            $attribute = $this->getRowFromListByField($productsExt['object']['eav_attribute'], 'attribute_id', $row);
            foreach ($children as $child) {
                $child_opt_vals = $this->getListFromListByField($productsExt['object']['catalog_product_entity_int'], 'entity_id', $child);
                $value = $this->getRowValueFromListByField($child_opt_vals, 'attribute_id', $row, 'value');
                $option_store = $this->getListFromListByField($productsExt['object']['eav_attribute_option_value'], 'option_id', $value);
                $option = array();
                $option_mage = array();
                foreach ($option_store as $opt) {
                    if ($opt['store_id'] == '0') {
                        $option['0'] = $opt['value'];
                        continue;
                    }
                    $option[$this->_notice['config']['languages'][$opt['store_id']]] = $opt['value'];
                }
                $option_mage['option_0'] = $option;
                $attr_import = $this->_makeAttributeImport($attribute, $option_mage, $attribute_set_id, $entity_type_id);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if (!$attr_after) {
                    var_dump($attr_import); exit();
                    return false;
                }
                $attr_after['option_label'] = $option[0];
                $opt_price = $this->getRowFromListByField($productsExt['object']['catalog_product_super_attribute_pricing'], 'value_index', $value);
                $attr_after['is_percent'] = isset($opt_price['is_percent']) ? $opt_price['is_percent'] : 0;
                $attr_after['pricing_value'] = isset($opt_price['pricing_value']) ? $opt_price['pricing_value'] : '';
                $attr_int[$child][$attr_after['attribute_id']] = $attr_after['option_ids']['option_0'];
                $attrMage[$attr_after['attribute_id']]['attribute_label'] = $attribute['frontend_label'];
                $attrMage[$attr_after['attribute_id']]['attribute_code'] = $attr_after['attribute_code'];
                $attrMage[$attr_after['attribute_id']]['values'][$attr_after['option_ids']['option_0']] = $attr_after;
            }
            $used_attribute[] = $attribute['attribute_id'];
        }
        foreach ($children as $row) {
            $check_imported = $this->getMageIdProduct($row);
            if ($check_imported) {
                $product_id = $check_imported;
            } else {
                $child_info = $this->getRowFromListByField($productsExt['object']['catalog_product_entity'], 'entity_id', $row);
                $product = array(
                    'type_id' => $child_info['type_id']
                );
                $product = array_merge($this->_convertProduct($child_info, $productsExt, $used_attribute), $product);
                $pro_import = $this->_process->product($product);
                if ($pro_import['result'] !== 'success') {
                    var_dump($row);
                    var_dump($product);
                    return false;
                }
                $this->productSuccess($row, $pro_import['mage_id']);
                $product_id = $pro_import['mage_id'];
            }
            //$this->afterSaveProduct($pro_import['mage_id'], $product, $child_info, $productsExt);
            $optionChild = $attr_int[$row];
            foreach ($optionChild as $attr => $opt_val) {
                $dataTMP['attribute_id'] = $attr;
                $dataTMP['value_index'] = $opt_val;
                $dataTMP['is_percent'] = 0;
                $dataChildes[$product_id][] = $dataTMP;
                $this->setProAttrSelect($entity_type_id, $attr, $product_id, $opt_val);
            }
        }
        if ($dataChildes && $attrMage)
            $result = $this->_createConfigProductData($dataChildes, $attrMage);
        return $result;
    }

    protected function _makeAttributeImport($attribute, $option, $attribute_set, $entity_type_id) {
        $apply = '';
        if (isset($attribute['apply_to'])) {
            $apply = explode(',', $attribute['apply_to']);
        }
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => substr($attribute['attribute_code'], 0, 30),
            'attribute_set_id' => $attribute_set,
            //'attribute_group_set' => $attribute_group_set,
            'frontend_input' => $attribute['frontend_input'],
            'frontend_label' => $attribute['frontend_label'],
            'apply_to' => $apply,
            'is_visible_on_front' => isset($attribute['is_visible_on_front']) ? $attribute['is_visible_on_front'] : 0,
            'is_global' => $attribute['is_global'],
            'is_configurable' => $attribute['is_configurable'],
            'option' => array(
                'value' => ($option) ? $option : array()
            )
        );
        $edit = array(
            'apply_to' => $apply,
            'is_visible_on_front' => $attribute['is_visible_on_front'],
            'is_global' => $attribute['is_global'],
            'is_configurable' => $attribute['is_configurable'],
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
                    'is_percent' => $option['is_percent'],
                    'pricing_value' => $option['pricing_value'],
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
        return $result;
    }

    protected function _importChildrenBundleProduct($parent_id, $children, $productsExt) {
        $optionData = $selectionData = array();
        $bundleOpts = $this->getListFromListByField($productsExt['object']['catalog_product_bundle_option'], 'parent_id', $parent_id);
        if ($bundleOpts) {
            foreach ($bundleOpts as $row) {
                $title = $this->getRowValueFromListByField($productsExt['object']['catalog_product_bundle_option_value'], 'option_id', $row['option_id'], 'title');
                $option = array(
                    'required' => $row['required'],
                    'option_id' => '',
                    'position' => $row['position'],
                    'type' => $row['type'],
                    'default_title' => $title,
                    'delete' => '',
                );
                $optionData[] = $option;
                $selections = $this->getListFromListByField($productsExt['object']['catalog_product_bundle_selection'], 'option_id', $row['option_id']);
                $group_select = array();
                foreach ($selections as $value) {
                    $check_imported = $this->getMageIdProduct($value['product_id']);
                    if ($check_imported) {
                        $product_id = $check_imported;
                    } else {
                        $child_product = $this->getRowFromListByField($productsExt['object']['catalog_product_entity'], 'entity_id', $value['product_id']);
                        $product = array(
                            'type_id' => $child_product['type_id']
                        );
                        $product = array_merge($this->_convertProduct($child_product, $productsExt), $product);
                        $pro_import = $this->_process->product($product);
                        if ($pro_import['result'] !== 'success') {
                            return false;
                        }
                        $this->productSuccess($value['product_id'], $pro_import['mage_id']);
                        $product_id = $pro_import['mage_id'];
                    }
                    //$this->afterSaveProduct($pro_import['mage_id'], $product, $child_product, $productsExt);
                    $selection = array(
                        'product_id' => $product_id,
                        'selection_qty' => $value['selection_qty'],
                        'selection_can_change_qty' => $value['selection_can_change_qty'],
                        'position' => $value['position'],
                        'is_default' => $value['is_default'],
                        'selection_id' => '',
                        'selection_price_type' => $value['selection_price_type'],
                        'selection_price_value' => $value['selection_price_value'],
                        'option_id' => '',
                        'delete' => '',
                    );
                    $group_select[] = $selection;
                }
                $selectionData[] = $group_select;
            }
        }
        $result = array(
            'can_save_configurable_attributes' => false,
            'can_save_custom_options' => true,
            'bundle_options_data' => $optionData,
            'bundle_selections_data' => $selectionData,
            'can_save_bundle_selections' => true,
            'affect_bundle_product_selections' => true,
        );
        return $result;
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @param int $parent_id : Id of parent product if exist
     * @return array
     */
    protected function _convertProduct($product, $productsExt, $used_attribute = array()) {
        $pro_data = array();
        $categories = array();
        $proCats = $this->getListFromListByField($productsExt['object']['catalog_category_product'], 'product_id', $product['entity_id']);
        if ($proCats) {
            foreach ($proCats as $pro_cat) {
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if ($cat_id) {
                    $categories[] = $cat_id;
                }
            }
        }
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $pro_data['category_ids'] = $categories;
        $pro_data['type_id'] = $product['type_id'];
        $webIds = array();
        $websites = $this->getListFromListByField($productsExt['object']['catalog_product_website'], 'product_id', $product['entity_id']);
        foreach ($websites as $web) {
            $store_id = $this->getRowValueFromListByField($productsExt['object']['core_store'], 'website_id', $web['website_id'], 'store_id');
            $webIds[] = $store_id && isset($this->_notice['config']['languages'][$store_id]) ? $this->getWebsiteIdByStoreId($this->_notice['config']['languages'][$store_id]) : $this->_notice['config']['website_id'];
        }
        $webIds = array_values(array_unique($webIds));
        $pro_data['website_ids'] = $webIds;
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = isset($this->_notice['config']['attributes'][$product['attribute_set_id']]) ? $this->_notice['config']['attributes'][$product['attribute_set_id']] : $this->_notice['config']['attribute_set_id'];
        $attribute = array();
        foreach ($productsExt['object']['eav_attribute'] as $row) {
            $attribute[$row['attribute_code']] = $row['attribute_id'];
            $attribute_type[$row['attribute_code']] = $row['backend_type'];
            $attribute_input[$row['attribute_code']] = $row['frontend_input'];
            $attribute_defined[$row['attribute_code']] = $row['is_user_defined'];
        }
        $varchar = $this->getListFromListByField($productsExt['object']['catalog_product_entity_varchar'], 'entity_id', $product['entity_id']);
        $text = $this->getListFromListByField($productsExt['object']['catalog_product_entity_text'], 'entity_id', $product['entity_id']);
        $int = $this->getListFromListByField($productsExt['object']['catalog_product_entity_int'], 'entity_id', $product['entity_id']);
        $decimal = $this->getListFromListByField($productsExt['object']['catalog_product_entity_decimal'], 'entity_id', $product['entity_id']);
        $datetime = $this->getListFromListByField($productsExt['object']['catalog_product_entity_datetime'], 'entity_id', $product['entity_id']);
        $names = $this->getListFromListByField($varchar, 'attribute_id', $attribute['name']);
        if ($pro_data['type_id'] == 'bundle') {
            $pro_data['store_id'] = 1;
            $pro_data['price_type'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['price_type'], 'value');
            $pro_data['sku_type'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['sku_type'], 'value');
            $pro_data['weight_type'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['weight_type'], 'value');
            $pro_data['price_view'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['price_view'], 'value');
        }
        $pro_data['name'] = $this->getRowValueFromListByField($names, 'store_id', '0', 'value');
        $descriptions = $this->getListFromListByField($text, 'attribute_id', $attribute['description']);
        $pro_data['description'] = $this->getRowValueFromListByField($descriptions, 'store_id', '0', 'value');
        $short_descriptions = $this->getListFromListByField($text, 'attribute_id', $attribute['short_description']);
        $pro_data['short_description'] = $this->getRowValueFromListByField($short_descriptions, 'store_id', '0', 'value');
        $pro_data['sku'] = $this->createProductSku($product['sku'], $this->_notice['config']['languages']);
        $pro_data['price'] = $this->getRowValueFromListByField($decimal, 'attribute_id', $attribute['price'], 'value');
        $pro_data['special_price'] = $this->getRowValueFromListByField($decimal, 'attribute_id', $attribute['special_price'], 'value');
        $pro_data['special_from_date'] = $this->getRowValueFromListByField($datetime, 'attribute_id', $attribute['special_from_date'], 'value');
        $pro_data['special_to_date'] = $this->getRowValueFromListByField($datetime, 'attribute_id', $attribute['special_to_date'], 'value');
        $pro_data['weight'] = $this->getRowValueFromListByField($decimal, 'attribute_id', $attribute['weight'], 'value');
        if (isset($attribute['manufacturer'])) {
            $manu_id = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['manufacturer'], 'value');
            if ($manufacture_mage_id = $this->getMageIdManufacturer($manu_id)) {
                $pro_data['manufacturer'] = $manufacture_mage_id;
            }
        }
        $meta_titles = $this->getListFromListByField($varchar, 'attribute_id', $attribute['meta_title']);
        $pro_data['meta_title'] = $this->getRowValueFromListByField($meta_titles, 'store_id', '0', 'value');
        $meta_keywords = $this->getListFromListByField($text, 'attribute_id', $attribute['meta_keyword']);
        $pro_data['meta_keyword'] = $this->getRowValueFromListByField($meta_keywords, 'store_id', '0', 'value');
        $meta_descriptions = $this->getListFromListByField($varchar, 'attribute_id', $attribute['meta_description']);
        $pro_data['meta_description'] = $this->getRowValueFromListByField($meta_descriptions, 'store_id', '0', 'value');
        $image_path = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['image'], 'value');
        $image_label = isset($attribute['image_label']) ? $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['image_label'], 'value') : '';
        if ($image_path_new = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_path, 'catalog/product', false, true)) {
            $pro_data['image_import_path'] = array('path' => $image_path_new, 'label' => $image_label);
        }
        $gallery = $this->getListFromListByField($productsExt['object']['catalog_product_entity_media_gallery'], 'entity_id', $product['entity_id']);
        if ($gallery) {
            foreach ($gallery as $img) {
                if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $img['value'], 'catalog/product', false, true)) {
                    $gal_label = $this->getRowValueFromListByField($productsExt['object']['catalog_product_entity_media_gallery_value'], 'value_id', $img['value_id'], 'label');
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $gal_label);
                }
            }
        }
        $tier_prices = $this->getListFromListByField($productsExt['object']['catalog_product_entity_tier_price'], 'entity_id', $product['entity_id']);
        if ($tier_prices) {
            foreach ($tier_prices as $tier) {
                if ($tier['all_groups'] == '1') {
                    $cus_group = Mage_Customer_Model_Group::CUST_GROUP_ALL;
                } elseif ($tier['all_groups'] == '0' && isset($this->_notice['config']['customer_group'][$tier['customer_group_id']])) {
                    $cus_group = $this->_notice['config']['customer_group'][$tier['customer_group_id']];
                } else {
                    $cus_group = Mage_Customer_Model_Group::CUST_GROUP_ALL;
                }
                $value = array(
                    'website_id' => 0,
                    'cust_group' => $cus_group,
                    'price_qty' => $tier['qty'],
                    'price' => $tier['value']
                );
                $tier_price_group[] = $value;
            }
            $pro_data['tier_price'] = $tier_price_group;
        }
        $pro_data['status'] = ($this->getRowValueFromListByField($int, 'attribute_id', $attribute['status'], 'value') == '1') ? 1 : 2;
        $pro_data['created_at'] = $product['created_at'];
        $pro_data['updated_at'] = $product['updated_at'];
        $pro_data['visibility'] = intval($this->getRowValueFromListByField($int, 'attribute_id', $attribute['visibility'], 'value'));
        $tax_class_id = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['tax_class_id'], 'value');
        if ($tax_class_id && $tax_pro_id = $this->getMageIdTaxProduct($tax_class_id)) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $stock = $this->getRowFromListByField($productsExt['object']['cataloginventory_stock_item'], 'product_id', $product['entity_id']);
        $stock_qty = floatval($stock['qty']);
        $pro_data['stock_data'] = array(
            'is_in_stock' => $stock['is_in_stock'],
            'manage_stock' => $stock['manage_stock'],
            'use_config_manage_stock' => $stock['use_config_manage_stock'],
            'qty' => $stock_qty
        );
        $pro_data['news_from_date'] = $this->getRowValueFromListByField($datetime, 'attribute_id', $attribute['news_from_date'], 'value');
        $pro_data['news_to_date'] = $this->getRowValueFromListByField($datetime, 'attribute_id', $attribute['news_to_date'], 'value');
        $url_store = $this->getListFromListByField($varchar, 'attribute_id', $attribute['url_key']);
        $pro_data['url_key'] = $this->getRowValueFromListByField($url_store, 'store_id', '0', 'value');

        //Attribute remain
        $attribute_remain = array_diff_key($attribute, $pro_data);
        //$attribute_set_id = $this->_notice['config']['attribute_set_id'];
        foreach ($attribute_remain as $key => $value) {
            if ($attribute_type[$key] == 'static' || !$attribute_defined[$key]) {
                continue;
            }
            if ($used_attribute) {
                if (in_array($value, $used_attribute)) {
                    continue;
                }
            }
            if ($option_value = $this->getListFromListByField(${$attribute_type[$key]}, 'attribute_id', $value)) {
                $attributeSets = $this->getListFromListByField($productsExt['object']['eav_entity_attribute'], 'attribute_id', $value);
                $attribute_set_src_ids = $this->duplicateFieldValueFromList($attributeSets, 'attribute_set_id');
                $attribute_set_id_desc = array($pro_data['attribute_set_id']);
                foreach ($attribute_set_src_ids as $attr_set) {
                    if (isset($this->_notice['config']['attributes'][$attr_set])) {
                        $attribute_set_id_desc[] = $this->_notice['config']['attributes'][$attr_set];
                    }
                }
                $attribute_set_id = array_unique($attribute_set_id_desc);
                //Group attr
//                $attribute_group_src_ids = $this->duplicateFieldValueFromList($attributeSets, 'attribute_group_id');
//                $attributeGroups = $this->getListFromListByListField($productsExt['object']['eav_attribute_group'], 'attribute_group_id', $attribute_group_src_ids);
//                foreach ($attributeGroups as $id => $row) {
//                    if (isset($this->_notice['config']['attributes'][$row['attribute_set_id']])) {
//                        $attributeGroups[$id]['attribute_set_id'] = $this->_notice['config']['attributes'][$row['attribute_set_id']];
//                    } else {
//                        unset($attributeGroups[$id]);
//                    }
//                }
                $attribute_add = $this->getRowFromListByField($productsExt['object']['eav_attribute'], 'attribute_id', $value);
                $option_mage = array();
                if ($attribute_input[$key] == 'select') {
                    $option_store = $this->getListFromListByField($productsExt['object']['eav_attribute_option_value'], 'option_id', $option_value[0]['value']);
                    if ($option_store) {
                        $option = array();
                        foreach ($option_store as $opt) {
                            if ($opt['store_id'] == '0') {
                                $option['0'] = $opt['value'];
                                continue;
                            }
                            if (isset($this->_notice['config']['languages'][$opt['store_id']])) {
                                $option[$this->_notice['config']['languages'][$opt['store_id']]] = $opt['value'];
                            }
                        }
                        if ($option) {
                            if (!isset($option['0'])) {
                                $option['0'] = reset($option);
                            }
                            $option_mage['option_0'] = $option;
                        }
                    }
                } elseif ($attribute_input[$key] == 'multiselect') {
                    if (!$option_value[0]['value']) continue;
                    $multi_opt = explode(',', $option_value[0]['value']);
                    foreach ($multi_opt as $k => $v) {
                        $option = array();
                        $option_multi_store = $this->getListFromListByField($productsExt['object']['eav_attribute_option_value'], 'option_id', $v);
                        foreach ($option_multi_store as $single) {
                            if ($single['store_id'] == '0') {
                                $option['0'] = $single['value'];
                                continue;
                            }
                            if (isset($this->_notice['config']['languages'][$single['store_id']])) {
                                $option[$this->_notice['config']['languages'][$single['store_id']]] = $single['value'];
                            }
                        }
                        if ($option) {
                            if (!isset($option['0'])) {
                                $option['0'] = reset($option);
                            }
                            $option_mage['option_'.$k] = $option;
                        }
                    }
                }
                
                $attr_import = $this->_makeAttributeImport($attribute_add, $option_mage, $attribute_set_id, $entity_type_id);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if (!$attr_after) {
                    var_dump($product['entity_id']);
                    var_dump($attr_import);
                    exit();
//                    return array(
//                        'result' => "warning",
//                        'msg' => $this->consoleWarning("Product Id = {$product['entity_id']} import failed. Error: Product attribute {$attribute_add['attribute_code']} could not create!")
//                    );
                }
                if ($attribute_input[$key] == 'select') {
                    $pro_data[$attr_after['attribute_code']] = isset($attr_after['option_ids']['option_0']) ? $attr_after['option_ids']['option_0'] : '';
                } elseif ($attribute_input[$key] == 'multiselect') {
                    $all_val = array();
                    foreach ($option_mage as $a => $b) {
                        $all_val[] = isset($attr_after['option_ids'][$a]) ? $attr_after['option_ids'][$a] : '';
                    }
                    $pro_data[$attr_after['attribute_code']] = $all_val;
                } else {
                    $pro_data[$attr_after['attribute_code']] = $option_value[0]['value'];
                }
            }
        }
        //Tag
        $proTags = $this->getListFromListByField($productsExt['object']['tag_relation'], 'product_id', $product['entity_id']);
        if ($proTags) {
            foreach ($proTags as $tag) {
                $pro_tag = $this->getRowFromListByField($productsExt['object']['tag'], 'tag_id', $tag['tag_id']);
                if ($pro_tag) {
                    $pro_data['tags'][] = $pro_tag['name'];
                }
            }
        }

        //Multi
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $store_data = array();
            $store_data['name'] = $this->getRowValueFromListByField($names, 'store_id', $lang_id, 'value');
            $store_data['description'] = $this->getRowValueFromListByField($descriptions, 'store_id', $lang_id, 'value');
            $store_data['short_description'] = $this->getRowValueFromListByField($short_descriptions, 'store_id', $lang_id, 'value');
            $store_data['meta_title'] = $this->getRowValueFromListByField($meta_titles, 'store_id', $lang_id, 'value');
            $store_data['meta_keyword'] = $this->getRowValueFromListByField($meta_keywords, 'store_id', $lang_id, 'value');
            $store_data['meta_description'] = $this->getRowValueFromListByField($meta_descriptions, 'store_id', $lang_id, 'value');
            $store_data['url_key'] = $this->getRowValueFromListByField($url_store, 'store_id', $lang_id, 'value');
            $store_data['store_id'] = $store_id;
            $multi_store[] = $store_data;
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

    protected function _importCustomerGroup($group) {
        $response = array();
        $tax_class_id = $this->getMageIdTaxCustomer($group['tax_class_id']);
        $group_code = $group['customer_group_code'];
        $oldGroup = Mage::getModel('customer/group')->getCollection()
                ->addFieldToFilter('customer_group_code', $group_code)
                ->getFirstItem();
        if ($oldGroup->getId()) {
            $response['result'] = 'success';
            $response['mage_id'] = $oldGroup->getId();
        } else {
            $newGroup = Mage::getModel('customer/group');
            $newGroup->setCode($group_code);
            $newGroup->setTaxClassId($tax_class_id);
            try {
                $newGroup->save();
                $response['result'] = 'success';
                $response['mage_id'] = $newGroup->getId();
            } catch (Exception $e) {
                $response['result'] = 'error';
                $response['msg'] = $e->getMessage();
            }
        }
        return $response;
    }

    protected function _taxCustomerGroupSuccess($id_import, $mage_id, $value = false) {
        return $this->_insertLeCaMgImport(self::TYPE_TAX_CUSTOMER_GROUP, $id_import, $mage_id, 1, $value);
    }

    protected function _getMageIdTaxCustomerGroup($id_import) {
        $result = $this->_selectLeCaMgImport(array(
            'domain' => $this->_cart_url,
            'type' => self::TYPE_TAX_CUSTOMER_GROUP,
            'id_import' => $id_import
        ));
        if (!$result) {
            return false;
        }
        return $result['mage_id'];
    }

    protected function review($data, $review_options) {
        $response = $rating = array();
        if (isset($data['rating'])) {
            $rating = $data['rating'];
            unset($data['rating']);
        }
        $created_at = $data['created_at'];
        $_review = Mage::getModel('review/review');
        $_review->addData($data);
        try {
            $_review->save();
            $_review->setCreatedAt($created_at);
            $_review->save();
            $review_id = $_review->getId();
            if ($rating) {
                foreach ($review_options as $rating_id => $option_ids) {
                    if (!isset($rating[$rating_id])) {
                        continue;
                    }
                    $_rating = Mage::getModel('rating/rating')
                            ->setRatingId($rating_id)
                            ->setReviewId($review_id)
                            ->addOptionVote($rating[$rating_id], $data['entity_pk_value']);
                }
            }
            $_review->aggregate();
            $response['result'] = 'success';
            $response['mage_id'] = $review_id;
        } catch (LitExtension_CartMigration_Exception $e) {
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        } catch (Exception $e) {
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }
    
    protected function _getDefaultLanguage($stores) {
        if (isset($stores['0']['sort_order'])) {
            $sort_order = $stores['0']['sort_order'];
        } else {
            return 1;
        }
        foreach ($stores as $store) {
            if ($store['sort_order'] < $sort_order) {
                $sort_order = $store['sort_order'];
            }
        }
        $default_lang = 1;
        foreach ($stores as $store) {
            if ($store['sort_order'] == $sort_order) {
                $default_lang = $store['store_id'];
                break;
            }
        }
        return $default_lang;
    }
    
    protected function _getCategoryParentId($path) {
        $array = explode("/", $path);
        $array = array_reverse($array);
        return $array[1];
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
        $query = "SELECT * FROM _DBPRF_tax_calculation_rule ORDER BY tax_calculation_rule_id ASC";
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
        $query = "SELECT eao.* FROM _DBPRF_eav_attribute as ea LEFT JOIN _DBPRF_eav_attribute_option as eao ON ea.attribute_id = eao.attribute_id WHERE attribute_code = 'manufacturer' ORDER BY eao.option_id ASC";
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
        $query = "SELECT * FROM _DBPRF_catalog_category_entity WHERE level > 1 ORDER BY entity_id ASC";
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
        $query = "SELECT * FROM _DBPRF_catalog_product_entity WHERE entity_id NOT IN
                    (SELECT product_id FROM _DBPRF_catalog_product_super_link UNION SELECT product_id FROM _DBPRF_catalog_product_bundle_selection UNION SELECT linked_product_id FROM catalog_product_link WHERE link_type_id = 3) ORDER BY entity_id ASC";
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
        $query = "SELECT * FROM _DBPRF_customer_entity ORDER BY entity_id ASC";
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
        $query = "SELECT * FROM _DBPRF_sales_order ORDER BY entity_id ASC";
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
        $query = "SELECT * FROM _DBPRF_review ORDER BY review_id ASC";
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
