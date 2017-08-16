<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Wpecommercev38
    extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT * FROM _DBPRF_options AS opt WHERE option_name = 'wpec_taxes_bands'",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_term_taxonomy WHERE taxonomy = 'wpsc_product_category' AND term_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'wpsc-product' AND post_status = 'publish' AND post_parent = 0 AND ID > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_wpsc_purchase_logs WHERE id > {$this->_notice['orders']['id_src']}",
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this;
        }
        foreach($data['object'] as $type => $row){
            if($type == 'taxes'){
                $data_taxes = array();
                $count = 0;
                $un_taxes = unserialize($row['0']['option_value']);
                if(is_array($un_taxes)){
                    foreach($un_taxes as $taxes){
                        if(isset($taxes['index']) && $taxes['index'] >= $this->_notice['taxes']['id_src']){
                            $data_taxes[] = $taxes;
                        }
                    }
                }
                if(!empty($data_taxes)) $count = count($data_taxes);
            }else{
                $count = $this->arrayToCount($row);
            }
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
                                    LEFT JOIN _DBPRF_wpsc_currency_list AS cur ON cur.id = opt.option_value
                                    WHERE opt.option_name = 'currency_type'",
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
        $order_status_data = array(
            '1' => 'Incomplete Sale',
            '2' => 'Order Received',
            '3' => 'Accepted Payment',
            '4' => 'Job Dispatched',
            '5' => 'Closed Order',
            '6' => 'Payment Declined',
        );
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
                'taxes' => "SELECT * FROM _DBPRF_options AS opt WHERE option_name = 'wpec_taxes_bands'",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_term_taxonomy WHERE taxonomy = 'wpsc_product_category' AND term_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'wpsc-product' AND post_status = 'publish' AND post_parent = 0 AND ID > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_wpsc_purchase_logs WHERE id > {$this->_notice['orders']['id_src']}",
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $totals = array();
        $totals['manufacturers'] = 0;
        $totals['reviews'] = 0;
        foreach($data['object'] as $type => $row){
            if($type == 'taxes'){
                $data_taxes = array();
                $taxes_count = 0;
                $un_taxes = unserialize($row['0']['option_value']);
                if(is_array($un_taxes)){
                    foreach($un_taxes as $taxes){
                        if(isset($taxes['index']) && $taxes['index'] >= $this->_notice['taxes']['id_src']){
                            $data_taxes[] = $taxes;
                        }
                    }
                }
                if(!empty($data_taxes)) $taxes_count = count($data_taxes);
                $totals[$type] = $taxes_count;
            }else{
                $count = $this->arrayToCount($row);
                $totals[$type] = $count;
            }
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
    public function getTaxesMain(){
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_options WHERE option_name = 'wpec_taxes_bands'"
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $tax_rule = unserialize($data['object'][0]['option_value']);
        $tmp = array();
        if(is_array($tax_rule)){
            for($i=$id_src; $i<$limit+$id_src; $i++){
                if(isset($tax_rule[$i])){
                    $tmp[] = $tax_rule[$i];
                }
            }
        }
        $result = array();
        $result['result'] = 'success';
        $result['object'] = $tmp;
        return $result;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
        $ext_query = array(
            'tax_rates' => "SELECT * FROM _DBPRF_options WHERE option_name = 'wpec_taxes_rates'"
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
        return $tax['index'];
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
            $this->taxProductSuccess($tax['index'], $tax_pro_ipt['mage_id']);
        }
        $taxRates = unserialize($taxesExt['object']['tax_rates']['0']['option_value']);
        if(is_array($taxRates)){
            $taxRatesCook = $this->_cookTaxRates($taxRates, $tax);
            foreach($taxRatesCook as $tax_rate){
                if(!$tax_rate['country_code']){
                    continue ;
                }
                $tax_rate_data = array();
                $code = $tax['name'] . "-" . $tax_rate['country_code'];
                if($tax_rate['region_code']){
                    $code .= "-". $tax_rate['region_code'];
                }
                $tax_rate_data['code'] = $this->createTaxRateCode($code);
                $tax_rate_data['tax_country_id'] = $tax_rate['country_code'];
                if(empty($tax_rate['region_code']) || $tax_rate['region_code'] == 'all-markets'){
                    $tax_rate_data['tax_region_id'] = 0;
                } else {
                    $tax_rate_data['tax_region_id'] = $this->_getRegionIdByCode($tax_rate['region_code'], $tax_rate['country_code']);
                }
                $tax_rate_data['zip_is_range'] = 0;
                $tax_rate_data['tax_postcode'] = "*";
                $tax_rate_data['rate'] = $tax_rate['rate'];
                $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
                if($tax_rate_ipt['result'] == 'success'){
                    $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
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
        $query = "SELECT tr_tx.*, tr.* FROM _DBPRF_term_taxonomy AS tr_tx
                          LEFT JOIN _DBPRF_terms AS tr ON tr.term_id = tr_tx.term_id
                          WHERE tr_tx.taxonomy = 'wpsc_product_category' AND tr_tx.term_id > {$id_src}
                          ORDER BY tr_tx.term_id ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){
        $cat_ids = $this->duplicateFieldValueFromList($categories['object'], 'term_id');
        $catIds_in_query = $this->arrayToInCondition($cat_ids);
        $ext_query = array(
            'wp_meta' => "SELECT * FROM _DBPRF_wpsc_meta WHERE meta_key = 'image' AND object_id IN {$catIds_in_query}"
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
        $img_src = $this->getRowValueFromListByField($categoriesExt['object']['wp_meta'], 'object_id', $category['term_id'], 'meta_value');
        if($img_src && $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']), $img_src, 'catalog/category')){
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
        $query = "SELECT * FROM _DBPRF_posts WHERE post_type = 'wpsc-product' AND post_status = 'publish' AND post_parent = 0 AND ID > {$id_src} ORDER BY ID ASC LIMIT {$limit}";
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
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'terms_relationship' => "SELECT * FROM _DBPRF_term_relationships AS te_re,_DBPRF_term_taxonomy AS te_tx,_DBPRF_terms AS te
                                        WHERE te_re.object_id IN {$pro_ids_query}
                                        AND te_re.term_taxonomy_id = te_tx.term_taxonomy_id
                                        AND te_tx.taxonomy IN ('wpsc_product_category', 'product_tag')
                                        AND te.term_id = te_tx.term_id",
            'product_children' => "SELECT * FROM _DBPRF_posts AS p
                                WHERE p.post_type = 'wpsc-product'
                                AND p.post_status = 'inherit'
                                AND p.post_parent IN {$pro_ids_query}",
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
            $child_ids = $this->duplicateFieldValueFromList($productsExt['object']['product_children'], 'ID');
            $child_ids_query = $this->arrayToInCondition($child_ids);
            $all_pro_ids = array_unique(array_merge($productIds, $child_ids));
            $allProIdsQuery = $this->arrayToInCondition($all_pro_ids);
            $ext_rel_query = array(
                'options_products' => "SELECT * FROM _DBPRF_term_relationships AS te_rl,_DBPRF_term_taxonomy AS te_tx, _DBPRF_terms AS te
                                WHERE te_rl.object_id IN {$child_ids_query}
                                AND te_tx.term_taxonomy_id = te_rl.term_taxonomy_id
                                ANd te_tx.taxonomy = 'wpsc-variation'
                                AND te.term_id = te_tx.term_id",
                'post_meta' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$allProIdsQuery}"
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
                $opt_ids = $this->duplicateFieldValueFromList($productsExtRel['object']['options_products'], 'parent');
                $opt_ids_query = $this->arrayToInCondition($opt_ids);
                $thumbnail_list = $this->getListFromListByField($productsExtRel['object']['post_meta'], 'meta_key', '_thumbnail_id');
                $thumbnail_ids = array();
                if ($thumbnail_list) {
                    $thumbnail_ids = $this->duplicateFieldValueFromList($thumbnail_list, 'meta_value');
                }
                $gallery_list = $this->getListFromListByField($productsExtRel['object']['post_meta'], 'meta_key', '_wpsc_product_gallery');
                $gallery_ids = array();
                if ($gallery_list) {
                    foreach ($gallery_list as $gallery) {
                        $tmp = unserialize($gallery['meta_value']);
                        if (is_array($tmp)) {
                            $gallery_ids = array_merge($tmp, $gallery_ids);
                        }
                    }
                }
                $gallery_ids = array_unique($gallery_ids);
                $images_ids = array_unique(array_merge($thumbnail_ids, $gallery_ids));
                $images_ids_query = $this->arrayToInCondition($images_ids);
                $productExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize(array(
                        'attributes_options' => "SELECT * FROM _DBPRF_terms AS te WHERE te.term_id IN {$opt_ids_query}",
                        'images_products' => "SELECT * FROM _DBPRF_posts AS p
                                            LEFT JOIN _DBPRF_postmeta AS pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_attached_file'
                                            WHERE p.ID IN {$images_ids_query}"
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
        $sku = $this->getRowValueFromListByField($post_meta, 'meta_key', '_wpsc_sku', 'meta_value');
        if(!$sku){
            $sku = $product['post_name'];
        }
        $children_product = $this->getListFromListByField($productsExt['object']['product_children'], 'post_parent', $product['ID']);
        $term_relationship = $this->getListFromListByField($productsExt['object']['terms_relationship'], 'object_id', $product['ID']);
        $proCat = $this->getListFromListByField($term_relationship, 'taxonomy', 'wpsc_product_category');
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
        $pro_data['category_ids'] = $categories;
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        if($children_product){
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
        $pro_data = array_merge($this->_convertProduct($product, $productsExt, $post_meta),$pro_data);
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
     * Get data relation use for import customer
     *
     * @param array $customers : Data of function getCustomersMain
     * @return array : Response of connector
     */
    public function getCustomersExt($customers){
        $result = array(
            'result' => 'success'
        );
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'ID');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
                'user_meta' => "SELECT * FROM _DBPRF_usermeta
                                WHERE user_id IN {$customer_ids_query}
                                AND meta_key IN ('first_name','last_name','nickname','_wpsc_visitor_id', 'wp_capabilities')"
        );
        $cus_ext_query = $this->_custom->getCustomersExtQueryCustom($this, $customers);
        if($cus_ext_query){
            $ext_query = array_merge($ext_query, $cus_ext_query);
        }
        if($ext_query) {
            $customersExt = $this->_getDataImport($this->_getUrlConnector('query'), array(
                'serialize' => true,
                'query' => serialize($ext_query)
            ));
            if (!$customersExt || $customersExt['result'] != 'success') {
                return $this->errorConnector(true);
            }
            $visitor_list = $this->getListFromListByField($customersExt['object']['user_meta'], 'meta_key', '_wpsc_visitor_id');
            $visitor_ids_query = "('null')";
            if ($visitor_list) {
                $visitor_ids = $this->duplicateFieldValueFromList($visitor_list, 'meta_value');
                $visitor_ids_query = $this->arrayToInCondition($visitor_ids);
            }
            $ext_rel_query = array(
                'address_meta' => "SELECT * FROM _DBPRF_wpsc_visitor_meta
                                WHERE wpsc_visitor_id IN {$visitor_ids_query}
                                 AND meta_key IN ('billingfirstname','billinglastname','billingcountry','billingaddress','billingpostcode','billingcity','billingphone','billingregion','shippingfirstname','shippinglastname','shippingcountry','shippingaddress','shippingpostcode','shippingcity','shippingregion')"
            );
            $cus_ext_rel_query = $this->_custom->getCustomerExtRelQueryCustom($this, $customers, $customersExt);
            if ($cus_ext_rel_query) {
                $ext_rel_query = array_merge($ext_rel_query, $cus_ext_rel_query);
            }
            if ($ext_rel_query) {
                $customersExtRel = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize($ext_rel_query)
                ));
                if (!$customersExtRel || $customersExtRel['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $region_list = $this->_getListFromListByFieldInArray($customersExtRel['object']['address_meta'], 'meta_key', array('billingregion', 'shippingregion'));
                $region_ids_query = "('null')";
                if ($region_list) {
                    $region_ids = $this->duplicateFieldValueFromList($region_list, 'meta_value');
                    $region_ids_query = $this->arrayToInCondition($region_ids);
                }
                $customersExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize(array(
                        'region_tax' => "SELECT * FROM _DBPRF_wpsc_region_tax WHERE id IN {$region_ids_query}"
                    ))
                ));
                if (!$customersExtThird || $customersExtThird['result'] != 'success') {
                    return $this->errorConnector(true);
                }
                $result['object'] = array_merge($customersExt['object'], $customersExtRel['object'], $customersExtThird['object']);
            }
        }
        return $result;
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
        $cus_data['firstname'] = $first_name ? $first_name : ' ';
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
        $visitor_id = $this->getRowValueFromListByField($user_meta, 'meta_key', '_wpsc_visitor_id', 'meta_value');
        $cusAdd = $this->getListFromListByField($customersExt['object']['address_meta'], 'wpsc_visitor_id', $visitor_id);
        if($cusAdd){
            $address_billing = array();
            $address_billing['firstname'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingfirstname', 'meta_value');
            $address_billing['lastname'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billinglastname', 'meta_value');
            $address_billing['country_id'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingcountry', 'meta_value');
            $address_billing['street'][0] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingaddress', 'meta_value');
            $address_billing['postcode'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingpostcode', 'meta_value');
            $address_billing['city'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingcity', 'meta_value');
            $address_billing['telephone'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingphone', 'meta_value');
            $billing_region = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'billingregion', 'meta_value');
            if($billing_region){
                $billing_region_code = $this->getRowValueFromListByField($customersExt['object']['region_tax'], 'id', $billing_region, 'code');
                $billing_region_id = $this->_getRegionIdByCode($billing_region_code, $address_billing['country_id']);
                if($billing_region_id){
                    $address_billing['region_id'] = $billing_region_id;
                }else{
                    $address_billing['region'] = $billing_region;
                }
            }
            $customAddress = Mage::getModel('customer/address');
            $customAddress->setData($address_billing)
                ->setCustomerId($customer_mage_id)
                ->setIsDefaultBilling('1')
                ->setSaveInAddressBook('1');
            try {
                $customAddress->save();
            }
            catch (Exception $ex) {
            }

            $address_shipping = array();
            $address_shipping['firstname'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippingfirstname', 'meta_value');
            $address_shipping['lastname'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippinglastname', 'meta_value');
            $address_shipping['country_id'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippingcountry', 'meta_value');
            $address_shipping['street'][0] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippingaddress', 'meta_value');
            $address_shipping['postcode'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippingpostcode', 'meta_value');
            $address_shipping['city'] = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippingcity', 'meta_value');
            $address_shipping['telephone'] = $address_billing['telephone'];
            $shipping_region = $this->getRowValueFromListByField($cusAdd, 'meta_key', 'shippingregion', 'meta_value');
            if($shipping_region){
                $shipping_region_code = $this->getRowValueFromListByField($customersExt['object']['region_tax'], 'id', $shipping_region, 'code');
                $shipping_region_id = $this->_getRegionIdByCode($shipping_region_code, $address_shipping['country_id']);
                if($shipping_region_id){
                    $address_shipping['region_id'] = $shipping_region_id;
                }else{
                    $address_shipping['region'] = $shipping_region;
                }
            }
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

    /**
     * Query for get data use for import order
     *
     * @return string
     */
    protected function _getOrdersMainQuery(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_wpsc_purchase_logs WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
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
        $order_ids = $this->duplicateFieldValueFromList($orders['object'], 'id');
        $order_ids_query = $this->arrayToInCondition($order_ids);
        $user_ids = $this->duplicateFieldValueFromList($orders['object'], 'user_ID');
        $user_ids_query = $this->arrayToInCondition($user_ids);
        $ext_query = array(
            'submit_form_data' => "SELECT * FROM _DBPRF_wpsc_submited_form_data AS sf
                                LEFT JOIN _DBPRF_wpsc_checkout_forms AS cf ON cf.id = sf.form_id
                                WHERE sf.log_id IN {$order_ids_query}",
            'orders_products' => "SELECT cc.*,p.post_parent FROM _DBPRF_wpsc_cart_contents AS cc
                                LEFT JOIN _DBPRF_posts AS p ON cc.prodid = p.ID
                                WHERE cc.purchaseid IN {$order_ids_query}",
            'user_meta' => "SELECT * FROM _DBPRF_users AS u
                                LEFT JOIN _DBPRF_usermeta AS um ON um.user_id = u.ID
                                AND um.meta_key IN ('first_name','last_name')
                                WHERE u.ID IN {$user_ids_query}"
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
            $region_list = $this->_getListFromListByFieldInArray($ordersExt['object']['submit_form_data'], 'unique_name', array('billingstate', 'shippingstate'));
            $region_ids_query = "('null')";
            if ($region_list) {
                $region_ids = $this->duplicateFieldValueFromList($region_list, 'value');
                $region_ids_query = $this->arrayToInCondition($region_ids);
            }
            $pro_ids = $this->_duplicateMultiFieldValueFromList($ordersExt['object']['orders_products'], array('prodid', 'post_parent'));
            $pro_ids_query = $this->arrayToInCondition($pro_ids);
            $proChildIds = $this->duplicateFieldValueFromList($ordersExt['object']['orders_products'], 'prodid');
            $proChildIdsQuery = $this->arrayToInCondition($proChildIds);
            $ext_rel_query = array(
                'region_tax' => "SELECT * FROM _DBPRF_wpsc_region_tax WHERE id IN {$region_ids_query}",
                'post_data' => "SELECT * FROM _DBPRF_posts WHERE ID IN {$pro_ids_query}",
                'products_sku' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$pro_ids_query} AND meta_key = '_wpsc_sku'",
                'products_options' => "SELECT * FROM _DBPRF_term_relationships AS tr,_DBPRF_term_taxonomy AS tt,_DBPRF_terms AS t
                                WHERE tr.object_id IN {$proChildIdsQuery}
                                AND tt.term_taxonomy_id = tr.term_taxonomy_id
                                ANd tt.taxonomy = 'wpsc-variation'
                                AND t.term_id = tt.term_id"
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
                $opt_parent_ids = $this->duplicateFieldValueFromList($ordersExtRel['object']['products_options'], 'parent');
                $opt_parent_ids_query = $this->arrayToInCondition($opt_parent_ids);
                $ordersExtThird = $this->_getDataImport($this->_getUrlConnector('query'), array(
                    'serialize' => true,
                    'query' => serialize(array(
                        'options_parents' => "SELECT * FROM _DBPRF_terms WHERE term_id IN {$opt_parent_ids_query}"
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

        $address_billing['firstname'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingfirstname', 'value');
        $address_billing['lastname'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billinglastname', 'value');
        $address_billing['email'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingemail', 'value');
        $address_billing['street']  = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingaddress', 'value');
        $address_billing['city'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingcity', 'value');
        $address_billing['postcode'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingpostcode', 'value');
        $address_billing['country_id'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingcountry', 'value');
        $billing_region_value = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingstate', 'value');
        $billing_region_code = $this->getRowValueFromListByField($ordersExt['object']['region_tax'], 'id', $billing_region_value, 'code');
        $billing_region_id = $this->_getRegionIdByCode($billing_region_code, $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $billing_region_code ? $billing_region_code : $billing_region_value;
        }
        $address_billing['telephone'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingphone', 'value');

        $address_shipping['firstname'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippingfirstname', 'value');
        $address_shipping['lastname'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippinglastname', 'value');
        $address_shipping['email'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingemail', 'value');
        $address_shipping['street']  = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippingaddress', 'value');
        $address_shipping['city'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippingcity', 'value');
        $address_shipping['postcode'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippingpostcode', 'value');
        $address_shipping['country_id'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippingcountry', 'value');
        $shipping_region_value = $this->getRowValueFromListByField($submit_form, 'unique_name', 'shippingstate', 'value');
        $shipping_region_code = $this->getRowValueFromListByField($ordersExt['object']['region_tax'], 'id', $shipping_region_value, 'code');
        $shipping_region_id = $this->_getRegionIdByCode($shipping_region_code, $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $shipping_region_code ? $shipping_region_code : $shipping_region_value;
        }
        $address_shipping['telephone'] = $this->getRowValueFromListByField($submit_form, 'unique_name', 'billingphone', 'value');

        $orderPro = $this->getListFromListByField($ordersExt['object']['orders_products'], 'purchaseid', $order['id']);
        $carts = array();
        if($orderPro){
            foreach($orderPro as $order_pro){
                $cart = array();
                $data_pro = $this->getRowFromListByField($ordersExt['object']['post_data'], 'ID', $order_pro['prodid']);
                if($data_pro['post_parent'] > 0 && $data_pro['post_status'] == 'inherit'){
                    $product_id = $this->getMageIdProduct($data_pro['post_parent']);
                    if($product_id){
                        $cart['product_id'] = $product_id;
                    }
                    $product_parent = $this->getRowFromListByField($ordersExt['object']['post_data'], 'ID', $data_pro['post_parent']);
                    $cart['name'] = $product_parent['post_title'];
                    $cart['sku'] = $this->getRowValueFromListByField($ordersExt['object']['products_sku'], 'post_id', $product_parent['ID'], 'meta_value');
                    $product_options = $this->getListFromListByField($ordersExt['object']['products_options'], 'object_id', $order_pro['prodid']);
                    $cart['product_options'] = serialize($this->_createProductOrderOption($product_options, $ordersExt['object']['options_parents']));
                }else{
                    $product_id = $this->getMageIdProduct($order_pro['prodid']);
                    if($product_id){
                        $cart['product_id'] = $product_id;
                    }
                    $cart['name'] = $order_pro['name'];
                    $cart['sku'] = $this->getRowValueFromListByField($ordersExt['object']['products_sku'], 'post_id', $order_pro['prodid'], 'meta_value');
                }
                $cart['price'] = $order_pro['price'];
                $cart['original_price'] = $order_pro['price'];
                $cart['tax_amount'] = $order_pro['tax_charged'];
                $cart['qty_ordered'] = $order_pro['quantity'];
                $cart['row_total'] = $order_pro['price']*$order_pro['quantity'];
                $cart['tax_percent'] = ($cart['row_total'] != 0) ? round(($cart['tax_amount']/$cart['row_total'])*100, 2) : '';

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
        if($user_meta && !empty($user_meta)){
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
        $order_data['shipping_amount'] = $order['base_shipping'];
        $order_data['base_shipping_amount'] = $order['base_shipping'];
        $order_data['base_shipping_invoiced'] = $order['base_shipping'];
        $order_data['shipping_description'] = $order['shipping_method'];
        $order['tax_amount'] = $order['wpec_taxes_total'];
        $order['base_tax_amount'] = $order['wpec_taxes_total'];
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
        $order_status_data['comment'] = "<b>Reference order #".$order['id']."</b><br /><b>Payment method: </b>".$this->_createNamePaymentMethod($order['gateway'])."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order['notes'];
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
        $result = array();
        $result['result'] = 'success';
        $result['object'] = array();
        return $result;
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
    protected function _convertProduct($product, $productsExt, $product_meta){
        $pro_data = array();
        $product_metadata = unserialize($this->getRowValueFromListByField($product_meta, 'meta_key', '_wpsc_product_metadata', 'meta_value'));
        if(is_array($product_metadata)){
            if(isset($product_metadata['weight'])) $pro_data['weight'] = $product_metadata['weight'] ? $product_metadata['weight']*0.45359237 : 0;
            $pro_data['tax_class_id'] = 0;
            if(isset($product_metadata['wpec_taxes_taxable'])){
                if($product_metadata['wpec_taxes_taxable'] == 'on') $pro_data['tax_class_id'] = 0;
            }elseif(isset($product_metadata['wpec_taxes_band'])){
                if($product_metadata['wpec_taxes_band'] >= 0 && $product_metadata['wpec_taxes_band'] != 'Disabled' && $mage_tax_id = $this->getMageIdTaxProduct($product_metadata['wpec_taxes_band'])){
                    $pro_data['tax_class_id'] = $mage_tax_id;
                }
            }
            $tierPrices = array();
            if(isset($product_metadata['table_rate_price']) && isset($product_metadata['table_rate_price']['quantity']) && isset($product_metadata['table_rate_price']['table_price'])){
                foreach($product_metadata['table_rate_price']['quantity'] as $key => $tier_qty){
                    if(isset($product_metadata['table_rate_price']['table_price'][$key]) && $product_metadata['table_rate_price']['table_price'][$key]){
                        $tierPrices[] = array(
                            'website_id'  => 0,
                            'cust_group'  => 32000,
                            'price_qty'   => $tier_qty,
                            'price'       => $product_metadata['table_rate_price']['table_price'][$key],
                        );
                    }
                }
            }
            $pro_data['tier_price'] = $tierPrices;
        }
        $metaData = $this->_getListMetaProductFromProductMeta($product_meta);
        $meta_des = '';
        if($metaData){
            foreach($metaData as $meta){
                if($meta['meta_key'] && $meta['meta_value']){
                    $meta_des.= $meta['meta_key'].': '.$meta['meta_value']."\n";
                }
            }
        }
        $pro_data['meta_description'] = $meta_des;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['status'] = (in_array($product['post_status'],array('publish', 'inherit'))) ? 1 : 2;
        $pro_data['created_at'] = $product['post_date'];
        $pro_data['name'] = $product['post_title'];
        $pro_data['price'] = $this->getRowValueFromListByField($product_meta, 'meta_key', '_wpsc_price', 'meta_value');
        $pro_data['special_price'] = $this->getRowValueFromListByField($product_meta, 'meta_key', '_wpsc_special_price', 'meta_value');
        $qty = $this->getRowValueFromListByField($product_meta, 'meta_key', '_wpsc_stock', 'meta_value');
        $pro_data['stock_data'] = array(
            'is_in_stock' =>  1,
            'manage_stock' =>  ($this->_notice['config']['add_option']['stock'] && $qty < 1)? 0 : 1,
            'use_config_manage_stock' =>  ($this->_notice['config']['add_option']['stock'] && $qty <1)? 0 : 1,
            'qty' => ($qty > 0) ? $qty : 0,
        );
        $pro_data['description'] = $this->changeImgSrcInText($product['post_content'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['post_excerpt'], $this->_notice['config']['add_option']['img_des']);
        $thumbnail_id = $this->getRowValueFromListByField($product_meta, 'meta_key', '_thumbnail_id', 'meta_value');
        $img_src = $this->getRowFromListByField($productsExt['object']['images_products'], 'ID', $thumbnail_id);
        if($img_src && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $img_src['meta_value'], 'catalog/product', false, true)){
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => $img_src['post_title'] ? $img_src['post_title'] : ' ');
        }
        $gallery_ids = unserialize($this->getRowValueFromListByField($product_meta, 'meta_key', '_wpsc_product_gallery', 'meta_value'));
        if(is_array($gallery_ids)){
            foreach($gallery_ids as $image_id){
                $image_gallery_src = $this->getRowFromListByField($productsExt['object']['images_products'], 'ID', $image_id);
                if($image_gallery_src && $gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $image_gallery_src['meta_value'], 'catalog/product', false, true)){
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => $image_gallery_src['post_title'] ? $image_gallery_src['post_title'] : ' ');
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

    protected function _importChildrenProduct($parent_product, $children_product, $productsExt, $sku_parent){
        $result = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $dataChildes = $cfg_attr_data = array();
        foreach($children_product as $child){
            $cfg_pro_data = $convertPro = array();
            $options_attributes = $this->getListFromListByField($productsExt['object']['options_products'], 'object_id', $child['ID']);
            if($options_attributes){
                foreach($options_attributes as $option){
                    $cfgProDataTmp =  array();
                    $attr_label = $this->getRowValueFromListByField($productsExt['object']['attributes_options'], 'term_id', $option['parent'], 'name');
                    $attr_code = $this->joinTextToKey($attr_label, 27, '_');
                    $attr_import = $this->_makeAttributeImport($attr_label, $attr_code, $option['name'], $entity_type_id, $this->_notice['config']['attribute_set_id']);
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
                }
            }
            $post_meta = $this->getListFromListByField($productsExt['object']['post_meta'], 'post_id', $child['ID']);
            $sku = $this->getRowValueFromListByField($post_meta, 'meta_key', '_wpsc_sku', 'meta_value');
            if(!$sku || $sku == $sku_parent){
                $sku = $child['post_name'];
            }
            $convertPro['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $convertPro['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            $convertPro['category_ids'] = array();
            $convertPro['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
            $convertPro = array_merge($this->_convertProduct($child, $productsExt, $post_meta), $convertPro);
            $pro_import = $this->_process->product($convertPro);
            if($pro_import['result'] !== 'success'){
                return array(
                    'result' => 'warning',
                    'msg' => "Product ID = {$parent_product['ID']} import failed. Error: Error: Product children could not create!"
                );
            };
            if(!empty($cfg_pro_data)){
                foreach($cfg_pro_data as $dataAttribute){
                    $this->setProAttrSelect($entity_type_id, $dataAttribute['attribute_id'], $pro_import['mage_id'], $dataAttribute['value_index']);
                }
            }
            $dataChildes[$pro_import['mage_id']] = $cfg_pro_data;
        }
        if(!empty($dataChildes) && !empty($cfg_attr_data)) $result = $this->_createConfigProductData($dataChildes, $cfg_attr_data);
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
            'query' => "SELECT tr_tx.*, tr.*
                          FROM _DBPRF_term_taxonomy AS tr_tx
                            LEFT JOIN _DBPRF_terms AS tr ON tr.term_id = tr_tx.term_id
                          WHERE tr_tx.taxonomy = 'wpsc_product_category' AND tr_tx.term_id = {$parent_id}"
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

    protected function _cookTaxRates($tax_rates, $tax_rule){
        $result = array();
        foreach($tax_rates as $row){
            if(isset($row['country_code']) && $row['country_code'] == $tax_rule['country_code']){
                if($row['country_code'] == 'all-markets'){
                    $allCountry = $this->_getAllCountry($row['rate']);
                    $result = array_merge($allCountry,$result);
                }else{
                    $result[] = $row;
                }
                break;
            }
        }
        if($tax_rule['rate'] > 0){
            if($tax_rule['country_code'] == 'all-markets'){
                $allCountry = $this->_getAllCountry($tax_rule['rate']);
                $result = array_merge($allCountry,$result);
            }else{
                $result[] = $tax_rule;
            }
        }
        return $result;
    }

    protected function _getAllCountry($rate){
        $result = array();
        $country_list = Mage::getResourceModel('directory/country_collection')
            ->loadData()
            ->toOptionArray(false);
        foreach($country_list as $country){
            $tmp['country_code'] = $country['value'];
            $tmp['rate'] = $rate;
            $result[] = $tmp;
        }
        return $result;
    }

    protected function _getRegionIdByCode($region_code, $country_code){
        if($region_code && $country_code){
            $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
            $regionId = $regionModel->getId();
            if($regionId) return $regionId;
        }
        return false;
    }

    protected function _getListFromListByFieldInArray($list, $field, $multi_filed = array()){
        if(!$list){
            return false;
        }
        $result = array();
        foreach($list as $row){
            if(in_array($row[$field], $multi_filed)){
                $result[] = $row;
            }
        }
        return $result;
    }

    protected function _duplicateMultiFieldValueFromList($list, $field = array()){
        $result = array();
        foreach ($list as $item) {
            foreach($field as $key){
                if (isset($item[$key])) {
                    $result[] = $item[$key];
                }
            }
        }
        $result = array_unique($result);
        return $result;
    }

    protected function _createProductOrderOption($product_options, $options_parents){
        $result = array();
        if($product_options && !empty($product_options)){
            foreach($product_options as $row){
                $attribute = $this->getRowFromListByField($options_parents, 'term_id', $row['parent']);
                $option = array(
                    'label' => $attribute['name'],
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

    protected function _createNamePaymentMethod($code_payment){
        if($code_payment == 'wpsc_merchant_testmode') return 'Manual Payment';
        if($code_payment == 'chronopay') return 'ChronoPay';
        if($code_payment == 'wpsc_merchant_paypal_standard') return 'PayPal Payments Standard';
        if($code_payment == 'wpsc_merchant_paypal_express') return 'PayPal Express Checkout';
        if($code_payment == 'wpsc_merchant_paypal_pro') return 'PayPal Pro';
        return $code_payment;
    }

    protected function _getListMetaProductFromProductMeta($product_meta){
        if(!$product_meta){
            return false;
        }
        $result = array();
        foreach($product_meta as $row){
            if($row['meta_key'][0] != '_'){
                $result[] = $row;
            }
        }
        return $result;
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
        $query = "SELECT * FROM _DBPRF_options WHERE option_name = 'wpec_taxes_bands'";
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
            'object' => unserialize($data['object'][0]['option_value'])
        );
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
        $query = "SELECT tr_tx.*, tr.* FROM _DBPRF_term_taxonomy AS tr_tx
                          LEFT JOIN _DBPRF_terms AS tr ON tr.term_id = tr_tx.term_id
                          WHERE tr_tx.taxonomy = 'wpsc_product_category' ORDER BY tr_tx.term_id";
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
        $query = "SELECT * FROM _DBPRF_posts WHERE post_type = 'wpsc-product' AND post_status = 'publish' AND post_parent = 0 ORDER BY ID ASC";
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
        $query = "SELECT * FROM _DBPRF_wpsc_purchase_logs ORDER BY id ASC";
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