<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
//9cbdb2626c93808384bf513ecf8e0618

class LitExtension_CartServiceMigrate_Model_Cart_Americommerce
    extends LitExtension_CartServiceMigrate_Model_Cart {
    
    public function __construct(){
        parent::__construct();
    }
    
    public function displayConfig(){
        
        $parent = parent::displayConfig();
        
        if($parent['result'] != "success"){
            return $parent;
        }
        
        $order_status_data = array();
        $response = array();
        
        $orderStatus = $this->api('order_statuses');
        $order_status = json_decode($orderStatus, true);
        if($order_status['order_statuses']){
            foreach ($order_status['order_statuses'] as $order_stt){
                $order_id = $order_stt['id'];
                $order_name = $order_stt['name'];
                $order_status_data[$order_id] = $order_name;
            }
        }
        $language_data = array(
            1 => "Default Language"
        );
        
        $currency = "USD";
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $this->_notice['config']['api_data'] = $this->getApiData();
        
        $this->_notice['config']['config_support']['country_map'] = false;
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['default_currency'] = $currency;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = array($currency => $currency);
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $response['result'] = 'success';
        return $response;
    }
    
    public function displayConfirm($params) {
        $parent = parent::displayConfirm($params);
        if($parent['result'] != "success"){
            return $parent;
        }
        return array(
            'result' => "success"
        );
    }

    
    public function displayImport() {
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
                    $this->_notice[$type]['imported'] = $recent[$type]['imported'];
                }
            }
        }
        usleep(10000000);
        $listTaxes = json_decode($this->api('tax_rates'), true);
        $listManufacturers = json_decode($this->api('manufacturers'), true);
        $listCategories = json_decode($this->api('categories'), true);
        $listProducts = json_decode($this->api('products'), true);
        $listCustomers = json_decode($this->api('customers'), true);
        usleep(10000000);
        $listOrders = json_decode($this->api('orders'), true);
        $listReviews = json_decode($this->api('product_reviews'), true);
        
//        if(!$listTaxes || !$listManufacturers || $listCategories || !$listProducts || !$listCustomers || !$listOrders || !isset($listReviews)){
//            return array(
//                'result' => 'error',
//                'msg' => "Could not get data from Americommerce"
//            );
//        }
        
        $tax_count = isset($listTaxes['total_count']) ? $listTaxes['total_count'] : 0;
        $manu_count = isset($listManufacturers['total_count']) ? $listManufacturers['total_count'] : 0;
        $cat_count = isset($listCategories['total_count']) ? $listCategories['total_count'] : 0;
        $prod_count = isset($listProducts['total_count']) ? $listProducts['total_count'] : 0;
        $cus_count = isset($listCustomers['total_count']) ? $listCustomers['total_count'] : 0;
        $ord_count = isset($listOrders['total_count']) ? $listOrders['total_count'] : 0;
        $rev_count = isset($listReviews['total_count']) ? $listReviews['total_count'] : 0;
        
        $total = array(
            'taxes' => $tax_count,
            'manufacturers' => $manu_count,
            'categories' => $cat_count,
            'products' => $prod_count,
            'customers' => $cus_count,
            'orders' => $ord_count,
            'reviews' => $rev_count
        );
        $totals = $this->_limitDemoModel($total);
        foreach ($totals as $type => $count){
            $this->_notice[$type]['total'] = $count;
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

    public function configCurrency() {
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
    
    public function prepareImportTaxes() {
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
        $imported = $this->_notice['taxes']['imported'];
        $limit = $this->_notice['setting']['taxes'];
        $page = floor($imported/$limit) + 1;
        $list_taxes = $this->api('tax_rates.json?page=' . $page . '&count=' . $limit);
        $listTaxes = json_decode($list_taxes, true);
        return array(
            'result' => "success",
            'data' => $listTaxes['tax_rates']
        );
    }

    /**
     * Get data relation use for import tax rule
     *
     * @param array $taxes
     * @return array
     */
    
    public function getTaxesExtMain($taxes){
        $get_regions = $this->api('regions.json');
        $getRegions = json_decode($get_regions, true);
        if(!$get_regions){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get TaxRegions from Americommerce")
            );
        }
        return array(
            'result' => "success",
            'data' => $getRegions['regions']
        );
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
            'class_name' => $tax['name']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess(1, $tax_pro_ipt['mage_id']);
        }
        $tax_region = $this->getRowFromListByField($taxesExt['data']['main'], 'id', $tax['region_id']);
        $tax_rate_data = array();
        $tax_rate_data['code'] = $this->createTaxRateCode($tax['name']);
        $tax_rate_data['tax_country_id'] = $tax_region['postal_code_country'];
        
        $tax_rate_data['tax_region_id'] = $tax_region['id']; //$this->getRegionId($tax_region['states'][0], $tax_region['postal_code_country']);
        $tax_rate_data['zip_is_range'] = 0;
        $tax_rate_data['tax_postcode'] = $tax_region['postal_codes'][0];
        $tax_rate_data['rate'] = $tax['rate'];
        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
        if ($tax_rate_ipt['result'] == 'success') {
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
        if ($custom) {
            $tax_rule_data = array_merge($tax_rule_data, $custom);
        }
        return array(
            'result' => 'success',
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
        if ($man_attr['result'] == 'success') {
            $this->manAttrSuccess(1, $man_attr['mage_id']);
        }
    }
    
    /**
     * Get data for convert to manufacturer option
     *
     * @return array
     */
    public function getManufacturersMain(){
        $imported = $this->_notice['manufacturers']['imported'];
        $limit = $this->_notice['setting']['manufacturers'];
        $page = floor($imported / $limit) + 1;
        usleep(10000000);
        $list_manufacturers = $this->api('manufacturers?page=' . $page . '&count=' . $limit);
        $listManufacturers = json_decode($list_manufacturers, true);
        return array(
            'result' => "success",
            'data' => $listManufacturers['manufacturers']
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
            0 => $manufacturer['name']
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
        $imported = $this->_notice['categories']['imported'];
        $limit = $this->_notice['setting']['categories'];
        $page = floor($imported/$limit) + 1;
        usleep(10000000);
        $list_categories = $this->api('categories.json?page=' . $page . '&count=' . $limit);
        $listCategories = json_decode($list_categories, true);
        return array(
            'result' => "success",
            'data' => $listCategories['categories']
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
        if($category['parent_category_id'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getIdDescCategory($category['parent_category_id']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['parent_category_id']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['id']} import failed. Error: Could not import parent category id = {$category['parent_category_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['name'];
        $cat_data['description'] = $category['short_description'];
        $cat_data['meta_title'] = $category['page_title'];
        $cat_data['meta_keywords'] = $category['keywords'];
        $cat_data['meta_description'] = $category['meta_description'];
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = ($category['is_hidden'] == false) ? 1 : 0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = 1;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        if ($category['category_thumbnail']) {
            $image_path = $this->downloadImage(rtrim($this->_cart_url, '/') , $category['category_thumbnail'], 'catalog/category');
            if ($image_path) {
                $cat_data['image'] = $image_path;
            }
        }
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
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }
    
    /**
     * Get data of main table use for import product
     *
     * @return array
     */
    public function getProductsMain(){
        $imported = $this->_notice['products']['imported'];
        $limit = $this->_notice['setting']['products'];
        $page = floor($imported/$limit) + 1;
        usleep(10000000);
        $products = $this->api('products.json?page=' . $page . '&count=' . $limit);
        $productsPage = json_decode($products, true);
        return array(
            'result' => "success",
            'data' => $productsPage['products']
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
        usleep(10000000);
        $productsImg = $this->api('product_pictures.json');
        $products_img = json_decode($productsImg, true);
        $productsVar = $this->api('product_variants.json');
        $products_var = json_decode($productsVar, true);
        $productsOptions = $this->api('variant_groups.json');
        $products_option = json_decode($productsOptions, true);
//        if(!$productsImg || !$productsVar || !$productsOptions){
//            return array(
//                'result' => "error",
//                'msg' => "Could not get data Products from Americommerce!"
//            );
//        }
        $result['product_img'] = $products_img['pictures'];
        $result['product_var'] = $products_var['variants'];
        $result['product_opt'] = $products_option['variant_groups'];
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
        return $product['id'];
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
        if($product['primary_category_id']){
            $cat_id = $this->getIdDescCategory($product['primary_category_id']);
            if($cat_id){
                $categories[] = $cat_id;
            }
        }
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_data['sku'] = $product['item_number'];
        $pro_data['name'] = $product['item_name'];
        $pro_data['description'] = $this->changeImgSrcInText($product['long_description_1'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $product['short_description'];
        $pro_data['meta_title'] = $product['page_title'] ;
        $pro_data['meta_keyword'] = $product['keywords'];
        $pro_data['meta_description'] = $product['meta_description'];
        $pro_data['price'] = $product['price'];
        $pro_data['special_price'] =  $product['retail'];
        $tierPrices = array();
        $pro_data['tier_price'] = $tierPrices;
        $pro_data['weight'] = $product['weight'] ? $product['weight'] : "";
        $pro_data['status'] = ($product['is_hidden'] == 1) ? 2 : 1;
        if($product['tax_code'] && $tax_pro_id = $this->getIdDescTaxProduct($product['tax_code'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        }else{
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['created_at'] = $this->_getTime($product['created_at']);
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['stock_data'] = array(
            'is_in_stock' => ($product['quantity_on_hand'] < 1) ? 0 : 1,
            'manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['quantity_on_hand'] < 1)? 0 : 1,
            'use_config_manage_stock' => ($this->_notice['config']['add_option']['stock'] && $product['quantity_on_hand'] < 1)? 0 : 1,
            'qty' => $product['quantity_on_hand']
        );
        if($manufacture_mage_id = $this->getIdDescManufacturer($product['manufacturer_id'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        
        $prod_img = $this->getListFromListByField($productsExt['data']['main']['product_img'], 'product_id', $product['id']);
        if($prod_img){
            foreach ($prod_img as $img){
                $path = $this->downloadImage(rtrim($this->_cart_url, '/'), $img['image_file'], 'catalog/product', false, true);
                if($img['is_primary'] == 'true'){
                    $pro_data['image_import_path'] = array('path' => $path, 'label' => '');
                }else{
                    $pro_data['image_gallery'][] = array('path' => $path, 'label' => '');
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
        //Related - Upsell
//        $getRelatedUpsell = $this->api('products/' . $product['id'] . '/related');
//        $getAll = json_decode($getRelatedUpsell, true);
//        $dataRU = $getAll['related'];
//        if($dataRU){
//            $relatedProducts = $this->getListFromListByField($dataRU, 'is_upsell', 'false');
//            $upsellProducts = $this->getListFromListByField($dataRU, 'is_upsell', 'true');
//            if($relatedProducts){
//                $related_Products = $this->duplicateFieldValueFromList($relatedProducts, 'id');
//                $this->setProductRelation($product_mage_id, $related_Products, 1, true);
//            }
//            if($upsellProducts){
//                $upsell_Products = $this->duplicateFieldValueFromList($upsellProducts, 'id');
//                $this->setProductRelation($product_mage_id, $upsell_Products, 4, true);
//            }
//        }
        //options
        $listOption = $this->getListFromListByField($productsExt['data']['main']['product_var'], 'product_id', $product['id']);
        $optionIds = $this->duplicateFieldValueFromList($listOption, 'variant_group_id');
        if($optionIds){
            foreach ($optionIds as $opt_id){
                $optionsDesc = $this->getRowFromListByField($productsExt['data']['main']['product_opt'], 'id', $opt_id);
                $type = $this->_getOptionType($optionsDesc['display_type']);
                $option = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($type),
                    'type' => $type,
                    'is_require' => $optionsDesc['is_hidden'] == 'false' ? 1 : 0,
                    'title' => $optionsDesc['name']
                );
                $values = array();
                $getValues = $this->getListFromListByField($listOption, 'variant_group_id', $opt_id);
                foreach ($getValues as $get_value){
                    $value = array(
                        'option_type_id' => -1,
                        'title' => $get_value['description'],
                        'price' => $get_value['price_adjustment'],
                        'price_type' => 'fixed',
                    );
                    $values[] = $value;
                }
                $option['values'] = (isset($values)) ? $values : '';
                $opt_data[] = $option;
            }
            $this->importProductOption($product_mage_id, $opt_data);
        }
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
        $imported = $this->_notice['customers']['imported'];
        $limit = $this->_notice['setting']['customers'];
        usleep(10000000);
        $page = floor($imported/$limit) + 1;
        $customers = $this->api('customers.json?page=' . $page . '&count=' . $limit);
//        if(!$customers){
//            return array(
//                'result' => "error",
//                'msg' => $this->consoleError("Could not get data Customers from Americommerce")
//            );
//        }
        $customer_list = json_decode($customers, 1);
        return array(
            'result' => "success",
            'data' => $customer_list['customers']
        );
    }
    
    /**
     * Get data relation use for import customer
     *
     * @param array $customers
     * @return array
     */
    public function getCustomersExtMain($customers){
        $result = array();
        usleep(10000000);
        $listAddress = $this->api('addresses');
        $list_Address = json_decode($listAddress, true);
        $result['address'] = $list_Address['addresses'];
        return array(
            'result' => "success",
            'data' => $result
        );
    }

    /**
     * Get primary key of source customer main
     *
     * @param array $customer
     * @param array $customersExt
     * @return int
     */
    public function getCustomerId($customer, $customersExt){
        return $customer['id'];
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
            $cus_data['id'] = $customer['id'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['is_subscribed'] = $customer['accepts_marketing'] ? 1 : 0;
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['first_name'];
        $cus_data['lastname'] = $customer['last_name'];
        $cus_data['created_at'] = $customer['created_at'];
        $cus_data['updated_at'] = $customer['updated_at'];
        $cus_data['group_id'] = 1;
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
        $addressCus = $this->getListFromListByField($customersExt['data']['main']['address'], 'customer_id', $customer['id']);
        $customAddress = Mage::getModel('customer/address');
        if($addressCus){
            $infoAddressBill = $this->getRowFromListByField($addressCus, 'is_default_billing_address', 'true');
            $address_billing = array();
            $address_billing['firstname'] = $infoAddressBill['first_name'];
            $address_billing['lastname'] = $infoAddressBill['last_name'];
            $countrybill_name = $infoAddressBill['country'];
            $address_billing['country_id'] = $this->getCountryIsoByName($countrybill_name);
            $address_billing['street'][0] = $infoAddressBill['address_line_1'];
            $address_billing['street'][1] = $infoAddressBill['address_line_2'];
            $address_billing['postcode'] = $infoAddressBill['postal_code'];
            $address_billing['city'] = $infoAddressBill['city'];
            $address_billing['telephone'] = $infoAddressBill['phone'];
            $address_billing['company'] = $infoAddressBill['company'];
//            var_dump($infoAddressBill);exit;
//            var_dump($address_billing);exit;
            $state_bill = $infoAddressBill['state'];
            if($billing_region_id = $this->getRegionId($state_bill, $address_billing['country_id'])){
                $address_billing['region_id'] = $billing_region_id;
                $address_billing['region'] = $state_bill;
            }else{
                $address_billing['region'] = $state_bill;
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
            
            $infoAddressShip = $this->getRowFromListByField($addressCus, 'is_default_shipping_address', 'true');
            $address_shipping = array();
            $address_shipping['firstname'] = $infoAddressShip['first_name'];
            $address_shipping['lastname'] = $infoAddressShip['last_name'];
            $countryship_name = $infoAddressShip['country'];
            $address_shipping['country_id'] = $this->getCountryIsoByName($countryship_name);
            $address_shipping['street'][0] = $infoAddressShip['address_line_1'];
            $address_shipping['street'][1] = $infoAddressShip['address_line_2'];
            $address_shipping['postcode'] = $infoAddressShip['postal_code'];
            $address_shipping['city'] = $infoAddressShip['city'];
            $address_shipping['company'] = $infoAddressShip['company'];
            $state_ship = $infoAddressShip['state'];
            if($shipping_region_id = $this->getRegionId($state_ship, $address_shipping['country_id'])){
                $address_shipping['region_id'] = $shipping_region_id;
                $address_shipping['region'] = $state_ship;
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
        $imported = $this->_notice['orders']['imported'];
        $limit = $this->_notice['setting']['orders'];
        $page = floor($imported/$limit) + 1;
        usleep(10000000);
        $orders = $this->api('orders.json?page=' . $page . '&count=' . $limit);
//        if(!$orders){
//            return array(
//                'result' => "error",
//                'msg' => $this->consoleError("Could not get data Order from Americommerce")
//            );
//        }
        $order_list = json_decode($orders, true);
        return array(
            'result' => "success",
            'data' => $order_list['orders']
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
        usleep(10000000);
        $orderItems = $this->api('order_items');
        $order_items = json_decode($orderItems, true);
        $orderAddress = $this->api('order_addresses');
        $order_address = json_decode($orderAddress, true);
        $orderCustomers = $this->api('customers');
        $order_customers = json_decode($orderCustomers, true);
        $orderShipments = $this->api('order_shipments');
        $order_shipments = json_decode($orderShipments, true);
        
        $result['items'] = $order_items['items'];
        $result['addresses'] = $order_address['addresses'];
        $result['customers'] = $order_customers['customers'];
        $result['shipments'] = $order_shipments['shipments'];
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
        return $order['id'];
    }
    
    /**
     * Convert source data to data import
     *
     * @param array $order
     * @param array $ordersExt
     * @return array
     */
    public function convertOrder($order, $ordersExt){
        if(LitExtension_CartServiceMigrate_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order, $ordersExt);
        }
        $data = array();
        $bill_addr = $this->getRowFromListByField($ordersExt['data']['main']['addresses'], 'id', $order['order_billing_address_id']);
        $address_billing['firstname'] = $bill_addr['first_name'];
        $address_billing['lastname'] = $bill_addr['last_name'];
        $address_billing['company'] = $bill_addr['company'];
        $address_billing['email'] = "";
        $address_billing['street'] = $bill_addr['address_line_1'] . "\n" . $bill_addr['address_line_2'];
        $address_billing['city'] = $bill_addr['city'];
        $address_billing['postcode'] = $bill_addr['postal_code'];
        $address_billing['telephone'] = $bill_addr['phone'];
        $address_billing['country_id'] = $bill_addr['country'];
        $billing_region_id = $this->getRegionId($bill_addr['state'], $address_billing['country_id']);
        if ($billing_region_id) {
            $address_billing['region_id'] = $billing_region_id;
        } else {
            $address_billing['region'] = $bill_addr['state'];
        }
        
        $ship_addr = $this->getRowFromListByField($ordersExt['data']['main']['addresses'], 'id', $order['order_shipping_address_id']);
        $address_shipping['firstname'] = $ship_addr['first_name'];
        $address_shipping['lastname'] = $ship_addr['last_name'];
        $address_shipping['company'] = $ship_addr['company'];
        $address_shipping['email'] = "";
        $address_shipping['street'] = $ship_addr['address_line_1'] . "\n" . $ship_addr['address_line_2'];
        $address_shipping['city'] = $ship_addr['city'];
        $address_shipping['postcode'] = $ship_addr['postal_code'];
        $address_shipping['telephone'] = $ship_addr['phone'];
        $address_shipping['country_id'] = $ship_addr['country'];
        $shipping_region_id = $this->getRegionId($ship_addr['state'], $address_shipping['country_id']);
        if ($shipping_region_id) {
            $address_shipping['region_id'] = $shipping_region_id;
        } else {
            $address_shipping['region'] = $ship_addr['state'];
        }
        
        $carts = array();
        $orderItems = $this->getListFromListByField($ordersExt['data']['main']['items'], 'order_id', $order['id']);
        if($orderItems){
            foreach ($orderItems as $item) {
                $cart = array();
                $product_id = $this->getIdDescProduct($item['product_id']);
                if ($product_id) {
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = 'simple';
                $cart['name'] = $item['item_name'];
                $cart['sku'] = $item['item_number'];
                $cart['price'] = $item['price'];
                $cart['original_price'] = $item['price'];
                $cart['tax_amount'] = "";
                $cart['tax_percent'] = "";
                $cart['discount_amount'] = $item['discount_amount'];
                $cart['qty_ordered'] = $item['quantity'];
                $cart['row_total'] = $item['price'] * $item['quantity'];
                if($item['variants']){
                    $product_opt = array();
                    $options = array();
                    foreach ($item['variants'] as $item_opt){
                        $desc = explode(': ', $item_opt['description']);
                        $option = array(
                            'label' => $desc[0],
                            'value' => $desc[1],
                            'print_value' => $desc[1],
                            'option_id' => 'option_' . $item_opt['id'],
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $options[] = $option;
                    }
                    $product_opt['options'] = $options;
                    $cart['product_options'] = serialize($product_opt);
                }
                $carts[] = $cart;
            }
        }
        
        $order_data = array();
        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $customer_id = $this->getIdDescCustomer($order['customer_id']);
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        
        $infoCus = $this->getRowFromListByField($ordersExt['data']['main']['customers'], 'id', $order['customer_id']);
        $order_data['customer_email'] = $infoCus['email'];
        $order_data['customer_firstname'] = $infoCus['first_name'];
        $order_data['customer_lastname'] = $infoCus['last_name'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order['order_status_id']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $order['subtotal'];
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['discounted_shipping_total'];
        $order_data['base_shipping_amount'] = $order['discounted_shipping_total'];
        $order_data['base_shipping_invoiced'] = $order['discounted_shipping_total'];
        $order_data['shipping_description'] = "";
        $order_data['tax_amount'] = $order['tax_total'];
        $order_data['base_tax_amount'] = $order['tax_total'];
        $order_data['discount_amount'] = $order['discount_total'];
        $order_data['base_discount_amount'] = $order['discount_total'];
        $order_data['grand_total'] = $order['grand_total'];
        $order_data['base_grand_total'] = $order['grand_total'];
        $order_data['base_total_invoiced'] = $order['grand_total'];
        $order_data['total_paid'] = $order['grand_total'];
        $order_data['base_total_paid'] = $order['grand_total'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        $order_data['base_currency_code'] = $store_currency['currency'];
        $order_data['global_currency_code'] = $store_currency['currency'];
        $order_data['store_currency_code'] = $store_currency['currency'];
        $order_data['order_currency_code'] = $store_currency['currency'];
        $order_data['created_at'] = $order['created_at'];
        $order_data['updated_at'] = $order['updated_at'];

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
        $order_status_id = $order['order_status_id'];
        $order_status_data['status'] = $this->_notice['config']['order_status'][$order_status_id];
        if ($order_status_data['status']) {
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $shipments = $this->getRowFromListByField($ordersExt['data']['main']['shipments'], 'order_id', $order['id']);
        $order_status_data['comment'] = "<b>Reference order #" . $order['id'] .
                "</b><br /><b>Payment method: </b>" . '...' .
                "<br /><b>Shipping method: </b> " . $shipments['shipping_method'] . "<br /><br /><b>Admin: </b>" .
                $order['admin_comments'] . "<br /><br /><b>Public: </b>" . $order['public_comments'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = $order['created_at'];
        $order_status_data['created_at'] = $order['created_at'];
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
        $imported = $this->_notice['reviews']['imported'];
        $limit = $this->_notice['setting']['reviews'];
        $page = floor($imported/$limit) + 1;
        usleep(10000000);
        $reviews = $this->api('product_reviews.json?page=' . $page . '&count=' . $limit);
        $review_data = json_decode($reviews, true);
        return array(
            'result' => "success",
            'data' => $review_data['product_reviews']
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
        return false;
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
        $product_mage_id = $this->getIdDescProduct($review['product_id']);
        if(!$product_mage_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['id']} import failed. Error: Product Id = {$review['product_id']} not imported!")
            );
        }

        $store_id = $this->_notice['config']['languages'][$this->_notice['config']['default_lang']];
        $data = array();
        $data['entity_pk_value'] = $product_mage_id;
        $data['status_id'] = 1;
        $data['title'] = $review['title'];
        $data['detail'] = $review['body'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = ($this->getIdDescCustomer($review['customer_id']))? $this->getIdDescCustomer($review['customer_id']) : null;
        $data['nickname'] = $review['author_display_name'];
        $data['rating'] = $review['overall_rating'];
        $data['created_at'] = $this->_getTime($review['created_at']);
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
    
    
    #############################################-Function-###############################################
    
//    protected function _setStartTime($time) {
//        $session = Mage::getSingleton('admin/session');
//        $session->unsLeCSTime();
//        $session->setLeCSTime($time);
//    }
//    
//    protected function _getDurationTime($time) {
//        $session = Mage::getSingleton('admin/session');
//        $last = $session->getLeCSTime();
//        if (!$last) {
//            return false;
//        }
//        $duration = $time - $last;
//        return (float)$duration*1000000;
//    }
    
    public function getCountryIsoByName($path){
        $request = $this->requestByGet('https://restcountries.eu/rest/v1/name/' . $path);
        $result = json_decode($request, true);
        $resultArr = (array)$result;
        $iso = "";
        foreach ($resultArr as $arr){
            $arr_iso = (array)$arr;
            if($arr_iso['name'] == $path){
                $iso = $arr_iso['alpha2Code'];
                break;
            }
        }
        return $iso;
    }
    
    function requestByGet($url, $data = array()){
        $options = http_build_query($data);
        if ($options) {
            $url .= "?" . $options;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt_array($ch, array(CURLINFO_HEADER_OUT => true));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


    public function getApiData() {
        return array(
            'api_token' => "API Token"
        );
    }
    
    public function api($path){
        $cart_url  = parse_url($this->_cart_url);
        $api_token = trim($this->_notice['config']['api']['api_token']);
        $curl = curl_init('https://' . $cart_url['host'] . '/api/v1/' . $path);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-AC-Auth-Token: ' . $api_token));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $json = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($status != 200) {
            die("Error: call to $path failed with status $status and response content: $json");
        }
        curl_close($curl);
        //$response = json_decode($json, true);
        return $json;
    }
    
    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($parent_id){
        $category_api = $this->api('categories.json');
        if(!$category_api){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Americommerce.")
            );
        }
        $category = json_decode($category_api, true);
        $category_parent = $this->getRowFromListByField($category['categories'], 'id', $parent_id);
        $categories = array(0 => $category_parent);
        $categoriesExt = $this->getCategoriesExt($categories);
        if($categoriesExt['result'] != 'success'){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Americommerce.")
            );
        }
        $convert = $this->convertCategory($category_parent, $categoriesExt);
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
    
    public function _getOptionType($text){
        $types = array(
            'DropDown' => "drop_down",
            'CheckBoxList' => "checkbox",
            'RadioButtonList' => "radio",
            'QuantityGrid' => 'field'
        );
        return $types[$text];
    }
    
    protected function _getTime($time) {
        return str_replace("T", " ",substr($time, 0, 19));
    }
//    public function _getTime($time){
//        $new_time = explode('T', $time);
//        return $new_time[0];
//    }
    
    
}