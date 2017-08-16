<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartServiceMigrate_Model_Cart_3dcart
    extends LitExtension_CartServiceMigrate_Model_Cart{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get info of api for user config
     */
    public function getApiData(){
        return array(
            'api_key' => "API Key"
        );
    }

    /**
     * Process and get data use for config display
     *
     * @return array : Response as success or error with msg
     */
    public function displayConfig(){
        $parent = parent::displayConfig();
        if($parent['result'] != "success"){
            return $parent;
        }
        $response = array();
        $order_status_query = "SELECT * FROM order_Status";
        $orderStatus = $this->api($order_status_query);
        $customer_group_query = "SELECT * FROM discount_group";
        $customerGroup = $this->api($customer_group_query);
        if(!$orderStatus){
            return array(
                'result' => 'warning',
                'elm' => '#error-api'
            );
        }
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data = array(
            1 => "Default Language"
        );
        $currency_data = array(
            1 => "Default Currency"
        );
        $order_status_data = $customer_group_data = array();
        foreach($orderStatus as $order_status){
            $order_status_key = $order_status['StatusID'];
            $order_status_label = $order_status['StatusText'];
            $order_status_data[$order_status_key] = $order_status_label;
        }
        if($customerGroup){
            foreach($customerGroup as $customer_group){
                $group_id= $customer_group['id'];
                $group_name = $customer_group['GroupName'];
                $customer_group_data[$group_id] = $group_name;
            }
        }
        $this->_notice['config']['api_data'] = $this->getApiData();
        $this->_notice['config']['config_support']['country_map'] = false;
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['default_currency'] = 1;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        if($customer_group_data){
            $this->_notice['config']['config_support']['customer_group_map'] = true;
            $this->_notice['config']['customer_group_data'] = $customer_group_data;
        }
        $response['result'] = 'success';
        return $response;
    }

    /**
     * Save config of use in config step to notice
     */
    public function displayConfirm($params){
        $parent = parent::displayConfirm($params);
        if($parent['result'] != "success"){
            return $parent;
        }
        return array(
            'result' => "success"
        );
    }

    /**
     * Get data for import display
     *
     * @return array : Response as success or error with msg
     */
    public function displayImport(){
        $parent = parent::displayImport();
        if($parent['result'] != "success"){
            return $parent;
        }
        $recent = $this->getRecentNotice();
        if($recent){
            $types = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews');
            foreach($types as $type){
                if($this->_notice['config']['add_option']['add_new'] || !$this->_notice['config']['import'][$type]){
                    $this->_notice[$type]['id_src'] = $recent[$type]['id_src'];
                }
            }
        }
        $data_query = array(
            'taxes' => "SELECT COUNT(1) FROM tax WHERE id > {$this->_notice['taxes']['id_src']}",
            'manufacturers' => "SELECT COUNT(1) FROM manufacturer WHERE id > {$this->_notice['manufacturers']['id_src']}",
            'categories' => "SELECT COUNT(1) FROM category WHERE id > {$this->_notice['categories']['id_src']}",
            'products' => "SELECT COUNT(1) FROM products WHERE catalogid > {$this->_notice['products']['id_src']}",
            'customers' => "SELECT COUNT(1) FROM customers WHERE contactid > {$this->_notice['customers']['id_src']}",
            'orders' => "SELECT COUNT(1) FROM orders WHERE orderid > {$this->_notice['orders']['id_src']}",
            'reviews' => "SELECT COUNT(1) FROM product_review WHERE id > {$this->_notice['reviews']['id_src']}"
        );
        $totals = array();
        foreach($data_query as $type => $query){
            $count_api = $this->api($query);
            $count = reset($count_api[0]);
            if(!$count){
                $count = 0;
            }
            $totals[$type] = $count;
        }
        $iTotal = $this->_limitDemoModel($totals);
        foreach($iTotal as $type => $total){
            $this->_notice[$type]['total'] = $total;
        }
        $this->_notice['taxes']['time_start'] = time();
        if(!$this->_notice['config']['add_option']['add_new']){
            $delete = $this->deleteTable(self::TABLE_IMPORT, array(
                'domain' => $this->_cart_url
            ));
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
        $parent = parent::configCurrency();
        if($parent['result'] != "success"){
            return $parent;
        }
        return array(
            'result' => "success"
        );
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
     * @return array
     */
    public function getTaxesMain(){
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $api_query = "SELECT TOP {$limit} * FROM tax WHERE id > {$id_src} ORDER BY id ASC";
        $tax_classes = $this->api($api_query);
        if(!$tax_classes){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart")
            );
        }
        return array(
            'result' => "success",
            'data' => $tax_classes
        );
    }

    /**
     * Get data relation use for import tax rule
     *
     * @param array $taxes
     * @return array
     */
    public function getTaxesExtMain($taxes){
        return false;
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax
     * @param array $taxesExt
     * @return int
     */
    public function getTaxId($tax, $taxesExt){
        return $tax['id'];
    }

    /**
     * Convert source data to data for import
     *
     * @param array $tax
     * @param array $taxesExt
     * @return array
     */
    public function convertTax($tax, $taxesExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::TAX_CONVERT){
            return $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        }
        $tax_cus_ids = $tax_pro_ids = $tax_rate_ids = array();
        if ($tax_cus_default = $this->getIdDescTaxCustomer(1)) {
            $tax_cus_ids[] = $tax_cus_default;
        }
        $tax_pro_data = array(
            'class_name' => $tax['tax_code']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id'] , $tax_pro_ipt['mage_id'], $tax['tax_code']);
        }
        $tax_rate_data = array(
            'code' => $this->createTaxRateCode($tax['tax_country']),
            'tax_country_id' => $tax['tax_country'],
            'tax_region_id' => ($tax['tax_state'] != 'ALL') ? $tax['tax_state'] : 0,
            'zip_is_range' => 0,
            'tax_postcode' => "*",
            'rate' => $tax['tax_value1']
        );
        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
        if($tax_rate_ipt['result'] == 'success'){
            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($tax['tax_code']);
        $tax_rule_data['tax_customer_class'] = $tax_cus_ids;
        $tax_rule_data['tax_product_class'] = $tax_pro_ids;
        $tax_rule_data['tax_rate'] = $tax_rate_ids;
        $tax_rule_data['priority'] = 0;
        $tax_rule_data['position'] = 0;
        $custom = $this->_custom->convertTaxCustom($this, $tax, $taxesExt);
        if ($custom) {
            $tax_rule_data = array_merge($tax_rule_data, $custom);
        }
        return array(
            'result' => "success",
            'data' => $tax_rule_data
        );
    }

    /**
     * Process after import success one row of tax main
     *
     * @param int $tax_mage_id
     * @param array $data
     * @param array $tax
     * @param array $taxesExt
     * @return boolean
     */
    public function afterSaveTax($tax_mage_id, $data, $tax, $taxesExt){
        if(parent::afterSaveTax($tax_mage_id, $data, $tax, $taxesExt)){
            return ;
        }
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
     * Get data for convert to manufacturer option
     *
     * @return array
     */
    public function getManufacturersMain(){
        $id_src = $this->_notice['manufacturers']['id_src'];
        $limit = $this->_notice['setting']['manufacturers'];
        $api_query = "SELECT TOP {$limit} * FROM manufacturer WHERE id > {$id_src} ORDER BY id ASC";
        $api_data = $this->api($api_query);
        if(!$api_data){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart")
            );
        }
        return array(
            'result' => "success",
            'data' => $api_data
        );
    }

    /**
     * Get data relation use for import manufacturer
     *
     * @param array $manufacturers
     * @return array
     */
    public function getManufacturersExtMain($manufacturers){
        return false;
    }

    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer
     * @param array $manufacturersExt
     * @return int
     */
    public function getManufacturerId($manufacturer, $manufacturersExt){
        return $manufacturer['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer
     * @param array $manufacturersExt
     * @return array
     */
    public function convertManufacturer($manufacturer, $manufacturersExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::MANUFACTURER_CONVERT){
            return $this->_custom->convertManufacturerCustom($this, $manufacturer, $manufacturersExt);
        }
        $man_attr_id = $this->getIdDescManAttr(1);
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
            0 => $manufacturer['manufacturer']
        );
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
     * Process after one manufacturer import successful
     *
     * @param int $manufacturer_mage_id
     * @param array $data
     * @param array $manufacturer
     * @param array $manufacturersExt
     * @return boolean
     */
    public function afterSaveManufacturer($manufacturer_mage_id, $data, $manufacturer, $manufacturersExt){
        if(parent::afterSaveManufacturer($manufacturer_mage_id, $data, $manufacturer, $manufacturersExt)){
            return ;
        }
    }

    /**
     * Process before import categories
     */
    public function prepareImportCategories(){
        parent::prepareImportCategories();
    }

    /**
     * Get data of main table use import category
     *
     * @return array
     */
    public function getCategoriesMain(){
        $limit = $this->_notice['setting']['categories'];
        $id_src = $this->_notice['categories']['id_src'];
        $api_query = "SELECT TOP {$limit} * FROM category WHERE id > {$id_src} ORDER BY id ASC";
        $api_data = $this->api($api_query);
        if(!$api_data){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart.")
            );
        }
        return array(
            'result' => "success",
            'data' => $api_data
        );
    }

    /**
     * Get data relation use for import categories
     *
     * @param array $categories
     * @return array
     */
    public function getCategoriesExtMain($categories){
        return false;
    }

    /**
     * Get primary key of source category
     *
     * @param array $category
     * @param array $categoriesExt
     * @return int
     */
    public function getCategoryId($category, $categoriesExt){
        return $category['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $category
     * @param array $categoriesExt
     * @return array
     */
    public function convertCategory($category, $categoriesExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::CATEGORY_CONVERT){
            return $this->_custom->convertCategoryCustom($this, $category, $categoriesExt);
        }
        if($category['category_parent'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getIdDescCategory($category['category_parent']);
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
        $cat_data['name'] = $category['category_name'];
        $cat_des = '';
        if($category['category_header']){
            $cat_des .= "<div class='le_category_header'>". $category['category_header'] ."</div>";
        }
        if($category['category_footer']){
            $cat_des .= "<div class='le_category_footer'>". $category['category_footer'] ."</div>";
        }
        $cat_data['description'] = $cat_des;
        $cat_data['meta_title'] = $category['category_title'];
        $meta = $this->_getMetaTags($category['category_meta']);
        $cat_data['meta_keywords'] = isset($meta['keywords']) ? $meta['keywords'] : '';
        $cat_data['meta_description'] = isset($meta['description']) ? $meta['description'] : '';
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['hide'] == 1) ? 0 : 1;
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
            'result' => "success",
            'data' => $cat_data
        );
    }

    /**
     * Process after one category import successful
     *
     * @param int $category_mage_id
     * @param array $data
     * @param array $category
     * @param array $categoriesExt
     * @return boolean
     */
    public function afterSaveCategory($category_mage_id, $data, $category, $categoriesExt){
        if(parent::afterSaveCategory($category_mage_id, $data, $category, $categoriesExt)){
            return ;
        }
    }

    /**
     * Process before import products
     */
    public function prepareImportProducts(){
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids']= $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    /**
     * Get data of main table use for import product
     *
     * @return array
     */
    public function getProductsMain(){
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $query = "SELECT TOP {$limit} * FROM products WHERE catalogid > {$id_src} ORDER BY catalogid ASC";
        $products = $this->api($query);
        if(!$products){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart.")
            );
        }
        return array(
            'result' => "success",
            'data' => $products
        );
    }

    /**
     * Get data relation use for import product
     *
     * @param array $products
     * @return array
     */
    public function getProductsExtMain($products){
        $result = array();
        $pro_ids = $this->duplicateFieldValueFromList($products['data'], 'catalogid');
        $pro_ids_con = $this->_arrayIdsToInCondition($pro_ids);
        $multi_query = array(
            'product_category' => "SELECT * FROM product_category WHERE catalogid IN {$pro_ids_con}",
            'product_images' => "SELECT * FROM product_images WHERE catalogid IN {$pro_ids_con}",
            'pricing' => "SELECT * FROM pricing WHERE prod_id IN {$pro_ids_con}",
            'product_related' => "SELECT * FROM product_related WHERE catalogid IN {$pro_ids_con} OR related_id IN {$pro_ids_con}",
            'product_accessories' => "SELECT * FROM product_accessories WHERE catalogid IN {$pro_ids_con} OR accessory_id IN {$pro_ids_con}"
        );
        if($multi_query){
            foreach($multi_query as $key => $query){
                $result[$key] = $this->api($query);
            }
        }
        $cat_ids = $this->duplicateFieldValueFromList($result['product_category'], 'categoryid');
        $cat_ids_con = $this->_arrayIdsToInCondition($cat_ids);
        $multi_query = array(
            'prodfeatures' => "SELECT * FROM prodfeatures WHERE category_id IN {$cat_ids_con} OR item_id IN {$pro_ids_con}"
        );
        if($multi_query){
            foreach($multi_query as $key => $query){
                $result[$key] = $this->api($query);
            }
        }
        $feature_ids = $this->duplicateFieldValueFromList($result['prodfeatures'], 'id');
        $feature_ids_con = $this->_arrayIdsToInCondition($feature_ids);
        $multi_query = array(
            'prodfeatures_options' => "SELECT * FROM prodfeatures_options WHERE caption_id IN {$feature_ids_con}"
        );
        if($multi_query){
            foreach($multi_query as $key => $query){
                $result[$key] = $this->api($query);
            }
        }
        return array(
            'result' => "success",
            'data' => $result
        );
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product
     * @param array $productsExt
     * @return int
     */
    public function getProductId($product, $productsExt){
        return $product['catalogid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $product
     * @param array $productsExt
     * @return array
     */
    public function convertProduct($product, $productsExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product, $productsExt);
        }
        $pro_data = $categories = array();
        $proCat = $this->getListFromListByField($productsExt['data']['main']['product_category'], 'catalogid', $product['catalogid']);
        if($proCat){
            foreach($proCat as $pro_cat){
                $cat_id = $this->getIdDescCategory($pro_cat['categoryid']);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        for($i = 1; $i <= 13; $i++){
            $attr_code = 'le_extra_field_' . $i;
            $attr_label = 'Extra Field ' . $i;
            if($i > 5){
                $type = 'textarea';
            }else{
                $type = 'text';
            }
            $attr_import = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' => $attr_code,
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => $type,
                'frontend_label' => array($attr_label),
                'is_visible_on_front' => 1,
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'option' => array(
                    'value' => array('option_0' => array(''))
                )
            );
            $attrAfterImport = $this->_process->attribute($attr_import);
            if($attrAfterImport){
                $pro_data[$attrAfterImport['attribute_code']] = $product['extra_field_' . $i];
            }
        }
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_data['sku'] = $this->createProductSku($product['id'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['name'];
        $pro_des = str_replace('"/assets/images/', rtrim($this->_cart_url, '/') . '"/assets/images/', $product['extended_description']);
        $pro_short_des = str_replace('"/assets/images/', rtrim($this->_cart_url, '/') . '"/assets/images/', $product['description']);
        $pro_data['description'] = $this->changeImgSrcInText($pro_des, $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($pro_short_des, $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $product['title'] ;
        $meta = $this->_getMetaTags($product['metatags']);
        $pro_data['meta_keyword'] = (isset($meta['keywords'])) ? $meta['keywords'] : '';
        $pro_data['meta_description'] = (isset($meta['description'])) ? $meta['description'] : '';
        if($product['price2']){
            $pro_data['price'] = $product['price2'];
            if($product['onsale'] == 1 && $product['saleprice'] > 0){
                $pro_data['special_price'] =  $product['saleprice'];
            }else{
                $pro_data['special_price'] =  $product['price'];
            }
        }else{
            $pro_data['price'] = $product['price'];
            if($product['onsale'] == 1 && $product['saleprice'] > 0){
                $pro_data['special_price'] =  $product['saleprice'];
            }
        }
        $tierPrices = array();
        $discountRules = $this->getListFromListByField($productsExt['data']['main']['pricing'], 'prod_id', $product['catalogid']);
        if($discountRules){
            foreach($discountRules as $discount_rule){
                if(isset($discount_rule['lowbound']) && is_numeric($discount_rule['lowbound'])){
                    if($discount_rule['percentage'] == 'True'){
                        $dis_price = $pro_data['price'] * $discount_rule['price'] / 100;
                    }else{
                        $dis_price = $discount_rule['price'];
                    }
                    $tierPrices[] = array(
                        'website_id'  => 0,
                        'cust_group'  => 32000,
                        'price_qty'   => $discount_rule['lowbound'],
                        'price'       => $dis_price
                    );
                }
            }
        }
        $pro_data['tier_price'] = $tierPrices;
        $pro_data['weight'] = $product['weight'] ? $product['weight']: 0 ;
        $pro_data['status'] = ($product['hide'] == 1) ? 2 : 1;
        if($product['tax_code'] && $tax_pro_id = $this->getIdDescTaxProduct($product['tax_code'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        }else{
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $product['date_created'];
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['stock_data'] = array(
            'is_in_stock' => ($product['stock'] < 1) ? 0 : 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['stock'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['stock'] < 1)? 0 : 1,
            'qty' => $product['stock']
        );
        if($manufacture_mage_id = $this->getIdDescManufacturer($product['manufacturer'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        $import_thumb = false;
        $thumb_name = $this->_getImageNameFromString($product['thumbnail']);
        for($i = 1; $i <=4; $i++){
            $image_name = "image" . $i;
            if($product[$image_name]){
                $imgFile = $this->_getImageNameFromString($product[$image_name]);
                if($this->_checkThumbnail($imgFile, $thumb_name)){
                    if($image_path = $this->downloadImage(rtrim($this->_cart_url, '/'), $product[$image_name], 'catalog/product', false, true)){
                        $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                        $import_thumb = true;
                    }
                }else{
                    if($gallery_path = $this->downloadImage(rtrim($this->_cart_url, '/'), $product[$image_name], 'catalog/product', false, true)){
                        $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '') ;
                    }
                }
            }
        }
        $proImg = $this->getListFromListByField($productsExt['data']['main']['product_images'], 'catalogid', $product['catalogid']);
        if($proImg){
            foreach($proImg as $gallery){
                $imgFile = $this->_getImageNameFromString($gallery['image']);
                if($this->_checkThumbnail($imgFile, $thumb_name)){
                    if($image_path = $this->downloadImage(rtrim($this->_cart_url, '/'), $gallery['image'], 'catalog/product', false, true)){
                        $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
                        $import_thumb = true;
                    }
                }else{
                    if($gallery_path = $this->downloadImage(rtrim($this->_cart_url, '/'), $gallery['image'], 'catalog/product', false, true)){
                        $pro_data['image_gallery'][] = array('path' => $gallery_path, 'label' => '') ;
                    }
                }
            }
        }
        if(!$import_thumb){
            if($product['thumbnail'] != '' && $image_path = $this->downloadImage(rtrim($this->_cart_url, '/'), $product['thumbnail'], 'catalog/product', false, true)){
                $pro_data['image_import_path'] = array('path' => $image_path, 'label' => '');
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
        //Related
        $proRelated = $this->getListFromListByField($productsExt['data']['main']['product_related'], 'catalogid', $product['catalogid']);
        if($proRelated){
            foreach($proRelated as $pro_related){
                if($pro_id_related = $this->getIdDescProduct($pro_related['related_id'])){
                    $products_links->assign("related", $product_mage_id, $pro_id_related);
                }else{
                    continue;
                }
            }
        }
        $proSrc = $this->getListFromListByField($productsExt['data']['main']['product_related'], 'related_id', $product['catalogid']);
        if($proSrc){
            foreach($proSrc as $pro_src){
                if($proSrcId = $this->getIdDescProduct($pro_src['catalogid'])){
                    $products_links->assign("related", $proSrcId, $product_mage_id);
                }else{
                    continue;
                }
            }
        }
        //Upsell
        $proUpsell = $this->getListFromListByField($productsExt['data']['main']['product_accessories'], 'catalogid', $product['catalogid']);
        if($proUpsell){
            foreach($proUpsell as $pro_upsell){
                if($pro_id_upsell = $this->getIdDescProduct($pro_upsell['accessory_id'])){
                    $products_links->assign("up_sell", $product_mage_id, $pro_id_upsell);
                }else{
                    continue;
                }
            }
        }
        $proSrc = $this->getListFromListByField($productsExt['data']['main']['product_accessories'], 'accessory_id', $product['catalogid']);
        if($proSrc){
            foreach($proSrc as $pro_src){
                if($proSrcId = $this->getIdDescProduct($pro_src['catalogid'])){
                    $products_links->assign("up_sell", $proSrcId, $product_mage_id);
                }else{
                    continue;
                }
            }
        }
        //Custom option
        if($product['usecatoptions'] == 0){
            $proCat = $this->getListFromListByField($productsExt['data']['main']['product_category'], 'catalogid', $product['catalogid']);
            if($proCat){
                foreach($proCat as $cat){
                    $proFeatures = $this->getListFromListByField($productsExt['data']['main']['prodfeatures'], 'category_id', $cat['categoryid']);
                    $this->_importCustomOptions($productsExt, $proFeatures, $product_mage_id);
                }
            }
        }
        //Product Feature Manually
        $proFeatures = $this->getListFromListByField($productsExt['data']['main']['prodfeatures'], 'item_id', $product['catalogid']);
        $this->_importCustomOptions($productsExt, $proFeatures, $product_mage_id);
    }

    /**
     * Process before import import customers
     */
    public function prepareImportCustomers(){
        parent::prepareImportCustomers();
    }

    /**
     * Get data of main table use for import customer
     *
     * @return array
     */
    public function getCustomersMain(){
        $limit = $this->_notice['setting']['customers'];
        $id_src = $this->_notice['customers']['id_src'] ;
        $query = "SELECT TOP {$limit} * FROM customers WHERE contactid > {$id_src} ORDER BY contactid ASC";
        $customers = $this->api($query);
        if(!$customers){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart")
            );
        }
        return array(
            'result' => "success",
            'data' => $customers
        );
    }

    /**
     * Get data relation use for import customer
     *
     * @param array $customers
     * @return array
     */
    public function getCustomersExtMain($customers){
       return false;
    }

    /**
     * Get primary key of source customer main
     *
     * @param array $customer
     * @param array $customersExt
     * @return int
     */
    public function getCustomerId($customer, $customersExt){
        return $customer['contactid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $customer
     * @param array $customersExt
     * @return array
     */
    public function convertCustomer($customer, $customersExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::CUSTOMER_CONVERT){
            return $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        }
        $cus_data = array();
        if ($this->_notice['config']['add_option']['pre_cus']) {
            $cus_data['id'] = $customer['contactid'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['billing_firstname'];
        $cus_data['lastname'] = $customer['billing_lastname'];
        $cus_data['created_at'] = $customer['last_update'];
        $cus_data['updated_at'] = $customer['last_update'];
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$customer['discount']]) ? $this->_notice['config']['customer_group'][$customer['discount']] : 1;
        $custom = $this->_custom->convertCustomerCustom($this, $customer, $customersExt);
        if ($custom) {
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
     * @param int $customer_mage_id
     * @param array $data
     * @param array $customer
     * @param array $customersExt
     * @return boolean
     */
    public function afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt){
        if(parent::afterSaveCustomer($customer_mage_id, $data, $customer, $customersExt)){
            return ;
        }
        $add_type = array('billing', 'shipping');
        foreach($add_type as $type){
            $address = array();
            $address['firstname'] = $customer[$type . '_firstname'];
            $address['lastname'] = $customer[$type .'_lastname'];
            $address['country_id'] = $customer[$type .'_country'];
            $address['street'] = $customer[$type .'_address']."\n".$customer[$type .'_address2'];
            $address['postcode'] = $customer[$type .'_zip'];
            $address['city'] = $customer[$type .'_city'];
            $address['telephone'] = $customer[$type .'_phone'];
            $address['company'] = $customer[$type .'_company'];
            if($customer[$type .'_state']){
                $region_id = $this->_getRegionIdByCode($customer[$type .'_state'],  $address['country_id']);
                if($region_id){
                    $address['region_id'] = $region_id;
                }
                $address['region'] = $customer[$type .'_state'];
            }
            $address_ipt = $this->_process->address($address, $customer_mage_id);
            try{
                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                if($type == 'billing'){
                    $cus->setDefaultBilling($address_ipt['mage_id']);
                }else{
                    $cus->setDefaultShipping($address_ipt['mage_id']);
                }
                $cus->save();
            }catch (Exception $e){}
        }
    }

    /**
     * Process before import orders
     */
    public function prepareImportOrders(){
        parent::prepareImportOrders();
    }

    /**
     * Get data use for import order
     *
     * @return array
     */
    public function getOrdersMain(){
        $limit = $this->_notice['setting']['orders'];
        $id_src = $this->_notice['orders']['id_src'];
        $query = "SELECT TOP {$limit} * FROM orders WHERE orderid > {$id_src} ORDER BY orderid ASC";
        $orders = $this->api($query);
        if(!$orders){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart")
            );
        }
        return array(
            'result' => "success",
            'data' => $orders
        );
    }

    /**
     * Get data relation use for import order
     *
     * @param array $orders
     * @return array
     */
    public function getOrdersExtMain($orders){
        $result = array();
        $order_ids = $this->duplicateFieldValueFromList($orders['data'], 'orderid');
        $order_ids_con = $this->_arrayIdsToInCondition($order_ids);
        $multi_query = array(
            'oitems' => "SELECT * FROM oitems WHERE orderid IN {$order_ids_con}"
        );
        if($multi_query){
            foreach($multi_query as $key => $query){
                $result[$key] = $this->api($query);
            }
        }
        return array(
            'result' => "success",
            'data' => $result
        );
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order
     * @param array $ordersExt
     * @return int
     */
    public function getOrderId($order, $ordersExt){
        return $order['orderid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order
     * @param array $ordersExt
     * @return array
     */
    public function convertOrder($order, $ordersExt){
        if (LitExtension_CartServiceMigrate_Model_Custom::ORDER_CONVERT) {
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();

        $address_billing['firstname'] = $order['ofirstname'];
        $address_billing['lastname'] = $order['olastname'];
        $address_billing['company'] = $order['ocompany'];
        $address_billing['email']   = $order['oemail'];
        $address_billing['street']  = $order['oaddress']."\n".$order['oaddress2'];
        $address_billing['city'] = $order['ocity'];
        $address_billing['postcode'] = $order['ozip'];
        $address_billing['country_id'] = $order['ocountry'];
        $billing_region_id = $this->_getRegionIdByCode($order['ostate'], $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $order['ostate'];
        }
        $address_billing['telephone'] = $order['ophone'];

        $address_shipping['firstname'] = $order['oshipfirstname'];
        $address_shipping['lastname'] = $order['oshiplastname'];
        $address_shipping['company'] = $order['oshipcompany'];
        $address_shipping['email']   = $order['oshipemail'];
        $address_shipping['street']  = $order['oshipaddress']."\n".$order['oshipaddress2'];
        $address_shipping['city'] = $order['oshipcity'];
        $address_shipping['postcode'] = $order['oshipzip'];
        $address_shipping['country_id'] = $order['oshipcountry'];
        $shipping_region_id = $this->_getRegionIdByCode($order['oshipstate'], $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $order['oshipstate'];
        }
        $address_shipping['telephone'] = $order['oshipphone'];

        $carts = array();
        $order_subtotal = 0;
        if($orderPro = $this->getListFromListByField($ordersExt['data']['main']['oitems'], 'orderid', $order['orderid'])){
            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getIdDescProduct($order_pro['catalogid']);
                if ($product_id) {
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $pro_name = $order_pro['itemname'];
                $ex1 = preg_split('/<br[^>]*>/i', $pro_name);
                $cart['name'] = isset($ex1[0]) ? $ex1[0] : '';
                $cart['sku'] = $order_pro['itemid'];
                $cart['price'] = $order_pro['unitprice'] + $order_pro['optionprice'];
                $cart['original_price'] = $order_pro['unitprice'] + $order_pro['optionprice'];
                $cart['qty_ordered'] = $order_pro['numitems'];
                $cart['row_total'] = $order_pro['numitems'] * $cart['price'];
                $order_subtotal += $cart['row_total'];
                if(!empty($ex1)){
                    $product_opt = array();
                    foreach($ex1 as $key => $ex1_data){
                        if($key == 0){
                            continue;
                        }
                        $ex1_data = strip_tags($ex1_data);
                        $ex2 = explode(': ', $ex1_data);
                        $option = array(
                            'label' => isset($ex2[0]) ? $ex2[0] : '',
                            'value' => isset($ex2[1]) ? $ex2[1] : '',
                            'print_value' => isset($ex2[1]) ? $ex2[1] : '',
                            'option_id' => 0 ,
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $product_opt[] = $option;
                    }
                    $cart['product_options'] = serialize(array('options' => $product_opt));
                }
                $carts[]= $cart;
            }
        }
        $order_data = array();
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $customer_id = $this->getIdDescCustomer($order['ocustomerid']);
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['oemail'];
        $order_data['customer_firstname'] = $order['ofirstname'];
        $order_data['customer_lastname'] = $order['olastname'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order['order_status']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order_subtotal);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['oshipcost'];
        $order_data['base_shipping_amount'] = $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order_data['shipping_amount'];
        $order_data['shipping_description'] = $order['oshipmethod'];
        $order_data['tax_amount'] = $order['otax'] + $order['otax2'] + $order['otax3'];
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['odiscount'] + $order['coupondiscount'];
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['orderamount']);
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
        $order_data['created_at'] = date("Y-m-d H:i:s", strtotime($order['date_started']));
        $order_data['updated_at'] = date("Y-m-d H:i:s", strtotime($order['last_update']));

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['orderid'];
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
     * @param int $order_mage_id
     * @param array $data
     * @param array $order
     * @param array $ordersExt
     * @return boolean
     */
    public function afterSaveOrder($order_mage_id, $data, $order, $ordersExt){
        if(parent::afterSaveOrder($order_mage_id, $data, $order, $ordersExt)){
            return ;
        }
        $order_status_data = array();
        $order_status_data['status'] = $data['order']['status'];
        if($order_status_data['status']){
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = $data['order']['updated_at'];
        $order_status_data['created_at'] = $data['order']['created_at'];
        $order_status_data['comment'] = "<b>Reference order #".$order['orderid']."</b><br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order['ocomment'];
        $order_status_data['is_customer_notified'] = 1;
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }

    /**
     * Process before import reviews
     */
    public function prepareImportReviews(){
        parent::prepareImportReviews();
    }

    /**
     * Get main data use for import review
     *
     * @return array
     */
    public function getReviewsMain(){
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $api_query = "SELECT TOP {$limit} * FROM product_review WHERE id > {$id_src} ORDER BY id ASC";
        $reviews = $this->api($api_query);
        if(!$reviews){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart")
            );
        }
        return array(
            'result' => "success",
            'data' => $reviews
        );
    }

    /**
     * Get relation data use for import reviews
     *
     * @param array $reviews
     * @return array
     */
    public function getReviewsExtMain($reviews){
        return false;
    }

    /**
     * Get primary key of source review main
     *
     * @param array $review
     * @param array $reviewsExt
     * @return int
     */
    public function getReviewId($review, $reviewsExt){
        return $review['id'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $review
     * @param array $reviewsExt
     * @return array
     */
    public function convertReview($review, $reviewsExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::REVIEW_CONVERT){
            return $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        }
        $product_mage_id = $this->getIdDescProduct($review['catalogid']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['id']} import failed. Error: Product Id = {$review['catalogid']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = ($review['approved'] == 1)? 1 : 3;
        $data['title'] = $review['short_review'];
        $data['detail'] = $review['long_review'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getIdDescCustomer($review['userid']))? $this->getIdDescCustomer($review['userid']) : null;
        $data['nickname'] = $review['user_name'];
        $data['rating'] = $review['rating'];
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($review['review_date']));
        $data['review_id_import'] = $review['id'];
        $custom = $this->_custom->convertReviewCustom($this, $review, $reviewsExt);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => "success",
            'data' => $data
        );
    }

    /**
     * Process after one review save successful
     *
     * @param int $review_mage_id
     * @param array $data
     * @param array $review
     * @param array $reviewsExt
     * @return boolean
     */
    public function afterSaveReview($review_mage_id, $data, $review, $reviewsExt){
        if(parent::afterSaveReview($review_mage_id, $data, $review, $reviewsExt)){
            return ;
        }
    }


    ############################################################ Extend function ##################################
    protected function _getRegionIdByCode($region_code, $country_code){
        $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
        $regionId = $regionModel->getId();
        if($regionId) return $regionId;
        return false;
    }

    protected function _importCustomOptions($productsExt, $proFeatures, $product_mage_id){
        if($proFeatures){
            $custom_option = array();
            foreach($proFeatures as $feature){
                $options = array();
                $feature_opts = $this->getListFromListByField($productsExt['data']['main']['prodfeatures_options'], 'caption_id', $feature['id']);
                if($feature_opts){
                    foreach($feature_opts as $opt){
                        $tmp['option_type_id'] = -1;
                        $tmp['title'] = $opt['featurename'];
                        $tmp['price'] = $opt['featureprice'];
                        $tmp['price_type'] = 'fixed';
                        $tmp['sort_order'] = $opt['sorting'];
                        $options[]=$tmp;
                    }
                }
                $feature_type = 'drop_down';
                if($feature['featuretype'] == 'Dropdown'){
                    $feature_type = 'drop_down';
                }
                if($feature['featuretype'] == 'Radio'){
                    $feature_type = 'radio';
                }
                if($feature['featuretype'] == 'Checkbox'){
                    $feature_type = 'checkbox';
                }
                if($feature['featuretype'] == 'Text'){
                    $feature_type = 'field';
                }
                if($feature['featuretype'] == 'TextArea'){
                    $feature_type = 'area';
                }
                if($feature['featuretype'] == 'File'){
                    $feature_type = 'file';
                }
                $tmp_opt = array(
                    'title' => $feature['featurecaption'],
                    'type' => $feature_type,
                    'is_require' => ($feature['featurerequired'] == 1) ? 1 : 0,
                    'sort_order' => $feature['sorting'],
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
            $this->importProductOption($product_mage_id, $custom_option);
        }
    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $cat_query = "SELECT * FROM category WHERE id = {$parent_id}";
        $category = $this->api($cat_query);
        if(!$category){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart.")
            );
        }
        if(is_array($category)){
            $categories = $category;
            $category = $category[0];
        }else{
            $categories = array(0 => $category);
        }
        $categoriesExt = $this->getCategoriesExt($categories);
        if($categoriesExt['result'] != 'success'){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from 3dcart.")
            );
        }
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

    public function api($query){
        $result = array();
        $url = parse_url($this->_cart_url);
        $params = array(
            'storeUrl' =>  $url['host'],
            'userKey' => trim($this->_notice['config']['api']['api_key'])
        );
        $db =  new soapclient('http://api.3dcart.com/cart_advanced.asmx?WSDL', array('trace' => 1,'soap_version' => SOAP_1_1));
        $data = $db->runQuery($params + array('sqlStatement' => $query));
        $any = $data->runQueryResult->any;
        if($any){
            $xml = simplexml_load_string($any, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $jsonDecode = json_decode($json,TRUE);
            if(isset($jsonDecode['runQueryRecord'])){
                $result = $jsonDecode['runQueryRecord'];
                if(count($result, COUNT_RECURSIVE) == count($result)){
                    $result = array($this->_valueEmptyArrayToFalse($result, true));
                }else{
                    $result = $this->_valueEmptyArrayToFalse($result);
                }
            }
        }
        return $result;
    }

    protected function _valueEmptyArrayToFalse($array, $single_array = false){
        if(!$array){
            return $array;
        }
        if($single_array){
            foreach($array as $key => $row){
                if(!$row){
                    $array[$key] = false;
                }
            }
        }else{
            foreach($array as $key => $row){
                if(is_array($row)){
                    foreach($row as $name => $value){
                        if(!$value){
                            $array[$key][$name] = false;
                        }
                    }
                }
            }
        }
        return $array;
    }

    protected function _getMetaTags($str)
    {
        $result = array();
        $pattern = '
      ~<\s*meta\s

      # using lookahead to capture type to $1
        (?=[^>]*?
        \b(?:name|property|http-equiv)\s*=\s*
        (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
        ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
      )

      # capture content to $2
      [^>]*?\bcontent\s*=\s*
        (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
        ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
      [^>]*>

      ~ix';

        if(preg_match_all($pattern, $str, $out)){
            $tmp =  array_combine($out[1], $out[2]);
            if($tmp){
                foreach($tmp as $key => $value){
                    $result[strtolower($key)] = $value;
                }
            }
        }
        return $result;
    }

    protected function _arrayIdsToInCondition($array){
        if(empty($array)){
            return "('null')";
        }
        $result = "(" . implode(",", $array) . ")";
        return $result;
    }

    protected function _getImageNameFromString($string){
        $info = pathinfo($string);
        if(isset($info['filename']) && $info['filename']){
            return strtolower($info['filename']);
        }
        return false;
    }

    protected function _checkThumbnail($image_name, $thumbnail_name){
        if (strpos($thumbnail_name, $image_name . '_') !== false) {
            return true;
        }
        if (strpos($thumbnail_name, '_' . $image_name) !== false) {
            return true;
        }
        return false;
    }
}