<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Prestashopv13 extends LitExtension_CartMigration_Model_Cart {

    public function __construct() {
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax WHERE id_tax > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturer WHERE id_manufacturer > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE id_category > {$this->_notice['categories']['id_src']} AND id_category > 1",
                'products' => "SELECT COUNT(1) FROM _DBPRF_product WHERE id_product > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE id_customer > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE id_order > {$this->_notice['orders']['id_src']}",
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
                "languages" => "SELECT cfg.*, lg.* FROM _DBPRF_lang AS lg LEFT JOIN _DBPRF_configuration AS cfg ON lg.id_lang = cfg.value WHERE cfg.name = 'PS_LANG_DEFAULT'",
                "currencies" => "SELECT cfg.*, cur.* FROM _DBPRF_currency AS cur LEFT JOIN _DBPRF_configuration AS cfg ON cur.id_currency = cfg.value WHERE cfg.name = 'PS_CURRENCY_DEFAULT'"
            ))
        ));
        if (!$default_cfg || $default_cfg['result'] != 'success') {
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['id_lang']) ? $object['languages']['0']['id_lang'] : 1;
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['id_currency']) ? $object['currencies']['0']['id_currency'] : 1;
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_lang WHERE active = 1",
                "currencies" => "SELECT * FROM _DBPRF_currency",
                "orders_status" => "SELECT * FROM _DBPRF_order_state_lang WHERE id_lang = '{$this->_notice['config']['default_lang']}'",
                "group_lang" => "SELECT * FROM _DBPRF_group_lang WHERE id_lang = '{$this->_notice['config']['default_lang']}'"
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
            $lang_id = $language_row['id_lang'];
            $lang_name = $language_row['name'] . "(" . $language_row['iso_code'] . ")";
            $language_data[$lang_id] = $lang_name;
        }
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['id_currency'];
            $currency_name = $currency_row['name'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['id_order_state'];
            $order_status_name = $order_status_row['name'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        foreach($obj['group_lang'] as $cus_status_row){
            $cus_status_id = $cus_status_row['id_group'];
            $cus_status_name = $cus_status_row['name'];
            $customer_group_data[$cus_status_id] = $cus_status_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['customer_group_data'] = $customer_group_data;
        $this->_notice['extend']['simple_product'] = false;
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
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders');
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax WHERE id_tax > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturer WHERE id_manufacturer > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE id_category > {$this->_notice['categories']['id_src']} AND id_category > 1",
                'products' => "SELECT COUNT(1) FROM _DBPRF_product WHERE id_product > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE id_customer > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE id_order > {$this->_notice['orders']['id_src']}",
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
            'query' => "SELECT * FROM _DBPRF_currency"
        ));
        if ($currencies && $currencies['result'] == 'success') {
            $data = array();
            foreach ($currencies['object'] as $currency) {
                $currency_id = $currency['id_currency'];
                $currency_value = $currency['conversion_rate'];
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
        $query = "SELECT * FROM _DBPRF_tax WHERE id_tax > {$id_src} ORDER BY id_tax ASC LIMIT {$limit}";
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
        $taxRuleIds = $this->duplicateFieldValueFromList($taxes['object'], 'id_tax');
        $tax_rule_id_con = $this->arrayToInCondition($taxRuleIds);
        $ext_query = array(
            'tax_lang' => "SELECT * FROM _DBPRF_tax_lang WHERE id_tax IN {$tax_rule_id_con}",
            'tax_state' => "SELECT * FROM _DBPRF_tax_state WHERE id_tax IN {$tax_rule_id_con}",
            'tax_zone' => "SELECT * FROM _DBPRF_tax_zone WHERE id_tax IN {$tax_rule_id_con}",
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
            $zoneIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_zone'], 'id_zone');
            $zone_query = $this->arrayToInCondition($zoneIds);
            $states = $this->duplicateFieldValueFromList($taxesExt['object']['tax_state'], 'id_state');
            $states_query = $this->arrayToInCondition($states);
            $ext_rel_query = array(
                'zone' => "SELECT * FROM _DBPRF_zone WHERE id_zone IN {$zone_query}",
                'state' => "SELECT * FROM _DBPRF_state WHERE id_state IN {$states_query}"
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
            $countryIds = $this->duplicateFieldValueFromList($taxesExt['object']['state'], 'id_country');
            $countries_query = $this->arrayToInCondition($countryIds);
            $ext_rel_rel_query = array(
                'country' => "SELECT * FROM _DBPRF_country WHERE id_country IN {$countries_query} OR id_zone IN {$zone_query}",
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
        return $tax['id_tax'];
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
        $tax_product = $this->getListFromListByField($taxesExt['object']['tax_lang'], 'id_tax', $tax['id_tax']);
        $tax_product_name = $this->getRowValueFromListByField($tax_product, 'id_lang', $this->_notice['config']['default_lang'], 'name');
        $tax_pro_data = array(
            'class_name' => $tax_product_name
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id_tax'], $tax_pro_ipt['mage_id']);
        }
        $taxZone = $this->getListFromListByField($taxesExt['object']['tax_state'], 'id_tax', $tax['id_tax']);
        if ($taxZone) {
            foreach ($taxZone as $tax_zone) {
                $id_country = $this->getRowValueFromListByField($taxesExt['object']['state'], 'id_state', $tax_zone['id_state'], 'id_country');
                $country_code = $this->getRowValueFromListByField($taxesExt['object']['country'], 'id_country', $id_country, 'iso_code');
                $state_name = $this->getRowFromListByField($taxesExt['object']['state'], 'id_state', $tax_zone['id_state']);
                if (!$country_code) {
                    continue;
                }
                $tax_rate_data = array();
                $code = $country_code . "-" . $state_name['iso_code'] . "-" . $tax_product_name;
                $tax_rate_data['code'] = $this->createTaxRateCode($code);
                $tax_rate_data['tax_country_id'] = $country_code;
                $tax_rate_data['tax_region_id'] = $this->getRegionId($state_name['name'], $country_code);
                $tax_rate_data['zip_is_range'] = 0;
                $tax_rate_data['tax_postcode'] = "*";
                $tax_rate_data['rate'] = $tax['rate'];
                $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                if ($tax_rate_ipt['result'] == 'success') {
                    $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                }
            }
        }
        $taxSuper = $this->getListFromListByField($taxesExt['object']['tax_zone'], 'id_tax', $tax['id_tax']);
        if ($taxSuper) {
            foreach ($taxSuper as $super) {
                $taxCountries = $this->getListFromListByField($taxesExt['object']['country'], 'id_zone', $super['id_zone']);
                if ($taxCountries) {
                    foreach ($taxCountries as $tax_country) {
                        $tax_rate_data = array();
                        $code = $tax_country['iso_code'] . "-All States-" . $tax_product_name;
                        $tax_rate_data['code'] = $this->createTaxRateCode($code);
                        $tax_rate_data['tax_country_id'] = $tax_country['iso_code'];
                        $tax_rate_data['tax_region_id'] = 0;
                        $tax_rate_data['zip_is_range'] = 0;
                        $tax_rate_data['tax_postcode'] = "*";
                        $tax_rate_data['rate'] = $tax['rate'];
                        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                        if ($tax_rate_ipt['result'] == 'success') {
                            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                        }
                    }
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax_product_name);
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
        $query = "SELECT * FROM _DBPRF_manufacturer WHERE id_manufacturer > {$id_src} ORDER BY id_manufacturer ASC LIMIT {$limit}";
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
        return $manufacturer['id_manufacturer'];
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
            0 => $manufacturer['name']
        );
        foreach ($this->_notice['config']['languages'] as $store_id) {
            $manufacturer['value']['option'][$store_id] = $manufacturer['name'];
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
        $query = "SELECT * FROM _DBPRF_category WHERE id_category > {$id_src} AND id_category > 1 ORDER BY id_category ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories) {
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'id_category');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'categories_lang' => "SELECT * FROM _DBPRF_category_lang WHERE id_category IN {$cat_id_con}"
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
        return $category['id_category'];
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
        if (!$category['id_parent'] || $category['id_parent'] == 1 || $category['id_parent'] == $category['id_category']) {
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['id_parent']);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($category['id_parent']);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['id_category']} import failed. Error: Could not import parent category id = {$category['id_parent']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['categories_lang'], 'id_category', $category['id_category']);
        $category_lang = $this->getRowFromListByField($catDesc, 'id_lang', $this->_notice['config']['default_lang']);
        $cat_data['name'] = $category_lang['name'];
        $cat_data['description'] = $category_lang['description'];
        $cat_data['meta_title'] = $category_lang['meta_title'];
        $cat_data['meta_keywords'] = $category_lang['meta_keywords'];
        $cat_data['meta_description'] = $category_lang['meta_description'];
        $image = $this->_getCategoryImagePath($category['id_category']);
        $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $image['image'], 'catalog/category');
        $cat_data['image'] = $img_path;
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['active'];
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            $store_data = array();
            $store_lang = $this->getRowFromListByField($catDesc, 'id_lang', $lang_id);
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_lang) {
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_lang['name'];
                $store_data['description'] = $store_lang['description'];
                $store_data['meta_title'] = $store_lang['meta_title'];
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
        $query = "SELECT * FROM _DBPRF_product WHERE id_product > {$id_src} ORDER BY id_product ASC LIMIT {$limit}";
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
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'id_product');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'product_lang' => "SELECT * FROM _DBPRF_product_lang WHERE id_product IN {$pro_ids_query}",
            'category_product' => "SELECT * FROM _DBPRF_category_product WHERE id_product IN {$pro_ids_query}",
            'image' => "SELECT * FROM _DBPRF_image WHERE id_product IN {$pro_ids_query}",
            'product_attribute' => "SELECT * FROM _DBPRF_product_attribute WHERE id_product IN {$pro_ids_query}",
            'feature_product' => "SELECT * FROM _DBPRF_feature_product WHERE id_product IN {$pro_ids_query}",
            'discount_quantity' => "SELECT * FROM _DBPRF_discount_quantity WHERE id_product IN {$pro_ids_query}",
            'product_tag' => "SELECT * FROM _DBPRF_product_tag WHERE id_product IN {$pro_ids_query}"
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
        //ExtsRel
        $productAttrIds = $this->duplicateFieldValueFromList($productsExt['object']['product_attribute'], 'id_product_attribute');
        $featuresIds = $this->duplicateFieldValueFromList($productsExt['object']['feature_product'], 'id_feature');
        $featureValsIds = $this->duplicateFieldValueFromList($productsExt['object']['feature_product'], 'id_feature_value');
        $tagIds = $this->duplicateFieldValueFromList($productsExt['object']['product_tag'], 'id_tag');
        $product_attr_ids_query = $this->arrayToInCondition($productAttrIds);
        $feature_ids_query = $this->arrayToInCondition($featuresIds);
        $feature_val_ids_query = $this->arrayToInCondition($featureValsIds);
        $tag_ids_query = $this->arrayToInCondition($tagIds);
        $ext_rel_query = array(
            'product_attribute_combination' => "SELECT pac.*, a.*
                                                    FROM _DBPRF_product_attribute_combination as pac
                                                        LEFT JOIN _DBPRF_attribute as a ON a.id_attribute = pac.id_attribute
                                                            WHERE pac.id_product_attribute IN {$product_attr_ids_query}",
            'feature' => "SELECT fl.* FROM _DBPRF_feature as f LEFT JOIN _DBPRF_feature_lang as fl ON fl.id_feature = f.id_feature WHERE f.id_feature IN {$feature_ids_query}",
            'feature_value' => "SELECT fv.*, fvl.*, fvl.value as name FROM _DBPRF_feature_value as fv LEFT JOIN _DBPRF_feature_value_lang as fvl ON fvl.id_feature_value = fv.id_feature_value WHERE fv.id_feature_value IN {$feature_val_ids_query}",
            'tag' => "SELECT * FROM _DBPRF_tag WHERE id_tag IN {$tag_ids_query} AND id_lang = {$this->_notice['config']['default_lang']}"
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
        $attributes = $this->duplicateFieldValueFromList($productsExtRel['object']['product_attribute_combination'], 'id_attribute');
        $attribute_group = $this->duplicateFieldValueFromList($productsExtRel['object']['product_attribute_combination'], 'id_attribute_group');
        $attr_ids_query = $this->arrayToInCondition($attributes);
        $attr_group_ids_query = $this->arrayToInCondition($attribute_group);
        $ext_rel_rel_query = array(
            'attribute_group_lang' => "SELECT * FROM _DBPRF_attribute_group_lang WHERE id_attribute_group IN {$attr_group_ids_query}",
            'attribute_lang' => "SELECT * FROM _DBPRF_attribute_lang WHERE id_attribute IN {$attr_ids_query}",
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
        return $product['id_product'];
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
        if ($this->_notice['extend']['simple_product']) {
            $result['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        } else {
            $children_product = $this->_getChildrenProduct($product, $productsExt);
            if (!$children_product) {
                $result['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            } else {
                $result['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
                $config_data = $this->_importChildrenProduct($product['id_product'], $children_product, $productsExt);
                if (!$config_data) {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Product ID = {$product['id_product']} import failed. Error: Product attribute ccould not create!"),
                    );
                }
                $result = array_merge($config_data, $result);
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
        $features = $this->getListFromListByField($productsExt['object']['feature_product'], 'id_product', $product['id_product']);
        if ($features) {
            $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
            $attribute_set_id = $this->_notice['config']['attribute_set_id'];
            $store_view = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE;
            foreach ($features as $row) {
                $attr = $this->getListFromListByField($productsExt['object']['feature'], 'id_feature', $row['id_feature']);
                $attr_val = $this->getListFromListByField($productsExt['object']['feature_value'], 'id_feature_value', $row['id_feature_value']);
                $attr_import = $this->_makeAttributeImport($attr, $attr_val, $entity_type_id, $attribute_set_id, $store_view);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if(!$attr_after) return false;
                $this->setProAttrSelect($entity_type_id, $attr_after['attribute_id'], $product_mage_id, $attr_after['option_ids']['option_0']);
            }
        }
        //Simple options
        if ($this->_notice['extend']['simple_product']) {
            $iPAs = $this->getListFromListByField($productsExt['object']['product_attribute'], 'id_product', $product['id_product']);
            if ($iPAs) {
                $opt_data = array();
                $id_product_attributes = $this->duplicateFieldValueFromList($iPAs, 'id_product_attribute');
                $iAs = $this->getListFromListByListField($productsExt['object']['product_attribute_combination'], 'id_product_attribute', $id_product_attributes);
                $attrGroups = $this->duplicateFieldValueFromList($iAs, 'id_attribute_group');
                foreach ($attrGroups as $group_id) {
                    $group_name = $this->getListFromListByField($productsExt['object']['attribute_group_lang'], 'id_attribute_group', $group_id);
                    if (!$group_name) {
                        continue;
                    }
                    $option = array(
                        'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType('drop_down'),
                        'type' => 'drop_down',
                        'is_require' => 1,
                        'title' => $this->getRowValueFromListByField($group_name, 'id_lang', $this->_notice['config']['default_lang'], 'name')
                    );
                    $values = array();
                    $attrOptions = $this->getListFromListByField($iAs, 'id_attribute_group', $group_id);
                    $attrIds = $this->duplicateFieldValueFromList($attrOptions, 'id_attribute');
                    foreach ($attrIds as $attr_id) {
                        $attr_name = $this->getListFromListByField($productsExt['object']['attribute_lang'], 'id_attribute', $attr_id);
                        $value = array(
                            'option_type_id' => -1,
                            'title' => $this->getRowValueFromListByField($attr_name, 'id_lang', $this->_notice['config']['default_lang'], 'name'),
                            'price' => 0,
                            'price_type' => 'fixed',
                        );
                        $values[] = $value;
                    }
                    $option['values'] = (isset($values)) ? $values : '';
                    $opt_data[] = $option;
                }
                $this->importProductOption($product_mage_id, $opt_data);
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
        $query = "SELECT * FROM _DBPRF_customer WHERE id_customer > {$id_src} ORDER BY id_customer ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers) {
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'id_customer');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'address' => "SELECT a.*, c.*, z.*, z.name as zone_name, c.iso_code as country_code
                                            FROM _DBPRF_address AS a
                                                LEFT JOIN _DBPRF_country AS c ON a.id_country = c.id_country
                                                LEFT JOIN _DBPRF_state AS z ON a.id_state = z.id_state
                                            WHERE a.id_customer IN {$customer_ids_query}"
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
        return $customer['id_customer'];
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
            $cus_data['id'] = $customer['id_customer'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['firstname'];
        $cus_data['lastname'] = $customer['lastname'];
        $cus_data['gender'] = $customer['id_gender'];
        $cus_data['dob'] = $customer['birthday'] != '0000-00-00' ? $customer['birthday'] : null;
        $cus_data['created_at'] = $customer['date_add'];
        $cus_data['is_subscribed'] = $customer['newsletter'];
        $cus_data['group_id'] = isset($customer['id_default_group']) ? isset($this->_notice['config']['customer_group'][$customer['id_default_group']]) ? $this->_notice['config']['customer_group'][$customer['id_default_group']] : 1 : 1;
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['passwd'] . ":" . $this->_notice['extend']['cookie_key']);
        $cusAdd = $this->getListFromListByField($customersExt['object']['address'], 'id_customer', $customer['id_customer']);
        if ($cusAdd) {
            foreach ($cusAdd as $key => $cus_add) {
                $address = array();
                $address['firstname'] = $cus_add['firstname'];
                $address['lastname'] = $cus_add['lastname'];
                $address['country_id'] = $cus_add['country_code'];
                $address['street'] = $cus_add['address1'] . "\n" . $cus_add['address2'];
                $address['postcode'] = $cus_add['postcode'];
                $address['city'] = $cus_add['city'];
                $address['telephone'] = ($cus_add['phone']) ? $cus_add['phone'] : $cus_add['phone_mobile'];
                $address['company'] = $cus_add['company'];
                $address['fax'] = '';
                $address['vat_id'] = $cus_add['vat_number'];
                if ($cus_add['id_state'] != 0) {
                    $region_id = $this->getRegionId($cus_add['zone_name'], $cus_add['country_code']);
                    if ($region_id) {
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['zone_name'];
                } else {
                    $address['region'] = $cus_add['address2'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if ($address_ipt['result'] == 'success' && $key == 0) {
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
        $query = "SELECT * FROM _DBPRF_orders WHERE id_order > {$id_src} ORDER BY id_order ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    protected function _getOrdersExtQuery($orders) {
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'id_order');
        $delAddr = (array) $this->duplicateFieldValueFromList($orders['object'], 'id_address_delivery');
        $invAddr = (array) $this->duplicateFieldValueFromList($orders['object'], 'id_address_invoice');
        $address_order = array_unique(array_merge($delAddr, $invAddr));
        $address_order = $this->_flitArrayNum($address_order);
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $address_query = $this->arrayToInCondition($address_order);
        $ext_query = array(
            'order_detail' => "SELECT * FROM _DBPRF_order_detail WHERE id_order IN {$order_ids_query}",
            'order_history' => "SELECT *  FROM _DBPRF_order_history WHERE id_order IN {$order_ids_query} ORDER BY id_order_history DESC",
            'address' => "SELECT * FROM _DBPRF_address WHERE id_address IN {$address_query}",
            'currency' => "SELECT currency_id, code FROM _DBPRF_currency",
            'carrier' => "SELECT * FROM _DBPRF_carrier",
            'message' => "SELECT * FROM _DBPRF_message WHERE id_order IN {$order_ids_query}",
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
        $countryIds = $this->duplicateFieldValueFromList($ordersExt['object']['address'], 'id_country');
        $stateIds = $this->duplicateFieldValueFromList($ordersExt['object']['address'], 'id_state');
        $cusIds = $this->duplicateFieldValueFromList($ordersExt['object']['address'], 'id_customer');
        $country_ids_query = $this->arrayToInCondition($countryIds);
        $state_ids_query = $this->arrayToInCondition($stateIds);
        $cus_ids_query = $this->arrayToInCondition($cusIds);
        $ext_rel_query = array(
            'country' => "SELECT * FROM _DBPRF_country WHERE id_country IN {$country_ids_query}",
            'state' => "SELECT * FROM _DBPRF_state WHERE id_state IN {$state_ids_query}",
            'customer' => "SELECT * FROM _DBPRF_customer WHERE id_customer IN {$cus_ids_query}",
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
        return $order['id_order'];
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
        $addr_invoice = $this->getRowFromListByField($ordersExt['object']['address'], 'id_address', $order['id_address_invoice']);
        $customer_info = $this->getRowFromListByField($ordersExt['object']['customer'], 'id_customer', $order['id_customer']);

        $address_billing['firstname'] = $addr_invoice['firstname'];
        $address_billing['lastname'] = $addr_invoice['lastname'];
        $address_billing['company'] = $addr_invoice['company'];
        $address_billing['email'] = $customer_info['email'];
        $address_billing['street'] = $addr_invoice['address1'] . "\n" . $addr_invoice['address2'];
        $address_billing['city'] = $addr_invoice['city'];
        $address_billing['postcode'] = $addr_invoice['postcode'];
        $bil_country = $this->getRowValueFromListByField($ordersExt['object']['country'], 'id_country', $addr_invoice['id_country'], 'iso_code');
        $address_billing['country_id'] = $bil_country;
        if (is_numeric($addr_invoice['id_state'])) {
            $billing_state = $this->getRowValueFromListByField($ordersExt['object']['state'], 'id_state', $addr_invoice['id_state'], 'name');
            if (!$billing_state) {
                $billing_state = '';
            }
        } else {
            $billing_state = '';
        }
        $billing_region_id = $this->getRegionId($billing_state, $address_billing['country_id']);
        if ($billing_region_id) {
            $address_billing['region_id'] = $billing_region_id;
        } else {
            $address_billing['region'] = $billing_state;
        }
        $address_billing['telephone'] = ($addr_invoice['phone']) ? $addr_invoice['phone'] : $addr_invoice['phone_mobile'];

        $addr_delivery = $this->getRowFromListByField($ordersExt['object']['address'], 'id_address', $order['id_address_delivery']);
        $address_shipping['firstname'] = $addr_delivery['firstname'];
        $address_shipping['lastname'] = $addr_delivery['lastname'];
        $address_shipping['company'] = $addr_delivery['company'];
        $address_shipping['email'] = $customer_info['email'];
        $address_shipping['street'] = $addr_delivery['address1'] . "\n" . $addr_delivery['address2'];
        $address_shipping['city'] = $addr_delivery['city'];
        $address_shipping['postcode'] = $addr_delivery['postcode'];
        $del_country = $this->getRowValueFromListByField($ordersExt['object']['country'], 'id_country', $addr_delivery['id_country'], 'iso_code');
        $address_shipping['country_id'] = $del_country;
        if (is_numeric($addr_delivery['id_state'])) {
            $shipping_state = $this->getRowValueFromListByField($ordersExt['object']['state'], 'id_state', $addr_delivery['id_state'], 'name');
            if (!$shipping_state) {
                $shipping_state = '';
            }
        } else {
            $shipping_state = '';
        }
        $shipping_region_id = $this->getRegionId($shipping_state, $address_shipping['country_id']);
        if ($shipping_region_id) {
            $address_shipping['region_id'] = $shipping_region_id;
        } else {
            $address_shipping['region'] = $shipping_state;
        }
        $address_shipping['telephone'] = ($addr_delivery['phone']) ? $addr_delivery['phone'] : $addr_delivery['phone_mobile'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['order_detail'], 'id_order', $order['id_order']);
        $carts = array();
        foreach ($orderPro as $order_pro) {
            $cart = array();
            if ($order_pro['product_reference']) {
                $sku = strtolower(str_replace(" ", "-", $order_pro['product_reference']));
            } else {
                $sku = strtolower(str_replace(" ", "-", $order_pro['product_name']));
            }
            $product_id = $this->getMageIdProduct($order_pro['product_id']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $cart['name'] = $order_pro['product_name'];
            $cart['sku'] = $sku;
            $cart['price'] = (intval($order_pro['product_quantity_discount'])) ? $order_pro['product_quantity_discount'] : $order_pro['product_price'];
            $cart['original_price'] = $order_pro['product_price'];
            $cart['tax_amount'] = ($cart['price'] * $order_pro['product_quantity']) * $order_pro['tax_rate'] / 100;
            $cart['tax_percent'] = $order_pro['tax_rate'];
            $cart['discount_amount'] = '0';
            $cart['qty_ordered'] = $order_pro['product_quantity'];
            $cart['row_total'] = $cart['price'] * $cart['qty_ordered'];
            $carts[] = $cart;
        }

        $customer_id = $this->getMageIdCustomer($order['id_customer']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['order_history'], 'id_order', $order['id_order']);
        $order_status = $orderStatus[0];
        $order_status_id = $order_status['id_order_state'];
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $shipping_name = $this->getRowValueFromListByField($ordersExt['object']['carrier'], 'id_carrier', $order['id_carrier'], 'name');
        $currency_code = $this->getRowValueFromListByField($ordersExt['object']['currency'], 'id_currency', $order['id_currency'], 'iso_code');

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $customer_info['email'];
        $order_data['customer_firstname'] = $customer_info['firstname'];
        $order_data['customer_lastname'] = $customer_info['lastname'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['total_products']);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['total_shipping'];
        $order_data['base_shipping_amount'] = $order['total_shipping'];
        $order_data['base_shipping_invoiced'] = $order['total_shipping'];
        $order_data['shipping_description'] = ($shipping_name) ? $shipping_name : 'Default shipping';
        $order_data['tax_amount'] = isset($order['total_products_wt']) ? $order['total_products_wt'] - $order['total_products'] : 0;
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['total_discounts'];
        $order_data['base_discount_amount'] = $order['total_discounts'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['total_paid']);
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
        $order_data['created_at'] = $order['date_add'];

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['id_order'];
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
        $orderStatus = $this->getListFromListByField($ordersExt['object']['order_history'], 'id_order', $order['id_order']);
        $orderComment = $this->getListFromListByField($ordersExt['object']['message'], 'id_order', $order['id_order']);
        $timeline = array();
        $state = '0000-00-00 00:00:00';
        $orderStatus = array_reverse($orderStatus);
        foreach ($orderStatus as $id => $row) {
            if ($id == 0) {
                $state = $row['id_order_state'];
            }
            $timeline[$row['date_add']]['history'] = $row['id_order_state'];
            $timeline[$row['date_add']]['message']['id_employee'] = '';
            $timeline[$row['date_add']]['message']['content'] = '';
        }
        foreach ($orderComment as $row) {
            $timeline[$row['date_add']]['message']['id_employee'] = $row['id_employee'];
            $timeline[$row['date_add']]['message']['content'] = $row['message'];
        }
        ksort($timeline);
        $i = 0;
        foreach ($timeline as $key => $value) {
            if (isset($value['history'])) {
                $state = $value['history'];
            }
            if (!isset($value['message'])) {
                continue;
            }
            $order_status_data = array();
            $order_status_id = $state;
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if ($i == 0) {
                $order_status_data['comment'] = "<b>Reference order #" . $order['id_order'] . "</b><br /><b>Payment method: </b>" . $order['payment'] . "<br /><b>Shipping method: </b> " . $data['order']['shipping_description'] . "<br /><br />" . $value['message']['content'];
            } else {
                $order_status_data['comment'] = $value['message']['content'];
            }
            $i++;
            $order_status_data['is_customer_notified'] = ($value['message']['id_employee']) ? 0 : 1;
            $order_status_data['updated_at'] = $key;
            $order_status_data['created_at'] = $key;
            $this->_process->ordersComment($order_mage_id, $order_status_data);
        }
    }

    /**
     * Query for get main data use for import review
     *
     * @return string
     */
    protected function _getReviewsMainQuery() {
        return array();
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews) {
        return array();
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
        return;
    }

    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return array
     */
    public function convertReview($review, $reviewsExt) {
        return array();
    }

############################################################ Extend function ##################################

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id) {
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_category WHERE id_category = {$parent_id}"
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
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @param int $parent_id : Id of parent product if exist
     * @return array
     */
    protected function _convertProduct($product, $productsExt, $parent_id = null) {
        $pro_data = array();
        $categories = array();
        $proCat = $this->getListFromListByField($productsExt['object']['category_product'], 'id_product', $product['id_product']);
        if ($proCat) {
            foreach ($proCat as $pro_cat) {
                $cat_id = $this->getMageIdCategory($pro_cat['id_category']);
                if ($cat_id) {
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        $proDesc = $this->getListFromListByField($productsExt['object']['product_lang'], 'id_product', $product['id_product']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'id_lang', $this->_notice['config']['default_lang']);
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        if ($product['reference']) {
            $pro_data['sku'] = $this->createProductSku($product['reference'], $this->_notice['config']['languages']);
        } else {
            $pro_data['sku'] = $this->createProductSku($pro_desc_def['name'], $this->_notice['config']['languages']);
        }
        if (isset($product['sku']) && !$product['reference']) {
            $part_sku = strtolower(str_replace(" ", "-", $product['sku']));
            $full_sku = $pro_data['sku'] . '-' . $part_sku;
            $pro_data['sku'] = $this->createProductSku($full_sku, $this->_notice['config']['languages']);
        }
        if (!$parent_id) {
            $pro_data['name'] = $pro_desc_def['name'];
        } else {
            $pro_data['name'] = $product['name'];
        }
        $pro_data['description'] = $this->changeImgSrcInText($pro_desc_def['description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_desc_def['description_short'], $this->_notice['config']['add_option']['img_des']);
		$pro_data['meta_title'] = $pro_desc_def['meta_title'];
        $pro_data['meta_keyword'] = $pro_desc_def['meta_keywords'];
        $pro_data['meta_description'] = $pro_desc_def['meta_description'];
        $pro_data['price'] = $product['price'] ? $product['price'] : 0;
        $proTier = $this->getListFromListByField($productsExt['object']['discount_quantity'], 'id_product', $product['id_product']);
        if ($proTier && !$parent_id) {
            foreach ($proTier as $row) {
                if ($row['id_discount_type'] == 1) {
                    $tier_price = $product['price'] - ($row['value'] * $product['price'] / 100);
                } elseif ($row['id_discount_type'] == 2) {
                    $tier_price = $product['price'] - $row['value'];
                } else {
                    $tier_price = $product['price'];
                }
                $value = array(
                    'website_id' => 0,
                    'cust_group' => Mage_Customer_Model_Group::CUST_GROUP_ALL,
                    'price_qty' => $row['quantity'],
                    'price' => $tier_price
                );
                $tier_prices[] = $value;
            }
            $pro_data['tier_price'] = $tier_prices;
        }
        if (!$parent_id) {
            if (intval($product['reduction_price']) || intval($product['reduction_percent'])) {
                if (intval($product['reduction_price'])) {
                    $special_price = $product['price'] - $product['reduction_price'];
                }
                if (intval($product['reduction_percent'])) {
                    $special_price = $product['price'] - ($product['reduction_percent'] * $product['price'] / 100);
                }
                $pro_data['special_price'] = $special_price;
                $pro_data['special_from_date'] = $this->_cookSpecialDate($product['reduction_from']);
                $pro_data['special_to_date'] = $this->_cookSpecialDate($product['reduction_to']);
            }
        }
        $pro_data['weight'] = $product['weight'] ? $product['weight'] : 0;
        if (!$parent_id) {
            $pro_data['status'] = ($product['active'] == 1) ? 1 : 2;
        } else {
            $pro_data['status'] = 1;
        }
        if ($product['id_tax'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['id_tax'])) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = (isset($product['date_add'])) ? $product['date_add'] : '0000-00-00 00:00:00';
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $product_quantity = $product['quantity'];
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product_quantity < 1) ? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product_quantity < 1) ? 0 : 1,
            'qty' => $product_quantity
        );
        if (!$parent_id) {
            if ($manufacture_mage_id = $this->getMageIdManufacturer($product['id_manufacturer'])) {
                $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
            }
            $images = $this->getListFromListByField($productsExt['object']['image'], 'id_product', $product['id_product']);
            if ($images) {
                $image_cover = $this->getRowValueFromListByField($images, 'cover', '1', 'id_image');
                $image_path_ps = $this->_getImagePath($image_cover, $product['id_product']);
                if (!$parent_id && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_path_ps, 'catalog/product', false, true)) {
                    $pro_data['image_import_path'] = array('path' => $image_path, 'label' => $image_path_ps);
                }
                $images_gallery = $this->getListFromListByField($images, 'cover', '0');
                $images_gallery_ps = array();
                foreach ($images_gallery as $row) {
                    if ($row['cover'] == '1') {
                        continue;
                    }
                    $images_gallery_ps[] = $this->_getImagePath($row['id_image'], $product['id_product']);
                }
                foreach ($images_gallery_ps as $gallery) {
                    if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery, 'catalog/product', false, true)) {
                        $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $gallery);
                    }
                }
            }
        }
        //Tag
        $proTags = $this->getListFromListByField($productsExt['object']['product_tag'], 'id_product', $product['id_product']);
        if ($proTags) {
            foreach ($proTags as $tag) {
                $pro_tag = $this->getRowFromListByField($productsExt['object']['tag'], 'id_tag', $tag['id_tag']);
                if ($pro_tag) {
                    $pro_data['tags'][] = $pro_tag['name'];
                }
            }
        }
        $multi_store = array();
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'id_lang', $lang_id)) {
                $store_data = array();
                $store_data['name'] = $store_data_change['name'];
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['short_description'] = $this->changeImgSrcInText($store_data_change['description_short'], $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = $store_data_change['meta_title'];
                $store_data['meta_keyword'] = $store_data_change['meta_keywords'];
                $store_data['meta_description'] = $store_data_change['meta_description'];
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
        }
        $pro_data['multi_store'] = $multi_store;
        if ($this->_seo && !$parent_id) {
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

    protected function _getOrderTaxValueFromListByCode($list, $code) {
        $result = 0;
        if ($list) {
            foreach ($list as $row) {
                if ($row['code'] == $code) {
                    $result += $row['value'];
                }
            }
        }
        return $result;
    }

    protected function _getCategoryImagePath($category_id) {
        $category_images = array();
        $category_images['image'] = 'c/' . $category_id . '.jpg';
        $category_images['thumbnail'] = 'c/' . $category_id . '-medium.jpg';
        return $category_images;
    }

    protected function _getChildrenProduct($product, $productsExt) {
        $product_id = $product['id_product'];
        $product_tax = $product['id_tax'];
        $price = $product['price'];
        $child_products = $this->getListFromListByField($productsExt['object']['product_attribute'], 'id_product', $product_id);
        if (!$child_products) {
            return false;
        }
        $product_lang = $this->getListFromListByField($productsExt['object']['product_lang'], 'id_product', $product_id);
        $product_name = $this->getRowValueFromListByField($product_lang, 'id_lang', $this->_notice['config']['default_lang'], 'name');
        foreach ($child_products as $key => $child_product) {
            $child_product_name = $this->getListFromListByField($productsExt['object']['product_attribute_combination'], 'id_product_attribute', $child_product['id_product_attribute']);
            $part_name = array();
            $sku_part = array();
            foreach ($child_product_name as $value) {
                $attr_name_lang = $this->getListFromListByField($productsExt['object']['attribute_lang'], 'id_attribute', $value['id_attribute']);
                $attr_name = $this->getRowValueFromListByField($attr_name_lang, 'id_lang', $this->_notice['config']['default_lang'], 'name');
                $attr_group_lang = $this->getListFromListByField($productsExt['object']['attribute_group_lang'], 'id_attribute_group', $value['id_attribute_group']);
                $attr_group = $this->getRowValueFromListByField($attr_group_lang, 'id_lang', $this->_notice['config']['default_lang'], 'name');
                $part_part_name = array();
                if ($child_product['id_product_attribute'] == $value['id_product_attribute']) {
                    $part_part_name[] = $attr_group;
                    $part_part_name[] = $attr_name;
                    $sku_part[] = $attr_name;
                    $part_name[] = implode(' - ', $part_part_name);
                }
            }
            $sku = implode('-', $sku_part);
            $name = implode(',', $part_name);
            $full_name = $product_name . ' - ' . $name;
            $child_products[$key]['name'] = $full_name;
            if (!$child_product['reference']) {
                $child_products[$key]['reference'] = $product['reference'];
            }
            $child_products[$key]['sku'] = $sku;
            $child_products[$key]['id_tax'] = $product_tax;
            $child_products[$key]['price'] = $price + $child_product['price'];
        }
        return $child_products;
    }

    protected function _importChildrenProduct($parent_id, $children, $productsExt) {
        $result = false;
        $attrOptImport = array();
        $child_attr_2 = array();
        $dataChildes = array();
        $attrMage = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attribute_set_id = $this->_notice['config']['attribute_set_id'];
        $store_view = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL;
        foreach ($children as $row) {
            $all_attr_value_2 = array();
            $pro_attr = $this->getListFromListByField($productsExt['object']['product_attribute_combination'], 'id_product_attribute', $row['id_product_attribute']);
            foreach ($pro_attr as $value) {
                $attr_name_lang = $this->getListFromListByField($productsExt['object']['attribute_lang'], 'id_attribute', $value['id_attribute']);
                $attr_group_lang = $this->getListFromListByField($productsExt['object']['attribute_group_lang'], 'id_attribute_group', $value['id_attribute_group']);
                $attr_import = $this->_makeAttributeImport($attr_group_lang, $attr_name_lang, $entity_type_id, $attribute_set_id, $store_view);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if(!$attr_after) return false;
                $attr_after['option_label'] = $this->getRowValueFromListByField($attr_name_lang, 'id_lang', $this->_notice['config']['default_lang'], 'name');
                $attrMage[$attr_after['attribute_id']]['attribute_label'] = $this->getRowValueFromListByField($attr_group_lang, 'id_lang', $this->_notice['config']['default_lang'], 'name');
                $attrMage[$attr_after['attribute_id']]['attribute_code'] = $attr_after['attribute_code'];
                $attrMage[$attr_after['attribute_id']]['values'][$attr_after['option_ids']['option_0']] = $attr_after;
                $pro_data_2[$attr_after['attribute_id']] = $attr_after['option_ids']['option_0'];
                $all_attr_value_2 = array_replace_recursive($all_attr_value_2, $pro_data_2);
            }
            $child_attr_2[$row['id_product_attribute']] = $all_attr_value_2;
        }
        $child_attr_2 = $this->_createDataOptionValue($child_attr_2);
        $configurable_products_data = array();
        $proNames = array();
        foreach ($children as $row) {
            $product = array(
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            );
            $product = array_merge($this->_convertProduct($row, $productsExt, $parent_id), $product);
            $pro_import = $this->_process->product($product);
            if ($pro_import['result'] !== 'success')
                return false;
            $optionValues = $child_attr_2[$row['id_product_attribute']];
            if (!empty($optionValues)) {
                foreach ($optionValues as $attr => $optionValue) {
                    $dataTMP['attribute_id'] = $attr;
                    $dataTMP['value_index'] = $optionValue;
                    $dataTMP['is_percent'] = 0;
                    $dataTMP['pricing_value'] = '';
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
        $attr_des[] = $this->getRowValueFromListByField($attribute, 'id_lang', $this->_notice['config']['default_lang'], 'name');
        $attr_name = $this->joinTextToKey($attr_des[0], 30, '_');
        if (in_array($attr_name,Mage::getModel('catalog/product')->getReservedAttributes())) {
            $attr_name = $attr_name . '123';
        }
        $opt_des = array();
        $opt_des[] = $this->getRowValueFromListByField($option, 'id_lang', $this->_notice['config']['default_lang'], 'name');
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if ($lang_id == $this->_notice['config']['default_lang']) {
                continue;
            }
            $opt_des[$store_id] = $this->getRowValueFromListByField($option, 'id_lang', $lang_id, 'name');
            $attr_des[$store_id] = $this->getRowValueFromListByField($attribute, 'id_lang', $lang_id, 'name');
        }
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

    protected function _getImagePath($id_image, $id_product) {
        $image_path = 'p/' . $id_product . '-' . $id_image . '.jpg';
        return $image_path;
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

    protected function _createDataOptionValue($dataValueAttr) {
        foreach ($dataValueAttr as $product_id => $dataValueOptions) {
            foreach ($dataValueAttr as $dataProTwo) {
                foreach ($dataProTwo as $attr_two => $value_two) {
                    $check = 0;
                    foreach ($dataValueOptions as $attr_one => $value_one) {
                        if ($attr_two == $attr_one) {
                            $check = 1;
                            break;
                        }
                    }
                    if ($check == 0)
                        $dataValueAttr[$product_id][$attr_two] = $value_two;
                }
            }
        }
        return $dataValueAttr;
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
        $query = "SELECT * FROM _DBPRF_tax ORDER BY id_tax ASC";
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
        $query = "SELECT * FROM _DBPRF_manufacturer ORDER BY id_manufacturer ASC";
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
        $query = "SELECT * FROM _DBPRF_category WHERE id_category > 1 ORDER BY id_category ASC";
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
        $query = "SELECT * FROM _DBPRF_product ORDER BY id_product ASC";
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
        $query = "SELECT * FROM _DBPRF_customer ORDER BY id_customer ASC";
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
        $query = "SELECT * FROM _DBPRF_orders ORDER BY id_order ASC";
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
        return array(
            'result' => 'success',
            'object' => array()
        );
    }

}
