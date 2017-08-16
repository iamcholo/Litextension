<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Magento19 extends LitExtension_CartMigration_Model_Cart {

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
                'products' => "SELECT COUNT(1) FROM _DBPRF_catalog_product_entity WHERE entity_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer_entity WHERE entity_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_sales_flat_order WHERE entity_id > {$this->_notice['orders']['id_src']}",
				'carts' => "SELECT COUNT(1) FROM _DBPRF_sales_flat_quote WHERE entity_id > {$this->_notice['carts']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_review WHERE review_id > {$this->_notice['reviews']['id_src']}",
                'pages' => "SELECT COUNT(1) FROM _DBPRF_cms_page WHERE page_id > {$this->_notice['pages']['id_src']}",
                'blocks' => "SELECT COUNT(1) FROM _DBPRF_cms_block WHERE block_id > {$this->_notice['blocks']['id_src']}",
                'transactions' => "SELECT COUNT(1) FROM _DBPRF_core_email_template WHERE template_id > {$this->_notice['transactions']['id_src']}",
                'rules' => "SELECT COUNT(1) FROM _DBPRF_salesrule WHERE rule_id > {$this->_notice['rules']['id_src']}",
                'cartrules' => "SELECT COUNT(1) FROM _DBPRF_catalogrule WHERE rule_id > {$this->_notice['cartrules']['id_src']}"
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
        if ($object && $object['core_store'] && $object['eav_entity_type']) {
            $this->_notice['config']['default_lang'] = $this->_getDefaultLanguage($object['core_store']);
            foreach ($object['eav_entity_type'] as $row) {
                $this->_notice['extend'][$row['entity_type_code']] = $row['entity_type_id'];
            }
        }
        if ($object['currencies']) {
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['value']) ? $object['currencies']['0']['value'] : 'USD';
        } else {
            $this->_notice['config']['default_currency'] = 'USD';
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "core_store" => "SELECT * FROM _DBPRF_core_store WHERE code != 'admin'",
                "currencies" => "SELECT * FROM _DBPRF_core_config_data WHERE path = 'currency/options/allow'",
                "sales_order_status" => "SELECT * FROM _DBPRF_sales_order_status",
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
        if ($obj['currencies']) {
            $currencies = explode(',', $obj['currencies'][0]['value']);
            foreach ($currencies as $currency_row) {
                $currency_id = $currency_row;
                $currency_name = $currency_row;
                $currency_data[$currency_id] = $currency_name;
            }
        } else {
            $currency_data['USD'] = 'USD';
        }
        foreach ($obj['sales_order_status'] as $order_status_row) {
            $order_status_id = $order_status_row['status'];
            $order_status_name = $order_status_row['label'];
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
        $this->_notice['config']['import_support']['carts'] = false;
        $this->_notice['extend']['migrate_image'] = true;
        $this->_notice['extend']['pre_prd'] = false;
	    //$this->_notice['config']['import_support']['pages'] = true;
        //$this->_notice['config']['config_support']['customer_group_map'] = false;
        //$this->_notice['config']['import_support']['blocks'] = true;
        //$this->_notice['config']['import_support']['transactions'] = true;
        //$this->_notice['config']['import_support']['rules'] = true;
        //$this->_notice['config']['import_support']['cartrules'] = true;
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
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews', 'pages', 'blocks', 'widgets', 'polls', 'transactions', 'newsletters', 'users', 'rules', 'cartrules');
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
                'products' => "SELECT COUNT(1) FROM _DBPRF_catalog_product_entity WHERE entity_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer_entity WHERE entity_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_sales_flat_order WHERE entity_id > {$this->_notice['orders']['id_src']}",
				'carts' => "SELECT COUNT(1) FROM _DBPRF_sales_flat_quote WHERE entity_id > {$this->_notice['carts']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_review WHERE review_id > {$this->_notice['reviews']['id_src']}",
                'pages' => "SELECT COUNT(1) FROM _DBPRF_cms_page WHERE page_id > {$this->_notice['pages']['id_src']}",
                'blocks' => "SELECT COUNT(1) FROM _DBPRF_cms_block WHERE block_id > {$this->_notice['blocks']['id_src']}",
                'transactions' => "SELECT COUNT(1) FROM _DBPRF_core_email_template WHERE template_id > {$this->_notice['transactions']['id_src']}",
                'rules' => "SELECT COUNT(1) FROM _DBPRF_salesrule WHERE rule_id > {$this->_notice['rules']['id_src']}",
                'cartrules' => "SELECT COUNT(1) FROM _DBPRF_catalogrule WHERE rule_id > {$this->_notice['cartrules']['id_src']}"
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
                if (!isset($this->_notice['config']['currencies'][$currency['currency_to']])) continue;
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
            if ($tax_rate['zip_is_range']) {
                $tax_rate_data['zip_is_range'] = $tax_rate['zip_is_range'];
                $tax_rate_data['zip_from'] = $tax_rate['zip_from'];
                $tax_rate_data['zip_to'] = $tax_rate['zip_to'];
            } else {
                $tax_rate_data['zip_is_range'] = 0;
            }
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
            if (isset($manu_store[$lang_id])) {
                $manufacturer_data['value']['option'][$store_id] = $manu_store[$lang_id];
            }
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
        //aaa
        if (isset($attribute['thumbnail'])) {
            $thumbs = $this->getListFromListByField($varchar, 'attribute_id', $attribute['thumbnail']);
            $thumb_path = $this->getRowValueFromListByField($thumbs, 'store_id', '0', 'value');
            if ($thumb_path && $thumbnail_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $thumb_path, 'catalog/category')) {
                $cat_data['thumbnail'] = $thumbnail_path;
            }
        }
        //aaa
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_anchor'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['is_anchor'], 'value');
        $include_in_menus = $this->getListFromListByField($int, 'attribute_id', $attribute['include_in_menu']);
        $cat_data['include_in_menu'] = $this->getRowValueFromListByField($include_in_menus, 'store_id', '0', 'value');
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
            $store_data['include_in_menu'] = $this->getRowValueFromListByField($include_in_menus, 'store_id', $lang_id, 'value');
            $store_data['display_mode'] = $this->getRowValueFromListByField($display_modes, 'store_id', $lang_id, 'value');
            $store_data['url_key'] = $this->getRowValueFromListByField($url_store, 'store_id', $lang_id, 'value');
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
        $query = "SELECT * FROM _DBPRF_catalog_product_entity WHERE entity_id > {$id_src} ORDER BY entity_id ASC LIMIT {$limit}";
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
        $ext_query = array(
            'catalog_product_relation' => "SELECT * FROM _DBPRF_catalog_product_relation WHERE parent_id IN {$pro_id_query} OR child_id IN {$pro_id_query}",
            'catalog_product_super_attribute' => "SELECT * FROM _DBPRF_catalog_product_super_attribute WHERE product_id IN {$pro_id_query}",
            'eav_attribute' => "SELECT * FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['catalog_product']}",
            'catalog_product_bundle_option' => "SELECT * FROM _DBPRF_catalog_product_bundle_option WHERE parent_id IN {$pro_id_query}",
            'catalog_product_bundle_selection' => "SELECT * FROM _DBPRF_catalog_product_bundle_selection WHERE parent_product_id IN {$pro_id_query} OR product_id IN {$pro_id_query}",
            'tag_relation' => "SELECT * FROM _DBPRF_tag_relation WHERE product_id IN {$pro_id_query}",
            'catalog_product_link' => "SELECT * FROM _DBPRF_catalog_product_link WHERE ( product_id IN {$pro_id_query} OR linked_product_id IN {$pro_id_query} ) AND link_type_id IN (1,4,5)",
            'downloadable_link' => "SELECT * FROM _DBPRF_downloadable_link WHERE product_id IN {$pro_id_query}",
            'catalog_product_entity_datetime' => "SELECT * FROM _DBPRF_catalog_product_entity_datetime WHERE entity_id IN {$pro_id_query}",
            'catalog_product_entity_decimal' => "SELECT * FROM _DBPRF_catalog_product_entity_decimal WHERE entity_id IN {$pro_id_query}",
            'catalog_product_entity_int' => "SELECT * FROM _DBPRF_catalog_product_entity_int WHERE entity_id IN {$pro_id_query}",
            'catalog_product_entity_text' => "SELECT * FROM _DBPRF_catalog_product_entity_text WHERE entity_id IN {$pro_id_query}",
            'catalog_product_entity_varchar' => "SELECT * FROM _DBPRF_catalog_product_entity_varchar WHERE entity_id IN {$pro_id_query}",
            'catalog_product_entity_gallery' => "SELECT * FROM _DBPRF_catalog_product_entity_gallery WHERE entity_id IN {$pro_id_query}",
            'catalog_product_entity_media_gallery' => "SELECT a.*, b.* FROM _DBPRF_catalog_product_entity_media_gallery a LEFT JOIN _DBPRF_catalog_product_entity_media_gallery_value b ON a.value_id = b.value_id WHERE b.store_id = 0 AND a.entity_id IN {$pro_id_query}",
            'catalog_product_entity_tier_price' => "SELECT * FROM _DBPRF_catalog_product_entity_tier_price WHERE entity_id IN {$pro_id_query}",
            'catalog_product_option' => "SELECT * FROM _DBPRF_catalog_product_option WHERE product_id IN {$pro_id_query}",
            'catalog_category_product' => "SELECT * FROM _DBPRF_catalog_category_product WHERE product_id IN {$pro_id_query}",
            'cataloginventory_stock_item' => "SELECT * FROM _DBPRF_cataloginventory_stock_item WHERE product_id IN {$pro_id_query}",
            'catalog_product_website' => "SELECT * FROM _DBPRF_catalog_product_website WHERE product_id IN {$pro_id_query}",
            'catalog_product_entity_group_price' => "SELECT * FROM _DBPRF_catalog_product_entity_group_price WHERE entity_id IN {$pro_id_query}"
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
        $superAttrIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_super_attribute'], 'product_super_attribute_id');
        $super_attr_id = $this->arrayToInCondition($superAttrIds);
        $attrIds = $this->duplicateFieldValueFromList($productsExt['object']['eav_attribute'], 'attribute_id');
        $attr_id = $this->arrayToInCondition($attrIds);
        $bundleOptIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_bundle_option'], 'option_id');
        $bundleOptIdsChild = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_bundle_selection'], 'option_id');
        $bundleOptAllIds = array_merge($bundleOptIds, $bundleOptIdsChild); 
        $bundle_opt_id = $this->arrayToInCondition($bundleOptAllIds);
        $tagIds = $this->duplicateFieldValueFromList($productsExt['object']['tag_relation'], 'tag_id');
        $tag_ids_query = $this->arrayToInCondition($tagIds);
        $linkIds = $this->duplicateFieldValueFromList($productsExt['object']['downloadable_link'], 'link_id');
        $link_ids_query = $this->arrayToInCondition($linkIds);
        //Add
        $optionAttrIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_entity_int'], 'value');
        $option_attr_id = $this->arrayToInCondition($optionAttrIds);
        $optionCusIds = $this->duplicateFieldValueFromList($productsExt['object']['catalog_product_option'], 'option_id');
        $option_cus_id = $this->arrayToInCondition($optionCusIds);
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
        $ext_rel_query = array(
            'catalog_product_super_attribute_label' => "SELECT * FROM _DBPRF_catalog_product_super_attribute_label WHERE product_super_attribute_id IN {$super_attr_id}",
            'catalog_product_super_attribute_pricing' => "SELECT * FROM _DBPRF_catalog_product_super_attribute_pricing WHERE product_super_attribute_id IN {$super_attr_id}",
            'catalog_eav_attribute' => "SELECT * FROM _DBPRF_catalog_eav_attribute WHERE attribute_id IN {$attr_id}",
            'catalog_product_bundle_option_value' => "SELECT * FROM _DBPRF_catalog_product_bundle_option_value WHERE option_id IN {$bundle_opt_id} AND store_id = 0",
            'tag' => "SELECT * FROM _DBPRF_tag WHERE tag_id IN {$tag_ids_query}",
            'eav_entity_attribute' => "SELECT * FROM _DBPRF_eav_entity_attribute WHERE attribute_id IN {$attr_id} AND entity_type_id = {$this->_notice['extend']['catalog_product']}",
            'core_store' => "SELECT * FROM _DBPRF_core_store WHERE code != 'admin'",
            'downloadable_link_price' => "SELECT * FROM _DBPRF_downloadable_link_price WHERE link_id IN {$link_ids_query}",
            'downloadable_link_title' => "SELECT * FROM _DBPRF_downloadable_link_title WHERE link_id IN {$link_ids_query}",
            'eav_attribute_option_value' => "SELECT * FROM _DBPRF_eav_attribute_option_value WHERE option_id IN {$option_attr_id} OR option_id IN {$all_option_query}",
            'catalog_product_option_title' => "SELECT * FROM _DBPRF_catalog_product_option_title WHERE option_id IN {$option_cus_id}",
            'catalog_product_option_price' => "SELECT * FROM _DBPRF_catalog_product_option_price WHERE option_id IN {$option_cus_id}",
            'catalog_product_option_type_value' => "SELECT a.*, b.*, c.price, c.price_type FROM _DBPRF_catalog_product_option_type_value as a 
                                                                                            LEFT JOIN _DBPRF_catalog_product_option_type_title as b ON a.option_type_id = b.option_type_id
                                                                                            LEFT JOIN _DBPRF_catalog_product_option_type_price as c ON b.option_type_id = c.option_type_id AND b.store_id = c.store_id
                                                                                            WHERE a.option_id IN {$option_cus_id}
                                                                                            ",
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
        $pro_data = array();
        $categories = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $proCats = $this->getListFromListByField($productsExt['object']['catalog_category_product'], 'product_id', $product['entity_id']);
        if ($proCats) {
            foreach ($proCats as $pro_cat) {
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if ($cat_id) {
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        $pro_data['type_id'] = $product['type_id'];
		$pro_data['attribute_set_id'] = isset($this->_notice['config']['attributes'][$product['attribute_set_id']]) ? $this->_notice['config']['attributes'][$product['attribute_set_id']] : $this->_notice['config']['attribute_set_id'];
        //Class
        if ($product['type_id'] == 'configurable') {
            $attrMage = $attribute_config = array();
            $attributeAll = $this->getListFromListByField($productsExt['object']['catalog_product_super_attribute'], 'product_id', $product['entity_id']);
            foreach ($attributeAll as $row) {
                $attribute = $this->getRowFromListByField($productsExt['object']['eav_attribute'], 'attribute_id', $row['attribute_id']);
                $info = $this->getRowFromListByField($productsExt['object']['catalog_eav_attribute'], 'attribute_id', $row['attribute_id']);
                $attr_import = $this->_makeAttributeImport($attribute, array(), $pro_data['attribute_set_id'], $info, $entity_type_id, true);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                $attr_label = $this->getRowValueFromListByField($productsExt['object']['catalog_product_super_attribute_label'], 'product_super_attribute_id', $row['product_super_attribute_id'], 'value');
                if ($attr_label) {
                    $attrMage[$attr_after['attribute_id']]['attribute_label'] = $attr_label;
                } else {
                    $attrMage[$attr_after['attribute_id']]['attribute_label'] = $attribute['frontend_label'];
                }
                $attrMage[$attr_after['attribute_id']]['attribute_code'] = $attr_after['attribute_code'];
                $attrMage[$attr_after['attribute_id']]['position'] = $row['position'];
            }
            $associated_product = array();
            $children = $this->getListFromListByField($productsExt['object']['catalog_product_relation'], 'parent_id', $product['entity_id']);
            foreach ($children as $child) {
                $id_desc = $this->getMageIdProduct($child['child_id']);
                if ($id_desc) {
                    foreach ($attrMage as $attr_id => $attr_info) {
                        $value_index = Mage::getModel('catalog/product')->load($id_desc)->getData($attr_info['attribute_code']);
                        if ($value_index) {
                            $associated_product[$id_desc][] = array(
                                'attribute_id' => $attr_id,
                                'label' => $attr_info['attribute_label'],
                                'value_index' => $value_index
                            );
                        }
                    }
                }
            }
            foreach ($attrMage as $key => $attribute) {
                $dad = array(
                    'label' => $attribute['attribute_label'],
                    'attribute_id' => $key,
                    'attribute_code' => $attribute['attribute_code'],
                    'position' => $attribute['position'],
                );
                $attribute_config[$key] = $dad;
            }
            $pro_data['configurable_products_data'] = $associated_product;
            $pro_data['configurable_attributes_data'] = $attribute_config;
            $pro_data['can_save_configurable_attributes'] = 1;
        } elseif ($product['type_id'] == 'bundle') {
            $optionData = $selectionData = array();
            $bundleOptions = $this->getListFromListByField($productsExt['object']['catalog_product_bundle_option'], 'parent_id', $product['entity_id']);
            foreach ($bundleOptions as $row) {
                $title = $this->getRowValueFromListByField($productsExt['object']['catalog_product_bundle_option_value'], 'option_id', $row['option_id'], 'title');
                $option = array(
                    'required' => $row['required'],
                    'option_id' => '',
                    'position' => $row['position'],
                    'type' => $row['type'],
                    'title' => $title,
                    'default_title' => $title,
                    'delete' => '',
                );
                $optionData[] = $option;
                $selections = $this->getListFromListByField($productsExt['object']['catalog_product_bundle_selection'], 'option_id', $row['option_id']);
                $group_select = array();
                foreach ($selections as $value) {
                    $id_desc = $this->getMageIdProduct($value['product_id']);
                    if ($id_desc) {
                        $selection = array(
                            'product_id' => $id_desc,
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
                }
                $selectionData[] = $group_select;
            }
            $pro_data['can_save_custom_options'] = true;
            $pro_data['bundle_options_data'] = $optionData;
            $pro_data['bundle_selections_data'] = $selectionData;
            $pro_data['can_save_bundle_selections'] = true;
            $pro_data['affect_bundle_product_selections'] = true;
        }
        //eof
        $webIds = array();
        $websites = $this->getListFromListByField($productsExt['object']['catalog_product_website'], 'product_id', $product['entity_id']);
        foreach ($websites as $web) {
            $store_id = $this->getRowValueFromListByField($productsExt['object']['core_store'], 'website_id', $web['website_id'], 'store_id');
            $webIds[] = $store_id && isset($this->_notice['config']['languages'][$store_id]) ? $this->getWebsiteIdByStoreId($this->_notice['config']['languages'][$store_id]) : $this->_notice['config']['website_id'];
        }
        $webIds = array_values(array_unique($webIds));
        $pro_data['website_ids'] = $webIds;
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
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
        if ($pro_data['type_id'] == 'downloadable') {
            $pro_data['links_purchased_separately'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['links_purchased_separately'], 'value');
            $pro_data['links_title'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['links_title'], 'value');
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
        $image_label = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['image_label'], 'value');
        $desc_location = Mage::getBaseDir() . '/media/' . 'catalog/product';
        if (!$this->_notice['extend']['migrate_image']) {
            if ($image_path) {
                $pro_data['image_import_path'] = array('path' => $desc_location . $image_path, 'label' => $image_label);
            }
        } elseif($image_path_new = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_path, 'catalog/product', false, true)) {
            $pro_data['image_import_path'] = array('path' => $image_path_new, 'label' => $image_label);
        }
        $gallery = $this->getListFromListByField($productsExt['object']['catalog_product_entity_media_gallery'], 'entity_id', $product['entity_id']);
        if ($gallery) {
            $gallery = $this->positionSort($gallery);
            foreach ($gallery as $img) {
                if (!$this->_notice['extend']['migrate_image']) {
                    if ($img['value']) {
                        $pro_data['image_gallery'][] = array('path' => $desc_location . $img['value'], 'label' => $img['label']);
                    }
                } elseif($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $img['value'], 'catalog/product', false, true)) {
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $img['label']);
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
        /*$pro_data['stock_data'] = array(
            'is_in_stock' => ($stock['is_in_stock'] == '1') ? 1 : 0,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $stock_qty < 1) ? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $stock_qty < 1) ? 0 : 1,
            'qty' => $stock_qty
        );*/
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
            /*if ($used_attribute) {
                if (in_array($value, $used_attribute)) {
                    continue;
                }
            }*/
            if ($option_value = $this->getListFromListByField($$attribute_type[$key], 'attribute_id', $value)) {
                $attributeSets = $this->getListFromListByField($productsExt['object']['eav_entity_attribute'], 'attribute_id', $value);
                $attribute_set_src_ids = $this->duplicateFieldValueFromList($attributeSets, 'attribute_set_id');
                $attribute_set_id_desc = array($pro_data['attribute_set_id']);
                foreach ($attribute_set_src_ids as $attr_set) {
                    if (isset($this->_notice['config']['attributes'][$attr_set])) {
                        $attribute_set_id_desc[] = $this->_notice['config']['attributes'][$attr_set];
                    }
                }
                $attribute_set_id = array_unique($attribute_set_id_desc);
                $attribute_add = $this->getRowFromListByField($productsExt['object']['eav_attribute'], 'attribute_id', $value);
                $info = $this->getRowFromListByField($productsExt['object']['catalog_eav_attribute'], 'attribute_id', $value);
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
					if (!$option_mage) {
						continue;
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
					if (!$option_mage) {
						continue;
					}
                }
                
                $attr_import = $this->_makeAttributeImport($attribute_add, $option_mage, $attribute_set_id, $info, $entity_type_id);
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
        if ($this->_notice['extend']['pre_prd']) {
            $pro_data['id'] = $product['entity_id'];
        }
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
        $groupPrices = $this->getListFromListByField($productsExt['object']['catalog_product_entity_group_price'], 'entity_id', $product['entity_id']);
        if($groupPrices){
            foreach ($groupPrices as $group_price){
                $data = array(
                    'entity_id' => $product_mage_id,
                    'all_groups' => 0,
                    'customer_group_id' => isset($this->_notice['config']['customer_group'][$group_price['customer_group_id']]) ? $this->_notice['config']['customer_group'][$group_price['customer_group_id']] : 0,
                    'value' => $group_price['value'],
                    'website_id' => 0
                );
                $this->insertTable('catalog_product_entity_group_price', $data);
            }
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
                    $optionIpt['sku'] = ($option['sku']) ? $option['sku'] : null;
                    $optionIpt['max_characters'] = $option['max_characters'] ? $option['max_characters'] : null;
                    $optionIpt['file_extension'] = $option['file_extension'] ? $option['file_extension'] : null;
                    $optionIpt['image_size_x'] = $option['image_size_x'] ? $option['image_size_x'] : null;
                    $optionIpt['image_size_y'] = $option['image_size_y'] ? $option['image_size_y'] : null;
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
            $child_products = $this->getListFromListByField($productsExt['object']['catalog_product_relation'], 'parent_id', $product['entity_id']);
            foreach ($child_products as $child) {
                $check_imported = $this->getMageIdProduct($child['child_id']);
                if ($check_imported) {
                    $group[] = $check_imported;
                }
            }
            $products_links = Mage::getModel('catalog/product_link_api');
            foreach ($group as $value) {
                $products_links->assign("grouped", $product_mage_id, $value);
            }
        } else {
            $parent_products = $this->getListFromListByField($productsExt['object']['catalog_product_relation'], 'child_id', $product['entity_id']);
            foreach ($parent_products as $parent) {
                $id_parent = $this->getMageIdProduct($parent['parent_id']);
                if ($id_parent) {
                    $model_parent = Mage::getModel('catalog/product')->load($id_parent);
                    $type_id = $model_parent->getTypeId();
                    if ($type_id == 'configurable') {
                        $add_data = $attribute_config = array();
                        $model_type = $model_parent->getTypeInstance();
                        $used_product_ids = $model_type->getChildrenIds($id_parent);
						$children = array();
                        $children = $used_product_ids[0];
                        //$children[$product_mage_id] = $product_mage_id;
                        $config_attributes = $model_type->getConfigurableAttributesAsArray($model_parent);
                        
                        $model_child = Mage::getModel('catalog/product')->load($product_mage_id);
                        foreach ($config_attributes as $key => $attribute) {
                            $dad = array(
                                'label' => $attribute['label'],
                                'use_default' => $attribute['use_default'],
                                'attribute_id' => $attribute['attribute_id'],
                                'code' => $attribute['attribute_code'],
                                'position' => $attribute['position'],
                            );
                            $dataTMP['attribute_id'] = $attribute['attribute_id'];
                            $dataTMP['value_index'] = $model_child->getData($attribute['attribute_code']);
                            $dataTMP['is_percent'] = 0;
                            $children[$product_mage_id][] = $dataTMP;
                            /*foreach ($attribute['values'] as $option) {
                                $child = array(
                                    'attribute_id' => $attribute['attribute_id'],
                                    'is_percent' => $option['is_percent'],
                                    'pricing_value' => $option['pricing_value'],
                                    'label' => $option['default_label'],
                                    'value_index' => $option['value_index'],
                                );
                                $values[] = $child;
                            }
                            $dad['values'] = $values;*/
                            $attribute_config[$key] = $dad;
                        }
                        $add_data['configurable_products_data'] = $children;
                        $add_data['configurable_attributes_data'] = $config_attributes;
                        $add_data['can_save_configurable_attributes'] = 1;
                        $add_data['affect_configurable_product_attributes'] = 1;
                        $model_parent->addData($add_data);
                        $model_parent->save();
                    } elseif ($type_id == 'bundle') {
                        $add_data = $optionData = $selectionData = array();
                        $model_type = $model_parent->getTypeInstance();
                        //$used_product_ids = $model_type->getChildrenIds($id_parent);
                        $allOptions = $model_type->getOptions($model_parent);
                        foreach ($allOptions as $row) {
                            $option = $row->getData();
                            $option['delete'] = '';
                            $optionData[] = $option;
                            $group_select = array();
                            $selectionsOption = $this->selectTable('catalog_product_bundle_selection', array('parent_product_id' => $id_parent, 'option_id' => $option['option_id']));
                            foreach ($selectionsOption as $value) {
                                $value['delete'] = '';
                                $group_select[] = $value;
                            }
                            $srcSelections = $this->getListFromListByField($productsExt['object']['catalog_product_bundle_selection'], 'parent_product_id', $parent['parent_id']);
                            $srcSelection = $this->getRowFromListByField($srcSelections, 'product_id', $product['entity_id']);
                            $title = $this->getRowValueFromListByField($productsExt['object']['catalog_product_bundle_option_value'], 'option_id', $srcSelection['option_id'], 'title');
                            if ($option['default_title'] == $title) {
                                $selection = array(
                                    'product_id' => $product_mage_id,
                                    'selection_qty' => $srcSelection['selection_qty'],
                                    'selection_can_change_qty' => $srcSelection['selection_can_change_qty'],
                                    'position' => $srcSelection['position'],
                                    'is_default' => $srcSelection['is_default'],
                                    'selection_id' => '',
                                    'selection_price_type' => $srcSelection['selection_price_type'],
                                    'selection_price_value' => $srcSelection['selection_price_value'],
                                    'option_id' => '',
                                    'delete' => '',
                                );
                                $group_select[] = $selection;
                            }
                            $selectionData[] = $group_select;
                        }
                        $add_data['bundle_options_data'] = $optionData;
                        $add_data['bundle_selections_data'] = $selectionData;
                        $add_data['can_save_custom_options'] = true;
                        $add_data['can_save_bundle_selections'] = true;
                        $add_data['affect_bundle_product_selections'] = true;
                        $model_parent->addData($add_data);
                        $model_parent->save();
                    } elseif ($type_id == 'grouped') {
                        $products_links = Mage::getModel('catalog/product_link_api');
                        $products_links->assign("grouped", $id_parent, $product_mage_id);
                    }
                }
            }
        }
        //Downloadable
        if ($product['type_id'] == 'downloadable') {
            $linkAll = $this->getListFromListByField($productsExt['object']['downloadable_link'], 'product_id', $product['entity_id']);
            foreach ($linkAll as $link) {
                $link['price'] = $this->getRowValueFromListByField($productsExt['object']['downloadable_link_price'], 'link_id', $link['link_id'], 'price');
                $link['title'] = $this->getRowValueFromListByField($productsExt['object']['downloadable_link_title'], 'link_id', $link['link_id'], 'title');
                $link['website_id'] = 0;
                $link['store_id'] = 0;
                $link['product_id'] = $product_mage_id;
                unset($link['link_id']);
                $this->_process->productDownloadLink($link);
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
        $cus_data['gender'] = $this->getRowValueFromListByField($int, 'attribute_id', $attribute['gender'], 'value');
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
//            $attribute_cus = array();
//            foreach ($customersExt['object']['eav_attribute'] as $row) {
//                if ($row['entity_type_id'] == $this->_notice['extend']['customer']) {
//                    $attribute_cus[$row['attribute_code']] = $row['attribute_id'];
//                }
//            }
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
                if (isset($attribute['vat_id'])) {
                    $address['vat_id'] = $this->getRowValueFromListByField($varchar, 'attribute_id', $attribute['vat_id'], 'value');
                }
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
        $query = "SELECT * FROM _DBPRF_sales_flat_order WHERE entity_id > {$id_src} ORDER BY entity_id ASC LIMIT {$limit}";
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
            'sales_flat_order_item' => "SELECT * FROM _DBPRF_sales_flat_order_item WHERE order_id IN {$order_id_query} ORDER BY item_id ASC",
            'sales_flat_order_address' => "SELECT * FROM _DBPRF_sales_flat_order_address WHERE parent_id IN {$order_id_query}",
            'sales_flat_order_status_history' => "SELECT * FROM _DBPRF_sales_flat_order_status_history WHERE parent_id IN {$order_id_query} ORDER BY entity_id ASC",
            'sales_flat_order_payment' => "SELECT * FROM _DBPRF_sales_flat_order_payment WHERE parent_id IN {$order_id_query}",
            'sales_flat_shipment_track' => "SELECT * FROM _DBPRF_sales_flat_shipment_track WHERE order_id IN {$order_id_query}",
            'sales_flat_invoice' => "SELECT * FROM _DBPRF_sales_flat_invoice WHERE order_id IN {$order_id_query}"
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
        $invoiceIds = $this->duplicateFieldValueFromList($ordersExt['object']['sales_flat_invoice'], 'entity_id');
        $invoice_ids_query = $this->arrayToInCondition($invoiceIds);
        $ext_rel_query = array(
            'sales_flat_invoice_comment' => "SELECT * FROM _DBPRF_sales_flat_invoice_comment WHERE parent_id IN {$invoice_ids_query}",
            'sales_flat_invoice_grid' => "SELECT * FROM _DBPRF_sales_flat_invoice_grid WHERE entity_id IN {$invoice_ids_query}",
            'sales_flat_invoice_item' => "SELECT * FROM _DBPRF_sales_flat_invoice_item WHERE parent_id IN {$invoice_ids_query} ORDER BY order_item_id ASC"
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
        $address_order = $this->getListFromListByField($ordersExt['object']['sales_flat_order_address'], 'parent_id', $order['entity_id']);
        //Billing address
        $billing = $this->getRowFromListByField($address_order, 'address_type', 'billing');

        $address_billing['firstname'] = $billing['firstname'];
        $address_billing['middlename'] = $billing['middlename'];
        $address_billing['lastname'] = $billing['lastname'];
        $address_billing['company'] = $billing['company'];
        $address_billing['email'] = $billing['email'];
        $address_billing['street'] = $billing['street'];
        $address_billing['city'] = $billing['city'];
        $address_billing['postcode'] = $billing['postcode'];
        $address_billing['telephone'] = $billing['telephone'];
        $address_billing['country_id'] = $billing['country_id'];
        $address_billing['region'] = $billing['region'];
        $address_billing['fax'] = $billing['fax'];
        $address_billing['region_id'] = $this->getRegionId($address_billing['region'], $address_billing['country_id']);

        //Shipping address
        $shipping = $this->getRowFromListByField($address_order, 'address_type', 'shipping');
        $address_shipping['firstname'] = $shipping['firstname'];
        $address_shipping['middlename'] = $shipping['middlename'];
        $address_shipping['lastname'] = $shipping['lastname'];
        $address_shipping['company'] = $shipping['company'];
        $address_shipping['email'] = $shipping['email'];
        $address_shipping['street'] = $shipping['street'];
        $address_shipping['city'] = $shipping['city'];
        $address_shipping['postcode'] = $shipping['postcode'];
        $address_shipping['telephone'] = $shipping['telephone'];
        $address_shipping['country_id'] = $shipping['country_id'];
        $address_shipping['region'] = $shipping['region'];
        $address_shipping['fax'] = $shipping['fax'];
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
        $store_id = ($order['store_id'] && isset($this->_notice['config']['languages'][$order['store_id']])) ? $this->_notice['config']['languages'][$order['store_id']] : $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $order_data['store_id'] = $store_id;
        $customer_id = $this->getMageIdCustomer($order['customer_id']);
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['customer_email'];
        $order_data['customer_firstname'] = $order['customer_firstname'];
        $order_data['customer_middlename'] = $order['customer_middlename'];
        $order_data['customer_lastname'] = $order['customer_lastname'];
        if (isset($this->_notice['config']['customer_group'][$order['customer_group_id']])) {
            $order_data['customer_group_id'] = $this->_notice['config']['customer_group'][$order['customer_group_id']];
        } else {
            $order_data['customer_group_id'] = 1;
        }
        $order_data['status'] = $this->_notice['config']['order_status'][$order['status']];
        $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        $order_data['subtotal'] = $order['subtotal'];
        $order_data['subtotal_canceled'] = $order['subtotal_canceled'];
        $order_data['subtotal_invoiced'] = $order['subtotal_invoiced'];
        $order_data['subtotal_refunded'] = $order['subtotal_refunded'];
        $order_data['base_subtotal'] = $order['base_subtotal'];
        $order_data['base_subtotal_canceled'] = $order['base_subtotal_canceled'];
        $order_data['base_subtotal_invoiced'] = $order['base_subtotal_invoiced'];
        $order_data['base_subtotal_refunded'] = $order['base_subtotal_refunded'];
        $order_data['shipping_amount'] = $order['shipping_amount'];
        $order_data['shipping_canceled'] = $order['shipping_canceled'];
        $order_data['shipping_invoiced'] = $order['shipping_invoiced'];
        $order_data['shipping_refunded'] = $order['shipping_refunded'];
        $order_data['shipping_tax_amount'] = $order['shipping_tax_amount'];
        $order_data['shipping_tax_refunded'] = $order['shipping_tax_refunded'];
        $order_data['base_shipping_amount'] = $order['base_shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order['base_shipping_invoiced'];
        $order_data['base_shipping_refunded'] = $order['base_shipping_refunded'];
        $order_data['base_shipping_tax_amount'] = $order['base_shipping_tax_amount'];
        $order_data['base_shipping_tax_refunded'] = $order['base_shipping_tax_refunded'];
        $order_data['shipping_description'] = $order['shipping_description'];
        $order_data['tax_amount'] = $order['tax_amount'];
        $order_data['tax_canceled'] = $order['tax_canceled'];
        $order_data['tax_invoiced'] = $order['tax_invoiced'];
        $order_data['tax_refunded'] = $order['tax_refunded'];
        $order_data['base_tax_amount'] = $order['base_tax_amount'];
        $order_data['base_tax_canceled'] = $order['base_tax_canceled'];
        $order_data['base_tax_invoiced'] = $order['base_tax_invoiced'];
        $order_data['base_tax_refunded'] = $order['base_tax_refunded'];
        $order_data['discount_amount'] = $order['discount_amount'];
        $order_data['discount_canceled'] = $order['discount_canceled'];
        $order_data['discount_invoiced'] = $order['discount_invoiced'];
        $order_data['discount_refunded'] = $order['discount_refunded'];
        $order_data['base_discount_amount'] = $order['base_discount_amount'];
        $order_data['base_discount_canceled'] = $order['base_discount_canceled'];
        $order_data['base_discount_invoiced'] = $order['base_discount_invoiced'];
        $order_data['base_discount_refunded'] = $order['base_discount_refunded'];
        $order_data['grand_total'] = $order['grand_total'];
        $order_data['base_grand_total'] = $order['base_grand_total'];
        $order_data['base_total_invoiced_cost'] = $order['base_total_invoiced_cost'];
        $order_data['base_total_offline_refunded'] = $order['base_total_offline_refunded'];
        $order_data['base_total_online_refunded'] = $order['base_total_online_refunded'];
        $order_data['base_total_canceled'] = $order['base_total_canceled'];
        $order_data['base_total_invoiced'] = $order['base_total_invoiced'];
        $order_data['base_total_refunded'] = $order['base_total_refunded'];
        $order_data['total_paid'] = $order['total_paid'];
        $order_data['total_refunded'] = $order['total_refunded'];
        $order_data['total_offline_refunded'] = $order['total_offline_refunded'];
        $order_data['total_online_refunded'] = $order['total_online_refunded'];
        $order_data['base_total_paid'] = $order['base_total_paid'];
        $order_data['base_to_global_rate'] = $order['base_to_global_rate'];
        $order_data['base_to_order_rate'] = $order['base_to_order_rate'];
        $order_data['store_to_base_rate'] = $order['store_to_base_rate'];
        $order_data['store_to_order_rate'] = $order['store_to_order_rate'];
        $order_data['base_currency_code'] = $order['base_currency_code'];
        $order_data['global_currency_code'] = $order['global_currency_code'];
        $order_data['store_currency_code'] = $order['store_currency_code'];
        $order_data['order_currency_code'] = $order['order_currency_code'];
        $order_data['remote_ip'] = isset($order['remote_ip']) ? $order['remote_ip'] : null;
        $order_data['created_at'] = $order['created_at'];
        $order_data['updated_at'] = $order['updated_at'];
        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['increment_id'];
        $payment = array();
        $paymentDataSrc = $this->getRowFromListByField($ordersExt['object']['sales_flat_order_payment'], 'parent_id', $order['entity_id']);
        foreach ($paymentDataSrc as $key => $value) {
            if ($key == 'entity_id' || $key == 'parent_id') {
                continue;
            }
            $payment[$key] = $value;
        }
        if ($payment['additional_information']) {
            $payment['additional_information'] = unserialize($payment['additional_information']);
        }
        $data['payment'] = $payment;
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
        $orderHistory = $this->getListFromListByField($ordersExt['object']['sales_flat_order_status_history'], 'parent_id', $order['entity_id']);
        foreach ($orderHistory as $key => $row) {
            $order_status_data = array();
            $order_status_data['status'] = isset($this->_notice['config']['order_status'][$row['status']]) ? $this->_notice['config']['order_status'][$row['status']] : null;
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            $order_status_data['comment'] = ($row['comment']) ? $row['comment'] : '';
            if ($key == 0) {
                $shipmentTrack = $this->getRowFromListByField($ordersExt['object']['sales_flat_shipment_track'], 'order_id', $order['entity_id']);
                if ($shipmentTrack) {
                    $track_number = isset($shipmentTrack['track_number']) ? $shipmentTrack['track_number'] : $shipmentTrack['number'];
                    $order_status_data['comment'] .= "<b>Shipment title: </b>" . $shipmentTrack['title'] . "<br /><b>Shipment track number: </b>" . $track_number;
                }
            }
            $order_status_data['is_customer_notified'] = ($row['is_customer_notified']) ? 1 : 0;
            $order_status_data['created_at'] = $row['created_at'];
            $order_status_data['updated_at'] = $row['created_at'];
            $this->_process->ordersComment($order_mage_id, $order_status_data);
        }
        //Invoice
        $orderInvoice = $this->getRowFromListByField($ordersExt['object']['sales_flat_invoice'], 'order_id', $order['entity_id']);
        if ($orderInvoice) {
            $invoiceData = array();
            $orderMage = Mage::getModel('sales/order')->load($order_mage_id);
            $bill_id = $orderMage->getBillingAddressId();
            $ship_id = $orderMage->getShippingAddressId();
            $store_id = ($orderInvoice['store_id']) ? $this->_notice['config']['languages'][$orderInvoice['store_id']] : $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
            $invoice_data = array(
                'store_id' => $store_id,
                'base_grand_total' => $orderInvoice['base_grand_total'],
                'shipping_tax_amount' => $orderInvoice['shipping_tax_amount'],
                'tax_amount' => $orderInvoice['tax_amount'],
                'base_tax_amount' => $orderInvoice['base_tax_amount'],
                'store_to_order_rate' => $orderInvoice['store_to_order_rate'],
                'base_shipping_tax_amount' => $orderInvoice['base_shipping_tax_amount'],
                'base_discount_amount' => $orderInvoice['base_discount_amount'],
                'base_to_order_rate' => $orderInvoice['base_to_order_rate'],
                'grand_total' => $orderInvoice['grand_total'],
                'shipping_amount' => $orderInvoice['shipping_amount'],
                'subtotal_incl_tax' => $orderInvoice['subtotal_incl_tax'],
                'base_subtotal_incl_tax' => $orderInvoice['base_subtotal_incl_tax'],
                'store_to_base_rate' => $orderInvoice['store_to_base_rate'],
                'base_shipping_amount' => $orderInvoice['base_shipping_amount'],
                'total_qty' => $orderInvoice['total_qty'],
                'base_to_global_rate' => $orderInvoice['base_to_global_rate'],
                'subtotal' => $orderInvoice['subtotal'],
                'base_subtotal' => $orderInvoice['base_subtotal'],
                'discount_amount' => $orderInvoice['discount_amount'],
                'order_id' => $order_mage_id,
                'state' => $orderInvoice['state'],
                'store_currency_code' => $orderInvoice['store_currency_code'],
                'order_currency_code' => $orderInvoice['order_currency_code'],
                'base_currency_code' => $orderInvoice['base_currency_code'],
                'global_currency_code' => $orderInvoice['global_currency_code'],
                'created_at' => $orderInvoice['created_at'],
                'updated_at' => $orderInvoice['updated_at'],
                'shipping_incl_tax' => $orderInvoice['shipping_incl_tax'],
                'base_shipping_incl_tax' => $orderInvoice['base_shipping_incl_tax'],
                'base_total_refunded' => $orderInvoice['base_total_refunded'],
                'discount_description' => $orderInvoice['discount_description'],
                'billing_address_id' => $bill_id? $bill_id : null,
                'shipping_address_id' => $ship_id ? $ship_id : null
            );
            $invoiceItems = $this->getListFromListByField($ordersExt['object']['sales_flat_invoice_item'], 'parent_id', $orderInvoice['entity_id']);
            $items = array();
            foreach ($invoiceItems as $row) {
                $item = array(
                    'base_price' => $row['base_price'],
                    'tax_amount' => $row['tax_amount'],
                    'base_row_total' => $row['base_row_total'],
                    'discount_amount' => $row['discount_amount'],
                    'row_total' => $row['row_total'],
                    'base_discount_amount' => $row['base_discount_amount'],
                    'price_incl_tax' => $row['price_incl_tax'],
                    'base_tax_amount' => $row['base_tax_amount'],
                    'base_price_incl_tax' => $row['base_price_incl_tax'],
                    'qty' => $row['qty'],
                    'price' => $row['price'],
                    'base_row_total_incl_tax' => $row['base_row_total_incl_tax'],
                    'row_total_incl_tax' => $row['row_total_incl_tax'],
                    'additional_data' => $row['additional_data'],
                    'description' => $row['description'],
                    'sku' => $row['sku'],
                    'name' => $row['name']
                );
                $product_id = $this->getMageIdProduct($row['product_id']);
                if ($product_id) {
                    $item['product_id'] = $product_id;
                }
                $items[] = $item;
            }
            $invoiceComments = $this->getListFromListByField($ordersExt['object']['sales_flat_invoice_comment'], 'parent_id', $orderInvoice['entity_id']);
            $comments = array();
            foreach ($invoiceComments as $row) {
                $comment = array(
                    'is_customer_notified' => $row['is_customer_notified'],
                    'is_visible_on_front' => $row['is_visible_on_front'],
                    'comment' => $row['comment'],
                    'created_at' => $row['created_at']
                );
                $comments[] = $comment;
            }
            $invoiceData['invoice'] = $invoice_data;
            $invoiceData['item'] = $items;
            $invoiceData['comment'] = $comments;
            $this->_process->ordersInvoice($order_mage_id, $invoiceData);
        }
    }
    
    /**
     * Query for get data use for import cart
     *
     * @return array : Response of connector
     */
    protected function _getCartsMainQuery() {
        $id_src = $this->_notice['carts']['id_src'];
        $limit = 10;//$this->_notice['setting']['carts'];
        $query = "SELECT * FROM _DBPRF_sales_flat_quote WHERE entity_id > {$id_src} ORDER BY entity_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import cart
     *
     * @param array $carts : Data of connector return for query function getCartsMainQuery
     * @return array
     */
    protected function _getCartsExtQuery($carts) {
        $cartIds = $this->duplicateFieldValueFromList($carts['object'], 'entity_id');
        $cart_id_query = $this->arrayToInCondition($cartIds);
        $ext_query = array(
            'sales_flat_quote_item' => "SELECT * FROM _DBPRF_sales_flat_quote_item WHERE quote_id IN {$cart_id_query} ORDER BY item_id ASC",
            'sales_flat_quote_address' => "SELECT * FROM _DBPRF_sales_flat_quote_address WHERE quote_id IN {$cart_id_query}",
            'sales_flat_quote_payment' => "SELECT * FROM _DBPRF_sales_flat_quote_payment WHERE quote_id IN {$cart_id_query}",
        );
        return $ext_query;
    }

    /**
     * Query for get data relation use for import cart
     *
     * @param array $carts : Data of connector return for query function getCartsMainQuery
     * @param array $cartsExt : Data of connector return for query function getCartsExtQuery
     * @return array
     */
    protected function _getCartsExtRelQuery($carts, $cartsExt) {
        $itemIds = $this->duplicateFieldValueFromList($cartsExt['object']['sales_flat_quote_item'], 'item_id');
        $item_ids_query = $this->arrayToInCondition($itemIds);
        $ext_rel_query = array(
            'sales_flat_quote_item_option' => "SELECT * FROm _DBPRF_sales_flat_quote_item_option WHERE item_id IN {$item_ids_query}",
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source cart main
     *
     * @param array $cart : One row of object in function getCartsMain
     * @param array $cartsExt : Data of function getCartsExt
     * @return int
     */
    public function getCartId($cart, $cartsExt) {
        return $cart['entity_id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $cart : One row of object in function getCartsMain
     * @param array $cartsExt : Data of function getCartsExt
     * @return array
     */
    public function convertCart($cart, $cartsExt) {
        if (LitExtension_CartMigration_Model_Custom::CART_CONVERT) {
            return $this->_custom->convertCartCustom($this, $cart, $cartsExt);
        }
        $cart = $this->cleanArrayKeys($cart);
        $data = array();
        $address_cart = $this->getListFromListByField($cartsExt['object']['sales_flat_quote_address'], 'quote_id', $cart['entity_id']);
        //Billing address
        $billing = $this->getRowFromListByField($address_cart, 'address_type', 'billing');
        $billing = $this->cleanArrayKeys($billing);

        $address_billing = $billing;
        unset($address_billing['address_id']);
        unset($address_billing['quote_id']);
        unset($address_billing['customer_address_id']);
        $customer_id = $this->getMageIdCustomer($billing['customer_id']);
        if ($customer_id && $billing['customer_id']) {
            $address_billing['customer_id'] = $customer_id;
        } else {
            $address_billing['customer_id'] = null;
        }
        $address_billing['region_id'] = $this->getRegionId($address_billing['region'], $address_billing['country_id']);

        //Shipping address
        $shipping = $this->getRowFromListByField($address_cart, 'address_type', 'shipping');
        $shipping = $this->cleanArrayKeys($shipping);
        
        $address_shipping = $shipping;
        unset($address_shipping['address_id']);
        unset($address_shipping['quote_id']);
        unset($address_shipping['customer_address_id']);
        $customer_id = $this->getMageIdCustomer($shipping['customer_id']);
        if ($customer_id && $shipping['customer_id']) {
            $address_shipping['customer_id'] = $customer_id;
        } else {
            $address_shipping['customer_id'] = null;
        }
        
        $address_shipping['region_id'] = $this->getRegionId($address_shipping['region'], $address_shipping['country_id']);

        //Row product item
        $items = array();
        $quoteProducts = $this->getListFromListByField($cartsExt['object']['sales_flat_quote_item'], 'quote_id', $cart['entity_id']);
        $i = 0;
        foreach ($quoteProducts as $key => $row) {
            $item = array();
            if (!$row['parent_item_id']) {
                $i = $key;
            }
            $row = $this->cleanArrayKeys($row);
            $item = $row;
            unset($item['item_id']);
            unset($item['quote_id']);
            unset($item['parent_item_id']);
            $store_id = ($row['store_id'] && isset($this->_notice['config']['languages'][$row['store_id']])) ? $this->_notice['config']['languages'][$row['store_id']] : $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
            $item['store_id'] = $store_id;
            $product_id = $this->getMageIdProduct($row['product_id']);
            if (!$product_id) continue;
            $item['product_id'] = $product_id;
            $item_options = array();
            $itemOptions = $this->getListFromListByField($cartsExt['object']['sales_flat_quote_item_option'], 'item_id', $row['item_id']);
            foreach ($itemOptions as $itemOption) {
                $item_option = array();
                $item_option['product_id'] = $product_id;
                $item_option['code'] = $itemOption['code'];
                $item_option['value'] = $itemOption['value'];
                $item_options[] = $item_option;
            }
            $item['item_options'] = $item_options;
            if (!$row['parent_item_id']) {
                $items[$key] = $item;
            } else {
                $items[$i]['children_item'][] = $item;
            }
        }
        $cart_data = array();
        $cart_data = $cart;
        unset($cart_data['entity_id']);
        $store_id = ($cart['store_id'] && isset($this->_notice['config']['languages'][$cart['store_id']])) ? $this->_notice['config']['languages'][$cart['store_id']] : $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $cart_data['store_id'] = $store_id;
        $customer_id = $this->getMageIdCustomer($cart['customer_id']);
        if ($customer_id) {
            $cart_data['customer_id'] = $customer_id;
            $cart_data['customer_is_guest'] = false;
        } else {
            $cart_data['customer_is_guest'] = true;
        }
        if (isset($this->_notice['config']['customer_group'][$cart['customer_group_id']])) {
            $cart_data['customer_group_id'] = $this->_notice['config']['customer_group'][$cart['customer_group_id']];
        } else {
            $cart_data['customer_group_id'] = 1;
        }
        $customer_tax_class = $this->getMageIdTaxCustomer($cart['customer_tax_class_id']);
        if ($customer_tax_class) {
            $cart_data['customer_tax_class_id'] = $customer_tax_class;
        } else {
            $cart_data['customer_tax_class_id'] = 0;
        }
        if ($cart['orig_order_id'] && $org_order_id = $this->getMageIdOrder($cart['orig_order_id'])) {
            $cart_data['orig_order_id'] = $org_order_id;
        } else {
            $cart_data['orig_order_id'] = 0;
        }
        $paymentDataSrc = $this->getRowFromListByField($cartsExt['object']['sales_flat_quote_payment'], 'quote_id', $cart['entity_id']);
        if ($paymentDataSrc) {
            $payment = array();
            $paymentDataSrc = $this->cleanArrayKeys($paymentDataSrc);
            $payment = $paymentDataSrc;
            unset($payment['payment_id']);
            unset($payment['quote_id']);
            $data['payment'] = $payment;
        }
        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['cart'] = $cart_data;
        $data['items'] = $items;
        $custom = $this->_custom->convertCartCustom($this, $cart, $cartsExt);
        if ($custom) {
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
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
            'review_store' => "SELECT * FROM _DBPRF_review_store WHERE review_id IN {$review_id_query} AND store_id != 0"
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
        $storeIds = $this->getListFromListByField($reviewsExt['object']['review_store'], 'review_id', $review['review_id']);
        $store_ids = array();
        foreach ($storeIds as $row) {
            if (isset($this->_notice['config']['languages'][$row['store_id']])) {
                $store_ids[] = $this->_notice['config']['languages'][$row['store_id']];
            }
        }
        $data = array();
        $review_detail = $this->getRowFromListByField($reviewsExt['object']['review_detail'], 'review_id', $review['review_id']);
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = $review['status_id'];
        $data['title'] = $review_detail['title'];
        $data['detail'] = $review_detail['detail'];
        $data['entity_id'] = 1;
        $data['stores'] = $store_ids;
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
    
    /**
     * Query for get main data use for import pages
     *
     * @return string
     */
    protected function _getPagesMainQuery() {
        $id_src = $this->_notice['pages']['id_src'];
        $limit = 10;//$this->_notice['setting']['pages'];
        $query = "SELECT * FROM _DBPRF_cms_page WHERE page_id > {$id_src} ORDER BY page_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import pages
     *
     * @param array $pages : Data of connector return for query function getPagesMainQuery
     * @return array
     */
    protected function _getPagesExtQuery($pages) {
        $pageIds = $this->duplicateFieldValueFromList($pages['object'], 'page_id');
        $page_id_query = $this->arrayToInCondition($pageIds);
        $ext_query = array(
            'page_store' => "SELECT * FROM _DBPRF_cms_page_store WHERE page_id IN {$page_id_query}",
        );
        return $ext_query;
    }
    
    /**
     * Query for get relation data use for import pages
     *
     * @param array $pages : Data of connector return for query function getPagesMainQuery
     * @param array $pagesExt : Data of connector return for query function getPagesExtQuery
     * @return array
     */
    protected function _getPagesExtRelQuery($pages, $pagesExt) {
        return array();
    }

    /**
     * Get primary key of source page main
     *
     * @param array $page : One row of object in function getPagesMain
     * @param array $pagesExt : Data of function getPagesExt
     * @return int
     */
    public function getPageId($page, $pagesExt) {
        return $page['page_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $page : One row of object in function getPagesMain
     * @param array $pagesExt : Data of function getPagesExt
     * @return array
     */
    public function convertPage($page, $pagesExt) {
        if (LitExtension_CartMigration_Model_Custom::PAGE_CONVERT) {
            return $this->_custom->convertPageCustom($this, $page, $pagesExt);
        }
        $data = $page;
        unset($data['page_id']);
        $storeIds = $this->getListFromListByField($pagesExt['object']['page_store'], 'page_id', $page['page_id']);
        $store_ids = array();
        foreach ($storeIds as $row) {
            if (isset($this->_notice['config']['languages'][$row['store_id']])) {
                $store_ids[] = $this->_notice['config']['languages'][$row['store_id']];
            }
        }
        $data['stores'] = $store_ids;
        $custom = $this->_custom->convertPageCustom($this, $page, $pagesExt);
        if ($custom) {
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }
    
    /**
     * Query for get main data use for import blocks
     *
     * @return string
     */
    protected function _getBlocksMainQuery() {
        $id_src = $this->_notice['blocks']['id_src'];
        $limit = 10;//$this->_notice['setting']['blocks'];
        $query = "SELECT * FROM _DBPRF_cms_block WHERE block_id > {$id_src} ORDER BY block_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import blocks
     *
     * @param array $blocks : Data of connector return for query function getBlocksMainQuery
     * @return array
     */
    protected function _getBlocksExtQuery($blocks) {
        $blockIds = $this->duplicateFieldValueFromList($blocks['object'], 'block_id');
        $block_id_query = $this->arrayToInCondition($blockIds);
        $ext_query = array(
            'block_store' => "SELECT * FROM _DBPRF_cms_block_store WHERE block_id IN {$block_id_query}",
        );
        return $ext_query;
    }
    
    /**
     * Query for get relation data use for import blocks
     *
     * @param array $blocks : Data of connector return for query function getBlocksMainQuery
     * @param array $blocksExt : Data of connector return for query function getBlocksExtQuery
     * @return array
     */
    protected function _getBlocksExtRelQuery($blocks, $blocksExt) {
        return array();
    }

    /**
     * Get primary key of source block main
     *
     * @param array $block : One row of object in function getBlocksMain
     * @param array $blocksExt : Data of function getBlocksExt
     * @return int
     */
    public function getBlockId($block, $blocksExt) {
        return $block['block_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $block : One row of object in function getBlocksMain
     * @param array $blocksExt : Data of function getBlocksExt
     * @return array
     */
    public function convertBlock($block, $blocksExt) {
        if (LitExtension_CartMigration_Model_Custom::BLOCK_CONVERT) {
            return $this->_custom->convertBlockCustom($this, $block, $blocksExt);
        }
        $data = $block;
        unset($data['block_id']);
        $store_ids = array();
        $storeIds = $this->getListFromListByField($blocksExt['object']['block_store'], 'block_id', $block['block_id']);
        foreach ($storeIds as $row) {
            if (isset($this->_notice['config']['languages'][$row['store_id']])) {
                $store_ids[] = $this->_notice['config']['languages'][$row['store_id']];
            }
        }
        $data['stores'] = $store_ids;
        $custom = $this->_custom->convertBlockCustom($this, $block, $blocksExt);
        if ($custom) {
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }
    
    /**
     * Query for get main data use for import widgets
     *
     * @return string
     */
    protected function _getWidgetsMainQuery() {
        $id_src = $this->_notice['widgets']['id_src'];
        $limit = $this->_notice['setting']['widgets'];
        $query = "SELECT * FROM _DBPRF_widget_instance WHERE instance_id > {$id_src} ORDER BY instance_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import widgets
     *
     * @param array $widgets : Data of connector return for query function getWidgetsMainQuery
     * @return array
     */
    protected function _getWidgetsExtQuery($widgets) {
        $widgetIds = $this->duplicateFieldValueFromList($widgets['object'], 'instance_id');
        $widget_id_query = $this->arrayToInCondition($widgetIds);
        $ext_query = array(
            'widget_instance_page' => "SELECT * FROM _DBPRF_widget_instance_page WHERE instance_id IN {$widget_id_query}",
        );
        return $ext_query;
    }
    
    /**
     * Query for get relation data use for import widgets
     *
     * @param array $widgets : Data of connector return for query function getWidgetsMainQuery
     * @param array $widgetsExt : Data of connector return for query function getWidgetsExtQuery
     * @return array
     */
    protected function _getWidgetsExtRelQuery($widgets, $widgetsExt) {
        $pageIds = $this->duplicateFieldValueFromList($widgetsExt['object']['widget_instance_page'], 'page_id');
        $page_id_query = $this->arrayToInCondition($pageIds);
        $ext_rel_query = array(
            'widget_instance_page_layout' => "SELECT * FROM _DBPRF_widget_instance_page_layout WHERE page_id IN {$page_id_query}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source widget main
     *
     * @param array $widget : One row of object in function getWidgetsMain
     * @param array $widgetsExt : Data of function getWidgetsExt
     * @return int
     */
    public function getWidgetId($widget, $widgetsExt) {
        return $widget['instance_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $widget : One row of object in function getWidgetsMain
     * @param array $widgetsExt : Data of function getWidgetsExt
     * @return array
     */
    public function convertWidget($widget, $widgetsExt) {//??
        if (LitExtension_CartMigration_Model_Custom::WIDGET_CONVERT) {
            return $this->_custom->convertWidgetCustom($this, $widget, $widgetsExt);
        }
        $data = $widget;
        unset($data['instance_id']);
        $store_ids = array();
        $storeIds = $this->getListFromListByField($widgetsExt['object']['block_store'], 'block_id', $widget['block_id']);
        foreach ($storeIds as $row) {
            if (isset($this->_notice['config']['languages'][$row['store_id']])) {
                $store_ids[] = $this->_notice['config']['languages'][$row['store_id']];
            }
        }
        $data['stores'] = $store_ids;
        $custom = $this->_custom->convertWidgetCustom($this, $widget, $widgetsExt);
        if ($custom) {
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }
    
    /**
     * Query for get main data use for import transactions
     *
     * @return string
     */
    protected function _getTransactionsMainQuery() {
        $id_src = $this->_notice['transactions']['id_src'];
        $limit = 10;//$this->_notice['setting']['transactions'];
        $query = "SELECT * FROM _DBPRF_core_email_template WHERE template_id > {$id_src} ORDER BY template_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import transactions
     *
     * @param array $transactions : Data of connector return for query function getTransactionsMainQuery
     * @return array
     */
    protected function _getTransactionsExtQuery($transactions) {
        return array();
    }
    
    /**
     * Query for get relation data use for import transactions
     *
     * @param array $transactions : Data of connector return for query function getTransactionsMainQuery
     * @param array $transactionsExt : Data of connector return for query function getTransactionsExtQuery
     * @return array
     */
    protected function _getTransactionsExtRelQuery($transactions, $transactionsExt) {
        return array();
    }

    /**
     * Get primary key of source transaction main
     *
     * @param array $transaction : One row of object in function getTransactionsMain
     * @param array $transactionsExt : Data of function getTransactionsExt
     * @return int
     */
    public function getTransactionId($transaction, $transactionsExt) {
        return $transaction['template_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $transaction : One row of object in function getTransactionsMain
     * @param array $transactionsExt : Data of function getTransactionsExt
     * @return array
     */
    public function convertTransaction($transaction, $transactionsExt) {
        if (LitExtension_CartMigration_Model_Custom::TRANSACTION_CONVERT) {
            return $this->_custom->convertTransactionCustom($this, $transaction, $transactionsExt);
        }
        $data = $transaction;
        unset($data['template_id']);
        $custom = $this->_custom->convertTransactionCustom($this, $transaction, $transactionsExt);
        if ($custom) {
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }
    
    /**
     * Query for get main data use for import rules
     *
     * @return string
     */
    protected function _getRulesMainQuery() {
        $id_src = $this->_notice['rules']['id_src'];
        $limit = 10;//$this->_notice['setting']['rules'];
        $query = "SELECT * FROM _DBPRF_salesrule WHERE rule_id > {$id_src} ORDER BY rule_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import rules
     *
     * @param array $rules : Data of connector return for query function getRulesMainQuery
     * @return array
     */
    protected function _getRulesExtQuery($rules) {
        $ruleIds = $this->duplicateFieldValueFromList($rules['object'], 'rule_id');
        $rule_id_query = $this->arrayToInCondition($ruleIds);
        $ext_query = array(
            'salesrule_coupon' => "SELECT * FROM _DBPRF_salesrule_coupon WHERE rule_id IN {$rule_id_query}",
            'salesrule_customer_group' => "SELECT * FROM _DBPRF_salesrule_customer_group WHERE rule_id IN {$rule_id_query}",
            'salesrule_label' => "SELECT * FROM _DBPRF_salesrule_label WHERE rule_id IN {$rule_id_query}",
            'salesrule_website' => "SELECT * FROM _DBPRF_salesrule_website WHERE rule_id IN {$rule_id_query}",
            'salesrule_product_attribute' => "SELECT * FROM _DBPRF_salesrule_product_attribute WHERE rule_id IN {$rule_id_query}"
        );
        return $ext_query;
    }
    
    /**
     * Query for get relation data use for import rules
     *
     * @param array $rules : Data of connector return for query function getRulesMainQuery
     * @param array $rulesExt : Data of connector return for query function getRulesExtQuery
     * @return array
     */
    protected function _getRulesExtRelQuery($rules, $rulesExt) {
        $attrIds = $this->duplicateFieldValueFromList($rulesExt['object']['salesrule_product_attribute'], 'attribute_id');
        $attr_ids_query = $this->arrayToInCondition($attrIds);
        $ext_rel_query = array(
            'eav_attribute' => "SELECT * FROM _DBPRF_eav_attribute WHERE entity_type_id = {$this->_notice['extend']['catalog_product']} AND attribute_id IN {$attr_ids_query}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source rule main
     *
     * @param array $rule : One row of object in function getRulesMain
     * @param array $rulesExt : Data of function getRulesExt
     * @return int
     */
    public function getRuleId($rule, $rulesExt) {
        return $rule['rule_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $rule : One row of object in function getRulesMain
     * @param array $rulesExt : Data of function getRulesExt
     * @return array
     */
    public function convertRule($rule, $rulesExt) {
        if (LitExtension_CartMigration_Model_Custom::RULE_CONVERT) {
            return $this->_custom->convertRuleCustom($this, $rule, $rulesExt);
        }
        $data['salesrule'] = $rule;
        unset($data['salesrule']['rule_id']);
        $ruleCoupons = array();
        $coupons = $this->getListFromListByField($rulesExt['object']['salesrule_coupon'], 'rule_id', $rule['rule_id']);
        foreach ($coupons as $coupon) {
            unset($coupon['coupon_id']);
            $ruleCoupons[] = $coupon;
        }
        $data['salesrule_coupon'] = $ruleCoupons;
        
        $customerGroups = array();
        $groups = $this->getListFromListByField($rulesExt['object']['salesrule_customer_group'], 'rule_id', $rule['rule_id']);
        $customer_groups = $this->duplicateFieldValueFromList($groups, 'customer_group_id');
        foreach($customer_groups as $group) {
            if (isset($this->_notice['config']['customer_group'][$group])) {
                $customerGroups[] = $this->_notice['config']['customer_group'][$group];
            }
        }
        $data['salesrule_customer_group'] = $customerGroups;
        
        $ruleLabels = array();
        $labels = $this->getListFromListByField($rulesExt['object']['salesrule_label'], 'rule_id', $rule['rule_id']);
        foreach ($labels as $label) {
            if (!isset($this->_notice['config']['languages'][$label['store_id']]) && $label['store_id'] != '0') continue;
            $label['store_id'] = $this->_notice['config']['languages'][$label['store_id']];
            unset($label['label_id']);
            $ruleLabels[] = $label;
        }
        $data['salesrule_label'] = $ruleLabels;
        
        $productAttrs = array();
        $attrs = $this->getListFromListByField($rulesExt['object']['salesrule_product_attribute'], 'rule_id', $rule['rule_id']);
        foreach ($attrs as $attr) {
            if (!isset($this->_notice['config']['customer_group'][$attr['customer_group_id']])) continue;
            $attr['website_id'] = $this->_notice['config']['website_id'];
            $attr['customer_group_id'] = $this->_notice['config']['customer_group'][$attr['customer_group_id']];
            $attr_code = $this->getRowValueFromListByField($rulesExt['object']['eav_attribute'], 'attribute_id', $attr['attribute_id'], 'attribute_code');
            $attr_target_id = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', $attr_code);
            if (!$attr_target_id) {
                return array(
                    'result' => 'warning',
                    'msg' => $this->consoleWarning("Shopping Cart Price Rule ID = {$rule['rule_id']} import failed. Error: Product's attribute do not exist in Target Site!"),
                );
            }
            $attr['attribute_id'] = $attr_target_id;
            unset($attr['rule_id']);
            $productAttrs[] = $attr;
        }
        $data['salesrule_product_attribute'] = $productAttrs;
        $custom = $this->_custom->convertRuleCustom($this, $rule, $rulesExt);
        if ($custom) {
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }
    
    /**
     * Query for get main data use for import cartrules
     *
     * @return string
     */
    protected function _getCartrulesMainQuery() {
        $id_src = $this->_notice['cartrules']['id_src'];
        $limit = 10;//$this->_notice['setting']['rules'];
        $query = "SELECT * FROM _DBPRF_catalogrule WHERE rule_id > {$id_src} ORDER BY rule_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import cartrules
     *
     * @param array $rules : Data of connector return for query function getCartrulesMainQuery
     * @return array
     */
    protected function _getCartrulesExtQuery($rules) {
        $ruleIds = $this->duplicateFieldValueFromList($rules['object'], 'rule_id');
        $rule_id_query = $this->arrayToInCondition($ruleIds);
        $ext_query = array(
            'catalogrule_group_website' => "SELECT * FROM _DBPRF_catalogrule_group_website WHERE rule_id IN {$rule_id_query}",
            'catalogrule_customer_group' => "SELECT * FROM _DBPRF_catalogrule_customer_group WHERE rule_id IN {$rule_id_query}",
            'catalogrule_product' => "SELECT * FROM _DBPRF_catalogrule_product WHERE rule_id IN {$rule_id_query}",
            'catalogrule_website' => "SELECT * FROM _DBPRF_catalogrule_website WHERE rule_id IN {$rule_id_query}",
        );
        return $ext_query;
    }
    
    /**
     * Query for get relation data use for import cartrules
     *
     * @param array $rules : Data of connector return for query function getCartrulesMainQuery
     * @param array $rulesExt : Data of connector return for query function getCartrulesExtQuery
     * @return array
     */
    protected function _getCartrulesExtRelQuery($rules, $rulesExt) {
        $prdIds = $this->duplicateFieldValueFromList($rulesExt['object']['catalogrule_product'], 'product_id');
        $prd_ids_query = $this->arrayToInCondition($prdIds);
        $ext_rel_query = array(
            'catalogrule_product_price' => "SELECT * FROM _DBPRF_catalogrule_product_price WHERE product_id IN {$prd_ids_query}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source cartrule main
     *
     * @param array $rule : One row of object in function getCartrulesMain
     * @param array $rulesExt : Data of function getCartrulesExt
     * @return int
     */
    public function getCartruleId($rule, $rulesExt) {
        return $rule['rule_id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $rule : One row of object in function getCartrulesMain
     * @param array $rulesExt : Data of function getCartrulesExt
     * @return array
     */
    public function convertCartrule($rule, $rulesExt) {
        if (LitExtension_CartMigration_Model_Custom::CARTRULE_CONVERT) {
            return $this->_custom->convertCartruleCustom($this, $rule, $rulesExt);
        }
        $data['catalogrule'] = $rule;
        unset($data['catalogrule']['rule_id']);
        $ruleGroups = array();
        $groups = $this->getListFromListByField($rulesExt['object']['catalogrule_customer_group'], 'rule_id', $rule['rule_id']);
        foreach ($groups as $group) {
            if (isset($this->_notice['config']['customer_group'][$group['customer_group_id']])) {
                $ruleGroups[] = $this->_notice['config']['customer_group'][$group['customer_group_id']];
            }
        }
        $data['customer_group'] = $ruleGroups;
        
        /*$ruleProducts = array();
        $products = $this->getListFromListByField($rulesExt['object']['catalogrule_product'], 'rule_id', $rule['rule_id']);
        foreach($products as $product) {
            if (!isset($this->_notice['config']['customer_group'][$product['customer_group_id']]) || !$product_id_desc = $this->getMageIdProduct($product['product_id'])) continue;
            unset($product['rule_product_id']);
            $product['customer_group_id'] = $this->_notice['config']['customer_group'][$product['customer_group_id']];
            $product['product_id'] = $product_id_desc;
            $product['website_id'] = $this->_notice['config']['website_id'];
            $ruleProducts[] = $product;
        }
        $data['product'] = $ruleProducts;*/
        
        $custom = $this->_custom->convertCartruleCustom($this, $rule, $rulesExt);
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

    protected function _makeAttributeImport($attribute, $option, $attribute_set, $info, $entity_type_id) {
        $apply = explode(',', $info['apply_to']);
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => substr($attribute['attribute_code'], 0, 30),
            'attribute_set_id' => $attribute_set,
            'frontend_input' => $attribute['frontend_input'],
            'frontend_label' => array($attribute['frontend_label']),
            'apply_to' => $apply,
            'is_visible_on_front' => (int) $info['is_visible_on_front'],
            'is_global' => (int) $info['is_configurable'] ? (int) $info['is_configurable'] : (int) $info['is_global'],
            'is_configurable' => (int) $info['is_configurable'],
            'option' => array(
                'value' => ($option) ? $option : array()
            )
        );
        $edit = array(
            'apply_to' => $apply,
            'is_visible_on_front' => (int) $info['is_visible_on_front'],
            'is_global' => (int) $info['is_configurable'] ? (int) $info['is_configurable'] : (int) $info['is_global'],
            'is_configurable' => (int) $info['is_configurable'],
        );
        $result['config'] = $config;
        $result['edit'] = $edit;
        return $result;
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
    
    protected function positionSort(array $arr) {
        $sorted = false;
        while (false === $sorted) {
            $sorted = true;
            for ($i = 0; $i < count($arr) - 1; ++$i) {
                $current = $arr[$i];
                $next = $arr[$i + 1];
                if ($next['position'] < $current['position']) {
                    $arr[$i] = $next;
                    $arr[$i + 1] = $current;
                    $sorted = false;
                }
            }
        }
        return $arr;
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
        $query = "SELECT eao.* FROM _DBPRF_eav_attribute as ea
                                                LEFT JOIN _DBPRF_eav_attribute_option as eao ON ea.attribute_id = eao.attribute_id
                                                WHERE attribute_code = 'manufacturer' ORDER BY eao.option_id ASC";
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
        $query = "SELECT cpe.* FROM _DBPRF_catalog_product_entity as cpe LEFT JOIN _DBPRF_catalog_product_relation as cpr ON cpe.entity_id = cpr.child_id WHERE cpr.child_id IS NULL ORDER BY cpe.entity_id ASC";
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
        $query = "SELECT * FROM _DBPRF_sales_flat_order ORDER BY entity_id ASC";
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
