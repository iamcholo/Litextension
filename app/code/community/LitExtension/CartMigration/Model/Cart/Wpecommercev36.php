<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Wpecommercev36
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_options AS opt,_DBPRF_currency_list AS cur WHERE opt.option_name = 'base_country' AND cur.isocode = opt.option_value",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_product_categories WHERE group_id = 2 AND id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_product_categories WHERE group_id = 1 AND id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_product_list WHERE publish = 1 AND active = 1 AND id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_purchase_logs WHERE id > {$this->_notice['orders']['id_src']}",
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
        $default_currency = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_options WHERE option_name = 'currency_type'"
        ));
        if(!$default_currency || $default_currency['result'] != 'success'){
            return $this->errorConnector();
        }
        $this->_notice['config']['default_currency'] = isset($default_currency['object']['0']['option_value']) ? $default_currency['object']['0']['option_value'] : 1;
        $this->_notice['config']['default_lang'] = 1;
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT * FROM _DBPRF_options AS opt
                                    LEFT JOIN _DBPRF_currency_list AS cur ON cur.id = opt.option_value
                                    WHERE opt.option_name = 'currency_type'",
                "orders_status" => "SELECT * FROM _DBPRF_purchase_statuses WHERE active = 1",
                "user_roles" => "SELECT * FROM _DBPRF_options WHERE option_name = 'wp_user_roles'"
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
            $currency_id = $currency_row['id'];
            $currency_name = $currency_row['currency'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['id'];
            $order_status_name = $order_status_row['name'];
            $order_status_data[$order_status_id] = $order_status_name;
        }
        if(isset($obj['user_roles'][0]['option_value'])){
            $userRoles = unserialize($obj['user_roles'][0]['option_value']);
            if(is_array($userRoles)){
                foreach($userRoles as $value => $user_role_data){
                    $user_roles_value = $value;
                    $user_roles_name = $user_role_data['name'];
                    $customer_group_data[$user_roles_value] = $user_roles_name;
                }
            }
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_options AS opt,_DBPRF_currency_list AS cur WHERE opt.option_name = 'base_country' AND cur.isocode = opt.option_value",
                'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_product_categories WHERE group_id = 2 AND id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_product_categories WHERE group_id = 1 AND id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_product_list WHERE publish = 1 AND active = 1 AND id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_purchase_logs WHERE id > {$this->_notice['orders']['id_src']}",
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $totals = array();
        $totals['reviews'] = 0;
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
        foreach($this->_notice['config']['currencies_data'] as $currency_id => $currency_value){
            $currency_mage = $this->_notice['config']['currencies'][$currency_id];
            $data[$currency_mage] = $currency_value;
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
        $query = "SELECT *, 0 AS check_region_table FROM _DBPRF_options AS opt,_DBPRF_currency_list AS cur
                        WHERE opt.option_name = 'base_country'
                        AND cur.isocode = opt.option_value";
        return $query;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $country_ids = $this->duplicateFieldValueFromList($taxes['object'], 'id');
        $country_ids_query = $this->arrayToInCondition($country_ids);
        $ext_query = array(
            'regions_tax' => "SELECT *,1 AS check_region_table FROM _DBPRF_region_tax WHERE country_id IN {$country_ids_query} AND tax != 0"
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
        return 1;
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
            'class_name' => $tax['country']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess(1, $tax_pro_ipt['mage_id']);
        }
        $taxRates = array();
        if($tax['has_regions'] == 1){
            if(!empty($taxesExt['object']['regions_tax'])) $taxRates = $this->getListFromListByField($taxesExt['object']['regions_tax'], 'country_id', $tax['id']);
        }else{
            $taxRates[] = $tax;
        }
        foreach($taxRates as $tax_rate){
            if(!$tax['isocode']){
                return false;
            }
            $tax_rate_data = array();
            $code = $tax['country'];
            if($tax_rate['check_region_table'] == 1){
                if($tax_rate['name']){
                    $code .= "-". $tax_rate['name'];
                }
            }
            $tax_rate_data['code'] = $this->createTaxRateCode($code);
            $tax_rate_data['tax_country_id'] = $tax['isocode'];
            if($tax_rate['check_region_table'] == 1){
                $tax_rate_data['tax_region_id'] = $tax_rate['code'] ? $this->_getRegionIdByCode($tax_rate['code'], $tax['isocode']) : 0;
            }else{
                $tax_rate_data['tax_region_id'] = 0;
            }
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = "*";
            $tax_rate_data['rate'] = $tax_rate['tax'];
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if($tax_rate_ipt['result'] == 'success'){
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['country']);
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
        $query = "SELECT * FROM _DBPRF_product_categories WHERE group_id = 2 AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
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
        $manufacturer_data['value']['option'] = array(
            0 => $manufacturer['name']
        );
        foreach($this->_notice['config']['languages'] as $store_id){
            $manufacturer['value']['option'][$store_id] = $manufacturer['name'];
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
        $query = "SELECT * FROM _DBPRF_product_categories WHERE group_id = 1 AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
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
        return $category['id'];
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
        if($category['category_parent'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['category_parent']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['category_parent']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['id']} import failed. Error: Could not import parent category id = {$category['category_parent']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['name'] ? $category['name'] : " ";
        $cat_data['description'] = $category['description'];
        if($category['image'] && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $category['image'], 'catalog/category')){
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
        $query = "SELECT * FROM _DBPRF_product_list WHERE publish = 1 AND active = 1 AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import product
     *
     * @param array $products : Data of function getProductsMain
     * @return array : Response of connector
     */
    public function getProductsExt($products){
        $result = array(
            'result' => 'success'
        );
        $pro_ids = $this->duplicateFieldValueFromList($products['object'], 'id');
        $pro_ids_query = $this->arrayToInCondition($pro_ids);
        $ext_query = array(
            'products_meta' => "SELECT * FROM _DBPRF_wpsc_productmeta WHERE product_id IN {$pro_ids_query}",
            'variation_values_invisible' => "SELECT * FROM _DBPRF_variation_values_associations AS vva
                                                LEFT JOIN _DBPRF_wpsc_variation_combinations AS vc ON vc.value_id = vva.value_id AND vc.product_id = vva.product_id
                                                WHERE vva.product_id IN {$pro_ids_query} AND vva.visible = 0",
            'categories_products' => "SELECT * FROM _DBPRF_item_category_associations AS ica
                                        LEFT JOIN _DBPRF_product_categories AS pc ON pc.id = ica.category_id
                                        WHERE ica.product_id IN {$pro_ids_query}"
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
        if($ext_query) {
            $productsExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
                'serialize' => true,
                'query' => serialize($ext_query)
            ));
            if (!$productsExt || $productsExt['result'] != 'success') {
                return $this->errorConnector(true);
            }
            $price_invisible_ids = $this->duplicateFieldValueFromList($productsExt['object']['variation_values_invisible'], 'priceandstock_id');
            $priceInvisibleIdsCon = $this->arrayToInCondition($price_invisible_ids);
            $ext_rel_query = array(
                'variation_combinations' => "SELECT * FROM _DBPRF_wpsc_variation_combinations AS vc
                                         LEFT JOIN _DBPRF_variation_values AS vv ON vv.id = vc.value_id
                                         LEFT JOIN _DBPRF_variation_priceandstock AS vp ON vp.id = vc.priceandstock_id
                                         WHERE vc.product_id IN {$pro_ids_query} AND priceandstock_id NOT IN {$priceInvisibleIdsCon}",
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
            if ($ext_rel_query) {
                $productsExtRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize($ext_rel_query)
                ));
                if (!$productsExtRel || $productsExtRel['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $variation_ids = $this->duplicateFieldValueFromList($productsExtRel['object']['variation_combinations'], 'variation_id');
                $variationIdsCon = $this->arrayToInCondition($variation_ids);
                $productExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize(array(
                        'product_variations' => "SELECT * FROM _DBPRF_product_variations WHERE id IN {$variationIdsCon}"
                    ))
                ));
                if (!$productExtThird || $productExtThird['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $result['object'] = array_merge($productsExt['object'], $productsExtRel['object'], $productExtThird['object']);
            }
        }
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
        return $product['id'];
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
        $pro_data = $children_product = $categories = array();
        $product_meta = $this->getListFromListByField($productsExt['object']['products_meta'], 'product_id', $product['id']);
        $sku = $this->getRowValueFromListByField($product_meta, 'meta_key', 'sku', 'meta_value');
        if(!$sku){
            $sku = $this->joinTextToKey($product['name']);
        }
        $variation_combinations = $this->getListFromListByField($productsExt['object']['variation_combinations'], 'product_id', $product['id']);
        $group_cat_list = $this->getListFromListByField($productsExt['object']['categories_products'], 'product_id', $product['id']);
        $proCat = $this->getListFromListByField($group_cat_list, 'group_id', 1);
        $proMan = $this->getListFromListByField($group_cat_list, 'group_id', 2);
        $meta_des = '';
        $custom_meta = $this->getListFromListByField($product_meta, 'custom', 1);
        if($custom_meta){
            foreach($custom_meta as $meta){
                if($meta['meta_key'] && $meta['meta_value']){
                    $meta_des.= $meta['meta_key'].': '.$meta['meta_value']."\n";
                }
            }
        }
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['category_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        if($proMan){
            if($manufacture_mage_id = $this->getMageIdManufacturer($proMan[0]['category_id'])){
                $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
            }
        }
        if($product['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['image'] , 'catalog/product', false, true)){
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => $product['name'] ? $product['name'] : ' ');
        }
        if($table_rate_prices = $this->getRowValueFromListByField($product_meta, 'meta_key', 'table_rate_price', 'meta_value')){
            $tierPrices = $this->_createGrandPriceProduct(unserialize($table_rate_prices));
            $pro_data['tier_price'] = $tierPrices;
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' =>  1,
            'manage_stock' =>  ($this->_notice['config']['add_option']['stock'] && $product['quantity_limited'] == 0)? 0 : 1,
            'use_config_manage_stock' =>  ($this->_notice['config']['add_option']['stock'] && $product['quantity_limited'] == 0)? 0 : 1,
            'qty' => ($product['quantity'] > 0) ? $product['quantity'] : 0,
        );
        $pro_data['name'] = $product['name'];
        $pro_data['description'] = $this->changeImgSrcInText($product['description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['additional_description'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_description'] = $meta_des;
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['category_ids'] = $categories;

        if($variation_combinations){
            foreach($variation_combinations as $row){
                $children_product[$row['priceandstock_id']][] = $row;
            }
        }
        if(!empty($children_product)){
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $config_data = $this->_importChildrenProduct($product, $children_product, $productsExt, $sku);
            if(isset($config_data['result']) && isset($config_data['msg']) && $config_data['result'] == 'warning' && $config_data['msg']){
                return array(
                    'result' => 'warning',
                    'msg' => $this->consoleError($config_data['msg']),
                );
            }
            $pro_data = array_merge($config_data, $pro_data);
        }
        $pro_data['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
        $pro_data = array_merge($this->_convertProduct($product, $productsExt), $pro_data);
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_users WHERE ID > {$id_src} ORDER BY ID ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){
        $cus_ids = $this->duplicateFieldValueFromList($customers['object'], 'ID');
        $cus_ids_con = $this->arrayToInCondition($cus_ids);
        $ext_query = array(
            'user_meta' => "SELECT * FROM _DBPRF_usermeta WHERE user_id IN {$cus_ids_con} AND meta_key IN ('first_name','last_name','nickname','wpshpcrt_usr_profile','wp_capabilities')"
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
        return $customer['ID'];
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
            $cus_data['id'] = $customer['ID'];
        }
        $user_meta = $this->getListFromListByField($customersExt['object']['user_meta'], 'user_id', $customer['ID']);
        $first_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'first_name', 'meta_value');
        $last_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'last_name', 'meta_value');
        $nick_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'nickname', 'meta_value');
        $user_capabilities = $this->getRowValueFromListByField($user_meta, 'meta_key', 'wp_capabilities', 'meta_value');
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['user_email'];
        $cus_data['firstname'] = $first_name ? $first_name : $nick_name;
        $cus_data['lastname'] = $last_name ? $last_name : ' ';
        $cus_data['created_at'] = $customer['user_registered'] ? $customer['user_registered'] : '';
        $cus_data['group_id'] = 1;
        $userRole = unserialize($user_capabilities);
        if(is_array($userRole)){
            foreach($userRole as $key => $user_role_data){
                if($user_role_data == 1 && isset($this->_notice['config']['customer_group'][$key])){
                    $cus_data['group_id'] = $this->_notice['config']['customer_group'][$key];
                }
            }
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['user_pass']);
        $user_meta = $this->getListFromListByField($customersExt['object']['user_meta'], 'user_id', $customer['ID']);
        if($user_meta){
            $address_meta = unserialize($this->getRowValueFromListByField($user_meta, 'meta_key', 'wpshpcrt_usr_profile', 'meta_value'));
            if(is_array($address_meta) && !empty($address_meta)){
                $address_billing = array();
                $address_billing['firstname'] = isset($address_meta[2]) ? $address_meta[2] : '';
                $address_billing['lastname'] = isset($address_meta[3]) ? $address_meta[3] : '';
                if(isset($address_meta[6])){
                    if(is_array($address_meta[6])){
                        $address_billing['country_id'] = isset($address_meta[6][0]) ? $address_meta[6][0] : '';
                    }else{
                        $address_billing['country_id'] = $address_meta[6];
                    }
                }
                $address_billing['street'][0] = isset($address_meta[4]) ? $address_meta[4] : '';
                $address_billing['postcode'] = isset($address_meta[7]) ? $address_meta[7] : '';
                $address_billing['city'] = isset($address_meta[5]) ? $address_meta[5] : '';
                $address_billing['telephone'] = isset($address_meta[17]) ? $address_meta[17] : '';
                $customAddress = Mage::getModel('customer/address');
                if(!empty($address_billing)){
                    $customAddress->setData($address_billing)
                        ->setCustomerId($customer_mage_id)
                        ->setIsDefaultBilling('1')
                        ->setSaveInAddressBook('1');
                    try {
                        $customAddress->save();
                    }
                    catch (Exception $ex) {
                    }
                }
                $address_shipping = array();
                $address_shipping['firstname'] = isset($address_meta[10]) ? $address_meta[10] : '';
                $address_shipping['lastname'] = isset($address_meta[11]) ? $address_meta[11] : '';
                if(isset($address_meta[15])){
                    if(is_array($address_meta[15])){
                        $address_shipping['country_id'] = isset($address_meta[15][0]) ? $address_meta[15][0] : '';
                    }else{
                        $address_shipping['country_id'] = $address_meta[15];
                    }
                }
                $address_shipping['street'][0] = isset($address_meta[12]) ? $address_meta[12] : '';
                $address_shipping['postcode'] = isset($address_meta[16]) ? $address_meta[16] : '';
                $address_shipping['city'] = isset($address_meta[13]) ? $address_meta[13] : '';
                $address_shipping['telephone'] = isset($address_billing['telephone']) ? $address_billing['telephone'] : '';
                $address_shipping['region'] = isset($address_meta[14]) ? $address_meta[14] : '';
                if(!empty($address_shipping)){
                    $customAddress->setData($address_shipping)
                        ->setCustomerId($customer_mage_id)
                        ->setIsDefaultShipping('1')
                        ->setSaveInAddressBook('1');
                    try {
                        $customAddress->save();
                    }
                    catch (Exception $ex) {
                    }
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
        $query = "SELECT * FROM _DBPRF_purchase_logs WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    public function getOrdersExt($orders){
        $result = array(
            'result' => 'success'
        );
        $orders_ids = $this->duplicateFieldValueFromList($orders['object'], 'id');
        $order_ids_con = $this->arrayToInCondition($orders_ids);
        $ship_region_ids = $this->duplicateFieldValueFromList($orders['object'], 'shipping_region');
        $ship_region_ids_con = $this->arrayToInCondition($ship_region_ids);
        $user_ids = $this->duplicateFieldValueFromList($orders['object'], 'user_ID');
        $user_ids_con = $this->arrayToInCondition($user_ids);
        $ext_query = array(
            'submit_form_data' => "SELECT * FROM _DBPRF_submited_form_data AS sfd
                                LEFT JOIN _DBPRF_collect_data_forms AS cdf ON cdf.id = sfd.form_id
                                WHERE sfd.log_id IN {$order_ids_con} AND cdf.active = 1",
            'region_table' => "SELECT * FROM _DBPRF_region_tax WHERE id IN {$ship_region_ids_con}",
            'cart_contents' => "SELECT * FROM _DBPRF_cart_contents WHERE purchaseid IN {$order_ids_con}",
            'user_meta' => "SELECT * FROM _DBPRF_users AS u
                                LEFT JOIN _DBPRF_usermeta AS um ON um.user_id = u.ID
                                AND um.meta_key IN ('first_name','last_name')
                                WHERE u.ID IN {$user_ids_con}"
        );
        $cus_ext_query = $this->_custom->getOrdersExtQueryCustom($this, $orders);
        if($cus_ext_query){
            $ext_query = array_merge($ext_query, $cus_ext_query);
        }
        if($ext_query) {
            $ordersExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
                'serialize' => true,
                'query' => serialize($ext_query)
            ));
            if (!$ordersExt || $ordersExt['result'] != 'success') {
                return $this->errorConnector(true);
            }
            $pro_ids = $this->duplicateFieldValueFromList($ordersExt['object']['cart_contents'], 'prodid');
            $pro_ids_con = $this->arrayToInCondition($pro_ids);
            $cart_ids = $this->duplicateFieldValueFromList($ordersExt['object']['cart_contents'], 'id');
            $cart_ids_con = $this->arrayToInCondition($cart_ids);
            $ext_rel_query = array(
                'products_meta' => "SELECT pl.*, pm.meta_value FROM _DBPRF_product_list AS pl
                                LEFT JOIN _DBPRF_wpsc_productmeta AS pm ON pm.product_id = pl.id
                                AND pm.meta_key = 'sku'
                                WHERE pl.id IN {$pro_ids_con}",
                'cart_item_variations' => "SELECT * FROM _DBPRF_cart_item_variations AS civ
                                    LEFT JOIN _DBPRF_variation_values AS vv ON vv.id = civ.value_id
                                    WHERE civ.cart_id IN {$cart_ids_con}"
            );
            $cus_ext_rel_query = $this->_custom->getOrdersExtRelQueryCustom($this, $orders, $ordersExt);
            if ($cus_ext_rel_query) {
                $ext_rel_query = array_merge($ext_rel_query, $cus_ext_rel_query);
            }
            if ($ext_rel_query) {
                $ordersExtRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize($ext_rel_query)
                ));
                if (!$ordersExtRel || $ordersExtRel['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $pro_variants = $this->duplicateFieldValueFromList($ordersExtRel['object']['cart_item_variations'], 'variation_id');
                $pro_variants_con = $this->arrayToInCondition($pro_variants);
                $ordersExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize(array(
                        'product_variations' => "SELECT * FROM _DBPRF_product_variations WHERE id IN {$pro_variants_con}"
                    ))
                ));
                if (!$ordersExtThird || $ordersExtThird['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $result['object'] = array_merge($ordersExt['object'], $ordersExtRel['object'], $ordersExtThird['object']);
            }
        }
        return $result;
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order : One row of object in function getOrdersMain
     * @param array $ordersExt : Data of function getOrdersExt
     * @return int
     */
    public function getOrderId($order, $ordersExt){
        return $order['id'];
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

        $submit_form = $this->getListFromListByField($ordersExt['object']['submit_form_data'], 'log_id', $order['id']);

        $address_billing['firstname'] = $this->getRowValueFromListByField($submit_form, 'form_id', '2', 'value');
        $address_billing['lastname'] = $this->getRowValueFromListByField($submit_form, 'form_id', '3', 'value');
        $address_billing['email'] = $this->getRowValueFromListByField($submit_form, 'form_id', '8', 'value');
        $address_billing['street']  = $this->getRowValueFromListByField($submit_form, 'form_id', '4', 'value');
        $address_billing['city'] = $this->getRowValueFromListByField($submit_form, 'form_id', '5', 'value');
        $address_billing['postcode'] = $this->getRowValueFromListByField($submit_form, 'form_id', '7', 'value');
        $billing_country_type = $this->getRowValueFromListByField($submit_form, 'form_id', '6', 'type');
        $address_billing['country_id'] = ($bill_country_id = $this->_createCountryOrderByType($billing_country_type, $order)) ? $bill_country_id : $this->getRowValueFromListByField($submit_form, 'form_id', '6', 'value');
        if($billing_country_type == 'country' && is_numeric($order['shipping_region'])){
            $bill_region_code = $this->getRowValueFromListByField($ordersExt['object']['region_table'], 'id', $order['shipping_region'], 'code');
            $address_billing['region_id'] = $this->_getRegionIdByCode($bill_region_code, $address_billing['country_id']);
        }
        $address_billing['telephone'] = $this->getRowValueFromListByField($submit_form, 'form_id', '17', 'value');

        $address_shipping['firstname'] = $this->getRowValueFromListByField($submit_form, 'form_id', '10', 'value');
        $address_shipping['lastname'] = $this->getRowValueFromListByField($submit_form, 'form_id', '11', 'value');
        $address_shipping['street']  = $this->getRowValueFromListByField($submit_form, 'form_id', '12', 'value');
        $address_shipping['city'] = $this->getRowValueFromListByField($submit_form, 'form_id', '13', 'value');
        $address_shipping['postcode'] = $this->getRowValueFromListByField($submit_form, 'form_id', '16', 'value');
        $shipping_country_type = $this->getRowValueFromListByField($submit_form, 'form_id', '15', 'value');
        $address_shipping['country_id'] = ($ship_country_id = $this->_createCountryOrderByType($shipping_country_type, $order)) ? $ship_country_id : $this->getRowValueFromListByField($submit_form, 'form_id', '15', 'value');
        $address_shipping['region'] = $this->getRowValueFromListByField($submit_form, 'form_id', '14', 'value');
        if($shipping_country_type == 'country' && !$address_shipping['region'] && is_numeric($order['shipping_region'])){
            $ship_region_code = $this->getRowValueFromListByField($ordersExt['object']['region_table'], 'id', $order['shipping_region'], 'code');
            $address_shipping['region_id'] = $this->_getRegionIdByCode($ship_region_code, $address_shipping['country_id']);
        }
        $address_shipping['telephone'] = $address_billing['telephone'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['cart_contents'], 'purchaseid', $order['id']);
        $carts = array();
        $shipping_per_item = 0;
        $tax_amount = 0;
        if($orderPro){
            foreach($orderPro as $item){
                $cart = array();
                $product_id = $this->getMageIdProduct($item['prodid']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $data_pro = $this->getRowFromListByField($ordersExt['object']['products_meta'], 'id', $item['prodid']);
                $cart['name'] = $data_pro['name'];
                $cart['sku'] = $data_pro['meta_value'];
                $price = $item['price'] * $item['quantity'];
                $gst = $price - ($price  / (1+($item['gst'] / 100)));
                $tax_per_item = 0;
                if($gst > 0) {
                    $tax_per_item = $gst / $item['quantity'];
                }
                $cart['price'] = number_format($item['price'] - $tax_per_item, 2, '.', ',');
                $cart['original_price'] =  $cart['price'];
                $cart['tax_amount'] = number_format($gst, 2, '.', ',');
                $cart['qty_ordered'] = $item['quantity'];
                $shipping = $item['pnp'] * $item['quantity'];
                $cart['row_total'] = $price + $shipping;
                $cart['tax_percent'] = $item['gst'];
                if($product_options = $this->_createProductOrderOption($item['id'], $ordersExt)){
                    $cart['product_options'] = serialize($product_options);
                }
                $shipping_per_item += $shipping;
                if(is_numeric($cart['tax_amount'])) $tax_amount += $cart['tax_amount'];

                $carts[]= $cart;
            }
        }
        $order_data = array();
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $order_data['store_id'] = $store_id;
        $customer_id = $this->getMageIdCustomer($order['user_ID']);
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $user_meta = $this->getListFromListByField($ordersExt['object']['user_meta'], 'ID', $order['user_ID']);
        if(!empty($user_meta)){
            $order_data['customer_email'] = $user_meta[0]['user_email'] ? $user_meta[0]['user_email'] : $address_billing['email'];
            $order_data['customer_firstname'] = ($cus_first_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'first_name', 'meta_value')) ? $cus_first_name : $address_billing['firstname'];
            $order_data['customer_lastname'] = ($cus_last_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'last_name', 'meta_value')) ? $cus_last_name : $address_billing['lastname'];
        }
        $order_data['customer_group_id'] = 1;
        $order_status_id = $order['processed'];
        if($this->_notice['config']['order_status']){
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $order['totalprice'];
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['base_shipping'] + $shipping_per_item;
        $order_data['base_shipping_amount'] = $order['base_shipping'];
        $order_data['base_shipping_invoiced'] = $order['base_shipping'];
        $order_data['shipping_description'] = $order['shipping_method'];
        $order_data['tax_amount'] = $tax_amount;
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['discount_value'];
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $order['totalprice'];
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
        $data['order_src_id'] = $order['id'];
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
        $order_status_id = $order['processed'];
        $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
        if($order_status_data['status']){
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['comment'] = "<b>Reference order #".$order['id']."</b><br /><b>Payment method: </b>".$this->_createNamePaymentMethod($order['gateway'])."<br /><b>Shipping method: </b> ".$data['order']['shipping_description'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date("Y-m-d H:i:s",$order['date']);
        $order_status_data['created_at'] = date("Y-m-d H:i:s",$order['date']);
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }

    /**
     * Get main data use for import review
     *
     * @return array : Response of connector
     */
    public function getReviewsMain(){
        return array();
    }

    /**
     * Get relation data use for import reviews
     *
     * @param array $reviews : Data of function getReviewsMain
     * @return array : Response of connector
     */
    public function getReviewsExt($reviews){
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
        return null;
    }

    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return array
     */
    public function convertReview($review, $reviewsExt){
        return false;
    }

############################################################ Extend function ##################################
    protected function _createProductOrderOption($cart_id, $ordersExt){
        $list_variation = $this->getListFromListByField($ordersExt['object']['cart_item_variations'], 'cart_id', $cart_id);
        if($list_variation){
            $result = array();
            foreach($list_variation as $row){
                $option = array(
                    'label' => $this->getRowValueFromListByField($ordersExt['object']['product_variations'], 'id', $row['variation_id'], 'name'),
                    'value' => $row['name'],
                    'print_value' => $row['name'],
                    'option_id' => 'option_pro',
                    'option_type' => 'drop_down',
                    'option_value' => 0,
                    'custom_view' => false
                );
                $result[] = $option;
            }
            return array('options' => $result);
        }
        return false;
    }

    protected function _convertProduct($product, $productsExt){
        $pro_data = array();
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['status'] = 1;
        $pro_data['price'] = $product['price'];
        if(isset($product['special_price'])){
            $pro_data['special_price'] = ($product['special_price']) ? $product['price'] - $product['special_price'] : '';
        }
        $pro_data['tax_class_id'] = 0;
        if(isset($product['notax'])){
            if($product['notax'] == 0) $pro_data['tax_class_id'] = $this->getMageIdTaxProduct(1);
        }
        $pro_data['weight'] = ($weight = $this->_convertWeightUnitToKg($product['weight'], $product['weight_unit'])) ? $weight : 0;
        $pro_data['multi_store'] = array();
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

    protected function _importChildrenProduct($parent_product, $children_product, $productsExt, $sku_parent){
        $result = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $dataChildes = $cfg_attr_data = array();
        foreach($children_product as $child){
            $cfg_pro_data = $convertPro = array();
            $merge_sku = $name_variants = '';
            $i = 0;
            foreach($child as $option){
                $cfgProDataTmp =  array();
                $attr_label = $this->getRowValueFromListByField($productsExt['object']['product_variations'], 'id', $option['variation_id'], 'name');
                $attr_code = $this->joinTextToKey($attr_label, 27, '_');
                $attr_import = $this->_makeAttributeImport($attr_label, $attr_code, $option['name'], $entity_type_id, $this->_notice['config']['attribute_set_id']);
                if(!$attr_import){
                    return array(
                        'result' => 'warning',
                        'msg' => "Product ID = {$parent_product['id']} import failed. Error: Product attribute could not create!"
                    );
                }
                $dataOptAfterImport = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if(!$dataOptAfterImport){
                    return array(
                        'result' => 'warning',
                        'msg' => "Product ID = {$parent_product['id']} import failed. Error: Product attribute could not create!"
                    );
                };
                $dataOptAfterImport['option_label'] = $option['name'];
                $cfgProDataTmp['label'] = $attr_label;
                $cfgProDataTmp['attribute_id'] = $dataOptAfterImport['attribute_id'];
                $cfgProDataTmp['value_index'] =  $dataOptAfterImport['option_ids']['option_0'];
                $cfgProDataTmp['is_percent'] = 0;
                $cfgProDataTmp['pricing_value'] = '';
                $cfg_pro_data[] = $cfgProDataTmp;
                $cfg_attr_data[$cfgProDataTmp['attribute_id']]['attribute_label'] = $attr_label;
                $cfg_attr_data[$cfgProDataTmp['attribute_id']]['attribute_id'] = $dataOptAfterImport['attribute_id'];
                $cfg_attr_data[$cfgProDataTmp['attribute_id']]['attribute_code'] = $dataOptAfterImport['attribute_code'];
                $cfg_attr_data[$cfgProDataTmp['attribute_id']]['values'][$cfgProDataTmp['value_index']] = $dataOptAfterImport;
                if($option['name']) {
                    $merge_sku = $merge_sku.'-'.$this->joinTextToKey($option['name']);
                    if($i == 0){
                        $name_variants = $name_variants.$option['name'];
                        $i++;
                    }else{
                        $name_variants = $name_variants.', '.$option['name'];
                    }
                }
            }
            $sku = $sku_parent.$merge_sku;
            $convertPro['name'] = $parent_product['name'].' '.$name_variants;
            $convertPro['description'] = $this->changeImgSrcInText($parent_product['description'], $this->_notice['config']['add_option']['img_des']);
            $convertPro['short_description'] = $this->changeImgSrcInText($parent_product['additional_description'], $this->_notice['config']['add_option']['img_des']);
            $convertPro['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' =>  ($this->_notice['config']['add_option']['stock'] && $parent_product['quantity_limited'] == 0)? 0 : 1,
                'use_config_manage_stock' =>  ($this->_notice['config']['add_option']['stock'] && $parent_product['quantity_limited'] == 0)? 0 : 1,
                'qty' => ($child[0]['stock'] > 0) ? $child[0]['stock'] : 0,
            );
            $convertPro['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
            $convertPro['category_ids'] = array();
            $convertPro['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $convertPro['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            $convertPro = array_merge($this->_convertProduct($child[0], $productsExt), $convertPro);
            $pro_import = $this->_process->product($convertPro);
            if($pro_import['result'] !== 'success'){
                return array(
                    'result' => 'warning',
                    'msg' => "Product ID = {$parent_product['id']} import failed. Error: Error: Product children could not create!"
                );
            };
            if(!empty($cfg_pro_data)){
                foreach($cfg_pro_data as $dataAttribute){
                    $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
                }
            }
            $dataChildes[$pro_import['mage_id']] = $cfg_pro_data;
        }
        if(!empty($dataChildes) && !empty($cfg_attr_data)){
            $result = $this->_createConfigProductData($dataChildes, $cfg_attr_data);
        }
        return $result;
    }

    protected function _createConfigProductData($dataChildes, $cfg_attr_data){
        $attribute_config = array();
        $result['configurable_products_data'] = $dataChildes;
        $i = 0;
        foreach($cfg_attr_data as $attr){
            $data = array(
                'label' => $attr['attribute_label'],
                'use_default' => 1,
                'attribute_id' => $attr['attribute_id'],
                'attribute_code' => $attr['attribute_code'],
                'frontend_label' => $attr['attribute_label'],
                'store_label' => $attr['attribute_label'],
                'html_id' => 'configurable__attribute_'.$i,
            );
            $values = array();
            foreach($attr['values'] as $option_attr){
                $value = array(
                    'attribute_id' => $option_attr['attribute_id'],
                    'is_percent' => 0,
                    'pricing_value' => '',
                    'label' => $option_attr['option_label'],
                    'value_index' => $option_attr['option_ids']['option_0']
                );
                $values[] = $value;
            }
            $data['values'] = $values;
            $i++;
            $attribute_config[] = $data;
        }
        $result['configurable_attributes_data'] = $attribute_config;
        $result['can_save_configurable_attributes'] = 1;
        return $result;
    }

    protected function _makeAttributeImport($attribute_name, $attribute_code, $option_name, $entity_type_id, $attribute_set_id){
        $multi_option = $multi_attr = $result = array();
        $multi_option[0] = $option_name;
        $multi_attr[0] = $attribute_name;
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
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_product_categories
                          WHERE group_id = 1 AND id = {$parent_id}"
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

    protected function _getRegionIdByCode($region_code, $country_code){
        if($region_code && $country_code){
            $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
            $regionId = $regionModel->getId();
            if($regionId) return $regionId;
        }
        return 0;
    }

    protected function _convertWeightUnitToKg($weight, $weight_unit){
        $result = false;
        if($weight_unit == 'pound') $result = $weight*0.45359237;
        if($weight_unit == 'once') $result = $weight*0.0283495231 ;
        if($weight_unit == 'gram') $result = $weight*0.001;
        if($result) return $result;
        return $weight;
    }

    protected function _createGrandPriceProduct($table_product_prices){
        $tierPrices = array();
        foreach($table_product_prices['quantity'] as $key => $customer_price){
            if($customer_price){
                $tierPrices[] = array(
                    'website_id'  => 0,
                    'cust_group'  => Mage_Customer_Model_Group::CUST_GROUP_ALL,
                    'price_qty'   => $customer_price,
                    'price'       => $table_product_prices['table_price'][$key],
                );
            }
        }
        return $tierPrices;
    }

    protected function _createCountryOrderByType($type, $order){
        if($type == 'country'){
            return $order['billing_country'];
        }elseif($type == 'delivery_country'){
            return $order['shipping_country'];
        }else{
            return false;
        }
    }

    protected function _createNamePaymentMethod($code_payment){
        if($code_payment == 'testmode') return 'Manual Payment';
        if($code_payment == 'chronopay') return 'ChronoPay';
        if($code_payment == 'paypal_standard') return 'PayPal Payments Standard';
        if($code_payment == 'paypal_express') return 'PayPal Express Checkout';
        if($code_payment == 'paypal_pro') return 'PayPal Pro';
        return $code_payment;
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
        $query = "SELECT *, 0 AS check_region_table FROM _DBPRF_options AS opt,_DBPRF_currency_list AS cur
                        WHERE opt.option_name = 'base_country'
                        AND cur.isocode = opt.option_value";
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
        $query = "SELECT * FROM _DBPRF_product_categories WHERE group_id = 2 ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_product_categories WHERE group_id = 1 ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_product_list WHERE publish = 1 AND active = 1 ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_users ORDER BY ID ASC";
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
        $query = "SELECT * FROM _DBPRF_purchase_logs ORDER BY id ASC";
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