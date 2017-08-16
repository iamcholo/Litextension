<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class   LitExtension_CartMigration_Model_Cart_Cubecartv4 extends LitExtension_CartMigration_Model_Cart{

    public function __construct(){
        parent::__construct();
    }

    public function checkRecent()
    {
        $data = $this->_getDataImport($this->_getUrlConnector('query'), array(
            'serialize' => true,
            'query' => serialize(array(
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_taxes WHERE id > {$this->_notice['taxes']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE cat_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_inventory WHERE productId > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE customer_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_order_sum WHERE cart_order_id > {$this->_notice['orders']['id_src']}",
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
        $default_cfg = $this->_getDataImport($this->_getUrlConnector('query'),array(
            'serialize' => true,
            'query' => serialize(array(
                'config' => "SELECT * FROM _DBPRF_config WHERE name = 'config'",
            ))
        ));
        if(!$default_cfg || $default_cfg['result'] != 'success'){
            return $this->errorConnector();
        }
        $object = $default_cfg['object'];
        if($object && $object['config']){
            $config_data = json_decode(base64_encode($object['config'][0]['array']));
            $this->_notice['config']['default_lang'] = isset($config_data['default_language']) ? $config_data['default_language'] : 'en';
            $this->_notice['config']['default_currency'] = isset($config_data['default_currency']) ? $config_data['default_currency'] : 'USD';
        }
        $data = $this->_getDataImport($this->_getUrlConnector('query'),array(
            "serialize" => true,
            "query" => serialize(array(
                "currencies" => "SELECT * FROM _DBPRF_currencies WHERE active = 1 ",
                "languages" => "SELECT DISTINCT prod_lang FROM _DBPRF_inv_lang",
            ))
        ));

        if(!$data || $data['result'] != 'success'){
            return $this->errorConnector();
        }

        $obj = $data['object'];
        $language_data = $currency_data = $order_status_data = $category_data = $attribute_data = array();
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");

        $language_data[$this->_notice['config']['default_lang']] = $this->_notice['config']['default_lang'];

        foreach($obj['currencies'] as $currency_row){
            $currency_id = $currency_row['currencyId'];
            $currency_name = $currency_row['code'];
            $currency_data[$currency_id] = $currency_name;
        }
        foreach($obj['languages'] as $language_row){
            $language_id = $language_row['prod_lang'];
            $languge_name = $language_row['prod_lang'];
            $language_data[$language_id] = $languge_name;
        }

        
        $order_status_data = array(
            1 => "Pending",
            2 => "Process",
            3 => "Complete",
            4 => "Declined",
            5 => "Failed",
            6 => "Cancelled"
        );

        
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        //$this->_notice['config']['customer_group_data'] = $customer_group_data;
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
    public function displayImport(){
        $recent = $this->getRecentNotice();
        if($recent){
            $types = array('taxes','manufacturers','categories','products','customers','orders','reviews');
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
                'taxes' => "SELECT COUNT(1) FROM _DBPRF_taxes WHERE id > {$this->_notice['taxes']['id_src']}",
                //'manufacturers' => "SELECT COUNT(1) FROM _DBPRF_manufacturers WHERE id > {$this->_notice['manufacturers']['id_src']}",
                'categories' => "SELECT COUNT(1) FROM _DBPRF_category WHERE cat_id > {$this->_notice['categories']['id_src']}",
                'products' => "SELECT COUNT(1) FROM _DBPRF_inventory WHERE productId > {$this->_notice['products']['id_src']}",
                'customers' => "SELECT COUNT(1) FROM _DBPRF_customer WHERE customer_id > {$this->_notice['customers']['id_src']}",
                'orders' => "SELECT COUNT(1) FROM _DBPRF_order_sum WHERE cart_order_id > {$this->_notice['orders']['id_src']}",
                'reviews' => "SELECT COUNT(1) FROM _DBPRF_reviews WHERE id > {$this->_notice['reviews']['id_src']}"
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
                $currency_id = $currency['currencyId'];
                $currency_value = $currency['value'];
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
        $query = "SELECT * FROM _DBPRF_taxes WHERE id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Query for get data relation use for import tax rule
     *
     * @param array $taxes : Data of connector return for query in function getTaxesMainQuery
     * @return array
     */

    protected function _getTaxesExtQuery($taxes){
        $taxIds = $this->duplicateFieldValueFromList($taxes['object'], 'id');
        $tax_id_con = $this->arrayToInCondition($taxIds);
        $ext_query = array(
            'tax_rates' => "SELECT tr.*,icr.*, ic.*
                            FROM _DBPRF_tax_rates AS tr
                              LEFT JOIN _DBPRF_iso_countries AS icr ON icr.id = tr.country_id
                              LEFT JOIN _DBPRF_iso_counties AS ic ON ic.id = tr.county_id
                            WHERE tr.type_id IN {$tax_id_con}"
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
        return false;
    }
    
     /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxesMain
     * @param array $taxesExt : Data of function getTaxesExt
     * @return int
     */

    public  function getTaxId($tax, $taxesExt){
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
            'class_name' => $tax['taxName']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] ==  'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id'],$tax_pro_ipt['mage_id']);
        }

        $taxRates = $this->getListFromListByField($taxesExt['object']['tax_rates'],'type_id',$tax['id']);
        foreach($taxRates as $tax_rate){
            $tax_rate_data = array();
            $code = $tax['taxName'] . "-" . $tax['percent'];
            $tax_rate_data['code'] = $this->createTaxRateCode($code);
            $tax_rate_data['tax_country_id'] = $tax_rate['iso'];
            if(!$tax_rate['county_id']){
                $tax_rate_data['tax_region_id'] = 0;
            } else {
                $tax_rate_data['tax_region_id'] = $this->getRegionId($tax_rate['name'], $tax_rate['iso']);
            }
            $tax_rate_data['zip_is_range'] = 0;
            $tax_rate_data['tax_postcode'] = "*";
            $tax_rate_data['rate'] = $tax_rate['tax_percent'];
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if($tax_rate_ipt['result'] == 'success'){
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
            }
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['taxName']);
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
     * Query for get data of main table use import category
     *
     * @return string
     */
    
     protected function _getCategoriesMainQuery(){
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $query = "SELECT * FROM _DBPRF_category WHERE cat_id > {$id_src} ORDER BY cat_id ASC LIMIT {$limit}";
        return $query;
    }
    
     /**
     * Query for get data relation use for import categories
     *
     * @param array $categories : Data of connector return for query function getCategoriesMainQuery
     * @return array
     */
    
    protected function _getCategoriesExtQuery($categories){
        $categoryIds = $this->duplicateFieldValueFromList($categories['object'],'cat_id');
        $cat_id_con = $this->arrayToInCondition($categoryIds);
        $ext_query = array(
            'cats_lang' => "SELECT * FROM _DBPRF_cats_lang WHERE cat_master_id IN {$cat_id_con}",
            'cats_images' => "SELECT * FROM _DBPRF_filemanager"
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
        return $category['cat_id'];
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
        if($category['cat_father_id'] == 0 || $category['cat_father_id'] == $category['cat_id']){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getMageIdCategory($category['cat_father_id']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['cat_father_id']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['cat_id']} import failed. Error: Could not import parent category id = {$category['cat_father_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $catDesc = $this->getListFromListByField($categoriesExt['object']['cats_lang'], 'cat_master_id', $category['cat_id']);
        //$cat_dess = $this->getRowFromListByField($catDesc, 'language', $this->_notice['config']['default_lang']);
        
        $cat_data['name'] = $category['cat_name'] ? $category['cat_name'] : " ";
        $cat_data['description'] = $category['cat_desc'];
        $cat_data['meta_title'] = $category['cat_metatitle'];
        $cat_data['meta_keywords'] = $category['cat_metakeywords'];
        $cat_data['meta_description'] = $category['cat_metadesc'];
        
        //$catImg = $this->getRowFromListByField($categoriesExt['object']['cats_images'], 'file_id', $category['cat_image']);
        if($category['cat_image']){
                $img_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_category']),  $category['cat_image'], 'catalog/category');
                $cat_data['image'] = $img_path;
        }


        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['hide'] == 0) ? 1 : 2;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $multi_store = array();
        foreach($this->_notice['config']['languages'] as $lang_id => $store_id){
            if($lang_id != $this->_notice['config']['default_lang']){
                $store_data = array();
                $store_def = $this->getRowFromListByField($catDesc, 'cat_lang', $lang_id);
                $store_data['store_id'] = $store_id;
                $store_data['name'] = $store_def['cat_name'];
                $store_data['description'] = $store_def['cat_desc'];
//                $store_data['meta_title'] = $store_def['seo_meta_title'];
//                $store_data['meta_keywords'] = $store_def['seo_meta_keywords'];
//                $store_data['meta_description'] = $store_def['seo_meta_description'];
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
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }
    
    /**
     * Query for get data of main table use for import product
     *
     * @return string
     */

    protected function _getProductsMainQuery(){
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT * FROM _DBPRF_inventory WHERE productId > {$id_src} ORDER BY productId ASC LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Query for get data relation use for import product
     *
     * @param array $products : Data of connector return for query function getProductsMainQuery
     * @return array
     */

    protected function _getProductsExtQuery($products){
        $productIds = $this->duplicateFieldValueFromList($products['object'], 'productId');
        $pro_ids_query = $this->arrayToInCondition($productIds);
        $ext_query = array(
            'inv_lang' => "SELECT * FROM _DBPRF_inv_lang WHERE prod_master_id IN {$pro_ids_query}",      
            'cats_idx' => "SELECT * FROM _DBPRF_cats_idx WHERE productId IN {$pro_ids_query}",
            'options_bot' => "SELECT * FROM _DBPRF_options_bot WHERE product IN {$pro_ids_query}",
            'products_images' => "SELECT ii.*, f.* FROM _DBPRF_img_idx AS ii
                                                  LEFT JOIN _DBPRF_filemanager AS f ON f.filename = ii.img
                                            WHERE ii.productId IN {$pro_ids_query}"
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
        $productOptionIds = $this->duplicateFieldValueFromList($productsExt['object']['options_bot'], 'option_id');
        $productOptionValueIds = $this->duplicateFieldValueFromList($productsExt['object']['options_bot'], 'value_id');
        $product_option_ids_query = $this->arrayToInCondition($productOptionIds);
        $product_option_value_ids_query = $this->arrayToInCondition($productOptionValueIds);
        $ext_rel_query = array(
            'options_top' => "SELECT * FROM _DBPRF_options_top WHERE option_id IN {$product_option_ids_query}",
            'options_mid' => "SELECT * FROM _DBPRF_options_mid WHERE value_id IN {$product_option_value_ids_query}"
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
    
    public function getProductId($product, $productsExt){
        return $product['productId'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProductsMain
     * @param array $productsExt : Data of function getProductsMain
     * @return array
     */

    public function convertProduct($product, $productsExt){
        if(LitExtension_CartMigration_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $categories = array();
        $proCat = $this->getListFromListByField($productsExt['object']['cats_idx'], 'productId', $product['productId']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getMageIdCategory($pro_cat['cat_id']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $proDesc = $this->getListFromListByField($productsExt['object']['inv_lang'], 'prod_master_id', $product['productId']);
        $pro_desc_def = $this->getRowFromListByField($proDesc, 'language',$this->_notice['config']['default_lang']);
        $pro_data = array();
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['sku'] = $this->createProductSku($product['productCode'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['name'];
        $pro_data['description'] = $product['description'];//$this->changeImgSrcInText(html_entity_decode($pro_desc_def['description']), $this->_notice['config']['add_option']['img_des']);
        $pro_data['price'] = $product['price'] ? $product['price'] : 0;
        $pro_data['special_price'] = $product['sale_price'];


        $pro_data['weight']   = $product['prodWeight'] ? $product['prodWeight']: 0 ;
        $pro_data['status'] = ($product['disabled']== 0)? 1 : 2;
        if($product['taxType'] != 0 && $tax_pro_id = $this->getMageIdTaxProduct($product['taxType'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['date_added'];
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $product['useStockLevel'],
            'use_config_manage_stock' => 0,
            'qty' => $product['stock_level']
        );
        
        if ($product['image'] != '' && $image_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $product['image'], 'catalog/product', false, true)) {
            $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
        }
        $proImg = $this->getListFromListByField($productsExt['object']['products_images'], 'productId', $product['productId']);
        if ($proImg) {
            foreach ($proImg as $gallery) {
                if ($gallery_path = $this->downloadImage($this->getUrlSuffix($this->_notice['config']['image_product']), $gallery['filename'], 'catalog/product', false, true)) {
                    $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '');
                }
            }
        }

        $pro_data['meta_title'] = $product['prod_metatitle'];
        $pro_data['meta_keyword'] = $product['prod_metakeywords'];
        $pro_data['meta_description'] = $product['prod_metadesc'];
        $multi_store = array();
        
        foreach($this->_notice['config']['languages'] as $lang_id => $store_id){
            if($lang_id != $this->_notice['config']['default_lang'] && $store_data_change = $this->getRowFromListByField($proDesc, 'prod_lang', $lang_id)){
                $store_data = array();
                $store_data['name'] = $store_data_change['name'];
                $store_data['description'] = $this->changeImgSrcInText($store_data_change['description'], $this->_notice['config']['add_option']['img_des']);
                $store_data['store_id'] = $store_id;
                $multi_store[] = $store_data;
            }
        }
        $pro_data['multi_store'] = $multi_store;
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
        $proAttr = $this->getListFromListByField($productsExt['object']['options_bot'], 'product', $product['productId']);
        if($proAttr){
            $opt_data = array();
            $proOptId = $this->duplicateFieldValueFromList($proAttr, 'option_id');
            foreach($proOptId as $pro_opt_id){
                $proOpt = $this->getListFromListByField($productsExt['object']['options_top'], 'option_id', $pro_opt_id);
                $proOptVal = $this->getListFromListByField($proAttr,'option_id', $pro_opt_id);
                if(!$proOpt){
                    continue;
                }
                $type = $this->getRowValueFromListByField($proOpt, 'option_id', $pro_opt_id, 'option_type');
                $type_import = $this->_getOptionTypeByTypeSrc($type);
                
                $option = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($type_import),
                    'type' => $type_import,
                    'is_require' => ($type == 0)? 1 : 0,
                    'title' => $this->getRowValueFromListByField($proOpt, 'option_id', $pro_opt_id, 'option_name')
                );
                $values = array();
                if($type_import != 'drop_down'){
                    $option['price'] = $proOptVal[0]['option_price'];
                    $option['price_type'] = 'fixed';
                    $opt_data[] = $option;
                    continue;
                }
                foreach($proOptVal as $pro_opt_val){
                    $proVal = $this->getRowFromListByField($productsExt['object']['options_mid'], 'value_id', $pro_opt_val['value_id']);
                    $value = array(
                        'option_type_id' => -1,
                        'title' => $proVal['value_name'],
                        'price' => $pro_opt_val['option_price'],
                        'price_type' => 'fixed'
                    );
                    $values[] = $value;
                }
                $option['values'] = $values;
                $opt_data[] = $option;
            }
            $this->importProductOption($product_mage_id, $opt_data);
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
        $query = "SELECT * FROM _DBPRF_customer WHERE customer_id > {$id_src} ORDER BY customer_id ASC LIMIT {$limit}";
        return $query;
    }
    
     /**
     * Query for get data relation use for import customer
     *
     * @param array $customers : Data of connector return for query function getCustomersMainQuery
     * @return array
     */

    protected function _getCustomersExtQuery($customers){
        $customerIds = $this->duplicateFieldValueFromList($customers['object'], 'customer_id');
        $customer_ids_query = $this->arrayToInCondition($customerIds);
        $ext_query = array(
            'iso_countries' => "SELECT * FROM _DBPRF_iso_countries"
          
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
        return $customer['customer_id'];
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
       // $info = $this->getRowFromListByField($customersExt['object']['customers_info'], 'customers_info_id', $customer['customers_id']);
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['customer_id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['firstName'];
        $cus_data['lastname'] = $customer['lastName'];
        $cus_data['gender'] = "";
        $cus_data['dob'] = "";
        $cus_data['created_at'] = "";
        $cus_data['is_subscribed'] = 0;
        //$groupsCustomer = $this->getRowFromListByField($customersExt['object']['customer_membership'], 'customer_id', $customer['customer_id']);
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
            return;
        }
        $this->_importCustomerRawPass($customer_mage_id, $customer['password']. ":" . $customer['salt']);
       // if($cusAdd){
           // foreach($cusAdd as $cus_add){
                $cus_add_iso = $this->getRowValueFromListByField($customersExt['object']['iso_countries'], 'id', $customer['country'], 'iso');
                $address = array();
                $address['firstname'] = $customer['firstName'];
                $address['lastname'] = $customer['lastName'];
                $address['country_id'] = $cus_add_iso;//$customer['iso'];
                $address['street'] = $customer['add_1']."\n".$customer['add_2'];
                $address['postcode'] = $customer['postcode'];
                $address['city'] = $customer['town'];
                $address['telephone'] = $customer['phone'];
                $address['company'] = $customer['companyName'];
                $address['fax'] = "";
                if($customer['county']){
                    $region_id = $this->getRegionId($customer['county'], $cus_add_iso);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $customer['county'];
                } else {
                    $address['region'] = $customer['add_2'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($address_ipt['result'] == 'success' ){
                    try{
                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                        $cus->setDefaultBilling($address_ipt['mage_id']);
                        $cus->setDefaultShipping($address_ipt['mage_id']);
                        $cus->save();
                    }catch (Exception $e){}
                }
            //}
        //}
    }
    
    /**
     * Get data use for import order
     *
     * @return array : Response of connector
     */

   
    public function _getOrdersMainQuery(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $query = "SELECT * FROM (SELECT *,cast( REPLACE(cart_order_id,'-','') AS UNSIGNED ) as order_id FROM _DBPRF_order_sum) cc WHERE cc.order_id > {$id_src} ORDER BY cc.order_id LIMIT {$limit}";
        return $query;
    }
    
    /**
     * Get data relation use for import order
     *
     * @param array $orders : Data of function getOrdersMain
     * @return array : Response of connector
     */

    protected function _getOrdersExtQuery($orders){
        $orderIds = $this->duplicateFieldValueFromList($orders['object'],'cart_order_id');
        $bilCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'country');
        $delCountry = (array) $this->duplicateFieldValueFromList($orders['object'], 'country_d');
        $countries = array_unique(array_merge($bilCountry, $delCountry));
        $bilState = (array) $this->duplicateFieldValueFromList($orders['object'], 'county');
        $delState = (array) $this->duplicateFieldValueFromList($orders['object'], 'county_d');
        $states = array_unique(array_merge($bilState, $delState));
        $order_ids_query = $this->arrayToInCondition($orderIds);
        $countries_query = $this->arrayToInCondition($countries);
        $states_query = $this->arrayToInCondition($states);
        $ext_query = array(
            'order_inv' => "SELECT * FROM _DBPRF_order_inv WHERE cart_order_id IN {$order_ids_query}",
            'currency' => "SELECT * FROM _DBPRF_currencies",
            'iso_countries' => "SELECT * FROM _DBPRF_iso_countries WHERE id IN {$countries_query}",
            'iso_counties' => "SELECT * FROM _DBPRF_iso_counties WHERE id IN {$states_query}"
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

        $address_billing = "";// $this->getNameFromString($order['title']);
        $address_billing['firstname'] = "";
        $address_billing['lastname'] = $order['name'];
        $address_billing['company'] = $order['companyName'];
        $address_billing['email']   = $order['email'];
        $address_billing['street']  = $order['add_1']."\n".$order['add_2'];
        $address_billing['city'] = $order['town'];
        $address_billing['postcode'] = $order['postcode'];
        $bil_country = $this->getRowValueFromListByField($ordersExt['object']['iso_countries'], 'name', $order['country'], 'iso');
        $address_billing['country_id'] = $bil_country;
        if(is_numeric($order['county'])){
            $billing_state = $this->getRowValueFromListByField($ordersExt['object']['iso_counties'], 'id', $order['county'], 'name');
            if(!$billing_state){
                $billing_state = $order['county'];
            }
        } else{
            $billing_state = $order['county'];
        }
        $billing_region_id = $this->getRegionId($billing_state, $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $billing_state;
        }
        $address_billing['telephone'] = $order['phone'];

        $address_shipping = "";//$this->getNameFromString($order['title_d']);
        $address_shipping['firstname'] = "";//$order['first_name_d'];
        $address_shipping['lastname'] = $order['name'];
        $address_shipping['company'] = $order['companyName'];
        $address_shipping['email']   = $order['email'];
        $address_shipping['street']  = $order['add_1_d']."\n".$order['add_2_d'];
        $address_shipping['city'] = $order['town_d'];
        $address_shipping['postcode'] = $order['postcode_d'];
        $del_country = $this->getRowValueFromListByField($ordersExt['object']['iso_countries'], 'printable_name', $order['country_d'], 'iso');
        $address_shipping['country_id'] = $del_country;
        if(is_numeric($order['county_d'])){
            $shipping_state = $this->getRowValueFromListByField($ordersExt['object']['iso_counties'], 'id', $order['county_d'], 'name');
            if(!$shipping_state){
                $shipping_state = $order['county_d'];
            }
        } else{
            $shipping_state = $order['county_d'];
        }
        $shipping_region_id = $this->getRegionId($shipping_state, $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $shipping_state;
        }
        $address_shipping['telephone'] = $order['phone'];

        $orderPro = $this->getListFromListByField($ordersExt['object']['order_inv'], 'cart_order_id', $order['cart_order_id']);
        $carts = array();
        $order_subtotal = 0;

            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getMageIdProduct($order_pro['productId']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_pro['name'];
                $cart['sku'] = $order_pro['productCode'];
                $cart['price'] = $order_pro['price'];
                $cart['original_price'] = $order_pro['price'];
                $cart['tax_amount'] =  0;
                $cart['tax_percent'] = 0;
                $cart['qty_ordered'] = $order_pro['quantity'];
                $cart['row_total'] = $order_pro['price'];
                $order_subtotal += $cart['row_total'];
                //$order_tax_amount += $cart['tax_amount'];

                if($order_pro['product_options']){
                    $listOpt = explode("\n",$order_pro['product_options']);
                    if($listOpt){
                        $product_opt = array();
                        foreach($listOpt as $key => $list_opt){
                            $partOption = explode("-", $list_opt);
                            if (count($partOption) < 2) continue;
                            $option = array(
                                'label' => $partOption[0],
                                'value' => $partOption[1],
                                'print_value' => $partOption[1],
                                'option_id' => 'option_'.$key,
                                'option_type' => 'drop_down',
                                'option_value' => 0,
                                'custom_view' => false
                            );
                            $product_opt[] = $option;
                        }
                        $cart['product_options'] = serialize(array('options' => $product_opt));
                    }
                }
                $carts[]= $cart;
            }

        $customer_id = $this->getMageIdCustomer($order['customer_id']);
        $customer_name = $this->getNameFromString($order['name']);
        $order_status_id = $order['status'];

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
        $order_data['customer_email'] = $order['email'];
        $order_data['customer_firstname'] = "";
        $order_data['customer_lastname'] = $order['name'];
        $order_data['customer_group_id'] = 1;
        if($this->_notice['config']['order_status']){
            $order_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order_subtotal);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['total_ship'];
        $order_data['base_shipping_amount'] = $order['total_ship'];
        $order_data['base_shipping_invoiced'] = $order['total_ship'];
        $order_data['shipping_description'] = $order['shipMethod']. "-" .$order['prod_total']. "-" .$order['ship_date'];

        $order_tax_amount =  $order['total_tax'];
        if($order_tax_amount){
            $order_data['tax_amount'] = $order_tax_amount;
            $order_data['base_tax_amount'] = $order_tax_amount;
        }
        $order_data['discount_amount'] = $order['discount'];
        $order_data['base_discount_amount'] = $order['discount'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['prod_total']);
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
        $order_data['created_at'] = date("Y-m-d H:i:s",$order['time']);
        //  var_dump($order_data);exit;

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

    public function afterSaveOrder($order_mage_id, $data, $order, $ordersExt){
        if(parent::afterSaveOrder($order_mage_id, $data, $order, $ordersExt)){
            return ;
        }
        if ($order['comments'] || $order['customer_comments']) {
            $order_status_data = array();
            $order_status_id = $order['status'];
            $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
            if ($order_status_data['status']) {
                $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
            }
            $order_status_data['comment'] = "<b>Admin comment:</b> " . $order['comments'] . "<br /><b>Customer comment:</b>" . $order['customer_comments'];
            $order_status_data['is_customer_notified'] = 1;
            $order_status_data['updated_at'] = date("Y-m-d H:i:s", $order['time']);
            $order_status_data['created_at'] = date("Y-m-d H:i:s", $order['time']);
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
        $product_mage_id = $this->getMageIdProduct($review['productId']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Reviews Id = {$review['id']} import failed. Error : Product Id {$review['product_id']} not imported!")
            );
        }
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        if(isset($review['approved'])){
            $data['status_id'] = ($review['approved'] == 0) ? 3 : 1;
        }else{
            $data['status_id'] = 1;
        }
        $data['title'] = $review['title'];
        $data['detail'] = $review['review'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        //$data['customer_id'] = ($this->getMageIdCustomer($review['customer_id'])) ? $this->getMageIdCustomer($review['customer_id']) : null;
        $data['nickname'] = $review['name'];
        $data['rating'] = $review['rating'];
        $data['created_at'] = date("Y-m-d H:i:s", $review['time']);
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
            'query' => "SELECT * FROM _DBPRF_category WHERE cat_id = {$parent_id}"
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
     * Get array value is number in array 2D
     */
    protected function _flitArrayNum($array){
        $data = array();
        foreach($array as $value){
            if(is_numeric($value)){
                $data[] = $value;
            }
        }
        return $data;
    }
    
     protected function _getOptionTypeByTypeSrc($type_name) {
        $types = array(
            '0' => 'drop_down',
            '1' => 'field',
           // '4' => 'radio',
            '2' => 'area',
        );
        return isset($types[$type_name]) ? $types[$type_name] : false;
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
        $query = "SELECT * FROM _DBPRF_taxes ORDER BY id ASC";
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
        $query = "SELECT * FROM _DBPRF_category ORDER BY cat_id ASC";
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
        $query = "SELECT * FROM _DBPRF_inventory ORDER BY productId ASC";
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
        $query = "SELECT * FROM _DBPRF_customer ORDER BY customer_id ASC";
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
        $query = "SELECT * FROM (SELECT *,cast( REPLACE(cart_order_id,'-','') AS UNSIGNED ) as order_id FROM _DBPRF_order_sum)  ORDER BY cc.order_id";
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
        $query = "SELECT * FROM _DBPRF_reviews ORDER BY id ASC";
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

