<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Xcartv5 extends LitExtension_CartMigration_Model_Cart
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
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_attribute_options WHERE attribute_id = {$this->_notice['extend']['attribute_manufacturer_id']} AND id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE category_id > {$this->_notice['categories']['id_src']} AND parent_id > 0",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_profiles WHERE order_id is NULL AND profile_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE order_id > {$this->_notice['orders']['id_src']} AND orderNumber IS NOT NULL",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
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
                "languages" => "SELECT * FROM _DBPRF_config WHERE name = 'default_language'",
                "currencies" => "SELECT * FROM _DBPRF_config AS cfg LEFT JOIN _DBPRF_currencies AS cur ON cfg.value = cur.currency_id
                                  WHERE cfg.name = 'shop_currency'",
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = isset($object['languages']['0']['value']) ? $object['languages']['0']['value'] : 'en';
            $this->_notice['config']['default_currency'] = isset($def_currencies['object']['0']['value']) ? $def_currencies['object']['0']['value'] : 1;
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_languages AS l
                                  LEFT JOIN _DBPRF_language_translations AS lt ON l.lng_id = lt.id WHERE l.added = 1",
                "orders_status" => "SELECT * FROM _DBPRF_order_shipping_status_translations WHERE code = '{$this->_notice['config']['default_lang']}'",
                'memberships' => "SELECT * FROM _DBPRF_membership_translations WHERE code = '{$this->_notice['config']['default_lang']}'"
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
            $lang_name = $language_row['name'];
            $language_data[$lang_code] = $lang_name;
        }
        $currency_data[$this->_notice['config']['default_currency']] = isset($def_currencies['object']['0']['code']) ? $def_currencies['object']['0']['code'] : 'USD';
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_value = $order_status_row['id'];
            $order_status_name = $order_status_row['name'];
            $order_status_data[$order_status_value] = $order_status_name;
        }
        $customer_group_data[0] = 'No Membership';
        foreach($obj['memberships'] as $membership_row){
            $membership_id = $membership_row['id'];
            $membership_name = $membership_row['name'];
            $customer_group_data[$membership_id] = $membership_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['customer_group_data'] = $customer_group_data;
        $manData = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                'attribute_translations' => "SELECT * FROM _DBPRF_attribute_translations WHERE name = 'Manufacturer' AND code = '{$this->_notice['config']['default_lang']}'"
            ))
        ));
        if($manData && $manData['result'] == 'success'){
            $manufacture_data = isset($manData['object']['attribute_translations'][0]) ? $manData['object']['attribute_translations'][0] : false;
            $this->_notice['extend']['attribute_manufacturer_id']= $manufacture_data['id'];
        }
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
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_attribute_options WHERE attribute_id = {$this->_notice['extend']['attribute_manufacturer_id']} AND id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_categories WHERE category_id > {$this->_notice['categories']['id_src']} AND parent_id > 0",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_profiles WHERE order_id is NULL AND profile_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE order_id > {$this->_notice['orders']['id_src']} AND orderNumber IS NOT NULL",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
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
     * Get data of table convert to tax rule
     *
     * @return array : Response of connector
     */
    public function getTaxesMain(){
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = "SELECT * FROM _DBPRF_tax_classes AS tc
                    LEFT JOIN _DBPRF_tax_class_translations AS tct ON tc.id = tct.id AND tct.code = '{$this->_notice['config']['default_lang']}' WHERE tc.id > {$id_src} ORDER BY tc.id ASC LIMIT {$limit}";
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
        $tax_rate_ids = $this->duplicateFieldValueFromList($taxes['object'], 'id');
        $tax_rate_ids_con = $this->arrayToInCondition($tax_rate_ids);
        $ext_query = array(
            'sales_tax_rates' => "SELECT * FROM _DBPRF_sales_tax_rates WHERE tax_class_id IN {$tax_rate_ids_con}"
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
        $tax_zone_ids = $this->duplicateFieldValueFromList($taxesExt['object']['sales_tax_rates'], 'zone_id');
        $tax_zone_ids_con = $this->arrayToInCondition($tax_zone_ids);
        $ext_rel_query = array(
            'zone_elements' => "SELECT * FROM _DBPRF_zone_elements WHERE zone_id IN {$tax_zone_ids_con} AND element_type IN ('C','S')"
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
        $taxRates = $this->getListFromListByField($taxesExt['object']['sales_tax_rates'], 'tax_class_id', $tax['id']);
        $listZoneCountries = $this->getListFromListByField($taxesExt['object']['zone_elements'], 'element_type', 'C');
        $listZoneStates = $this->getListFromListByField($taxesExt['object']['zone_elements'], 'element_type', 'S');
        if($taxRates){
            foreach($taxRates as $tax_rate){
                if($tax_rate['zone_id'] > 0){
                    $zoneCountriesForTax = $this->getListFromListByField($listZoneCountries, 'zone_id', $tax_rate['zone_id']);
                    $zoneStatesForTax = $this->getListFromListByField($listZoneStates, 'zone_id', $tax_rate['zone_id']);
                    $cookTaxRates = $this->_cookTaxRates($zoneCountriesForTax, $zoneStatesForTax, $tax_rate['value'] + 0);
                }else{
                    $cookTaxRates = $this->_cookTaxRatesAllCountries($tax_rate['value'] + 0);
                }
                foreach($cookTaxRates as $row_cook){
                    if(!$row_cook['country']){
                        continue ;
                    }
                    $tax_rate_data = array();
                    $tax_rate_data['code'] = $this->createTaxRateCode($tax['name'] . "-" . $row_cook['country'] . "-". $row_cook['state_code']);
                    $tax_rate_data['tax_country_id'] = $row_cook['country'];
                    if(!$row_cook['state']){
                        $tax_rate_data['tax_region_id'] = 0;
                    }else{
                        $tax_rate_data['tax_region_id'] = $row_cook['state'];
                    }
                    $tax_rate_data['zip_is_range'] = 0;
                    $tax_rate_data['tax_postcode'] = "*";
                    $tax_rate_data['rate'] = $tax_rate['value'] + 0;
                    $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                    if($tax_rate_ipt['result'] == 'success'){
                        $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
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
        $query = "SELECT * FROM _DBPRF_attribute_options WHERE attribute_id = {$this->_notice['extend']['attribute_manufacturer_id']} AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers : Data of connector return for query function getManufacturersMainQuery
     * @return array
     */
    protected function _getManufacturersExtQuery($manufacturers){
        $man_ids = $this->duplicateFieldValueFromList($manufacturers['object'], 'id');
        $man_ids_query = $this->arrayToInCondition($man_ids);
        $ext_query = array(
            'attribute_option_translations' => "SELECT * FROM _DBPRF_attribute_option_translations WHERE id IN {$man_ids_query} AND code = '{$this->_notice['config']['default_lang']}'"
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
        return $manufacturer['id'];
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
        $man_name = $this->getRowValueFromListByField($manufacturersExt['object']['attribute_option_translations'], 'id', $manufacturer['id'], 'name');
        $manufacturer_data['value']['option'] = array(
            0 => $man_name
        );
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $man_name;
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
        $query = "SELECT * FROM _DBPRF_categories WHERE category_id > {$id_src} AND parent_id > 0 ORDER BY category_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'category_id');
        $cat_id_in_query = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'category_translations' => "SELECT * FROM _DBPRF_category_translations WHERE id IN {$cat_id_in_query}",
            'category_images' => "SELECT * FROM _DBPRF_category_images WHERE category_id IN {$cat_id_in_query}",
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
        return $category['category_id'];
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
        if($category['parent_id'] == 1){
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
                        'msg' => $this->consoleWarning("Category Id = {$category['category_id']} import failed. Error: Could not import parent category id = {$category['parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['category_translations'], 'id', $category['category_id']);
        $cat_default = $this->getRowFromListByField($catDesc, 'code', $this->_notice['config']['default_lang']);
        $cat_data['name'] = $cat_default['name'];
        $cat_data['description'] = $cat_default['description'];
        $cat_data['meta_title'] = $cat_default['metaTitle'];
        $cat_data['meta_keywords'] = $cat_default['metaTags'];
        $cat_data['meta_description'] = $cat_default['metaDesc'];
        $category_image = $this->getRowValueFromListByField($categoriesExt['object']['category_images'], 'category_id', $category['category_id'], 'path');
        if($category_image && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), 'category/' . $category_image , 'catalog/category')){
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['enabled'] == 1) ? 1 : 0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_code => $value){
            $store_data = array();
            $store_change = $this->getRowFromListByField($catDesc, 'code', $lang_code);
            if($lang_code != $this->_notice['config']['default_lang'] && $store_change){
                $store_data['store_id'] = $value;
                $store_data['name'] = $store_change['name'];
                $store_data['description'] = $store_change['description'];
                $store_data['meta_title'] = $store_change['metaTitle'];
                $store_data['meta_keywords'] = $store_change['metaTags'];
                $store_data['meta_description'] = $store_change['metaDesc'];
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
        $query = "SELECT * FROM _DBPRF_products WHERE product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'product_id');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'product_translations' => "SELECT * FROM _DBPRF_product_translations WHERE id IN {$pro_ids_query}",
            'attribute_values_select' => "SELECT * FROM _DBPRF_attribute_values_select WHERE product_id IN {$pro_ids_query}",
            'attribute_values_checkbox' => "SELECT * FROM _DBPRF_attribute_values_checkbox WHERE product_id IN {$pro_ids_query}",
            'attribute_values_text' => "SELECT * FROM _DBPRF_attribute_values_text WHERE product_id IN {$pro_ids_query}",
            'product_variants' => "SELECT * FROM _DBPRF_product_variants WHERE product_id IN {$pro_ids_query}",
            'product_variants_attributes' => "SELECT * FROM _DBPRF_product_variants_attributes WHERE product_id IN {$pro_ids_query}",
            'category_products' => "SELECT * FROM _DBPRF_category_products WHERE product_id IN {$pro_ids_query}",
            'inventory' => "SELECT * FROM _DBPRF_inventory WHERE inventoryId IN {$pro_ids_query}",
            'product_images' => "SELECT * FROM _DBPRF_product_images WHERE product_id IN {$pro_ids_query}",
            'upselling_products' => "SELECT * FROM _DBPRF_upselling_products WHERE product_id IN {$pro_ids_query} OR parent_product_id IN {$pro_ids_query}"
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
        $attrSelectIds = $this->duplicateFieldValueFromList($productsExt['object']['attribute_values_select'], 'attribute_id');
        $attrCheckBoxIds = $this->duplicateFieldValueFromList($productsExt['object']['attribute_values_checkbox'], 'attribute_id');
        $attrTextIds = $this->duplicateFieldValueFromList($productsExt['object']['attribute_values_text'], 'attribute_id');
        $attribute_ids = array_unique(array_merge($attrSelectIds, $attrCheckBoxIds, $attrTextIds));
        $attribute_ids_con = $this->arrayToInCondition($attribute_ids);
        $attrOptSelectIds = $this->duplicateFieldValueFromList($productsExt['object']['attribute_values_select'], 'attribute_option_id');
        $attrOptCheckBoxIds = $this->duplicateFieldValueFromList($productsExt['object']['attribute_values_checkbox'], 'attribute_option_id');
        $attrOptTextIds = $this->duplicateFieldValueFromList($productsExt['object']['attribute_values_text'], 'attribute_option_id');
        $attribute_opt_ids = array_unique(array_merge($attrOptSelectIds, $attrOptCheckBoxIds, $attrOptTextIds));
        $attr_opt_ids_con = $this->arrayToInCondition($attribute_opt_ids);
        $pro_variant_ids = $this->duplicateFieldValueFromList($productsExt['object']['product_variants'], 'id');
        $pro_variant_ids_con = $this->arrayToInCondition($pro_variant_ids);
        $ext_rel_query = array(
            'attribute_translations' => "SELECT * FROM _DBPRF_attribute_translations WHERE id IN {$attribute_ids_con}",
            'attribute_option_translations' => "SELECT * FROM _DBPRF_attribute_option_translations WHERE id IN {$attr_opt_ids_con}",
            'product_variant_attribute_value_checkbox' => "SELECT * FROM _DBPRF_product_variant_attribute_value_checkbox WHERE variant_id IN {$pro_variant_ids_con}",
            'product_variant_attribute_value_select' => "SELECT * FROM _DBPRF_product_variant_attribute_value_select WHERE variant_id IN {$pro_variant_ids_con}",
            'product_variant_images' => "SELECT * FROM _DBPRF_product_variant_images WHERE product_variant_id IN {$pro_variant_ids_con}"
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
        return $product['product_id'];
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
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $proVariants = $this->getListFromListByField($productsExt['object']['product_variants'], 'product_id', $product['product_id']);
        if($proVariants){
            $type_id = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $config_data = $this->_importChildrenProduct($product, $productsExt, $proVariants);
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
        if (parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)) {
            return;
        }
        //Related Product
        $products_links = Mage::getModel('catalog/product_link_api');
        $upSellingPro = $this->getListFromListByField($productsExt['object']['upselling_products'], 'parent_product_id', $product['product_id']);
        if($upSellingPro){
            foreach($upSellingPro as $up_selling_pro){
                if($pro_id_related = $this->getMageIdProduct($up_selling_pro['product_id'])){
                    $related_data = array('position' => $up_selling_pro['orderBy'] ? $up_selling_pro['orderBy'] : '');
                    $products_links->assign("related", $product_mage_id, $pro_id_related, $related_data);
                }else{
                    continue;
                }
            }
        }
        $upSellingProSrc = $this->getListFromListByField($productsExt['object']['upselling_products'], 'product_id', $product['product_id']);
        if($upSellingProSrc){
            foreach($upSellingProSrc as $up_selling_pro_src){
                if($proSrcId = $this->getMageIdProduct($up_selling_pro_src['parent_product_id'])){
                    $related_data = array('position' => $up_selling_pro_src['orderBy'] ? $up_selling_pro_src['orderBy'] : '');
                    $products_links->assign("related", $proSrcId, $product_mage_id, $related_data);
                }else{
                    continue;
                }
            }
        }
        $proVariantAttr = $this->getListFromListByField($productsExt['object']['product_variants_attributes'], 'product_id', $product['product_id']);
        $attrValCheck = $this->getListFromListByField($productsExt['object']['attribute_values_checkbox'], 'product_id', $product['product_id']);
        $attrValSelect = $this->getListFromListByField($productsExt['object']['attribute_values_select'], 'product_id', $product['product_id']);
        $attrValText = $this->getListFromListByField($productsExt['object']['attribute_values_text'], 'product_id', $product['product_id']);
        $attrCheckIds = $this->duplicateFieldValueFromList($attrValCheck, 'attribute_id');
        $attrSelectIds = $this->duplicateFieldValueFromList($attrValSelect, 'attribute_id');
        $attrTexIds = $this->duplicateFieldValueFromList($attrValText , 'attribute_id');
        $proVariantAttrIds = $this->duplicateFieldValueFromList($proVariantAttr, 'attribute_id');
        $custom_option = array();
        foreach($attrCheckIds as $attr_check_ids){
            if(!in_array($attr_check_ids, $proVariantAttrIds)){
                $options = array();
                $attrValCheckCus = $this->getListFromListByField($attrValCheck, 'attribute_id', $attr_check_ids);
                foreach($attrValCheckCus as $attr_val_check_cus){
                    $tmp['option_type_id'] = -1;
                    $tmp['title'] = $attr_val_check_cus['value'];
                    $tmp['price'] = $attr_val_check_cus['priceModifier'];
                    $tmp['price_type'] = ($attr_val_check_cus['priceModifierType'] == 'a') ? 'fixed' : 'percent';
                    $options[]=$tmp;
                }
                $attrName = $this->getListFromListByField($productsExt['object']['attribute_translations'], 'id', $attr_check_ids);
                $attribute_name_def = $this->getRowValueFromListByField($attrName, 'code', $this->_notice['config']['default_lang'], 'name');
                $tmp_opt = array(
                    'title' => $attribute_name_def,
                    'type' => 'checkbox',
                    'is_require' => 0,
                    'sort_order' => 0,
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
        }
        foreach($attrSelectIds as $attr_select_id){
            if(!in_array($attr_select_id, $proVariantAttrIds) && $attr_select_id != $this->_notice['extend']['attribute_manufacturer_id']){
                $options = array();
                $attrValSelectCus = $this->getListFromListByField($attrValSelect, 'attribute_id', $attr_select_id);
                foreach($attrValSelectCus as $attr_val_select_cus){
                    $attrOptTrans = $this->getListFromListByField($productsExt['object']['attribute_option_translations'], 'id', $attr_val_select_cus['attribute_option_id']);
                    $attr_opt_def_name = $this->getRowValueFromListByField($attrOptTrans, 'code', $this->_notice['config']['default_lang'], 'name');
                    $tmp['option_type_id'] = -1;
                    $tmp['title'] = $attr_opt_def_name;
                    $tmp['price'] = $attr_val_select_cus['priceModifier'];
                    $tmp['price_type'] = ($attr_val_select_cus['priceModifierType'] == 'a') ? 'fixed' : 'percent';
                    $options[]=$tmp;
                }
                $attrName = $this->getListFromListByField($productsExt['object']['attribute_translations'], 'id', $attr_select_id);
                $attribute_name_def = $this->getRowValueFromListByField($attrName, 'code', $this->_notice['config']['default_lang'], 'name');
                $tmp_opt = array(
                    'title' => $attribute_name_def,
                    'type' => 'drop_down',
                    'is_require' => 0,
                    'sort_order' => 0,
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
        }
        foreach($attrTexIds as $attr_text_id){
            if(!in_array($attr_text_id, $proVariantAttrIds)){
                $options = array();
                $attrValTextCus = $this->getListFromListByField($attrValText, 'attribute_id', $attr_text_id);
                foreach($attrValTextCus as $attr_val_text_cus){
                    $tmp['option_type_id'] = -1;
                    $tmp['title'] = $attr_val_text_cus['value'];
                    $tmp['price'] = '';
                    $tmp['price_type'] = 'fixed';
                    $options[]=$tmp;
                }
                $attrName = $this->getListFromListByField($productsExt['object']['attribute_translations'], 'id', $attr_text_id);
                $attribute_name_def = $this->getRowValueFromListByField($attrName, 'code', $this->_notice['config']['default_lang'], 'name');
                $tmp_opt = array(
                    'title' => $attribute_name_def,
                    'type' => 'field',
                    'is_require' => 0,
                    'sort_order' => 0,
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
        }
        $this->importProductOption($product_mage_id, $custom_option);
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_profiles WHERE order_id is NULL AND profile_id > {$id_src} ORDER BY profile_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'profile_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'profile_addresses' => "SELECT * FROM _DBPRF_profile_addresses WHERE profile_id IN {$customer_ids_query}",
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
        $add_ids = $this->duplicateFieldValueFromList($customersExt['object']['profile_addresses'], 'address_id');
        $add_ids_query = $this->arrayToInCondition($add_ids);
        $state_ids = $this->duplicateFieldValueFromList($customersExt['object']['profile_addresses'], 'state_id');
        $state_ids_query = $this->arrayToInCondition($state_ids);
        $ext_rel_query = array(
            'address_field_value' => "SELECT afv.*, af.serviceName FROM _DBPRF_address_field_value AS afv
                                        LEFT JOIN _DBPRF_address_field AS af ON af.id = afv.address_field_id
                                        WHERE afv.address_id IN {$add_ids_query}",
            'states' => "SELECT * FROM _DBPRF_states WHERE state_id IN {$state_ids_query}"
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
        return $customer['profile_id'];
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
            $cus_data['id'] = $customer['profile_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['login'];
        $addresses_id = $this->getRowValueFromListByField($customersExt['object']['profile_addresses'], 'profile_id', $customer['profile_id'], 'address_id');
        $address_field_value = $this->getListFromListByField($customersExt['object']['address_field_value'], 'address_id', $addresses_id);
        $cus_data['firstname'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'firstname', 'value');
        $cus_data['lastname'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'lastname', 'value');
        $cus_data['created_at'] = date("Y-m-d H:i:s", $customer['added']);
        if($customer['membership_id'] > 0){
            $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$customer['membership_id']]) ? $this->_notice['config']['customer_group'][$customer['membership_id']] : 1;
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
        if (parent::afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt)) {
            return;
        }
        $this->_importCustomerRawPass($customer_mage_id, $customer['password']);
        $profileAddresses = $this->getListFromListByField($customersExt['object']['profile_addresses'], 'profile_id', $customer['profile_id']);
        if($profileAddresses){
            foreach($profileAddresses as $key => $profile_add){
                $address = array();
                $address_field_value = $this->getListFromListByField($customersExt['object']['address_field_value'], 'address_id', $profile_add['address_id']);
                $address['firstname'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'firstname', 'value');
                $address['lastname'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'lastname', 'value');
                $address['country_id'] = $profile_add['country_code'];
                if($profile_add['state_id'] > 0){
                    $state_name = $this->getRowValueFromListByField($customersExt['object']['states'], 'state_id', $profile_add['state_id'], 'state');
                    $region_id = $this->getRegionId($state_name, $address['country_id']);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                }else{
                    $state_name = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'custom_state', 'value');
                    $region_id = $this->getRegionId($state_name, $address['country_id']);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }else{
                        $address['region'] = $state_name;
                    }
                }
                $address['street'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'street', 'value');
                $address['postcode'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'zipcode', 'value');
                $address['city'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'city', 'value');
                $address['telephone'] = $this->getRowValueFromListByField($address_field_value, 'serviceName', 'phone', 'value');
                $address_ipt = $this->_process->address($address, $customer_mage_id);
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

    /**
     * Query for get data use for import order
     *
     * @return string
     */
    protected function _getOrdersMainQuery(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_orders WHERE orderNumber IS NOT NULL AND order_id > {$id_src} ORDER BY order_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'order_id');
        $order_ids_con = $this->arrayToInCondition($orderIds);
        $profile_ids = $this->duplicateFieldValueFromList($orders['object'], 'profile_id');
        $orig_profile_ids = $this->duplicateFieldValueFromList($orders['object'], 'orig_profile_id');
        $allProfileIds = array_unique(array_merge($profile_ids, $orig_profile_ids));
        $allProfileIdsQuery = $this->arrayToInCondition($allProfileIds);
        $ext_query = array(
            'profiles' => "SELECT * FROM _DBPRF_profiles WHERE profile_id IN {$allProfileIdsQuery}",
            'profile_addresses' => "SELECT * FROM _DBPRF_profile_addresses WHERE profile_id IN {$allProfileIdsQuery}",
            'order_items' => "SELECT * FROM _DBPRF_order_items WHERE order_id IN {$order_ids_con}",
            'order_surcharges' => "SELECT * FROM _DBPRF_order_surcharges WHERE order_id IN {$order_ids_con}"
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
        $add_ids = $this->duplicateFieldValueFromList($ordersExt['object']['profile_addresses'], 'address_id');
        $add_ids_query = $this->arrayToInCondition($add_ids);
        $state_ids = $this->duplicateFieldValueFromList($ordersExt['object']['profile_addresses'], 'state_id');
        $state_ids_query = $this->arrayToInCondition($state_ids);
        $item_ids = $this->duplicateFieldValueFromList($ordersExt['object']['order_items'], 'item_id');
        $item_ids_query = $this->arrayToInCondition($item_ids);
        $ext_rel_query = array(
            'address_field_value' => "SELECT afv.*, af.serviceName FROM _DBPRF_address_field_value AS afv
                                        LEFT JOIN _DBPRF_address_field AS af ON af.id = afv.address_field_id
                                        WHERE afv.address_id IN {$add_ids_query}",
            'states' => "SELECT * FROM _DBPRF_states WHERE state_id IN {$state_ids_query}",
            'order_item_attribute_values' => "SELECT * FROM _DBPRF_order_item_attribute_values WHERE item_id IN {$item_ids_query}"
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
        return $order['order_id'];
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

        $profileAddresses = $this->getListFromListByField($ordersExt['object']['profile_addresses'], 'profile_id', $order['profile_id']);

        $profileAddBill = $this->getRowFromListByField($profileAddresses, 'is_billing' , 1);
        $bill_address_field_value = $this->getListFromListByField($ordersExt['object']['address_field_value'], 'address_id', $profileAddBill['address_id']);
        $address_billing['firstname'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'firstname', 'value');
        $address_billing['lastname'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'lastname', 'value');
        $address_billing['email']   = $this->getRowValueFromListByField($ordersExt['object']['profiles'], 'profile_id', $order['orig_profile_id'], 'login');
        $address_billing['street']  = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'street', 'value');
        $address_billing['city'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'city', 'value');
        $address_billing['postcode'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'zipcode', 'value');
        $address_billing['country_id'] = $profileAddBill['country_code'];
        if($profileAddBill['state_id'] > 0){
            $state_name = $this->getRowValueFromListByField($ordersExt['object']['states'], 'state_id', $profileAddBill['state_id'], 'state');
            $region_id = $this->getRegionId($state_name, $address_billing['country_id']);
            if($region_id){
                $address_billing['region_id'] = $region_id;
            }
        }else{
            $state_name = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'custom_state', 'value');
            $region_id = $this->getRegionId($state_name, $address_billing['country_id']);
            if($region_id){
                $address_billing['region_id'] = $region_id;
            }else{
                $address_billing['region'] = $state_name;
            }
        }
        $address_billing['telephone'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'phone', 'value');

        $profileAddShip = $this->getRowFromListByField($profileAddresses, 'is_shipping' , 1);
        $ship_address_field_value = $this->getListFromListByField($ordersExt['object']['address_field_value'], 'address_id', $profileAddShip['address_id']);
        $address_shipping['firstname'] = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'firstname', 'value');
        $address_shipping['lastname'] = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'lastname', 'value');
        $address_shipping['email']   =  $address_billing['email'];
        $address_shipping['street']  = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'street', 'value');
        $address_shipping['city'] = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'city', 'value');
        $address_shipping['postcode'] = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'zipcode', 'value');
        $address_shipping['country_id'] = $profileAddShip['country_code'];
        if($profileAddShip['state_id'] > 0){
            $state_name = $this->getRowValueFromListByField($ordersExt['object']['states'], 'state_id', $profileAddShip['state_id'], 'state');
            $region_id = $this->getRegionId($state_name, $address_shipping['country_id']);
            if($region_id){
                $address_shipping['region_id'] = $region_id;
            }
        }else{
            $state_name = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'custom_state', 'value');
            $region_id = $this->getRegionId($state_name, $address_shipping['country_id']);
            if($region_id){
                $address_shipping['region_id'] = $region_id;
            }else{
                $address_shipping['region'] = $state_name;
            }
        }
        $address_shipping['telephone'] = $this->getRowValueFromListByField($ship_address_field_value, 'serviceName', 'phone', 'value');

        $orderItems = $this->getListFromListByField($ordersExt['object']['order_items'], 'order_id', $order['order_id']);
        $carts = array();
        if($orderItems){
            foreach($orderItems as $order_item){
                $cart = array();
                $product_id = $this->getMageIdProduct($order_item['object_id']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_item['name'];
                $cart['sku'] = $order_item['sku'];
                $cart['price'] = $order_item['itemNetPrice'];
                $cart['original_price'] = $order_item['itemNetPrice'];
//                $cart['tax_amount'] = $order_item['total_tax'];
//                $cart['tax_percent'] = $order_item['price_tax'] / $order_pro['base_price'] * 100;
                $cart['qty_ordered'] = $order_item['amount'];
                $cart['row_total'] = $order_item['total'];
                $orderItemAttrValues = $this->getListFromListByField($ordersExt['object']['order_item_attribute_values'], 'item_id', $order_item['item_id']);
                $product_opt = array();
                if($orderItemAttrValues){
                    foreach($orderItemAttrValues as $order_item_attr_val){
                        $option = array(
                            'label' => $order_item_attr_val['name'],
                            'value' => $order_item_attr_val['value'],
                            'print_value' => $order_item_attr_val['value'],
                            'option_id' => '',
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $product_opt[] = $option;
                    }
                }
                if($product_opt){
                    $cart['product_options'] = serialize(array('options' => $product_opt));
                }
                $carts[]= $cart;
            }
        }
        $customer_id = $this->getMageIdCustomer($order['orig_profile_id']);
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $orderSurcharges = $this->getListFromListByField($ordersExt['object']['order_surcharges'], 'order_id', $order['order_id']);
        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $this->getRowValueFromListByField($ordersExt['object']['profiles'], 'profile_id', $order['orig_profile_id'], 'login');
        $order_data['customer_firstname'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'firstname', 'value');
        $order_data['customer_lastname'] = $this->getRowValueFromListByField($bill_address_field_value, 'serviceName', 'lastname', 'value');
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status'] && isset($this->_notice['config']['order_status'][$order['shipping_status_id']])){
            $order_data['status'] = $this->_notice['config']['order_status'][$order['shipping_status_id']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['subtotal']);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $this->getRowValueFromListByField($orderSurcharges, 'type', 'shipping', 'value');
        $order_data['base_shipping_amount'] =  $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] =  $order_data['shipping_amount'];
        $order_data['shipping_description'] = $order['shipping_method_name'];
        $order_data['tax_amount'] = 0;
        $order_data['base_tax_amount'] = 0;
        $order_data['discount_amount'] = $this->getRowValueFromListByField($orderSurcharges, 'type', 'discount', 'value');
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['total']);
        $order_data['base_grand_total'] = $order_data['grand_total'];
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
        $data['order_src_id'] = $order['orderNumber'];
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
        $order_status_data['status'] = isset($this->_notice['config']['order_status'][$order['shipping_status_id']]) ? $this->_notice['config']['order_status'][$order['shipping_status_id']] : '';
        if($order_status_data['status']){
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['comment'] = "<b>Reference order #".$order['orderNumber']."</b><br /><b>Payment method: </b>".$order['payment_method_name']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order['notes'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date("Y-m-d H:i:s",$order['date']);
        $order_status_data['created_at'] = date("Y-m-d H:i:s",$order['lastRenewDate']);
        $this->_process->ordersComment($order_mage_id, $order_status_data);

        if($order['adminNotes']){
            $order_status_data_2 = $order_status_data;
            $order_status_data_2['comment'] = $order['adminNotes'];
            $this->_process->ordersComment($order_mage_id, $order_status_data_2);
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
        $query = "SELECT * FROM _DBPRF_reviews WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
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
        return $review['id'];
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
        $product_mage_id = $this->getMageIdProduct($review['product_id']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['id']} import failed. Error: Product Id = {$review['product_id']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        if($review['status'] > 0){
            $data['status_id'] = 1;
        }else{
            $data['status_id'] = 3;
        }
        $data['title'] = '';
        $data['detail'] = $review['review'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['nickname'] = $review['reviewerName'];
        $data['rating'] = $review['rating'];
        $data['created_at'] = date("Y-m-d H:i:s",$review['additionDate']);
        $data['review_id_import'] = $review['id'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

    ##################### Extend Function ###############################
    protected function _importChildrenProduct($product, $productsExt, $proVariants){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = $attrMage = array();
        if($proVariants){
            foreach($proVariants as $pro_variant){
                $option_collection = '';
                $dataAttrVariants = $dataOpts = array();
                $proVariantAttrValCheck = $this->getListFromListByField($productsExt['object']['product_variant_attribute_value_checkbox'], 'variant_id', $pro_variant['id']);
                $proVariantAttrValSelect = $this->getListFromListByField($productsExt['object']['product_variant_attribute_value_select'], 'variant_id', $pro_variant['id']);
                if($proVariantAttrValCheck){
                    foreach($proVariantAttrValCheck as $pro_vary_attr_val_check){
                        $attrValCheck = $this->getRowFromListByField($productsExt['object']['attribute_values_checkbox'], 'id', $pro_vary_attr_val_check['attribute_value_id']);
                        $attrTrans = $this->getListFromListByField($productsExt['object']['attribute_translations'], 'id', $attrValCheck['attribute_id']);
                        $tmp['attribute_name'] = $this->getRowValueFromListByField($attrTrans, 'code', $this->_notice['config']['default_lang'], 'name');
                        $tmp['attribute_option_value'] = $attrValCheck['value'];
                        $tmp['attribute_type'] = 'CheckBox';
                        $dataAttrVariants[] = $tmp;
                    }
                }
                if($proVariantAttrValSelect){
                    foreach($proVariantAttrValSelect as $pro_vary_attr_val_select){
                        $attrValSelect = $this->getRowFromListByField($productsExt['object']['attribute_values_select'], 'id', $pro_vary_attr_val_select['attribute_value_id']);
                        $attrTrans = $this->getListFromListByField($productsExt['object']['attribute_translations'], 'id', $attrValSelect['attribute_id']);
                        $attrOptTrans = $this->getListFromListByField($productsExt['object']['attribute_option_translations'], 'id', $attrValSelect['attribute_option_id']);
                        $tmp['attribute_name'] = $this->getRowValueFromListByField($attrTrans, 'code', $this->_notice['config']['default_lang'], 'name');
                        $tmp['attribute_option_value'] = $this->getRowValueFromListByField($attrOptTrans, 'code', $this->_notice['config']['default_lang'], 'name');
                        $tmp['attribute_type'] = 'Select';
                        $dataAttrVariants[] = $tmp;
                    }
                }
                if($dataAttrVariants){
                    foreach($dataAttrVariants as $data_attr_variant){
                        $attribute_code = $this->joinTextToKey($data_attr_variant['attribute_name'], 27, '_');
                        $attr_opt_val = $data_attr_variant['attribute_option_value'];
                        if($data_attr_variant['attribute_type'] == 'CheckBox'){
                            if($data_attr_variant['attribute_option_value'] == 1){
                                $attr_opt_val = 'Yes';
                            }else{
                                $attr_opt_val = 'No';
                            }
                        }
                        $opt_attr_data = array(
                            'entity_type_id'                => $entity_type_id,
                            'attribute_set_id'              => $this->_notice['config']['attribute_set_id'],
                            'attribute_code'                => $attribute_code,
                            'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                            'frontend_input'                => ($data_attr_variant['attribute_type'] == 'CheckBox') ? 'boolean' : 'select',
                            'frontend_label'                => array($data_attr_variant['attribute_name']),
                            'option'                        => array(
                                'value' => array(
                                    'option_0' => array($attr_opt_val)
                                )
                            )
                        );
                        $optAttrDataImport = $this->_process->attribute($opt_attr_data);
                        if (!$optAttrDataImport) {
                            return array(
                                'result' => "warning",
                                'msg' => $this->consoleWarning("Product Id = {$product['product_id']} import failed. Error: Product attribute could not create!")
                            );
                        }
                        $dataTMP = array(
                            'attribute_id' => $optAttrDataImport['attribute_id'],
                            'value_index' => ($data_attr_variant['attribute_type'] == 'CheckBox') ? $data_attr_variant['attribute_option_value'] : $optAttrDataImport['option_ids']['option_0'],
                            'is_percent' => 0,
                        );
                        $dataOpts[] = $dataTMP;
                        if ($data_attr_variant['attribute_option_value']){
                            if($data_attr_variant['attribute_type'] == 'CheckBox'){
                                $option_collection = $option_collection . ' - ' . ($data_attr_variant['attribute_option_value'] == 1) ? 'Yes' : 'No';
                            }else{
                                $option_collection = $option_collection . ' - ' . $data_attr_variant['attribute_option_value'];
                            }
                        }
                        $attrMage[$optAttrDataImport['attribute_id']]['attribute_label'] = $data_attr_variant['attribute_name'];
                        $attrMage[$optAttrDataImport['attribute_id']]['attribute_code'] = $optAttrDataImport['attribute_code'];
                        $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['label'] = $data_attr_variant['attribute_option_value'];
                        $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['value_index'] = ($data_attr_variant['attribute_type'] == 'CheckBox') ? $data_attr_variant['attribute_option_value'] : $optAttrDataImport['option_ids']['option_0'];
                        $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['pricing_value'] = 0;
                    }
                    $data_variation = array(
                        'option_collection' => $option_collection,
                        'object' => $pro_variant
                    );
                    $convertPro = $this->_convertProduct($product, $productsExt, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, true, $data_variation);
                    $pro_import = $this->_process->product($convertPro);
                    if ($pro_import['result'] !== 'success') {
                        return array(
                            'result' => "warning",
                            'msg' => $this->consoleWarning("Product Id = {$product['product_id']} import failed. Error: Product children could not create!")
                        );
                    }
                    foreach ($dataOpts as $dataAttribute) {
                        $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
                    }
                    $dataChildes[$pro_import['mage_id']] = $dataOpts;
                }
            }
        }
        if($dataChildes){
            $result = $this->_createConfigProductData($dataChildes, $attrMage);
        }
        return array(
            'result' => 'success',
            'data' => $result
        );
    }

    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array()){
        $pro_data = $categories = array();
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $proCat = $this->getListFromListByField($productsExt['object']['category_products'], 'product_id', $product['product_id']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        $proTranslations = $this->getListFromListByField($productsExt['object']['product_translations'], 'id', $product['product_id']);
        $pro_trans_def = $this->getRowFromListByField($proTranslations, 'code', $this->_notice['config']['default_lang']);
        if($is_variation_pro){
            $pro_data['name'] = $pro_trans_def['name'] . $data_variation['option_collection'];
            $pro_data['sku'] = $this->createProductSku($data_variation['object']['sku'], $this->_notice['config']['languages']);
            $pro_data['price'] = $data_variation['object']['price'] ? $data_variation['object']['price'] : 0;
            $pro_data['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['amount'] < 1)? 0 : 1,
                'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['amount'] < 1)? 0 : 1,
                'qty' => ($data_variation['object']['amount'] >= 0 )? $data_variation['object']['amount']: 0,
            );
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
        }else{
            $pro_data['name'] = $pro_trans_def['name'];
            $pro_data['sku'] = $this->createProductSku($product['sku'], $this->_notice['config']['languages']);
            $pro_data['price'] = $product['price'];
            if($product['salePriceValue'] > 0){
                if($product['discountType'] == 'sale_price'){
                    $pro_data['special_price'] = $product['salePriceValue'];
                }else{
                    $pro_data['special_price'] = $pro_data['price'] - ($pro_data['price'] * $product['salePriceValue'] / 100);
                }
            }
            $inventory = $this->getRowFromListByField($productsExt['object']['inventory'], 'inventoryId', $product['product_id']);
            $pro_data['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $inventory['amount'] < 1)? 0 : 1,
                'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $inventory['amount'] < 1)? 0 : 1,
                'qty' => ($inventory['amount'] >= 0 )? $inventory['amount']: 0,
            );
            if($inventory['lowLimitEnabled'] == 1){
                $pro_data['stock_data']['min_sale_qty'] = $inventory['lowLimitAmount'];
            }
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $images = $this->getListFromListByField($productsExt['object']['product_images'], 'product_id', $product['product_id']);
            if($images){
                $thumbnail = $images[0];
                foreach($images as $img){
                    if($img['orderby'] < $thumbnail['orderby']){
                        $thumbnail = $img;
                    }
                }
                if($img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'product/' . $thumbnail['path'], 'catalog/product', false, true)) {
                    $pro_data['image_import_path'] = array('path' => $img_path, 'label' => '');
                }
                foreach($images as $img){
                    if($img['id'] == $thumbnail['id']){
                        continue;
                    }else{
                        if($img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'product/' . $img['path'], 'catalog/product', false, true)) {
                            $pro_data['image_gallery'][] = array('path' => $img_path, 'label' => '') ;
                        }
                    }
                }
            }
        }
        $pro_data['description'] = $this->changeImgSrcInText($pro_trans_def['description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_trans_def['briefDescription'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $pro_trans_def['metaTitle'] ;
        $pro_data['meta_keyword'] = $pro_trans_def['metaTags'];
        $pro_data['meta_description'] = $pro_trans_def['metaDesc'];
        $pro_data['weight']   = $product['weight'] ? $product['weight']: 0 ;
        $pro_data['status'] = ($product['enabled']== 1)? 1 : 2;
        if($tax_pro_id = $this->getMageIdTaxProduct($product['tax_class_id'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = date("Y-m-d H:i:s", $product['date']);
        $attr_val_select  = $this->getListFromListByField($productsExt['object']['attribute_values_select'], 'product_id', $product['product_id']);
        $man_id = $this->getRowValueFromListByField($attr_val_select, 'attribute_id', $this->_notice['extend']['attribute_manufacturer_id'], 'attribute_option_id');
        if($manufacture_mage_id = $this->getMageIdManufacturer($man_id)){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        foreach($this->_notice['config']['languages'] as $lang_code => $store_id){
            if($lang_code != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proTranslations, 'code', $lang_code)){
                $store_data = array();
                if($is_variation_pro){
                    $store_data['name'] = $store_data_change['name'] . $data_variation['option_collection'];
                }else{
                    $store_data['name'] = $store_data_change['name'];
                }
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['short_description'] = $this->changeImgSrcInText($store_data_change['briefDescription'], $this->_notice['config']['add_option']['img_des']);
                $store_data['meta_title'] = $store_data_change['metaTitle'] ;
                $store_data['meta_keyword'] = $store_data_change['metaTags'];
                $store_data['meta_description'] = $store_data_change['metaDesc'];
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
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

    protected function _createConfigProductData($dataChildes, $attrMage){
        $attribute_config = array();
        $result['configurable_products_data'] = $dataChildes;
        foreach ($attrMage as $attr_id => $attribute) {
            $dad = array(
                'label' => $attribute['attribute_label'],
                'attribute_id' => $attr_id,
                'attribute_code' => $attribute['attribute_code'],
                'frontend_label' => $attribute['attribute_label'],
                'html_id' => 'config_super_product__attribute_'.$attr_id,
            );
            $values = array();
            foreach($attribute['values'] as $option) {
                $child = array(
                    'attribute_id' => $attr_id,
                    'is_percent' => 0,
                    'pricing_value' => $option['pricing_value'],
                    'label' => $option['label'],
                    'value_index' => $option['value_index'],
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

    protected function _cookTaxRates($zoneCountries, $zoneStates, $rate){
        $tax_rate = array();
        if($zoneCountries){
            foreach($zoneCountries as $row_country){
                $tmp = array();
                if($zoneStates){
                    foreach($zoneStates as $row_state){
                        if($row_country['element_value'] == substr($row_state['element_value'],0,2)){
                            $tmp['country'] = $row_country['element_value'];
                            $state_code= substr($row_state['element_value'],3);
                            $tmp['state'] = $this->_getRegionIdByCode($state_code, $tmp['country']);
                            $tmp['state_code'] = $state_code;
                            $tmp['rate'] = $rate;
                            $tax_rate[] = $tmp;
                        }
                    }
                }
                if(empty($tmp)){
                    $tmp['country'] = $row_country['element_value'];
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
        $query = "SELECT * FROM _DBPRF_tax_classes AS tc
                    LEFT JOIN _DBPRF_tax_class_translations AS tct ON tc.id = tct.id AND tct.code = '{$this->_notice['config']['default_lang']}'";
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
        $query = "SELECT * FROM _DBPRF_categories";
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
        $query = "SELECT * FROM _DBPRF_products";
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
        $query = "";
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
        $query = "";
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
        $query = "";
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