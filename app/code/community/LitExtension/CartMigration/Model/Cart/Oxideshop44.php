<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Oxideshop44 extends LitExtension_CartMigration_Model_Cart {

    public function __construct() {
        parent::__construct();
    }
    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_oxmanufacturers WHERE `OXID` > '{$this->_notice['manufacturers']['id_src']}'",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_oxcategories WHERE `OXID` > '{$this->_notice['categories']['id_src']}'",
                'products' => "SELECT COUNT(1) FROM _DBPRF_oxarticles WHERE OXPARENTID = '' AND `OXID` > '{$this->_notice['products']['id_src']}'",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_oxuser WHERE `OXID` > '{$this->_notice['customers']['id_src']}'", 
                'orders' => "SELECT COUNT(1) FROM _DBPRF_oxorder WHERE `OXID` > '{$this->_notice['orders']['id_src']}'",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_oxreviews WHERE `OXID` > '{$this->_notice['reviews']['id_src']}'"
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
                "languages" => "SELECT * FROM _DBPRF_oxshops WHERE OXACTIVE = '1'",
                "currencies" => "SELECT * FROM _DBPRF_oxshops WHERE OXACTIVE = '1'",
            ))
        ));
        if (!$default_cfg || $default_cfg['result'] != 'success') {
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if ($object && $object['languages'] && $object['currencies']) {
            $this->_notice['config']['default_lang'] = $object['languages'][0]['OXDEFLANGUAGE'] ? $object['languages'][0]['OXDEFLANGUAGE'] : 0;
            $this->_notice['config']['default_currency'] = $object['languages'][0]['OXDEFCURRENCY'] ? $object['languages'][0]['OXDEFCURRENCY'] : 'EUR';
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT DISTINCT OXCURRENCY FROM _DBPRF_oxorder",
                'oxgroups' => "SELECT * FROM _DBPRF_oxgroups WHERE OXACTIVE = 1",
                'order_status' => "SELECT DISTINCT `OXTRANSSTATUS` FROM _DBPRF_oxorder"
            ))
        ));
        if (!$data || $data['result'] != 'success') {
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data = array(
            0 => "Default Language"
        );
        
        foreach ($obj['currencies'] as $curr){
            $curr_id = $curr['OXCURRENCY'];
            $curr_name = $curr['OXCURRENCY'];
            $currency_data[$curr_id] = $curr_name;
        }
//        $languages = json_decode(json_encode($this->_notice['extend']['cookie_key'][1]), true);
//        foreach ($languages as $langs){
//            $lang_id = $langs['id'];
//            $lang_name = $langs['name'];
//            $language_data[$lang_id] = $lang_name;
//        }
//        $currencies = $this->_notice['extend']['cookie_key'][2];
//        foreach ($currencies as $currency_row) {
//            $currency_row = (array)$currency_row;
//            $currency_id = $currency_row['id'];
//            $currency_name = $currency_row['name'];
//            $currency_data[$currency_id] = $currency_name;
//        }
        foreach ($obj['order_status'] as $order_stt){
            $id = $order_stt['OXTRANSSTATUS'];
            $name = $order_stt['OXTRANSSTATUS'];
            $order_status_data[$id] = $name;
        }
        foreach ($obj['oxgroups'] as $user_group){
            $group_id = $user_group['OXID'];
            $group_name = $user_group['OXTITLE'];
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
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_oxmanufacturers WHERE `OXID` > '{$this->_notice['manufacturers']['id_src']}'",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_oxcategories WHERE `OXID` > '{$this->_notice['categories']['id_src']}'",
                'products' => "SELECT COUNT(1) FROM _DBPRF_oxarticles WHERE OXPARENTID = '' AND `OXID` > '{$this->_notice['products']['id_src']}'",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_oxuser WHERE `OXID` > '{$this->_notice['customers']['id_src']}'", 
                'orders' => "SELECT COUNT(1) FROM _DBPRF_oxorder WHERE `OXID` > '{$this->_notice['orders']['id_src']}'",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_oxreviews WHERE `OXID` > '{$this->_notice['reviews']['id_src']}'"
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
            'query' => "SELECT DISTINCT OXCURRENCY FROM _DBPRF_oxorder"
        ));
        if ($currencies && $currencies['result'] == 'success') {
            $data = array();
            foreach ($currencies['object'] as $currency) {
                $currency_id = $currency['OXCURRENCY'];
                $currency_value = $currency['OXCURRENCY'];
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
        return array();
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes) {
        return array();
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
        return false;
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
        return array(
            'result' => 'success',
            'data' => array()
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
        $query = "SELECT * FROM _DBPRF_oxmanufacturers WHERE `OXID` > '{$id_src}' ORDER BY `OXID` ASC LIMIT {$limit}";
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
        return $manufacturer['OXID'];
    }
    
    public function checkManufacturerImport($manufacturer, $manufacturersExt){
        $login_name = $manufacturer['OXID'];
        return $this->_getMageIdByValue($login_name, self::TYPE_MANUFACTURER);
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
            0 => $manufacturer['OXTITLE']
        );
        foreach ($this->_notice['config']['languages'] as $store_id) {
            $manufacturer['value']['option'][$store_id] = $manufacturer['OXTITLE'];
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
        $query = "SELECT * FROM _DBPRF_oxcategories WHERE `OXID` > '{$id_src}' ORDER BY `OXID` ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories) {
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'OXID');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'oxobject2seodata' => "SELECT * FROM _DBPRF_oxobject2seodata WHERE OXOBJECTID IN {$cat_id_con}"
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
        return $category['OXID'];
    }
    
    public function checkCategoryImport($category, $categoriesExt){
        $catId = $category['OXID'];
        return $this->_getMageIdByValue($catId, self::TYPE_CATEGORY);
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
        if ($category['OXPARENTID'] == 'oxrootid') {
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->_getMageIdByValue($category['OXPARENTID'], self::TYPE_CATEGORY);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($category['OXPARENTID']);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['OXID']} import failed. Error: Could not import parent category id = {$category['OXPARENTID']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_name = ($this->_notice['config']['default_lang'] == 0) ? $category['OXTITLE'] : $category['OXTITLE_' . $this->_notice['config']['default_lang']];
        $cat_des = ($this->_notice['config']['default_lang'] == 0) ? $category['OXDESC'] : $category['OXDESC_' . $this->_notice['config']['default_lang']];
        $catMeta = $this->getListFromListByField($categoriesExt['object']['oxobject2seodata'], 'OXOBJECTID', $category['OXID']);
        $cat_meta_key = $this->getRowValueFromListByField($catMeta, 'OXLANG', $this->_notice['config']['default_lang'], 'OXKEYWORDS');
        $cat_meta_des = $this->getRowValueFromListByField($catMeta, 'OXLANG', $this->_notice['config']['default_lang'], 'OXDESCRIPTION');
        $cat_data['name'] = $cat_name ? html_entity_decode($cat_name) : " ";
        $cat_data['description'] = html_entity_decode($cat_des);
        $cat_data['meta_keywords'] = $cat_meta_key;
        $cat_data['meta_description'] = $cat_meta_des;
        if ($category['OXTHUMB'] && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), '/0/' . $category['OXTHUMB'], 'catalog/category')) {
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['OXACTIVE'];
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach ($this->_notice['config']['languages_data'] as $lang_id => $store_id) {
            $store_data = array();
            if ($lang_id != $this->_notice['config']['default_lang']) {
                $store_data['store_id'] = $store_id;
                $cat_Name = ($lang_id == 0) ? $category['OXTITLE'] : $category['OXTITLE_' . $lang_id];
                $cat_Desc = ($lang_id == 0) ? $category['OXDESC'] : $category['OXDESC_' . $lang_id];
                $store_data['name'] = html_entity_decode($cat_Name);
                $store_data['description'] = html_entity_decode($cat_Desc);
                $store_data['meta_keywords'] = $this->getRowValueFromListByField($catMeta, 'OXLANG', $lang_id, 'OXKEYWORDS');
                $store_data['meta_description'] = $this->getRowValueFromListByField($catMeta, 'OXLANG', $lang_id, 'OXDESCRIPTION');
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
    
    public function importCategory($data, $category, $categoriesExt){
        if(LitExtension_CartMigration_Model_Custom::CATEGORY_IMPORT){
            return $this->_custom->importCategoryCustom($this, $data, $category, $categoriesExt);
        }
        $id_src = $this->getCategoryId($category, $categoriesExt);
        $categoryIpt = $this->_process->category($data);
        if($categoryIpt['result'] == 'success'){
            $id_desc = $categoryIpt['mage_id'];
            $this->categorySuccess(false, $id_desc, $id_src);
        } else {
            $categoryIpt['result'] = 'warning';
            $msg = "Category Id = {$id_src} import failed. Error: " . $categoryIpt['msg'];
            $categoryIpt['msg'] = $this->consoleWarning($msg);
        }
        return $categoryIpt;
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
        $query = "SELECT * FROM _DBPRF_oxarticles WHERE OXPARENTID = '' AND `OXID` > '{$id_src}' ORDER BY `OXID` ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products) {
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'OXID');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'oxobject2category' => "SELECT * FROM _DBPRF_oxobject2category WHERE OXOBJECTID IN {$pro_ids_query}",
            'oxobject2attribute' => "SELECT * FROM _DBPRF_oxobject2attribute WHERE OXOBJECTID IN {$pro_ids_query}",
            'oxobject2seodata' => "SELECT * FROM _DBPRF_oxobject2seodata WHERE OXOBJECTID IN {$pro_ids_query}",
            'oxobject2article' => "SELECT * FROM _DBPRF_oxobject2article WHERE OXOBJECTID IN {$pro_ids_query} OR OXARTICLENID IN {$pro_ids_query}",
            'oxobject2selectlist' => "SELECT * FROM _DBPRF_oxobject2selectlist WHERE OXOBJECTID IN {$pro_ids_query}",
            'oxprice2article' => "SELECT * FROM _DBPRF_oxprice2article WHERE OXARTID IN {$pro_ids_query}",
            'oxarticles' => "SELECT * FROM _DBPRF_oxarticles WHERE OXID IN {$pro_ids_query}",
            'oxartextends' => "SELECT * FROM _DBPRF_oxartextends WHERE OXID IN {$pro_ids_query}",
            'variants' => "SELECT * FROM _DBPRF_oxarticles WHERE OXPARENTID IN {$pro_ids_query}",
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
        $attributeIds = $this->duplicateFieldValueFromList($productsExt['object']['oxobject2attribute'], 'OXATTRID');
        $attribute_id_query = $this->arrayToInCondition($attributeIds);
        $selectlistIds = $this->duplicateFieldValueFromList($productsExt['object']['oxobject2selectlist'], 'OXSELNID');
        $select_list_id_query = $this->arrayToInCondition($selectlistIds);
        $ext_rel_query = array(
            'oxattribute' => "SELECT * FROM _DBPRF_oxattribute WHERE OXID IN {$attribute_id_query}",
            'oxselectlist' => "SELECT * FROM _DBPRF_oxselectlist WHERE OXID IN {$select_list_id_query}"
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
        return $product['OXID'];
    }
    
    public function checkProductImport($product, $productsExt){
        $login_name = $product['OXID'];
        return $this->_getMageIdByValue($login_name, self::TYPE_PRODUCT);
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return array
     */
    
    public function convertProduct($product, $productsExt){
        if (LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $proVariants = $this->getListFromListByField($productsExt['object']['variants'], 'OXPARENTID', $product['OXID']);
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
    //=========================================================================//
    
    protected function _importChildrenProduct($product, $productsExt, $proVariants){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = $attrMage = array();
        if($proVariants){
            foreach($proVariants as $pro_variant){
                $option_collection = '';
                $dataAttrVariants = $dataOpts = array();
                $infoAttr = explode(' | ', $product['OXVARNAME']);
                $infoAttrValue = explode(' | ', $pro_variant['OXVARSELECT']);
                if($infoAttr){
                    foreach($infoAttr as $k => $attr_name){
                        $tmp['attribute_name'] = $attr_name;
                        $tmp['attribute_option_value'] = $infoAttrValue[$k];
                        $dataAttrVariants[] = $tmp;
                    }
                }
                if($dataAttrVariants){
                    foreach($dataAttrVariants as $data_attr_variant){
                        $attribute_code = $this->joinTextToKey($data_attr_variant['attribute_name'], 27, '_');
                        $attr_opt_val = $data_attr_variant['attribute_option_value'];
                        $opt_attr_data = array(
                            'entity_type_id'                => $entity_type_id,
                            'attribute_set_id'              => $this->_notice['config']['attribute_set_id'],
                            'attribute_code'                => $attribute_code,
                            'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                            'frontend_input'                => 'select',
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
                                'msg' => $this->consoleWarning("Product Id = {$product['OXID']} import failed. Error: Product attribute could not create!")
                            );
                        }
                        $dataTMP = array(
                            'attribute_id' => $optAttrDataImport['attribute_id'],
                            'value_index' => $optAttrDataImport['option_ids']['option_0'],
                            'is_percent' => 0,
                        );
                        $dataOpts[] = $dataTMP;
                        if ($data_attr_variant['attribute_option_value']){
                            $option_collection = $option_collection . ' - ' . $data_attr_variant['attribute_option_value'];
                        }
                        $attrMage[$optAttrDataImport['attribute_id']]['attribute_label'] = $data_attr_variant['attribute_name'];
                        $attrMage[$optAttrDataImport['attribute_id']]['attribute_code'] = $optAttrDataImport['attribute_code'];
                        $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['label'] = $data_attr_variant['attribute_option_value'];
                        $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['value_index'] = $optAttrDataImport['option_ids']['option_0'];
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
                            'msg' => $this->consoleWarning("Product Id = {$product['OXID']} import failed. Error: Product children could not create!")
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
    
    ///////////////////////////
    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array()){
        $pro_data = $categories = array();
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $proCat = $this->getListFromListByField($productsExt['object']['oxobject2category'], 'OXOBJECTID', $product['OXID']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->_getMageIdByValue($pro_cat['OXCATNID'], self::TYPE_CATEGORY);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        $title_prod = ($this->_notice['config']['default_lang'] == 0) ? $product['OXTITLE'] : $product['OXTITLE_' . $this->_notice['config']['default_lang']];
//        $lang_def = $this->_notice['config']['languages_data'][$this->_notice['config']['default_lang']];
        if($is_variation_pro){
            
            $pro_data['name'] = $title_prod . $data_variation['option_collection'];
            $pro_data['sku'] = $this->createProductSku($data_variation['object']['OXARTNUM'], $this->_notice['config']['languages']);
            $pro_data['price'] = $data_variation['object']['OXPRICE'] ? $data_variation['object']['OXPRICE'] : 0;
            $pro_data['status'] = ($data_variation['object']['OXACTIVE'] == 1)? 1 : 2;
            $pro_data['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['OXSTOCK'] < 1)? 0 : 1,
                'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['OXSTOCK'] < 1)? 0 : 1,
                'qty' => ($data_variation['object']['OXSTOCK'] >= 0 )? $data_variation['object']['OXSTOCK']: 0,
            );
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
        } else {
            $pro_data['name'] = $title_prod;
            $pro_data['sku'] = $this->createProductSku($product['OXARTNUM'], $this->_notice['config']['languages']);
            $pro_data['price'] = $product['OXPRICE'];
            $pro_data['status'] = ($product['OXACTIVE'] == 1)? 1 : 2;
            $pro_data['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['OXSTOCK'] < 1)? 0 : 1,
                'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['OXSTOCK'] < 1)? 0 : 1,
                'qty' => ($product['OXSTOCK'] >= 0) ? $product['OXSTOCK'] : 0,
            );
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            if ($product['OXPIC1'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), '/1/' . $product['OXPIC1'], 'catalog/product', false, true)) {
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => "");
            }
            for($i=2; $i<=12; $i++){
                if($product['OXPIC'.$i] != ''){
                    $gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), '/'.$i.'/' . $product['OXPIC'.$i], 'catalog/product', false, true);
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => "");
                }
            }
        }
        $proDescDef = $this->getRowFromListByField($productsExt['object']['oxartextends'], 'OXID', $product['OXID']);
        $prod_desc_def = ($this->_notice['config']['default_lang'] == 0) ? $proDescDef['OXLONGDESC'] : $proDescDef['OXLONGDESC_' . $this->_notice['config']['default_lang']];
        $prod_short_desc_def = ($this->_notice['config']['default_lang'] == 0) ? $product['OXSHORTDESC'] : $product['OXSHORTDESC_' . $this->_notice['config']['default_lang']];
        $pro_data['description'] = $this->changeImgSrcInText($prod_desc_def, $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($prod_short_desc_def, $this->_notice['config']['add_option']['img_des']);
        $pro_Meta = $this->getListFromListByField($productsExt['object']['oxobject2seodata'], 'OXOBJECTID', $product['OXID']);
        $proMeta = $this->getRowFromListByField($pro_Meta, 'OXLANG', $this->_notice['config']['default_lang']);
        $pro_data['meta_title'] = $product['OXTITLE'];
        $pro_data['meta_keyword'] = $proMeta['OXKEYWORDS'];
        $pro_data['meta_description'] = $proMeta['OXDESCRIPTION'];
        //
        $tierPrice = $this->getListFromListByField($productsExt['object']['oxprice2article'], 'OXARTID', $product['OXID']);
        if($tierPrice){
            foreach ($tierPrice as $tier_price){
                $tierprice = 0;
                if($tier_price['OXADDABS'] != 0){
                    $tierprice = $tier_price['OXADDABS'];
                }elseif($tier_price['OXADDPERC'] != 0){
                    $tierprice = $product['OXPRICE'] * $tier_price['OXADDPERC'] / 100;
                }
                $value = array(
                    'website_id' => 0,
                    'cust_group' => Mage_Customer_Model_Group::CUST_GROUP_ALL,
                    'price_qty' => $tier_price['OXAMOUNT'] . '-' .$tier_price['OXAMOUNTTO'],
                    'price' => $tierprice
                );
                $tier_prices[] = $value;
            }
            $pro_data['tier_price'] = $tier_prices;
        }
        //
        $pro_data['weight']   = $product['OXWEIGHT'] ? $product['OXWEIGHT']: 0;
        $pro_data['tax_class_id'] = 0;
        $pro_data['created_at'] = $product['OXINSERT'];
        if($manufacture_mage_id = $this->getMageIdManufacturer($product['OXMANUFACTURERID'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        //tags
        $productTags = $proDescDef['OXTAGS'];
        if ($productTags){
            $proTags = explode(',', $productTags);
            foreach ($proTags as $tag) {
                $pro_data['tags'][] = $tag;
            }
        }
        foreach($this->_notice['config']['languages_data'] as $lang_id => $store_id){
            if($lang_id != $this->_notice['config']['default_lang']){
                $store_data = array();
                $name_prod = ($lang_id == 0) ? $product['OXTITLE'] : $product['OXTITLE_' . $lang_id];
                if($is_variation_pro){
                    $store_data['name'] = $name_prod . $data_variation['option_collection'];
                }else{
                    $store_data['name'] = $name_prod;
                }
                $desc_prod_2 = ($lang_id == 0) ? $proDescDef['OXLONGDESC'] : $proDescDef['OXLONGDESC_' . $lang_id];
                $short_desc_prod_2 = ($lang_id == 0) ? $product['OXSHORTDESC'] : $product['OXSHORTDESC_' . $lang_id];
                $store_data['description'] = $this->changeImgSrcInText($desc_prod_2, $this->_notice['config']['add_option']['img_des']);
                $store_data['short_description'] = $this->changeImgSrcInText($short_desc_prod_2, $this->_notice['config']['add_option']['img_des']);
                $proMetaLang = $this->getRowFromListByField($pro_Meta, 'OXLANG', $lang_id);
                $store_data['meta_keyword'] = $proMetaLang['OXKEYWORDS'];
                $store_data['meta_description'] = $proMetaLang['OXDESCRIPTION'];
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
        }
        //$pro_data['multi_store'] = $multi_store;
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
        $lang_def = $this->_notice['config']['languages_data'][$this->_notice['config']['default_lang']];
        $attribute = $this->getListFromListByField($productsExt['object']['oxobject2attribute'], 'OXATTRID', $product['OXID']);
        if($attribute){
            $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
            $attribute_set_id = $this->_notice['config']['attribute_set_id'];
            $store_view = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE;
            foreach ($attribute as $row){
                $attr = $this->getRowFromListByField($product['object']['oxattribute'], 'OXID', $row['OXATTRID']);
                $attr_value = $row;
                $attr_import = $this->_makeAttributeImport($attr, $attr_value, $entity_type_id, $attribute_set_id, $store_view, $productsExt);
                $attr_after = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if(!$attr_after) return false;
                $this->setProAttrSelect($entity_type_id, $attr_after['attribute_id'], $product_mage_id, $attr_after['option_ids']['option_0']);
            }
        }
        //custom option
        $proAttr = $this->getListFromListByField($productsExt['object']['oxobject2selectlist'], 'OXOBJECTID', $product['OXID']);
        if ($proAttr) {
            $opt_data = array();
            $opt_data_store = array();
            foreach ($proAttr as $pro_attr) {
                $option_def = $this->getRowFromListByField($productsExt['object']['oxselectlist'], 'OXID', $pro_attr['OXSELNID']);
                $option = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType('drop_down'),
                    'type' => 'drop_down',
                    'is_require' => 0,
                    'title' => ($this->_notice['config']['default_lang'] == 0) ? $option_def['OXTITLE'] : $option_def['OXTITLE_' . $this->_notice['config']['default_lang']]
                );
                foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                    if ($lang_id == $this->_notice['config']['default_lang']) {
                        continue;
                    }
                    //$option_lang = $this->getRowFromListByField($productsExt['object']['oxselectlist_'.$lang_name], 'OXID', $pro_attr['OXSELNID']);
                    $option_store[$lang_id] = array(
                        'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType('drop_down'),
                        'type' => 'drop_down',
                        'is_require' => 0,
                        'title' => ($lang_id == 0) ? $option_def['OXTITLE'] : $option_def['OXTITLE_' . $lang_id]
                    );
                }
                $values = array();
                $value_stores = array();
                
                $proOptVals = ($this->_notice['config']['default_lang'] == 0) ? $option_def['OXVALDESC'] : $option_def['OXVALDESC_' . $this->_notice['config']['default_lang']];
                $proOptVal = explode("__@@", $proOptVals);
                if($proOptVal){
                    foreach ($proOptVal as $k => $pro_oppt){
                        if(!$pro_oppt){
                            continue;
                        }
                        $price = 0;
                        $value_def = explode("!P!", $pro_oppt);
                        if(isset($value_def[1])){
                            if(strpos($value_def[1], "%")){
                                $price_modif = trim($value_def[1], '%');
                                $price = $product['OXPRICE'] * $price_modif / 100;
                            }else{
                                $price = $value_def[1];
                            }
                        }
                        $value = array(
                            'option_type_id' => -1,
                            'title' => $value_def[0],
                            'price' => $price,
                            'sku' => "",
                            'sort_order' => "",
                            'price_type' => 'fixed',
                        );
                        $values[] = $value;
                        foreach ($this->_notice['config']['languages_data'] as $lang_id => $lang_name) {
                            if ($lang_id == $this->_notice['config']['default_lang']) {
                                continue;
                            }
                            //$option_lang_val = $this->getRowFromListByField($productsExt['object']['oxselectlist_'.$lang_name], 'OXID', $pro_attr['OXSELNID']);
                            $option_desc = ($lang_id == 0) ? $option_def['OXVALDESC'] : $option_def['OXVALDESC_' . $lang_id];
                            $optionAll = explode("__@@", $option_desc);
                            $optionVal = explode("!P!", $optionAll[$k]);
                            $price_ = 0;
                            if(strpos($optionVal[1], '%')){
                                $price_modif = trim($optionVal[1], '%');
                                $price_ = $product['OXPRICE'] * $price_modif / 100;
                            }else{
                                $price_ = $optionVal[1];
                            }
                            $value_store = array(
                                'option_type_id' => -1,
                                'title' => $optionVal[0],
                                'price' => $price_,
                                'sku' => "",
                                'sort_order' => "",
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
            if (count($this->_notice['config']['languages_data']) > 1) {
                foreach ($this->_notice['config']['languages'] as $key => $val) {
                    if($key == $this->_notice['config']['default_lang']) {continue;}
                    $this->_updateProductOptionStoreView($product_mage_id, $opt_data_store[$key], $opt_data, $val);
                }
            }
        }
        ////crosssell
        $productCross = $this->getListFromListByField($productsExt['object']['oxobject2article'], 'OXARTICLENID', $product['OXID']);
        if($productCross){
            $crossell_product = $this->duplicateFieldValueFromList($productCross, 'OXOBJECTID');
            $this->setProductRelation($product_mage_id, $crossell_product, 5);
        }
        $productR = $this->getListFromListByField($productsExt['object']['oxobject2article'], 'OXOBJECTID', $product['OXID']);
        if($productR){
            $crossell_product_r = $this->duplicateFieldValueFromList($productR, 'OXARTICLENID');
            $this->setProductRelation($crossell_product_r, $product_mage_id, 5);
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
        $query = "SELECT * FROM _DBPRF_oxuser WHERE `OXID` > '{$id_src}' ORDER BY `OXID` ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers) {
        $countryIds = $this->duplicateFieldValueFromList($customers['object'], 'OXCOUNTRYID');
        $country_id_con = $this->arrayToInCondition($countryIds);
        $stateIds = $this->duplicateFieldValueFromList($customers['object'], 'OXSTATEID');
        $state_id_query = $this->arrayToInCondition($stateIds);
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'OXID');
        $customer_id_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'country' => "SELECT * FROM _DBPRF_oxcountry WHERE OXID IN {$country_id_con}",
            'oxstates' => "SELECT * FROM _DBPRF_oxstates WHERE OXID IN {$state_id_query}",
            'oxnewssubscribed' => "SELECT * FROM _DBPRF_oxnewssubscribed WHERE OXUSERID IN {$customer_id_query}",
            'oxobject2group' => "SELECT * FROM _DBPRF_oxobject2group WHERE OXOBJECTID IN {$customer_id_query}",
            'all_address' => "SELECT * FROM _DBPRF_oxaddress WHERE OXUSERID IN {$customer_id_query}"
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
        $allCountryIds = $this->duplicateFieldValueFromList($customersExt['object']['all_address'], 'OXCOUNTRYID');
        $allStateIds = $this->duplicateFieldValueFromList($customersExt['object']['all_address'], 'OXSTATEID');
        $allCountryIds_con = $this->arrayToInCondition($allCountryIds);
        $allStateIds_con = $this->arrayToInCondition($allStateIds);
        $ext_rel_query = array(
            'all_country' => "SELECT * FROM _DBPRF_oxcountry WHERE OXID IN {$allCountryIds_con}",
            'all_state' => "SELECT * FROM _DBPRF_oxstates WHERE OXID IN {$allStateIds_con}"
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
        return $customer['OXID'];
    }

    public function checkCustomerImport($customer, $customersExt){
        $login_name = $customer['OXID'];
        return $this->_getMageIdByValue($login_name, self::TYPE_CUSTOMER);
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
            $cus_data['id'] = $customer['OXID'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['OXUSERNAME'];
        $cus_data['firstname'] = $customer['OXFNAME'];
        $cus_data['lastname'] = $customer['OXLNAME'];
        $cus_data['created_at'] = $customer['OXCREATE'];
        $cus_data['dob'] = $customer['OXBIRTHDATE'];
        $cus_data['taxvat'] = $customer['OXUSTID'];
        $oxnewssubscribed = $this->getRowFromListByField($customersExt['object']['oxnewssubscribed'], 'OXUSERID', $customer['OXID']);
        $cus_data['is_subscribed'] = ($oxnewssubscribed['OXDBOPTIN'] == 1) ? 1 : 0;
        $customerGroup = $this->getListFromListByField($customersExt['object']['oxobject2group'], 'OXOBJECTID', $customer['OXID']);
        if($customerGroup){
            foreach ($customerGroup as $group){
                if(isset($this->_notice['config']['customer_group'][$group['OXGROUPSID']])){
                    $cus_data['group_id'] = $this->_notice['config']['customer_group'][$group['OXGROUPSID']];
                }
                break;
            }
        }
        $gender = '';
        if($customer['OXSAL'] == 'MR'){
            $gender = 1;
        }elseif($customer['OXSAL'] == 'MRS'){
            $gender = 2;
        }
        $cus_data['gender'] = $gender;
        $custom = $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        if ($custom) {
            $cus_data = array_merge($cus_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $cus_data
        );
    }
    
//    public function importCustomer($data, $customer, $customersExt){
//        if(LitExtension_CartMigration_Model_Custom::CUSTOMER_IMPORT){
//            return $this->_custom->importCustomerCustom($this, $data, $customer, $customersExt);
//        }
//        $id_src = $this->getCustomerId($customer, $customersExt);
//        if(!isset($data['created_at']) || !$data['created_at']){
//            $data['created_mat'] = date("Y-m-d H:i:s");
//        }
//        $customerIpt = $this->_process->customer($data);
//        if($customerIpt['result'] == 'success'){
//            $id_desc = $customerIpt['mage_id'];
//            $this->customerSuccess(false, $id_desc, $id_src);
//        } else {
//            $customerIpt['result'] = 'warning';
//            $msg = "Customer Id = {$id_src} import failed. Error: " . $customerIpt['msg'];
//            $customerIpt['msg'] = $this->consoleWarning($msg);
//        }
//        return $customerIpt;
//    }

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
        $this->_importCustomerRawPass($customer_mage_id, $customer['OXPASSWORD'] . ":" . $customer['OXPASSSALT']);
        $this->customerSuccess(false, $customer_mage_id, $customer['OXID']);
        $address = array();
        $country_id = $this->getRowValueFromListByField($customersExt['object']['country'], 'OXID', $customer['OXCOUNTRYID'], 'OXISOALPHA2');
        $address['firstname'] = $customer['OXFNAME'];
        $address['lastname'] = $customer['OXLNAME'];
        $address['country_id'] = $country_id;
        $address['street'] = $customer['OXSTREETNR'] . "\n" . $customer['OXSTREET'];
        $address['postcode'] = $customer['OXZIP'];
        $address['city'] = $customer['OXCITY'];
        $address['telephone'] = $customer['OXFON'];
        $address['company'] = $customer['OXCOMPANY'];
        $address['fax'] = $customer['OXFAX'];
        if ($customer['OXSTATEID']) {
            $state = $this->getRowValueFromListByField($customersExt['object']['oxstates'], 'OXID', $customer['OXSTATEID'], 'OXTITLE');
            $region_id = $this->getRegionId($state, $country_id);
            if ($region_id) {
                $address['region_id'] = $region_id;
            }
        } else {
            $address['region'] = $customer['OXSTREET'];
        }
        $address_ipt = $this->_process->address($address, $customer_mage_id);
        if ($address_ipt['result'] == 'success') {
            try {
                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                $cus->setDefaultBilling($address_ipt['mage_id']);
                $cus->setDefaultShipping($address_ipt['mage_id']);
                $cus->save();
            } catch (Exception $e) {

            }
        }
        //
        $cusAdd = $this->getListFromListByField($customersExt['object']['all_address'], 'OXUSERID', $customer['OXID']);
        if ($cusAdd) {
            foreach ($cusAdd as $cus_add) {
                $address = array();
                $address['firstname'] = $cus_add['OXFNAME'];
                $address['lastname'] = $cus_add['OXLNAME'];
                $address['country_id'] = $this->getRowValueFromListByField($customersExt['object']['all_country'], 'OXID', $cus_add['OXCOUNTRYID'], 'OXISOALPHA2');
                $address['street'] = $cus_add['OXSTREET'] . "\n" . $cus_add['OXSTREETNR'];
                $address['postcode'] = $cus_add['OXZIP'];
                $address['city'] = $cus_add['OXCITY'];
                $address['telephone'] = $customer['OXFON'];
                $address['company'] = $cus_add['OXCOMPANY'];
                $address['fax'] = $customer['OXFAX'];
                if ($cus_add['OXSTATEID']) {
                    $region_id = $this->getRegionId($this->getRowValueFromListByField($customersExt['object']['all_state'], 'OXID', $cus_add['OXSTATEID'], 'OXTITLE'), $cus_add['iso_code_2']);
                    if($region_id) {
                        $address['region_id'] = $region_id;
                    }
                } else {
                    $address['region'] = $cus_add['OXSTREET'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
//                if ($address_ipt['result'] == 'success' && $cus_add['address_id'] == $customer['address_id']) {
//                    try {
//                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
//                        $cus->setDefaultBilling($address_ipt['mage_id']);
//                        $cus->setDefaultShipping($address_ipt['mage_id']);
//                        $cus->save();
//                    } catch (Exception $e) {
//                        
//                    }
//                }
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
        $query = "SELECT * FROM _DBPRF_oxorder WHERE `OXID` > '{$id_src}' ORDER BY `OXID` ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    protected function _getOrdersExtQuery($orders) {
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'OXID');
        $order_id_con = $this->arrayToInCondition($orderIds);
        $bilCountryIds = (array) $this->duplicateFieldValueFromList($orders['object'], 'OXBILLCOUNTRYID');
        $delCountryIds = (array) $this->duplicateFieldValueFromList($orders['object'], 'OXDELCOUNTRYID');
        $countryIds = array_unique(array_merge($bilCountryIds, $delCountryIds));
        $countryIds = $this->_flitArrayNum($countryIds);
        $country_id_con = $this->arrayToInCondition($countryIds);
        $bilStateIds = (array) $this->duplicateFieldValueFromList($orders['object'], 'OXBILLSTATEID');
        $delStateIds = (array) $this->duplicateFieldValueFromList($orders['object'], 'OXDELSTATEID');
        $stateIds = array_unique(array_merge($bilStateIds, $delStateIds));
        $stateIds = $this->_flitArrayNum($stateIds);
        $state_id_con = $this->arrayToInCondition($stateIds);
        $ext_query = array(
            'oxorderarticles' => "SELECT * FROM _DBPRF_oxorderarticles WHERE OXORDERID IN {$order_id_con}",
            'oxcountry' => "SELECT * FROM _DBPRF_oxcountry  WHERE OXID IN {$country_id_con}",
            'oxstates' => "SELECT * FROM _DBPRF_oxstates WHERE OXID IN {$state_id_con}"
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
    public function getOrderId($order, $ordersExt) {
        return $order['OXID'];
    }

    public function checkOrderImport($order, $ordersExt){
        $login_name = $order['OXID'];
        return $this->_getMageIdByValue($login_name, self::TYPE_ORDER);
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
        $address_billing['firstname'] = $order['OXBILLFNAME'];
        $address_billing['lastname'] = $order['OXBILLLNAME'];
        $address_billing['company'] = $order['OXBILLCOMPANY'];
        $address_billing['email'] = $order['OXBILLEMAIL'];
        $address_billing['street'] = $order['OXBILLSTREETNR'] . " " . $order['OXBILLSTREET'];
        $address_billing['city'] = $order['OXBILLCITY'];
        $address_billing['postcode'] = $order['OXBILLZIP'];
        $bil_country = $this->getRowValueFromListByField($ordersExt['object']['oxcountry'], 'OXID', $order['OXBILLCOUNTRYID'], 'OXISOALPHA2');
        $address_billing['country_id'] = $bil_country;
        if ($order['OXBILLSTATEID'] && $bil_state = $this->getRowValueFromListByField($ordersExt['object']['oxstates'], 'OXID', $order['OXBILLSTATEID'], 'OXTITLE')) {
            $billing_state = $bil_state;
        }else{
            $billing_state = $order['OXBILLSTATEID'];
        }
        $billing_region_id = $this->getRegionId($billing_state, $address_billing['country_id']);
        if ($billing_region_id) {
            $address_billing['region_id'] = $billing_region_id;
        } else {
            $address_billing['region'] = $billing_state;
        }
        $address_billing['telephone'] = $order['OXBILLFON'];
        
        $address_shipping['firstname'] = $order['OXDELFNAME'];
        $address_shipping['lastname'] = $order['OXDELLNAME'];
        $address_shipping['company'] = $order['OXDELCOMPANY'];
        $address_shipping['email'] = "";
        $address_shipping['street'] = $order['OXDELSTREETNR'] . " " . $order['OXDELSTREET'];
        $address_shipping['city'] = $order['OXDELCITY'];
        $address_shipping['postcode'] = $order['OXDELZIP'];
        $del_country = $this->getRowValueFromListByField($ordersExt['object']['oxcountry'], 'OXID', $order['OXDELCOUNTRYID'], 'OXISOALPHA2');
        $address_shipping['country_id'] = $del_country;
        if ($order['OXDELSTATEID']) {
            $shipping_state = $this->getRowValueFromListByField($ordersExt['object']['oxstates'], 'OXID', $order['OXDELSTATEID'], 'OXTITLE');
            if (!$shipping_state) {
                $shipping_state = $order['OXDELSTATEID'];
            }
        } else {
            $shipping_state = $order['OXDELSTATEID'];
        }
        $shipping_region_id = $this->getRegionId($shipping_state, $address_shipping['country_id']);
        if ($shipping_region_id) {
            $address_shipping['region_id'] = $shipping_region_id;
        } else {
            $address_shipping['region'] = $shipping_state;
        }
        $address_shipping['telephone'] = $order['OXDELFON'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['oxorderarticles'], 'OXORDERID', $order['OXID']);
        foreach ($orderPro as $order_pro) {
            $cart = array();
            $product_id = $this->getMageIdProduct($order_pro['OXARTID']);
            if ($product_id) {
                $cart['product_id'] = $product_id;
            }
            $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $cart['name'] = $order_pro['OXTITLE'];
            $cart['sku'] = "";
            $cart['price'] = $order_pro['OXBPRICE'];
            $cart['original_price'] = $order_pro['OXPRICE'];
            $cart['tax_amount'] = 0;
            $cart['tax_percent'] = '0';
            $cart['discount_amount'] = "";
            $cart['qty_ordered'] = $order_pro['OXAMOUNT'];
            $cart['row_total'] = $order_pro['OXBRUTPRICE'];
            if ($order_pro['OXSELVARIANT']) {
                //abcxyz
            }
            $carts[] = $cart;
        }

        $customer_id = $this->_getMageIdByValue($order['OXUSERID'], self::TYPE_CUSTOMER);
        $order_status_id = $order['OXTRANSSTATUS'];
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
        $order_data['customer_email'] = $order['OXBILLEMAIL'];
        $order_data['customer_firstname'] = $order['OXBILLFNAME'];
        $order_data['customer_lastname'] = $order['OXBILLLNAME'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['OXTOTALBRUTSUM']);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['OXDELCOST'];
        $order_data['base_shipping_amount'] = $order['OXDELCOST'];
        $order_data['base_shipping_invoiced'] = $order['OXDELCOST'];
        $order_data['shipping_description'] = $order['OXDELTYPE'];
        if ($order['OXARTVATPRICE1']) {
            $order_data['tax_amount'] = $order['OXARTVATPRICE1'];
            $order_data['base_tax_amount'] = $order['OXARTVATPRICE1'];
        }
        $order_data['discount_amount'] = $order['OXDISCOUNT'];
        $order_data['base_discount_amount'] = $order['OXDISCOUNT'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['OXTOTALORDERSUM']);
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
        $order_data['created_at'] = $order['OXORDERDATE'];

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
        $order_status_data = array();
        $order_status_id = $order['OXTRANSSTATUS'];
        $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
        if ($order_status_data['status']) {
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['comment'] = $order['OXREMARK'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = $order['OXORDERDATE'];
        $order_status_data['created_at'] = $order['OXORDERDATE'];
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }

    /**
     * Query for get main data use for import review
     *
     * @return string
     */
    protected function _getReviewsMainQuery() {
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $query = "SELECT * FROM _DBPRF_oxreviews WHERE `OXID` > '{$id_src}' ORDER BY `OXID` ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get relation data use for import reviews
     *
     * @param array $reviews : Data of connector return for query function getReviewsMainQuery
     * @return array
     */
    protected function _getReviewsExtQuery($reviews) {
        $userIds = $this->duplicateFieldValueFromList($reviews['object'], 'OXUSERID');
        $userIds_con = $this->arrayToInCondition($userIds);
        $ext_query = array(
            'user_review' => "SELECT * FROM _DBPRF_oxuser WHERE OXID IN {$userIds_con}"
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
        return $review['OXID'];
    }

    public function checkReviewImport($review, $reviewsExt){
        $login_name = $review['OXID'];
        return $this->_getMageIdByValue($login_name, self::TYPE_REVIEW);
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
        $product_mage_id = $this->getMageIdProduct($review['OXOBJECTID']);
        if (!$product_mage_id) {
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['OXID']} import failed. Error: Product Id = {$review['OXOBJECTID']} not imported!")
            );
        }
        $review_name = $this->getRowFromListByField($reviewsExt['object']['user_review'], 'OXID', $review['OXUSERID']);
        $store_id = $this->_notice['config']['languages_data'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = 1;
        $data['title'] = " ";
        $data['detail'] = $review['OXTEXT'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->_getMageIdByValue($review['OXUSERID'], self::TYPE_CUSTOMER)) ? $this->_getMageIdByValue($review['OXUSERID'], self::TYPE_CUSTOMER) : null;
        $data['nickname'] = $review_name['OXFNAME'] . ' ' . $review_name['OXLNAME'];
        $data['rating'] = $review['OXRATING'];
        $data['created_at'] = $review['OXCREATE'];
        $data['review_id_import'] = $review['OXID'];
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

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_oxcategories WHERE `OXID` = '{$parent_id}'"
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
        if ($category_ipt['result'] == 'success'){
            $this->categorySuccess(false, $category_ipt['mage_id'], $parent_id);
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

    protected function _getOptionTypeByTypeSrc($type_name) {
        $types = array(
            'select' => 'drop_down',
            'text' => 'field',
            'radio' => 'radio',
            'checkbox' => 'checkbox',
            'file' => 'file',
            'textarea' => 'area',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'date_time'
        );
        return isset($types[$type_name]) ? $types[$type_name] : false;
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
                        if($options[$key]['values']) {
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
                if ($row['code'] == $code) {
                    $result += $row['value'];
                }
            }
        }
        return $result;
    }
    
    protected function _makeAttributeImport($attribute, $option, $entity_type_id, $attribute_set_id, $store_view, $productsExt) {
        $attr_des = array();
        $attr_des[] = $attribute['OXTITLE'];
        $attr_name = $this->joinTextToKey($attr_des[0], 30, '_');
        $opt_des = array();
        $opt_des[] = $option['OXVALUE'];
        foreach ($this->_notice['config']['languages'] as $lang_id => $store_id) {
            if($lang_id == $this->_notice['config']['default_lang']) {continue;}
            $opt_des[$store_id] = $option['OXVALUE_' . $lang_id];// $this->getRowValueFromListByField($productsExt['object']['oxobject2attribute_'.$store_id], 'OXID', $option['OXID'], 'OXVALUE');
            $attr_des[$store_id] = $attribute['OXTITLE_' . $lang_id];//$this->getRowValueFromListByField($productsExt['object']['oxattribute_'.$store_id], 'OXID', $option['OXATTRID'], 'OXTITLE');
        }
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attr_name,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => 'select',
            'frontend_label' =>  $attr_des,
            'is_visible_on_front' => 1,
            'is_global' => $store_view,
            'is_configurable' => false,
            'option' => array(
                'value' => array('option_0' => $opt_des)
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
    
}
