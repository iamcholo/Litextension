<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Woocommercev1
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_tax_classes'",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_term_taxonomy WHERE taxonomy = 'product_cat' AND term_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'product' AND post_status NOT IN ('inherit','auto-draft') AND ID > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'shop_order' AND post_status = 'publish' AND ID > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_comments AS cm,_DBPRF_posts AS p WHERE cm.comment_post_ID = p.ID AND p.post_type = 'product' AND cm.comment_ID > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this;
        }
        foreach($data['object'] as $type => $row){
            if($type == 'taxes' && isset($row[0]['option_value'])){
                $tax_rules = $this->_createTaxClassFromString($row[0]['option_value'], $this->_notice['taxes']['id_src']);
                $count = count($tax_rules);
            }else{
                $count = $this->arrayToCount($row);
            }
            $this->_notice[$type]['new'] = $count;
        }
        return $this;
    }

    public function displayConfig(){
        $response = array();
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_currency'",
                "orders_status" => "SELECT * FROM _DBPRF_term_taxonomy AS term_taxonomy
                                      LEFT JOIN _DBPRF_terms AS terms ON term_taxonomy.term_id = terms.term_id
                                    WHERE term_taxonomy.taxonomy = 'shop_order_status'",
                "user_roles" => "SELECT * FROM _DBPRF_options WHERE option_name = 'wp_user_roles'"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['default_currency'] = isset($obj['currencies'][0]['option_id']) ? $obj['currencies'][0]['option_id'] : 1;
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data['1'] = 'Default Language';
        foreach ($obj['currencies'] as $currency_row) {
            $currency_id = $currency_row['option_id'];
            $currency_name = $currency_row['option_value'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach ($obj['orders_status'] as $order_status_row) {
            $order_status_id = $order_status_row['term_taxonomy_id'];
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

    public function displayConfirm($params){
        parent::displayConfirm($params);
        return array(
            'result' => 'success'
        );
    }

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
                'taxes' => "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_tax_classes'",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_term_taxonomy WHERE taxonomy = 'product_cat' AND term_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'product' AND post_status NOT IN ('inherit','auto-draft') AND ID > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'shop_order' AND post_status = 'publish' AND ID > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_comments AS cm,_DBPRF_posts AS p WHERE cm.comment_post_ID = p.ID AND p.post_type = 'product' AND cm.comment_ID > {$this->_notice['reviews']['id_src']}"
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $totals = array();
        foreach($data['object'] as $type => $row){
            if($type == 'taxes' && isset($row[0]['option_value'])){
                $tax_rules = $this->_createTaxClassFromString($row[0]['option_value'], $this->_notice['taxes']['id_src']);
                $count = count($tax_rules);
            }else{
                $count = $this->arrayToCount($row);
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

    public function configCurrency(){
        parent::configCurrency();
        $allowCur = $this->_notice['config']['currencies'];
        $allow_cur = implode(',', $allowCur);
        $this->_process->currencyAllow($allow_cur);
        $default_cur = $this->_notice['config']['currencies'][$this->_notice['config']['default_currency']];
        $this->_process->currencyDefault($default_cur);
        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_currency'"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){
                $currency_id = $currency['option_id'];
                $currency_value = $currency['option_value'];
                $currency_mage = $this->_notice['config']['currencies'][$currency_id];
                $data[$currency_mage] = $currency_value;
            }
            $this->_process->currencyRate(array(
                $default_cur => $data
            ));
        }
        return ;
    }

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
        $taxes = array();
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_tax_classes'"
        ));
        if(!$query || $query['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $taxes['result'] = $query['result'];
        $taxes['object'] = $this->_createTaxClassFromString($query['object'][0]['option_value'], $id_src, $limit);
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
            'tax_rates' => "SELECT * FROM _DBPRF_options WHERE option_name IN ('woocommerce_tax_rates', 'woocommerce_local_tax_rates')"
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
            'class_name' => $tax['label']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id'], $tax_pro_ipt['mage_id'], $tax['value']);
        }
        $woo_tax_rates = unserialize($this->getRowValueFromListByField($taxesExt['object']['tax_rates'], 'option_name', 'woocommerce_tax_rates', 'option_value'));
        $woo_local_tax_rates = unserialize($this->getRowValueFromListByField($taxesExt['object']['tax_rates'], 'option_name', 'woocommerce_local_tax_rates', 'option_value'));
        if(is_array($woo_tax_rates)){
            foreach($woo_tax_rates as $tax_rates){
                if(!isset($tax_rates['countries']) || !is_array($tax_rates['countries']) || empty($tax_rates['countries']) || !isset($tax_rates['class']) || $tax_rates['class'] != $tax['value']){
                    continue ;
                }
                foreach($tax_rates['countries'] as $country => $anyStates){
                    if(!is_array($anyStates)){
                        continue;
                    }
                    foreach($anyStates as $state){
                        $tax_rate_data = array();
                        $code = $tax['label'] . "-" . $tax_rates['label'] . "-" . $country . "-" . $state;
                        $tax_rate_data['code'] = $this->createTaxRateCode($code);
                        $tax_rate_data['tax_country_id'] = $country;
                        if($state && $state != '*' && $region_id = $this->_getRegionIdByCode($state, $country)){
                            $tax_rate_data['tax_region_id'] = $region_id;
                        }else{
                            $tax_rate_data['tax_region_id'] = 0;
                        }
                        $tax_rate_data['zip_is_range'] = 0;
                        $tax_rate_data['tax_postcode'] = "*";
                        $tax_rate_data['rate'] = isset($tax_rates['rate']) ? $tax_rates['rate'] : 0;
                        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                        if($tax_rate_ipt['result'] == 'success'){
                            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                        }
                    }
                }

            }
        }
        if(is_array($woo_local_tax_rates)){
            foreach($woo_local_tax_rates as $local_rates){
                if(!isset($local_rates['country']) || !$local_rates['country'] || $local_rates['class'] != $tax['value']){
                    continue;
                }
                if(isset($local_rates['postcode']) && is_array($local_rates['postcode']) && !empty($local_rates['postcode'])){
                    foreach($local_rates['postcode'] as $post_code){
                        $tax_rate_data = array();
                        $code = $tax['label'] . "-" . $local_rates['label'] . "-" .$local_rates['country'] . "-" . $local_rates['state']. "-" . $post_code;
                        $tax_rate_data['code'] = $this->createTaxRateCode($code);
                        $tax_rate_data['tax_country_id'] = $local_rates['country'];
                        if($local_rates['state'] && $local_rates['state'] != '*' && $region_id = $this->_getRegionIdByCode($local_rates['state'], $local_rates['country'])){
                            $tax_rate_data['tax_region_id'] = $region_id;
                        }else{
                            $tax_rate_data['tax_region_id'] = 0;
                        }
                        $tax_rate_data['zip_is_range'] = 0;
                        $tax_rate_data['tax_postcode'] = $post_code;
                        $tax_rate_data['rate'] = isset($local_rates['rate']) ? $local_rates['rate'] : 0;
                        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                        if($tax_rate_ipt['result'] == 'success'){
                            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                        }
                    }
                }else{
                    $tax_rate_data = array();
                    $code = $tax['label'] . "-" . $local_rates['label'] . $local_rates['country'] . "-" . $local_rates['state'];
                    $tax_rate_data['code'] = $this->createTaxRateCode($code);
                    $tax_rate_data['tax_country_id'] = $local_rates['country'];
                    if($local_rates['state'] && $local_rates['state'] != '*' && $region_id = $this->_getRegionIdByCode($local_rates['state'], $local_rates['country'])){
                        $tax_rate_data['tax_region_id'] = $region_id;
                    }else{
                        $tax_rate_data['tax_region_id'] = 0;
                    }
                    $tax_rate_data['zip_is_range'] = 0;
                    $tax_rate_data['tax_postcode'] = "*";
                    $tax_rate_data['rate'] = isset($local_rates['rate']) ? $local_rates['rate'] : 0;
                    $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                    if($tax_rate_ipt['result'] == 'success'){
                        $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
                    }
                }
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['label']);
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
     * Query for get data for convert to manufacturer option
     *
     * @return string
     */
    protected function _getManufacturersMainQuery(){
        return false;
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
        return null;
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturersMain
     * @param array $manufacturersExt : Data of function getManufacturersExt
     * @return array
     */
    public function convertManufacturer($manufacturer, $manufacturersExt){
        return false;
    }

    /**
     * Query for get data of main table use import category
     *
     * @return string
     */
    protected function _getCategoriesMainQuery(){
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $query = "SELECT * FROM _DBPRF_term_taxonomy as tx
                          LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                          WHERE tx.taxonomy = 'product_cat'
                          AND tx.term_id > {$id_src} ORDER BY tx.term_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'term_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'categories_images' => "SELECT wt.*, pm.meta_value AS image_src FROM _DBPRF_woocommerce_termmeta AS wt
                                      LEFT JOIN _DBPRF_postmeta AS pm ON pm.post_id = wt.meta_value AND pm.meta_key = '_wp_attached_file'
                                    WHERE wt.meta_key = 'thumbnail_id'
                                      AND wt.woocommerce_term_id IN {$cat_id_con}"
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
        return $category['term_id'];
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
                        'msg' => $this->consoleWarning("Category Id = {$category['term_id']} import failed. Error: Could not import parent category id = {$category['parent']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['name'] ? $category['name'] : " ";
        $cat_data['description'] = $category['description'];
        $category_image = $this->getRowValueFromListByField($categoriesExt['object']['categories_images'], 'woocommerce_term_id', $category['term_id'], 'image_src');
        if($category_image && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  $category_image, 'catalog/category')){
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
        $query = "SELECT * FROM _DBPRF_posts WHERE post_type = 'product'
                            AND post_status NOT IN ('inherit','auto-draft') AND ID > {$id_src} ORDER BY ID ASC LIMIT {$limit}";
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
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'ID');
        $pro_ids_con = $this->arrayToInCondition($productIds);
        $ext_query = array(
            "post_variant" => "SELECT * FROM _DBPRF_posts WHERE post_parent IN {$pro_ids_con} AND post_status = 'publish' AND post_type = 'product_variation'",
            "term_relationship" => "SELECT * FROM _DBPRF_term_relationships AS tr
                                      LEFT JOIN _DBPRF_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
                                      LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                                    WHERE tr.object_id IN {$pro_ids_con}",
            "post_grouped" => "SELECT * FROM _DBPRF_posts WHERE post_parent IN {$pro_ids_con} AND post_type = 'product' AND post_status IN ('publish', 'trash')"
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
            $proChildIds = $this->duplicateFieldValueFromList($productsExt['object']['post_variant'], 'ID');
            $all_pro_ids_con = $this->arrayToInCondition(array_merge($proChildIds, $productIds));
            $attr_pa_values_list = $this->_getListFromListByFieldAsFirstKey($productsExt['object']['term_relationship'], 'taxonomy', 'pa_');
            $attr_values = array();
            if($attr_pa_values_list){
                $attr_pa_values = $this->duplicateFieldValueFromList($attr_pa_values_list, 'taxonomy');
                foreach($attr_pa_values as $row){
                    $attr_values[] = substr_replace($row,'',0,3);
                }
            }
            $attr_values_con = $this->arrayToInCondition($attr_values);
            $ext_rel_query = array(
                "post_meta" => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$all_pro_ids_con}",
                "woo_attribute_taxonomies" => "SELECT * FROM _DBPRF_woocommerce_attribute_taxonomies WHERE attribute_name IN {$attr_values_con}",
                "gallery_post" => "SELECT * FROM _DBPRF_posts WHERE post_parent IN {$all_pro_ids_con} AND post_type IN ('attachment') AND post_mime_type LIKE 'image/%'"
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
                $thumb_ids = array();
                $thumb_list = $this->getListFromListByField($productsExtRel['object']['post_meta'], 'meta_key', '_thumbnail_id');
                if($thumb_list){
                    $thumb_ids = $this->duplicateFieldValueFromList($thumb_list, 'meta_value');
                }
                $thumb_ids_con = $this->arrayToInCondition($thumb_ids);
                $gallery_ids = $this->duplicateFieldValueFromList($productsExtRel['object']['gallery_post'], 'ID');
                $all_images_ids = array_unique(array_merge($thumb_ids, $gallery_ids));
                $all_images_ids_con = $this->arrayToInCondition($all_images_ids);
                $productExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize(array(
                        'images_meta' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$all_images_ids_con} AND meta_key IN ('_wp_attached_file')",
                        'thumbnails_post' => "SELECT * FROM _DBPRF_posts WHERE ID IN {$thumb_ids_con}"
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
        return $product['ID'];
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
        $pro_data = $categories = $tags = array();
        $post_meta = $this->getListFromListByField($productsExt['object']['post_meta'], 'post_id', $product['ID']);
        $term_relationship = $this->getListFromListByField($productsExt['object']['term_relationship'], 'object_id', $product['ID']);
        $sku = $this->getRowValueFromListByField($post_meta, 'meta_key', '_sku', 'meta_value');
        if(!$sku){
            $sku = $this->joinTextToKey($product['post_title']);
        }
        $type_tmp = $this->getRowValueFromListByField($term_relationship, 'taxonomy', 'product_type', 'name');
        $variant_products = $this->getListFromListByField($productsExt['object']['post_variant'], 'post_parent', $product['ID']);
        $proCat = $this->getListFromListByField($term_relationship, 'taxonomy', 'product_cat');
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['term_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $proTags = $this->getListFromListByField($term_relationship, 'taxonomy', 'product_tag');
        if($proTags){
            foreach($proTags as $pro_tag){
                $tags[] = $pro_tag['name'];
            }
        }
        $pro_data['tags'] = $tags;
        $pro_data['type_id'] = $this->_createTypeProduct($type_tmp, $post_meta);
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['category_ids'] = $categories;
        $tax_pro_code = $this->getRowValueFromListByField($post_meta, 'meta_key', '_tax_class', 'meta_value');
        if(!$tax_pro_code){
            $tax_pro_code = 'null';
        }
        if($tax_pro_id = $this->_getMageIdTaxProductByValue($tax_pro_code)){
            $pro_data['tax_class_id'] = $tax_pro_id;
        }else{
            $pro_data['tax_class_id'] = 0;
        }

        if($pro_data['type_id'] == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $variant_products){
            $config_data = $this->_importChildrenProduct($product, $sku, $productsExt, $variant_products, $post_meta, $term_relationship, $pro_data['tax_class_id']);
            if(isset($config_data['result']) && isset($config_data['msg']) && $config_data['result'] == 'warning' && $config_data['msg']){
                return array(
                    'result' => 'warning',
                    'msg' => $this->consoleError($config_data['msg']),
                );
            }
            $pro_data = array_merge($config_data, $pro_data);
        }
        $pro_data['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
        $pro_data['price'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_regular_price', 'meta_value');
        $pro_data = array_merge($this->_convertProduct($product, $productsExt, $post_meta),$pro_data);
        if($variant_products && isset($variant_products[0]['ID'])){
            $child_meta = $this->getListFromListByField($productsExt['object']['post_meta'], 'post_id', $variant_products[0]['ID']);
            $pro_data['price'] = $this->getRowValueFromListByField($child_meta, 'meta_key', '_price', 'meta_value');
            $pro_data['special_price'] = $this->getRowValueFromListByField($child_meta, 'meta_key', '_sale_price', 'meta_value');
            $pro_data['special_from_date'] = $this->getRowValueFromListByField($child_meta, 'meta_key', '_sale_price_dates_from', 'meta_value');
            $pro_data['special_to_date'] = $this->getRowValueFromListByField($child_meta, 'meta_key', '_sale_price_dates_to', 'meta_value');
        }
        $pro_data['post_meta'] = $post_meta;
        $pro_data['term_relationship'] = $term_relationship;

        // Add multi Option
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $meta_attribute = unserialize($this->getRowValueFromListByField($post_meta, 'meta_key', '_product_attributes', 'meta_value'));
        $opt_attr_pa_list = $this->_getListFromListByFieldAsFirstKey($term_relationship, 'taxonomy', 'pa_');
        $allOptionAttributeOut = $this->_getAllOptionAttributeOut($opt_attr_pa_list, $meta_attribute);
        $allOptionAttributeIn = $this->_getAllOptionAttributeIn($meta_attribute);
        $dataAttrIn = $dataAttrOut = array();
        if(!empty($allOptionAttributeIn)){
            foreach($allOptionAttributeIn as $attr_slug => $attributeIn){
                if(empty($attributeIn)){
                    continue;
                }
                $attribute_code = 'le_'.$this->joinTextToKey(str_replace('-',' ',$attr_slug), 30, '_');
                foreach($attributeIn as $optionIn){
                    $attr_import = $this->_makeAttributeImport($optionIn['name_attribute'], $attribute_code, $optionIn['name_option'], $entity_type_id, $this->_notice['config']['attribute_set_id'], 'multiselect');
                    $attr_after = $this->_process->attribute($attr_import['config']);
                    if(!$attr_after){
                        continue;
                    }
                    $dataAttrIn[$attr_after['attribute_code']][] = $attr_after['option_ids']['option_0'];
                }
            }
        }

        if(!empty($allOptionAttributeOut)){
            foreach($allOptionAttributeOut as $attr_slug => $attributeOut){
                if(empty($attributeOut)){
                    continue;
                }
                $attribute_code = 'le_'.$this->joinTextToKey(str_replace('-',' ',$attr_slug), 30, '_');
                $attribute_name = $this->getRowValueFromListByField($productsExt['object']['woo_attribute_taxonomies'], 'attribute_name', $attr_slug, 'attribute_label');
                foreach($attributeOut as $optionOut){
                    $attr_import = $this->_makeAttributeImport($attribute_name, $attribute_code, $optionOut['name'], $entity_type_id, $this->_notice['config']['attribute_set_id'], 'multiselect');
                    $attr_after = $this->_process->attribute($attr_import['config']);
                    if(!$attr_after){
                        continue;
                    }
                    $dataAttrOut[$attr_after['attribute_code']][] = $attr_after['option_ids']['option_0'];
                }
            }
        }
        $dataAttr = array_merge($dataAttrIn, $dataAttrOut);
        if(!empty($dataAttr)){
            $pro_data = array_merge($pro_data, $dataAttr);
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
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt){
        if(parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)){
            return ;
        }
        $products_links = Mage::getModel('catalog/product_link_api');
        if($data['type_id'] == Mage_Catalog_Model_Product_Type::TYPE_GROUPED){
            $child_grouped = $this->getListFromListByField($productsExt['object']['post_grouped'], 'post_parent', $product['ID']);
            if($child_grouped){
                foreach($child_grouped as $child){
                    $id_child = $this->getMageIdProduct($child['ID']);
                    if($id_child){
                        $products_links->assign("grouped", $product_mage_id, $id_child);
                    }
                }
            }
        }elseif($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE){
            $id_post_parent = $this->getMageIdProduct($product['post_parent']);
            if($id_post_parent){
                $products_links->assign("grouped", $id_post_parent, $product_mage_id);
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
            'user_meta' => "SELECT * FROM _DBPRF_usermeta WHERE user_id IN {$cus_ids_con}"
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
        $customAddress = Mage::getModel('customer/address');
        if($user_meta){
            $address_billing = array();
            $address_billing['firstname'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_first_name', 'meta_value');
            $address_billing['lastname'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_last_name', 'meta_value');
            $address_billing['country_id'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_country', 'meta_value');
            if($street_bill_one = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_address_1', 'meta_value')){
                $address_billing['street'][0] = $street_bill_one;
            }
            if($street_bill_two = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_address_2', 'meta_value')){
                $address_billing['street'][1] = $street_bill_two;
            }
            $address_billing['postcode'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_postcode', 'meta_value');
            $address_billing['city'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_city', 'meta_value');
            $address_billing['telephone'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_phone', 'meta_value');
            $address_billing['company'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_company', 'meta_value');
            $billing_region = $this->getRowValueFromListByField($user_meta, 'meta_key', 'billing_state', 'meta_value');
            if($billing_region){
                $billing_region_id = $this->getRegionId($billing_region, $address_billing['country_id']);
                if($billing_region_id){
                    $address_billing['region_id'] = $billing_region_id;
                }else{
                    $address_billing['region'] = $billing_region;
                }
            }
            $check_import = false;
            foreach($address_billing as $add_bill){
                if($add_bill){
                    $check_import = true;
                    break;
                }
            }
            if($check_import){
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
            $address_shipping['firstname'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_first_name', 'meta_value');
            $address_shipping['lastname'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_last_name', 'meta_value');
            $address_shipping['country_id'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_country', 'meta_value');
            if($street_ship_one = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_address_1', 'meta_value')){
                $address_shipping['street'][0] = $street_ship_one;
            }
            if($street_ship_two = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_address_2', 'meta_value')){
                $address_shipping['street'][1] = $street_ship_two;
            }
            $address_shipping['postcode'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_postcode', 'meta_value');
            $address_shipping['city'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_city', 'meta_value');
            $address_shipping['company'] = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_company', 'meta_value');
            $shipping_region = $this->getRowValueFromListByField($user_meta, 'meta_key', 'shipping_state', 'meta_value');
            if($shipping_region){
                $shipping_region_id = $this->getRegionId($shipping_region, $address_shipping['country_id']);
                if($shipping_region_id){
                    $address_shipping['region_id'] = $shipping_region_id;
                }else{
                    $address_shipping['region'] = $shipping_region;
                }
            }
            $check_import = false;
            foreach($address_shipping as $add_ship){
                if($add_ship){
                    $check_import = true;
                    break;
                }
            }
            if($check_import){
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

    /**
     * Query for get data use for import order
     *
     * @return string
     */
    protected function _getOrdersMainQuery(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_posts
                          WHERE post_type = 'shop_order'
                            AND post_status = 'publish' AND ID > {$id_src} ORDER BY ID ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import order
     *
     * @param array $orders : Data of connector return for query function getOrdersMainQuery
     * @return array
     */
    protected function _getOrdersExtQuery($orders){
        $order_ids = $this->duplicateFieldValueFromList($orders['object'], 'ID');
        $order_ids_con = $this->arrayToInCondition($order_ids);
        $ext_query = array(
            'post_meta' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$order_ids_con}",
            'term_relationships' => "SELECT * FROM _DBPRF_term_relationships AS tr
                                          LEFT JOIN _DBPRF_term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                          LEFT JOIN _DBPRF_terms AS t ON t.term_id = tt.term_id
                                        WHERE tr.object_id IN {$order_ids_con} AND tt.taxonomy = 'shop_order_status'",
            'order_comments' => "SELECT * FROM _DBPRF_comments AS cmt
                                  LEFT JOIN _DBPRF_commentmeta AS cmt_meta ON cmt_meta.comment_id = cmt.comment_id
                                  WHERE cmt.comment_post_ID IN {$order_ids_con} AND cmt_meta.meta_key = 'is_customer_note'"
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
        $cus_list = $this->getListFromListByField($ordersExt['object']['post_meta'], 'meta_key', '_customer_user');
        $cus_ids = array();
        if($cus_list){
            $cus_ids = $this->duplicateFieldValueFromList($cus_list, 'meta_value');
        }
        $cus_ids_con = $this->arrayToInCondition($cus_ids);
        $order_items_list = $this->getListFromListByField($ordersExt['object']['post_meta'], 'meta_key', '_order_items');
        $list_pro = array();
        if($order_items_list){
            foreach($order_items_list as $srl_value){
                $un_srl_value = unserialize($srl_value['meta_value']);
                if(is_array($un_srl_value)){
                    foreach($un_srl_value as $item){
                        if(isset($item['id']) && $item['id']){
                            $list_pro[] = $item['id'];
                        }
                        if(isset($item['variation_id']) && $item['variation_id']){
                            $list_pro[] = $item['variation_id'];
                        }
                    }
                }
            }
        }
        $list_pro = array_unique($list_pro);
        $list_pro_con = $this->arrayToInCondition($list_pro);
        $ext_rel_query = array(
            'products_meta' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$list_pro_con}",
            'user' => "SELECT * FROM _DBPRF_users WHERE ID IN {$cus_ids_con}",
            'user_meta' => "SELECT * FROM _DBPRF_usermeta WHERE user_id IN {$cus_ids_con} AND meta_key IN ('first_name','last_name')",
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
        return $order['ID'];
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

        $post_meta = $this->getListFromListByField($ordersExt['object']['post_meta'], 'post_id', $order['ID']);
        $address_billing['firstname'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_first_name', 'meta_value');
        $address_billing['lastname'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_last_name', 'meta_value');
        $address_billing['company'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_company', 'meta_value');
        $address_billing['email'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_email', 'meta_value');
        $address_billing['street'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_address_1', 'meta_value')."\n".$this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_address_2', 'meta_value');
        $address_billing['city'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_city', 'meta_value');
        $address_billing['postcode'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_postcode', 'meta_value');
        $address_billing['country_id'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_country', 'meta_value');
        $address_billing['telephone'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_phone', 'meta_value');
        $bill_region = $this->getRowValueFromListByField($post_meta, 'meta_key', '_billing_state', 'meta_value');
        if($bill_region_id = $this->getRegionId($bill_region, $address_billing['country_id'])){
            $address_billing['region_id'] = $bill_region_id;
        }else{
            $address_billing['region'] = $bill_region;
        }
        $address_billing['save_in_address_book'] = true;

        $address_shipping['firstname'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_first_name', 'meta_value');
        $address_shipping['lastname'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_last_name', 'meta_value');
        $address_shipping['company'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_company', 'meta_value');
        $address_shipping['street'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_address_1', 'meta_value')."\n".$this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_address_2', 'meta_value');
        $address_shipping['city'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_city', 'meta_value');
        $address_shipping['postcode'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_postcode', 'meta_value');
        $address_shipping['country_id'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_country', 'meta_value');
        $ship_region = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_state', 'meta_value');
        if($ship_region_id = $this->getRegionId($ship_region, $address_shipping['country_id'])){
            $address_shipping['region_id'] = $ship_region_id;
        }else{
            $address_shipping['region'] = $ship_region;
        }
        $address_shipping['save_in_address_book'] = true;

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $customer_id = $this->getRowValueFromListByField($post_meta , 'meta_key', '_customer_user', 'meta_value');
        $customer_mage_id = $this->getMageIdCustomer($customer_id);
        $user = $this->getRowFromListByField($ordersExt['object']['user'], 'ID', $customer_id);
        $user_meta = $this->getListFromListByField($ordersExt['object']['user_meta'], 'user_id', $customer_id);
        $status_create = $this->getRowValueFromListByField($ordersExt['object']['term_relationships'], 'object_id', $order['ID'], 'term_taxonomy_id');
        $order_items = unserialize($this->getRowValueFromListByField($post_meta, 'meta_key', '_order_items', 'meta_value'));
        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_mage_id){
            $order_data['customer_id'] = $customer_mage_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $user['user_email'] ? $user['user_email'] : $address_billing['email'] ;
        $order_data['customer_firstname'] = ($first_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'first_name', 'meta_value')) ? $first_name : $address_billing['firstname'];
        $order_data['customer_lastname'] = ($last_name = $this->getRowValueFromListByField($user_meta, 'meta_key', 'last_name', 'meta_value')) ? $first_name : $address_billing['lastname'];
        $order_data['status'] = $this->_notice['config']['order_status'][$status_create];
        $order_data['state'] =  $this->getOrderStateByStatus($order_data['status']);

        $order_subtotal = 0;
        $carts = array();
        if(is_array($order_items)){
            foreach($order_items as $item){
                $cart = array();
                $product_id = $this->getMageIdProduct($item['id']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['name'] = $item['name'];
                $pro_meta = $this->getListFromListByField($ordersExt['object']['products_meta'], 'post_id', $item['id']);
                $cart['sku'] = $this->getRowValueFromListByField($pro_meta, 'meta_key', '_sku', 'meta_value');
                $cart['qty_ordered'] = $item['qty'];
                $subtotal = $item['line_subtotal'];
                $total = $item['line_total'];
                $cart['original_price'] = $subtotal/$cart['qty_ordered'];
                $cart['price'] = $total/$cart['qty_ordered'];
                $order_subtotal = $order_subtotal + $total;
                if($item['variation_id']){
                    $variation_meta = $this->getListFromListByField($ordersExt['object']['products_meta'], 'post_id', $item['variation_id']);
                    $product_options = $this->_getListFromListByFieldAsFirstKey($variation_meta, 'meta_key', 'attribute_');
                    $cart['product_options'] = serialize($this->_createProductOrderOption($product_options));
                }
                $cart['tax_amount'] = $item['line_tax'];
                $cart['tax_percent'] = ($total != 0) ? $cart['tax_amount']/$total *100 : 0;
                $cart['row_total'] = $total;
                $carts[]= $cart;
            }
        }
        $order_data['shipping_description'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_shipping_method_title', 'meta_value');
        $order_data['shipping_amount'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_order_shipping', 'meta_value');
        $order_data['base_shipping_amount'] = $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order_data['shipping_amount'];
        $order_data['subtotal'] = $order_subtotal;
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['tax_amount'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_order_tax', 'meta_value');
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_order_discount', 'meta_value');
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_order_total', 'meta_value');
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
        $order_data['created_at'] = $order['post_date'];

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['ID'];
        $data['woo_payment_method'] = $this->getRowValueFromListByField($post_meta, 'meta_key', '_payment_method_title', 'meta_value');
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
        $order_comments = $this->getListFromListByField($ordersExt['object']['order_comments'], 'comment_post_ID', $order['ID']);
        if($order_comments){
            foreach($order_comments as $key => $comment){
                $order_data['status'] = $data['order']['status'];
                if($order_data['status']){
                    $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
                }
                if($key == 0){
                    $order_data['comment'] = "<b>Reference order #".$order['ID']."</b><br /><b>Payment method: </b>".$data['woo_payment_method']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$comment['comment_content'];
                } else {
                    $order_data['comment'] = $comment['comment_content'];
                }
                $order_data['is_customer_notified'] = $comment['meta_value'];
                $order_data['updated_at'] = $comment['comment_date'];
                $order_data['created_at'] = $comment['comment_date'];
                $this->_process->ordersComment($order_mage_id, $order_data);
            }
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
        $query = "SELECT cm.*, p.post_type, cm_meta.meta_value FROM _DBPRF_comments AS cm
                    LEFT JOIN _DBPRF_posts AS p ON p.ID = cm.comment_post_ID
                    LEFT JOIN _DBPRF_commentmeta AS cm_meta ON cm_meta.comment_id = cm.comment_id AND cm_meta.meta_key = 'rating'
                    WHERE p.post_type = 'product' AND cm.comment_ID > {$id_src} ORDER BY cm.comment_ID ASC LIMIT {$limit}";
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
        return $review['comment_ID'];
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
        $product_mage_id = $this->getMageIdProduct($review['comment_post_ID']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['comment_ID']} import failed. Error: Product Id = {$review['comment_post_ID']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['comment_approved'] == 0)? 3 : 1;
        $data['title'] = " ";
        $data['detail'] = $review['comment_content'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['user_id']))? $this->getMageIdCustomer($review['user_id']) : null;
        $data['nickname'] = $review['comment_author'];
        $data['rating'] = $review['meta_value'];
        $data['created_at'] = $review['comment_date'];
        $data['review_id_import'] = $review['comment_ID'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

###################################### Extend Function #################################################################
    protected function _createProductOrderOption($product_options){
        if(!$product_options){
            return false;
        }
        $result = array();
        foreach($product_options as $pro_opt){
            $attribute = $this->getAttributeNameByPrefix($pro_opt['meta_key']);
            if($pro_opt['meta_value'] == ''){
                continue;
            }else{
                $option_value = $pro_opt['meta_value'];
            }
            $option = array(
                'label' => $attribute,
                'value' => $option_value,
                'print_value' => $option_value,
                'option_id' => 'option_pro',
                'option_type' => 'drop_down',
                'option_value' => 0,
                'custom_view' => false
            );
            $result[] = $option;
        }
        return array('options' => $result);
    }

    protected function getAttributeNameByPrefix($name_attribute){
        $attributePrefix = substr($name_attribute,0,13);
        if($attributePrefix == 'attribute_pa_'){
            $name_attribute = ucfirst(str_replace('-', ' ', substr_replace($name_attribute,'',0,13)));
        }else{
            $name_attribute = ucfirst(str_replace('-', ' ', substr_replace($name_attribute,'',0,10)));
        }
        return $name_attribute;
    }

    protected function _convertProduct($product, $productsExt, $product_meta){
        $pro_data = $categories = array();
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['created_at'] = $product['post_date'];
        $pro_data['special_price'] = $this->getRowValueFromListByField($product_meta, 'meta_key', '_sale_price', 'meta_value');
        $pro_data['special_from_date'] = $this->getRowValueFromListByField($product_meta, 'meta_key', '_sale_price_dates_from', 'meta_value');
        $pro_data['special_to_date'] = $this->getRowValueFromListByField($product_meta, 'meta_key', '_sale_price_dates_to', 'meta_value');
        $pro_data['name'] = $product['post_title'];
        $pro_data['description'] = $this->changeImgSrcInText($product['post_content'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['post_excerpt'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['weight'] = ($weight = $this->getRowValueFromListByField($product_meta, 'meta_key', '_weight', 'meta_value')) ? $weight : 0;
        $pro_data['status'] = 1;
        $qty = $this->getRowValueFromListByField($product_meta, 'meta_key', '_stock', 'meta_value');
        $manger_stock = $this->getRowValueFromListByField($product_meta, 'meta_key', '_manage_stock', 'meta_value');
        $check_manager_stock = 1;
        if(($this->_notice['config']['add_option']['stock'] && $qty < 1) || $manger_stock == 'no'){
            $check_manager_stock = 0;
        }
        $in_stock = 0;
        if($stock_status = $this->getRowValueFromListByField($product_meta, 'meta_key', '_stock_status', 'meta_value')){
            if($stock_status == 'instock' && $qty > 0){
                $in_stock = 1;
            }
        }else{
            if($qty > 0){
                $in_stock = 1;
            }
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' =>  $in_stock,
            'manage_stock' => $check_manager_stock,
            'use_config_manage_stock' => $check_manager_stock,
            'qty' => ($qty > 0) ? $qty : 0,
        );
        $thumbnail_id = $this->getRowValueFromListByField($product_meta, 'meta_key', '_thumbnail_id', 'meta_value');
        if($thumbnail_id){
            $img_src = $this->getRowValueFromListByField($productsExt['object']['images_meta'], 'post_id', $thumbnail_id, 'meta_value');
            $img_label = $this->getRowValueFromListByField($productsExt['object']['thumbnails_post'], 'ID', $thumbnail_id, 'post_title');
            if($img_src && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $img_src, 'catalog/product', false, true)){
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => $img_label ? $img_label : '');
            }
        }
        $gallery_list = $this->getListFromListByField($productsExt['object']['gallery_post'], 'post_parent', $product['ID']);
        if($gallery_list){
            foreach($gallery_list as $image_gallery){
                $image_gallery_src = $this->getRowValueFromListByField($productsExt['object']['images_meta'], 'post_id', $image_gallery['ID'], 'meta_value');
                if($image_gallery_src && $gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_gallery_src, 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $image_gallery['post_title'] ? $image_gallery['post_title'] : '');
                }
            }
        }
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

    protected function _addCustomOption($anyData, $pro_mage_id){
        $custom_option = array();
        foreach($anyData as $data){
            $options = array();
            foreach($data['option'] as $option){
                $tmp['option_type_id'] = -1;
                $tmp['title'] = $option;
                $tmp['price'] = '';
                $tmp['price_type'] = 'fixed';
                $options[]=$tmp;
            }
            $tmp_opt = array(
                'title' => $data['attribute'],
                'type' => 'drop_down',
                'is_require' => 1,
                'sort_order' => 0,
                'values' => $options,
            );
            $custom_option[] = $tmp_opt;
        }
        $this->importProductOption($pro_mage_id, $custom_option);
    }

    protected function _createDataCustomOption($anyData, $attribute_name, $attr_out = false){
        $result = array();
        $result['attribute'] = $attribute_name;
        $result['option'] = array();
        foreach($anyData as $data){
            if($attr_out){
                if($data['name']){
                    $result['option'][] = $data['name'];
                }
            }else{
                if($data['name_option']){
                    $result['option'][] = $data['name_option'];
                }
            }
        }
        return $result;
    }

    protected function _importChildrenProduct($parent_product, $sku_parent, $productsExt, $variant_products, $parent_meta, $term_relationship, $tax_parent_id){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $meta_parent_attribute = unserialize($this->getRowValueFromListByField($parent_meta, 'meta_key', '_product_attributes', 'meta_value'));
        $attrForVariation = array();
        if(is_array($meta_parent_attribute)){
            foreach($meta_parent_attribute as $attr_code => $row){
                if($row['is_variation'] == '1'){
                    $attrForVariation[$attr_code] = $row;
                }
            }
        }
        $allOptionAttributeIn = $this->_getAllOptionAttributeIn($attrForVariation);
        $opt_attr_pa_list = $this->_getListFromListByFieldAsFirstKey($term_relationship, 'taxonomy', 'pa_');
        $allOptionAttributeOut = $this->_getAllOptionAttributeOut($opt_attr_pa_list, $attrForVariation);
        $dataChildes = array();
        foreach($variant_products as $child){
            $convertPro = $attr_pro_data = $checksDuplicate = array();
            $option_collection = '';
            $meta_children = $this->getListFromListByField($productsExt['object']['post_meta'], 'post_id', $child['ID']);
            $options_list = $this->_getListFromListByFieldAsFirstKey($meta_children, 'meta_key', 'attribute_');
            $check_ipt_child = true;
            if(!$options_list){
                continue;
            }
            foreach($options_list as $opt){
                $opt_meta_val = trim($opt['meta_value']);
                if(!$opt_meta_val){
                    $check_ipt_child = false;
                    break;
                }
            }
            if(!$check_ipt_child){
                continue;
            }
            foreach($options_list as $option){
                $data_option = $this->_getDataOptionVariantPro($option, $allOptionAttributeIn, $allOptionAttributeOut, $productsExt['object']['woo_attribute_taxonomies']);
                if(!$data_option || empty($data_option)){
                    continue;
                }
                foreach($checksDuplicate as $checkDuplicate){
                    if($data_option['attr_code'] == $checkDuplicate){
                        $data_option['attr_code'] = $data_option['attr_code'].'_pa';
                    }
                }
                $checksDuplicate[] = $data_option['attr_code'];
                $attr_import = $this->_makeAttributeImport($data_option['attr_name'], $data_option['attr_code'], $data_option['option_name'], $entity_type_id, $this->_notice['config']['attribute_set_id']);
                if(!$attr_import){
                    return array(
                        'result' => 'warning',
                        'msg' => "Product ID = {$parent_product['ID']} import failed. Error: Product attribute could not create!"
                    );
                }
                $dataOptAfterImport = $this->_process->attribute($attr_import['config'], $attr_import['edit']);
                if(!$dataOptAfterImport){
                    return array(
                        'result' => 'warning',
                        'msg' => "Product ID = {$parent_product['ID']} import failed. Error: Product attribute could not create!"
                    );
                };
                $dataOptAfterImport['option_label'] = $data_option['option_name'];
                $attrProDataTmp = array(
                    'attribute_label' => $data_option['attr_name'],
                    'woo_attribute_code' => $data_option['attr_code'],
                    'mage_attribute_code' => $dataOptAfterImport['attribute_code'],
                    'attribute_id' => $dataOptAfterImport['attribute_id'],
                    'value_index' => $dataOptAfterImport['option_ids']['option_0'],
                    'value_label' => $data_option['option_name'],
                );
                $attr_pro_data[] = $attrProDataTmp;

                if($data_option['option_name']){
                    $option_collection.= ', '.$data_option['option_name'];
                }else{
                    $option_collection.= ', Any '.$data_option['attr_name'];
                }
            }

            $child_pro_name = $parent_product['post_title'].$option_collection;
            $sku = $this->getRowValueFromListByField($meta_children, 'meta_key', '_sku', 'meta_value');
            if(!$sku){
                $sku = $sku_parent. "-" .$this->joinTextToKey($option_collection);
            }
            if($this->getRowValueFromListByField($meta_children, 'meta_key', '_virtual', 'meta_value') == 'yes'){
                $convertPro['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL;
            }else{
                $convertPro['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            }
            $tax_pro_row = $this->getRowFromListByField($meta_children, 'meta_key', '_tax_class');
            if($tax_pro_row){
                $tax_pro_code = $tax_pro_row['meta_value'];
                if(!$tax_pro_code){
                    $tax_pro_code = 'null';
                }
                if($tax_pro_id = $this->_getMageIdTaxProductByValue($tax_pro_code)){
                    $convertPro['tax_class_id'] = $tax_pro_id;
                }else{
                    $convertPro['tax_class_id'] = 0;
                }
            }else{
                $convertPro['tax_class_id'] = $tax_parent_id;
            }
            $convertPro['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            $convertPro['name'] = $child_pro_name;
            $convertPro['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
            $convertPro['category_ids'] = array();
            $convertPro['price'] = $this->getRowValueFromListByField($meta_children, 'meta_key', '_price', 'meta_value');
            $convertPro = array_merge($this->_convertProduct($child, $productsExt, $meta_children), $convertPro);
            $pro_import = $this->_process->product($convertPro);
            if($pro_import['result'] !== 'success'){
                return array(
                    'result' => 'warning',
                    'msg' => "Product ID = {$parent_product['ID']} import failed. Error: Error: Product children could not create!"
                );
            };

            if(!empty($attr_pro_data)){
                foreach($attr_pro_data as $dataAttribute){
                    $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
                }
            }
            $dataChildes[$pro_import['mage_id']] = $attr_pro_data;
        }
        $cfg_pro_data = false;
        if(!empty($dataChildes)){
            $cfg_pro_data = $this->_createConfigProductData($dataChildes);
        }
        return array(
            'configurable_products_data' => $cfg_pro_data['configurable_products_data'],
            'configurable_attributes_data' => $cfg_pro_data['configurable_attributes_data'],
            'can_save_configurable_attributes' => 1,
        );
    }

    protected function _changeArrayUniqueAttribute($current_array){
        $new_array = array();
        foreach($current_array as $arr){
            $tmp = 0;
            foreach($new_array as $new){
                if($arr == $new){
                    $tmp = 1;
                    break;
                }
            }
            if($tmp == 0){
                $new_array[] = $arr;
            }
        }
        return $new_array;
    }

    protected function _createConfigProductData($dataChildes){
        $cfg_pro_data = $cfg_attr_data = array();
        foreach($dataChildes as $pro_mage_id => $child){
            foreach($child as $attr){
                $cfgProDataTMP = array(
                    'label' => $attr['attribute_label'],
                    'attribute_id' => $attr['attribute_id'],
                    'value_index' => $attr['value_index'],
                    'is_percent' => 0,
                    'pricing_value' => '',
                );
                $cfg_attr_data[$attr['attribute_id']]['label'] = $attr['attribute_label'];
                $cfg_attr_data[$attr['attribute_id']]['attribute_id'] = $attr['attribute_id'];
                $cfg_attr_data[$attr['attribute_id']]['attribute_code'] = $attr['mage_attribute_code'];
                $cfg_attr_data[$attr['attribute_id']]['frontend_label'] = $attr['attribute_label'];
                $cfg_attr_data[$attr['attribute_id']]['html_id'] = 'configurable__attribute_0';
                $cfg_attr_data[$attr['attribute_id']]['value'][$attr['value_index']] = array(
                    'value_index' => $attr['value_index'],
                    'label' => $attr['value_label'],
                    'is_percent' => 0,
                    'pricing_value' => 0,
                    'attribute_id' => $attr['attribute_id'],
                );
                $cfg_pro_data[$pro_mage_id][] = $cfgProDataTMP;
            }
        }
        $result = array(
            'configurable_products_data' => $cfg_pro_data,
            'configurable_attributes_data' => $cfg_attr_data,
        );
        return $result;
    }

    protected function _getAllOptionAttributeOut($list_opt_attr, $listAttrCondition){
        $result = array();
        if($list_opt_attr){
            foreach($list_opt_attr as $row){
                foreach($listAttrCondition as $attr){
                    if($row['taxonomy'] == $attr['name']){
                        $attr_slug = substr_replace($row['taxonomy'],'',0,3);
                        $result[$attr_slug][] = $row;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    protected function _makeAttributeImport($attribute_name, $attribute_code, $option_name, $entity_type_id, $attribute_set_id, $type = 'select'){
        $multi_option = $multi_attr = $result = array();
        $multi_option[0] = $option_name;
        $multi_attr[0] = $attribute_name;
        $config = array(
            'entity_type_id' => $entity_type_id,
            'attribute_code' => $attribute_code,
            'attribute_set_id' => $attribute_set_id,
            'frontend_input' => $type,
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

    protected function _getDataOptionVariantPro($data_option, $allOptionAttributeIn, $allOptionAttributeOut, $woo_attribute_list){
        $result = array();
        $attributePrefix = substr($data_option['meta_key'],0,13);
        if($attributePrefix == 'attribute_pa_'){
            $attr_slug = substr_replace($data_option['meta_key'],'',0,13);
            if(!isset($allOptionAttributeOut[$attr_slug])){
                return false;
            }
            $result['attr_prefix'] = 'attribute_pa_';
            $attr_name = $this->getRowValueFromListByField($woo_attribute_list, 'attribute_name', $attr_slug, 'attribute_label');
            $result['attr_name'] = $attr_name ? $attr_name : $attr_slug;
            $result['attr_slug'] = $attr_slug;
            $result['attr_code'] = $this->joinTextToKey(str_replace('-',' ',$result['attr_slug']), 30, '_');
            $result['option_name'] = $this->getRowValueFromListByField($allOptionAttributeOut[$attr_slug], 'slug', $data_option['meta_value'], 'name');
        }else{
            $attr_slug = substr_replace($data_option['meta_key'],'',0,10);
            if(!isset($allOptionAttributeIn[$attr_slug])){
                return false;
            }
            $result['attr_prefix'] = 'attribute_';
            $result['attr_name'] = isset($allOptionAttributeIn[$attr_slug][0]['name_attribute']) ? $allOptionAttributeIn[$attr_slug][0]['name_attribute'] : $attr_slug;
            $result['attr_slug'] = $attr_slug;
            $result['attr_code'] = $this->joinTextToKey(str_replace('-',' ',$result['attr_slug']), 30, '_');
            $result['option_name'] = $this->getRowValueFromListByField($allOptionAttributeIn[$attr_slug], 'slug', $data_option['meta_value'], 'name_option');
        }
        return $result;
    }

    protected function _getListFromListByFieldAsFirstKey($list, $field, $first_key){
        if(!$list){
            return false;
        }
        $result = array();
        foreach($list as $row){
            if(strpos($row[$field],$first_key) === 0){
                $result[] = $row;
            }
        }
        return $result;
    }

    protected function _getAllOptionAttributeIn($meta_product_attribute){
        $result = array();
        if($meta_product_attribute && is_array($meta_product_attribute)){
            foreach($meta_product_attribute as $attribute => $arr){
                if(isset($arr['value']) && !is_array($arr['value']) && trim($arr['value']) != NULL  && $arr['is_taxonomy'] == '0'){
                    $options = explode("|", $arr['value']);
                    foreach($options as $option){
                        $tmp = array();
                        $tmp['name_attribute'] = isset($arr['name']) ? $arr['name'] : ' ';
                        $tmp['name_option'] = $option;
                        $tmp['slug'] = $option;
                        $result[$attribute][] = $tmp;
                    }
                }
            }
        }
        return $result;
    }

    protected function _createTypeProduct($type_tmp, $post_meta){
        if($this->getRowValueFromListByField($post_meta, 'meta_key', '_downloadable', 'meta_value') == 'yes'){
            $result = 'downloadable';
        }
        elseif($this->getRowValueFromListByField($post_meta, 'meta_key', '_virtual', 'meta_value') == 'yes'){
            $result = Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL;
        }
        elseif($type_tmp == 'variable'){
            $result = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
        }
        elseif($type_tmp == 'grouped'){
            $result = Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
        }
        else{
            $result = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        }
        if($result){
            return $result;
        }else{
            return false;
        }
    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_term_taxonomy as tx
                          LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                          WHERE tx.taxonomy = 'product_cat' AND tx.term_id = {$parent_id}"
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

    protected function _createTaxClassFromString($tax_class, $id_src, $limit = false){
        $result = $response = array();
        $id = 1;
        $response[] = array('id' => $id, 'value' => '', 'label' => 'Standard');
        $tax_classes = array_filter(array_map('trim', explode("\n",$tax_class)));
        if($tax_classes & is_array($tax_classes)){
            foreach($tax_classes as $class){
                $id++;
                $value = $this->joinTextToKey($class);
                $label = $class;
                $response[] = array('id' => $id ,'value' => $value, 'label' => $label);
            }
        }
        $count_limit = 0;
        if($limit){
            foreach($response as $row){
                if($row['id'] > $id_src && $count_limit < $limit){
                    $result[] = $row;
                    $count_limit++;
                }
            }
        }else{
            foreach($response as $row){
                if($row['id'] > $id_src){
                    $result[] = $row;
                    $count_limit++;
                }
            }
        }
        return $result;
    }

    /**
     * Get magento tax product id import by value
     */
    protected function _getMageIdTaxProductByValue($value){
        $result = $this->_selectLeCaMgImport(array(
            'domain' => $this->_cart_url,
            'type' => self::TYPE_TAX_PRODUCT,
            'value' => $value
        ));
        if(!$result){
            return false;
        }
        return $result['mage_id'];
    }

    protected function _getRegionIdByCode($region_code, $country_code){
        $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
        $regionId = $regionModel->getId();
        if($regionId) return $regionId;
        return false;
    }

    protected function _updateMultiOptionsProduct($mage_id_pro, $attribute_id, $value, $entity_type_id){
        $table = $this->_resource->getTableName('catalog_product_entity_varchar');
        $query = "INSERT INTO {$table} (`value_id`, `entity_type_id`, `attribute_id`, `store_id`, `entity_id`, `value`) VALUES (null, {$entity_type_id}, {$attribute_id}, '0', {$mage_id_pro}, '{$value}')";
        try{
            $this->_write->query($query);
        }catch (LitExtension_CartMigration_Exception $e){
        }catch(Exception $e){
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
        $query = "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_tax_classes'";
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => $query,
        ));
        if(!$data || $data['result'] != 'success'){
            return array(
                'result' => 'success',
                'object' => array()
            );
        }
        $result = array(
            'result' => 'success',
        );
        $result['object'] = $this->_createTaxClassFromString($data['object'][0]['option_value'], 0);
        return $result;
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
        $query = "SELECT * FROM _DBPRF_term_taxonomy as tx
                          LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                          WHERE tx.taxonomy = 'product_cat' ORDER BY tx.term_id ASC";
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
        $query = "SELECT * FROM _DBPRF_posts WHERE post_type = 'product'
                            AND post_status NOT IN ('inherit','auto-draft') ORDER BY ID ASC";
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
        $query = "SELECT * FROM _DBPRF_posts
                          WHERE post_type = 'shop_order'
                            AND post_status = 'publish' ORDER BY ID ASC";
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
        $query = "SELECT cm.*, p.post_type, cm_meta.meta_value FROM _DBPRF_comments AS cm
                    LEFT JOIN _DBPRF_posts AS p ON p.ID = cm.comment_post_ID
                    LEFT JOIN _DBPRF_commentmeta AS cm_meta ON cm_meta.comment_id = cm.comment_id AND cm_meta.meta_key = 'rating'
                    WHERE p.post_type = 'product' ORDER BY cm.comment_ID ASC";
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