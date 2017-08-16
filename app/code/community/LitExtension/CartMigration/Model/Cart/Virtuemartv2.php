<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Virtuemartv2
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_calcs WHERE virtuemart_calc_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_manufacturers WHERE virtuemart_manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_categories WHERE virtuemart_category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_products WHERE virtuemart_product_id > {$this->_notice['products']['id_src']} AND product_parent_id = 0",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_orders WHERE virtuemart_order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_rating_reviews WHERE virtuemart_rating_review_id > {$this->_notice['reviews']['id_src']}"
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
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'virtuemart_configs' => "SELECT config FROM _DBPRF_virtuemart_configs WHERE virtuemart_config_id = 1",
                'extensions' => "SELECT * FROM _DBPRF_extensions WHERE name = 'com_languages'",
                'virtuemart_vendors' => "SELECT vendor_currency,vendor_accepted_currencies FROM _DBPRF_virtuemart_vendors",
                'virtuemart_orderstates' => "SELECT * FROM _DBPRF_virtuemart_orderstates",
                'virtuemart_shoppergroups' => "SELECT * FROM _DBPRF_virtuemart_shoppergroups",
                'languages' => "SELECT * FROM _DBPRF_languages"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $langCode = array();
        if($data['object']['virtuemart_configs']){
            $vm_config = $data['object']['virtuemart_configs'][0]['config'];
            $langCode = $this->_getVmConfigByKey($vm_config, 'active_languages');
            if($langCode){
                $this->_notice['config']['default_lang'] = $langCode[0];
            }
            $vm_lang = $this->_getVmConfigByKey($vm_config, 'vmlang');
            if($vm_lang){
                $langCode[] = $this->_convertCodeToJmLang($vm_lang);
                $this->_notice['config']['default_lang'] = $this->_convertCodeToJmLang($vm_lang);
            }
            if($langCode) $langCode = array_unique($langCode);
        }
        if(!$langCode){
            $jm_lang = $data['object']['extensions'][0];
            if($jm_lang){
                $params = @json_decode($jm_lang['params'], 1);
                if($params){
                    $default_lang = $params['site'];
                } else {
                    $default_lang = 'en-GB';
                }
                $this->_notice['config']['default_lang'] = $this->_convertJmLangToCode($default_lang);
                $langCode[] = $default_lang;
            }
        }
        $langFile = array();
        foreach($langCode as $lang_code){
            $langFile[] = $this->_createLanguageFilePath($lang_code);
        }
        $langSrc = $this->_getDataImport($this->_getUrlConnector('file_Content'), array(
            'serialize' => true,
            'files' => serialize($langFile)
        ));
        if(!$langSrc || $langSrc['result'] != 'success'){
            return $this->errorConnector();
        }
        $currencies = array();
        foreach($data['object']['virtuemart_vendors'] as $key => $row){
            if($row['vendor_currency']) $currencies = array_merge($currencies, array($row['vendor_currency']));
            if($row['vendor_accepted_currencies']) $currencies = array_merge($currencies, explode(',',$row['vendor_accepted_currencies']));
            if($key == 0){
                $this->_notice['config']['default_currency'] = $row['vendor_currency'];
            }
        }
        $currencies = array_unique($currencies);
        $cur_con = $this->arrayToInCondition($currencies);
        $curSrc = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_virtuemart_currencies WHERE virtuemart_currency_id IN {$cur_con}"
        ));
        if(!$curSrc || $curSrc['result'] != 'success'){
            return $this->errorConnector();
        }
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        if($data['object']['languages']){
            foreach($data['object']['languages'] as $lang){
                $key = $this->_convertJmLangToCode($lang['lang_code']);
                $value = $lang['title'];
                $language_data[$key] = $value;
            }
        }else{
            foreach($langSrc['object'] as $lang_src){
                $tag = $this->_convertJmLangToCode($this->_getParamFromContentXml($lang_src, array('metadata','tag'),2));
                $name = $this->_getParamFromContentXml($lang_src, array('metadata','name'),2);
                $language_data[$tag] = $name;
            }
        }
        foreach($curSrc['object'] as $cus_src){
            $key = $cus_src['virtuemart_currency_id'];
            $value = $cus_src['currency_name'];
            $currency_data[$key] = $value;
        }
        foreach($data['object']['virtuemart_orderstates'] as $order_status_row){
            $key = $order_status_row['order_status_code'];
            $value = $order_status_row['order_status_name'];
            $order_status_data[$key] = $value;
        }
        $customer_group_data[0] = 'None';
        foreach($data['object']['virtuemart_shoppergroups'] as $shopper_group_row){
            $shopper_group_id = $shopper_group_row['virtuemart_shoppergroup_id'];
            $shopper_group_name = $shopper_group_row['shopper_group_name'];
            $customer_group_data[$shopper_group_id] = $shopper_group_name;
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_calcs WHERE virtuemart_calc_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_manufacturers WHERE virtuemart_manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_categories WHERE virtuemart_category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_products WHERE virtuemart_product_id > {$this->_notice['products']['id_src']} AND product_parent_id = 0",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_orders WHERE virtuemart_order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_virtuemart_rating_reviews WHERE virtuemart_rating_review_id > {$this->_notice['reviews']['id_src']}"
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
        $curIds = array_keys($this->_notice['config']['currencies']);
        $cur_id_con = $this->arrayToInCondition($curIds);
        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_virtuemart_currencies WHERE virtuemart_currency_id IN {$cur_id_con}"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){
                $currency_id = $currency['virtuemart_currency_id'];
                $currency_value = 1;
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
        $query = "SELECT * FROM _DBPRF_virtuemart_calcs WHERE virtuemart_calc_id > {$id_src} ORDER BY virtuemart_calc_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'virtuemart_calc_id');
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'virtuemart_calc_countries' => "SELECT * FROM _DBPRF_virtuemart_calc_countries WHERE virtuemart_calc_id IN {$tax_id_con}",
            'virtuemart_calc_states' => "SELECT * FROM _DBPRF_virtuemart_calc_states WHERE virtuemart_calc_id IN {$tax_id_con}"
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
        $countryIds = $this->duplicateFieldValueFromList($taxesExt['object']['virtuemart_calc_countries'], 'virtuemart_country_id');
        $stateIds = $this->duplicateFieldValueFromList($taxesExt['object']['virtuemart_calc_states'], 'virtuemart_state_id');
        $country_id_con = $this->arrayToInCondition($countryIds);
        $state_id_con = $this->arrayToInCondition($stateIds);
        $ext_rel_query = array(
            'virtuemart_countries' => "SELECT * FROM _DBPRF_virtuemart_countries WHERE virtuemart_country_id IN {$country_id_con}",
            'virtuemart_states' => "SELECT * FROM _DBPRF_virtuemart_states WHERE virtuemart_state_id IN {$state_id_con}"
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
        return $tax['virtuemart_calc_id'];
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
            'class_name' => $tax['calc_name']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['virtuemart_calc_id'], $tax_pro_ipt['mage_id']);
        }
        $vmCalCountries = $this->getListFromListByField($taxesExt['object']['virtuemart_calc_countries'], 'virtuemart_calc_id', $tax['virtuemart_calc_id']);
        $vmCalStates = $this->getListFromListByField($taxesExt['object']['virtuemart_calc_states'], 'virtuemart_calc_id', $tax['virtuemart_calc_id']);
        if($vmCalCountries){
            foreach($vmCalCountries as $cal_country){
                $country_id = $cal_country['virtuemart_country_id'];
                $country = $this->getRowFromListByField($taxesExt['object']['virtuemart_countries'], 'virtuemart_country_id', $country_id);
                if($country){
                    $has_state = false;
                    $tax_rate_data = array();
                    $tax_rate_data['tax_country_id'] = $country['country_2_code'];
                    foreach($vmCalStates as $cal_state){
                        $state = $this->getRowFromListByField($taxesExt['object']['virtuemart_states'], 'virtuemart_state_id', $cal_state['virtuemart_state_id']);
                        if(!$state || $state['virtuemart_country_id'] != $country_id){
                            continue;
                        }
                        $has_state = true;
                        $code = $tax['calc_name'] . "-" . $country['country_name'] . "-" . $state['state_name'];
                        $tax_rate_data['code'] = $this->createTaxRateCode($code);
                        $tax_rate_data['tax_region_id'] = $this->getRegionId($state['state_name'], $country['country_2_code']);
                        $tax_rate_data['zip_is_range'] = 0;
                        $tax_rate_data['tax_postcode'] = "*";
                        $tax_rate_data['rate'] = $tax['calc_value_mathop'] == '+%' ? $tax['calc_value'] : 0;
                        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                        if($tax_rate_ipt['result'] == 'success'){
                            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                        }
                    }
                    if($has_state == false){
                        $code = $tax['calc_name'] . "-" . $country['country_name'] . "-*";
                        $tax_rate_data['code'] = $this->createTaxRateCode($code);
                        $tax_rate_data['tax_region_id'] = 0;
                        $tax_rate_data['tax_postcode'] = "*";
                        $tax_rate_data['rate'] = $tax['calc_value_mathop'] == '+%' ? $tax['calc_value'] : 0;
                        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                        if($tax_rate_ipt['result'] == 'success'){
                            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                        }
                    }
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['calc_name']);
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
        $query = "SELECT * FROM _DBPRF_virtuemart_manufacturers WHERE virtuemart_manufacturer_id > {$id_src} ORDER BY virtuemart_manufacturer_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @return array
     */
    protected function _getManufacturersExtQuery($manufacturers){
        $manufacturersIds = $this->duplicateFieldValueFromList($manufacturers['object'], 'virtuemart_manufacturer_id');
        $manufacturer_id_con = $this->arrayToInCondition($manufacturersIds);
        $ext_query = array();
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $table = "virtuemart_manufacturers_" . $lang_code;
            $ext_query[$table] = "SELECT * FROM _DBPRF_{$table} WHERE virtuemart_manufacturer_id IN {$manufacturer_id_con}";
        }
        return $ext_query;
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
        return $manufacturer['virtuemart_manufacturer_id'];
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
        $man_table_def = "virtuemart_manufacturers_" . $this->_convertJmLangToCode($this->_notice['config']['default_lang']);
        $man_def = $this->getRowFromListByField($manufacturersExt['object'][$man_table_def], 'virtuemart_manufacturer_id', $manufacturer['virtuemart_manufacturer_id']);
        if(!$man_def){
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Manufacturer Id = {$manufacturer['virtuemart_manufacturer_id']} import failed. Error: Manufacturer data not exists!")
            );
        }
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id
        );
        $manufacturer_data['value']['option'] = array(
            0 => $man_def['mf_name']
        );
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $table = "virtuemart_manufacturers_" . $lang_code;
            $man_desc = $this->getRowFromListByField($manufacturersExt['object'][$table], 'virtuemart_manufacturer_id', $manufacturer['virtuemart_manufacturer_id']);
            if($man_desc){
                $manufacturer['value']['option'][$store_id] = $man_desc['mf_name'];
            }
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
        $query = "SELECT * FROM _DBPRF_virtuemart_categories WHERE virtuemart_category_id > {$id_src} ORDER BY virtuemart_category_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'virtuemart_category_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'virtuemart_category_categories' => "SELECT * FROM _DBPRF_virtuemart_category_categories WHERE category_child_id IN {$cat_id_con}",
            'virtuemart_category_medias' => "SELECT * FROM _DBPRF_virtuemart_category_medias WHERE virtuemart_category_id IN {$cat_id_con}"
        );
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $table = "virtuemart_categories_".$lang_code;
            $ext_query[$table] = "SELECT * FROM _DBPRF_{$table} WHERE virtuemart_category_id IN {$cat_id_con}";
        }
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
        $mediaIds = $this->duplicateFieldValueFromList($categoriesExt['object']['virtuemart_category_medias'], 'virtuemart_media_id');
        $media_id_con = $this->arrayToInCondition($mediaIds);
        $ext_rel_query = array(
            'virtuemart_medias' => "SELECT * FROM _DBPRF_virtuemart_medias WHERE virtuemart_media_id IN {$media_id_con}"
        );
        return $ext_rel_query;
    }

    /**
     * Get primary key of source category
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return int
     */
    public function getCategoryId($category, $categoriesExt){
        return $category['virtuemart_category_id'];
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
        $parent = $this->getRowFromListByField($categoriesExt['object']['virtuemart_category_categories'], 'category_child_id', $category['virtuemart_category_id']);
        if(!$parent || $parent['category_parent_id'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($parent['category_parent_id']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($parent['category_parent_id']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['virtuemart_category_id']} import failed. Error: Could not import parent category id = {$parent['category_parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_table_def = "virtuemart_categories_" . $this->_convertJmLangToCode($this->_notice['config']['default_lang']);
        $cat_def = $this->getRowFromListByField($categoriesExt['object'][$cat_table_def], 'virtuemart_category_id', $category['virtuemart_category_id']);
        if(!$cat_def){
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Category Id = {$category['virtuemart_category_id']} import failed. Error: Category data not exists!")
            );
        }
        $cat_data['name'] = $cat_def['category_name'] ? $cat_def['category_name'] : " ";
        $cat_data['description'] = $cat_def['category_description'];
        $cat_data['meta_title'] = $cat_def['customtitle'];
        $cat_data['meta_keywords'] = $cat_def['metakey'];
        $cat_data['meta_description'] = $cat_def['metadesc'];
        $cat_media = $this->getRowFromListByField($categoriesExt['object']['virtuemart_category_medias'], 'virtuemart_category_id', $category['virtuemart_category_id']);
        if($cat_media){
            $media = $this->getRowFromListByField($categoriesExt['object']['virtuemart_medias'], 'virtuemart_media_id', $cat_media['virtuemart_media_id']);
            if($media && $media['file_url']){
                $img_path = $this->downloadImage($this->_cart_url,  $media['file_url'], 'catalog/category');
                if($img_path){
                    $cat_data['image'] = $img_path;
                }
            }
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['published'];
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            if($jm_lang == $this->_notice['config']['default_lang']){
                continue ;
            }
            $store_data = array();
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $table = "virtuemart_categories_".$lang_code;
            $cat_desc = $this->getRowFromListByField($categoriesExt['object'][$table], 'virtuemart_category_id', $category['virtuemart_category_id']);
            if($cat_desc){
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $cat_desc['category_name'];
                $store_data['description'] = $cat_desc['category_description'];
                $store_data['meta_title'] = $cat_desc['customtitle'];
                $store_data['meta_keywords'] = $cat_desc['metakey'];
                $store_data['meta_description'] = $cat_desc['metadesc'];
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
        $query = "SELECT * FROM _DBPRF_virtuemart_products WHERE virtuemart_product_id > {$id_src} AND product_parent_id = 0 ORDER BY virtuemart_product_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'virtuemart_product_id');
        $product_id_con = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'virtuemart_product_categories' => "SELECT * FROM _DBPRF_virtuemart_product_categories WHERE virtuemart_product_id IN {$product_id_con}",
            'virtuemart_product_manufacturers' => "SELECT * FROM _DBPRF_virtuemart_product_manufacturers WHERE virtuemart_product_id IN {$product_id_con}",
            'virtuemart_product_medias' => "SELECT * FROM _DBPRF_virtuemart_product_medias WHERE virtuemart_product_id IN {$product_id_con} ORDER BY ordering ASC",
            'virtuemart_product_prices' => "SELECT * FROM _DBPRF_virtuemart_product_prices WHERE virtuemart_product_id IN {$product_id_con} ORDER BY virtuemart_product_price_id ASC",
            'virtuemart_product_customfields' => "SELECT * FROM _DBPRF_virtuemart_product_customfields WHERE virtuemart_product_id IN {$product_id_con} OR (custom_value IN {$product_id_con} AND custom_value > virtuemart_product_id)",
            'virtuemart_products_children' => "SELECT * FROM _DBPRF_virtuemart_products WHERE product_parent_id IN {$product_id_con}",
        );
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $table = "virtuemart_products_".$lang_code;
            $ext_query[$table] = "SELECT * FROM _DBPRF_{$table} WHERE virtuemart_product_id IN {$product_id_con}";
        }
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
        $proChildIds = $this->duplicateFieldValueFromList($productsExt['object']['virtuemart_products_children'], 'virtuemart_product_id');
        $mediaIds = $this->duplicateFieldValueFromList($productsExt['object']['virtuemart_product_medias'], 'virtuemart_media_id');
        $customIds = $this->duplicateFieldValueFromList($productsExt['object']['virtuemart_product_customfields'], 'virtuemart_custom_id');
        $pro_child_con = $this->arrayToInCondition($proChildIds);
        $media_id_con = $this->arrayToInCondition($mediaIds);
        $custom_id_con = $this->arrayToInCondition($customIds);
        $ext_rel_query = array(
            'virtuemart_product_categories_children' => "SELECT * FROM _DBPRF_virtuemart_product_categories WHERE virtuemart_product_id IN {$pro_child_con}",
            'virtuemart_product_manufacturers_children' => "SELECT * FROM _DBPRF_virtuemart_product_manufacturers WHERE virtuemart_product_id IN {$pro_child_con}",
            'virtuemart_product_medias_children' => "SELECT vpm.*, vm.* FROM _DBPRF_virtuemart_product_medias AS vpm LEFT JOIN _DBPRF_virtuemart_medias AS vm ON vpm.virtuemart_media_id = vm.virtuemart_media_id WHERE vpm.virtuemart_product_id IN {$pro_child_con} ORDER BY vpm.ordering ASC",
            'virtuemart_product_prices_children' => "SELECT * FROM _DBPRF_virtuemart_product_prices WHERE virtuemart_product_id IN {$pro_child_con} ORDER BY virtuemart_product_price_id ASC",
            'virtuemart_medias' => "SELECT * FROM _DBPRF_virtuemart_medias WHERE virtuemart_media_id IN {$media_id_con}",
            'virtuemart_customs' => "SELECT * FROM _DBPRF_virtuemart_customs WHERE virtuemart_custom_id IN {$custom_id_con}"
        );
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $table = "virtuemart_products_" . $lang_code;
            $key = "virtuemart_products_children_". $lang_code;
            $ext_rel_query[$key] = "SELECT * FROM _DBPRF_{$table} WHERE virtuemart_product_id IN {$pro_child_con}";
        }
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
        return $product['virtuemart_product_id'];
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
        $proChild = $this->getListFromListByField($productsExt['object']['virtuemart_products_children'], 'product_parent_id', $product['virtuemart_product_id']);
        $pro_data = array();
        if($proChild){
            $config_data = $this->_importChildrenProduct($product, $productsExt);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $pro_data = array_merge($pro_data, $config_data['data']);
        } else {
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        }
        $pro_convert = $this->_convertProduct($product, $productsExt);
        if($pro_convert['result'] != 'success'){
            return $pro_convert;
        }
        $pro_data = array_merge($pro_data, $pro_convert['data']);
        return array(
            'result' => "success",
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
        $options = $this->_createProductCustomOption($product, $productsExt);
        if($options){
            $this->importProductOption($product_mage_id, $options);
        }
        $this->_importProductCustomField($product_mage_id, $product, $productsExt);
        $this->_importRelatedProduct($product_mage_id, $product, $productsExt);
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_users WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'id');
        $customer_id_con = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'virtuemart_userinfos' => "SELECT * FROM _DBPRF_virtuemart_userinfos WHERE virtuemart_user_id IN {$customer_id_con}",
            'virtuemart_vmuser_shoppergroups' => "SELECT * FROM _DBPRF_virtuemart_vmuser_shoppergroups WHERE virtuemart_user_id IN {$customer_id_con}"
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
        $countryIds = $this->duplicateFieldValueFromList($customersExt['object']['virtuemart_userinfos'], 'virtuemart_country_id');
        $stateIds = $this->duplicateFieldValueFromList($customersExt['object']['virtuemart_userinfos'], 'virtuemart_state_id');
        $country_id_con = $this->arrayToInCondition($countryIds);
        $state_id_con = $this->arrayToInCondition($stateIds);
        $ext_rel_query = array(
            'virtuemart_countries' => "SELECT * FROM _DBPRF_virtuemart_countries WHERE virtuemart_country_id IN {$country_id_con}",
            'virtuemart_states' => "SELECT * FROM _DBPRF_virtuemart_states WHERE virtuemart_state_id IN {$state_id_con}"
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
        return $customer['id'];
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
        $cusInfo = $this->getListFromListByField($customersExt['object']['virtuemart_userinfos'], 'virtuemart_user_id', $customer['id']);
        $cus_info = $this->getRowFromListByField($cusInfo, 'address_type', 'BT');
        $shopper_group = $this->getRowValueFromListByField($customersExt['object']['virtuemart_vmuser_shoppergroups'], 'virtuemart_user_id', $customer['id'], 'virtuemart_shoppergroup_id');
        if(!$shopper_group){
            $shopper_group = 0;
        }
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = ($cus_info)? $cus_info['first_name'] : ' ';
        $cus_data['middlename'] = ($cus_info)? $cus_info['middle_name'] : ' ';
        $cus_data['lastname'] = ($cus_info)? $cus_info['last_name'] : ' ';
        if($cus_info){
            $cus_data['gender'] = $this->_getGenderFromTitle($cus_info['title']);
        }
        $cus_data['created_at'] = $customer['registerDate'];
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$shopper_group]) ? $this->_notice['config']['customer_group'][$shopper_group] : 1;
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['password']);
        $cusInfo = $this->getListFromListByField($customersExt['object']['virtuemart_userinfos'], 'virtuemart_user_id', $customer['id']);
        if($cusInfo){
            foreach($cusInfo as $cus_info){
                $address = $this->_convertAddress($cus_info, $customersExt);
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success'){
                    try{
                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                        if($cus_info['address_type'] == 'BT'){
                            $cus->setDefaultBilling($address_ipt['mage_id']);
                            $cus->save();
                        }
                        if($cus_info['address_type'] == 'ST'){
                            $cus->setDefaultShipping($address_ipt['mage_id']);
                            $cus->save();
                        }
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
        $query = "SELECT * FROM _DBPRF_virtuemart_orders WHERE virtuemart_order_id > {$id_src} ORDER BY virtuemart_order_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'virtuemart_order_id');
        $paymentIds = $this->duplicateFieldValueFromList($orders['object'], 'virtuemart_paymentmethod_id');
        $shippingIds = $this->duplicateFieldValueFromList($orders['object'], 'virtuemart_shipmentmethod_id');
        $order_id_con = $this->arrayToInCondition($orderIds);
        $payment_id_con = $this->arrayToInCondition($paymentIds);
        $shipping_id_con = $this->arrayToInCondition($shippingIds);
        $ext_query = array(
            'virtuemart_order_items' => "SELECT * FROM _DBPRF_virtuemart_order_items WHERE virtuemart_order_id IN {$order_id_con}",
            'virtuemart_order_userinfos' => "SELECT * FROM _DBPRF_virtuemart_order_userinfos WHERE virtuemart_order_id IN {$order_id_con}",
            'virtuemart_order_histories' => "SELECT * FROM _DBPRF_virtuemart_order_histories WHERE virtuemart_order_id IN {$order_id_con} ORDER BY virtuemart_order_history_id ASC",
        );
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            $payment_table = "virtuemart_paymentmethods_" . $lang_code;
            $shipping_table = "virtuemart_shipmentmethods_" . $lang_code;
            $ext_query[$payment_table] = "SELECT * FROM _DBPRF_{$payment_table} WHERE virtuemart_paymentmethod_id IN {$payment_id_con}";
            $ext_query[$shipping_table] = "SELECT * FROM _DBPRF_{$shipping_table} WHERE virtuemart_shipmentmethod_id IN {$shipping_id_con}";
        }
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
        $countryIds = $this->duplicateFieldValueFromList($ordersExt['object']['virtuemart_order_userinfos'], 'virtuemart_country_id');
        $stateIds = $this->duplicateFieldValueFromList($ordersExt['object']['virtuemart_order_userinfos'], 'virtuemart_state_id');
        $country_id_con = $this->arrayToInCondition($countryIds);
        $state_id_con = $this->arrayToInCondition($stateIds);
        $ext_rel_query = array(
            'virtuemart_countries' => "SELECT * FROM _DBPRF_virtuemart_countries WHERE virtuemart_country_id IN {$country_id_con}",
            'virtuemart_states' => "SELECT * FROM _DBPRF_virtuemart_states WHERE virtuemart_state_id IN {$state_id_con}"
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
        return $order['virtuemart_order_id'];
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
        $userInfo = $this->getListFromListByField($ordersExt['object']['virtuemart_order_userinfos'], 'virtuemart_order_id', $order['virtuemart_order_id']);
        $userBilling = $this->getRowFromListByField($userInfo, 'address_type', 'BT');
        $userShipping = $this->getRowFromListByField($userInfo, 'address_type', 'ST');
        if(!$userShipping){
            $userShipping = $userBilling;
        }
        $address_billing = $this->_convertAddress($userBilling, $ordersExt);
        $address_shipping = $this->_convertAddress($userShipping, $ordersExt);
        $orderPro = $this->getListFromListByField($ordersExt['object']['virtuemart_order_items'], 'virtuemart_order_id', $order['virtuemart_order_id']);
        $carts = array();
        if($orderPro){
            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getMageIdProduct($order_pro['virtuemart_product_id']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['name'] = $order_pro['order_item_name'];
                $cart['sku'] = $order_pro['order_item_sku'];
                $cart['price'] = $order_pro['product_final_price'];
                $cart['original_price'] = $order_pro['product_item_price'];
                $cart['tax_amount'] = $order_pro['product_tax'] * $order_pro['product_quantity'];
                $cart['discount_amount'] = abs($order_pro['product_subtotal_discount']);
                $cart['qty_ordered'] = $order_pro['product_quantity'];
                $cart['row_total'] = $order_pro['product_subtotal_with_tax'] - $order_pro['product_tax'] * $order_pro['product_quantity'] - $order_pro['product_subtotal_discount'];
                if($order_pro['product_attribute']){
                    $options = array();
                    $listOpt = json_decode($order_pro['product_attribute'], 1);
                    if($listOpt){
                        foreach($listOpt as $item){
                            if(is_string($item)){
                                preg_match_all("/<span\s[^>]*>(.*)<\/span>/siU", $item, $matches);
                                if($matches[0]){
                                    $option = array(
                                        'label' => $matches[0][0],
                                        'value' => $matches[0][1],
                                        'print_value' => $matches[0][1],
                                        'option_id' => 'option_pro',
                                        'option_type' => 'drop_down',
                                        'option_value' => 0,
                                        'custom_view' => false
                                    );
                                    $options[] = $option;
                                }
                            }
                            if(is_array($item)){
                                $list_sel_name = "";
                                foreach($item as $stocks){
                                    foreach($stocks as $select_option => $select_name){
                                        if($select_option == "child_id"){
                                            continue;
                                        }
                                        $list_sel_name .= $select_name . " ";
                                    }
                                }
                                if($list_sel_name){
                                    $option = array(
                                        'label' => " ",
                                        'value' => $list_sel_name,
                                        'print_value' => $list_sel_name,
                                        'option_id' => 'option_pro',
                                        'option_type' => 'drop_down',
                                        'option_value' => 0,
                                        'custom_view' => false
                                    );
                                    $options[] = $option;
                                }
                            }
                        }
                        $cart['product_options'] = serialize(array('options' => $options));
                    }
                }
                $carts[] = $cart;
            }
        }
        $customer_id = $this->getMageIdCustomer($order['virtuemart_user_id']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['virtuemart_order_histories'], 'virtuemart_order_id', $order['virtuemart_order_id']);
        $order_status = $orderStatus[0];
        $order_status_id = $order_status['order_status_code'];
        $shipping_amount = $order['order_shipment']+ $order['order_shipment_tax'];
        $store_id = $this->_notice['config']['languages'][$order['order_language']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $lang_code = $this->_convertJmLangToCode($order['order_language']);
        $shipping_table = "virtuemart_shipmentmethods_" . $lang_code;
        $shipment = $this->getRowFromListByField($ordersExt['object'][$shipping_table], 'virtuemart_shipmentmethod_id', $order['virtuemart_shipmentmethod_id']);

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $userBilling['email'];
        $order_data['customer_firstname'] = $userBilling['first_name'];
        $order_data['customer_middlename'] = $userBilling['middle_name'];
        $order_data['customer_lastname'] = $userBilling['last_name'];
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status']){
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['order_subtotal']);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $shipping_amount;
        $order_data['base_shipping_amount'] = $shipping_amount;
        $order_data['base_shipping_invoiced'] = $shipping_amount;
        $order_data['shipping_description'] = $shipment ? $shipment['shipment_name'] : "Flat Rate";
        $order_data['tax_amount'] = $order['order_tax'];
        $order_data['base_tax_amount'] = $order['order_tax'];
        $order_data['discount_amount'] = abs($order['order_discountAmount']);
        $order_data['base_discount_amount'] = abs($order['order_discountAmount']);
        $order_data['grand_total'] = $this->incrementPriceToImport($order['order_total']);
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
        $order_data['created_at'] = $order['created_on'];

        $data = array();
        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['virtuemart_order_id'];
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
        $orderStatus = $this->getListFromListByField($ordersExt['object']['virtuemart_order_histories'], 'virtuemart_order_id', $order['virtuemart_order_id']);
        foreach($orderStatus as $key => $order_status){
            $order_status_data = array();
            $order_status_id = $order_status['order_status_code'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if($order_status_data['status']){
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if($key == 0){
                $lang_code = $this->_convertJmLangToCode($order['order_language']);
                $shipping_table = "virtuemart_shipmentmethods_" . $lang_code;
                $payment_table = "virtuemart_paymentmethods_" . $lang_code;
                $shipment = $this->getRowFromListByField($ordersExt['object'][$shipping_table], 'virtuemart_shipmentmethod_id', $order['virtuemart_shipmentmethod_id']);
                $payment = $this->getRowFromListByField($ordersExt['object'][$payment_table], 'virtuemart_paymentmethod_id', $order['virtuemart_paymentmethod_id']);
                $order_status_data['comment'] = "<b>Reference order #".$order['virtuemart_order_id']."</b><br />";
                if($payment){
                    $order_status_data['comment'] .= "<b>Payment method: </b>".$payment['payment_name']."<br />";
                }
                if($shipment){
                    $order_status_data['comment'] .= "<b>Shipping method: </b> ".$shipment['shipment_name']."<br />";
                }
                if($order['customer_note']){
                    $order_status_data['comment'] .= "<b>Shopper's note: </b>" . $order['customer_note'] ."<br />";
                }
                $order_status_data['comment'] .= $order_status['comments'];
            } else {
                $order_status_data['comment'] = $order_status['comments'];
            }
            $order_status_data['is_customer_notified'] = $order_status['customer_notified'];
            $order_status_data['updated_at'] = $order_status['modified_on'];
            $order_status_data['created_at'] = $order_status['created_on'];
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
        $query = "SELECT * FROM _DBPRF_virtuemart_rating_reviews WHERE virtuemart_rating_review_id > {$id_src} ORDER BY virtuemart_rating_review_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews){
        $userIds = $this->duplicateFieldValueFromList($reviews['object'], 'created_by');
        $user_id_con = $this->arrayToInCondition($userIds);
        $ext_query = array(
            'users' => "SELECT * FROM _DBPRF_users WHERE id IN {$user_id_con}"
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
        return $review['virtuemart_rating_review_id'];
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
        $product_mage_id = $this->getMageIdProduct($review['virtuemart_product_id']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['virtuemart_product_id']} import failed. Error: Product Id = {$review['virtuemart_product_id']} not imported!")
            );
        }
        $customer_id  = $this->getMageIdCustomer($review['created_by']);
        $user = $this->getRowFromListByField($reviewsExt['object']['users'], 'id', $review['created_by']);
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['published'] == '1')? 1 : 3;
        $data['title'] = " ";
        $data['detail'] = $review['comment'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($customer_id)? $customer_id : null;
        $data['nickname'] = $user ? $user['name'] : " ";
        $data['rating'] = (int) $review['review_rating'];
        $data['created_at'] = $review['created_on'];
        $data['review_id_import'] = $review['virtuemart_rating_review_id'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => "success",
            'data' => $data
        );
    }

############################################## Extend function #################################################

    /**
     * Get config of VirtueMart
     */
    protected function _getVmConfigByKey($config, $key){
        $result = false;
        $config = explode('|', $config);
        foreach($config as $item){
            $item = explode('=',$item);
            if($item[0] == $key && !empty($item[1])){
                if($item[0]=='offline_message' ){
                    $result = @unserialize(base64_decode($item[1]));
                } else{
                    $value = @unserialize($item[1] );
                    if($value) $result = $value;
                }
                break ;
            }
        }
        return $result;
    }

    /**
     * Convert VirtueMart language code (use for table language suffix) to Joomla language code. Exp: en_gb -> en-GB
     */
    protected function _convertCodeToJmLang($lang_code){
        $result = "";
        $code = explode('_', $lang_code);
        if($code){
            $result = strtolower($code[0]).'-'.strtoupper($code[1]);
        }
        return $result;
    }

    /**
     * Get path file language in Joomla
     */
    protected function _createLanguageFilePath($code){
        $path = 'language/'.$code.'/'.$code.'.xml';
        return $path;
    }

    /**
     * Get data from Joomla language file
     */
    protected function _getParamFromContentXml($content, $field, $level = 1){
        $tag = @simplexml_load_string($content);
        for($i = 0; $i < $level; $i++){
            if($field[$i] && $tag->$field[$i]){
                $tag = $tag->$field[$i];
            }
        }
        return (string)$tag;
    }

    /**
     * Convert Joomla language code to VirtueMart language code (table language suffix)
     */
    protected function _convertJmLangToCode($tag){
        $tag = str_replace('-', '_', $tag);
        $tag = strtolower($tag);
        return $tag;
    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_virtuemart_categories WHERE virtuemart_category_id = {$parent_id}"
        ));
        if(!$categories || $categories['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $categoriesExt = $this->getCategoriesExt($categories);
        if($categoriesExt['result'] != 'success'){
            return $categoriesExt;
        }
        $category = array();
        if($categories['object']){
            $category = $categories['object'][0];
        }
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
     * Import children product with attributes and create data for magento configurable product
     */
    protected function _importChildrenProduct($parent, $productsExt){
        $proChild = $this->getListFromListByField($productsExt['object']['virtuemart_products_children'], 'product_parent_id', $parent['virtuemart_product_id']);
        $attrIpt = $this->_importAttribute($parent, $proChild, $productsExt);
        if($attrIpt['result'] != 'success'){
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Product Id = {$parent['virtuemart_product_id']} import failed. Error: Product attribute could not be created!")
            );
        }
        if($attrIpt['type'] == 'change'){
            return array(
                'result' => "success",
                'data' => array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
                )
            );
        }
        $configurable_products_data = $configurable_attributes_data = $pro_attr_opt = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        foreach($proChild as $pro_child){
            $pro_child_data = array(
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
            );
            $pro_child_convert = $this->_convertProduct($pro_child, $productsExt, true);
            if($pro_child_convert['result'] != 'success'){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent['virtuemart_product_id']} import failed. Error: Product children could not create!(Error code: Product child data not found.)")
                );
            }
            $pro_child_data = array_merge($pro_child_convert['data'], $pro_child_data);
            $pro_child_ipt = $this->_process->product($pro_child_data);
            if($pro_child_ipt['result'] != 'success'){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent['virtuemart_product_id']} import failed. Error: Product children could not create!(Error code: " . $pro_child_ipt['msg'] . ".)")
                );
            }
            $this->productSuccess($pro_child['virtuemart_product_id'], $pro_child_ipt['mage_id']);
            foreach($attrIpt['data'] as $attr_ipt){
                $key = "option_" . $pro_child['virtuemart_product_id'];
                $option_ids = $attr_ipt['data']['option_ids'];
                if(isset($option_ids[$key])){
                    $this->setProAttrSelect($entity_type_id, $attr_ipt['data']['attribute_id'], $pro_child_ipt['mage_id'], $option_ids[$key]);
                    $pro_attr_data = array(
                        'label' => $attr_ipt['value'][$pro_child['virtuemart_product_id']],
                        'attribute_id' => $attr_ipt['data']['attribute_id'],
                        'value_index' => $option_ids[$key],
                        'is_percent' => 0,
                        'pricing_value' => '',
                    );
                    $configurable_products_data[$pro_child_ipt['mage_id']][] = $pro_attr_data;
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
            $valueSrc = array_unique($attr_ipt['value']);
            $values = array();
            foreach($valueSrc as $pro_id => $value_src){
                $option_key = "option_" . $pro_id;
                $value = array(
                    'label' => $value_src,
                    'attribute_id' => $attr_ipt['data']['attribute_id'],
                    'value_index' => $option_ids[$option_key],
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

    /**
     * Convert general product data from product parent or children of VirtueMart product
     */
    protected function _convertProduct($product, $productsExt, $is_variant = false){
        $pro_data = $category_ids = $catSrc = $mediaSrc = $priceSrc = $manSrc = array();
        $lang_def_code = $this->_convertJmLangToCode($this->_notice['config']['default_lang']);
        if($product['product_parent_id']){
            $catSrc = $this->getListFromListByField($productsExt['object']['virtuemart_product_categories_children'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $mediaSrc = $this->getListFromListByField($productsExt['object']['virtuemart_product_medias_children'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            if(!$mediaSrc){
                $parentMediaSrc = $this->getListFromListByField($productsExt['object']['virtuemart_product_medias'], 'virtuemart_product_id', $product['product_parent_id']);
                if($parentMediaSrc){
                    foreach($parentMediaSrc as $key => $parent_media_src){
                        $media_src = $this->getRowFromListByField($productsExt['object']['virtuemart_medias'], 'virtuemart_media_id', $parent_media_src['virtuemart_media_id']);
                        if($media_src){
                            $mediaSrc[$key] = $media_src;
                        }
                    }
                }
            }
            $priceSrcList = $this->getListFromListByField($productsExt['object']['virtuemart_product_prices_children'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $manSrc = $this->getRowFromListByField($productsExt['object']['virtuemart_product_manufacturers_children'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $pro_table_def = "virtuemart_products_children_" . $lang_def_code;
        } else {
            $catSrc = $this->getListFromListByField($productsExt['object']['virtuemart_product_categories'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $mediaSrc = $this->getListFromListByField($productsExt['object']['virtuemart_product_medias'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $priceSrcList = $this->getListFromListByField($productsExt['object']['virtuemart_product_prices'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $manSrc = $this->getRowFromListByField($productsExt['object']['virtuemart_product_manufacturers'], 'virtuemart_product_id', $product['virtuemart_product_id']);
            $pro_table_def = "virtuemart_products_" . $lang_def_code;
        }
        if($catSrc){
            foreach($catSrc as $cat_src){
                $cat_src_id = $cat_src['virtuemart_category_id'];
                $cat_mage_id = $this->getMageIdCategory($cat_src_id);
                if($cat_mage_id){
                    $category_ids[] = $cat_mage_id;
                }
            }
        }
        $pro_def = $this->getRowFromListByField($productsExt['object'][$pro_table_def], 'virtuemart_product_id', $product['virtuemart_product_id']);
        if(!$pro_def){
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Product Id = {$product['virtuemart_product_id']} import failed. Error: Product data not exists!")
            );
        }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = $this->createProductSku($product['product_sku'], $this->_notice['config']['languages']);
        $pro_data['name'] = $pro_def['product_name'];
        $pro_data['description'] = $this->changeImgSrcInText($pro_def['product_desc'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_def['product_s_desc'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $pro_def['customtitle'];
        $pro_data['meta_keyword'] = $pro_def['metakey'];
        $pro_data['meta_description'] = $pro_def['metadesc'];
        $pro_data['weight'] = ($product['product_weight'])? $product['product_weight'] : 0;
        $pro_data['status'] = ($product['published'] == 1)? 1 : 2;
        $tierPrices = array();
        if($priceSrcList){
            $priceSrc = $priceSrcList[0];
            $pro_data['price'] = $priceSrc['product_price'];
            $tax_src_id = $priceSrc['product_tax_id'];
            if($tax_src_id && $tax_mage_id = $this->getMageIdTaxProduct($tax_src_id)){
                $pro_data['tax_class_id'] = $tax_mage_id;
            } else {
                $pro_data['tax_class_id'] = 0;
            }
            if((count($priceSrcList) == 1) && $priceSrc['product_override_price'] != 0 && $priceSrc['override'] == 1){
                $pro_data['special_price'] =  $priceSrc['product_override_price'];
            }
            if(count($priceSrcList) > 1){
                foreach($priceSrcList as $price_src){
                    $tierPrices[] = array(
                        'website_id'  => 0,
                        'cust_group'  => isset($this->_notice['config']['customer_group'][$price_src['virtuemart_shoppergroup_id']]) ? $this->_notice['config']['customer_group'][$price_src['virtuemart_shoppergroup_id']] : Mage_Customer_Model_Group::CUST_GROUP_ALL,
                        'price_qty'   => $price_src['price_quantity_start'],
                        'price'       => $price_src['product_price']
                    );
                }
            }
        } else {
            $pro_data['price'] = 0;
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['tier_price'] = $tierPrices;
        $pro_data['created_at'] = $product['created_on'];
        if($is_variant){
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
        }else{
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['product_in_stock'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['product_in_stock'] < 1)? 0 : 1,
            'qty' => ($product['product_in_stock'])? $product['product_in_stock'] : 0,
        );
        if($manSrc && $manufacture_mage_id = $this->getMageIdManufacturer($manSrc['virtuemart_manufacturer_id'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        if($mediaSrc){
            foreach($mediaSrc as $key => $media_src){
                if($product['product_parent_id']){
                    $media = $media_src;
                } else {
                    $media = $this->getRowFromListByField($productsExt['object']['virtuemart_medias'], 'virtuemart_media_id', $media_src['virtuemart_media_id']);
                }
                if(!$media){
                    continue ;
                }
                if($media['file_url']){
                    $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $media['file_url'], 'catalog/product', false, true);
                    if(!$image_path){
                        $image_path = $this->downloadImage($this->_cart_url, $media['file_url'], 'catalog/product', false, true);
                    }
                    if($key == 0){
                        $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                    }else{
                        $pro_data['image_gallery'][] = array('path' => $image_path, 'label' => '') ;
                    }
                }
            }
        }
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $jm_lang => $store_id){
            if($jm_lang == $this->_notice['config']['default_lang']){
                continue ;
            }
            $lang_code = $this->_convertJmLangToCode($jm_lang);
            if($product['product_parent_id']){
                $table = "virtuemart_products_children_".$lang_code;
            } else {
                $table = "virtuemart_products_".$lang_code;
            }
            $pro_desc = $this->getRowFromListByField($productsExt['object'][$table], 'virtuemart_product_id', $product['virtuemart_product_id']);
            if(!$pro_desc && $product['product_parent_id']){
                $table = "virtuemart_products_".$lang_code;
            }
            $pro_desc = $this->getRowFromListByField($productsExt['object'][$table], 'virtuemart_product_id', $product['virtuemart_product_id']);
            if(!$pro_desc){
                continue;
            }
            $store_data = array();
            $store_data['store_id'] = $store_id;
            $store_data['name'] = $pro_desc['product_name'];
            $store_data['description'] = $this->changeImgSrcInText($pro_desc['product_desc'], $this->_notice['config']['add_option']['img_des']);
            $store_data['short_description'] = $this->changeImgSrcInText($pro_desc['product_s_desc'], $this->_notice['config']['add_option']['img_des']);
            $store_data['meta_title'] = $pro_desc['customtitle'];
            $store_data['meta_keyword'] = $pro_desc['metakey'];
            $store_data['meta_description'] = $pro_desc['metadesc'];
            $multi_store[] = $store_data;
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
     * Import attribute by custom field type generic child or plugin stockable with cart variant
     */
    protected function _importAttribute($parent, $proChild, $productsExt){
        $genChild = $this->_importAttributeByGenericChild($parent, $proChild, $productsExt);
        if($genChild['result'] != 'success'){
            return $genChild;
        }
        if($genChild['type'] == 'change'){
            $plgChild = $this->_importAttributeByPluginStock($parent, $proChild, $productsExt);
            return $plgChild;
        }
        return $genChild;
    }

    /**
     * Import attribute by custom field generic child type
     */
    protected function _importAttributeByGenericChild($parent, $proChild, $productsExt){
        $proCus = $this->getListFromListByField($productsExt['object']['virtuemart_product_customfields'], 'virtuemart_product_id', $parent['virtuemart_product_id']);
        $cusIds = $this->duplicateFieldValueFromList($proCus, 'virtuemart_custom_id');
        $attrSrc = $attrIpt = $attr_src = $opt_label = array();
        foreach($cusIds as $cus_id){
            $custom = $this->getRowFromListByField($productsExt['object']['virtuemart_customs'], 'virtuemart_custom_id', $cus_id);
            if($custom && $custom['field_type'] == 'A'){
                $attrSrc = $custom;
                break ;
            }
        }
        if(!$attrSrc){
            return array(
                'result' => 'success',
                'type' => 'change',
                'data' => array()
            );
        }
        $data = array(
            'entity_type_id' => Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId(),
            'attribute_code' => $this->joinTextToKey($attrSrc['custom_title'], 27, '_'),
            'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
            'frontend_input' => 'select',
            'frontend_label' => array($attrSrc['custom_title']),
            'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'is_configurable' => true,
            'apply_to' => array()
        );
        $attr_src['label'] = $attrSrc['custom_title'];
        $lang_def_code = $this->_convertJmLangToCode($this->_notice['config']['default_lang']);
        $pro_key_def = "virtuemart_products_children_" . $lang_def_code;
        $values = array();
        foreach($proChild as $pro_child){
            $pro_def = $this->getRowFromListByField($productsExt['object'][$pro_key_def], 'virtuemart_product_id', $pro_child['virtuemart_product_id']);
            if(!$pro_def){
                continue ;
            }
            $key = "option_" . $pro_child['virtuemart_product_id'];
            $values[$key] = array(
                0 => $pro_def['product_name']
            );
            $opt_label[$pro_child['virtuemart_product_id']] = $pro_def['product_name'];
        }
        if(!$values){
            return array(
                'result' => 'success',
                'type' => 'change',
                'data' => array()
            );
        }
        $data['option']['value'] = $values;
        $attr_ipt = $this->_process->attribute($data, array(
            'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'is_configurable' => true,
            'apply_to' => array()
        ));
        if(!$attr_ipt){
            return array(
                'result' => "warning",
                'msg' => ""
            );
        }
        $attr_src['value'] = $opt_label;
        $attr_src['data'] = $attr_ipt;
        $attrIpt[] = $attr_src;
        return array(
            'result' => 'success',
            'type' => '',
            'data' => $attrIpt
        );
    }

    /**
     * Import attribute by custom field plugin stockable type
     */
    protected function _importAttributeByPluginStock($parent, $proChild, $productsExt){
        $proCus = $this->getListFromListByField($productsExt['object']['virtuemart_product_customfields'], 'virtuemart_product_id', $parent['virtuemart_product_id']);
        $cusIds = $this->duplicateFieldValueFromList($proCus, 'virtuemart_custom_id');
        $cusSrc = array();
        foreach($cusIds as $cus_id){
            $custom = $this->getRowFromListByField($productsExt['object']['virtuemart_customs'], 'virtuemart_custom_id', $cus_id);
            if($custom && $custom['field_type'] == 'E' && $custom['custom_element'] == 'stockable' && $custom['is_cart_attribute']){
                $cusSrc[] = $custom;
                break ;
            }
        }
        if(!$cusSrc){
            return array(
                'result' => 'success',
                'type' => 'change',
                'data' => array()
            );
        }
        $attrIpt = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        foreach($cusSrc as $cus_src){
            $pro_cus = $this->getRowFromListByField($proCus, 'virtuemart_custom_id', $cus_src['virtuemart_custom_id']);
            if(!$pro_cus){
                continue ;
            }
            $cusParam = $this->_convertPluginCustomParams($cus_src['custom_params']);
            $proVal = json_decode($pro_cus['custom_param'], 1);
            for($i = 1; $i < 5; $i++){
                $attr_save = $attr_data = array();
                $select_name = "selectname" . $i;
                if(isset($cusParam[$select_name]) && $cusParam[$select_name] != ""){
                    $attr_data = array(
                        'entity_type_id' => $entity_type_id,
                        'attribute_code' => $this->joinTextToKey($cusParam[$select_name], 27, '_'),
                        'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                        'frontend_input' => 'select',
                        'frontend_label' => array($cusParam[$select_name]),
                        'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                        'is_configurable' => true,
                        'apply_to' => array()
                    );
                    $attr_save['label'] = $cusParam[$select_name];
                    $values = $opt_label = array();
                    if(isset($proVal['child']) && $proVal['child']){
                        foreach($proVal['child'] as $pro_id => $pro_val){
                            if($pro_val['is_variant']){
                                $key = "option_" . $pro_id;
                                $select_option = "selectoptions" . $i;
                                $values[$key][0] = (isset($pro_val[$select_option])) ? $pro_val[$select_option] : " ";
                                $opt_label[$pro_id] = (isset($pro_val[$select_option])) ? $pro_val[$select_option] : " ";
                            }
                        }
                    }
                    if(!$values){
                        continue ;
                    }
                    $attr_save['value'] = $opt_label;
                    $attr_data['option']['value'] = $values;
                    $attr_ipt = $this->_process->attribute($attr_data, array(
                        'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                        'is_configurable' => true,
                        'apply_to' => array()
                    ));
                    if(!$attr_ipt){
                        return array(
                            'result' => "warning",
                            'msg' => ""
                        );
                    }
                    $attr_save['data'] = $attr_ipt;
                    $attrIpt[] = $attr_save;
                }
            }
        }
        if(!$attrIpt){
            return array(
                'result' => 'success',
                'type' => 'change',
                'data' => array()
            );
        }
        return array(
            'result' => 'success',
            'type' => '',
            'data' => $attrIpt
        );
    }

    /**
     * Convert custom field params to array
     */
    protected function _convertPluginCustomParams($string){
        $params = explode('|', $string);
        $data = array();
        foreach ($params as $item) {
            $item = explode('=', $item);
            $key = $item[0];
            unset($item[0]);
            $item = implode('=', $item);
            if (!empty($item)) {
                if(strpos($key, 'selectoptions') !== false){
                    $option = json_decode($item, 1);
                    $option = str_replace( "\r", "" , $option);
                    $options = explode("\n", $option);
                    $data[$key] = $option ? $options : array();
                } else {
                    $data[$key] = json_decode($item, 1);
                }
            }
        }
        return $data;
    }

    /**
     * Create Magento custom option from VirtueMart custom field which is cart variant
     */
    protected function _createProductCustomOption($product, $productsExt){
        $proCus = $this->getListFromListByField($productsExt['object']['virtuemart_product_customfields'], 'virtuemart_product_id', $product['virtuemart_product_id']);
        if(!$proCus){
            return false;
        }
        $cusIds = $this->duplicateFieldValueFromList($proCus, 'virtuemart_custom_id');
        $options = array();
        foreach($cusIds as $cus_id){
            $custom = $this->getRowFromListByField($productsExt['object']['virtuemart_customs'], 'virtuemart_custom_id', $cus_id);
            if(!$custom || !$custom['is_cart_attribute'] || $custom['admin_only']){
                continue;
            }
            $proCusVal = $this->getListFromListByField($proCus, 'virtuemart_custom_id', $cus_id);
            if(!$proCusVal){
                continue ;
            }
            $opt_type_import = $this->_getOptionTypeByCustomType($custom['field_type']);
            if(!$opt_type_import){
                continue ;
            }
            $option = array(
                'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($opt_type_import),
                'type' => $opt_type_import,
                'is_require' => 1,
                'title' => $custom['custom_title'],
            );
            if(in_array($custom['field_type'], array('S', 'I', 'V'))){
                $option['values'] = array();
                foreach($proCusVal as $pro_cus_val){
                    $value = array(
                        'option_type_id' => -1,
                        'title' => $pro_cus_val['custom_value'],
                        'price' => ($pro_cus_val['custom_price'])? $pro_cus_val['custom_price'] : 0,
                        'price_type' => 'fixed',
                    );
                    $option['values'][] = $value;
                }
            }
            if (in_array($custom['field_type'], array('B'))){
                $pro_cus_val = $proCusVal[0];
                $value = array(
                    'option_type_id' => -1,
                    'title' => 'No',
                    'price' => ($pro_cus_val['custom_price'])? $pro_cus_val['custom_price'] : 0,
                    'price_type' => 'fixed',
                );
                $option['values'][] = $value;
            }
            if (in_array($custom['field_type'], array('X', 'Y', 'D', 'T', 'M'))){
                $pro_cus_val = $proCusVal[0];
                $option['price'] = ($pro_cus_val['custom_price'])? $pro_cus_val['custom_price'] : 0;
                $option['price_type'] = 'fixed';
            }
            $options[] = $option;
        }
        return $options;
    }

    /**
     * Convert VirtueMart custom field type to Magento custom option type
     */
    protected function _getOptionTypeByCustomType($type_name){
        $types = array(
            'S' => 'radio',
            'I' => 'radio',
//            'P' => 'radio',
            'B' => 'radio',
            'D' => 'date',
            'T' => 'time',
            'M' => 'file',
            'V' => 'drop_down',
            'X' => 'area',
            'Y' => 'area'
        );
        return isset($types[$type_name])? $types[$type_name]: false;
    }

    /**
     * Import VirtueMart custom field is not cart variant
     */
    protected function _importProductCustomField($product_mage_id, $product, $productsExt){
        $proCus = $this->getListFromListByField($productsExt['object']['virtuemart_product_customfields'], 'virtuemart_product_id', $product['virtuemart_product_id']);
        if(!$proCus){
            return ;
        }
        $cusIds = $this->duplicateFieldValueFromList($proCus, 'virtuemart_custom_id');
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        foreach($cusIds as $cus_id){
            $custom = $this->getRowFromListByField($productsExt['object']['virtuemart_customs'], 'virtuemart_custom_id', $cus_id);
            if(!$custom || $custom['is_cart_attribute'] || !in_array($custom['field_type'], array('S', 'I', 'D', 'T', 'X', 'Y', 'B'))){
                continue ;
            }
            $pro_cus_val = $this->getRowFromListByField($proCus, 'virtuemart_custom_id', $cus_id);
            if(!$pro_cus_val){
                continue ;
            }
            $attr_code = $this->joinTextToKey($custom['custom_title'], 27, '_');
            if(!$attr_code){
                continue;
            }
            if(in_array($custom['field_type'], array('S', 'I', 'B'))){
                $attr_type = 'text';
            } else if (in_array($custom['field_type'], array('D', 'T'))){
                $attr_type = 'date';
            } else {
                $attr_type = 'textarea';
            }
            $data = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' => $attr_code,
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => $attr_type,
                'frontend_label' => array($custom['custom_title']),
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
                'is_configurable' => false,
                'is_visible_on_front' => true,
                'option' => array(
                    'value' => array()
                )
            );
            $attr_ipt = $this->_process->attribute($data);
            if(!$attr_ipt){
                continue ;
            }
            if(in_array($custom['field_type'], array('S', 'I'))){
                $this->setProAttrVarchar($entity_type_id, $attr_ipt['attribute_id'], $product_mage_id, $pro_cus_val['custom_value']);
            } if(in_array($custom['field_type'], array('B'))){
                $varchar = ($pro_cus_val['custom_value'])? "Yes" : "No";
                $this->setProAttrVarchar($entity_type_id, $attr_ipt['attribute_id'], $product_mage_id, $varchar);
            } else if (in_array($custom['field_type'], array('D', 'T'))){
                $this->setProAttrDate($entity_type_id, $attr_ipt['attribute_id'], $product_mage_id, $pro_cus_val['custom_value']);
            } else {
                $this->setProAttrText($entity_type_id, $attr_ipt['attribute_id'], $product_mage_id, $pro_cus_val['custom_value']);
            }
        }
        return ;
    }

    /**
     * Detected gender of VirtueMart use for magento gender
     *
     * @param string $title
     * @return int
     */
    protected function _getGenderFromTitle($title){
        $result = 2;
        $male = array('Mr.','Dr.','Prof.');
        if(in_array($title,$male)){
            $result = 1;
        }
        return $result;
    }

    /**
     * Convert VirtueMart address table construct to magento address construct
     *
     * @param array $cus_info : Row of table address
     * @param array $extra : Data country and state of user
     * @return array
     */
    protected function _convertAddress($cus_info, $extra){
        $address = array();
        $address['firstname'] = $cus_info['first_name'];
        $address['middlename'] = $cus_info['middle_name'];
        $address['lastname'] = $cus_info['last_name'];
        $address['street'] = $cus_info['address_1'] . "\n" . $cus_info['address_2'];
        $address['postcode'] = $cus_info['zip'];
        $address['city'] = $cus_info['city'];
        $address['telephone'] = $cus_info['phone_1'] ? $cus_info['phone_1'] : $cus_info['phone_2'];
        $address['company'] = $cus_info['company'];
        $address['fax'] = $cus_info['fax'];
        if($cus_info['virtuemart_country_id']){
            $country = $this->getRowFromListByField($extra['object']['virtuemart_countries'], 'virtuemart_country_id', $cus_info['virtuemart_country_id']);
            if($country){
                $address['country_id'] = $country['country_2_code'];
                if($cus_info['virtuemart_state_id']){
                    $state = $this->getRowFromListByField($extra['object']['virtuemart_states'], 'virtuemart_state_id', $cus_info['virtuemart_state_id']);
                    if($state){
                        $region_id = $this->getRegionId($state['state_name'], $country['country_2_code']);
                        if($region_id){
                            $address['region_id'] = $region_id;
                        } else{
                            $address['region'] = $state['state_name'];
                        }
                    } else {
                        $address['region_id'] = 0;
                    }
                } else {
                    $address['region_id'] = 0;
                }
            }
        }
        return $address;
    }

    protected function _importRelatedProduct($product_mage_id, $product, $productsExt){
        $products_links = Mage::getModel('catalog/product_link_api');
        $proCus = $this->getListFromListByField($productsExt['object']['virtuemart_product_customfields'], 'virtuemart_product_id', $product['virtuemart_product_id']);
        if($proCus) {
            $cusIds = $this->duplicateFieldValueFromList($proCus, 'virtuemart_custom_id');
            foreach ($cusIds as $cus_id) {
                $custom = $this->getRowFromListByField($productsExt['object']['virtuemart_customs'], 'virtuemart_custom_id', $cus_id);
                if ($custom && $custom['field_type'] == 'R') {
                    $proCusVal = $this->getListFromListByField($proCus, 'virtuemart_custom_id', $cus_id);
                    if (!$proCusVal) {
                        continue;
                    }
                    foreach ($proCusVal as $pro_cus_val) {
                        if ($pro_id_related = $this->getMageIdProduct($pro_cus_val['custom_value'])) {
                            $related_data = array('position' => $pro_cus_val['ordering']);
                            $products_links->assign("related", $product_mage_id, $pro_id_related, $related_data);
                        } else {
                            continue;
                        }
                    }
                }
            }
        }

        $proSrc = $this->getListFromListByField($productsExt['object']['virtuemart_product_customfields'], 'custom_value', $product['virtuemart_product_id']);
        if($proSrc){
            $cusIds = $this->duplicateFieldValueFromList($proSrc, 'virtuemart_custom_id');
            foreach ($cusIds as $cus_id) {
                $custom = $this->getRowFromListByField($productsExt['object']['virtuemart_customs'], 'virtuemart_custom_id', $cus_id);
                if ($custom && $custom['field_type'] == 'R') {
                    $proCusVal = $this->getListFromListByField($proSrc, 'virtuemart_custom_id', $cus_id);
                    if (!$proCusVal) {
                        continue;
                    }
                    foreach ($proCusVal as $pro_cus_val) {
                        if ($pro_id_src = $this->getMageIdProduct($pro_cus_val['virtuemart_product_id'])) {
                            $related_data = array('position' => $pro_cus_val['ordering']);
                            $products_links->assign("related", $pro_id_src, $product_mage_id, $related_data);
                        } else {
                            continue;
                        }
                    }
                }
            }
        }
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
        $query = "SELECT * FROM _DBPRF_virtuemart_calcs ORDER BY virtuemart_calc_id ASC";
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
        $query = "SELECT * FROM _DBPRF_virtuemart_manufacturers ORDER BY virtuemart_manufacturer_id ASC";
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
        $query = "SELECT * FROM _DBPRF_virtuemart_categories ORDER BY virtuemart_category_id ASC";
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
        $query = "SELECT * FROM _DBPRF_virtuemart_products WHERE product_parent_id = 0 ORDER BY virtuemart_product_id ASC";
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
        $query = "SELECT * FROM _DBPRF_users ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_virtuemart_orders ORDER BY virtuemart_order_id ASC";
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
        $query = "SELECT * FROM _DBPRF_virtuemart_rating_reviews ORDER BY virtuemart_rating_review_id ASC";
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