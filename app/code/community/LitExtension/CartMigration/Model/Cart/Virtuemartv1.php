<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Virtuemartv1
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $user_type = "'Public Front-end','Registered','Author','Editor','Publisher'";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_vm_tax_rate WHERE tax_rate_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_vm_manufacturer WHERE manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_vm_category WHERE category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_vm_product WHERE product_id > {$this->_notice['products']['id_src']} AND product_parent_id = 0",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE id > {$this->_notice['customers']['id_src']} AND `usertype` IN ($user_type)",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_vm_orders WHERE order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_vm_product_reviews WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
                'components' => "SELECT * FROM _DBPRF_components WHERE option = 'com_languages'",
                'vm_vendor' => "SELECT * FROM _DBPRF_vm_vendor ORDER BY vendor_id LIMIT 1",
                'vm_order_status' => "SELECT * FROM _DBPRF_vm_order_status",
                'user_group' => "SELECT * FROM _DBPRF_vm_shopper_group"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $currencies = array();
        $currency_code_def = "";
        foreach($data['object']['vm_vendor'] as $key => $row){
            if($row['vendor_currency']) $currencies = array_merge($currencies, array($row['vendor_currency']));
            if($row['vendor_accepted_currencies']) $currencies = array_merge($currencies, explode(',', $row['vendor_accepted_currencies']));
            if($key == 0){
                $currency_code_def = $row['vendor_currency'];
            }
        }
        $currencies = array_unique($currencies);
        $currency_con = $this->arrayToInCondition($currencies);
        $curSrc = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_vm_currency WHERE currency_code IN {$currency_con} ORDER BY currency_id ASC"
        ));
        if(!$curSrc || $curSrc['result'] != 'success'){
            return $this->errorConnector();
        }
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data[1] = "Default Language";
        $this->_notice['config']['default_lang'] = 1;
        foreach($currencies as $currency_code){
            $currency = $this->getRowFromListByField($curSrc['object'], 'currency_code', $currency_code);
            if($currency){
                $key = $currency['currency_id'];
                $value = $currency['currency_name'];
                $currency_data[$key] = $value;
                if($currency_code == $currency_code_def){
                    $this->_notice['config']['default_currency'] = $key;
                }
            }
        }
        foreach($data['object']['vm_order_status'] as $order_status_row){
            $key = $order_status_row['order_status_code'];
            $value = $order_status_row['order_status_name'];
            $order_status_data[$key] = $value;
        }
        foreach($data['object']['user_group'] as $user_group){
            $user_group_id = $user_group['shopper_group_id'];
            $user_group_name = $user_group['shopper_group_name'];
            $customer_group_data[$user_group_id] = $user_group_name;
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
        $user_type = "'Public Front-end','Registered','Author','Editor','Publisher'";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_vm_tax_rate WHERE tax_rate_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_vm_manufacturer WHERE manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_vm_category WHERE category_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_vm_product WHERE product_id > {$this->_notice['products']['id_src']} AND product_parent_id = 0",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE id > {$this->_notice['customers']['id_src']} AND `usertype` IN ($user_type)",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_vm_orders WHERE order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_vm_product_reviews WHERE review_id > {$this->_notice['reviews']['id_src']}"
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
            'query' => "SELECT * FROM _DBPRF_vm_currency WHERE currency_id IN {$cur_id_con}"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){
                $currency_id = $currency['currency_id'];
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
        $query = "SELECT * FROM _DBPRF_vm_tax_rate WHERE tax_rate_id > {$id_src} ORDER BY tax_rate_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $countryCode = $this->duplicateFieldValueFromList($taxes['object'], 'tax_country');
        $stateCode = $this->duplicateFieldValueFromList($taxes['object'], 'tax_state');
        $country_code_con = $this->arrayToInCondition($countryCode);
        $state_code_con = $this->arrayToInCondition($stateCode);
        $ext_query = array(
            'vm_country' => "SELECT * FROM _DBPRF_vm_country WHERE country_3_code IN {$country_code_con}",
            'vm_state' => "SELECT * FROM _DBPRF_vm_state WHERE state_2_code IN {$state_code_con}"
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
        return array();
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt){
        return $tax['tax_rate_id'];
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
        $country = $this->getRowFromListByField($taxesExt['object']['vm_country'], 'country_3_code', $tax['tax_country']);
        $state = array();
        if($tax['tax_state'] != "-"){
            $state = $this->getRowFromListByField($taxesExt['object']['vm_state'], 'state_2_code', $tax['tax_state']);
        }
        if($country){
            $tax_pro_name = $country['country_name'];
            if($state){
                $code = $country['country_name'] . "-" . $state['state_name'];
            } else {
                $code = $country['country_name'] . "-*";
            }
            $tax_pro_data = array(
                'class_name' => $tax_pro_name
            );
            $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
            if($tax_pro_ipt['result'] == 'success'){
                $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
                $this->taxProductSuccess($tax['tax_rate_id'], $tax_pro_ipt['mage_id']);
            }
            $tax_rate_data = array();
            $tax_rate_data['tax_country_id'] = $country['country_2_code'];
            $tax_rate_data['code'] = $this->createTaxRateCode($code);
            if($state){
                $tax_rate_data['tax_region_id'] = $this->getRegionId($state['state_name'], $country['country_2_code']);
            } else {
                $tax_rate_data['tax_region_id'] = 0;
            }
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = "*";
            $tax_rate_data['rate'] = $tax['tax_rate'] * 100;
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if($tax_rate_ipt['result'] == 'success'){
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
            $tax_rule_data = array();
            $tax_rule_data['code'] = $this->createTaxRuleCode($code);
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
        return array(
            'result' => "warning",
            'msg' => $this->consoleWarning("Tax Id = {$tax['tax_rate_id']} import failed. Error: Tax country not exists!")
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
        $query = "SELECT * FROM _DBPRF_vm_manufacturer WHERE manufacturer_id > {$id_src} ORDER BY manufacturer_id ASC LIMIT {$limit}";
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
        return $manufacturer['manufacturer_id'];
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
        $store_id = $this->_notice['config']['languages'][1];
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id,
            'value' => array(
                'option' => array(
                    0 => $manufacturer['mf_name'],
                    $store_id => $manufacturer['mf_name']
                )
            )
        );
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
        $query = "SELECT * FROM _DBPRF_vm_category WHERE category_id > {$id_src} ORDER BY category_id ASC LIMIT {$limit}";
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
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'vm_category_xref' => "SELECT * FROM _DBPRF_vm_category_xref WHERE category_child_id IN {$cat_id_con}"
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
        $parent = $this->getRowFromListByField($categoriesExt['object']['vm_category_xref'], 'category_child_id', $category['category_id']);
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
                        'msg' => $this->consoleWarning("Category Id = {$category['category_id']} import failed. Error: Could not import parent category id = {$parent['category_parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['category_name'];
        $cat_data['description'] = $category['category_description'];
        if($category['category_full_image'] || $category['category_thumb_image']){
            $cat_img_src = $category['category_full_image'] ? $category['category_full_image'] : $category['category_thumb_image'];
            $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  $cat_img_src, 'catalog/category');
            if($img_path){
                $cat_data['image'] = $img_path;
            }
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        if(isset($category['published'])){
            $is_active = $category['published'];
        }else{
            if($category['category_publish'] == 'Y'){
                $is_active = 1;
            }else{
                $is_active = 0;
            }
        }
        $cat_data['is_active'] = $is_active;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
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
        $query = "SELECT * FROM _DBPRF_vm_product WHERE product_id > {$id_src} AND product_parent_id = 0 ORDER BY product_id ASC LIMIT {$limit}";
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
        $discountIds = $this->duplicateFieldValueFromList($products['object'], 'product_discount_id');
        $taxIds = $this->duplicateFieldValueFromList($products['object'], 'product_tax_id');
        $product_id_con = $this->arrayToInCondition($productIds);
        $discount_id_con = $this->arrayToInCondition($discountIds);
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'vm_product_children' => "SELECT * FROM _DBPRF_vm_product WHERE product_parent_id IN {$product_id_con}",
            'vm_product_category_xref' => "SELECT * FROM _DBPRF_vm_product_category_xref WHERE product_id IN {$product_id_con}",
            'vm_product_mf_xref' => "SELECT * FROM _DBPRF_vm_product_mf_xref WHERE product_id IN {$product_id_con}",
            'vm_product_attribute_sku' => "SELECT * FROM _DBPRF_vm_product_attribute_sku WHERE product_id IN {$product_id_con}",
            'vm_product_price' => "SELECT * FROM _DBPRF_vm_product_price WHERE product_id IN {$product_id_con} ORDER BY product_price_id ASC",
            'vm_product_discount' => "SELECT * FROM _DBPRF_vm_product_discount WHERE discount_id IN {$discount_id_con}",
            'vm_tax_rate' => "SELECT * FROM _DBPRF_vm_tax_rate WHERE tax_rate_id IN {$tax_id_con}",
            'vm_product_files' => "SELECT * FROM _DBPRF_vm_product_files WHERE file_product_id IN {$product_id_con} AND file_extension IN ('jpg', 'png', 'gif', 'jpeg', 'ico')"
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
        $proChildIds = $this->duplicateFieldValueFromList($productsExt['object']['vm_product_children'], 'product_id');
        $manIds = $this->duplicateFieldValueFromList($productsExt['object']['vm_product_mf_xref'], 'manufacturer_id');
        $discountIds = $this->duplicateFieldValueFromList($productsExt['object']['vm_product_children'], 'product_discount_id');
        $taxIds = $this->duplicateFieldValueFromList($productsExt['object']['vm_product_children'], 'product_tax_id');
        $pro_child_id_con = $this->arrayToInCondition($proChildIds);
        $man_id_con = $this->arrayToInCondition($manIds);
        $discount_id_con = $this->arrayToInCondition($discountIds);
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_rel_query = array(
            'vm_product_attribute' => "SELECT * FROM _DBPRF_vm_product_attribute WHERE product_id IN {$pro_child_id_con}",
            'vm_product_mf_xref_children' => "SELECT * FROM _DBPRF_vm_product_mf_xref WHERE product_id IN {$pro_child_id_con}",
            'vm_product_price_children' => "SELECT * FROM _DBPRF_vm_product_price WHERE product_id IN {$pro_child_id_con} ORDER BY product_price_id ASC",
            'vm_product_discount_children' => "SELECT * FROM _DBPRF_vm_product_discount WHERE discount_id IN {$discount_id_con}",
            'vm_tax_rate_children' => "SELECT * FROM _DBPRF_vm_tax_rate WHERE tax_rate_id IN {$tax_id_con}"
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
        if(LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $proChild = $this->getListFromListByField($productsExt['object']['vm_product_children'], 'product_parent_id', $product['product_id']);
        $pro_data = array();
        if($proChild){
            $config_data = $this->_importChildrenProduct($product, $productsExt);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $pro_data = array_merge($pro_data, $config_data['data']);
        } else {
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
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
        if($product['attribute'] || $product['custom_attribute']){
            $options = array();
            if($product['attribute']){
                $attributes = @explode(';', $product['attribute']);
                foreach($attributes as $attribute){
                    $opts = @explode(',', $attribute);
                    if($opts){
                        $option = array(
                            'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT,
                            'type' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
                            'is_require' => 1,
                            'title' => $opts[0],
                        );
                        $values = array();
                        foreach($opts as $key => $valSrc){
                            if($key != 0){
                                preg_match('/(.*)[[](.*)[]]/', $valSrc, $val_src);
                                if(isset($val_src[1]) && isset($val_src[2])){
                                    $value = array(
                                        'option_type_id' => -1,
                                        'title' => $val_src[1],
                                        'price' => $val_src[2],
                                        'price_type' => 'fixed',
                                    );
                                    $values[] = $value;
                                }elseif($valSrc){
                                    $value = array(
                                        'option_type_id' => -1,
                                        'title' => $valSrc,
                                        'price' => 0,
                                        'price_type' => 'fixed',
                                    );
                                    $values[] = $value;
                                }
                            }
                        }
                        if($values){
                            $option['values'] = $values;
                            $options[] = $option;
                        }
                    }
                }
            }
            if($product['custom_attribute']){
                $attributes = @explode(';', $product['custom_attribute']);
                foreach($attributes as $attribute){
                    $option = array(
                        'previous_group' => Mage_Catalog_Model_Product_Option::OPTION_GROUP_TEXT,
                        'type' => Mage_Catalog_Model_Product_Option::OPTION_TYPE_FIELD,
                        'is_require' => 1,
                        'title' => $attribute,
                        'price' => 0,
                        'price_type' => 'fixed',
                    );
                    $options[] = $option;
                }
            }
            if($options){
                $this->importProductOption($product_mage_id, $options);
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
        $user_type = "('Public Front-end','Registered','Author','Editor','Publisher')";
        $query = "SELECT * FROM _DBPRF_users WHERE id > {$id_src} AND usertype IN {$user_type} ORDER BY id ASC LIMIT {$limit}";
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
            'vm_user_info' => "SELECT * FROM _DBPRF_vm_user_info WHERE user_id IN {$customer_id_con}",
            'vm_shopper_vendor_xref' => "SELECT * FROM _DBPRF_vm_shopper_vendor_xref WHERE user_id IN {$customer_id_con}"
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
        $countryCodes = $this->duplicateFieldValueFromList($customersExt['object']['vm_user_info'], 'country');
        $stateCodes = $this->duplicateFieldValueFromList($customersExt['object']['vm_user_info'], 'state');
        $country_code_con = $this->arrayToInCondition($countryCodes);
        $state_code_con = $this->arrayToInCondition($stateCodes);
        $ext_rel_query = array(
            'vm_country' => "SELECT * FROM _DBPRF_vm_country WHERE country_3_code IN {$country_code_con}",
            'vm_state' => "SELECT * FROM _DBPRF_vm_state WHERE state_2_code IN {$state_code_con}"
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
        $cusInfo = $this->getListFromListByField($customersExt['object']['vm_user_info'], 'user_id', $customer['id']);
        $cus_info = $this->getRowFromListByField($cusInfo, 'address_type', 'BT');
        $shopper_group = $this->getRowValueFromListByField($customersExt['object']['vm_shopper_vendor_xref'], 'user_id', $customer['id'], 'shopper_group_id');
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = ($cus_info) ? $cus_info['first_name'] : " ";
        $cus_data['middlename'] = ($cus_info)? $cus_info['middle_name'] : " ";
        $cus_data['lastname'] = ($cus_info)? $cus_info['last_name'] : " ";
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
        $cusInfo = $this->getListFromListByField($customersExt['object']['vm_user_info'], 'user_id', $customer['id']);
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
                } catch (Exception $e){}
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
        $query = "SELECT * FROM _DBPRF_vm_orders WHERE order_id > {$id_src} ORDER BY order_id ASC LIMIT {$limit}";
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
        $order_id_con = $this->arrayToInCondition($orderIds);
        $ext_query = array(
            'vm_order_history' => "SELECT * FROM _DBPRF_vm_order_history WHERE order_id IN {$order_id_con} ORDER BY order_status_history_id ASC",
            'vm_order_item' => "SELECT * FROM _DBPRF_vm_order_item WHERE order_id IN {$order_id_con}",
            'vm_order_payment' => "SELECT * FROM _DBPRF_vm_order_payment WHERE order_id IN {$order_id_con}",
            'vm_order_user_info' => "SELECT * FROM _DBPRF_vm_order_user_info WHERE order_id IN {$order_id_con}"
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
        $paymentIds = $this->duplicateFieldValueFromList($ordersExt['object']['vm_order_payment'], 'payment_method_id');
        $countryCodes = $this->duplicateFieldValueFromList($ordersExt['object']['vm_order_user_info'], 'country');
        $stateCodes = $this->duplicateFieldValueFromList($ordersExt['object']['vm_order_user_info'], 'state');
        $payment_id_con = $this->arrayToInCondition($paymentIds);
        $country_code_con = $this->arrayToInCondition($countryCodes);
        $state_code_con = $this->arrayToInCondition($stateCodes);
        $ext_rel_query = array(
            'vm_payment_method' => "SELECT * FROM _DBPRF_vm_payment_method WHERE payment_method_id IN {$payment_id_con}",
            'vm_country' => "SELECT * FROM _DBPRF_vm_country WHERE country_3_code IN {$country_code_con}",
            'vm_state' => "SELECT * FROM _DBPRF_vm_state WHERE state_2_code IN {$state_code_con}"
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
        if(LitExtension_CartMigration_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $cusInfo = $this->getListFromListByField($ordersExt['object']['vm_order_user_info'], 'order_id', $order['order_id']);
        $userBilling = $this->getRowFromListByField($cusInfo, 'address_type', 'BT');
        $userShipping = $this->getRowFromListByField($cusInfo, 'address_type', 'ST');
        if(!$userShipping){
            $userShipping = $userBilling;
        }
        $address_billing = $this->_convertAddress($userBilling, $ordersExt);
        $address_shipping = $this->_convertAddress($userShipping, $ordersExt);
        $orderPro = $this->getListFromListByField($ordersExt['object']['vm_order_item'], 'order_id', $order['order_id']);
        $carts = array();
        if($orderPro){
            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getMageIdProduct($order_pro['product_id']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['name'] = $order_pro['order_item_name'];
                $cart['sku'] = $order_pro['order_item_sku'];
                $cart['price'] = $order_pro['product_final_price'];
                $cart['original_price'] = $order_pro['product_final_price'];
                $cart['qty_ordered'] = $order_pro['product_quantity'];
                $cart['row_total'] = $order_pro['product_final_price'] * $order_pro['product_quantity'];
                if($order_pro['product_attribute']){
                    $options = array();
                    $listOpt = explode(';', $order_pro['product_attribute']);
                    foreach($listOpt as $item){
                        if(!$item){
                            continue ;
                        }
                        $split = explode(':', $item);
                        $option = array(
                            'label' => $split[0],
                            'value' => isset($split[1])? $split[1] : " ",
                            'print_value' => isset($split[1])? $split[1] : " ",
                            'option_id' => 'option_pro',
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $options[] = $option;
                    }
                    $cart['product_options'] = serialize(array('options' => $options));
                }
                $carts[] = $cart;
            }
        }
        $customer_id = $this->getMageIdCustomer($order['user_id']);
        $orderStatus = $this->getListFromListByField($ordersExt['object']['vm_order_history'], 'order_id', $order['order_id']);
        $order_status = $orderStatus[0];
        $order_status_id = $order_status['order_status_code'];
        $shipping_amount = $order['order_shipping']+ $order['order_shipping_tax'];
        $store_id = $this->_notice['config']['languages'][1];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $shipping = explode('|', $order['ship_method_id']);
        if($shipping){
            $shipping_desc = $shipping[1].'('.$shipping[2].')';
        } else {
            $shipping_desc = "Flat Rate";
        }
        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $userBilling['user_email'];
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
        $order_data['shipping_description'] = $shipping_desc;
        $order_data['tax_amount'] = $order['order_tax'];
        $order_data['base_tax_amount'] = $order['order_tax'];
        $order_data['discount_amount'] = $order['coupon_discount']  + $order['order_discount'];
        $order_data['base_discount_amount'] = $order['coupon_discount']  + $order['order_discount'];
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
        $order_data['created_at'] = date("Y-m-d H:i:s", $order['cdate']);

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['order_id'];
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
        $orderStatus = $this->getListFromListByField($ordersExt['object']['vm_order_history'], 'order_id', $order['order_id']);
        foreach($orderStatus as $key => $order_status){
            $order_status_data = array();
            $order_status_id = $order_status['order_status_code'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if($order_status_data['status']){
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if($key == 0){
                $shipping = explode('|', $order['ship_method_id']);
                if($shipping){
                    $shipping_desc = $shipping[1].'('.$shipping[2].')';
                } else {
                    $shipping_desc = "Flat Rate";
                }
                $payment_id = $this->getRowValueFromListByField($ordersExt['object']['vm_order_payment'], 'order_id', $order['order_id'], 'payment_method_id');
                $payment = false;
                if($payment_id){
                    $payment = $this->getRowFromListByField($ordersExt['object']['vm_payment_method'], 'payment_method_id', $payment_id);
                }
                $order_status_data['comment'] = "<b>Reference order #".$order['order_id']."</b><br />";
                if($payment){
                    $order_status_data['comment'] .= "<b>Payment method: </b>".$payment['payment_method_name']."<br />";
                }
                if($order['customer_note']){
                    $order_status_data['comment'] .= "<b>Shopper's note: </b>" . $order['customer_note'] ."<br />";
                }
                $order_status_data['comment'] .= "<b>Shipping method: </b> ".$shipping_desc."<br /><br />" . $order_status['comments'];
            } else {
                $order_status_data['comment'] = $order_status['comments'];
            }
            $order_status_data['is_customer_notified'] = $order_status['customer_notified'];
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
        $query = "SELECT * FROM _DBPRF_vm_product_reviews WHERE review_id > {$id_src} ORDER BY review_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews){
        $userIds = $this->duplicateFieldValueFromList($reviews['object'], 'userid');
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
        $product_mage_id = $this->getMageIdProduct($review['product_id']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['review_id']} import failed. Error: Product Id = {$review['product_id']} not imported!")
            );
        }
        $customer_id  = $this->getMageIdCustomer($review['userid']);
        $user = $this->getRowFromListByField($reviewsExt['object']['users'], 'id', $review['userid']);
        $store_id = $this->_notice['config']['languages'][1];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['published'] == 'N')? 3 : 1;
        $data['title'] = " ";
        $data['detail'] = $review['comment'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($customer_id)? $customer_id : null;
        $data['nickname'] = $user ? $user['name'] : " ";
        $data['rating'] = (int) $review['user_rating'];
        $data['created_at'] = date("Y-m-d H:i:s", $review['time']);
        $data['review_id_import'] = $review['review_id'];
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
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_vm_category WHERE category_id = {$parent_id}"
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
     * Import children product with attributes and create data for magento configurable product
     */
    protected function _importChildrenProduct($parent, $productsExt){
        $proChild = $this->getListFromListByField($productsExt['object']['vm_product_children'], 'product_parent_id', $parent['product_id']);
        $attrSrc = $this->getListFromListByField($productsExt['object']['vm_product_attribute_sku'], 'product_id', $parent['product_id']);
        $proChildIds = $this->duplicateFieldValueFromList($proChild, 'product_id');
        $attrOptSrc = $this->getListFromListByListField($productsExt['object']['vm_product_attribute'], 'product_id', $proChildIds);
        if(!$attrSrc || !$attrOptSrc){
            return array(
                'result' => "success",
                'data' => array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
                )
            );
        }
        $store_id = $this->_notice['config']['languages'][1];
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attrImports = $configurable_products_data = array();
        foreach($attrSrc as $attr_src){
            $attrOpt = $this->getListFromListByField($attrOptSrc, 'attribute_name', $attr_src['attribute_name']);
            if(!$attrOpt){
                continue ;
            }
            $attr_code = $this->joinTextToKey($attr_src['attribute_name'], 27, '_');
            if(!$attr_code){
                continue ;
            }
            $attr_data = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' => $attr_code,
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => 'select',
                'frontend_label' => array(
                    0 => $attr_src['attribute_name'],
                    $store_id => $attr_src['attribute_name']
                ),
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,

            );
            $attr_data_edit = array(
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            );
            $value = array();
            foreach($attrOpt as $attr_opt){
                if($attr_opt['attribute_value'] != ''){
                    $key = 'option_'.$attr_opt['attribute_id'];
                    $value[$key] = array(
                        0 => $attr_opt['attribute_value'],
                        $store_id => $attr_opt['attribute_value']
                    );
                }
            }
            if(!$value){
                continue ;
            }
            $attr_data['option']['value'] = $value;
            $attr_ipt = $this->_process->attribute($attr_data, $attr_data_edit);
            if(!$attr_ipt){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent['product_id']} import failed. Error: Product attribute could not be created!")
                );
            }
            $attrImports[$attr_src['attribute_name']] = $attr_ipt;
        }
        if(!$attrImports){
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Product Id = {$parent['product_id']} import failed. Error: Product attribute could not be created!")
            );
        }
        $configurable_products_data = $configurable_attributes_data = array();
        foreach($proChild as $pro_child){
            $pro_child_data = array(
                'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
            );
            $pro_child_convert = $this->_convertProduct($pro_child, $productsExt);
            if($pro_child_convert['result'] != 'success'){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent['product_id']} import failed. Error: Product children could not create!(Error code: Product children data not found.)")
                );
            }
            $pro_child_data = array_merge($pro_child_convert['data'], $pro_child_data);
            $pro_child_ipt = $this->_process->product($pro_child_data);
            if($pro_child_ipt['result'] != 'success'){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent['product_id']} import failed. Error: Product children could not create!(Error code: " . $pro_child_ipt['msg'] . ". )")
                );
            }
            $this->productSuccess($pro_child['product_id'], $pro_child_ipt['mage_id']);
            $proChildOpt = $this->getListFromListByField($productsExt['object']['vm_product_attribute'], 'product_id', $pro_child['product_id']);
            $configPro = array();
            foreach($proChildOpt as $pro_child_opt){
                $attr_name = $pro_child_opt['attribute_name'];
                if(!isset($attrImports[$attr_name])){
                    continue ;
                }
                $attr_ipt_data = $attrImports[$attr_name];
                $attr_id = $attr_ipt_data['attribute_id'];
                $key = "option_" . $pro_child_opt['attribute_id'];
                if($pro_child_opt['attribute_value'] != ''){
                    $opt_id = $attr_ipt_data['option_ids'][$key];
                    $config_pro = array(
                        'label' => $pro_child_opt['attribute_value'],
                        'attribute_id' => $attr_id,
                        'value_index' => $opt_id,
                        'is_percent' => 0,
                        'pricing_value' => '',
                    );
                    $configPro[] = $config_pro;
                    $this->setProAttrSelect($entity_type_id, $attr_id, $pro_child_ipt['mage_id'], $opt_id);
                }
            }
            if(!$configPro){
                return array(
                    'result' => "warning",
                    'msg' => $this->consoleWarning("Product Id = {$parent['product_id']} import failed. Error: Product children could not create!(Error code: Product child attribute not found.)")
                );
            }
            $configurable_products_data[$pro_child_ipt['mage_id']] = $configPro;
        }
        foreach($attrSrc as $key => $attr_src){
            $attrOpt = $this->getListFromListByField($attrOptSrc, 'attribute_name', $attr_src['attribute_name']);
            $attribute_name = $attr_src['attribute_name'];
            if(!isset($attrImports[$attribute_name])){
                continue ;
            }
            $attr_ipt_data = $attrImports[$attribute_name];
            $config_data = array(
                'label' => $attribute_name,
                'use_default' => 1,
                'attribute_id' => $attr_ipt_data['attribute_id'],
                'attribute_code' => $attr_ipt_data['attribute_code'],
                'frontend_label' => $attribute_name,
                'store_label' => $attribute_name,
                'html_id' => 'configurable__attribute_'.$key,
            );
            $values = array();
            foreach($attrOpt as $attr_opt){
                if($attr_opt['attribute_value'] == ''){
                    continue ;
                }
                $key = "option_" . $attr_opt['attribute_id'];
                $value_index = $attr_ipt_data['option_ids'][$key];
                $value = array(
                    'attribute_id' => $attr_ipt_data['attribute_id'],
                    'is_percent' => 0,
                    'label' => $attr_opt['attribute_value'],
                    'value_index' => $value_index,
                    'pricing_value' => ''
                );
                $values[] = $value;
            }
            $config_data['values'] = $values;
            $configurable_attributes_data[] = $config_data;
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
    protected function _convertProduct($product, $productsExt){
        $pro_data = $category_ids = $catSrc = array();
        $discount_key = $tax_key = $price_key = $man_key = "";
        if($product['product_parent_id']){
            $discount_key = "vm_product_discount_children";
            $tax_key = "vm_tax_rate_children";
            $price_key = "vm_product_price_children";
            $man_key = "vm_product_mf_xref_children";
        } else {
            $catSrc = $this->getListFromListByField($productsExt['object']['vm_product_category_xref'], 'product_id', $product['product_id']);
            $discount_key = "vm_product_discount";
            $tax_key = "vm_tax_rate";
            $price_key = "vm_product_price";
            $man_key = "vm_product_mf_xref";
        }
        if($catSrc){
            foreach($catSrc as $cat_src){
                $cat_id = $this->getMageIdCategory($cat_src['category_id']);
                if($cat_id){}
                $category_ids[] = $cat_id;
            }
        }
        $price = $this->getRowFromListByField($productsExt['object'][$price_key], 'product_id', $product['product_id']);
        $product_price = $price ? $price['product_price'] : 0;
        $special_price = $product_price;
        $man = $this->getRowFromListByField($productsExt['object'][$man_key], 'product_id', $product['product_id']);
        $discount = $this->getRowFromListByField($productsExt['object'][$discount_key], 'discount_id', $product['product_discount_id']);
        $tax = $this->getRowFromListByField($productsExt['object'][$tax_key], 'tax_rate_id', $product['product_tax_id']);
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = $this->createProductSku($product['product_sku'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['product_name'];
        $pro_data['description'] = $this->changeImgSrcInText($product['product_desc'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['product_s_desc'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['weight'] = ($product['product_weight'])? $product['product_weight'] : 0;
        $pro_data['status'] = ($product['product_publish']== 'Y')? 1 : 2;
        $pro_data['price'] = $product_price;
        if($discount){
            if($discount['is_percent']){
                $special_price -= $product_price * $discount['amount'] / 100;
            } else {
                $special_price -= $discount['amount'];
            }
        }
        if($tax){
            $special_price += $product_price * $tax['tax_rate'];
        }
        if($special_price != $product_price){
            $pro_data['special_price'] = $special_price;
        }
        $tax_mage_id = $this->getMageIdTaxProduct($product['product_tax_id']);
        $pro_data['tax_class_id'] = $tax_mage_id ? $tax_mage_id : 0;
        if($man){
            $man_mage_id = $this->getMageIdManufacturer($man['manufacturer_id']);
            if($man_mage_id){
                $pro_data[self::MANUFACTURER_CODE] = $man_mage_id;
            }
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['product_in_stock'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['product_in_stock'] < 1)? 0 : 1,
            'qty' => ($product['product_in_stock']> 0)? $product['product_in_stock'] : 0,
        );
        if($product['product_full_image'] && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['product_full_image'], 'catalog/product', false, true)){
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
        }
        $proImages = $this->getListFromListByField($productsExt['object']['vm_product_files'], 'file_product_id', $product['product_id']);
        if($proImages) {
            foreach ($proImages as $image) {
                if($gallery_path = $this->downloadImage(rtrim($this->_cart_url, '/'), $image['file_name'], 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '') ;
                }
            }
        }
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
     * @param array $customersExt : Data country and state of user
     * @return array
     */
    protected function _convertAddress($cus_info, $customersExt){
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
        $country = $this->getRowFromListByField($customersExt['object']['vm_country'], 'country_3_code', $cus_info['country']);
        if($country){
            $address['country_id'] = $country['country_2_code'];
            if($cus_info['state'] != "-"){
                $states = $this->getListFromListByField($customersExt['object']['vm_state'], 'country_id', $country['country_id']);
                $state = $this->getRowFromListByField($states, 'state_2_code', $cus_info['state']);
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
        return $address;
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
        $query = "SELECT * FROM _DBPRF_vm_tax_rate ORDER BY tax_rate_id ASC";
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
        $query = "SELECT * FROM _DBPRF_vm_manufacturer ORDER BY manufacturer_id ASC";
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
        $query = "SELECT * FROM _DBPRF_vm_category ORDER BY category_id ASC";
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
        $query = "SELECT * FROM _DBPRF_vm_product WHERE product_parent_id = 0 ORDER BY product_id ASC";
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
        $user_type = "('Public Front-end','Registered','Author','Editor','Publisher')";
        $query = "SELECT * FROM _DBPRF_users WHERE usertype IN {$user_type} ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_vm_orders ORDER BY order_id ASC";
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
        $query = "SELECT * FROM _DBPRF_vm_product_reviews ORDER BY review_id ASC";
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