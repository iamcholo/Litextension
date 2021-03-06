<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Interspirev6
    extends LitExtension_CartMigration_Model_Cart
{

    public function __construct()
    {
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_classes WHERE id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_brands WHERE brandid > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categoryid > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE productid > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customerid > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orderid > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE reviewid > {$this->_notice['reviews']['id_src']}"
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
                "currencies" => "SELECT * FROM _DBPRF_currencies WHERE currencyisdefault = 1"
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['currencies']) {
            $this->_notice['config']['default_currency'] = isset($object['currencies']['0']['currencyid']) ? $object['currencies']['0']['currencyid'] : 1;
        }
        $this->_notice['config']['default_lang'] = 1;
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT * FROM _DBPRF_currencies",
                "orders_status" => "SELECT * FROM _DBPRF_order_status",
                "customer_groups" => "SELECT * FROM _DBPRF_customer_groups"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data['1'] = 'Default Language';
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['currencyid'];
            $currency_name = $currency_row['currencyname'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['statusid'];
            $order_status_name = $order_status_row['statusdesc'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        foreach($obj['customer_groups'] as $cus_group){
            $group_id = $cus_group['customergroupid'];
            $group_name = $cus_group['groupname'];
            $customer_group_data[$group_id] = $group_name;
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_classes WHERE id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_brands WHERE brandid > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE categoryid > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE productid > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customers WHERE customerid > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE orderid > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE reviewid > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $totals = array();
        foreach($data['object'] as $type => $row){
            $count = $this->arrayToCount($row);
            if($type == 'taxes' && $this->_notice['taxes']['id_src'] == 0){
                $count = $count + 1;
            }
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
                $currency_id = $currency['currencyid'];
                $currency_value = $currency['currencycode'];
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
     * Get data of table convert to tax rule
     *
     * @return array : Response of connector
     */
    public function getTaxesMain(){
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = "SELECT * FROM _DBPRF_tax_classes WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        $taxes = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query
        ));
        if(!$taxes || $taxes['result'] != 'success'){
            return $this->errorConnector(true);
        }
        if($id_src == 0){
            $tmp['id'] = 0;
            $tmp['name'] = 'Default Tax Class';
            array_unshift($taxes['object'], $tmp);
        }
        return $taxes;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $ext_query = array(
            'tax_rate_class_rates' => "SELECT tc.*, tr.tax_zone_id, tr.name, tr.enabled FROM _DBPRF_tax_rate_class_rates AS tc LEFT JOIN _DBPRF_tax_rates AS tr ON tc.tax_rate_id = tr.id"
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
        $tax_zone_ids = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rate_class_rates'], 'tax_zone_id');
        $tax_zone_ids_con = $this->arrayToInCondition($tax_zone_ids);
        $ext_rel_query = array(
            'tax_zone_locations' => "SELECT * FROM _DBPRF_tax_zone_locations WHERE tax_zone_id IN {$tax_zone_ids_con}"
        );
        return $ext_rel_query;
    }

    /**
     * Get data relation use for import tax rule
     *
     * @param array $taxes : Data of function getTaxesMain
     * @return array : Response of connector
     */
    public function getTaxesExt($taxes){
        $taxesExt = array(
            'result' => 'success'
        );
        $ext_query = $this->_getTaxesExtQuery($taxes);
        $cus_ext_query = $this->_custom->getTaxesExtQueryCustom($this, $taxes);
        if($cus_ext_query){
            $ext_query = array_merge($ext_query, $cus_ext_query);
        }
        if($ext_query){
            $taxesExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
                'serialize' => true,
                "query" => serialize($ext_query)
            ));
            if(!$taxesExt || $taxesExt['result'] != 'success'){
                return $this->errorConnector(true);
            }
            $ext_rel_query = $this->_getTaxesExtRelQuery($taxes, $taxesExt);
            $cus_ext_rel_query = $this->_custom->getTaxesExtRelQueryCustom($this, $taxes, $taxesExt);
            if($cus_ext_rel_query){
                $ext_rel_query = array_merge($ext_rel_query, $cus_ext_rel_query);
            }
            if($ext_rel_query){
                $taxesExtRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    "query" => serialize($ext_rel_query)
                ));
                if(!$taxesExtRel || $taxesExtRel['result'] != 'success'){
                    return $this->errorConnector(true);
                }
                $value_ids = $this->duplicateFieldValueFromList($taxesExtRel['object']['tax_zone_locations'], 'value_id');
                $country_ids = $this->duplicateFieldValueFromList($taxesExtRel['object']['tax_zone_locations'], 'country_id');
                $all_country_ids_con = $this->arrayToInCondition(array_unique(array_merge($value_ids, $country_ids)));
                $ext_third_query = array(
                    'countries' => "SELECT * FROM _DBPRF_countries WHERE countryid IN {$all_country_ids_con}"
                );
                $taxesExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    "query" => serialize($ext_third_query)
                ));
                if(!$taxesExtThird || $taxesExtThird['result'] != 'success'){
                    return $this->errorConnector(true);
                }
                $taxesExt['object'] = array_merge($taxesExt['object'], $taxesExtRel['object'], $taxesExtThird['object']);
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
    public function getTaxId($tax, $taxesExt){
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
            'class_name' => $tax['name']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id'], $tax_pro_ipt['mage_id']);
        }
        $taxRateClassRates = $this->getListFromListByField($taxesExt['object']['tax_rate_class_rates'], 'tax_class_id', $tax['id']);
        if($tax['id'] == 0){
            $taxRateClassRates = $taxesExt['object']['tax_rate_class_rates'];
        }
        if($taxRateClassRates){
            foreach($taxRateClassRates as $tax_rate_class_rates){
                $taxZoneLocations = $this->getListFromListByField($taxesExt['object']['tax_zone_locations'], 'tax_zone_id', $tax_rate_class_rates['tax_zone_id']);
                foreach($taxZoneLocations as $tax_zone_location){
                    if($tax_zone_location['type'] == 'country'){
                        if(!$tax_zone_location['value_id']){
                            continue ;
                        }
                        $tax_rate_data = array();
                        $country_iso_code = $this->getRowValueFromListByField($taxesExt['object']['countries'], 'countryid', $tax_zone_location['value_id'], 'countryiso2');
                        if($country_iso_code){
                            $code = $tax['name'] . "-" . $tax_rate_class_rates['name'] . '-' . $country_iso_code;
                            $tax_rate_data['code'] = $this->createTaxRateCode($code);
                            $tax_rate_data['tax_country_id'] = $country_iso_code;
                            $tax_rate_data['tax_region_id'] = 0;
                            $tax_rate_data['zip_is_range'] = 0;
                            $tax_rate_data['tax_postcode'] = "*";
                            $tax_rate_data['rate'] = $tax_rate_class_rates['rate'];
                            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                            if($tax_rate_ipt['result'] == 'success'){
                                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                            }
                        }
                    }elseif($tax_zone_location['type'] == 'state'){
                        if(!$tax_zone_location['country_id']){
                            continue ;
                        }
                        $tax_rate_data = array();
                        $country_iso_code = $this->getRowValueFromListByField($taxesExt['object']['countries'], 'countryid', $tax_zone_location['country_id'], 'countryiso2');
                        if($country_iso_code){
                            $code = $tax['name'] . "-" . $tax_rate_class_rates['name'] . '-' . $country_iso_code . '-' . $tax_zone_location['value'];
                            $tax_rate_data['code'] = $this->createTaxRateCode($code);
                            $tax_rate_data['tax_country_id'] = $country_iso_code;
                            $country_state = $this->getRegionId($tax_zone_location['value'], $country_iso_code);
                            $tax_rate_data['tax_region_id'] = $country_state ? $country_state : 0;
                            $tax_rate_data['zip_is_range'] = 0;
                            $tax_rate_data['tax_postcode'] = "*";
                            $tax_rate_data['rate'] = $tax_rate_class_rates['rate'];
                            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                            if($tax_rate_ipt['result'] == 'success'){
                                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                            }
                        }
                    }elseif($tax_zone_location['type'] == 'zip'){
                        if(!$tax_zone_location['country_id']){
                            continue ;
                        }
                        $tax_rate_data = array();
                        $country_iso_code = $this->getRowValueFromListByField($taxesExt['object']['countries'], 'countryid', $tax_zone_location['country_id'], 'countryiso2');
                        if($country_iso_code){
                            $code = $tax['name'] . "-" . $tax_rate_class_rates['name'] . $country_iso_code . '-' . $tax_zone_location['value'];
                            $tax_rate_data['code'] = $this->createTaxRateCode($code);
                            $tax_rate_data['tax_country_id'] = $country_iso_code;
                            $tax_rate_data['tax_region_id'] = 0;
                            $tax_rate_data['zip_is_range'] = $tax_zone_location['value'];
                            $tax_rate_data['tax_postcode'] = "*";
                            $tax_rate_data['rate'] = $tax_rate_class_rates['rate'];
                            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                            if($tax_rate_ipt['result'] == 'success'){
                                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                            }
                        }
                    }
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['name']);
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
        $query = "SELECT * FROM _DBPRF_brands WHERE brandid > {$id_src} ORDER BY brandid ASC LIMIT {$limit}";
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
        return $manufacturer['brandid'];
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
            0 => $manufacturer['brandname']
        );
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $manufacturer['brandname'];
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
       return array();
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
        if($category['catparentid'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['catparentid']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['catparentid']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['categoryid']} import failed. Error: Could not import parent category id = {$category['catparentid']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['catname'] ? $category['catname'] : " ";
        $cat_data['meta_title'] = $category['catpagetitle'];
        $cat_data['meta_keywords'] = $category['catmetakeywords'];
        $cat_data['meta_description'] = $category['catmetadesc'];
        if($category['catimagefile'] && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  $category['catimagefile'], 'catalog/category')){
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = 1;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $cat_data['multi_store'] = array();
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
        $query = "SELECT * FROM _DBPRF_products WHERE productid > {$id_src} ORDER BY productid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'productid');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'product_variation_combinations' => "SELECT * FROM _DBPRF_product_variation_combinations WHERE vcproductid IN {$pro_ids_query}",
            'categoryassociations' => "SELECT * FROM _DBPRF_categoryassociations WHERE productid IN {$pro_ids_query}",
            'product_images' => "SELECT * FROM _DBPRF_product_images WHERE imageprodid IN {$pro_ids_query}",
            'product_configurable_fields' => "SELECT * FROM _DBPRF_product_configurable_fields WHERE fieldprodid IN {$pro_ids_query}",
            'product_customfields' => "SELECT * FROM _DBPRF_product_customfields WHERE fieldprodid IN {$pro_ids_query}",
            'product_discounts' => "SELECT * FROM _DBPRF_product_discounts WHERE discountprodid IN {$pro_ids_query}",
            'product_tagassociations' => "SELECT ta.*, t.tagname FROM _DBPRF_product_tagassociations AS ta
                                          LEFT JOIN _DBPRF_product_tags as t ON ta.tagid = t.tagid
                                          WHERE productid IN {$pro_ids_query}",
        );
        foreach($productIds as $pro_id){
            $table_name = 'product_related_' . $pro_id;
            $like = "%" . $pro_id . ",%";
            $or_like = "%," . $pro_id . "%";
            $ext_query[$table_name] = "SELECT productid FROM _DBPRF_products WHERE prodrelatedproducts LIKE '{$like}' OR prodrelatedproducts LIKE '{$or_like}' OR prodrelatedproducts = $pro_id";
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
        $variation_opt_ids = $this->duplicateFieldValueFromList($productsExt['object']['product_variation_combinations'], 'vcoptionids');
        $varyOptIds = array();
        foreach($variation_opt_ids as $vc_opt_id){
            $tmp = explode(',', $vc_opt_id);
            $varyOptIds = array_merge($varyOptIds, $tmp);
        }
        $varyOptIds = array_unique($varyOptIds);
        $varyOptIdsCon = $this->arrayToInCondition($varyOptIds);
        $ext_rel_query = array(
            'product_variation_options' => "SELECT * FROM _DBPRF_product_variation_options WHERE voptionid IN {$varyOptIdsCon}"
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
        if (LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $cusFields = $this->getListFromListByField($productsExt['object']['product_customfields'], 'fieldprodid', $product['productid']);
        if($cusFields){
            foreach($cusFields as $cus_field){
                $attr_code = $this->joinTextToKey($cus_field['fieldname'], 30, '_');
                $attr_import = array(
                    'entity_type_id' => $entity_type_id,
                    'attribute_code' => $attr_code,
                    'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                    'frontend_input' => 'text',
                    'frontend_label' => array($cus_field['fieldname']),
                    'is_visible_on_front' => 1,
                    'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                    'is_configurable' => true,
                    'option' => array(
                        'value' => array('option_0' => array(''))
                    )
                );
                $attrAfterImport = $this->_process->attribute($attr_import);
                if($attrAfterImport){
                    $pro_data[$attrAfterImport['attribute_code']] = $cus_field['fieldvalue'];
                }
            }
        }
        $cusAttr = array(
            array(
                'attribute_code' => 'le_width',
                'attribute_name' => 'Width',
                'source_field' => 'prodwidth'
            ),
            array(
                'attribute_code' => 'le_height',
                'attribute_name' => 'Height',
                'source_field' => 'prodheight'
            ),
            array(
                'attribute_code' => 'le_depth',
                'attribute_name' => 'Depth',
                'source_field' => 'proddepth'
            ),
        );
        foreach($cusAttr as $attr_data){
            $attr_import = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' => $attr_data['attribute_code'],
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => 'text',
                'frontend_label' => array($attr_data['attribute_name']),
                'is_visible_on_front' => 1,
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'option' => array(
                    'value' => array('option_0' => array(''))
                )
            );
            $attrAfterImport = $this->_process->attribute($attr_import);
            if($attrAfterImport){
                $pro_data[$attrAfterImport['attribute_code']] = $product[$attr_data['source_field']];
            }
        }
        if($product['prodtype'] == 2){
            $type_id = 'downloadable';
        }else{
            $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        }

        $variation_combinations = $this->getListFromListByField($productsExt['object']['product_variation_combinations'], 'vcproductid', $product['productid']);
        if($variation_combinations){
            $type_id = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $config_data = $this->_importChildrenProduct($product, $productsExt, $variation_combinations);
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
        //Related Products
        $products_links = Mage::getModel('catalog/product_link_api');
        if($product['prodrelatedproducts']){
            $proRelated = explode(',', $product['prodrelatedproducts']);
            if($proRelated){
                foreach($proRelated as $pro_related_id){
                    if($pro_related_id > 0 && $pro_id_related = $this->getMageIdProduct($pro_related_id)){
                        $products_links->assign("related", $product_mage_id, $pro_id_related);
                    }
                }
            }
        }
        $table_related = 'product_related_' . $product['productid'];
        $proSrcRelated = $productsExt['object'][$table_related];
        if($proSrcRelated){
            if($proSrcRelated[0]['productid'] && $proMageSrcRelated = $this->getMageIdProduct($proSrcRelated[0]['productid'])){
                $products_links->assign("related", $proMageSrcRelated, $product_mage_id);
            }
        }

        $confFields = $this->getListFromListByField($productsExt['object']['product_configurable_fields'], 'fieldprodid', $product['productid']);
        if($confFields){
            $custom_option = array();
            foreach($confFields as $conf_field){
                $options = array();
                if($conf_field['fieldselectoptions']){
                    $options_val = explode(',', $conf_field['fieldselectoptions']);
                    foreach($options_val as $opt){
                        $tmp['option_type_id'] = -1;
                        $tmp['title'] = $opt;
                        $tmp['price'] = '';
                        $tmp['price_type'] = 'fixed';
                        $options[]=$tmp;
                    }
                }
                $conf_type = 'drop_down';
                if($conf_field['fieldtype'] == 'text'){
                    $conf_type = 'field';
                }
                if($conf_field['fieldtype'] == 'textarea'){
                    $conf_type = 'area';
                }
                if($conf_field['fieldtype'] == 'checkbox'){
                    continue;
                }
                $tmp_opt = array(
                    'title' => $conf_field['fieldname'],
                    'type' => $conf_type,
                    'is_require' => ($conf_field['fieldrequired'] == 1) ? 1 : 0,
                    'sort_order' => 0,
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
            $this->importProductOption($product_mage_id, $custom_option);
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
        $query = "SELECT * FROM _DBPRF_customers WHERE customerid > {$id_src} ORDER BY customerid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'customerid');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'shipping_addresses' => "SELECT * FROM _DBPRF_shipping_addresses WHERE shipcustomerid IN {$customer_ids_query}"
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
        $countryIds = $this->duplicateFieldValueFromList($customersExt['object']['shipping_addresses'], 'shipcountryid');
        $country_ids_query = $this->arrayToInCondition($countryIds);
        $ext_rel_query = array(
            'countries' => "SELECT * FROM _DBPRF_countries WHERE countryid IN {$country_ids_query}",
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
        return $customer['customerid'];
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
            $cus_data['id'] = $customer['customerid'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['custconemail'];
        $cus_data['firstname'] = $customer['custconfirstname'];
        $cus_data['lastname'] = $customer['custconlastname'];
        $cus_data['created_at'] = date("Y-m-d H:i:s", $customer['custdatejoined']);
        if($customer['custgroupid'] > 0){
            $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$customer['custgroupid']]) ? $this->_notice['config']['customer_group'][$customer['custgroupid']] : 1;
        }else{
            $cus_data['group_id'] = 1;
        }
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['custpassword'] . ":" . $customer['salt']);
        $cusAdd = $this->getListFromListByField($customersExt['object']['shipping_addresses'], 'shipcustomerid', $customer['customerid']);
        if($cusAdd){
            foreach($cusAdd as $cus_add){
                $address = array();
                $address['firstname'] = $cus_add['shipfirstname'];
                $address['lastname'] = $cus_add['shiplastname'];
                $country_code = $this->getRowValueFromListByField($customersExt['object']['countries'], 'countryid', $cus_add['shipcountryid'], 'countryiso2');
                $address['country_id'] = $country_code;
                $address['street'] = $cus_add['shipaddress1']."\n".$cus_add['shipaddress2'];
                $address['postcode'] = $cus_add['shipzip'];
                $address['city'] = $cus_add['shipcity'];
                $address['telephone'] = $cus_add['shipphone'];
                $address['company'] = $cus_add['shipcompany'];
                if($cus_add['shipstateid'] != 0){
                    $region_id = $this->getRegionId($cus_add['shipstate'], $country_code);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['shipstate'];
                } else {
                    $address['region'] = $cus_add['shipstate'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success'){
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
        $order_ids_con = $this->arrayToInCondition($orderIds);
        $cusIds = $this->duplicateFieldValueFromList($orders['object'], 'ordcustid');
        $cus_ids_con = $this->arrayToInCondition($cusIds);
        $ext_query = array(
            'customers' => "SELECT * FROM _DBPRF_customers WHERE customerid IN {$cus_ids_con}",
            'order_addresses' => "SELECT * FROM _DBPRF_order_addresses WHERE order_id IN {$order_ids_con}",
            'order_products' => "SELECT * FROM _DBPRF_order_products WHERE orderorderid IN {$order_ids_con}",
            'order_shipping' => "SELECT * FROM _DBPRF_order_shipping WHERE order_id IN {$order_ids_con}",
            'order_configurable_fields' => "SELECT * FROM _DBPRF_order_configurable_fields WHERE orderid IN {$order_ids_con}"
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
        if (LitExtension_CartMigration_Model_Custom::ORDER_CONVERT) {
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();
        $customer = $this->getRowFromListByField($ordersExt['object']['customers'], 'customerid', $order['ordcustid']);

        $address_billing['firstname'] = $order['ordbillfirstname'];
        $address_billing['lastname'] = $order['ordbilllastname'];
        $address_billing['company'] = $order['ordbillcompany'];
        $address_billing['email']   = $customer['custconemail'];
        $address_billing['street']  = $order['ordbillstreet1']."\n".$order['ordbillstreet2'];
        $address_billing['city'] = $order['ordbillsuburb'];
        $address_billing['postcode'] = $order['ordbillzip'];
        $address_billing['country_id'] = $order['ordbillcountrycode'];
        $billing_region_id = $this->getRegionId($order['ordbillstate'], $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $order['ordbillstate'];
        }
        $address_billing['telephone'] = $order['ordbillphone'];

        $order_addresses = $this->getRowFromListByField($ordersExt['object']['order_addresses'], 'order_id', $order['orderid']);
        $address_shipping['firstname'] = $order_addresses['first_name'];
        $address_shipping['lastname'] = $order_addresses['last_name'];
        $address_shipping['company'] = $order_addresses['company'];
        $address_shipping['email']   = $customer['custconemail'];
        $address_shipping['street']  = $order_addresses['address_1']."\n".$order_addresses['address_2'];
        $address_shipping['city'] = $order_addresses['city'];
        $address_shipping['postcode'] = $order_addresses['zip'];
        $address_shipping['country_id'] = $order_addresses['country_iso2'];
        $shipping_region_id = $this->getRegionId($order_addresses['state'], $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $order_addresses['state'];
        }
        $address_shipping['telephone'] = $order_addresses['phone'];

        $orderConfigFields = $this->getListFromListByField($ordersExt['object']['order_configurable_fields'], 'orderid', $order['orderid']);
        $orderPro = $this->getListFromListByField($ordersExt['object']['order_products'], 'orderorderid', $order['orderid']);
        $carts = array();
        foreach($orderPro as $order_pro) {
            $cart = array();
            $product_id = $this->getMageIdProduct($order_pro['ordprodid']);
            if($product_id){
                $cart['product_id'] = $product_id;
            }
            $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $cart['name'] = $order_pro['ordprodname'];
            $cart['sku'] = $order_pro['ordprodsku'];
            $cart['price'] = $order_pro['base_price'];
            $cart['original_price'] = $order_pro['base_price'];
            $cart['tax_amount'] = $order_pro['total_tax'];
            $cart['tax_percent'] = $order_pro['price_tax'] / $order_pro['base_price'] * 100;
            $cart['qty_ordered'] = $order_pro['ordprodqty'];
            $cart['row_total'] = $order_pro['total_ex_tax'];
            $orderProOpts = unserialize($order_pro['ordprodoptions']);
            $product_opt = array();
            if(is_array($orderProOpts)){
                foreach($orderProOpts as $opt_label => $opt_val){
                    $option = array(
                        'label' => $opt_label,
                        'value' => $opt_val,
                        'print_value' => $opt_val,
                        'option_id' => '',
                        'option_type' => 'drop_down',
                        'option_value' => 0,
                        'custom_view' => false
                    );
                    $product_opt[] = $option;
                }
            }
            if($orderConfigFields){
                $proConfigFields = $this->getListFromListByField($orderConfigFields, 'ordprodid', $order_pro['orderprodid']);
                if($proConfigFields){
                    foreach($proConfigFields as $pro_config_field){
                        $option = array(
                            'label' => $pro_config_field['fieldname'],
                            'value' => $pro_config_field['textcontents'],
                            'print_value' => $pro_config_field['textcontents'],
                            'option_id' => '',
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $product_opt[] = $option;
                    }
                }
            }
            if($product_opt){
                $cart['product_options'] = serialize(array('options' => $product_opt));
            }
            $carts[]= $cart;
        }

        $customer_id = $this->getMageIdCustomer($order['ordcustid']);
        $order_shipping = $this->getRowFromListByField($ordersExt['object']['order_shipping'], 'order_id', $order['orderid']);
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
        $order_data['customer_email'] = $customer['custconemail'];
        $order_data['customer_firstname'] = $customer['custconfirstname'];
        $order_data['customer_lastname'] = $customer['custconlastname'];
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status'] && isset($this->_notice['config']['order_status'][$order['ordstatus']])){
            $order_data['status'] = $this->_notice['config']['order_status'][$order['ordstatus']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['subtotal_ex_tax']);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['shipping_cost_inc_tax'];
        $order_data['base_shipping_amount'] =  $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] =  $order_data['shipping_amount'];
        $order_data['shipping_description'] = $order_shipping['method'];
        $order_data['tax_amount'] = $order['subtotal_tax'];
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['orddiscountamount'] + $order['coupon_discount'];
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['total_inc_tax']);
        $order_data['base_grand_total'] = $order_data['grand_total'];
//        $order_data['base_total_invoiced'] = $order_data['grand_total'];
//        $order_data['total_paid'] = $order_data['grand_total'];
//        $order_data['base_total_paid'] = $order_data['grand_total'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $store_currency['base'];
        $order_data['global_currency_code'] = $store_currency['base'];
        $order_data['store_currency_code'] = $store_currency['base'];
        $order_data['order_currency_code'] = $store_currency['base'];
        $order_data['created_at'] = date("Y-m-d H:i:s",$order['orddate']);

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
        $order_status_data['status'] = isset($this->_notice['config']['order_status'][$order['ordstatus']]) ? $this->_notice['config']['order_status'][$order['ordstatus']] : '';
        if($order_status_data['status']){
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['comment'] = "<b>Reference order #".$order['orderid']."</b><br /><b>Payment method: </b>".$order['orderpaymentmethod']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order['ordcustmessage'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date("Y-m-d H:i:s",$order['ordlastmodified']);
        $order_status_data['created_at'] = date("Y-m-d H:i:s",$order['orddate']);
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
        $query = "SELECT * FROM _DBPRF_reviews WHERE reviewid > {$id_src} ORDER BY reviewid ASC LIMIT {$limit}";
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
        return $review['reviewid'];
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
        $product_mage_id = $this->getMageIdProduct($review['revproductid']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['reviewid']} import failed. Error: Product Id = {$review['revproductid']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        if($review['revstatus'] == 1){
            $data['status_id'] = 1;
        }elseif($review['revstatus'] == 2){
            $data['status_id'] = 3;
        }else{
            $data['status_id'] = 2;
        }
        $data['title'] = $review['revtitle'];
        $data['detail'] = $review['revtext'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['nickname'] = $review['revfromname'];
        $data['rating'] = $review['revrating'];
        $data['created_at'] = date("Y-m-d H:i:s",$review['revdate']);
        $data['review_id_import'] = $review['reviewid'];
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
    protected function _importChildrenProduct($product, $productsExt, $variation_combinations){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = array();
        foreach($variation_combinations as $variation) {
            $dataOpts = array();
            $option_collection = '';
            $vcOptionIds = explode(',', $variation['vcoptionids']);
            foreach($vcOptionIds as $vc_opt_id){
                $variation_option = $this->getRowFromListByField($productsExt['object']['product_variation_options'], 'voptionid', $vc_opt_id);
                $attribute_name = $variation_option['voname'];
                $attribute_code = $this->joinTextToKey($attribute_name, 27, '_');
                $option_name = $variation_option['vovalue'];
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
            }
            $data_variation = array(
                'option_collection' => $option_collection,
                'object' => $variation
            );
            $convertPro = $this->_convertProduct($product, $productsExt, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, true, $data_variation);
            $pro_import = $this->_process->product($convertPro);
            if ($pro_import['result'] !== 'success') {
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

    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array())
    {
        $pro_data = $categories = array();
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $proCat = $this->getListFromListByField($productsExt['object']['categoryassociations'], 'productid', $product['productid']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['categoryid']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        if($is_variation_pro){
            $pro_data['name'] = $product['prodname'] . $data_variation['option_collection'];
            $pro_data['sku'] = $this->createProductSku($data_variation['object']['vcsku'], $this->_notice['config']['languages']);
            if($data_variation['object']['vcpricediff'] == 'fixed'){
                $pro_data['price'] = $data_variation['object']['vcprice'];
            }elseif($data_variation['object']['vcpricediff'] == 'add'){
                $pro_data['price'] = $product['prodcalculatedprice'] + $data_variation['object']['vcprice'];
            }else{
                $pro_data['price'] = $product['prodcalculatedprice'] - $data_variation['object']['vcprice'];
            }
            if($data_variation['object']['vcweightdiff'] == 'fixed'){
                $pro_data['weight'] = $data_variation['object']['vcweight'];
            }elseif($data_variation['object']['vcweightdiff'] == 'add'){
                $pro_data['weight'] = $product['prodweight'] + $data_variation['object']['vcweight'];
            }else{
                $pro_data['weight'] = $product['prodweight'] - $data_variation['object']['vcweight'];
            }
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            if($data_variation['object']['vcimage'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $data_variation['object']['vcimage'], 'catalog/product', false, true)){
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
            }
            if($product['prodinvtrack'] == 2){
                $pro_data['stock_data'] = array(
                    'is_in_stock' => ($data_variation['object']['vcstock'] > 0) ? 1 : 0,
                    'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['vcstock'] < 1)? 0 : 1,
                    'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['vcstock'] < 1)? 0 : 1,
                    'qty' => $data_variation['object']['vcstock']
                );
            }else{
                $pro_data['stock_data'] = array(
                    'manage_stock' => 0,
                    'is_in_stock' => 1,
                    'use_config_manage_stock' => 0,
                );
            }
        }else{
            if($product['prodinvtrack'] == 1){
                $pro_data['stock_data'] = array(
                    'is_in_stock' => ($product['prodcurrentinv'] > 0) ? 1 : 0,
                    'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['prodcurrentinv'] < 1)? 0 : 1,
                    'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['prodcurrentinv'] < 1)? 0 : 1,
                    'qty' => $product['prodcurrentinv']
                );
            }else{
                $pro_data['stock_data'] = array(
                    'manage_stock' => 0,
                    'is_in_stock' => 1,
                    'use_config_manage_stock' => 0,
                );
            }
            $pro_data['name'] = $product['prodname'];
            $pro_data['sku'] = $this->createProductSku($product['prodcode'], $this->_notice['config']['languages']);
            if($product['prodretailprice'] > 0){
                $pro_data['price'] = $product['prodretailprice'];
                $pro_data['special_price'] =  $product['prodcalculatedprice'];
            }else{
                $pro_data['price'] = $product['prodprice'];
                if($product['prodsaleprice'] > 0){
                    $pro_data['special_price'] =  $product['prodsaleprice'];
                }
            }
            $tierPrices = array();
            $discountRules = $this->getListFromListByField($productsExt['object']['product_discounts'], 'discountprodid', $product['productid']);
            if($discountRules){
                foreach($discountRules as $discount_rule){
                    if($discount_rule['discountquantitymin']){
                        $dis_price = $discount_rule['discountamount'];
                        if($discount_rule['discounttype'] == 'price'){
                            $dis_price = $product['prodcalculatedprice'] - $discount_rule['discountamount'];
                        }
                        if($discount_rule['discounttype'] == 'percent'){
                            $dis_price = $product['prodcalculatedprice'] - ($product['prodcalculatedprice'] * $discount_rule['discountamount'] / 100);
                        }
                        $tierPrices[] = array(
                            'website_id'  => 0,
                            'cust_group'  => 32000,
                            'price_qty'   => $discount_rule['discountquantitymin'],
                            'price'       => $dis_price
                        );
                    }
                }
            }
            $pro_data['tier_price'] = $tierPrices;
            $pro_data['weight'] = $product['prodweight'];
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $images = $this->getListFromListByField($productsExt['object']['product_images'], 'imageprodid', $product['productid']);
            foreach($images as $img){
                if($img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $img['imagefile'], 'catalog/product', false, true)){
                    if($img['imageisthumb'] == 1){
                        $pro_data['image_import_path'] = array('path' => $img_path, 'label' => $img['imagedesc']);
                    }else{
                        $pro_data['image_gallery'][] = array('path' => $img_path, 'label' => $img['imagedesc']) ;
                    }
                }
            }
        }
        $tags = array();
        $proTags = $this->getListFromListByField($productsExt['object']['product_tagassociations'], 'productid', $product['productid']);
        if($proTags){
            foreach($proTags as $pro_tag){
                $tags[] = $pro_tag['tagname'];
            }
        }
        $pro_data['tags'] = $tags;
        $pro_data['description'] = $this->changeImgSrcInText($product['proddesc'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $product['prodpagetitle'] ;
        $pro_data['meta_keyword'] = $product['prodmetakeywords'];
        $pro_data['meta_description'] = $product['prodmetadesc'];
        $pro_data['weight']   = $product['prodweight'] ? $product['prodweight']: 0 ;
        $pro_data['status'] = ($product['prodvisible']== 1)? 1 : 2;
        if($tax_pro_id = $this->getMageIdTaxProduct($product['tax_class_id'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = date("Y-m-d H:i:s", $product['proddateadded']);
        if($manufacture_mage_id = $this->getMageIdManufacturer($product['prodbrandid'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        $pro_data['multi_store'] = array();
        if(!$is_variation_pro){
            if($this->_seo){
                $seo = $this->_seo->convertProductSeo($this, $product, $productsExt);
                if($seo){
                    $pro_data['seo_url'] = $seo;
                }
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
        $query = "SELECT * FROM _DBPRF_tax_classes ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_brands ORDER BY brandid ASC";
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
        $query = "SELECT * FROM _DBPRF_products ORDER BY productid ASC";
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
        $query = "SELECT * FROM _DBPRF_customers ORDER BY customerid ASC";
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
        $query = "SELECT * FROM _DBPRF_reviews ORDER BY reviewid ASC";
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
