<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartServiceMigrate_Model_Cart_Bigcommerce
    extends LitExtension_CartServiceMigrate_Model_Cart {

    const CONFIGURABLE_PRODUCT = false;
    const CUSTOM_OPTIONS_RULES = false;
    const MIGRATE_RULES = false;
    protected $_api_url = null;

    public function __construct(){
        parent::__construct();
    }

    /**
     * Get info of api for user config
     */
    public function getApiData(){
        return array(
            'username' => "Username",
            'api_path' => "API Path",
            'api_token' => "API Token"
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
        $api_store = $this->api("/store.json");
        $api_order_status = $this->api("/order_statuses.json");
        $api_customer_group = $this->api("/customer_groups.json");
        if(!$api_store || !$api_order_status){
            return array(
                'result' => 'warning',
                'elm' => '#error-api'
            );
        }
        $store = json_decode($api_store, true);
        $orderStatus = json_decode($api_order_status, true);
        $customerGroup = json_decode($api_customer_group, true);
        $currency = isset($store['currency']) ? $store['currency'] : "USD";
        $category_data = array("Default Root Category");
        $attribute_data = array("Default Attribute Set");
        $language_data = array(
            1 => "Default Language"
        );
        $order_status_data = $customer_group_data = array();
        foreach($orderStatus as $order_status){
            $order_status_key = $order_status['id'];
            $order_status_label = $order_status['name'];
            $order_status_data[$order_status_key] = $order_status_label;
        }
        if($customerGroup){
            foreach($customerGroup as $customer_group){
                $group_id= $customer_group['id'];
                $group_name = $customer_group['name'];
                $customer_group_data[$group_id] = $group_name;
            }
        }
        $this->_notice['config']['api_data'] = $this->getApiData();
        $this->_notice['config']['config_support']['country_map'] = false;
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['default_currency'] = $currency;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $language_data;
        $this->_notice['config']['currencies_data'] = array($currency => $currency);
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
            $types = array('manufacturers', 'categories', 'products', 'customers', 'orders');
            foreach($types as $type){
                if($this->_notice['config']['add_option']['add_new'] || !$this->_notice['config']['import'][$type]){
                    $this->_notice[$type]['id_src'] = $recent[$type]['id_src'];
                    if($type == 'manufacturers'){
                        $api_imported = $this->api("/brands/count.json?max_id=" . $this->_notice[$type]['id_src']);
                    }else{
                        $api_imported = $this->api("/". $type ."/count.json?max_id=" . $this->_notice[$type]['id_src']);
                    }
                    $type_imported = json_decode($api_imported, true);
                    $this->_notice[$type]['imported'] = $type_imported['count'];
                }
            }
        }
        $api_taxes = $this->api("/taxclasses.json");
        $api_manufacturers = $this->api("/brands/count.json");
        $api_categories = $this->api("/categories/count.json");
        $api_products = $this->api('/products/count.json');
        $api_customer = $this->api("/customers/count.json");
        $api_orders = $this->api("/orders/count.json");
        $reviews_count = 0;
        $i = 1;
        while($api_reviews = $this->api("/products/reviews.json?page=" . $i . "&limit=250")){
            $count_tmp = count(json_decode($api_reviews, true));
            if($count_tmp > 0){
                $reviews_count += $count_tmp;
                $i++;
            }else{
                break;
            }
        }

        $tax_classes = json_decode($api_taxes, true);
        $manufacturers = json_decode($api_manufacturers, true);
        $categories = json_decode($api_categories, true);
        $products = json_decode($api_products, true);
        $customers = json_decode($api_customer, true);
        $orders = json_decode($api_orders, true);

        $tax_count = count($tax_classes) + 1;
        $man_count = isset($manufacturers['count']) ? $manufacturers['count'] : 0;
        $cat_count = isset($categories['count']) ? $categories['count'] : 0;
        $pro_count = isset($products['count']) ? $products['count'] : 0;
        $cus_count = isset($customers['count']) ? $customers['count'] : 0;
        $order_count = isset($orders['count']) ? $orders['count'] : 0;

        $totals = array(
            'taxes' => $tax_count,
            'manufacturers' => $man_count,
            'categories' => $cat_count,
            'products' => $pro_count,
            'customers' => $cus_count,
            'orders' => $order_count,
            'reviews' => $reviews_count
        );
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
        $imported = $this->_notice['taxes']['imported'];
        $limit = $this->_notice['setting']['taxes'];
        $page = floor($imported/$limit) + 1;
        $api_tax = $this->api('/taxclasses.json?page=' . $page . '&limit=' . $limit);

        $tax_classes = json_decode($api_tax, true);
        $tax_classes[] = array(
            'id' => 0,
            'name' => 'Default Tax Class'
        );
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
            'class_name' => $tax['name']
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if ($tax_pro_ipt['result'] == 'success') {
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
            $this->taxProductSuccess($tax['id'] , $tax_pro_ipt['mage_id']);
        }
        if($tax_rate_mage_id = $this->getIdDescTaxRate(3)){
            $tax_rate_ids[] = $tax_rate_mage_id;
        }else{
            $tax_rate_data = array(
                'code' => $this->createTaxRateCode('US'),
                'tax_country_id' => 'US',
                'tax_region_id' => 0,
                'zip_is_range' => 0,
                'tax_postcode' => "*",
                'rate' => 0
            );
            $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
            if($tax_rate_ipt['result'] == 'success'){
                $this->taxRateSuccess(3, $tax_rate_ipt['mage_id']);
                $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
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
        $imported = $this->_notice['manufacturers']['imported'];
        $limit = $this->_notice['setting']['manufacturers'];
        $page = floor($imported/$limit) + 1;
        $api_brands = $this->api('/brands.json?page=' . $page . '&limit=' . $limit);
        if(!$api_brands){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce")
            );
        }
        $brands = json_decode($api_brands, true);
        return array(
            'result' => "success",
            'data' => $brands
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
        $id_src = $this->_notice['categories']['id_src'] + 1;
        $api_categories = $this->api("/categories.json?min_id=" . $id_src ."&limit=" . $limit);
        if(!$api_categories){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce.")
            );
        }
        $categories = json_decode($api_categories, true);
        return array(
            'result' => "success",
            'data' => $categories
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
        if($category['parent_id'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getIdDescCategory($category['parent_id']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['parent_id']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['id']} import failed. Error: Could not import parent category id = {$category['parent_id']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['name'];
        if($category['image_file']){
            $cat_img_url = rtrim($this->_cart_url, '/') . '/product_images/' . $category['image_file'];
            if($cat_img_url && $img_path = $this->downloadImageFromUrl($cat_img_url, 'catalog/category')){
                $cat_data['image'] = $img_path;
            }
        }
        $cat_des = str_replace('%%GLOBAL_ShopPath%%', rtrim($this->_cart_url, '/'), $category['description']);
        $cat_data['description'] = $this->changeImgSrcInText($cat_des, $this->_notice['config']['add_option']['img_des']);
        $cat_data['meta_title'] = $category['page_title'];
        $cat_data['meta_keywords'] = $category['meta_keywords'];
        $cat_data['meta_description'] = $category['meta_description'];
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_data['is_active'] = $category['is_visible'] ? 1 : 0;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = $category['is_visible'];
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
        $imported = $this->_notice['products']['imported'];
        $limit = $this->_notice['setting']['products'];
        $id_src = $this->_notice['products']['id_src'] + 1;
        $api_products = $this->api("/products.json?min_id=" . $id_src ."&limit=" . $limit);
        if(!$api_products){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce.")
            );
        }
        $products = json_decode($api_products, true);
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
        foreach($products['data'] as $product){
            $discountRules = $images = $cusFields = $confFields = $optSetOpts = $sku_data = array();
            if($product['discount_rules']['resource']) {
                $api_discount = $this->api($product['discount_rules']['resource'] . '.json');
                if ($api_discount) {
                    $discountRules = json_decode($api_discount, 1);
                }
            }
            if(isset($product['images']['resource']) && $product['images']['resource']) {
                $api_images = $this->api($product['images']['resource'] . '.json');
                $images = json_decode($api_images, 1);
            }
            if(isset($product['configurable_fields']['resource']) && $product['configurable_fields']['resource']) {
                $api_conf_field = $this->api($product['configurable_fields']['resource'] . '.json');
                $confFields = json_decode($api_conf_field, true);
            }
            $option_ids_sku = array();
            if(self::CONFIGURABLE_PRODUCT){
                if(isset($product['skus']['resource']) && $product['skus']['resource']){
                    $api_skus = $this->api($product['skus']['resource']. '.json');
                    $sku_data = json_decode($api_skus, true);
                    if($sku_data){
                        foreach ($sku_data as $opt_sku) {
                            foreach ($opt_sku['options'] as $option) {
                                $apiOptData = $this->api('/products/' . $product['id'] . '/options/' . $option['product_option_id'] . '.json');
                                if($apiOptData){
                                    $optData = json_decode($apiOptData, 1);
                                    $option_ids_sku[] = $optData['option_id'];
                                    $result[$product['id']]['options_data'][$option['product_option_id']] = $optData;
                                    $apiOptValues = $this->api('/options/' . $optData['option_id'] . '/values/' . $option['option_value_id'] . '.json');
                                    $result[$product['id']]['options_value_data'][$optData['option_id']][$option['option_value_id']] = json_decode($apiOptValues, 1);
                                }
                            }
                        }
                    }
                }
            }
            if($product['option_set_id']){
                $apiOptSetOpts = $this->api('/optionsets/' . $product['option_set_id'] .'/options.json');
                $optSetOpts = json_decode($apiOptSetOpts, 1);
                if($optSetOpts) {
                    foreach($optSetOpts as $key => $values){
                        if(in_array($values['option_id'], $option_ids_sku)){
                            unset($optSetOpts[$key]);
                        }
                    }
                    foreach ($optSetOpts as $opt_set_opts) {
                        if (isset($opt_set_opts['option']['resource']) && $opt_set_opts['option']['resource']) {
                            $api_opt = $this->api($opt_set_opts['option']['resource'] . '.json');
                            if($api_opt){
                                $result[$product['id']]['options'][$opt_set_opts['id']] = json_decode($api_opt, 1);
                            }
                        }
                    }
                }
            }
            if(isset($product['custom_fields']['resource']) && $product['custom_fields']['resource']) {
                $api_cus_field = $this->api($product['custom_fields']['resource'] . '.json');
                $cusFields = json_decode($api_cus_field, true);
            }
            $optRulesApi = $this->api('/products/' . $product['id'] .'/rules.json');
            $optRules = json_decode($optRulesApi, 1);
            $result[$product['id']]['discount_rules'] = $discountRules;
            $result[$product['id']]['images'] = $images;
            $result[$product['id']]['configurable_fields'] = $confFields;
            $result[$product['id']]['option_set_options'] = $optSetOpts;
            $result[$product['id']]['skus'] = $sku_data;
            $result[$product['id']]['custom_fields'] = $cusFields;
            $result[$product['id']]['option_rules'] = $optRules ? $optRules : array();
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
        $pro_data = array();
        $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        if(self::CONFIGURABLE_PRODUCT){
            $optSku = $productsExt['data']['main'][$product['id']]['skus'];
            if(!empty($optSku)){
                $type_id = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
                $config_data = $this->_importChildrenProduct($product, $productsExt, $optSku);
                if($config_data['result'] != 'success'){
                    return $config_data;
                }
                $pro_data = array_merge($config_data['data'], $pro_data);
            }
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
     * @param int $product_mage_id
     * @param array $data
     * @param array $product
     * @param array $productsExt
     * @return boolean
     */
    public function afterSaveProduct($product_mage_id, $data, $product, $productsExt){
        if(parent::afterSaveProduct($product_mage_id, $data, $product, $productsExt)){
            return ;
        }
        if($confFields = $productsExt['data']['main'][$product['id']]['configurable_fields']){
            $custom_option = array();
            foreach($confFields as $conf_field){
                $options = array();
                if($conf_field['select_options']){
                    $options_val = explode(',', $conf_field['select_options']);
                    foreach($options_val as $opt){
                        $tmp['option_type_id'] = -1;
                        $tmp['title'] = $opt;
                        $tmp['price'] = '';
                        $tmp['price_type'] = 'fixed';
                        $options[]=$tmp;
                    }
                }
                $conf_type = 'drop_down';
                if(in_array($conf_field['type'], array('T', 'ML'))){
                    $conf_type = 'area';
                }
                if($conf_field['type'] == 'F'){
                    $conf_type = 'file';
                }
                if($conf_field['type'] == 'C'){
                    $conf_type = 'checkbox';
                }
                if($conf_field['type'] == 'S'){
                    $conf_type = 'drop_down';
                }
                $tmp_opt = array(
                    'title' => $conf_field['name'],
                    'type' => $conf_type,
                    'is_require' => ($conf_field['is_required'] == 1) ? 1 : 0,
                    'sort_order' => 0,
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
            $this->importProductOption($product_mage_id, $custom_option);
        }
        $this->_addCustomOption($product, $product_mage_id, $productsExt);
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
        $id_src = $this->_notice['customers']['id_src'] + 1;
        $api_customer = $this->api('/customers.json?min_id=' . $id_src . '&limit=' . $limit);
        if(!$api_customer){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce")
            );
        }
        $customers = json_decode($api_customer, 1);
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
        $result = array();
        foreach($customers['data'] as $customer){
            $cusAddress = array();
            if(isset($customer['addresses']['resource']) && $customer['addresses']['resource']) {
                $api_cus_add = $this->api($customer['addresses']['resource'] . '.json');
                $cusAddress = json_decode($api_cus_add, 1);
            }
            $result[$customer['id']]['addresses'] = $cusAddress;
        }
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
        $cus_data['email'] = $customer['email'];
        $cus_data['firstname'] = $customer['first_name'];
        $cus_data['lastname'] = $customer['last_name'];
        $cus_data['created_at'] = date("Y-m-d H:i:s", strtotime($customer['date_created']));
        $cus_data['updated_at'] = date("Y-m-d H:i:s", strtotime($customer['date_modified']));
        $cus_data['group_id'] = isset($this->_notice['config']['customer_group'][$customer['customer_group_id']]) ? $this->_notice['config']['customer_group'][$customer['customer_group_id']] : 1;
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
        if($cusAddress = $customersExt['data']['main'][$customer['id']]['addresses']){
            foreach($cusAddress as $key => $cus_add){
                $address = array();
                $address['firstname'] = $cus_add['first_name'];
                $address['lastname'] = $cus_add['last_name'];
                $address['country_id'] = $cus_add['country_iso2'];
                $address['street'] = $cus_add['street_1']."\n".$cus_add['street_2'];
                $address['postcode'] = $cus_add['zip'];
                $address['city'] = $cus_add['city'];
                $address['telephone'] = $customer['phone'];
                $address['company'] = $cus_add['company'];
                if($cus_add['state']){
                    $region_id = $this->getRegionId($cus_add['state'], $cus_add['country_iso2']);
                    if($region_id){
                        $address['region_id'] = $region_id;
                    }
                    $address['region'] = $cus_add['state'];
                } else {
                    $address['region'] = $cus_add['state'];
                }
                $address_ipt = $this->_process->address($address, $customer_mage_id);
                if($key == 0){
                    try{
                        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                        $cus->setDefaultBilling($address_ipt['mage_id']);
                        $cus->setDefaultShipping($address_ipt['mage_id']);
                        $cus->save();
                    }catch (Exception $e){}
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
        $limit = $this->_notice['setting']['orders'];
        $id_src = $this->_notice['orders']['id_src'] + 1;
        $api_orders = $this->api('/orders.json?min_id=' . $id_src . '&limit=' . $limit);
        if(!$api_orders){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce")
            );
        }
        $orders = json_decode($api_orders, 1);
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
        foreach($orders['data'] as $order){
            $decode_shipping = $orderPro = array();
            if($order['shipping_addresses']['resource']){
                $api_ship = $this->api($order['shipping_addresses']['resource'] . '.json');
                $decode_shipping = json_decode($api_ship, 1);
            }
            if($order['products']['resource']){
                $api_pro = $this->api($order['products']['resource'] . '.json');
                $orderPro = json_decode($api_pro, 1);
            }
            $result[$order['id']]['shipping_addresses'] = $decode_shipping;
            $result[$order['id']]['products'] = $orderPro;
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

        $order_bill = $order['billing_address'];
        $address_billing['firstname'] = $order_bill['first_name'];
        $address_billing['lastname'] = $order_bill['last_name'];
        $address_billing['company'] = $order_bill['company'];
        $address_billing['email']   = $order_bill['email'];
        $address_billing['street']  = $order_bill['street_1']."\n".$order_bill['street_2'];
        $address_billing['city'] = $order_bill['city'];
        $address_billing['postcode'] = $order_bill['zip'];
        $address_billing['country_id'] = $order_bill['country_iso2'];
        $billing_region_id = $this->getRegionId($order_bill['state'], $address_billing['country_id']);
        if($billing_region_id){
            $address_billing['region_id'] = $billing_region_id;
        } else{
            $address_billing['region'] = $order_bill['state'];
        }
        $address_billing['telephone'] = $order_bill['phone'];

        $decode_shipping = $ordersExt['data']['main'][$order['id']]['shipping_addresses'];
        $order_ship = false;
        if($decode_shipping){
            $order_ship = $decode_shipping[0];
        }
        $address_shipping['firstname'] = $order_ship['first_name'];
        $address_shipping['lastname'] = $order_ship['last_name'];
        $address_shipping['company'] = $order_ship['company'];
        $address_shipping['email']   = $order_ship['email'];
        $address_shipping['street']  = $order_ship['street_1']."\n".$order_ship['street_2'];
        $address_shipping['city'] = $order_ship['city'];
        $address_shipping['postcode'] = $order_ship['zip'];
        $address_shipping['country_id'] = $order_ship['country_iso2'];
        $shipping_region_id = $this->getRegionId($order_ship['state'], $address_shipping['country_id']);
        if($shipping_region_id){
            $address_shipping['region_id'] = $shipping_region_id;
        } else{
            $address_shipping['region'] = $order_ship['state'];
        }
        $address_shipping['telephone'] = $order_ship['phone'];

        $carts = array();
        if($orderPro = $ordersExt['data']['main'][$order['id']]['products']){
            foreach($orderPro as $order_pro){
                $cart = array();
                $product_id = $this->getIdDescProduct($order_pro['product_id']);
                if ($product_id) {
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_pro['name'];
                $cart['sku'] = $order_pro['sku'];
                $cart['price'] = $order_pro['base_price'];
                $cart['original_price'] = $order_pro['base_price'];
                $cart['qty_ordered'] = $order_pro['quantity'];
                $cart['row_total'] = $order_pro['total_ex_tax'];
                if($order_pro['product_options']){
                    $product_opt = array();
                    foreach($order_pro['product_options'] as $pro_opt){
                        $option = array(
                            'label' => $pro_opt['display_name'],
                            'value' => $pro_opt['display_value'],
                            'print_value' => $pro_opt['display_value'],
                            'option_id' => 'option_'.$pro_opt['option_id'],
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
        $customer_id = $this->getIdDescCustomer($order['customer_id']);
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $order['billing_address']['email'];
        $order_data['customer_firstname'] = $order['billing_address']['first_name'];
        $order_data['customer_lastname'] = $order['billing_address']['last_name'];
        $order_data['customer_group_id'] = 1;
        if ($this->_notice['config']['order_status']) {
            $order_data['status'] = $this->_notice['config']['order_status'][$order['status_id']];
            $order_data['state'] = $this->getOrderStateByStatus($order_data['status']);
        }
        $order_data['subtotal'] = $this->incrementPriceToImport($order['subtotal_ex_tax']);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $order['shipping_cost_ex_tax'];
        $order_data['base_shipping_amount'] = $order_data['shipping_amount'];
        $order_data['base_shipping_invoiced'] = $order_data['shipping_amount'];
        $order_data['shipping_description'] = $order_ship['shipping_method'];
        $order_data['tax_amount'] = $order['total_tax'];
        $order_data['base_tax_amount'] = $order_data['tax_amount'];
        $order_data['discount_amount'] = $order['discount_amount'] + $order['coupon_discount'];
        $order_data['base_discount_amount'] = $order_data['discount_amount'];
        $order_data['grand_total'] = $this->incrementPriceToImport($order['total_inc_tax']);
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
        $order_data['order_currency_code'] = $order['currency_code'];
        $order_data['created_at'] = date("Y-m-d H:i:s", strtotime($order['date_created']));
        $order_data['updated_at'] = date("Y-m-d H:i:s", strtotime($order['date_modified']));

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
        $order_status_data['status'] = $data['order']['status'];
        if($order_status_data['status']){
            $order_status_data['state'] = $this->getOrderStateByStatus($order_status_data['status']);
        }
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = $data['order']['updated_at'];
        $order_status_data['created_at'] = $data['order']['created_at'];
        $comment_one['comment'] = "<b>Reference order #".$order['id']."</b><br /><b>Payment method: </b>".$order['payment_method']."<br /><b>Shipping method: </b> ".$data['order']['shipping_description']."<br /><br />".$order['customer_message'];
        $order_cm_one = array_merge($order_status_data, $comment_one);
        $this->_process->ordersComment($order_mage_id, $order_cm_one);
        if($order['staff_notes']){
            $comment_two['comment'] = $order['staff_notes'];
            $order_cm_two = array_merge($order_status_data, $comment_two);
            $this->_process->ordersComment($order_mage_id, $order_cm_two);
        }
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
        $reviews = array();
        $imported = $this->_notice['reviews']['imported'];
        $limit = $this->_notice['setting']['reviews'];
        $page = floor($imported/$limit) + 1;
        $api_reviews = $this->api('/products/reviews.json?page=' . $page . '&limit=' . $limit);
        if(!$api_reviews){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce")
            );
        }
        $reviews = json_decode($api_reviews, 1);
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
        $data['status_id'] = ($review['status'] == 0)? 3 : 1;
        $data['title'] = $review['title'];
        $data['detail'] = $review['review'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = null;
        $data['nickname'] = $review['author'];
        $data['rating'] = $review['rating'];
        $data['created_at'] = date("Y-m-d H:i:s", strtotime($review['date_created']));
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

    /**
     * TODO : Extend function
     */

    public function api($path){
        $api_url = $this->getApiUrl();
        if(!$api_url){
            return false;
        }
        $url = $api_url . $path;
        return $this->_requestApi($url);
    }

    public function getApiUrl(){
        if(!$this->_api_url){
            $this->_api_url = $this->_createApiUrl();
            if(!$this->_api_url){
                return false;
            }
        }
        return $this->_api_url;
    }

    protected function _createApiUrl(){
        $username = trim($this->_notice['config']['api']['username']);
        $api_path = trim($this->_notice['config']['api']['api_path']);
        $api_token = trim($this->_notice['config']['api']['api_token']);
        if(!$username || !$api_path || !$api_token){
            return false;
        }
        $url = parse_url($api_path);
        $api_url = $url['scheme'] . '://' . $username . ':' . $api_token . '@' . $url['host'];
        if(isset($url['path'])){
            $api_url .= $url['path'];
        }
        return rtrim($api_url, '/');
    }

    /**
     * TODO : Client Request
     */

    /**
     * Client request url
     */
    protected function _requestApi($url, $method = Zend_Http_Client::GET, $params = array(), $config = array('timeout' => 60), $header = array()){
        $result = false;
        $valid = $this->_checkUrlExists($url);
        while($valid == 'limit'){
            $valid = $this->_checkUrlExists($url);
            sleep(300);
        }
        if($valid == 'error'){
            return $result;
        }
        $client = new Zend_Http_Client($url, $config);
        if($params){
            switch ($method) {
                case Zend_Http_Client::GET :
                    $client->setParameterGet($params);
                    break;
                case Zend_Http_Client::POST :
                    $client->setParameterPost($params);
                    break;
                case Zend_Http_Client::PUT :
                    $client->setParameterPost($params);
                    break;
                case Zend_Http_Client::DELETE :
                    $client->setParameterGet($params);
                    break;
                default:
                    $client->setParameterPost($params);
                    break;
            }
        }
        if($header){
            $client->setHeaders($header);
        }
        $response = $client->request($method);
        $result = $response->getBody();
        sleep($this->_notice['setting']['delay']);
        return $result;
    }

    /**
     * Check url exists
     */
    protected function _checkUrlExists($url){
        $header = @get_headers($url, 1);
        if(!$header){
            return 'error';
        }
        if(isset($header['X-BC-ApiLimit-Remaining']) && $header['X-BC-ApiLimit-Remaining'] < 10){
            return 'limit';
        }
        $string = $header[0];
        if(strpos($string, "200")){
            return 'success';
        }
        return 'error';
    }

    protected function _importCategoryParent($id){
        $api_category = $this->api("/categories/" . $id . ".json");
        if(!$api_category){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce.")
            );
        }
        $category = json_decode($api_category, true);
        $categories = array(0 => $category);
        $categoriesExt = $this->getCategoriesExt($categories);
        if($categoriesExt['result'] != "success"){
            return array(
                'result' => "error",
                'msg' => $this->consoleError("Could not get data from Bigcommerce.")
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
            $this->categorySuccess($id, $category_ipt['mage_id']);
            $this->afterSaveCategory($category_ipt['mage_id'], $data, $category, $categoriesExt);
        } else {
            $category_ipt['result'] = 'warning';
        }
        return $category_ipt;
    }

    /*
     * Import Custom Option
     */
    protected function _addCustomOption($product, $product_mage_id, $productsExt){
        $custom_option = array();
        if($optSetOpts = $productsExt['data']['main'][$product['id']]['option_set_options']){
            foreach($optSetOpts as $opt_set_opts){
                $options = array();
                if(isset($opt_set_opts['values']) && $opt_set_opts['values']){
                    foreach($opt_set_opts['values'] as $option){
                        $price_adjust = '';
                        $sku = $this->getRowValueFromListByField($productsExt['data']['main'][$product['id']]['skus'], 'option_value_id', $option['option_value_id'], 'sku');
                        if(self::CUSTOM_OPTIONS_RULES){
                            if($productsExt['data']['main'][$product['id']]['option_rules']){
                                foreach($productsExt['data']['main'][$product['id']]['option_rules'] as $opt_rule){
                                    $check_break = false;
                                    if($opt_rule['conditions']){
                                        foreach($opt_rule['conditions'] as $condition){
                                            if($condition['option_value_id'] == $option['option_value_id']){
                                                $price_adjust = $opt_rule['price_adjuster']['adjuster_value'];
                                                $check_break = true;
                                                break;
                                            }
                                        }
                                    }
                                    if($check_break){
                                        break;
                                    }
                                }
                            }
                        }
                        $tmp['option_type_id'] = -1;
                        $tmp['title'] = $option['label'];
                        $tmp['sku'] = $sku;
                        $tmp['price'] = $price_adjust;
                        $tmp['price_type'] = 'fixed';
                        $options[]=$tmp;
                    }
                }
                $cus_opt_type = 'drop_down';
                if(isset($productsExt['data']['main'][$product['id']]['options'][$opt_set_opts['id']]) && $opt = $productsExt['data']['main'][$product['id']]['options'][$opt_set_opts['id']]){
                    if($opt && in_array($opt['type'], array('CS', 'RB'))){
                        $cus_opt_type = 'radio';
                    }
                    if($opt && $opt['type'] == 'D'){
                        $cus_opt_type = 'date';
                    }
                    if($opt && $opt['type'] == 'F'){
                        $cus_opt_type = 'file';
                    }
                    if($opt && $opt['type'] == 'S'){
                        $cus_opt_type = 'drop_down';
                    }
                    if($opt && $opt['type'] == 'MT'){
                        $cus_opt_type = 'area';
                    }
                    if($opt && in_array($opt['type'], array('N', 'T'))){
                        $cus_opt_type = 'field';
                    }
                    if($opt && $opt['type'] == 'C'){
                        $cus_opt_type = 'checkbox';
                    }
                }
                $tmp_opt = array(
                    'title' => $opt_set_opts['display_name'],
                    'type' => $cus_opt_type,
                    'is_require' => 0,
                    'sort_order' => 0,
                    'values' => $options,
                );
                $custom_option[] = $tmp_opt;
            }
            $this->importProductOption($product_mage_id, $custom_option);
        }
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
        $result = $config;
        if(!$result) return false;
        return $result;
    }

    //Import Config Product
    protected function _importChildrenProduct($product, $productsExt, $optSku){
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $result = $dataChildes = $attrMage = array();
        if($optSku){
            foreach ($optSku as $opt_sku) {
                $option_collection = '';
                $dataOpts = array();
                foreach ($opt_sku['options'] as $option) {
                    if(isset($productsExt['data']['main'][$product['id']]['options_data'][$option['product_option_id']])){
                        $optData = $productsExt['data']['main'][$product['id']]['options_data'][$option['product_option_id']];
                    }else{
                        return array(
                            'result' => "warning",
                            'msg' => $this->consoleWarning("Product Id = {$product['id']} import failed. Error: Product attribute could not create!")
                        );
                    }
                    if($optData){
                        $attribute_name = $optData['display_name'];
                        if(isset($productsExt['data']['main'][$product['id']]['options_value_data'][$optData['option_id']][$option['option_value_id']])){
                            $optValue = $productsExt['data']['main'][$product['id']]['options_value_data'][$optData['option_id']][$option['option_value_id']];
                        }else{
                            return array(
                                'result' => "warning",
                                'msg' => $this->consoleWarning("Product Id = {$product['id']} import failed. Error: Product attribute could not create!")
                            );
                        }
                        if($optValue){
                            $optionValLabel = $optValue['label'];
                            $attribute_code = $this->joinTextToKey($attribute_name, 27, '_');
                            $opt_attr_data = array(
                                'entity_type_id'                => $entity_type_id,
                                'attribute_set_id'              => $this->_notice['config']['attribute_set_id'],
                                'attribute_code'                => $attribute_code,
                                'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                                'frontend_input'                => 'select',
                                'frontend_label'                => array($attribute_name),
                                'option'                        => array(
                                    'value' => array(
                                        'option_0' => array($optionValLabel)
                                    )
                                )
                            );
                            $optAttrDataImport = $this->_process->attribute($opt_attr_data);
                            if (!$optAttrDataImport) {
                                return array(
                                    'result' => "warning",
                                    'msg' => $this->consoleWarning("Product Id = {$product['id']} import failed. Error: Product attribute could not create!")
                                );
                            }
                            $dataTMP = array(
                                'attribute_id' => $optAttrDataImport['attribute_id'],
                                'value_index' => $optAttrDataImport['option_ids']['option_0'],
                                'is_percent' => 0,
                            );
                            $dataOpts[] = $dataTMP;
                            if ($optionValLabel){
                                $option_collection = $option_collection . ' - ' . $optionValLabel;
                            }
                            $attrMage[$optAttrDataImport['attribute_id']]['attribute_label'] = $attribute_name;
                            $attrMage[$optAttrDataImport['attribute_id']]['attribute_code'] = $optAttrDataImport['attribute_code'];
                            $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['label'] = $optionValLabel;
                            $attrMage[$optAttrDataImport['attribute_id']]['values'][$optAttrDataImport['option_ids']['option_0']]['value_index'] = $optAttrDataImport['option_ids']['option_0'];
                        }
                    }
                }
                $rule_price = $rule_weight = false;
                if(self::MIGRATE_RULES){
                    $optRules = $productsExt['data']['main'][$product['id']]['option_rules'];
                    if($optRules) {
                        foreach ($optRules as $opt_rules) {
                            if(!$opt_rules['is_enabled']){
                                continue;
                            }
                            if($opt_rules['conditions']){
                                foreach($opt_rules['conditions'] as $condition) {
                                    foreach($opt_sku['options'] as $option){
                                        if ($condition['option_value_id'] == $option['option_value_id'] && $condition['product_option_id'] == $option['product_option_id']) {
                                            if($opt_rules['price_adjuster']['adjuster'] == 'absolute'){
                                                $rule_price = $opt_rules['price_adjuster']['adjuster_value'];
                                            }elseif($opt_rules['price_adjuster']['adjuster'] == 'relative'){
                                                if($product['retail_price'] && $product['retail_price'] > 0){
                                                    $priceDad = $product['retail_price'];
                                                }else{
                                                    $priceDad = $product['calculated_price'];
                                                }
                                                $rule_price = $priceDad + $opt_rules['price_adjuster']['adjuster_value'];
                                            }
                                            //weight
                                            if($opt_rules['weight_adjuster']['adjuster'] == 'absolute'){
                                                $rule_weight = $opt_rules['weight_adjuster']['adjuster_value'];
                                            }elseif($opt_rules['weight_adjuster']['adjuster'] == 'relative'){
                                                $rule_weight = $product['weight'] + $opt_rules['weight_adjuster']['adjuster_value'];
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $data_variation = array(
                    'option_collection' => $option_collection,
                    'object' => $opt_sku,
                    'rule_price' => $rule_price,
                    'rule_weight' => $rule_weight
                );
                $convertPro = $this->_convertProduct($product, $productsExt, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, true, $data_variation);
                $pro_import = $this->_process->product($convertPro);
                if ($pro_import['result'] !== 'success') {
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Id = {$product['id']} import failed. Error: Product children could not create!")
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

    protected function _convertProduct($product, $productsExt, $type_id, $is_variation_pro = false, $data_variation = array()){
        $pro_data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        if($is_variation_pro){
            if($data_variation['rule_price']){
                $pro_data['price'] = $data_variation['rule_price'];
            }else{
                $pro_data['price'] = $data_variation['object']['cost_price'];
            }
            $pro_data['weight'] = $data_variation['rule_weight'];
            $pro_data['sku'] =  $this->createProductSku($data_variation['object']['sku'], $this->_notice['config']['languages']);
            $manager_stock = 1;
            if(($this->_notice['config']['add_option']['stock'] && $data_variation['object']['inventory_level'] < 1) || $product['inventory_tracking'] != 'sku'){
                $manager_stock = 0;
            }
            $pro_data['stock_data'] = array(
                'is_in_stock' => ($data_variation['object']['inventory_level'] < 1) ? 0 : 1,
                'manage_stock' => $manager_stock,
                'use_config_manage_stock' => $manager_stock,
                'qty' => $data_variation['object']['inventory_level'],
            );
            $pro_data['name'] = $product['name'] . $data_variation['option_collection'];
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            //UPC
            if($data_variation['object']['upc']){
                $attr_code = 'le_upc';
                $attr_import = $this->_makeAttributeImport('UPC/EAN', $attr_code, '', $entity_type_id, $this->_notice['config']['attribute_set_id'], 'text');
                $attrAfterImport = $this->_process->attribute($attr_import);
                $pro_data[$attrAfterImport['attribute_code']] = $data_variation['object']['upc'];
            }
            //
        }else{
            $tierPrices = array();
            if($product['retail_price'] && $product['retail_price'] > 0){
                $pro_data['price'] = $product['retail_price'];
                $pro_data['special_price'] =  $product['calculated_price'];
            }else{
                $pro_data['price'] = $product['calculated_price'];
            }
            if($discountRules = $productsExt['data']['main'][$product['id']]['discount_rules']){
                foreach($discountRules as $discount_rule){
                    if(isset($discount_rule['min']) && is_numeric($discount_rule['min'])){
                        $dis_price = $discount_rule['type_value'];
                        if($discount_rule['type'] == 'price'){
                            $dis_price = $product['calculated_price'] - $discount_rule['type_value'];
                        }
                        if($discount_rule['type'] == 'percent'){
                            $dis_price = $product['calculated_price'] - ($product['calculated_price'] * $discount_rule['type_value'] / 100);
                        }
                        $tierPrices[] = array(
                            'website_id'  => 0,
                            'cust_group'  => 32000,
                            'price_qty'   => $discount_rule['min'],
                            'price'       => $dis_price
                        );
                    }
                }
            }
            $pro_data['tier_price'] = $tierPrices;
            $pro_data['sku'] = $this->createProductSku($product['sku'], $this->_notice['config']['languages']);
            $manager_stock = 1;
            if(($this->_notice['config']['add_option']['stock'] && $product['inventory_level'] < 1) || $product['inventory_tracking'] == 'none'){
                $manager_stock = 0;
            }
            $pro_data['stock_data'] = array(
                'is_in_stock' => ($product['inventory_level'] < 1) ? 0 : 1,
                'manage_stock' => $manager_stock,
                'use_config_manage_stock' => $manager_stock,
                'qty' => $product['inventory_level'],
                'notify_stock_qty' => $product['inventory_warning_level'],
                'min_sale_qty' => $product['order_quantity_minimum'],
                'max_sale_qty' => $product['order_quantity_maximum']
            );
            $pro_data['name'] = $product['name'];
            if($images = $productsExt['data']['main'][$product['id']]['images']){
                foreach($images as $img){
                    if(isset($img['zoom_url']) && $img['zoom_url']){
                        if($img_path = $this->downloadImageFromUrl($img['zoom_url'], 'catalog/product', false, true)){
                            if($img['is_thumbnail'] == 1){
                                $pro_data['image_import_path'] = array('path' => $img_path, 'label' => $img['description'] ? $img['description'] : '');
                            }else{
                                $pro_data['image_gallery'][] = array('path' => $img_path, 'label' => $img['description'] ? $img['description'] : '') ;
                            }
                        }
                    }
                }
            }
            if($optRules = $productsExt['data']['main'][$product['id']]['option_rules']){
                foreach($optRules as $option_rule){
                    if(isset($option_rule['image_file']) && $option_rule['image_file']){
                        if($img_path = $this->downloadImageFromUrl(rtrim($this->_cart_url, '/') . '/product_images/' . $option_rule['image_file'], 'catalog/product', false, true)){
                            $pro_data['image_gallery'][] = array('path' => $img_path, 'label' => '') ;
                        }
                    }
                }
            }
            if($cusFields = $productsExt['data']['main'][$product['id']]['custom_fields']){
                foreach($cusFields as $cus_field){
                    $attr_code = $this->joinTextToKey($cus_field['name'], 30, '_');
                    $attr_import = $this->_makeAttributeImport($cus_field['name'], $attr_code, '', $entity_type_id, $this->_notice['config']['attribute_set_id'], 'text');
                    $attrAfterImport = $this->_process->attribute($attr_import);
                    $pro_data[$attrAfterImport['attribute_code']] = $cus_field['text'];
                }
            }
            //UPC
            if($product['upc']){
                $attr_code = 'le_upc';
                $attr_import = $this->_makeAttributeImport('UPC/EAN', $attr_code, '', $entity_type_id, $this->_notice['config']['attribute_set_id'], 'text');
                $attrAfterImport = $this->_process->attribute($attr_import);
                $pro_data[$attrAfterImport['attribute_code']] = $product['upc'];
            }
            //
            $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $pro_data['weight'] = $product['weight'] ? $product['weight']: 0 ;
        }
        $categories = $tierPrices = array();
        if($product['categories']){
            foreach($product['categories'] as $pro_cat){
                $cat_id = $this->getIdDescCategory($pro_cat);
                if($cat_id){
                    $categories[] = $cat_id;
                }
            }
        }
        $pro_data['type_id'] = $type_id;
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $categories;
        $pro_des = str_replace('%%GLOBAL_ShopPath%%', rtrim($this->_cart_url, '/'), $product['description']);
        $pro_data['description'] = $this->changeImgSrcInText($pro_des, $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $product['page_title'] ;
        $pro_data['meta_keyword'] = $product['meta_keywords'];
        $pro_data['meta_description'] = $product['meta_description'];
        $pro_data['status'] = ($product['is_visible']== 1)? 1 : 2;
        if($product['tax_class_id'] && $tax_pro_id = $this->getIdDescTaxProduct($product['tax_class_id'])){
            $pro_data['tax_class_id'] = $tax_pro_id;
        } elseif($tax_pro_id = $this->getIdDescTaxProduct(0)) {
            $pro_data['tax_class_id'] = $tax_pro_id;
        } else {
            $pro_data['tax_class_id'] = 0;
        }
        $pro_data['create_at'] = $product['date_created'];
        if($product['brand_id'] && $manufacture_mage_id = $this->getIdDescManufacturer($product['brand_id'])){
            $pro_data[self::MANUFACTURER_CODE] = $manufacture_mage_id;
        }
        $pro_data['multi_store'] = array();
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
                    'pricing_value' => 0,
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