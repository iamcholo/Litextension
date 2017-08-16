<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Cart_Drupalcart1x
    extends LitExtension_CartMigration_Model_Cart{

    const IMPORT_ANY_ATTRIBUTE = true;

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $status_not_select = array('cart','checkout_checkout', 'checkout_review', 'checkout_payment', 'checkout_complete');
        $status_not_select = $this->arrayToInCondition($status_not_select);
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT * FROM _DBPRF_commerce_tax_rate ",//WHERE tax_id > {$this->_notice['taxes']['id_src']}
//                'categories' => "SELECT COUNT(1) FROM _DBPRF_taxonomy_term_data WHERE tid > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_commerce_product WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE uid > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_commerce_order WHERE status NOT IN {$status_not_select} AND order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_field_data_comment_body WHERE  bundle = 'comment_node_product' AND entity_id > {$this->_notice['reviews']['id_src']}"

            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this;
        }
        return $this;
    }

    public function displayConfig(){
        $response = array();
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT * FROM _DBPRF_variable WHERE name = 'commerce_default_currency'",
//                "orders_status" => "SELECT * FROM _DBPRF_uc_order_statuses",
                "user_roles" => "SELECT * FROM _DBPRF_role",
            ))
        ));
        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }
        $obj = $data['object'];
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['default_currency'] = isset($obj['currencies'][0]['value']) ? $obj['currencies'][0]['value'] : 1;
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = $customer_group_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data['1'] = 'Default Language';
        if($obj['currencies'] ){
            foreach ($obj['currencies'] as $currency_row) {
                $currency_name = $currency_row['name'];
                $currency_data[$currency_name] = unserialize($currency_row['value']);
            }

        }

//        if(!empty($obj['orders_status'])){
//            foreach ($obj['orders_status'] as $order_status_row) {
//                $order_status_id = $order_status_row['order_status_id'];
//                $order_status_name = $order_status_row['title'];
//                $order_status_data[$order_status_id] = $order_status_name;
//            }
//        }else{
            $order_status_data = array(
                'canceled'       => 'Canceled',
                'cart'           => 'Shopping cart',
                'completed'      => 'Completed',
                'checkout_checkout'    => 'Checkout: Checkout',
                'checkout_review'  => 'Checkout: Review',
                'checkout_payment' => 'Checkout: Payment',
                'checkout_complete'  => 'Checkout: Complete',
                'pending'        => 'Pending',
                'processing'     => 'Processing',
            );
//        }
        if(isset($obj['user_roles'][0])){
            $userRoles = $obj['user_roles'];
            if(is_array($userRoles)){
                foreach($userRoles as $userRole){
                    $user_roles_value = $userRole['rid'];
                    $user_roles_name = $userRole['name'];
                    if($user_roles_name != 'administrator'){
                        $customer_group_data[$user_roles_value] = $user_roles_name;
                    }
                }
            }
        }
        $this->_notice['config']['import_support']['categories'] = false;
        $this->_notice['config']['import_support']['reviews'] = false;
        $this->_notice['config']['import_support']['manufacturers'] = false;
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
            $types = array('taxes', 'products', 'customers', 'orders', 'reviews');// 'categories',
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
        $status_not_select = array('cart','checkout_checkout', 'checkout_review', 'checkout_payment', 'checkout_complete');
        $status_not_select = $this->arrayToInCondition($status_not_select);
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_commerce_tax_rate",
//                'categories' => "SELECT COUNT(1) FROM _DBPRF_taxonomy_term_data WHERE tid > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_commerce_product WHERE product_id > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_users WHERE uid > {$this->_notice['customers']['id_src']} AND uid NOT IN (SELECT uid FROM _DBPRF_users_roles WHERE rid = '3')",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_commerce_order WHERE status NOT IN {$status_not_select} AND order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_field_data_comment_body WHERE entity_id > {$this->_notice['reviews']['id_src']}"
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
            'query' => "SELECT * FROM _DBPRF_variable WHERE name = 'commerce_default_currency'"
        ));
        if($currencies && $currencies['result'] == 'success'){
            $data = array();
            foreach($currencies['object'] as $currency){var_dump($currency);exit();
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
    }

    /**
     * Get data of table convert to tax rule
     *
     * @return array : Response of connector
     */
    public function getTaxesMain(){
        $imported = $this->_notice['taxes']['imported'];

        $taxes = array();
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $query =  $this->_getDataImport($this->_getUrlConnector('query'), array(
            'query' => "SELECT * FROM _DBPRF_commerce_tax_rate ORDER BY name ASC LIMIT {$imported},{$limit}"
        ));
        if(!$query || $query['result'] != 'success'){
            return $this->errorConnector(true);
        }
        $taxes = $query;
        return $taxes;
    }

    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */
    protected function _getTaxesExtQuery($taxes){
//
//        $tax_values = $this->duplicateFieldValueFromList($taxes['object'], 'id');
//        $tax_values_con = $this->arrayToInCondition($tax_values);
//        $ext_query = array(
//            'tax_rates' => "SELECT * FROM commerce_tax_rate WHERE id IN {$tax_values_con}"
//        );
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

//        $taxRateIds = $this->duplicateFieldValueFromList($taxesExt['object']['tax_rates'], 'id');
//        $tax_zone_con = $this->arrayToInCondition($taxRateIds);
//        $ext_rel_query = array(
//            'tax_rates_location' => "SELECT * FROM _DBPRF_uc_taxes WHERE id IN {$tax_zone_con}"
//        );
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
        return null;
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
            $this->taxProductSuccess(1, $tax_pro_ipt['mage_id'],$tax['name']);
        }


        $tax_rate_data = array();
        $code = $tax['name'];

        $tax_rate_data['code'] = $this->createTaxRateCode($code);

        $tax_rate_data['tax_region_id'] = 0;
        $tax_rate_data['zip_is_range'] = 0;
        $tax_rate_data['tax_postcode'] = "*";
        $tax_rate_data['rate'] = $tax['rate'];
        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
        if($tax_rate_ipt['result'] == 'success'){
            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
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
     * Import tax with data convert of function convertTax
     *
     * @param array $data : Data of function convertTax
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return array
     */
    public function importTax($data, $tax, $taxesExt){
        if(LitExtension_CartMigration_Model_Custom::TAX_IMPORT){
            return $this->_custom->importTaxCustom($this, $data, $tax, $taxesExt);
        }
        $id_src = $this->getTaxId($tax, $taxesExt);
        $taxIpt = $this->_process->taxRule($data);
        if($taxIpt['result'] == 'success'){
            $id_desc = $taxIpt['mage_id'];
            $this->taxSuccess(1, $id_desc,$tax['name']);
        } else {
            $taxIpt['result'] = 'warning';
            $msg = "Tax Id = {$tax['name']} import failed. Error: " . $taxIpt['msg'];
            $taxIpt['msg'] = $this->consoleWarning($msg);
        }
        return $taxIpt;
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
        $query = "SELECT * FROM _DBPRF_taxonomy_term_data as td
                          LEFT JOIN _DBPRF_taxonomy_term_hierarchy AS th ON td.tid = th.tid
                          WHERE td.tid > {$id_src} ORDER BY td.tid ASC LIMIT {$limit}";
        return array();
    }

    /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    protected function _getCategoriesExtQuery($categories){

        $categoryIds = $this->duplicateFieldValueFromList($categories['object'], 'tid');
        $cat_id_con = $this->arrayToInCondition($categoryIds);

        $ext_query = array(
            'categories_images' => "SELECT uc.*, fm.* FROM _DBPRF_field_data_uc_catalog_image AS uc
                                      LEFT JOIN _DBPRF_file_managed AS fm ON uc.uc_catalog_image_fid = fm.fid
                                    WHERE uc.entity_id IN {$cat_id_con}",
            "url_alias" => "SELECT * FROM `url_alias`"
        );
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
        return null;//$category['tid']
    }

    /**
     * Convert source data to data import
     *
     * @param array $category : One row of object in function getCategoriesMain
     * @param array $categoriesExt : Data of function getCategoriesExt
     * @return array
     */
    public function convertCategory($category, $categoriesExt){
        return array();
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
                        'msg' => $this->consoleWarning("Category Id = {$category['tid']} import failed. Error: Could not import parent category id = {$category['parent']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['name'] ? $category['name'] : " ";
        $cat_data['description'] = $category['description'];
        $category_image = $this->getRowValueFromListByField($categoriesExt['object']['categories_images'], 'entity_id', $category['tid'], 'filename');

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
        $query = "SELECT * FROM _DBPRF_commerce_product WHERE product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
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
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'product_id');
        $pro_ids_con = $this->arrayToInCondition($productIds);
        $ext_query = array(
            "products_price" => "SELECT * FROM _DBPRF_field_data_commerce_price as fdcp WHERE fdcp.entity_id IN {$pro_ids_con}",
            "url_alias" => "SELECT * FROM `url_alias`"
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
            $result['object'] = $productsExt['object'];

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
        $pro_data = $categories = $tags = array();

        $sku = $product['sku'];

        if(!$sku){
            $sku = $product['title'];
        }
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $commerce_price_data = unserialize($this->getRowValueFromListByField($productsExt['object']['products_price'], 'entity_id', $product['product_id'], 'commerce_price_data'));
        if(isset($commerce_price_data['include_tax'])){
            $pro_data['tax_class_id'] = $this->_getMageIdTaxProductByValue($commerce_price_data['include_tax']);
        }else{
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
        $pro_data = array_merge($this->_convertProduct($product, $productsExt, null), $pro_data);

        $pro_data['price'] = $this->getRowValueFromListByField($productsExt['object']['products_price'], 'entity_id', $product['product_id'], 'commerce_price_amount')/100;

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
    }

    /**
     * Query for get data of main table use for import customer
     *
     * @return string
     */
    protected function _getCustomersMainQuery(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $query = "SELECT * FROM _DBPRF_users WHERE uid > {$id_src} and uid NOT IN (SELECT uid FROM _DBPRF_users_roles WHERE rid = '3') ORDER BY uid ASC LIMIT {$limit}";
        return $query;
    }

    /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */
    protected function _getCustomersExtQuery($customers){

        $cus_ids = $this->duplicateFieldValueFromList($customers['object'], 'uid');
        $cus_ids_con = $this->arrayToInCondition($cus_ids);
        $ext_query = array(
            'user_profiles' => "SELECT * FROM _DBPRF_commerce_customer_profile WHERE uid IN {$cus_ids_con}",
            'customer_address' => "SELECT * FROM _DBPRF_field_data_commerce_customer_address",
            "user_roles" => "SELECT * FROM _DBPRF_role WHERE rid != '3'",
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

        $data = $this->getListFromListByField($customersExt['object']['user_profiles'], 'uid', $customer['uid']);
        $revision_ids = $this->duplicateFieldValueFromList($data, 'revision_id');
        $revision_id = max($revision_ids);
        $address = $this->getRowFromListByField($customersExt['object']['customer_address'],'revision_id',$revision_id);

        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['mail'];
        $cus_data['firstname'] = $address['commerce_customer_address_first_name'];
        $cus_data['lastname'] = $address['commerce_customer_address_last_name'];
        $cus_data['created_at'] = $customer['created'] ? date('Y-m-d h:i:s',$customer['created']) : '';
        $cus_data['group_id'] = 1;


        if(is_array($customersExt['object']['user_roles'])){
            foreach($customersExt['object']['user_roles'] as $key => $user_role_data){
                if(isset($this->_notice['config']['customer_group'][$user_role_data['rid']])){
                    $cus_data['group_id'] = $this->_notice['config']['customer_group'][$user_role_data['rid']];
                }
            }
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
        $this->_importCustomerRawPass($customer_mage_id, $customer['pass']);
        $customAddress = Mage::getModel('customer/address');
        $data = $this->getListFromListByField($customersExt['object']['user_profiles'], 'uid', $customer['uid']);
        $revision_ids = $this->duplicateFieldValueFromList($data, 'revision_id');
        $revision_id = max($revision_ids);
        $customer_address = $this->getRowFromListByField($customersExt['object']['customer_address'],'revision_id',$revision_id);
        $address_billing = array();
        $address_billing['firstname'] = $customer_address['commerce_customer_address_first_name'];
        $address_billing['lastname'] = $customer_address['commerce_customer_address_last_name'];
        $address_billing['country_id'] = $customer_address['commerce_customer_address_country'];
        $address_billing['street'][0] = $customer_address['commerce_customer_address_thoroughfare'];
        $address_billing['street'][1] = $customer_address['commerce_customer_address_premise'];
        $address_billing['postcode'] = $customer_address['commerce_customer_address_postal_code'];
        $address_billing['city'] = $customer_address['commerce_customer_address_locality'];
        $address_billing['telephone'] = '';
        $address_billing['company'] = '';
        $billing_region = $customer_address['commerce_customer_address_administrative_area'];
        if($billing_region){
            if($billing_region_id = $this->getRegionId($customer_address['commerce_customer_address_administrative_area'], $address_billing['country_id'])){
                $address_billing['region_id'] = $billing_region_id;
            }elseif($billing_region_id = $this->_getRegionIdByCode($billing_region, $address_billing['country_id'])){
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


    }

    /**
     * Query for get data use for import order
     *
     * @return string
     */
    protected function _getOrdersMainQuery(){
        $status_not_select = array('cart','checkout_checkout', 'checkout_review', 'checkout_payment', 'checkout_complete');
        $status_not_select = $this->arrayToInCondition($status_not_select);
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM _DBPRF_commerce_order
                          WHERE status NOT IN {$status_not_select} AND order_id > {$id_src} ORDER BY order_id ASC LIMIT {$limit}";//
        return $query;
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */
    public function getOrdersExt($orders)
    {
        $result = array(
            'result' => 'success'
        );
        $revision_ids = $this->duplicateFieldValueFromList($orders['object'], 'revision_id');
        $revision_ids_con = $this->arrayToInCondition($revision_ids);
        $order_ids = $this->duplicateFieldValueFromList($orders['object'], 'order_id');
        $order_ids_con = $this->arrayToInCondition($order_ids);//co.order_id ,LEFT JOIN _DBPRF_commerce_order AS co ON co.order_id = cb.entity
        $ext_query = array(
            'order_total' => "SELECT * FROM _DBPRF_field_data_commerce_order_total WHERE entity_id IN {$order_ids_con}",
            'line_items' => "SELECT * FROM _DBPRF_commerce_line_item WHERE order_id IN {$order_ids_con}",
            'order_revision' => "SELECT * FROM _DBPRF_commerce_order_revision WHERE revision_id IN {$revision_ids_con}",
            'customer_billing' => "SELECT ca.*,cb.revision_id FROM _DBPRF_field_data_commerce_customer_billing AS cb
                            LEFT JOIN _DBPRF_field_data_commerce_customer_address AS ca on cb.commerce_customer_billing_profile_id = ca.entity_id
                            WHERE cb.revision_id IN {$revision_ids_con}",
            'products'  =>  "SELECT * FROM _DBPRF_commerce_product",
            'product_price'  =>  "SELECT * FROM _DBPRF_field_data_commerce_price",

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

        }
        $result['object'] = $ordersExt['object'];
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
        $data = array();
        $billing_data  = $this->getRowFromListByField($ordersExt['object']['customer_billing'],'revision_id',$order['revision_id']);

        $address_billing['firstname'] =$billing_data['commerce_customer_address_first_name'];
        $address_billing['lastname'] =$billing_data['commerce_customer_address_last_name'];
        $address_billing['company'] = ' ';
        $address_billing['email'] = $order['mail'];
        $address_billing['street'] = $billing_data['commerce_customer_address_thoroughfare']."\n".$billing_data['commerce_customer_address_premise'];
        $address_billing['city'] = $billing_data['commerce_customer_address_locality'];
        $address_billing['postcode'] = $billing_data['commerce_customer_address_postal_code'];
        $address_billing['country_id'] =$billing_data['commerce_customer_address_country'];
        $address_billing['telephone'] = ' ';
        $address_billing['region_id'] = $billing_data['commerce_customer_address_administrative_area'];
        $address_billing['save_in_address_book'] = true;
        $address_shipping['firstname'] = $billing_data['commerce_customer_address_first_name'];
        $address_shipping['lastname'] = $billing_data['commerce_customer_address_last_name'];
        $address_shipping['company'] = ' ';
        $address_shipping['street'] = $billing_data['commerce_customer_address_thoroughfare']."\n".$billing_data['commerce_customer_address_premise'];
        $address_shipping['city'] = $billing_data['commerce_customer_address_locality'];
        $address_shipping['postcode'] = $billing_data['commerce_customer_address_postal_code'];
        $address_shipping['country_id'] = $billing_data['commerce_customer_address_country'];
        $address_shipping['region_id'] =  $billing_data['commerce_customer_address_administrative_area'];
        $address_shipping['save_in_address_book'] = true;

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $customer_mage_id = $this->getMageIdCustomer($order['uid']);
        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_mage_id){
            $order_data['customer_id'] = $customer_mage_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['mail'];
        $order_data['customer_firstname'] =  $billing_data['commerce_customer_address_first_name'];
        $order_data['customer_lastname'] =  $billing_data['commerce_customer_address_last_name'];

        $order_data['status'] = $this->_notice['config']['order_status'][$order['status']];
        $order_data['state'] =  $this->getOrderStateByStatus($order_data['status']);

        $order_items = $this->getListFromListByField($ordersExt['object']['line_items'], 'order_id', $order['order_id']);
        $order_subtotal = 0;
        $carts = array();
        foreach($order_items as $item){
            $cart = array();
            $data = unserialize($item['data']);

            if(isset($data['context']) && $product_mage_id = $this->getMageIdProduct($data['context']['product_ids'][0])){
                $cart['product_id'] = $product_mage_id;
            }else{
                continue;
            }

            $cart['name'] = $this->getRowValueFromListByField($ordersExt['object']['products'],'sku',$item['line_item_label'],'title');

            $cart['sku'] = $item['line_item_label'];
            $cart['qty_ordered'] = $item['quantity'];
            $subtotal = $item['quantity']*$this->getRowValueFromListByField($ordersExt['object']['product_price'],'entity_id',$data['context']['product_ids'][0],'commerce_price_amount');
            $total = $subtotal;
            $cart['original_price'] = $subtotal/$cart['qty_ordered'];
            $cart['price'] = $this->getRowValueFromListByField($ordersExt['object']['product_price'],'entity_id',$data['context']['product_ids'][0],'commerce_price_amount');
//            $cart['tax_amount'] = 0;
//            $cart['tax_percent'] = ($total != 0) ? $cart['tax_amount']/$total *100 : 0;
            $cart['row_total'] = $total;
            $order_subtotal = $order_subtotal + $total;

//            $product_options = $data['attributes'];

//           if($productType == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE){
//                if(count($product_options)){
//                    $product_opt = array();
//                    foreach($product_options as $label => $option){
////                        $aid = $this->getRowValueFromListByField($ordersExt['object']['attributes'],'label',$label,'aid');
//                        foreach($option as $opId => $op_name){
//                            $opt_data = array(
//                                'label' => isset($label) ? $label : " ",
//                                'value' => isset($op_name) ? $op_name : " ",
//                                'print_value' => isset($op_name) ? $op_name : " ",
//                                'option_id' => 'option_' . $opId,
//                                'option_type' => 'drop_down',
//                                'option_value' => 0,
//                                'custom_view' => false
//                            );
//                            $product_opt[] = $opt_data;
//                        }
//                    }
//                    $cart['product_options'] = serialize(array('options' => $product_opt));
//                }
//            }
            $carts[]= $cart;

//            if($item['order_item_type'] == 'shipping'){
//                $ship_meta = $this->getListFromListByField($ordersExt['object']['order_items_meta'], 'order_item_id', $item['order_item_id']);
//                if($ship_meta){
//                    $order_data['shipping_description'] = $item['order_item_name'];
//                    $order_data['shipping_amount'] = $this->getRowValueFromListByField($ship_meta, 'meta_key', 'cost', 'meta_value');
//                    $order_data['base_shipping_amount'] = $order_data['shipping_amount'];
//                    $order_data['base_shipping_invoiced'] = $order_data['shipping_amount'];
//                }
//            }
        }
        $order_data['tax_amount'] = 0;
//        $order_data['subtotal'] = $order_subtotal;
//        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $tax_data = unserialize($this->getRowValueFromListByField($ordersExt['object']['order_total'], 'entity_id', $order['order_id'],'commerce_order_total_data'));

        foreach($tax_data['components'] as $line_item){
            if($line_item['name'] != 'base_price'){
                $order_data['tax_amount'] += $line_item['price']['amount']/100;
            }else{
                $order_data['subtotal'] = $line_item['price']['amount']/100;
                $order_data['base_subtotal'] = $line_item['price']['amount']/100;
            }
        }
        $total = $this->getRowValueFromListByField($ordersExt['object']['order_total'], 'entity_id', $order['order_id'],'commerce_order_total_amount')/100;

        $order_data['base_tax_amount'] = $order_data['tax_amount'];

        $order_data['grand_total'] = $total;
        $order_data['base_grand_total'] = $total;
        $order_data['base_total_invoiced'] = $total;
        $order_data['total_paid'] = $total;
        $order_data['base_total_paid'] = $total;
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $store_currency['base'];
        $order_data['global_currency_code'] = $store_currency['base'];
        $order_data['store_currency_code'] = $store_currency['base'];
        $order_data['order_currency_code'] = $store_currency['base'];
        $order_data['created_at'] = date('Y-m-d h:i:s',$order['created']);;

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['order_id'];

        $payment_method_data = unserialize($order['data']);

        if(isset($payment_method_data['payment_method'])){
            $data['dc_payment_method'] = $payment_method_data['payment_method'];
        }
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

    }

    /**
     * Query for get main data use for import review
     *
     * @return string
     */
    protected function _getReviewsMainQuery(){
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $query = "SELECT * FROM _DBPRF_comment AS cm
                    LEFT JOIN _DBPRF_field_data_comment_body AS fd ON fd.entity_id = cm.cid
                    WHERE fd.bundle = 'comment_node_product' AND cm.cid > {$id_src} ORDER BY cm.cid ASC LIMIT {$limit}";

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
        return $review['cid'];
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
        $product_mage_id = $this->getMageIdProduct($review['nid']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['cid']} import failed. Error: Product Id = {$review['nid']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['status'] == 0)? 3 : 1;
        $data['title'] = $review['subject'];
        $data['detail'] = $review['comment_body_value'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getMageIdCustomer($review['uid']))? $this->getMageIdCustomer($review['uid']) : null;
        $data['nickname'] = $review['name'];

        $data['created_at'] = date('Y-m-d h:i:s',$review['created']);
        $data['review_id_import'] = $review['cid'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

    /**
     * Import review with data convert in function convertReview
     *
     * @param array $data : Data of function convertReview
     * @param array $review : One row of object in function getReviewsMain
     * @param array $reviewsExt : Data of function getReviewsExt
     * @return array
     */
    public function importReview($data, $review, $reviewsExt){
        if(LitExtension_CartMigration_Model_Custom::REVIEW_IMPORT){
            return $this->_custom->importReviewCustom($this, $data, $review, $reviewsExt);
        }
        $id_src = $this->getReviewId($review, $reviewsExt);
        $reviewIpt = $this->_process->review($data, null);
        if($reviewIpt['result'] == 'success'){
            $id_desc = $reviewIpt['mage_id'];
            $this->reviewSuccess($id_src, $id_desc);
        } else {
            $reviewIpt['result'] = 'warning';
            $msg = "Review Id = {$id_src} import failed. Error: " . $reviewIpt['msg'];
            $reviewIpt['msg'] = $this->consoleWarning($msg);
        }
        return $reviewIpt;
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

    protected function _convertProduct($product, $productsExt, $child_data){
        $pro_data = array();

        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];


        $created = $product['created'];
        if($created){
            $pro_data['created_at'] = date('Y-m-d h:i:s',$created);
        }else{
            $pro_data['created_at'] = now();
        }

        $pro_data['price'] = $this->getRowValueFromListByField($productsExt['object']['products_price'], 'revision_id',  $product['revision_id'], 'commerce_price_amount')/100;

        $pro_data['name'] = $product['title'];
//        $description = $this->getRowValueFromListByField($productsExt['object']['products'], 'nid', $product['nid'], 'body_value');
        $pro_data['description'] = ' ';
        $pro_data['weight'] = 0;
        $pro_data['status'] = ($product['status']) ? 1 : 2;

        $qty = 0;

        $manger_stock = 0;
        $in_stock = 1;

        $pro_data['stock_data'] = array(
            'is_in_stock' =>  $in_stock,
            'manage_stock' => $manger_stock,
            'use_config_manage_stock' => 1,
            'qty' => $qty,
        );

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
                $tmp['title'] = $option['title'];
                $tmp['price'] = $option['price'];
                $tmp['price_type'] = 'fixed';
                $options[]=$tmp;
            }
            if($data['type'] == 'field'){
                $tmp_opt = array(
                    'title' => $data['attribute'],
                    'type' => $data['type'],
                    'is_require' => $data['required'],
                    'sort_order' => $data['sort_order'],
                    'price' => $data['price']
                );
                $custom_option[] = $tmp_opt;
            }else{
                $tmp_opt = array(
                    'title' => $data['attribute'],
                    'type' => $data['type'],
                    'is_require' => $data['required'],
                    'sort_order' => $data['sort_order'],
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
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

    protected function _importChildrenProduct($parent_product, $sku_parent, $productsExt){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();

        $dataChildes = array();
        $childs = $this->getListFromListByField($productsExt['object']['product_adjustments'], 'nid', $parent_product['nid']);
        $tmp = 1;

        foreach($childs as $child){
            $convertPro = $attr_pro_data = $checksDuplicate = array();
            $options = unserialize($child['combination']);
            $child_pro_name = $this->getRowValueFromListByField($productsExt['object']['products'], 'nid', $parent_product['nid'], 'title');
            $price = 0;
            foreach($options as $att_id => $option_id){
                $name_option = $this->getRowValueFromListByField($productsExt['object']['attribute_option'], 'oid', $option_id, 'name');
                $child_pro_name .= ' - '.$name_option;
                $price +=  $this->getRowValueFromListByField($productsExt['object']['attribute_option'], 'oid', $option_id, 'price');
                $attributes = $this->getListFromListByField($productsExt['object']['attributes'], 'aid',$att_id);
                $option_store = $this->getListFromListByField($productsExt['object']['attribute_option'], 'aid',$att_id);
                $value_index = 0;
                if(!count($options)){
                    continue;
                }
                $attr_name = $this->getRowValueFromListByField($productsExt['object']['attributes'], 'aid', $att_id, 'label');
                $attr_code = $this->getRowValueFromListByField($productsExt['object']['attributes'], 'aid', $att_id, 'name');
                $attr_code = str_replace(' ','_',$attr_code);
                $attr_code = substr($attr_code, 0, 30);
                $option_collection = '';
                $option_mage = array();
                foreach ($option_store as $key1 => $opt) {
                    $option = array();
                    if (isset($this->_notice['config']['languages'])) {
                        $option[] = $opt['name'];
                    }
                    if ($option) {
                        if (!isset($option['0'])) {
                            $option['0'] = reset($option);
                        }
                    }
                    $option_mage['option_'.$key1] = $option;
                    if($option_id == $opt['oid']){
                        $value_index = $key1;
                    }
                }

                $attr_import = $this->_makeAttributeImport($attr_name, $attr_code ,$option_mage, $entity_type_id, $this->_notice['config']['attribute_set_id'], 'select');

                if(!$attr_import){
                    return array(
                        'result' => 'warning',
                        'msg' => "Product ID = {$parent_product['nid']} import failed. Error: Product attribute could not create!"
                    );
                }

                $dataOptAfterImport = $this->_process->attribute($attr_import['config'], $attr_import['edit']);

                if(!$dataOptAfterImport){
                    return array(
                        'result' => 'warning',
                        'msg' => "Product ID = {$parent_product['nid']} import failed. Error: Product attribute could not create!"
                    );
                };

                $dataOptAfterImport['option_label'] = $name_option;
                $attrProDataTmp = array(
                    'attribute_label' => $attr_name,
                    'uc_attribute_code' => $attr_code,
                    'mage_attribute_code' => $dataOptAfterImport['attribute_code'],
                    'attribute_id' => $dataOptAfterImport['attribute_id'],
                    'value_index' => $dataOptAfterImport['option_ids']['option_'.$value_index],
                    'value_label' => $name_option,
                );

                $attr_pro_data[] = $attrProDataTmp;

            }

            $sku = $child['model'];

            if(!$sku || $sku == $sku_parent){
                $sku = $sku_parent. "-" .$tmp;
            }
            $tmp++;

            $convertPro['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;

            $convertPro['tax_class_id'] = 0;

            $convertPro['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $convertPro['name'] = $child_pro_name;
            $convertPro['sku'] = $this->createProductSku($sku, $this->_notice['config']['languages']);
            $convertPro['category_ids'] = array();
            $convertPro = array_merge($this->_convertProduct($parent_product, $productsExt, $child), $convertPro);
            $convertPro['price'] = $price;
            $pro_import = $this->_process->product($convertPro);
            if($pro_import['result'] !== 'success'){
                return array(
                    'result' => 'warning',
                    'msg' => "Product ID = {$parent_product['nid']} import failed. Error: Error: Product children could not create!"
                );
            };
            $this->productSuccess($sku, $pro_import['mage_id']);
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

    protected function _compareArray($arr1, $arr2){
        foreach($arr1 as $opt1){
            $to_continue = false;
            foreach($arr2 as $opt2){
                if($opt1 == $opt2){
                    $to_continue = true;
                    break;
                }
            }
            if(!$to_continue){
                return false;
            }
        }
        return true;
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

    protected function _makeAttributeImport($attribute_name, $attribute_code, $option, $entity_type_id, $attribute_set_id, $type = 'select'){
        $multi_attr = $result = array();

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
                'value' => ($option) ? $option : array()
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
            $attr_label = $this->getRowValueFromListByField($woo_attribute_list, 'attribute_name', $attr_slug, 'attribute_label');
            if(!$attr_label){
                $attr_label = $attr_slug;
            }
            $result['attr_name'] = $attr_label;
            $result['attr_slug'] = $attr_slug;
            $result['attr_code'] = $this->joinTextToKey(str_replace('-',' ',$result['attr_slug']), 30, '_');
            $result['option_name'] = $this->getRowValueFromListByField($allOptionAttributeOut[$attr_slug], 'slug', $data_option['meta_value'], 'name');
        }else{
            $attr_slug = substr_replace($data_option['meta_key'],'',0,10);
            if(!isset($allOptionAttributeIn[$attr_slug])){
                return false;
            }
            $result['attr_prefix'] = 'attribute_';
            $result['attr_name'] = isset($allOptionAttributeIn[$attr_slug][0]['name_attribute']) ? $allOptionAttributeIn[$attr_slug][0]['name_attribute'] : '';
            $result['attr_slug'] = $attr_slug;
            $result['attr_code'] = $this->joinTextToKey(str_replace('-',' ',$result['attr_slug']), 30, '_');
            $result['option_name'] = $this->getRowValueFromListByField($allOptionAttributeIn[$attr_slug], 'name_option', $data_option['meta_value'], 'name_option');
        }
        return $result;
    }

    protected function _getAnyOptions($meta_key, $allOptionAttributeIn, $allOptionAttributeOut){
        $result = array();
        $attributePrefix = substr($meta_key,0,13);
        if($attributePrefix == 'attribute_pa_'){
            $attr_slug = substr_replace($meta_key,'',0,13);
            if(!isset($allOptionAttributeOut[$attr_slug])){
                return false;
            }
            foreach($allOptionAttributeOut[$attr_slug] as $opt_attr_out){
                $tmp['meta_key'] = $meta_key;
                $tmp['meta_value'] = $opt_attr_out['slug'];
                $result[] = $tmp;
            }
        }else{
            $attr_slug = substr_replace($meta_key,'',0,10);
            if(!isset($allOptionAttributeIn[$attr_slug])){
                return false;
            }
            foreach($allOptionAttributeIn[$attr_slug] as $opt_attr_in){
                $tmp['meta_key'] = $meta_key;
                $tmp['meta_value'] = $opt_attr_in['slug'];
                $result[] = $tmp;
            }
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
                if(isset($arr['value']) && !is_array($arr['value']) && trim($arr['value'] != NULL) && $arr['is_taxonomy'] == '0'){
                    $options = explode(" | ",$arr['value']);
                    foreach($options as $option){
                        $tmp = array();
                        $tmp['name_attribute'] = isset($arr['name']) ? $arr['name'] : ' ';
                        $tmp['name_option'] = $option;
                        $option_remove_accent = $this->_removeAccents($option);
                        $tmp['slug'] = $this->_sanitizeTitleWithDashes($option_remove_accent);
                        $result[$attribute][] = $tmp;
                    }
                }
            }
        }
        return $result;
    }

    protected function _sanitizeTitleWithDashes($title, $raw_title = '', $context = 'display'){
        $title = strip_tags($title);
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        $title = str_replace('%', '', $title);
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title);
        $title = str_replace('.', '-', $title);

        if ( 'save' == $context ) {
            $title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );
            $title = str_replace( array(
                '%c2%a1', '%c2%bf',
                '%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
                '%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
                '%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
                '%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
                '%c2%b4', '%cb%8a', '%cc%81', '%cd%81',
                '%cc%80', '%cc%84', '%cc%8c',
            ), '', $title );
            $title = str_replace( '%c3%97', 'x', $title );
        }
        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');

        return $title;
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
            'query' => "SELECT * FROM _DBPRF_taxonomy_term_data as td
                          LEFT JOIN _DBPRF_taxonomy_term_hierarchy AS th ON td.tid = th.tid
                          WHERE td.tid = {$parent_id}"
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
    protected function fconver($value){
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

    protected function _removeAccents($string, $utf8 = true) {
        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;

        if ($utf8) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
                chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
                chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
                chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
                chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
                chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
                chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
                chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
                chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
                chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
                chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
                chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
                chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
                chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
                chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
                chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
                chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
                chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
                chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
                chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
                chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
                chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
                chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
                chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
                chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
                chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
                chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
                chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
                chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
                chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
                chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
                chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',
                // Decompositions for Latin Extended-A
                chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
                chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
                chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
                chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
                chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
                chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
                chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
                chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
                chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
                chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
                chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
                chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
                chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
                chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
                chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
                chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
                chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
                chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
                chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
                chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
                chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
                chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
                chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
                chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
                chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
                chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
                chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
                chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
                chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
                chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
                chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
                chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
                chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
                chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
                chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
                chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
                chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
                chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
                chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
                chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
                chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
                chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
                chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
                chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
                chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
                chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
                chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
                chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
                chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
                chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
                chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
                chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
                chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
                chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
                chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
                chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
                chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
                chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
                chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
                chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
                chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
                chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
                chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
                chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
                // Decompositions for Latin Extended-B
                chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
                chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
                // Euro Sign
                chr(226).chr(130).chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194).chr(163) => '',
                // Vowels with diacritic (Vietnamese)
                // unmarked
                chr(198).chr(160) => 'O', chr(198).chr(161) => 'o',
                chr(198).chr(175) => 'U', chr(198).chr(176) => 'u',
                // grave accent
                chr(225).chr(186).chr(166) => 'A', chr(225).chr(186).chr(167) => 'a',
                chr(225).chr(186).chr(176) => 'A', chr(225).chr(186).chr(177) => 'a',
                chr(225).chr(187).chr(128) => 'E', chr(225).chr(187).chr(129) => 'e',
                chr(225).chr(187).chr(146) => 'O', chr(225).chr(187).chr(147) => 'o',
                chr(225).chr(187).chr(156) => 'O', chr(225).chr(187).chr(157) => 'o',
                chr(225).chr(187).chr(170) => 'U', chr(225).chr(187).chr(171) => 'u',
                chr(225).chr(187).chr(178) => 'Y', chr(225).chr(187).chr(179) => 'y',
                // hook
                chr(225).chr(186).chr(162) => 'A', chr(225).chr(186).chr(163) => 'a',
                chr(225).chr(186).chr(168) => 'A', chr(225).chr(186).chr(169) => 'a',
                chr(225).chr(186).chr(178) => 'A', chr(225).chr(186).chr(179) => 'a',
                chr(225).chr(186).chr(186) => 'E', chr(225).chr(186).chr(187) => 'e',
                chr(225).chr(187).chr(130) => 'E', chr(225).chr(187).chr(131) => 'e',
                chr(225).chr(187).chr(136) => 'I', chr(225).chr(187).chr(137) => 'i',
                chr(225).chr(187).chr(142) => 'O', chr(225).chr(187).chr(143) => 'o',
                chr(225).chr(187).chr(148) => 'O', chr(225).chr(187).chr(149) => 'o',
                chr(225).chr(187).chr(158) => 'O', chr(225).chr(187).chr(159) => 'o',
                chr(225).chr(187).chr(166) => 'U', chr(225).chr(187).chr(167) => 'u',
                chr(225).chr(187).chr(172) => 'U', chr(225).chr(187).chr(173) => 'u',
                chr(225).chr(187).chr(182) => 'Y', chr(225).chr(187).chr(183) => 'y',
                // tilde
                chr(225).chr(186).chr(170) => 'A', chr(225).chr(186).chr(171) => 'a',
                chr(225).chr(186).chr(180) => 'A', chr(225).chr(186).chr(181) => 'a',
                chr(225).chr(186).chr(188) => 'E', chr(225).chr(186).chr(189) => 'e',
                chr(225).chr(187).chr(132) => 'E', chr(225).chr(187).chr(133) => 'e',
                chr(225).chr(187).chr(150) => 'O', chr(225).chr(187).chr(151) => 'o',
                chr(225).chr(187).chr(160) => 'O', chr(225).chr(187).chr(161) => 'o',
                chr(225).chr(187).chr(174) => 'U', chr(225).chr(187).chr(175) => 'u',
                chr(225).chr(187).chr(184) => 'Y', chr(225).chr(187).chr(185) => 'y',
                // acute accent
                chr(225).chr(186).chr(164) => 'A', chr(225).chr(186).chr(165) => 'a',
                chr(225).chr(186).chr(174) => 'A', chr(225).chr(186).chr(175) => 'a',
                chr(225).chr(186).chr(190) => 'E', chr(225).chr(186).chr(191) => 'e',
                chr(225).chr(187).chr(144) => 'O', chr(225).chr(187).chr(145) => 'o',
                chr(225).chr(187).chr(154) => 'O', chr(225).chr(187).chr(155) => 'o',
                chr(225).chr(187).chr(168) => 'U', chr(225).chr(187).chr(169) => 'u',
                // dot below
                chr(225).chr(186).chr(160) => 'A', chr(225).chr(186).chr(161) => 'a',
                chr(225).chr(186).chr(172) => 'A', chr(225).chr(186).chr(173) => 'a',
                chr(225).chr(186).chr(182) => 'A', chr(225).chr(186).chr(183) => 'a',
                chr(225).chr(186).chr(184) => 'E', chr(225).chr(186).chr(185) => 'e',
                chr(225).chr(187).chr(134) => 'E', chr(225).chr(187).chr(135) => 'e',
                chr(225).chr(187).chr(138) => 'I', chr(225).chr(187).chr(139) => 'i',
                chr(225).chr(187).chr(140) => 'O', chr(225).chr(187).chr(141) => 'o',
                chr(225).chr(187).chr(152) => 'O', chr(225).chr(187).chr(153) => 'o',
                chr(225).chr(187).chr(162) => 'O', chr(225).chr(187).chr(163) => 'o',
                chr(225).chr(187).chr(164) => 'U', chr(225).chr(187).chr(165) => 'u',
                chr(225).chr(187).chr(176) => 'U', chr(225).chr(187).chr(177) => 'u',
                chr(225).chr(187).chr(180) => 'Y', chr(225).chr(187).chr(181) => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin)
                chr(201).chr(145) => 'a',
                // macron
                chr(199).chr(149) => 'U', chr(199).chr(150) => 'u',
                // acute accent
                chr(199).chr(151) => 'U', chr(199).chr(152) => 'u',
                // caron
                chr(199).chr(141) => 'A', chr(199).chr(142) => 'a',
                chr(199).chr(143) => 'I', chr(199).chr(144) => 'i',
                chr(199).chr(145) => 'O', chr(199).chr(146) => 'o',
                chr(199).chr(147) => 'U', chr(199).chr(148) => 'u',
                chr(199).chr(153) => 'U', chr(199).chr(154) => 'u',
                // grave accent
                chr(199).chr(155) => 'U', chr(199).chr(156) => 'u',
            );

            // Used for locale-specific rules
            $locale = 'de_DE';

            if ( 'de_DE' == $locale ) {
                $chars[ chr(195).chr(132) ] = 'Ae';
                $chars[ chr(195).chr(164) ] = 'ae';
                $chars[ chr(195).chr(150) ] = 'Oe';
                $chars[ chr(195).chr(182) ] = 'oe';
                $chars[ chr(195).chr(156) ] = 'Ue';
                $chars[ chr(195).chr(188) ] = 'ue';
                $chars[ chr(195).chr(159) ] = 'ss';
            } elseif ( 'da_DK' === $locale ) {
                $chars[ chr(195).chr(134) ] = 'Ae';
                $chars[ chr(195).chr(166) ] = 'ae';
                $chars[ chr(195).chr(152) ] = 'Oe';
                $chars[ chr(195).chr(184) ] = 'oe';
                $chars[ chr(195).chr(133) ] = 'Aa';
                $chars[ chr(195).chr(165) ] = 'aa';
            }

            $string = strtr($string, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
                .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
                .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
                .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
                .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
                .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
                .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
                .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
                .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
                .chr(252).chr(253).chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
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
        $query = "SELECT * FROM _DBPRF_posts WHERE post_type = 'product' AND post_status NOT IN ('inherit','auto-draft') ORDER BY ID ASC";
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
        $query = "SELECT * FROM _DBPRF_posts WHERE post_type = 'shop_order' AND post_status NOT IN ('inherit','auto-draft') ORDER BY ID ASC";
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
        $query = "SELECT cm.* FROM _DBPRF_comment AS cm ORDER BY cm.cid ASC";
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
}