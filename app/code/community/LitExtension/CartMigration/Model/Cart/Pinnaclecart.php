<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Pinnaclecart
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_classes WHERE class_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_catalog WHERE cid > {$this->_notice['categories']['id_src']} AND key_name != 'gift_cert'",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE pid > {$this->_notice['products']['id_src']}  AND product_id != 'gift_certificate'",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE uid > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE oid > {$this->_notice['orders']['id_src']} AND order_num != 0",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_products_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
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
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "languages" => "SELECT * FROM _DBPRF_languages",
                "currencies" => "SELECT * FROM _DBPRF_currencies",
                "orders" => "SELECT distinct(status) FROM _DBPRF_orders"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $default_lang = $this->getRowValueFromListByField($obj['languages'], 'is_default', 'Yes', 'language_id');
        $default_currency = $this->getRowValueFromListByField($obj['currencies'], 'is_default', 'Yes', 'currency_id');
        $this->_notice['config']['default_lang'] = $default_lang ? $default_lang : 1;
        $this->_notice['config']['default_currency'] = $default_currency ? $default_currency : 1;
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        foreach($obj['languages'] as $language_row){
            $lang_id = $language_row['language_id'];
            $lang_name = $language_row['name'] . "(" . $language_row['code'] . ")";
            $language_data[$lang_id] = $lang_name;
        }
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['currency_id'];
            $currency_name = $currency_row['title'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders'] as $order_status_row) {
            $order_status_id = $order_status_row['status'];
            $order_status_name = $order_status_row['status'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $this->_notice['config']['config_support']['customer_group_map'] = false;
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
    public function displayImport()
    {
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_tax_classes WHERE class_id > {$this->_notice['taxes']['id_src']}",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE manufacturer_id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_catalog WHERE cid > {$this->_notice['categories']['id_src']} AND key_name != 'gift_cert'",
                'products' => "SELECT COUNT(1) FROM _DBPRF_products WHERE pid > {$this->_notice['products']['id_src']} AND product_id != 'gift_certificate'",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE uid > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_orders WHERE oid > {$this->_notice['orders']['id_src']} AND order_num != 0",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_products_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
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
        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_currencies"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){
                $currency_id = $currency['currency_id'];
                $currency_value = $currency['exchange_rate'];
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
        $query = "SELECT * FROM _DBPRF_tax_classes WHERE class_id > {$id_src} ORDER BY class_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'class_id');
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'tax_rates' => "SELECT * FROM _DBPRF_tax_rates WHERE class_id IN {$tax_id_con}",
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
        $taxZoneIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rates'], 'zone_id');
        $tax_zone_query = $this->arrayToInCondition($taxZoneIds);
        $ext_rel_query = array(
            'tax_zones_regions' => "SELECT tzr.*, c.iso_a2, s.name, s.short_name FROM _DBPRF_tax_zones_regions AS tzr
                                      LEFT JOIN _DBPRF_countries AS c ON c.coid = tzr.coid
                                      LEFT JOIN _DBPRF_states AS s ON s.stid = s.stid AND s.coid = tzr.coid
                                      WHERE tzr.zone_id IN {$tax_zone_query}",
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
        return $tax['class_id'];
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
            'class_name' => $tax['class_name']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['class_id'], $tax_pro_ipt['mage_id']);
        }
        $taxRates = $this->getListFromListByField($taxesExt['object']['tax_rates'], 'class_id', $tax['class_id']);
        foreach($taxRates as $tax_rate){
            $taxZone = $this->getListFromListByField($taxesExt['object']['tax_zones_regions'], 'zone_id', $tax_rate['zone_id']);
            foreach($taxZone as $tax_zone){
                if($tax_zone['coid'] == 0){
                    $countries = Mage::getModel('directory/country')->getCollection();
                    if($countries) {
                        foreach ($countries as $row) {
                            $tax_rate_data = array();
                            $tax_rate_data['code'] = $this->createTaxRateCode($tax_rate['rate_description'] . "-" . $row->getCountryId());
                            $tax_rate_data['tax_country_id'] = $row->getCountryId();
                            $tax_rate_data['tax_region_id'] = 0;
                            $tax_rate_data['zip_is_range'] = 0;
                            $tax_rate_data['tax_postcode'] = "*";
                            $tax_rate_data['rate'] = $tax_rate['tax_rate'];
                            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                            if($tax_rate_ipt['result'] == 'success'){
                                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                            }
                        }
                    }
                }else{
                    $tax_rate_data['code'] = $this->createTaxRateCode($tax_rate['rate_description'] . "-" . $tax_zone['iso_a2'] . $tax_zone['name']);
                    $tax_rate_data['tax_country_id'] = $tax_zone['iso_a2'];
                    $tax_rate_data['tax_region_id'] = 0;
                    $tax_rate_data['zip_is_range'] = 0;
                    $tax_rate_data['tax_postcode'] = "*";
                    $tax_rate_data['rate'] = $tax_rate['tax_rate'];
                    $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                    if($tax_rate_ipt['result'] == 'success'){
                        $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                    }
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['class_name']);
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
        $query = "SELECT * FROM _DBPRF_manufacturers WHERE manufacturer_id > {$id_src} ORDER BY manufacturer_id ASC LIMIT {$limit}";
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
        $manufacturer_data = array(
            'attribute_id' => $man_attr_id
        );
        $manufacturer_data['value']['option'] = array(
            0 => $manufacturer['manufacturer_name']
        );
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $manufacturer['manufacturer_name'];
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
        $query = "SELECT * FROM _DBPRF_catalog WHERE cid > {$id_src} AND category_path IS NOT NULL ORDER BY cid ASC LIMIT {$limit}";
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
        return $category['cid'];
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
        if($category['parent'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['parent']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['parent']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['cid']} import failed. Error: Could not import parent category id = {$category['parent']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data['name'] = $category['name'];
        $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  'catalog/' . $category['cid'] . '.jpg', 'catalog/category');
        if(!$img_path){
            $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  'catalog/' . $category['cid'] . '.png', 'catalog/category');
        }
        if(!$img_path){
            $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  'catalog/' . $category['cid'] . '.gif', 'catalog/category');
        }
        if($img_path){
            $cat_data['image'] = $img_path;
        }
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['is_visible'] == "Yes") ? 1 : 0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $cat_data['description'] = $category['description'];
        $cat_data['meta_title'] = $category['meta_title'];
        $cat_data['meta_description'] = $category['meta_description'];
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
        $query = "SELECT * FROM _DBPRF_products WHERE pid > {$id_src} AND product_id != 'gift_certificate' ORDER BY pid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */
    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'pid');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'products_inventory' => "SELECT * FROM _DBPRF_products_inventory WHERE pid IN {$pro_ids_query}",
            'products_attributes' => "SELECT * FROM _DBPRF_products_attributes WHERE pid IN {$pro_ids_query}",
            'products_categories' => "SELECT * FROM _DBPRF_products_categories WHERE pid IN {$pro_ids_query}",
            'products_images' => "SELECT * FROM _DBPRF_products_images WHERE pid IN {$pro_ids_query}",
            'products_quantity_discounts' => "SELECT * FROM _DBPRF_products_quantity_discounts WHERE pid IN {$pro_ids_query}"
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
        return array();
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsExt
     * @return int
     */
    public function getProductId($product, $productsExt){
        return $product['pid'];
    }

    public function convertProduct($product, $productsExt){
        if (LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = array();
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        if($product['product_type'] == 'Virtual'){
            $type_id = Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL;
        }
        $proVariants = $this->getListFromListByField($productsExt['object']['products_inventory'], 'pid', $product['pid']);
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
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt)
    {
        if (parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)) {
            return;
        }
        if($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE){
            $proAttributes = $this->getListFromListByField($productsExt['object']['products_attributes'], 'pid', $product['pid']);
            if($proAttributes){
                $opt_data = array();
                foreach($proAttributes as $pro_attr){
                    if($pro_attr['attribute_type'] == 'text'){
                        $previous_group =  Mage_Catalog_Model_Product_Option::OPTION_GROUP_TEXT;
                        $type = Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN;
                    }elseif($pro_attr['attribute_type'] == 'radio'){
                        $previous_group =  Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT;
                        $type = Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO;
                    }else{
                        $previous_group =  Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT;
                        $type = Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN;
                    }
                    $option = array(
                        'previous_group' => $previous_group,
                        'type' => $type,
                        'is_require' => 1,
                        'title' => $pro_attr['caption']
                    );
                    $list_options = preg_split("/\r\n|\n|\r/", $pro_attr['options']);
                    if($list_options){
                        $values = array();
                        foreach($list_options as $options){
                            preg_match('/(?P<option_name>.*)(\()(?P<option_price>.*)(\))/', $options, $optionsParse);
                            if(!$optionsParse){
                                $optionsParse['option_name'] = $options;
                                $optionsParse['option_price'] = '';
                            }
                            $option_name = $optionsParse['option_name'];
                            $option_price = $optionsParse['option_price'];
                            $price_percent = $prefix_price = $price_number = 0;
                            if($option_price){
                                preg_match('/(?P<prefix>\D*)(?P<number>\d+(\.\d+)?)(?P<percent>%*)/', $option_price, $priceParse);
                                $prefix_price = $priceParse['prefix'];
                                $price_number = $priceParse['number'];
                                $price_percent = $priceParse['percent'];
                            }
                            if(!$option_name){
                                continue;
                            }
                            $value = array(
                                'option_type_id' => -1,
                                'title' => $option_name,
                                'price' => $prefix_price . $price_number,
                                'price_type' => $price_percent ? 'percent' : 'fixed',
                            );
                            $values[] = $value;
                        }
                        $option['values'] = $values;
                        $opt_data[] = $option;
                    }
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
    protected function _getCustomersMainQuery(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_users WHERE uid > {$id_src} ORDER BY uid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'uid');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'users_shipping' => "SELECT * FROM _DBPRF_users_shipping WHERE uid IN {$customer_ids_query}"
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
        $countryBillIds = $this->duplicateFieldValueFromList($customers['object'], 'country');
        $countryShipIds = $this->duplicateFieldValueFromList($customersExt['object']['users_shipping'], 'country');
        $country_ids_query = $this->arrayToInCondition(array_unique(array_merge($countryBillIds, $countryShipIds)));
        $statesBillIds = $this->duplicateFieldValueFromList($customers['object'], 'state');
        $statesShipIds = $this->duplicateFieldValueFromList($customersExt['object']['users_shipping'], 'state');
        $states_ids_query = $this->arrayToInCondition(array_unique(array_merge($statesBillIds, $statesShipIds)));
        $ext_rel_query = array(
            'countries' => "SELECT * FROM _DBPRF_countries WHERE coid IN {$country_ids_query}",
            'states' => "SELECT * FROM _DBPRF_states WHERE stid IN {$states_ids_query}"
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
        return $customer['uid'];
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
            $cus_data['id'] = $customer['uid'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['fname'];
        $cus_data['lastname'] = $customer['lname'];
        $cus_data['created_at'] = $customer['created_date'];
        $cus_data['group_id'] = 1;
        $cus_data['is_subscribed'] = ($customer['receives_marketing'] == 'Yes') ? true : false;
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
        //Billing
        $address = array();
        $address['firstname'] = $customer['fname'];
        $address['lastname'] = $customer['lname'];
        $country_code = $this->getRowValueFromListByField($customersExt['object']['countries'], 'coid', $customer['country'], 'iso_a2');
        $address['country_id'] = $country_code;
        $address['street'] = $customer['address1']."\n".$customer['address2'];
        $address['postcode'] = $customer['zip'];
        $address['city'] = $customer['city'];
        $address['company'] = $customer['company'];
        if($customer['state'] != 0){
            $state = $this->getRowFromListByField($customersExt['object']['states'], 'stid', $customer['state']);
            $region_id = $this->getRegionId($state['name'], $country_code);
            if($region_id){
                $address['region_id'] = $region_id;
            }
            $address['region'] = $customer['province'];
        } else {
            $address['region'] = $customer['province'];
        }
        $address['telephone'] = $customer['phone'];
        $address_ipt = $this->_process->address($address, $customer_mage_id);
        if($address_ipt['result'] == 'success'){
            try{
                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                $cus->setDefaultBilling($address_ipt['mage_id']);
                $cus->save();
            }catch (Exception $e){}
        }
        //Shipping
        $cusAdd = $this->getListFromListByField($customersExt['object']['users_shipping'], 'uid', $customer['uid']);
        if($cusAdd){
            foreach($cusAdd as $cus_add){
                $address = array();
                $customer_name = $this->getNameFromString($cus_add['name']);
                $address['firstname'] = $customer_name['firstname'];
                $address['lastname'] = $customer_name['lastname'];
                $country_code = $this->getRowValueFromListByField($customersExt['object']['countries'], 'coid', $cus_add['country'], 'iso_a2');
                $address['country_id'] = $country_code;
                $address['street'] = $cus_add['address1']."\n".$cus_add['address2'];
                $address['postcode'] = $cus_add['zip'];
                $address['city'] = $cus_add['city'];
                $address['company'] = $cus_add['company'];
                if($cus_add['state'] != 0){
                    $state = $this->getRowFromListByField($customersExt['object']['states'], 'stid', $cus_add['state']);
                    $region_id = $this->getRegionId($state['name'], $country_code);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['province'];
                } else {
                    $address['region'] = $cus_add['province'];
                }
                $address['telephone'] = $customer['phone'];
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success'){
                    try{
                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
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
        $query = "SELECT * FROM _DBPRF_orders WHERE oid > {$id_src} AND order_num != 0 ORDER BY oid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'oid');
        $order_ids_con = $this->arrayToInCondition($orderIds);
        $userIds = $this->duplicateFieldValueFromList($orders['object'], 'uid');
        $user_ids_con = $this->arrayToInCondition($userIds);
        $ext_query = array(
            'orders_content' => "SELECT * FROM _DBPRF_orders_content WHERE oid IN {$order_ids_con}",
            'users' => "SELECT * FROM _DBPRF_users WHERE uid IN {$user_ids_con}",
            'orders_shipments' => "SELECT * FROM _DBPRF_orders_shipments WHERE oid IN {$order_ids_con}",
            'admin_notes' => "SELECT * FROM _DBPRF_admin_notes WHERE oid IN {$order_ids_con}"
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
        $userCountryIds = $this->duplicateFieldValueFromList($ordersExt['object']['users'], 'country');
        $countryShipIds = $this->duplicateFieldValueFromList($orders['object'], 'shipping_country');
        $country_ids_con = $this->arrayToInCondition(array_unique(array_merge($userCountryIds, $countryShipIds)));
        $userStatesIds = $this->duplicateFieldValueFromList($ordersExt['object']['users'], 'state');
        $statesShipIds = $this->duplicateFieldValueFromList($orders['object'], 'shipping_state');
        $states_ids_con = $this->arrayToInCondition(array_unique(array_merge($userStatesIds, $statesShipIds)));
        $ext_rel_query = array(
            'countries' => "SELECT * FROM _DBPRF_countries WHERE coid IN {$country_ids_con}",
            'states' => "SELECT * FROM _DBPRF_states WHERE stid IN {$states_ids_con}"
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
        return $order['oid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return array
     */
    public function convertOrder($order, $ordersExt)
    {
        if (LitExtension_CartMigration_Model_Custom::ORDER_CONVERT) {
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();
        $user = $this->getRowFromListByField($ordersExt['object']['users'], 'uid', $order['uid']);
        $address_billing['firstname'] = $user['fname'];
        $address_billing['lastname'] = $user['lname'];
        $address_billing['company'] = $user['company'];
        $address_billing['email']   = $user['email'];
        $address_billing['street']  = $user['address1']."\n".$user['address2'];
        $address_billing['city'] = $user['city'];
        $address_billing['postcode'] = $user['zip'];
        $country = $this->getRowValueFromListByField($ordersExt['object']['countries'], 'coid', $user['country'], 'iso_a2');
        $address_billing['country_id'] = $country;
        $state = $this->getRowValueFromListByField($ordersExt['object']['states'], 'stid', $user['state'], 'name');
        $billing_region_id = $this->getRegionId($state, $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $user['province'];
        }
        $address_billing['telephone'] = $user['phone'];

        $customer_name = $this->getNameFromString($order['shipping_name']);
        $address_shipping['firstname'] = $customer_name['firstname'];
        $address_shipping['lastname'] = $customer_name['lastname'];
        $address_shipping['company'] = $order['shipping_company'];
        $address_shipping['email']   = $address_billing['email'];
        $address_shipping['street']  = $order['shipping_address1']."\n".$order['shipping_address2'];
        $address_shipping['city'] = $order['shipping_city'];
        $address_shipping['postcode'] = $order['shipping_zip'];
        $country = $this->getRowValueFromListByField($ordersExt['object']['countries'], 'coid', $order['shipping_country'], 'iso_a2');
        $address_shipping['country_id'] = $country;
        $state = $this->getRowValueFromListByField($ordersExt['object']['states'], 'stid', $order['shipping_state'], 'name');
        $shipping_region_id = $this->getRegionId($state, $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $order['shipping_province'];
        }
        $address_shipping['telephone'] = $address_billing['telephone'];

        $carts = array();
        $orderContent = $this->getListFromListByField($ordersExt['object']['orders_content'], 'oid', $order['oid']);
        if($orderContent){
            foreach($orderContent as $order_pro) {
                $cart = array();
                $product_id = $this->getMageIdProduct($order_pro['pid']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_pro['title'];
                $cart['sku'] = $order_pro['product_sku'];
                $cart['price'] = $order_pro['price'];
                $cart['original_price'] = $order_pro['price'];
                $cart['tax_amount'] = $order_pro['tax_amount'];
                $cart['tax_percent'] = $order_pro['tax_rate'];
                $cart['qty_ordered'] = $order_pro['quantity'];
                $cart['row_total'] = $order_pro['price_withtax'] * $order_pro['quantity'];
                $orderProOpts = preg_split("/\r\n|\n|\r/", $order_pro['options_clean']);
                $product_opt = array();
                if($orderProOpts){
                    foreach($orderProOpts as $order_pro_opt){
                        $attribute = explode(' : ', $order_pro_opt);
                        $attribute_name = isset($attribute[0]) ? $attribute[0] : '';
                        $option_name = isset($attribute[1]) ? $attribute[1] : '';
                        $option = array(
                            'label' => $attribute_name,
                            'value' => $option_name,
                            'print_value' => $option_name,
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

        $customer_id = $this->getMageIdCustomer($order['uid']);
        $order_data = array();
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $user['email'];
        $order_data['customer_firstname'] = $address_billing['firstname'];
        $order_data['customer_lastname'] = $address_billing['lastname'];
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status'] && isset($this->_notice['config']['order_status'][$order['status']])){
            $order_data['status'] = $this->_notice['config']['order_status'][$order['status']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $order['subtotal_amount'];
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['shipping_amount'];
        $order_data['base_shipping_amount'] =  $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] =  $order_data['shipping_amount'];
        $order_shipments = $this->getRowFromListByField($ordersExt['object']['orders_shipments'], 'oid', $order['oid']);
        $order_data['shipping_description'] = $order_shipments['shipping_cm_name'];
        $order_data['tax_amount'] = $order['tax_amount'];
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['discount_amount'] + $order['promo_discount_amount'];
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $order['total_amount'];
        $order_data['base_grand_total'] = $order['total_amount'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $store_currency['base'];
        $order_data['global_currency_code'] = $store_currency['base'];
        $order_data['store_currency_code'] = $store_currency['base'];
        $order_data['order_currency_code'] = $store_currency['base'];
        $order_data['created_at'] = $order['create_date'];

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['oid'];
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
        $orderStatus = $this->getListFromListByField($ordersExt['object']['admin_notes'], 'oid', $order['oid']);
        foreach($orderStatus as $key => $order_status){
            $order_status_data = array();
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order['status']];
            if($order_status_data['status']){
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            if($key == 0){
                $order_status_data['comment'] = "<b>Reference order #".$order['oid']."</b><br /><b>Payment method: </b>".$order['payment_method_name']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order_status['note_text'];
            } else {
                $order_status_data['comment'] = $order_status['comments'];
            }
            $order_status_data['is_customer_notified'] = 1;
            $order_status_data['updated_at'] = $order_status['created_at'];
            $order_status_data['created_at'] = $order_status['created_at'];
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
        $query = "SELECT * FROM _DBPRF_products_reviews WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
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
        $product_mage_id = $this->getMageIdProduct($review['pid']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['id']} import failed. Error: Product Id = {$review['pid']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        if($review['status'] == 'approved'){
            $data['status_id'] = 1;
        }
        if($review['status'] == 'pending'){
            $data['status_id'] = 2;
        }
        if($review['status'] == 'declined'){
            $data['status_id'] = 3;
        }
        $data['title'] = $review['review_title'];
        $data['detail'] = $review['review_text'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['user_id']))? $this->getMageIdCustomer($review['user_id']) : null;
        $data['nickname'] = '';
        $data['rating'] = $review['rating'];
        $data['created_at'] = $review['date_created'];
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
    ############################################################ Extend function ##################################
    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_catalog WHERE cid = {$parent_id}"
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

    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array())
    {
        $pro_data = $categories = array();
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $proCat = $this->getListFromListByField($productsExt['object']['products_categories'], 'pid', $product['pid']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['cid']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        if($is_variation_pro){
            $pro_data['name'] = $product['title'] . $data_variation['option_collection'];
            $pro_data['sku'] = $this->createProductSku($data_variation['object']['product_sku'], $this->_notice['config']['languages']);
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            $pro_data['price'] = 0;
            $pro_data['weight'] = 0;
            $pro_data['stock_data'] = array(
                'is_in_stock' => ($data_variation['object']['stock'] > 0) ? 1 : 0,
                'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['stock'] < 1)? 0 : 1,
                'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['stock'] < 1)? 0 : 1,
                'qty' => $data_variation['object']['stock']
            );
            $pro_data['status'] = ($data_variation['object']['is_active'] == '1')? 1 : 2;
            $pro_data['weight'] = $product['weight'] + $data_variation['weight'];
        }else{
            $pro_data['name'] = $product['title'];
            $pro_data['sku'] = $this->createProductSku($product['product_sku'], $this->_notice['config']['languages']);
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $pro_data['price'] = ($product['price2'] > 0) ? $product['price2'] : $product['price'];
            $pro_data['weight'] = $product['weight'];
            if($product['price2'] > 0){
                $pro_data['special_price'] =  $product['price'];
            }
            $manager_stock = 1;
            if($product['inventory_control'] == 'No'){
                $manager_stock = 0;
            }
            if($this->_notice['config']['add_option']['stock'] && $product['stock'] < 1){
                $manager_stock = 0;
            }
            $pro_data['stock_data'] = array(
                'is_in_stock' => ($product['stock'] > 0) ? 1 : 0,
                'manage_stock' => $manager_stock,
                'use_config_manage_stock' => $manager_stock,
                'qty' => $product['stock']
            );
            $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'products/' . $this->_convertImageName($product['product_id']) . '.jpg', 'catalog/product', false, true);
            if(!$img_path){
                $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'products/' . $this->_convertImageName($product['product_id']) . '.png', 'catalog/product', false, true);
            }
            if(!$img_path){
                $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'products/' . $this->_convertImageName($product['product_id']). '.gif', 'catalog/product', false, true);
            }
            if($img_path){
                $pro_data['image_import_path'] = array('path' => $img_path, 'label' => '');
            }
            $proImages = $this->getListFromListByField($productsExt['object']['products_images'], 'pid', $product['pid']);
            if($proImages){
                foreach($proImages as $pro_image){
                    if($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), 'products/secondary/' . $pro_image['filename'] . $pro_image['type'], 'catalog/product', false, true)){
                        $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '') ;
                    }
                }
            }
            $pro_data['status'] = ($product['is_visible'] == 'Yes')? 1 : 2;
            $pro_data['weight'] = $product['weight'];
        }
        $pro_data['description'] = $this->changeImgSrcInText($product['description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['overview'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $product['meta_title'] ;
        $pro_data['meta_description'] = $product['meta_description'];
        if($tax_pro_id = $this->getMageIdTaxProduct($product['tax_class_id'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['added'];
        if($manufacture_mage_id = $this->getMageIdManufacturer($product['manufacturer_id'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
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
        return $pro_data;
    }

    protected function _importChildrenProduct($product, $productsExt, $variation_combinations){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = $attrMage = array();
        $attributePro = $this->getListFromListByField($productsExt['object']['products_attributes'], 'pid', $product['pid']);
        $allAttribute = array();
        foreach($attributePro as $attribute_pro){
            $allOptions = array();
            $list_options = preg_split("/\r\n|\n|\r/", $attribute_pro['options']);
            foreach($list_options as $options){
                $price_prefix = $opt_price = $opt_percent = $option_name = $weight_prefix = $opt_weight = $weight_percent = '';
                preg_match('/(?P<option_name>.*)(\()(?P<option_price>.*)(\))/', $options, $optionsParse);
                if(!$optionsParse){
                    $optionsParse['option_name'] = $options;
                    $optionsParse['option_price'] = '';
                }
                $option_name = $optionsParse['option_name'];
                $option_price = preg_replace('/\s+/', '', $optionsParse['option_price']);
                $ex_option_price = explode(',', $option_price);
                if($ex_option_price[0]){
                    preg_match('/(?P<prefix>\D*)(?P<number>\d+(\.\d+)?)(?P<percent>%*)/', $ex_option_price[0], $priceParse);
                    $price_prefix = $priceParse['prefix'];
                    $opt_price = $priceParse['number'];
                    $opt_percent = $priceParse['percent'];
                }
                if(isset($ex_option_price[1])){
                    preg_match('/(?P<prefix>\D*)(?P<number>\d+(\.\d+)?)(?P<percent>%*)/', $ex_option_price[1], $weightParse);
                    $weight_prefix = $weightParse['prefix'];
                    $opt_weight = $weightParse['number'];
                    $weight_percent = $weightParse['percent'];
                }
                $allOptions[] = array(
                    'attribute_caption' => $attribute_pro['caption'],
                    'attribute_name' => $attribute_pro['name'],
                    'option_name' => $option_name,
                    'prefix_price' => isset($price_prefix) ? $price_prefix : '',
                    'option_price' => isset($opt_price) ? $opt_price : 0,
                    'option_percent' => isset($opt_percent) ? $opt_percent : '',
                    'prefix_weight' => isset($weight_prefix) ? $weight_prefix : '',
                    'option_weight' => isset($opt_weight) ? $opt_weight : 0,
                    'weight_percent' => isset($weight_percent) ? $weight_percent : ''
                );
            }
            if($allOptions){
                $allAttribute[] = $allOptions;
            }
        }
        //cook attribute_list in table products_inventory
//        foreach($variation_combinations as $key => $product_inventory){
//            $attributeList = preg_split("/\r\n|\n|\r/", $product_inventory['attributes_list']);
//            $attribute_list_merge = '';
//            foreach($attributeList as $attribute_list){
//                $attribute_list_merge .=  $attribute_list . '; ';
//            }
//            $variation_combinations[$key]['attributes_list'] = $attribute_list_merge;
//        }
        $combination = $this->_combinationFromMultiArray($allAttribute);
        if($combination){
            foreach($combination as $comb_row){
                $dataOpts = array();
                $option_collection = $attrList = '';
                $child_weight = 0;
                foreach ($comb_row as $option_attribute) {
                    $attribute_name = $option_attribute['attribute_caption'];
                    $attribute_code = $this->joinTextToKey($attribute_name, 27, '_');
                    $option_name = $option_attribute['option_name'];
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
                            'msg' => $this->consoleWarning("Product Id = {$product['pid']} import failed. Error: Product attribute could not create!")
                        );
                    }
                    $dataTMP = array(
                        'attribute_id' => $optAttrDataImport['attribute_id'],
                        'value_index' => $optAttrDataImport['option_ids']['option_0'],
                        'is_percent' => 0,
                    );
                    $dataOpts[] = $dataTMP;
                    if ($option_name){
                        $option_collection = $option_collection . ' - ' . $option_name;
                    }
                    $attrMage[$optAttrDataImport['attribute_id']]['attribute_label'] = $attribute_name;
                    $attrMage[$optAttrDataImport['attribute_id']]['attribute_code'] = $optAttrDataImport['attribute_code'];
                    $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['label'] = $option_name;
                    $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['value_index'] = $optAttrDataImport['option_ids']['option_0'];
                    $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['pricing_value'] = $option_attribute['prefix_price'] . $option_attribute['option_price'];
                    $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['is_percent'] = $option_attribute['option_percent'] ? 1 : 0;

                    $attrList[] = $option_attribute['attribute_name'] . ': ' . $option_name;
                    if($option_attribute['weight_percent']){
                        $weight = $product['weight'] * $option_attribute['option_weight'] / 100;
                    }else{
                        $weight = $option_attribute['option_weight'];
                    }
                    if($option_attribute['option_weight']){
                        $child_weight += $option_attribute['prefix_weight'] . $weight;
                    }
                }
                $data_variation = array(
                    'option_collection' => $option_collection,
                    'object' => false,
                    'weight' => $child_weight
                );
                foreach($variation_combinations as $variation){
                    $check = 0;
                    foreach($attrList as $attr_list){
                        if (preg_match('/'. $attr_list . '/',$variation['attributes_list'])){
                            $check++;
                        }
                    }
                    $variant_attr_list = preg_split("/\r\n|\n|\r/", $variation['attributes_list']);
                    if($check == count($variant_attr_list)){
                        $data_variation['object'] = $variation;
                    }
                }
                $convertPro = $this->_convertProduct($product, $productsExt, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, true, $data_variation);
                $pro_import = $this->_process->product($convertPro);
                if ($pro_import['result'] !== 'success') {
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$product['pid']} import failed. Error: Product children could not create!")
                    );
                }
                foreach ($dataOpts as $dataAttribute) {
                    $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
                }
                $dataChildes[$pro_import['mage_id']] = $dataOpts;
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
                    'is_percent' => $option['is_percent'],
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

    protected function _convertImageName($string){
        $string = str_replace(array("\\", "/", "*", ":", "?", "<", ">", "|", '"', "'"), array('_', '_', '_', '_', '_', '_', '_', '_', '_', '_'), strtolower(trim($string)));
        return $string;
    }

}