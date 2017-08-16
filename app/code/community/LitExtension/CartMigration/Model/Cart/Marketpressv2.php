<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Cart_Marketpressv2
    extends LitExtension_CartMigration_Model_Cart{

    //const IMPORT_ANY_ATTRIBUTE = true;

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'categories' => "SELECT COUNT(1) FROM _DBPRF_term_taxonomy WHERE taxonomy = 'product_category' AND term_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'product' AND post_status IN ('publish', 'trash') AND ID > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'mp_order' AND post_status NOT IN ('inherit','auto-draft') AND ID > {$this->_notice['orders']['id_src']}",
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this;
        }
        foreach($data['object'] as $type => $row){
            $count = $this->arrayToCount($row);
            $this->_notice[$type]['new'] = $count;
        }
        $this->_notice['taxes']['new'] = 1;
        return $this;
    }

    public function displayConfig(){
        $response = array();
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "setting" => "SELECT * FROM _DBPRF_options WHERE option_name = 'mp_settings'",
                "user_roles" => "SELECT * FROM _DBPRF_options WHERE option_name = 'wp_user_roles'",
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $this->_notice['config']['default_lang'] = 1;
        $settings = $obj['setting'][0]['option_value'];
        if($settings){
            $setting = unserialize($settings);
        }
        $this->_notice['config']['default_currency'] = $setting['currency'] ? $setting['currency'] : "USD";
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        
        $language_data['1'] = 'Default Language';
        
        $order_status_data = array(
            'order_received' => "Received",
            'order_paid' => "Paid",
            'order_shipped' => "Shipped",
            'order_closed' => "Closed",
            'trash' => "Trash"
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
                'categories' => "SELECT COUNT(1) FROM _DBPRF_term_taxonomy WHERE taxonomy = 'product_category' AND term_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'product' AND post_status IN ('publish', 'trash') AND ID > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE ID > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_posts WHERE post_type = 'mp_order' AND post_status NOT IN ('inherit','auto-draft') AND ID > {$this->_notice['orders']['id_src']}",
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
        $totals['taxes'] = 1;
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

//    public function configCurrency(){
//        parent::configCurrency();
//        $allowCur = $this->_notice['config']['currencies'];
//        $allow_cur = implode(',', $allowCur);
//        $this->_process->currencyAllow($allow_cur);
//        $default_cur = $this->_notice['config']['currencies'][$this->_notice['config']['default_currency']];
//        $this->_process->currencyDefault($default_cur);
//        $currencies = $this->_getDataImport($this->_getUrlConnector('query'), array(
//            'query' => "SELECT * FROM _DBPRF_options WHERE option_name = 'woocommerce_currency'"
//        ));
//        if($currencies && $currencies['result'] == 'success'){
//            $data = array();
//            foreach($currencies['object'] as $currency){
//                $currency_id = $currency['option_id'];
//                $currency_value = $currency['option_value'];
//                $currency_mage = $this->_notice['config']['currencies'][$currency_id];
//                $data[$currency_mage] = $currency_value;
//            }
//            $this->_process->currencyRate(array(
//                $default_cur => $data
//            ));
//        }
//        return ;
//    }

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
            'query' => "SELECT * FROM _DBPRF_options WHERE option_name = 'mp_settings'"
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
        return array();
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
        $tax_rate_data = array();
        $code = $tax['country_iso'] . "-" . $tax['state_iso'];
        $tax_rate_data['code'] = $this->createTaxRateCode($code);
        $tax_rate_data['tax_country_id'] = $tax['country_iso'];
        $tax_rate_data['tax_region_id'] = $this->_getRegionIdByCode($tax['state_iso'], $tax['country_iso']);
        $tax_rate_data['zip_is_range'] = 0;
        $tax_rate_data['tax_postcode'] = $tax['zip_code'];
        $tax_rate_data['rate'] = $tax['value'];
        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
        if ($tax_rate_ipt['result'] == 'success') {
            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
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
                          WHERE tx.taxonomy = 'product_category'
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
                            AND post_status IN ('publish', 'trash') AND ID > {$id_src} ORDER BY ID ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import product
     *
     * @param array $products : Data of function getProductsMain
     * @return array : Response of connector
     */
    public function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'ID');
        $pro_ids_con = $this->arrayToInCondition($productIds);
        $ext_query = array(
            "post_variant" => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$pro_ids_con}",
            "post_image" => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$pro_ids_con} AND meta_key = '_thumbnail_id'",
            "term_relationships" => "SELECT * FROM _DBPRF_term_relationships AS tr
                                LEFT JOIN _DBPRF_term_taxonomy AS tx ON tr.term_taxonomy_id = tx.term_taxonomy_id
                                LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                                    WHERE tr.object_id IN {$pro_ids_con}"
        );
        return $ext_query;
    }
    
     protected function _getProductsExtRelQuery($products, $productsExt) {
        $imageIds = $this->duplicateFieldValueFromList($productsExt['object']['post_image'], 'meta_value');
        $imageIds_query = $this->arrayToInCondition($imageIds);
        $ext_rel_query = array(
            'postmeta_image' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$imageIds_query}",
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
        $pro_data = array();
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $proVariants = $this->getListFromListByField($productsExt['object']['post_variant'], 'post_id', $product['ID']);
        $att = $this->getRowValueFromListByField($proVariants, 'meta_key', 'mp_var_name', 'meta_value');
        $getAttr = unserialize($att);
        $count = count($getAttr);
        if($count > 1){
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
    //////////////////////
    
    protected function _importChildrenProduct($product, $productsExt, $proVariants){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = $attrMage = array();
        $getAttr = $this->getRowValueFromListByField($proVariants, 'meta_key', 'mp_var_name', 'meta_value');
        $getSku = $this->getRowValueFromListByField($proVariants, 'meta_key', 'mp_sku', 'meta_value');
        $getPrice = $this->getRowValueFromListByField($proVariants, 'meta_key', 'mp_price', 'meta_value');
        $getStock = $this->getRowValueFromListByField($proVariants, 'meta_key', 'mp_inventory', 'meta_value');
        $getSaleprice = $this->getRowValueFromListByField($proVariants, 'meta_key', 'mp_sale_price', 'meta_value');
        $attrValue = unserialize($getAttr);
        $get_sku = unserialize($getSku);
        $get_price = unserialize($getPrice);
        $get_stock = unserialize($getStock);
        $get_saleprice = unserialize($getSaleprice);
        if(count($attrValue) > 1){
            foreach($attrValue as $k => $attr_value){
                $option_collection = '';
                $dataAttrVariants = $dataOpts = array();
                $tmp['attribute_name'] = "Variation Name";
                $tmp['attribute_option_value'] = $attr_value;
                $dataAttrVariants[] = $tmp;
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
                                'msg' => $this->consoleWarning("Product Id = {$product['ID']} import failed. Error: Product attribute could not create!")
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
                    $variant = array(
                        'sku' => $get_sku[$k],
                        'price' => $get_price[$k],
                        'status' => 1,
                        'stock' => $get_stock[$k],
                        'sale_price' => $get_saleprice[$k]
                    );
                    $data_variation = array(
                        'option_collection' => $option_collection,
                        'object' => $variant
                    );
                    $convertPro = $this->_convertProduct($product, $productsExt, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, true, $data_variation);
                    $pro_import = $this->_process->product($convertPro);
                    if ($pro_import['result'] !== 'success') {
                        return array(
                            'result' => "warning",
                            'msg' => $this->consoleWarning("Product Id = {$product['ID']} import failed. Error: Product children could not create!")
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
    
    ////////////////
    
    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array()){
        $pro_data = $categories = array();
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        
        $proCat = $this->getListFromListByField($productsExt['object']['term_relationships'], 'object_id', $product['ID']);
        $pro_cat = $this->getListFromListByField($proCat, 'taxonomy', 'product_category');
        if($pro_cat){
            foreach($pro_cat as $cat){
                $cat_id = $this->getMageIdCategory($cat['term_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['category_ids'] = $categories;
        if($is_variation_pro){
            $pro_data['name'] = $product['post_title'] . $data_variation['option_collection'];
            $pro_data['sku'] = $this->createProductSku($data_variation['object']['sku'], $this->_notice['config']['languages']);
            $pro_data['price'] = $data_variation['object']['price'] ? $data_variation['object']['price'] : 0;
            $pro_data['special_price'] = $data_variation['object']['sale_price'];
            $pro_data['special_from_date'] = "";
            $pro_data['special_to_date'] = "";
            $pro_data['status'] = 1;
            $pro_data['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['stock'] < 1)? 0 : 1,
                'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $data_variation['object']['stock'] < 1)? 0 : 1,
                'qty' => ($data_variation['object']['stock'] >= 0 )? $data_variation['object']['stock']: 0,
            );
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
        } else {
            $pro_data['name'] = $product['post_title'];
            $prodDesc = $this->getListFromListByField($productsExt['object']['post_variant'], 'post_id', $product['ID']);
            $getSku = unserialize($this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_sku', 'meta_value'));
            $getPrice = unserialize($this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_price', 'meta_value'));
            $getStock = unserialize($this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_inventory', 'meta_value'));
            $getSalePrice = unserialize($this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_sale_price', 'meta_value'));
            $data = $this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_file', 'meta_value');
            if($data){
                $pro_data['type_id'] = 'downloadable';
            }
            $pro_data['sku'] = $this->createProductSku($getSku[0], $this->_notice['config']['languages']);
            $pro_data['price'] = $getPrice[0];
            $pro_data['special_price'] = $getSalePrice[0];
            $pro_data['special_from_date'] = "";
            $pro_data['special_to_date'] = "";
            $pro_data['status'] = ($product['post_status'] == 'publish') ? 1 : 2;
            $track_inv = $this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_track_inventory', 'meta_value');
            $pro_data['stock_data'] = array(
                'is_in_stock' =>  1,
                'manage_stock' => ($track_inv == 0) ? 0 : 1,//($this->_notice['config']['add_option']['stock'] && $track_inv == 0) ? 0 : 1,
                'use_config_manage_stock' => ($track_inv == 0) ? 0 : 1,//($this->_notice['config']['add_option']['stock'] && $track_inv == 0) ? 0 : 1,
                'qty' => $getStock[0],
            );
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $image_id = $this->getRowValueFromListByField($productsExt['object']['post_image'], 'post_id', $product['ID'], 'meta_value');
            $imageInfo = $this->getListFromListByField($productsExt['object']['postmeta_image'], 'post_id', $image_id);
            $img = $this->getRowValueFromListByField($imageInfo, 'meta_key', '_wp_attached_file', 'meta_value');
            if($img){
                $path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $img, 'catalog/product', false, true);
                $pro_data['image_import_path'] = array('path' => $path, 'label' => '');
            }
        }
        $pro_data['description'] = $this->changeImgSrcInText($product['post_content'], $this->_notice['config']['add_option']['img_des']) ;
        $pro_data['short_description'] = "";
        $pro_data['meta_title'] = "";
        $pro_data['meta_keyword'] = "";
        $pro_data['meta_description'] = "";
        $pro_data['weight']   = "";
        $pro_data['tax_class_id'] = 0;
        $pro_data['created_at'] = $product['post_date'];
        //tags
        $pro_tags = $this->getListFromListByField($proCat, 'taxonomy', 'product_tag');
        if($pro_tags){
            foreach($pro_tags as $tag){
               $pro_data['tags'][] = $tag['slug'];
            }
        }
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
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt){
        if(parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)){
            return ;
        }
        //Downloadable
        $prodDesc = $this->getListFromListByField($productsExt['object']['post_variant'], 'post_id', $product['ID']);
        $link = $this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_file', 'meta_value');
        $get_price = unserialize($this->getRowValueFromListByField($prodDesc, 'meta_key', 'mp_price', 'meta_value'));
        if($link){
            $link_info = array();
            $link_info['is_shareable'] = 2;
            if(strpos($link, $this->_notice['config']['cart_url']) === false){
                $link_info['link_url'] = $link;//outer link use type 'url'
                $link_info['link_type'] = 'url';//'url'
            } else {
                $path = str_replace($this->_notice['config']['cart_url'] . '/wp-content/uploads', "", $link);
                $link_info['link_file'] = $path;
                $link_info['link_url'] = null;//outer link use type 'url'
                $link_info['link_type'] = 'file';//'url'
            }
            $link_arr = explode('/', $link);
            $title = array_pop($link_arr);
            $link_info['title'] = $title;
            $link_info['price'] = $get_price[0];
            $link_info['website_id'] = 0;
            $link_info['store_id'] = 0;
            $link_info['product_id'] = $product_mage_id;
            $this->_process->productDownloadLink($link_info);
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
            $shipping_info = unserialize($this->getRowValueFromListByField($user_meta, 'meta_key', 'mp_shipping_info', 'meta_value'));
            $billing_info = unserialize($this->getRowValueFromListByField($user_meta, 'meta_key', 'mp_billing_info', 'meta_value'));
            $address_billing['firstname'] = $billing_info['name'];
            $address_billing['lastname'] = "";
            $address_billing['country_id'] = $billing_info['country'];
            $address_billing['street'][0] = $billing_info['address1'];
            $address_billing['street'][1] = $billing_info['address2'];
            $address_billing['postcode'] = $billing_info['zip'];
            $address_billing['city'] = $billing_info['city'];
            $address_billing['telephone'] = $billing_info['phone'];
            $address_billing['company'] = "";
            if($billing_region_id = $this->getRegionId($billing_info['state'], $address_billing['country_id'])){
                $address_billing['region_id'] = $billing_region_id;
            }elseif($billing_region_id = $this->_getRegionIdByCode($billing_info['state'], $address_billing['country_id'])){
                $address_billing['region_id'] = $billing_region_id;
            }else{
                $address_billing['region'] = $billing_info['state'];
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
            $address_shipping['firstname'] = $shipping_info['name'];
            $address_shipping['lastname'] = "";
            $address_shipping['country_id'] = $shipping_info['country'];
            $address_shipping['street'][0] = $shipping_info['address1'];
            $address_shipping['street'][1] = $shipping_info['address2'];
            $address_shipping['postcode'] = $shipping_info['zip'];
            $address_shipping['city'] = $shipping_info['city'];
            $address_shipping['company'] = "";
            $state_ship = $shipping_info['state'];
            if($shipping_region_id = $this->getRegionId($state_ship, $address_shipping['country_id'])){
                $address_shipping['region_id'] = $shipping_region_id;
            }elseif($shipping_region_id = $this->_getRegionIdByCode($state_ship, $address_shipping['country_id'])){
                $address_shipping['region_id'] = $shipping_region_id;
            }else{
                $address_shipping['region'] = $state_ship;
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
                        WHERE post_type = 'mp_order' AND post_status NOT IN ('inherit','auto-draft') AND ID > {$id_src} ORDER BY ID ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    public function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'], 'ID');
        $orderIds_query = $this->arrayToInCondition($orderIds);
        $ext_query = array(
            'order_meta' => "SELECT * FROM _DBPRF_postmeta WHERE post_id IN {$orderIds_query}"
        );
        return $ext_query;
    }


    protected function _getOrdersExtRelQuery($orders, $ordersExt)
    {
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
        $getDataOrder = $this->getListFromListByField($ordersExt['object']['order_meta'], 'post_id', $order['ID']);
        $shippingInfo = unserialize($this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_shipping_info', 'meta_value'));
        $address_billing['firstname'] = $shippingInfo['name'];
        $address_billing['lastname'] = "";
        $address_billing['company'] = "";
        $address_billing['email'] = $shippingInfo['email'];
        $address_billing['street'] = $shippingInfo['address1'] . "\n" . $shippingInfo['address2'];
        $address_billing['city'] = $shippingInfo['city'];
        $address_billing['postcode'] = $shippingInfo['zip'];
        $address_billing['country_id'] = $shippingInfo['country'];
        $address_billing['telephone'] = $shippingInfo['phone'];
        $bill_region = $shippingInfo['state'];
        if($bill_region_id = $this->getRegionId($bill_region, $address_billing['country_id'])){
            $address_billing['region_id'] = $bill_region_id;
        }elseif($bill_region_id = $this->_getRegionIdByCode($bill_region, $address_billing['country_id'])){
            $address_billing['region_id'] = $bill_region_id;
        }else{
            $address_billing['region'] = $bill_region;
        }
        $address_billing['save_in_address_book'] = true;

        $address_shipping['firstname'] = $shippingInfo['name'];
        $address_shipping['lastname'] = "";
        $address_shipping['company'] = "";
        $address_shipping['street'] = $shippingInfo['address1'] . "\n" . $shippingInfo['address2'];
        $address_shipping['city'] = $shippingInfo['city'];
        $address_shipping['postcode'] = $shippingInfo['zip'];
        $address_shipping['country_id'] = $shippingInfo['country'];
        $ship_region = $shippingInfo['state'];
        if($ship_region_id = $this->getRegionId($ship_region, $address_shipping['country_id'])){
            $address_shipping['region_id'] = $ship_region_id;
        }elseif($ship_region_id = $this->_getRegionIdByCode($ship_region, $address_shipping['country_id'])){
            $address_shipping['region_id'] = $ship_region_id;
        }else{
            $address_shipping['region'] = $ship_region;
        }
        $address_shipping['save_in_address_book'] = true;

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $customer_mage_id = $this->getMageIdCustomer($order['post_author']);

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_mage_id){
            $order_data['customer_id'] = $customer_mage_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $shippingInfo['email'] ;
        $order_data['customer_firstname'] = $shippingInfo['name'];
        $order_data['customer_lastname'] = "";
        $order_data['status'] = $this->_notice['config']['order_status'][$order['post_status']];
        $order_data['state'] =  $this->getOrderStateByStatus($order_data['status']);
        
        $order_items = unserialize($this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_cart_info', 'meta_value'));
        $order_subtotal = 0;
        $carts = array();
        foreach($order_items as $k => $item){
            if(!isset($item['name'])){
                foreach ($item as $item_variant){
                    $cart = array();
                    $product_mage_id = $this->getMageIdProduct($k);
                    if($product_mage_id){
                        $cart['product_id'] = $product_mage_id;
                    }
                    $cart['name'] = $item_variant['name'];
                    $cart['sku'] = $item_variant['SKU'];
                    $cart['qty_ordered'] = $item_variant['quantity'];
                    $cart['original_price'] = $item_variant['price'];
                    $cart['price'] = $item_variant['price'];
                    $total = $cart['price'] * $cart['qty_ordered'];
                    $cart['tax_amount'] = "";//$item_variant['price'] - $item_variant['before_tax_price'];
                    $cart['tax_percent'] = "";//$cart['tax_amount']/$total *100;
                    $cart['row_total'] = $total;
                    $order_subtotal = $order_subtotal + $total;
                    $carts[]= $cart;
                }
            }else{
                $cart = array();
                $product_mage_id = $this->getMageIdProduct($k);
                if($product_mage_id){
                    $cart['product_id'] = $product_mage_id;
                }
                $cart['name'] = $item['name'];
                $cart['sku'] = $item['SKU'];
                $cart['qty_ordered'] = $item['quantity'];
                $cart['original_price'] = $item['price'];
                $cart['price'] = $item['price'];
                $total = $cart['price'] * $cart['qty_ordered'];
                $cart['tax_amount'] = "";//$item['price'] - $item['before_tax_price'];
                $cart['tax_percent'] = "";//$cart['tax_amount']/$total *100;
                $cart['row_total'] = $total;
                $order_subtotal = $order_subtotal + $total;
                $carts[]= $cart;
            }
        }
        $order_data['subtotal'] = $order_subtotal;
        $discountInfo = unserialize($this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_discount_info', 'meta_value'));
        $discountAmount = $discountInfo['discount'];
        $discount_value = trim($discountAmount, '-%');
        $discount = $order_subtotal * $discount_value / 100;
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_shipping_total', 'meta_value');
        $order_data['tax_amount'] = $this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_tax_total', 'meta_value');
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $discount;
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_order_total', 'meta_value');
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
        $getDataOrder = $this->getListFromListByField($ordersExt['object']['order_meta'], 'post_id', $order['ID']);
        if($getDataOrder){
            $order_data['status'] = $order['post_status'];
            if($order_data['status']){
                $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
            }
            $payment_info = unserialize($this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_payment_info', 'meta_value'));
            $shipping_info = unserialize($this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_shipping_info', 'meta_value'));
            $cmt = $this->getRowValueFromListByField($getDataOrder, 'meta_key', 'mp_order_notes', 'meta_value');
            $order_data['comment'] = "<b>Reference order #".$order['ID']."<br /><b>Shipping method: </b> ".$shipping_info['method']."</b><br /><b>Payment Gateway: </b>".$payment_info['gateway_public_name']."<br /><b>Payment Type: </b> ".$payment_info['method']."<br /><br />".$cmt;
            $order_data['updated_at'] = $order['post_date'];
            $order_data['created_at'] = $order['post_modified'];
            $this->_process->ordersComment($order_mage_id, $order_data);
        }
    }

    /**
     * Query for get main data use for import review
     *
     * @return string
     */
    protected function _getReviewsMainQuery(){
        return array();
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
        return $review['ID'];
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
        $data = array();
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

###################################### Extend Function #################################################################

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

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $categories = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_term_taxonomy as tx
                          LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                          WHERE tx.taxonomy = 'product_category' AND tx.term_id = {$parent_id}"
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
        $tax_classessss = unserialize($tax_class);
        //var_dump($tax_classessss);exit;
        $tax_classes = $tax_classessss['tax'];
        if($tax_classes & is_array($tax_classes)){
            $id++;
            $value = $tax_classes['rate'];
            $label = $tax_classes['label'];
            $response[] = array(
                'id' => $id ,
                'value' => $value, 
                'label' => $label,
                'country_iso' => $tax_classessss['base_country'],
                'state_iso' => $tax_classessss['base_province'],
                'zip_code' => $tax_classessss['base_zip']
            );
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

    protected function _getRegionIdByCode($region_code, $country_code){
        $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
        $regionId = $regionModel->getId();
        if($regionId) return $regionId;
        return false;
    }

}