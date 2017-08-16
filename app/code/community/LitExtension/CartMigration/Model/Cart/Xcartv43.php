<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Xcartv43
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_taxes WHERE taxid > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturerid > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categoryid > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE productid > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orderid > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_product_reviews WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
                "languages" => "SELECT * FROM _DBPRF_config AS cfg WHERE cfg.name = 'default_customer_language'",
                "currencies" => "SELECT * FROM _DBPRF_config AS cfg WHERE cfg.name = 'currency_symbol'",
                "country" => "SELECT * FROM _DBPRF_config AS cfg WHERE cfg.name = 'default_country'"
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies'] && $object['country']) {
            $def_cur_code = substr($object['country']['0']['value'], 0, 2);
            $def_currencies = $this->_getDataImport($this->_getUrlConnector('query'),array(
                'query' => "SELECT * FROM _DBPRF_currencies AS cur, _DBPRF_countries AS co
                            WHERE cur.symbol = '{$object['currencies']['0']['value']}'
                            AND co.code = '{$object['country']['0']['value']}'
                            AND co.code_N3 = cur.code_int"
            ));
            if(!$def_currencies || $def_currencies['result'] != 'success' || !$def_currencies['object']){
                $def_currencies = $this->_getDataImport($this->_getUrlConnector('query'),array(
                    'query' => "SELECT * FROM _DBPRF_currencies AS cur, _DBPRF_countries AS co
                            WHERE cur.symbol = '{$object['currencies']['0']['defvalue']}'
                            AND co.code = '{$def_cur_code}'
                            AND co.code_N3 = cur.code_int"
                ));
            }
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['value']) ? $object['languages']['0']['value'] : 'en';
            $this->_notice['config']['default_currency'] = isset($def_currencies['object']['0']['code_int']) ? $def_currencies['object']['0']['code_int'] : 1;
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT DISTINCT lg.code, lg_code.* FROM _DBPRF_languages AS lg LEFT JOIN _DBPRF_language_codes AS lg_code ON lg.code = lg_code.code",
                "languages_code" => "SELECT DISTINCT code FROM _DBPRF_languages",
                "orders_status" => "SELECT * FROM _DBPRF_languages AS lg
                                    WHERE lg.code = '{$this->_notice['config']['default_lang']}' AND lg.name IN ('lbl_not_finished','lbl_queued','lbl_pre_authorized','lbl_processed','lbl_backordered','lbl_declined','lbl_failed','lbl_complete')",
                'memberships' => "SELECT * FROM _DBPRF_memberships",
                "languages_xcart41" => "SELECT DISTINCT code FROM _DBPRF_languages"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        if($obj['languages']){
            foreach($obj['languages'] as $language_row){
                $lang_code = $language_row['code'];
                $lang_name = $language_row['language'];
                $language_data[$lang_code] = $lang_name;
            }
        }elseif($obj['languages_code']){
            $lis_lg_codes = array();
            foreach($obj['languages_code'] AS $row){
                $lg_codes = $this->_getDataImport($this->_getUrlConnector('query'),array(
                    'query' => "SELECT * FROM _DBPRF_languages WHERE name = 'language_{$row['code']}' AND code = '{$row['code']}'"
                ));
                if($lg_codes['result'] == 'success'){
                    $lis_lg_codes = array_merge($lis_lg_codes, $lg_codes['object']);
                }
            }
            foreach($lis_lg_codes as $language_row){
                $lang_code = $language_row['code'];
                $lang_name = $language_row['value'];
                $language_data[$lang_code] = $lang_name;
            }
        }else{
            foreach($obj['languages_xcart41'] as $language_row){
                $lang_code = $language_row['code'];
                $lang_name = $language_row['code'];
                $language_data[$lang_code] = $lang_name;
            }
        }
        $currency_data[ $this->_notice['config']['default_currency']] = isset($def_currencies['object']['0']['name']) ? $def_currencies['object']['0']['name'] : $object['currencies']['0']['value'];
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_value = $this->_getOrderStatusValueByKey($order_status_row['name']);
            $order_status_name = $order_status_row['value'];
            $order_status_data[$order_status_value] = $order_status_name;
        }
        $customer_group_data[0] = 'Not member';
        foreach($obj['memberships'] as $membership_row){
            $membership_id = $membership_row['membershipid'];
            $membership_name = $membership_row['membership'];
            $customer_group_data[$membership_id] = $membership_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['customer_group_data'] = $customer_group_data;
        $this->_notice['customers']['id_src'] = '';
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_taxes WHERE taxid > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturerid > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categoryid > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE productid > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orderid > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_product_reviews WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
        $data = array();
        foreach($this->_notice['config']['currencies_data'] as $currency_id => $value){
            $currency_mage = $this->_notice['config']['currencies'][$currency_id];
            $data[$currency_mage] = $value;
        }
        $this->_process->currencyRate(array(
            $default_cur => $data
        ));
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
        $query = "SELECT * FROM _DBPRF_taxes AS tx WHERE taxid > {$id_src} ORDER BY tx.taxid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'taxid');
        $taxIds_in_query = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            "tax_rates" => "SELECT * FROM _DBPRF_tax_rates AS tr WHERE tr.taxid IN {$taxIds_in_query}"
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
        $zoneIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rates'], 'zoneid');
        $zoneIds_in_query = $this->arrayToInCondition($zoneIds);
        $ext_rel_query = array(
            "zone_element" => "SELECT * FROM _DBPRF_zone_element AS ze
                                        WHERE ze.zoneid IN {$zoneIds_in_query}
                                        AND ze.field_type IN ('C','S')",
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
        return $tax['taxid'];
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
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['taxid'], $tax_pro_ipt['mage_id']);
        }
        $taxRates = $this->getListFromListByField($taxesExt['object']['tax_rates'], 'taxid', $tax['taxid']);
        $listZoneCountries = $this->getListFromListByField($taxesExt['object']['zone_element'], 'field_type', 'C');
        $listZoneStates = $this->getListFromListByField($taxesExt['object']['zone_element'], 'field_type', 'S');
        if($taxRates){
            foreach($taxRates as $tax_rate){
                if($tax_rate['zoneid'] > 0){
                    $zoneCountriesForTax = $this->getListFromListByField($listZoneCountries, 'zoneid', $tax_rate['zoneid']);
                    $zoneStatesForTax = $this->getListFromListByField($listZoneStates, 'zoneid', $tax_rate['zoneid']);
                    $cookTaxRates = $this->_cookTaxRates($zoneCountriesForTax, $zoneStatesForTax, $tax_rate['rate_value']);
                }else{
                    $cookTaxRates = $this->_cookTaxRatesAllCountries($tax_rate['rate_value']);
                }
                foreach($cookTaxRates as $row_cook){
                    if(!$row_cook['country']){
                        continue ;
                    }
                    $tax_rate_data = array();
                    $tax_rate_data['code'] = $this->createTaxRateCode($tax['tax_name'] . "-" . $row_cook['country'] . "-". $row_cook['state_code']);
                    $tax_rate_data['tax_country_id'] = $row_cook['country'];
                    if(!$row_cook['state']){
                        $tax_rate_data['tax_region_id'] = 0;
                    }else{
                        $tax_rate_data['tax_region_id'] = $row_cook['state'];
                    }
                    $tax_rate_data['zip_is_range'] = 0;
                    $tax_rate_data['tax_postcode'] = "*";
                    $tax_rate_data['rate'] = $tax_rate['rate_value'];
                    $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                    if($tax_rate_ipt['result'] == 'success'){
                        $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                    }
                }
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
        $query = "SELECT * FROM _DBPRF_manufacturers AS mf WHERE manufacturerid > {$id_src} ORDER BY manufacturerid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @return array
     */
    protected function _getManufacturersExtQuery($manufacturers){
        $manufacturerIds = $this->duplicateFieldValueFromList($manufacturers['object'], 'manufacturerid');
        $manufacturerIds_in_query = $this->arrayToInCondition($manufacturerIds);
        $ext_query = array(
            'manufacturers_lng' => "SELECT * FROM _DBPRF_manufacturers_lng WHERE manufacturerid IN {$manufacturerIds_in_query}"
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
        return $manufacturer['manufacturerid'];
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
        $man_list_lng = $this->getListFromListByField($manufacturersExt['object']['manufacturers_lng'], 'manufacturerid', $manufacturer['manufacturerid']);
        $man_default = $this->getRowValueFromListByField($man_list_lng, 'code', $this->_notice['config']['default_lang'], 'manufacturer');
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id
        );
        $manufacturer_data['value']['option'] = array(
            0 => $man_default ? $man_default : $manufacturer['manufacturer']
        );
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $manufacturer['manufacturer'];
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
        $query = "SELECT * FROM _DBPRF_categories WHERE categoryid > {$id_src} ORDER BY categoryid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'categoryid');
        $cat_id_in_query = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'categories_description' => "SELECT * FROM _DBPRF_categories_lng WHERE categoryid IN {$cat_id_in_query}",
            'categories_images' => "SELECT * FROM _DBPRF_images_C WHERE id IN {$cat_id_in_query}"
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
        return $category['categoryid'];
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
        if($category['parentid'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['parentid']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['parentid']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['categoryid']} import failed. Error: Could not import parent category id = {$category['parentid']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['categories_description'], 'categoryid', $category['categoryid']);
        $cat_default = $this->getRowFromListByField($catDesc, 'code', $this->_notice['config']['default_lang']);
        $cat_data['name'] = $cat_default['category'] ? $cat_default['category'] : $category['category'];
        $cat_data['description'] = $cat_default['description'] ? $cat_default['description'] : $category['description'];
        if(isset($category['title_tag'])){
            $meta_title = $category['title_tag'];
        }elseif(isset($category['meta_title'])){
            $meta_title = $category['meta_title'];
        }else{
            $meta_title = '';
        }
        $cat_data['meta_title'] = $meta_title;
        $cat_data['meta_keywords'] = $category['meta_keywords'];
        $cat_data['meta_description'] = isset($category['meta_description']) ? $category['meta_description'] : $category['meta_descr'];
        $category_image = $this->getRowValueFromListByField($categoriesExt['object']['categories_images'], 'id', $category['categoryid'], 'filename');
        if($category_image && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), '/C/'.$category_image, 'catalog/category')){
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['avail'] == 'N') ? 0 : 1;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_code => $value){
            $store_data = array();
            $store_change = $this->getRowFromListByField($catDesc, 'code', $lang_code);
            if($lang_code != $this->_notice['config']['default_lang'] && $store_change){
                $store_data['store_id'] = $value;
                $store_data['name'] = $store_change['category'];
                $store_data['description'] = $store_change['description'];
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
        $query = "SELECT pro.*, pri.priceid, pri.quantity, pri.price, pri.variantid, pri.membershipid FROM _DBPRF_products AS pro
                          LEFT JOIN _DBPRF_pricing AS pri ON pri.productid = pro.productid AND pri.variantid = 0 AND pri.quantity = 1 AND pri.membershipid  = 0
                          WHERE pro.productid > {$id_src} ORDER BY pro.productid ASC LIMIT {$limit}";
        return $query;
    }


    /**
     * Get data relation use for import product
     *
     * @param array $products : Data of function getProductsMain
     * @return array : Response of connector
     */
    public function getProductsExt($products){
        $proIds = $this->duplicateFieldValueFromList($products['object'], 'productid');
        $proIds_in_query = $this->arrayToInCondition($proIds);
        $ext_query = array(
            'tier_prices' => "SELECT * FROM _DBPRF_pricing WHERE productid IN {$proIds_in_query} AND variantid = 0",
            'variant_products' => "SELECT * FROM _DBPRF_variants AS vary
                                    LEFT JOIN _DBPRF_pricing AS pri ON pri.variantid = vary.variantid AND pri.productid = vary.productid
                                    WHERE vary.variantid != 0 AND vary.productid IN {$proIds_in_query}",
            'images_t' => "SELECT * FROM _DBPRF_images_T AS img_pro WHERE img_pro.id IN {$proIds_in_query}",
            'images_d' => "SELECT * FROM _DBPRF_images_D AS img_d WHERE img_d.id IN {$proIds_in_query} ",
            'images_p' => "SELECT * FROM _DBPRF_images_P AS img_d WHERE img_d.id IN {$proIds_in_query} ",
            'categories_product' => "SELECT * FROM _DBPRF_products_categories WHERE productid IN {$proIds_in_query}",
            'products_tax' => "SELECT * FROM _DBPRF_product_taxes AS pro_tax WHERE pro_tax.productid IN {$proIds_in_query}",
            'product_custom_options' => "SELECT * FROM _DBPRF_classes AS cl
                                            LEFT JOIN _DBPRF_class_options AS co ON co.classid = cl.classid
                                            WHERE cl.is_modifier = 'Y' AND cl.productid IN {$proIds_in_query}",
            'products_lng' => "SELECT * FROM _DBPRF_products_lng WHERE productid IN {$proIds_in_query}",
            'product_links' => "SELECT * FROM _DBPRF_product_links WHERE productid1 IN {$proIds_in_query} OR productid2 IN {$proIds_in_query}",
            'extra_field_values' => "SELECT efv.*, ef.field FROM _DBPRF_extra_field_values AS efv
                                        LEFT JOIN _DBPRF_extra_fields AS ef ON ef.fieldid = efv.fieldid
                                        WHERE productid IN {$proIds_in_query}"
        );
        if($this->_seo){
            $seo_ext_query = $this->_seo->getProductsExtQuery($this, $products);
            if($seo_ext_query){
                $ext_query = array_merge($ext_query, $seo_ext_query);
            }
        }
        $cus_ext_query = $this->_custom->getProductsExtQueryCustom($this, $products);
        if($cus_ext_query){
            $ext_query = array_merge($ext_query, $cus_ext_query);
        }
        $productsExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize($ext_query)
        ));
        if(!$productsExt || $productsExt['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $variantIds = $this->duplicateFieldValueFromList($productsExt['object']['variant_products'], 'variantid');
        $varyIds_in_query = $this->arrayToInCondition($variantIds);
        $ext_rel_query = array(
            'options_variant_product' => "SELECT * FROM _DBPRF_variant_items AS vi
                                            LEFT JOIN _DBPRF_class_options AS co ON co.optionid = vi.optionid
                                            LEFT JOIN _DBPRF_classes AS cl ON cl.classid = co.classid
                                            WHERE vi.variantid IN {$varyIds_in_query}",
            'images_w' => "SELECT * FROM _DBPRF_images_W AS img WHERE img.id IN {$varyIds_in_query}",
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
        if(!$productsExtRel || $productsExtRel['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $optionIds = $this->duplicateFieldValueFromList($productsExtRel['object']['options_variant_product'], 'optionid');
        $optIds_in_query = $this->arrayToInCondition($optionIds);
        $classIds = $this->duplicateFieldValueFromList($productsExtRel['object']['options_variant_product'], 'classid');
        $classIds_in_query = $this->arrayToInCondition($classIds);
        $productExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'option_multi_languages' => "SELECT * FROM _DBPRF_product_options_lng WHERE optionid IN {$optIds_in_query}",
                'class_multi_languages' => "SELECT * FROM _DBPRF_class_lng WHERE classid IN {$classIds_in_query}"
            ))
        ));
        if(!$productExtThird || $productExtThird['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $result = array(
            'result' => 'success',
            'object' => array_merge($productsExt['object'], $productsExtRel['object'], $productExtThird['object']),
        );
        return $result;
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return int
     */
    public function getProductId($product, $productsExt){
        return $product['productid'];
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
        $pro_data = $tierPrices = $categories = array();
        $children_product = $this->getListFromListByField($productsExt['object']['variant_products'], 'productid', $product['productid']);
        $list_data = $this->getListFromListByField($productsExt['object']['products_lng'], 'productid', $product['productid']);
        $listTierPrices = $this->getListFromListByField($productsExt['object']['tier_prices'], 'productid', $product['productid']);
        $images_list_d = $this->getListFromListByField($productsExt['object']['images_d'], 'id', $product['productid']);
        $images_list_p = $this->getListFromListByField($productsExt['object']['images_p'], 'id', $product['productid']);
        $thumb_img = $this->getRowFromListByField($productsExt['object']['images_t'], 'id', $product['productid']);
        $proCat = $this->getListFromListByField($productsExt['object']['categories_product'], 'productid', $product['productid']);
        $sku = $product['productcode'];
        if(!$sku){
            $sku = $this->joinTextToKey($product['product']);
        }
        if($listTierPrices){
            foreach($listTierPrices as $tier_price){
                if($tier_price['quantity'] == 1 && $tier_price['membershipid'] == 0){
                    continue;
                }
                $tierPrices[] = array(
                    'website_id'  => 0,
                    'cust_group'  => ($tier_price['membershipid'] == 2) ? 2 : 32000,
                    'price_qty'   => $tier_price['quantity'],
                    'price'       => $tier_price['price']
                );
            }
        }
        $pro_data['tier_price'] = $tierPrices;
        $pro_data['name'] =  $product['product'] ? $product['product'] : ' ';
        $pro_data['description'] = $this->changeImgSrcInText($product['fulldescr'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['descr'], $this->_notice['config']['add_option']['img_des']);
        if($thumb_img  && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), '/T/'.$thumb_img['filename'], 'catalog/product', false, true)){
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => $thumb_img['alt'] ? $thumb_img['alt'] : $pro_data['name']);
        }
        if($images_list_d){
            foreach($images_list_d as $gallery){
                if($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), '/D/'.$gallery['filename'], 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $gallery['alt'] ? $gallery['alt'] : $pro_data['name']) ;
                }
            }
        }
        if($images_list_p){
            foreach($images_list_p as $gallery){
                if($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), '/P/'.$gallery['filename'], 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $gallery['alt'] ? $gallery['alt'] : $pro_data['name']) ;
                }
            }
        }
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['categoryid']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $exFields = $this->getListFromListByField($productsExt['object']['extra_field_values'], 'productid', $product['productid']);
        if($exFields){
            foreach($exFields as $ex_field){
                $attr_code = $this->joinTextToKey('le ' . $ex_field['field'], 30, '_');
                $attr_import = array(
                    'entity_type_id' => $entity_type_id,
                    'attribute_code' => $attr_code,
                    'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                    'frontend_input' => 'text',
                    'frontend_label' => array($ex_field['field']),
                    'is_visible_on_front' => 1,
                    'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                    'is_configurable' => true,
                    'option' => array(
                        'value' => array('option_0' => array(''))
                    )
                );
                $attrAfterImport = $this->_process->attribute($attr_import);
                if($attrAfterImport){
                    $pro_data[$attrAfterImport['attribute_code']] = $ex_field['value'];
                }
            }
        }

        if($children_product){
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $config_data = $this->_importChildrenProduct($product, $children_product, $productsExt, $list_data);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data = array_merge($config_data['data'], $pro_data);
        }
        $pro_data['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
        $pro_data = array_merge($this->_convertProduct($product, $productsExt, $list_data),$pro_data);
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
        $result = array();
        $products_links = Mage::getModel('catalog/product_link_api');
        $dataCustomOption = $this->getListFromListByField($productsExt['object']['product_custom_options'], 'productid', $product['productid']);
        if($dataCustomOption){
            foreach($dataCustomOption as $option){
                $result[$option['classid']][] = $option;
            }
            $this->_addCustomOption($result, $product_mage_id, $data['price']);
        }
        $proRelated = $this->getListFromListByField($productsExt['object']['product_links'], 'productid1', $product['productid']);
        if($proRelated){
            foreach($proRelated as $pro_related){
                if($pro_id_related = $this->getMageIdProduct($pro_related['productid2'])){
                    $related_data = array('position' => $pro_related['orderby'] ? $pro_related['orderby'] : '');
                    $products_links->assign("related", $product_mage_id, $pro_id_related, $related_data);
                }else{
                    continue;
                }
            }
        }
        $proSrc = $this->getListFromListByField($productsExt['object']['product_links'], 'productid2', $product['productid']);
        if($proSrc){
            foreach($proSrc as $pro_src){
                if($proSrcId = $this->getMageIdProduct($pro_src['productid1'])){
                    $related_data = array('position' => $pro_src['orderby'] ? $pro_src['orderby'] : '');
                    $products_links->assign("related", $proSrcId, $product_mage_id, $related_data);
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
        $query = "SELECT * FROM _DBPRF_customers WHERE `login` > '{$id_src}' ORDER BY `login` ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        return array();
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
        return $customer['login'];
    }

    /**
     * Check customer has been imported
     *
     * @param array $customer : One row of object in function getCustomersMain
     * @param array $customersExt : Data of function getCustomersExt
     * @return boolean
     */
    public function checkCustomerImport($customer, $customersExt){
        $login_name = $customer['login'];
        return $this->_getMageIdByValue($login_name, self::TYPE_CUSTOMER);
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
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['taxvat'] = $customer['tax_number'];
        $cus_data['firstname'] = $customer['firstname'];
        $cus_data['lastname'] = $customer['lastname'];
        $cus_data['created_at'] = date("Y-m-d H:i:s",$customer['first_login']);
        $gender = ' ';
        if($customer['title'] == 'Mr.') $gender = 1;
        if($customer['title'] == 'Mrs.') $gender = 2;
        $cus_data['gender'] = $gender;
        $membership_id = $customer['membershipid'];
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$membership_id]) ? $this->_notice['config']['customer_group'][$membership_id] : 1;
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['password']. ":" . $this->_notice['extend']['cookie_key']);
        $this->customerSuccess(false, $customer_mage_id, $customer['login']);
        $billing_data['firstname'] = $customer['b_firstname'] ? $customer['b_firstname'] : $customer['firstname'];
        $billing_data['lastname'] = $customer['b_lastname'] ? $customer['b_lastname'] : $customer['lastname'];
        $billing_data['country_id'] = $customer['b_country'];
        $bill_street = preg_split('/\n|\r\n?/',$customer['b_address']);
        $billing_data['street'][0] = isset($bill_street[0]) ? $bill_street[0] : ' ';
        $billing_data['street'][1] = isset($bill_street[1]) ? $bill_street[1] : ' ';
        $billing_data['postcode'] = $customer['b_zipcode'];
        $billing_data['city'] = $customer['b_city'];
        $billing_data['telephone'] = $customer['phone'];
        $billing_data['fax'] = $customer['fax'];
        $billing_region_id = $this->_getRegionIdByCode($customer['b_state'],$customer['b_country']);
        if($billing_region_id){
            $billing_data['region_id'] = $billing_region_id;
        }else{
            $billing_data['region'] = $customer['b_state'];
        }
        $customAddress = Mage::getModel('customer/address');
        $customAddress->setData($billing_data)
            ->setCustomerId($customer_mage_id)
            ->setIsDefaultBilling('1')
            ->setSaveInAddressBook('1');
        try {
            $customAddress->save();
        }
        catch (Exception $ex) {
        }

        $shipping_data['firstname'] = $customer['s_firstname'] ? $customer['s_firstname'] : $customer['firstname'];
        $shipping_data['lastname'] = $customer['s_lastname'] ? $customer['s_lastname'] : $customer['lastname'];
        $shipping_data['country_id'] = $customer['s_country'];
        $ship_street = preg_split('/\n|\r\n?/',$customer['s_address']);
        $shipping_data['street'][0] = isset($ship_street[0]) ? $ship_street[0] : '';
        $shipping_data['street'][1] = isset($ship_street[1]) ? $ship_street[1] : '';
        $shipping_data['postcode'] = $customer['s_zipcode'];
        $shipping_data['city'] = $customer['s_city'];
        $shipping_data['telephone'] = $customer['phone'];
        $shipping_data['fax'] = $customer['fax'];
        $shipping_region_id = $this->_getRegionIdByCode($customer['s_state'],$customer['s_country']);
        if($shipping_region_id){
            $shipping_data['region_id'] = $shipping_region_id;
        }else{
            $shipping_data['region'] = $customer['s_state'];
        }
        $customAddress->setData($shipping_data)
            ->setCustomerId($customer_mage_id)
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');
        try {
            $customAddress->save();
        }
        catch (Exception $ex) {
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
        $query = "SELECT * FROM _DBPRF_orders WHERE orderid > {$id_src} ORDER BY orderid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'orderid');
        $orderIds_in_query = $this->arrayToInCondition($orderIds);
        $shipIds = $this->duplicateFieldValueFromList($orders['object'], 'shippingid');
        $shipIds_query = $this->arrayToInCondition($shipIds);
        $ext_query = array(
            'order_products' => "SELECT * FROM _DBPRF_order_details WHERE orderid IN {$orderIds_in_query}",
            'shipping' => "SELECT * FROM _DBPRF_shipping WHERE shippingid IN {$shipIds_query}"
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
        return $order['orderid'];
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

        $address_billing['firstname'] = $order['b_firstname'] ? $order['b_firstname'] : $order['firstname'];
        $address_billing['lastname'] = $order['b_lastname'] ? $order['b_lastname'] : $order['lastname'];
        $address_billing['company'] = $order['company'];
        $address_billing['email']   = $order['email'];
        $address_billing_street =  preg_split('/\n|\r\n?/',$order['b_address']);
        $address_billing['street']  = $address_billing_street[0]."\n".$address_billing_street[1];
        $address_billing['city'] = $order['b_city'];
        $address_billing['postcode'] = $order['b_zipcode'];
        $address_billing['country_id'] = $order['b_country'];
        $address_billing['telephone'] = $order['phone'];
        if($bill_region_id = $this->_getRegionIdByCode($order['b_state'], $order['b_country'])){
            $address_billing['region_id'] = $bill_region_id;
        }else{
            $address_billing['region'] = $order['b_state'];
        }
        $address_billing['save_in_address_book'] = true;

        $address_shipping['firstname'] = $order['s_firstname'] ? $order['s_firstname'] : $order['firstname'];
        $address_shipping['lastname'] = $order['s_lastname'] ? $order['s_lastname'] : $order['lastname'];
        $address_shipping['company'] = $order['company'];
        $address_shipping['email']   = $order['email'];
        $address_shipping_street = preg_split('/\n|\r\n?/',$order['s_address']);
        $address_shipping['street']  = $address_shipping_street[0]."\n".$address_shipping_street[1];
        $address_shipping['city'] = $order['s_city'];
        $address_shipping['postcode'] = $order['s_zipcode'];
        $address_shipping['country_id'] = $order['s_country'];
        $address_shipping['telephone'] = $order['phone'];
        if($del_region_id = $this->_getRegionIdByCode($order['s_state'], $order['s_country'])){
            $address_shipping['region_id'] = $del_region_id;
        }else{
            $address_shipping['region'] = $order['s_state'];
        }
        $address_shipping['save_in_address_book'] = true;

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $order_data = array();
        $order_data['store_id'] = $store_id;
        $customer_mage_id = $this->_getMageIdByValue($order['login'], self::TYPE_CUSTOMER);
        if($customer_mage_id){
            $order_data['customer_id'] = $customer_mage_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['email'];
        $order_data['customer_firstname'] = $order['firstname'] ? $order['firstname'] : $address_billing['firstname'];
        $order_data['customer_lastname'] = $order['lastname'] ? $order['lastname'] : $address_billing['lastname'];
        $order_data['customer_group_id'] = 1;
        $status_create = $order['status'];
        $order_data['status'] = $this->_notice['config']['order_status'][$status_create];
        $order_data['state'] =  $this->getOrderStateByStatus($order_data['status']);
        $orderProducts = $this->getListFromListByField($ordersExt['object']['order_products'], 'orderid', $order['orderid']);
        $carts = array();
        if($orderProducts){
            foreach($orderProducts as $item){
                $cart = array();
                $product_id = $this->getMageIdProduct($item['productid']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['name'] = $item['product'];
                $cart['sku'] = $item['productcode'];
                $cart['price'] = $item['price'];
                $cart['original_price'] = $item['price'];
                $extra_data = unserialize($item['extra_data']);
                $tax_amount = 0;
                $tax_percent = 0;
                if(is_array($extra_data) && isset($extra_data['taxes'])){
                    foreach($extra_data['taxes'] as $tax){
                        $tax_amount += $tax['tax_value_precise'];
                        $tax_percent += $tax['rate_value'];
                    }
                }
                $cart['tax_amount'] = $tax_amount;
                $cart['tax_percent'] = $tax_percent;
                $cart['qty_ordered'] = $item['amount'];
                $cart['row_total'] = $item['price'] * $item['amount'];
                if(!empty($item['product_options'])){
                    $product_opt = $this->_createProductOrderOption($item['product_options']);
                    $cart['product_options'] = serialize($product_opt);
                }
                $carts[]= $cart;
            }
        }
        $order_data['subtotal'] = $order['subtotal'];
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['shipping_cost'];
        $order_data['base_shipping_amount'] =  $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order_data['shipping_amount'];
        $order_data['shipping_description'] = $this->getRowValueFromListByField($ordersExt['object']['shipping'], 'shippingid', $order['shippingid'], 'shipping');
        $order_data['tax_amount'] = $order['tax'];
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['discount'] + $order['coupon_discount'];
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $order['total'];
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
        $order_data['created_at'] = date("Y-m-d H:i:s",$order['date']);

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['orderid'];
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
        $order_status_data = array();
        $order_status_id = $order['status'];
        $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
        if($order_status_data['status']){
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['comment'] = "<b>Reference order #".$order['orderid']."</b><br /><b>Payment method: </b>".$order['payment_method']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order['customer_notes'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date("Y-m-d H:i:s",$order['date']);
        $order_status_data['created_at'] = date("Y-m-d H:i:s",$order['date']);
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }

    /**
     * Query for get main data use for import review
     *
     * @return string
     */
    protected function _getReviewsMainQuery(){
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $query = "SELECT pr.*, pr_vote.vote_value FROM _DBPRF_product_reviews AS pr
                  LEFT JOIN _DBPRF_product_votes AS pr_vote ON pr_vote.remote_ip = pr.remote_ip
                  WHERE pr.review_id > {$id_src} ORDER BY pr.review_id ASC LIMIT {$limit}";
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
        $product_mage_id = $this->getMageIdProduct($review['productid']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['review_id']} import failed. Error: Product Id = {$review['productid']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = 2;
        $data['title'] = ' ';
        $data['detail'] = $review['message'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = null;
        $data['nickname'] = $review['email'];
        $data['rating'] = $review['vote_value']/20;
        $data['created_at'] = ' ';
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

    ########################### Extend Function ################################
    protected function _convertProduct($product, $productsExt, $list_data, $check_variant_pro = false){
        $pro_data = array();
        $pro_data['price'] = $product['price'];
        $product_tax = $this->getRowValueFromListByField($productsExt['object']['products_tax'], 'productid', $product['productid'], 'taxid');
        if($product_tax != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product_tax)){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        if(isset($product['add_date'])){
            $pro_data['created_at'] = date("Y-m-d H:i:s", $product['add_date']);
        }
        $pro_data['weight'] =  ($product['weight'])? $product['weight'] : 0;
        $pro_data['status'] = 1;
        $pro_data['stock_data'] = array(
            'is_in_stock' =>  1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['avail'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['avail'] < 1)? 0 : 1,
            'qty' => ($product['avail'] >= 0 )? $product['avail']: 0,
        );
        if(isset($product['manufacturerid']) && $manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturerid'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        $pro_data['meta_title'] = isset($product['title_tag']) ? $product['title_tag'] : '';
        $pro_data['meta_keyword'] = isset($product['meta_keywords']) ? $product['meta_keywords'] : $product['keywords'];
        $pro_data['meta_description'] = isset($product['meta_description']) ? $product['meta_description'] : '';
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];

        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_code => $value){
            if($lang_code == $this->_notice['config']['default_lang']){
                continue ;
            }
            $pro_store = $this->getRowFromListByField($list_data, 'code', $lang_code);
            if(!$pro_store){
                continue ;
            }
            $store_data = array();
            if($pro_store['product'] && !$check_variant_pro){
                $store_data['name'] = $pro_store['product'];
            }
            if($pro_store['fulldescr']){
                $store_data['description'] = $this->changeImgSrcInText($pro_store['fulldescr'],$this->_notice['config']['add_option']['img_des']);
            }
            if($pro_store['descr']){
                $store_data['short_description'] = $this->changeImgSrcInText($pro_store['descr'],$this->_notice['config']['add_option']['img_des']);
            }
            if(!empty($store_data)){
                $store_data['store_id'] = $value;
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

    protected function _createProductOrderOption($listOpt){
        $listOpt = preg_split('/\n|\r\n?/',$listOpt);
        $result = array();
        if(is_array($listOpt) && !empty($listOpt)){
            foreach($listOpt as $row){
                $group_option = explode(': ',$row);
                $option = array(
                    'label' => $group_option[0],
                    'value' => $group_option[1],
                    'print_value' => $group_option[1],
                    'option_id' => 'option_pro',
                    'option_type' => 'drop_down',
                    'option_value' => 0,
                    'custom_view' => false
                );
                $result[] = $option;
            }
        }
        return array('options' => $result);
    }

    protected function _addCustomOption($groups_options, $pro_mage_id, $product_price){
        $custom_option = array();
        foreach($groups_options as $class_id => $group_option){
            $options = array();
            foreach($group_option as $option){
                $tmp['option_type_id'] = -1;
                $tmp['title'] = $option['option_name'];
                $tmp['price'] = $option['price_modifier'];
                if($option['modifier_type'] == '%'){
                    $tmp['price_type'] = 'percent';
                }else{
                    $tmp['price_type'] = 'fixed';
                }
                $options[]=$tmp;
            }
            $tmp_opt = array(
                'title' => $group_option[0]['classtext'],
                'type' => 'drop_down',
                'is_require' => 1,
                'sort_order' => 0,
                'values' => $options,
            );
            $custom_option[] = $tmp_opt;
        }
        $this->importProductOption($pro_mage_id, $custom_option);
    }

    protected function _importChildrenProduct($parent_product, $children_products, $productsExt, $list_data){
        $result = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $dataChildes = array();
        foreach($children_products as $children) {
            if ($children['quantity'] == 1 && $children['membershipid'] == '0') {
                $dataOpts = $convertPro = array();
                $option_collection = '';
                $options_attribute = $this->getListFromListByField($productsExt['object']['options_variant_product'], 'variantid', $children['variantid']);
                if ($options_attribute) {
                    foreach ($options_attribute as $option) {
                        $dataTMP = array();
                        $attr_name = $option['classtext'];
                        $attr_code = $this->joinTextToKey($option['class'], 27, '_');
                        $opt_name = $option['option_name'];
                        $opt_list_multi_lng = $this->getListFromListByField($productsExt['object']['option_multi_languages'], 'optionid', $option['optionid']);
                        $attr_list_multi_lng = $this->getListFromListByField($productsExt['object']['class_multi_languages'], 'classid', $option['classid']);
                        $pre_optAttr_multi = $this->_prepareMultiStoreAttribute($opt_list_multi_lng, $attr_list_multi_lng);
                        $attr_import = $this->_makeAttributeImport($attr_name, $attr_code, $opt_name, $entity_type_id, $this->_notice['config']['attribute_set_id'], $pre_optAttr_multi['option_multi_store'], $pre_optAttr_multi['attribute_multi_store']);
                        if (!$attr_import) {
                            return array(
                                'result' => "warning",
                                'msg' => $this->consoleWarning("Product Id = {$parent_product['productid']} import failed. Error: Product attribute could not create!")
                            );
                        }
                        $dataOptAfterImport = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                        if (!$dataOptAfterImport) {
                            return array(
                                'result' => "warning",
                                'msg' => $this->consoleWarning("Product Id = {$parent_product['productid']} import failed. Error: Product attribute could not create!")
                            );
                        }
                        $dataTMP['attribute_id'] = $dataOptAfterImport['attribute_id'];
                        $dataTMP['value_index'] = $dataOptAfterImport['option_ids']['option_0'];
                        $dataTMP['is_percent'] = 0;
                        $dataOpts[] = $dataTMP;
                        if ($option['option_name']) {
                            $option_collection = $option_collection . ', ' . $option['option_name'];
                        }
                    }
                }
                $sku = $children['productcode'];
                $thumb_img = $this->getRowFromListByField($productsExt['object']['images_w'], 'id', $children['variantid']);
                if ($thumb_img && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), '/W/' . $thumb_img['filename'], 'catalog/product', false, true)) {
                    $convertPro['image_import_path'] = array('path' => $image_path, 'label' => $thumb_img['alt'] ? $thumb_img['alt'] : $parent_product['product']);
                }
                $convertPro['name'] = $parent_product['product'] ? $parent_product['product'] . $option_collection : ' ';
                if (!$sku) {
                    $sku = $this->joinTextToKey($convertPro['name']);
                }
                $convertPro['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
                $convertPro['description'] = $this->changeImgSrcInText($parent_product['fulldescr'], $this->_notice['config']['add_option']['img_des']);
                $convertPro['short_description'] = $this->changeImgSrcInText($parent_product['descr'], $this->_notice['config']['add_option']['img_des']);
                $convertPro['category_ids'] = array();
                $convertPro['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $convertPro['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
                $listTierPrices = $this->getListFromListByField($children_products, 'variantid', $children['variantid']);
                $tierPrices = array();
                if ($listTierPrices) {
                    foreach ($listTierPrices as $tier_price) {
                        if ($tier_price['quantity'] == '1' && $tier_price['membershipid'] == '0') {
                            continue;
                        }
                        $tierPrices[] = array(
                            'website_id' => 0,
                            'cust_group' => ($tier_price['membershipid'] == 2) ? 2 : 32000,
                            'price_qty' => $tier_price['quantity'],
                            'price' => $tier_price['price']
                        );
                    }
                }
                $convertPro['tier_price'] = $tierPrices;
                $convertPro = array_merge($this->_convertProduct($children, $productsExt, $list_data, true), $convertPro);
                $pro_import = $this->_process->product($convertPro);
                if ($pro_import['result'] !== 'success') {
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$parent_product['productid']} import failed. Error: Product children could not create!")
                    );
                }
                foreach ($dataOpts as $dataAttribute) {
                    $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
                }
                $dataChildes[$pro_import['mage_id']] = $dataOpts;
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

    protected function _prepareMultiStoreAttribute($opt_list_multi_lng, $attr_list_multi_lng){
        $result = false;
        foreach($this->_notice['config']['languages'] as $lang_code => $store_id){
            $tmp_opt = $tmp_attr = array();
            if($store_opt_change = $this->getRowValueFromListByField($opt_list_multi_lng, 'code', $lang_code, 'option_name')){
                $tmp_opt['option_name'] = $store_opt_change;
                $tmp_opt['option_store_id'] = $store_id;
                $result['option_multi_store'][] = $tmp_opt;
            }
            if($store_attr_change = $this->getRowValueFromListByField($attr_list_multi_lng, 'code', $lang_code, 'classtext')){
                $tmp_attr['attribute_store_id'] = $store_id;
                $tmp_attr['attribute_name'] = $store_attr_change;
                $result['attribute_multi_store'][] = $tmp_attr;
            }
        }
        return $result;
    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_categories WHERE categoryid = {$parent_id}"
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

    protected function _cookTaxRates($zoneCountries, $zoneStates, $rate){
        $tax_rate = array();
        if($zoneCountries){
            foreach($zoneCountries as $row_country){
                $tmp = array();
                if($zoneStates){
                    foreach($zoneStates as $row_state){
                        if($row_country['field'] == substr($row_state['field'],0,2)){
                            $tmp['country'] = $row_country['field'];
                            $state_code= substr($row_state['field'],3);
                            $tmp['state'] = $this->_getRegionIdByCode($state_code, $tmp['country']);
                            $tmp['state_code'] = $state_code;
                            $tmp['rate'] = $rate;
                            $tax_rate[] = $tmp;
                        }
                    }
                }
                if(empty($tmp)){
                    $tmp['country'] = $row_country['field'];
                    $tmp['state'] = 0;
                    $tmp['state_code'] = '*';
                    $tmp['rate'] = $rate;
                    $tax_rate[] = $tmp;
                }
            }
        }
        return $tax_rate;
    }

    protected function _cookTaxRatesAllCountries($rate){
        $result = array();
        $countries = Mage::getModel('directory/country')->getCollection();
        if($countries){
            foreach($countries as $row){
                $tmp = array();
                $tmp['country'] = $row->getCountryId();
                $tmp['state'] = 0;
                $tmp['state_code'] = '*';
                $tmp['rate'] = $rate;
                $result[] = $tmp;
            }
        }
        return $result;
    }

    protected function _getRegionIdByCode($region_code, $country_code){
        $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
        $regionId = $regionModel->getId();
        if($regionId) return $regionId;
        return false;
    }

    protected function _getOrderStatusValueByKey($key){
        if($key == 'lbl_not_finished') return 'I';
        if($key == 'lbl_queued') return 'Q';
        if($key == 'lbl_pre_authorized') return 'A';
        if($key == 'lbl_processed') return 'P';
        if($key == 'lbl_backordered') return 'B';
        if($key == 'lbl_declined') return 'D';
        if($key == 'lbl_failed') return 'F';
        if($key == 'lbl_complete') return 'C';
        return false;
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
     * Get magento id import by src value and type
     */
    protected function _getMageIdByValue($value, $type){
        $result = $this->_selectLeCaMgImport(array(
            'domain' => $this->_cart_url,
            'type' => $type,
            'value' => $value
        ));
        if(!$result){
            return false;
        }
        return $result['mage_id'];
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
        $query = "SELECT * FROM _DBPRF_taxes ORDER BY tx.taxid ASC";
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
        $query = "SELECT * FROM _DBPRF_manufacturers ORDER BY manufacturerid ASC";
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
        $query = "SELECT * FROM _DBPRF_categories ORDER BY categoryid ASC";
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
        $query = "SELECT pro.*, pri.priceid, pri.quantity, pri.price, pri.variantid, pri.membershipid FROM _DBPRF_products AS pro
                          LEFT JOIN _DBPRF_pricing AS pri ON pri.productid = pro.productid AND pri.variantid = 0 AND pri.quantity = 1 AND pri.membershipid  = 0 ORDER BY pro.productid ASC";
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
        $query = "SELECT * FROM _DBPRF_customers ORDER BY `login` ASC";
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
        $query = "SELECT * FROM _DBPRF_orders ORDER BY orderid ASC";
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
        $query = "SELECT pr.*, pr_vote.vote_value FROM _DBPRF_product_reviews AS pr
                  LEFT JOIN _DBPRF_product_votes AS pr_vote ON pr_vote.remote_ip = pr.remote_ip ORDER BY pr.review_id ASC";
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