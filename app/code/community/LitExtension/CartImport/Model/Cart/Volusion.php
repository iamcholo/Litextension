<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Cart_Volusion
    extends LitExtension_CartImport_Model_Cart{

    const VLS_CUR       = 'lecaip_volusion_currency';
    const VLS_TAX       = 'lecaip_volusion_tax';
    const VLS_CAT       = 'lecaip_volusion_category';
    const VLS_PRO       = 'lecaip_volusion_product';
    const VLS_OPT_CAT   = 'lecaip_volusion_option_category';
    const VLS_OPT       = 'lecaip_volusion_option';
    const VLS_KIT       = 'lecaip_volusion_kit';
    const VLS_KIT_LNK   = 'lecaip_volusion_kit_lnk';
    const VLS_CUS       = 'lecaip_volusion_customer';
    const VLS_ORD       = 'lecaip_volusion_order';
    const VLS_ORD_DTL   = 'lecaip_volusion_order_detail';
    const VLS_REV       = 'lecaip_volusion_review';
    const VLS_VERSION   = 1;

    protected $_demo_limit = array(
        'exchangeRates' => 100,
        'taxes' => 100,
        'manufacturers' => 100,
        'categories' => 100,
        'products' => 100,
        'optionCategories' => 100,
        'options' => 100,
        'kits' => 100,
        'kitLinks' => 100,
        'customers' => 100,
        'orders' => 100,
        'orderDetails' => 100,
        'reviews' => 0
    );

    public function __construct(){
        parent::__construct();
    }

    /**
     * List file to upload
     */
    public function getListUpload(){
        $upload = array(
            array('value' => 'exchangeRates', 'label' => "ExchangeRates"),
            array('value' => 'taxes', 'label' => "Tax"),
            array('value' => 'categories', 'label' => "Categories"),
            array('value' => 'products', 'label' => "Products"),
            array('value' => 'optionCategories', 'label' => "OptionCategories"),
            array('value' => 'options', 'label' => "Options"),
            array('value' => 'kits', 'label' => "KITS"),
            array('value' => 'kitLinks', 'label' => "KITLNKS"),
            array('value' => 'customers', 'label' => "Customers"),
            array('value' => 'orders', 'label' => "Orders"),
            array('value' => 'orderDetails', 'label' => "OrderDetails"),
            array('value' => 'reviews', 'label' => "Reviews")
        );
        return $upload;
    }

    /**
     * Clear database of previous import
     */
    public function clearPreSection(){
        if(!Mage::getStoreConfig('lecaip/setup/volusion')){
            return ;
        }
        $tables = array(
            self::VLS_CUR,
            self::VLS_TAX,
            self::VLS_CAT,
            self::VLS_PRO,
            self::VLS_OPT_CAT,
            self::VLS_OPT,
            self::VLS_KIT,
            self::VLS_KIT_LNK,
            self::VLS_CUS,
            self::VLS_ORD,
            self::VLS_ORD_DTL,
            self::VLS_REV
        );
        $folder = $this->_folder;
        foreach($tables as $table){
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
            $this->writeQuery($query);
        }
    }

    /**
     * List allow extensions file upload
     */
    public function getAllowExtensions()
    {
        return array('csv');
    }

    /**
     * Get file name upload by value list upload
     */
    public function getUploadFileName($upload_name)
    {
        return $upload_name . '.csv';
    }

    /**
     * Config and show warning after user upload file
     */
    public function getUploadInfo($up_msg){
        $files = array_filter($this->_notice['config']['files']);
        if(!empty($files)){
            $this->_notice['config']['import_support']['manufacturers'] = false;
            if(!$this->_notice['config']['files']['exchangeRates']){
                $this->_notice['config']['config_support']['currency_map'] = false;
            }
            if(!$this->_notice['config']['files']['taxes']){
                $this->_notice['config']['import_support']['taxes'] = false;
            }
            if(!$this->_notice['config']['files']['categories']){
                $this->_notice['config']['config_support']['category_map'] = false;
                $this->_notice['config']['import_support']['categories'] = false;
            }
            if(!$this->_notice['config']['files']['products']){
                $this->_notice['config']['config_support']['attribute_map'] = false;
                $this->_notice['config']['import_support']['products'] = false;
            }
            if(!$this->_notice['config']['files']['reviews']){
                $this->_notice['config']['import_support']['reviews'] = false;
            }
            if(!$this->_notice['config']['files']['customers']){
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['customers'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
            }
            if(!$this->_notice['config']['files']['orders']){
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
            }
            if(!$this->_notice['config']['files']['taxes']
                && !$this->_notice['config']['files']['customers']
                && !$this->_notice['config']['files']['orders']){
                $this->_notice['config']['config_support']['country_map'] = false;
            }
            foreach($files as $type => $upload){
                if($upload){
                    $func_construct = $type . "TableConstruct";
                    $construct = $this->$func_construct();
                    $validate = isset($construct['validation']) ? $construct['validation'] : false;
                    $csv_file = Mage::getBaseDir('media'). self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
                    $readCsv = $this->readCsv($csv_file, 0, 1, false);
                    if($readCsv['result'] == 'success'){
                        foreach($readCsv['data'] as $item){
                            if($validate){
                                foreach($validate as $row){
                                    if(!in_array($row, $item['title'])){
                                        $up_msg[$type] = array(
                                            'elm' => '#ur-' . $type,
                                            'msg' => "<div class='uir-warning'> File uploaded has incorrect structure</div>"
                                        );
                                    }
                                }
                            }
                        }
                    }

                }
            }
            if(isset($files['products']) && (!isset($files['optionCategories']) || !isset($files['options']))){
                $up_msg['products'] = array(
                    'elm' => '#ur-products',
                    'msg' => "<div class='uir-warning'> Product option not uploaded.</div>"
                );
            }
            if(!isset($files['products']) && isset($files['reviews'])){
                $up_msg['reviews'] = array(
                    'elm' => '#ur-reviews',
                    'msg' => "<div class='uir-warning'> Product not uploaded.</div>"
                );
            }
            if(isset($files['orders']) && !isset($files['customers'])){
                $up_msg['orders'] = array(
                    'elm' => '#ur-orders',
                    'msg' => "<div class='uir-warning'> Customer not uploaded.</div>"
                );
            }
            if(isset($files['orders']) && !isset($files['orderDetails'])){
                $up_msg['orders'] = array(
                    'elm' => '#ur-orders',
                    'msg' => "<div class='uir-warning'>  Order details not uploaded.</div>"
                );
            }
            $this->_notice['csv_import']['function'] = '_setupStorageCsv';
        }
        return array(
            'result' => 'success',
            'msg' => $up_msg
        );
    }

    /**
     * Process and get data use for config display
     *
     * @return array : Response as success or error with msg
     */
    public function displayConfig(){
        $parent = parent::displayConfig();
        if($parent["result"] != "success"){
            return $parent;
        }
        $response = array();
        $category_data = array("Root category");
        $attribute_data = array("Root attribute set");
        $languages_data = array(1 => "Default language");
        $order_status_data = array(
            'New - See Order Notes',
            'New',
            'Pending',
            'Processing',
            'Payment Declined',
            'Awaiting Payment',
            'Ready to Ship',
            'Pending Shipment',
            'Partially Shipped',
            'Shipped',
            'Partially Backordered',
            'Backordered',
            'See Line Items',
            'See Order Notes',
            'Partially Returned',
            'Returned',
            'Cancel Order',
            'Cancelled',
        );
        $currency_table = $this->getTableName(self::VLS_CUR);
        $currency_query = "SELECT * FROM {$currency_table} WHERE folder = '{$this->_folder}'";
        $currencies = $this->readQuery($currency_query);
        if($currencies['result'] != 'success'){
            return $this->errorDatabase();
        }
        $currency_data = array();
        foreach($currencies['data'] as $currency){
            $key = $currency['er_id'];
            $value = $currency['currency'];
            $currency_data[$key] = $value;
        }
        $country_data = array();
        if($this->_notice['config']['files']['taxes']){
            $tax_table = $this->getTableName(self::VLS_TAX);
            $tax_country_query = "SELECT taxcountry FROM {$tax_table} WHERE folder = '{$this->_folder}' GROUP BY taxcountry";
            $tax_country = $this->readQuery($tax_country_query);
            if($tax_country['result'] != 'success'){
                return $this->errorDatabase();
            }
            $tax_country_name = $this->duplicateFieldValueFromList($tax_country['data'], 'taxcountry');
            if($tax_country_name){
                $country_data = array_merge($country_data, $tax_country_name);
            }
        }
        if($this->_notice['config']['files']['customers']){
            $cus_table = $this->getTableName(self::VLS_CUS);
            $cus_country_query = "SELECT country FROM {$cus_table} WHERE folder = '{$this->_folder}' GROUP BY country";
            $cus_country = $this->readQuery($cus_country_query);
            if($cus_country['result'] !== 'success'){
                return $this->errorDatabase();
            }
            $cus_country_name = $this->duplicateFieldValueFromList($cus_country['data'], 'country');
            if($cus_country_name){
                $country_data = array_merge($country_data, $cus_country_name);
            }
        }
        if($this->_notice['config']['files']['orders']){
            $ord_table = $this->getTableName(self::VLS_ORD);
            $ord_country_query_bil = "SELECT billingcountry FROM {$ord_table} WHERE folder = '{$this->_folder}' GROUP BY billingcountry";
            $ord_country_query_ship = "SELECT shipcountry FROM {$ord_table} WHERE folder = '{$this->_folder}' GROUP BY shipcountry";
            $ord_country_bil = $this->readQuery($ord_country_query_bil);
            $ord_country_ship = $this->readQuery($ord_country_query_ship);
            if($ord_country_bil['result'] != 'success' || $ord_country_ship['result'] != 'success'){
                return $this->errorDatabase();
            }
            $ord_country_bil_name = $this->duplicateFieldValueFromList($ord_country_bil['data'], 'billingcountry');
            $ord_country_ship_name = $this->duplicateFieldValueFromList($ord_country_ship['data'], 'shipcountry');
            if($ord_country_bil_name){
                $country_data = array_merge($country_data, $ord_country_bil_name);
            }
            if($ord_country_ship_name){
                $country_data = array_merge($country_data, $ord_country_ship_name);
            }
        }
        if($country_data){
            $country_data = array_unique($country_data);
            $country_data = array_filter($country_data);
            $this->_notice['config']['countries_data'] = $country_data;
        } else {
            $this->_notice['config']['config_support']['country_map'] = false;
        }
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $languages_data;
        $this->_notice['config']['currencies_data'] = $currency_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
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
        $tax_table = $this->getTableName(self::VLS_TAX);
        $category_table = $this->getTableName(self::VLS_CAT);
        $product_table = $this->getTableName(self::VLS_PRO);
        $customer_table = $this->getTableName(self::VLS_CUS);
        $order_table = $this->getTableName(self::VLS_ORD);
        $review_table = $this->getTableName(self::VLS_REV);
        $queries = array(
            'taxes' => "SELECT COUNT(1) AS count FROM {$tax_table} WHERE folder = '{$this->_folder}'",
            'categories' => "SELECT COUNT(1) AS count FROM {$category_table} WHERE folder = '{$this->_folder}'",
            'products' => "SELECT COUNT(1) AS count FROM {$product_table} WHERE folder = '{$this->_folder}' AND (ischildofproductcode IS NULL OR ischildofproductcode = '')",
            'customers' => "SELECT COUNT(1) AS count FROM {$customer_table} WHERE folder = '{$this->_folder}'",
            'orders' => "SELECT COUNT(1) AS count FROM {$order_table} WHERE folder = '{$this->_folder}'",
            'reviews' => "SELECT COUNT(1) AS count FROM {$review_table} WHERE folder = '{$this->_folder}'"
        );
        $data = array();
        foreach($queries as $type => $query){
            $read = $this->readQuery($query);
            if($read['result'] != 'success'){
                return $this->errorDatabase();
            }
            $count = $this->arrayToCount($read['data'], 'count');
            $data[$type] = $count;
        }
        $data = $this->_limit($data);
        foreach($data as $type => $count){
            $this->_notice[$type]['total'] = $count;
        }
        if(LitExtension_CartImport_Model_Custom::CLEAR_IMPORT){
            $del = $this->deleteTable(self::TABLE_IMPORT, array(
                'folder' => $this->_folder
            ));
            if(!$del){
                return $this->errorDatabase();
            }
        }
        return array(
            'result' => 'success'
        );
    }

    /**
     * Router and work with csv file
     */
    public function storageCsv(){
        if(LitExtension_CartImport_Model_Custom::CSV_STORAGE){
            return $this->_custom->storageCsvCustom($this);
        }
        $function = $this->_notice['csv_import']['function'];
        if(!$function){
            return array(
                'result' => 'success',
                'msg' => ''
            );
        }
        return $this->$function();
    }

    /**
     * Config currency
     */
    public function configCurrency(){
        return array(
            'result' => 'success'
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
     */
    public function getTaxes(){
        $id_src = $this->_notice['taxes']['id_src'];
        $limit = $this->_notice['setting']['taxes'];
        $tax_table = $this->getTableName(self::VLS_TAX);
        $query = "SELECT * FROM {$tax_table} WHERE folder = '{$this->_folder}' AND taxid > {$id_src} ORDER BY taxid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    /**
     * Get primary key of main tax table
     *
     * @param array $tax : One row of function getTaxes
     * @return int
     */
    public function getTaxId($tax){
        return $tax['taxid'];
    }

    /**
     * Convert source data to data for import
     *
     * @param array $tax : One row of function getTaxes
     * @return array
     */
    public function convertTax($tax){
        if(LitExtension_CartImport_Model_Custom::TAX_CONVERT){
            return $this->_custom->convertTaxCustom($this, $tax);
        }
        $tax_cus_ids = $tax_pro_ids = $tax_rate_ids = array();
        if($tax_cus_default = $this->getIdDescTaxCustomer(1)){
            $tax_cus_ids[] = $tax_cus_default;
        }
        $class_name = $tax['taxcountry'];
        if($tax['taxstatelong']){
            $class_name .= "-" . $tax['taxstatelong'];
        }
        $tax_pro_data = array(
            'class_name' => $class_name
        );
        $tax_pro_ipt = $this->_process->taxProduct($tax_pro_data);
        if($tax_pro_ipt['result'] == 'success'){
            $tax_pro_ids[] = $tax_pro_ipt['mage_id'];
        }
        $tax_rate_data = array();
        $tax_rate_data['code'] = $this->createTaxRateCode($class_name);
        $country_id_config = $this->getArrayValueByValueArray($tax['taxcountry'], $this->_notice['config']['countries_data'], $this->_notice['config']['countries']);
        $country_id = $country_id_config ? $country_id_config : 'US';
        $tax_rate_data['tax_country_id'] = $country_id;
        if(!$tax['taxstatelong']){
            $tax_rate_data['tax_region_id'] = 0;
        } else {
            $tax_rate_data['tax_region_id'] = $this->getRegionId($tax['taxstatelong'], $country_id);
        }
        $tax_rate_data['zip_is_range'] = 0;
        $tax_rate_data['tax_postcode'] = "*";
        $tax_rate_data['rate'] = $tax['tax1_percent'] ? $tax['tax1_percent'] : 0;
        $tax_rate_ipt = $this->_process->taxRate($tax_rate_data);
        if($tax_rate_ipt['result'] == 'success'){
            $tax_rate_ids[] = $tax_rate_ipt['mage_id'];
        }
        $tax_rule_data = array();
        $tax_rule_data['code'] = $this->createTaxRuleCode($class_name);
        $tax_rule_data['tax_customer_class'] = $tax_cus_ids;
        $tax_rule_data['tax_product_class'] = $tax_pro_ids;
        $tax_rule_data['tax_rate'] = $tax_rate_ids;
        $tax_rule_data['priority'] = 0;
        $tax_rule_data['position'] = 0;
        $custom = $this->_custom->convertTaxCustom($this, $tax);
        if($custom){
            $tax_rule_data = array_merge($tax_rule_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $tax_rule_data
        );
    }

    /**
     * Get data for convert to manufacturer option
     */
    public function getManufacturers(){
        return false;
    }

    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer : One row of object in function getManufacturers
     * @return int
     */
    public function getManufacturerId($manufacturer){
        return false;
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturers
     * @return array
     */
    public function convertManufacturer($manufacturer){
        return false;
    }

    /**
     * Get data of main table use import category
     */
    public function getCategories(){
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $cat_table = $this->getTableName(self::VLS_CAT);
        $query = "SELECT * FROM {$cat_table} WHERE folder = '{$this->_folder}' AND categoryid > {$id_src} ORDER BY categoryid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        if($this->_notice['config']['add_option']['seo_url'] && $this->_notice['config']['add_option']['seo_plugin']){
            $seo_model = 'lecaip/' . $this->_notice['config']['add_option']['seo_plugin'];
            $this->_seo = Mage::getModel($seo_model);
        }
        return $result;
    }

    /**
     * Get primary key of source category
     *
     * @param array $category : One row of object in function getCategories
     * @return int
     */
    public function getCategoryId($category){
        return $category['categoryid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $category : One row of object in function getCategories
     * @return array
     */
    public function convertCategory($category){
        if(LitExtension_CartImport_Model_Custom::CATEGORY_CONVERT){
            return $this->_custom->convertCategoryCustom($this, $category);
        }
        if($category['parentid'] == 0){
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getIdDescCategory($category['parentid']);
            if(!$cat_parent_id){
                $parent_ipt = $this->_importCategoryParent($category['parentid']);
                if($parent_ipt['result'] == 'error'){
                    return $parent_ipt;
                } else if($parent_ipt['result'] == 'warning'){
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['categoryid']} import failed. Error: Could not import parent category id = {$category['parentid']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['categoryname'];
        $cat_data['description']  = $category['categorydescription'];
        $cat_data['meta_title'] = $category['metatag_title'];
        $cat_data['meta_keywords'] = $category['metatag_keywords'];
        $cat_data['meta_description'] = $category['metatag_description'];
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $cat_image_jpg = '/v/vspfiles/photos/categories/' . $category['categoryid'] . '.jpg';
        $cat_image_gif = '/v/vspfiles/photos/categories/' . $category['categoryid'] . '.gif';
        $url_none_http = $this->removeHttp(strtolower($this->_cart_url));
        $cart_url = 'http' . $url_none_http;
        if($img_path = $this->downloadImage($cart_url,  $cat_image_gif, 'catalog/category')){
            $cat_data['image'] = $img_path;
        }
        if($img_path = $this->downloadImage($cart_url,  $cat_image_jpg, 'catalog/category')){
            $cat_data['image'] = $img_path;
        }
        $cat_data['is_active'] = ($category['hidden'] == 'N') ? true : false;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = ($category['hidden'] ==  'N') ? 1 : 0;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $cat_data['position'] = $category['categoryorder'];
        if($this->_seo){
            $seo = $this->_seo->convertCategorySeo($this, $category);
            if($seo){
                $cat_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertCategoryCustom($this, $category);
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
     * Get data of main table use for import product
     */
    public function getProducts(){
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $product_table = $this->getTableName(self::VLS_PRO);
        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND id > {$id_src} AND (ischildofproductcode IS NULL OR ischildofproductcode = '') ORDER BY id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        if($this->_notice['config']['add_option']['seo_url'] && $this->_notice['config']['add_option']['seo_plugin']){
            $seo_model = 'lecaip/' . $this->_notice['config']['add_option']['seo_plugin'];
            $this->_seo = Mage::getModel($seo_model);
        }
        return $result;
    }

    /**
     * Get primary key of source product main
     *
     * @param array $product : One row of object in function getProducts
     * @return int
     */
    public function getProductId($product){
        return $product['id'];
    }

    /**
     * Check product has been imported
     *
     * @param array $product : One row of object in function getProducts
     * @return boolean
     */
    public function checkProductImport($product){
        $product_code = $product['productcode'];
        return $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $product_code);
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProducts
     * @return array
     */
    public function convertProduct($product){
        if(LitExtension_CartImport_Model_Custom::PRODUCT_CONVERT){
            return $this->_custom->convertProductCustom($this, $product);
        }
        $pro_has_child = $this->_checkProductHasChild($product);
        if($pro_has_child){
            $config_data = $this->_importChildrenProduct($product);
            if($config_data['result'] != 'success'){
                return $config_data;
            }
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
            $pro_data = array_merge($pro_data, $config_data['data']);
        } else {
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        }
        $pro_convert = $this->_convertProduct($product);
        if($pro_convert['result'] != 'success'){
            return $pro_convert;
        }
        $pro_data = array_merge($pro_data, $pro_convert['data']);
        return array(
            'result' => "success",
            'data' => $pro_data
        );
    }

    /**
     * Import product with data convert in function convertProduct
     *
     * @param array $data : Data of function convertProduct
     * @param array $product : One row of object in function getProducts
     * @return array
     */
    public function importProduct($data, $product){
        if(LitExtension_CartImport_Model_Custom::PRODUCT_IMPORT){
            return $this->_custom->importProductCustom($this, $data, $product);
        }
        $id_src = $this->getProductId($product);
        $productIpt = $this->_process->product($data);
        if($productIpt['result'] == 'success'){
            $id_desc = $productIpt['mage_id'];
            $this->productSuccess($id_src, $id_desc, $product['productcode']);
        } else {
            $productIpt['result'] = 'warning';
            $msg = "Product code = {$product['productcode']} import failed. Error: " . $productIpt['msg'];
            $productIpt['msg'] = $this->consoleWarning($msg);
        }
        return $productIpt;
    }

    /**
     * Process after one product import successful
     *
     * @param int $product_mage_id : Id of product save successful to magento
     * @param array $data : Data of function convertProduct
     * @param array $product : One row of object in function getProducts
     * @return boolean
     */
    public function afterSaveProduct($product_mage_id, $data, $product){
        if(parent::afterSaveProduct($product_mage_id, $data, $product)){
            return ;
        }
        if($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $product['optionids']){
            $optionIds = explode(',', $product['optionids']);
            $option_id_con = $this->arrayToInCondition($optionIds);
            $option_table = $this->getTableName(self::VLS_OPT);
            $option_query = "SELECT * FROM {$option_table} WHERE folder = '{$this->_folder}' AND id IN {$option_id_con}";
            $options = $this->readQuery($option_query);
            if($options['result'] != 'success' || empty($options['data'])){
                return;
            }
            $optionCatIds = $this->duplicateFieldValueFromList($options['data'], 'optioncatid');
            $option_cat_id_con = $this->arrayToInCondition($optionCatIds);
            $opt_cat_table = $this->getTableName(self::VLS_OPT_CAT);
            $opt_cat_query = "SELECT * FROM {$opt_cat_table} WHERE folder = '{$this->_folder}' AND id IN {$option_cat_id_con}";
            $optionCat = $this->readQuery($opt_cat_query);
            if($optionCat['result'] != 'success' || empty($optionCat['data'])){
                return;
            }
            $option_data = array();
            $types = array(
                'DROPDOWN' => 'drop_down',
                'DROPDOWN_CONTROL' => 'drop_down',
                'DROPDOWN_CLIENT' => 'drop_down',
                'DROPDOWN_SMARTMATCH' => 'drop_down',
                'CHECKBOX' => 'checkbox',
                'RADIO' => 'radio',
                'TEXTBOX' => 'field',
                'PLAIN_TEXT' => 'area'
            );
            foreach($optionCat['data'] as $option_cat){
                $display_type = $option_cat['displaytype'];
                $opt_data = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($types[$display_type]),
                    'type' => $types[$display_type],
                    'is_require' => 1,
                    'title' => $option_cat['optioncategoriesdesc'],
                );
                $optChild = $this->getListFromListByField($options['data'], 'optioncatid', $option_cat['id']);
                if(in_array($display_type, array('DROPDOWN', 'DROPDOWN_CONTROL', 'DROPDOWN_CLIENT', 'DROPDOWN_SMARTMATCH', 'CHECKBOX', 'RADIO'))){
                    $opt_data['values'] = array();
                    if($optChild){
                        foreach($optChild as $opt_child){
                            $value = array(
                                'option_type_id' => -1,
                                'title' => strip_tags($opt_child['optionsdesc']),
                                'price' => $opt_child['pricediff'],
                                'price_type' => 'fixed',
                            );
                            $opt_data['values'][] = $value;
                        }
                    }
                }
                if(in_array($display_type, array('TEXTBOX', 'PLAIN_TEXT'))){
                    if(isset($optChild[0])){
                        $opt_child = $optChild[0];
                        $opt_data['price'] = $opt_child['pricediff'];
                        $opt_data['price_type'] = 'fixed';
                    }
                }
                $option_data[] = $opt_data;
            }
            if($option_data){
                $this->importProductOption($product_mage_id, $option_data);
            }
        }
    }

    /**
     * Get data of main table use for import customer
     */
    public function getCustomers(){
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $customer_table = $this->getTableName(self::VLS_CUS);
        $query = "SELECT * FROM {$customer_table} WHERE folder = '{$this->_folder}' AND customerid > {$id_src} ORDER BY customerid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    /**
     * Get primary key of source customer main
     *
     * @param array $customer : One row of object in function getCustomers
     * @return int
     */
    public function getCustomerId($customer){
        return $customer['customerid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $customer : One row of object in function getCustomers
     * @return array
     */
    public function convertCustomer($customer){
        if(LitExtension_CartImport_Model_Custom::CUSTOMER_CONVERT){
            return $this->_custom->convertCustomerCustom($this, $customer);
        }
        $cus_data = array();
        if($this->_notice['config']['add_option']['pre_cus']){
            $cus_data['id'] = $customer['customerid'];
        }
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['emailaddress'];
        $cus_data['firstname'] = $customer['firstname'] ? $customer['firstname'] : " ";
        $cus_data['lastname'] = $customer['lastname'] ? $customer['lastname'] : " ";
        $cus_data['created_at'] = $customer['lastmodified'] ? date('Y-m-d H:i:s', strtotime($customer['lastmodified'])) : null;
        $cus_data['is_subscribed'] = ($customer['emailsubscriber'] ==  'Y') ? 1 : 0;
        $cus_data['group_id'] = 1;
        $custom = $this->_custom->convertCustomerCustom($this, $customer);
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
     * @param array $customer : One row of object function getCustomers
     * @return boolean
     */
    public function afterSaveCustomer($customer_mage_id, $data, $customer){
        if(parent::afterSaveCustomer($customer_mage_id, $data, $customer)){
            return ;
        }
        $address = array();
        $address['firstname'] = $customer['firstname'] ? $customer['firstname'] : " ";
        $address['lastname'] = $customer['lastname'] ? $customer['lastname'] : " ";
        /*$country_id_config = $this->getArrayValueByValueArray($customer['country'], $this->_notice['config']['countries_data'], $this->_notice['config']['countries']);
        $country_id = $country_id_config ? $country_id_config : 'US';*/
        $country_cus = str_replace(' ','',$customer['country']);
        $COUNTRY = array(
            'AF' => "Afghanistan",
            'AX' => "Aland Islands",
            'AL' => "Albania",
            'DZ' => "Algeria",
            'AS' => "American Samoa",
            'AD' => "Andorra",
            'AO' => "Angola",
            'AI' => "Anguilla",
            'AQ' => "Antarctica",
            'AG' => "Antigua and Barbuda",
            'AR' => "Argentina",
            'AM' => "Armenia",
            'AW' => "Aruba",
            'AP' => "Asia/Pacific Region",
            'AU' => "Australia",
            'AT' => "Austria",
            'AZ' => "Azerbaijan",
            'BS' => "Bahamas",
            'BH' => "Bahrain",
            'BD' => "Bangladesh",
            'BB' => "Barbados",
            'BY' => "Belarus",
            'BE' => "Belgium",
            'BZ' => "Belize",
            'BJ' => "Benin",
            'BM' => "Bermuda",
            'BT' => "Bhutan",
            'BO' => "Bolivia",
            'BQ' => "Bonaire, Saint Eustatius and Saba",
            'BA' => "Bosnia and Herzegovina",
            'BW' => "Botswana",
            'BR' => "Brazil",
            'IO' => "British Indian Ocean Territory",
            'BN' => "Brunei Darussalam",
            'BG' => "Bulgaria",
            'BF' => "Burkina Faso",
            'BI' => "Burundi",
            'KH' => "Cambodia",
            'CM' => "Cameroon",
            'CA' => "Canada",
            'CV' => "Cape Verde",
            'KY' => "Cayman Islands",
            'CF' => "Central African Republic",
            'TD' => "Chad",
            'CL' => "Chile",
            'CN' => "China",
            'CX' => "Christmas Island",
            'CC' => "Cocos (Keeling) Islands",
            'CO' => "Colombia",
            'KM' => "Comoros",
            'CG' => "Congo",
            'CD' => "Congo, The Democratic Republic of the",
            'CK' => "Cook Islands",
            'CR' => "Costa Rica",
            'CI' => "Cote D'Ivoire",
            'HR' => "Croatia",
            'CU' => "Cuba",
            'CW' => "Curacao",
            'CY' => "Cyprus",
            'CZ' => "Czech Republic",
            'DK' => "Denmark",
            'DJ' => "Djibouti",
            'DM' => "Dominica",
            'DO' => "Dominican Republic",
            'EC' => "Ecuador",
            'EG' => "Egypt",
            'SV' => "El Salvador",
            'GQ' => "Equatorial Guinea",
            'ER' => "Eritrea",
            'EE' => "Estonia",
            'ET' => "Ethiopia",
            'EU' => "Europe",
            'FK' => "Falkland Islands (Malvinas)",
            'FO' => "Faroe Islands",
            'FJ' => "Fiji",
            'FI' => "Finland",
            'FR' => "France",
            'GF' => "French Guiana",
            'PF' => "French Polynesia",
            'TF' => "French Southern Territories",
            'GA' => "Gabon",
            'GM' => "Gambia",
            'GE' => "Georgia",
            'DE' => "Germany",
            'GH' => "Ghana",
            'GI' => "Gibraltar",
            'GR' => "Greece",
            'GL' => "Greenland",
            'GD' => "Grenada",
            'GP' => "Guadeloupe",
            'GU' => "Guam",
            'GT' => "Guatemala",
            'GG' => "Guernsey",
            'GN' => "Guinea",
            'GW' => "Guinea-Bissau",
            'GY' => "Guyana",
            'HT' => "Haiti",
            'VA' => "Holy See (Vatican City State)",
            'HN' => "Honduras",
            'HK' => "Hong Kong",
            'HU' => "Hungary",
            'IS' => "Iceland",
            'IN' => "India",
            'ID' => "Indonesia",
            'IR' => "Iran, Islamic Republic of",
            'IQ' => "Iraq",
            'IE' => "Ireland",
            'IM' => "Isle of Man",
            'IL' => "Israel",
            'IT' => "Italy",
            'JM' => "Jamaica",
            'JP' => "Japan",
            'JE' => "Jersey",
            'JO' => "Jordan",
            'KZ' => "Kazakhstan",
            'KE' => "Kenya",
            'KI' => "Kiribati",
            'KP' => "Korea, Democratic People's Republic of",
            'KR' => "Korea, Republic of",
            'KW' => "Kuwait",
            'KG' => "Kyrgyzstan",
            'LA' => "Lao People's Democratic Republic",
            'LV' => "Latvia",
            'LB' => "Lebanon",
            'LS' => "Lesotho",
            'LR' => "Liberia",
            'LY' => "Libya",
            'LI' => "Liechtenstein",
            'LT' => "Lithuania",
            'LU' => "Luxembourg",
            'MO' => "Macau",
            'MK' => "Macedonia",
            'MG' => "Madagascar",
            'MW' => "Malawi",
            'MY' => "Malaysia",
            'MV' => "Maldives",
            'ML' => "Mali",
            'MT' => "Malta",
            'MH' => "Marshall Islands",
            'MQ' => "Martinique",
            'MR' => "Mauritania",
            'MU' => "Mauritius",
            'YT' => "Mayotte",
            'MX' => "Mexico",
            'FM' => "Micronesia, Federated States of",
            'MD' => "Moldova, Republic of",
            'MC' => "Monaco",
            'MN' => "Mongolia",
            'ME' => "Montenegro",
            'MS' => "Montserrat",
            'MA' => "Morocco",
            'MZ' => "Mozambique",
            'MM' => "Myanmar",
            'NA' => "Namibia",
            'NR' => "Nauru",
            'NP' => "Nepal",
            'NL' => "Netherlands",
            'NC' => "New Caledonia",
            'NZ' => "New Zealand",
            'NI' => "Nicaragua",
            'NE' => "Niger",
            'NG' => "Nigeria",
            'NU' => "Niue",
            'NF' => "Norfolk Island",
            'MP' => "Northern Mariana Islands",
            'NO' => "Norway",
            'OM' => "Oman",
            'PK' => "Pakistan",
            'PW' => "Palau",
            'PS' => "Palestinian Territory",
            'PA' => "Panama",
            'PG' => "Papua New Guinea",
            'PY' => "Paraguay",
            'PE' => "Peru",
            'PH' => "Philippines",
            'PN' => "Pitcairn Islands",
            'PL' => "Poland",
            'PT' => "Portugal",
            'PR' => "Puerto Rico",
            'QA' => "Qatar",
            'RE' => "Reunion",
            'RO' => "Romania",
            'RU' => "Russian Federation",
            'RW' => "Rwanda",
            'BL' => "Saint Barthelemy",
            'SH' => "Saint Helena",
            'KN' => "Saint Kitts and Nevis",
            'LC' => "Saint Lucia",
            'MF' => "Saint Martin",
            'PM' => "Saint Pierre and Miquelon",
            'VC' => "Saint Vincent and the Grenadines",
            'WS' => "Samoa",
            'SM' => "San Marino",
            'ST' => "Sao Tome and Principe",
            'SA' => "Saudi Arabia",
            'SN' => "Senegal",
            'RS' => "Serbia",
            'SC' => "Seychelles",
            'SL' => "Sierra Leone",
            'SG' => "Singapore",
            'SX' => "Sint Maarten (Dutch part)",
            'SK' => "Slovakia",
            'SI' => "Slovenia",
            'SB' => "Solomon Islands",
            'SO' => "Somalia",
            'ZA' => "South Africa",
            'GS' => "South Georgia and the South Sandwich Islands",
            'SS' => "South Sudan",
            'ES' => "Spain",
            'LK' => "Sri Lanka",
            'SD' => "Sudan",
            'SR' => "Suriname",
            'SJ' => "Svalbard and Jan Mayen",
            'SZ' => "Swaziland",
            'SE' => "Sweden",
            'CH' => "Switzerland",
            'SY' => "Syrian Arab Republic",
            'TW' => "Taiwan",
            'TJ' => "Tajikistan",
            'TZ' => "Tanzania, United Republic of",
            'TH' => "Thailand",
            'TL' => "Timor-Leste",
            'TG' => "Togo",
            'TK' => "Tokelau",
            'TO' => "Tonga",
            'TT' => "Trinidad and Tobago",
            'TN' => "Tunisia",
            'TR' => "Turkey",
            'TM' => "Turkmenistan",
            'TC' => "Turks and Caicos Islands",
            'TV' => "Tuvalu",
            'UG' => "Uganda",
            'UA' => "Ukraine",
            'AE' => "United Arab Emirates",
            'GB' => "United Kingdom",
            'US' => "United States",
            'UM' => "United States Minor Outlying Islands",
            'UY' => "Uruguay",
            'UZ' => "Uzbekistan",
            'VU' => "Vanuatu",
            'VE' => "Venezuela",
            'VN' => "Vietnam",
            'VG' => "Virgin Islands, British",
            'VI' => "Virgin Islands, U.S.",
            'WF' => "Wallis and Futuna",
            'YE' => "Yemen",
            'ZM' => "Zambia",
            'ZW' => "Zimbabwe"
        );
        foreach ($COUNTRY AS $code => $country){
            $country_def = str_replace(' ','',$country);
            if (strtolower($country_cus) == strtolower($country_def)){
                $country_cus_code = $code;
            }
        }
        $address['country_id'] = $country_cus_code;
        $address['street'] = $customer['billingaddress1'] . "\n" . $customer['billingaddress2'];
        $address['postcode'] = $customer['postalcode'];
        $address['city'] = $customer['city'];
        $address['telephone'] = $customer['phonenumber'];
        $address['company'] = $customer['companyname'];
        $address['fax'] = $customer['faxnumber'];
        if($customer['state']){
            $region_id = false;
            if(strlen($customer['state']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($customer['state'], $country_cus_code);
                if($region->getId()){
                    $region_id = $region->getId();
                }
            } else {
                $region_id = $this->getRegionId($customer['state'], $country_cus_code);
            }
            if($region_id){
                $address['region_id'] = $region_id;
            }
            $address['region'] = $customer['state'];
        } else {
            $address['region_id'] = 0;
        }
        $address_ipt = $this->_process->address($address, $customer_mage_id);
        if($address_ipt['result'] == 'success'){
            try{
                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                $cus->setDefaultBilling($address_ipt['mage_id']);
                $cus->setDefaultShipping($address_ipt['mage_id']);
            }catch (Exception $e){}
        }
    }

    /**
     * Get data use for import order
     */
    public function getOrders(){
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $order_table = $this->getTableName(self::VLS_ORD);
        $query = "SELECT * FROM {$order_table} WHERE folder = '{$this->_folder}' AND orderid > {$id_src} ORDER BY orderid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    /**
     * Get primary key of source order main
     *
     * @param array $order : One row of object in function getOrders
     * @return int
     */
    public function getOrderId($order){
        return $order['orderid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order : One row of object in function getOrders
     * @return array
     */
    public function convertOrder($order){
        if(LitExtension_CartImport_Model_Custom::ORDER_CONVERT){
            return $this->_custom->convertOrderCustom($this, $order);
        }
        $cus_table = $this->getTableName(self::VLS_CUS);
        $customers = $this->readQuery("SELECT * FROM {$cus_table} WHERE folder = '{$this->_folder}' AND customerid = {$order['customerid']} ");
        if($customers['result'] != 'success' || empty($customers['data'])){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Order id = {$order['orderid']} import failed. Error: Customer not import!")
            );
        }
        $ord_dlt_table = $this->getTableName(self::VLS_ORD_DTL);
        $ordDtlSrc = $this->readQuery("SELECT * FROM {$ord_dlt_table} WHERE folder = '{$this->_folder}' AND orderid = {$order['orderid']}");
        $ordDtl = ($ordDtlSrc['result'] == 'success') ? $ordDtlSrc['data'] : array();
        $customer = $customers['data'][0];

        $COUNTRY = array(
            'AF' => "Afghanistan",
            'AX' => "Aland Islands",
            'AL' => "Albania",
            'DZ' => "Algeria",
            'AS' => "American Samoa",
            'AD' => "Andorra",
            'AO' => "Angola",
            'AI' => "Anguilla",
            'AQ' => "Antarctica",
            'AG' => "Antigua and Barbuda",
            'AR' => "Argentina",
            'AM' => "Armenia",
            'AW' => "Aruba",
            'AP' => "Asia/Pacific Region",
            'AU' => "Australia",
            'AT' => "Austria",
            'AZ' => "Azerbaijan",
            'BS' => "Bahamas",
            'BH' => "Bahrain",
            'BD' => "Bangladesh",
            'BB' => "Barbados",
            'BY' => "Belarus",
            'BE' => "Belgium",
            'BZ' => "Belize",
            'BJ' => "Benin",
            'BM' => "Bermuda",
            'BT' => "Bhutan",
            'BO' => "Bolivia",
            'BQ' => "Bonaire, Saint Eustatius and Saba",
            'BA' => "Bosnia and Herzegovina",
            'BW' => "Botswana",
            'BR' => "Brazil",
            'IO' => "British Indian Ocean Territory",
            'BN' => "Brunei Darussalam",
            'BG' => "Bulgaria",
            'BF' => "Burkina Faso",
            'BI' => "Burundi",
            'KH' => "Cambodia",
            'CM' => "Cameroon",
            'CA' => "Canada",
            'CV' => "Cape Verde",
            'KY' => "Cayman Islands",
            'CF' => "Central African Republic",
            'TD' => "Chad",
            'CL' => "Chile",
            'CN' => "China",
            'CX' => "Christmas Island",
            'CC' => "Cocos (Keeling) Islands",
            'CO' => "Colombia",
            'KM' => "Comoros",
            'CG' => "Congo",
            'CD' => "Congo, The Democratic Republic of the",
            'CK' => "Cook Islands",
            'CR' => "Costa Rica",
            'CI' => "Cote D'Ivoire",
            'HR' => "Croatia",
            'CU' => "Cuba",
            'CW' => "Curacao",
            'CY' => "Cyprus",
            'CZ' => "Czech Republic",
            'DK' => "Denmark",
            'DJ' => "Djibouti",
            'DM' => "Dominica",
            'DO' => "Dominican Republic",
            'EC' => "Ecuador",
            'EG' => "Egypt",
            'SV' => "El Salvador",
            'GQ' => "Equatorial Guinea",
            'ER' => "Eritrea",
            'EE' => "Estonia",
            'ET' => "Ethiopia",
            'EU' => "Europe",
            'FK' => "Falkland Islands (Malvinas)",
            'FO' => "Faroe Islands",
            'FJ' => "Fiji",
            'FI' => "Finland",
            'FR' => "France",
            'GF' => "French Guiana",
            'PF' => "French Polynesia",
            'TF' => "French Southern Territories",
            'GA' => "Gabon",
            'GM' => "Gambia",
            'GE' => "Georgia",
            'DE' => "Germany",
            'GH' => "Ghana",
            'GI' => "Gibraltar",
            'GR' => "Greece",
            'GL' => "Greenland",
            'GD' => "Grenada",
            'GP' => "Guadeloupe",
            'GU' => "Guam",
            'GT' => "Guatemala",
            'GG' => "Guernsey",
            'GN' => "Guinea",
            'GW' => "Guinea-Bissau",
            'GY' => "Guyana",
            'HT' => "Haiti",
            'VA' => "Holy See (Vatican City State)",
            'HN' => "Honduras",
            'HK' => "Hong Kong",
            'HU' => "Hungary",
            'IS' => "Iceland",
            'IN' => "India",
            'ID' => "Indonesia",
            'IR' => "Iran, Islamic Republic of",
            'IQ' => "Iraq",
            'IE' => "Ireland",
            'IM' => "Isle of Man",
            'IL' => "Israel",
            'IT' => "Italy",
            'JM' => "Jamaica",
            'JP' => "Japan",
            'JE' => "Jersey",
            'JO' => "Jordan",
            'KZ' => "Kazakhstan",
            'KE' => "Kenya",
            'KI' => "Kiribati",
            'KP' => "Korea, Democratic People's Republic of",
            'KR' => "Korea, Republic of",
            'KW' => "Kuwait",
            'KG' => "Kyrgyzstan",
            'LA' => "Lao People's Democratic Republic",
            'LV' => "Latvia",
            'LB' => "Lebanon",
            'LS' => "Lesotho",
            'LR' => "Liberia",
            'LY' => "Libya",
            'LI' => "Liechtenstein",
            'LT' => "Lithuania",
            'LU' => "Luxembourg",
            'MO' => "Macau",
            'MK' => "Macedonia",
            'MG' => "Madagascar",
            'MW' => "Malawi",
            'MY' => "Malaysia",
            'MV' => "Maldives",
            'ML' => "Mali",
            'MT' => "Malta",
            'MH' => "Marshall Islands",
            'MQ' => "Martinique",
            'MR' => "Mauritania",
            'MU' => "Mauritius",
            'YT' => "Mayotte",
            'MX' => "Mexico",
            'FM' => "Micronesia, Federated States of",
            'MD' => "Moldova, Republic of",
            'MC' => "Monaco",
            'MN' => "Mongolia",
            'ME' => "Montenegro",
            'MS' => "Montserrat",
            'MA' => "Morocco",
            'MZ' => "Mozambique",
            'MM' => "Myanmar",
            'NA' => "Namibia",
            'NR' => "Nauru",
            'NP' => "Nepal",
            'NL' => "Netherlands",
            'NC' => "New Caledonia",
            'NZ' => "New Zealand",
            'NI' => "Nicaragua",
            'NE' => "Niger",
            'NG' => "Nigeria",
            'NU' => "Niue",
            'NF' => "Norfolk Island",
            'MP' => "Northern Mariana Islands",
            'NO' => "Norway",
            'OM' => "Oman",
            'PK' => "Pakistan",
            'PW' => "Palau",
            'PS' => "Palestinian Territory",
            'PA' => "Panama",
            'PG' => "Papua New Guinea",
            'PY' => "Paraguay",
            'PE' => "Peru",
            'PH' => "Philippines",
            'PN' => "Pitcairn Islands",
            'PL' => "Poland",
            'PT' => "Portugal",
            'PR' => "Puerto Rico",
            'QA' => "Qatar",
            'RE' => "Reunion",
            'RO' => "Romania",
            'RU' => "Russian Federation",
            'RW' => "Rwanda",
            'BL' => "Saint Barthelemy",
            'SH' => "Saint Helena",
            'KN' => "Saint Kitts and Nevis",
            'LC' => "Saint Lucia",
            'MF' => "Saint Martin",
            'PM' => "Saint Pierre and Miquelon",
            'VC' => "Saint Vincent and the Grenadines",
            'WS' => "Samoa",
            'SM' => "San Marino",
            'ST' => "Sao Tome and Principe",
            'SA' => "Saudi Arabia",
            'SN' => "Senegal",
            'RS' => "Serbia",
            'SC' => "Seychelles",
            'SL' => "Sierra Leone",
            'SG' => "Singapore",
            'SX' => "Sint Maarten (Dutch part)",
            'SK' => "Slovakia",
            'SI' => "Slovenia",
            'SB' => "Solomon Islands",
            'SO' => "Somalia",
            'ZA' => "South Africa",
            'GS' => "South Georgia and the South Sandwich Islands",
            'SS' => "South Sudan",
            'ES' => "Spain",
            'LK' => "Sri Lanka",
            'SD' => "Sudan",
            'SR' => "Suriname",
            'SJ' => "Svalbard and Jan Mayen",
            'SZ' => "Swaziland",
            'SE' => "Sweden",
            'CH' => "Switzerland",
            'SY' => "Syrian Arab Republic",
            'TW' => "Taiwan",
            'TJ' => "Tajikistan",
            'TZ' => "Tanzania, United Republic of",
            'TH' => "Thailand",
            'TL' => "Timor-Leste",
            'TG' => "Togo",
            'TK' => "Tokelau",
            'TO' => "Tonga",
            'TT' => "Trinidad and Tobago",
            'TN' => "Tunisia",
            'TR' => "Turkey",
            'TM' => "Turkmenistan",
            'TC' => "Turks and Caicos Islands",
            'TV' => "Tuvalu",
            'UG' => "Uganda",
            'UA' => "Ukraine",
            'AE' => "United Arab Emirates",
            'GB' => "United Kingdom",
            'US' => "United States",
            'UM' => "United States Minor Outlying Islands",
            'UY' => "Uruguay",
            'UZ' => "Uzbekistan",
            'VU' => "Vanuatu",
            'VE' => "Venezuela",
            'VN' => "Vietnam",
            'VG' => "Virgin Islands, British",
            'VI' => "Virgin Islands, U.S.",
            'WF' => "Wallis and Futuna",
            'YE' => "Yemen",
            'ZM' => "Zambia",
            'ZW' => "Zimbabwe"
        );
        $country_bill = str_replace(' ','',$order['billingcountry']);
        $country_ship = str_replace(' ','',$order['shipcountry']);
        $country_bill_code = $country_ship_code = 'US';
        foreach ($COUNTRY AS $code => $country){
            $country_def = str_replace(' ','',$country);
            if (strtolower($country_bill) == strtolower($country_def)){
                $country_bill_code = $code;
            }
            if (strtolower($country_ship) == strtolower($country_def)){
                $country_ship_code = $code;
            }
        }

        $data = $address_billing = $address_shipping = $carts = array();

        $address_billing['firstname'] = $order['billingfirstname'];
        $address_billing['lastname'] = $order['billinglastname'];
        $address_billing['company'] = $order['billingcompanyname'];
        $address_billing['email'] = $customer['emailaddress'];
        $address_billing['country_id'] = $country_bill_code;
        $address_billing['street'] = $order['billingaddress1'] . "\n" . $order['billingaddress2'];
        $address_billing['postcode'] = $order['billingpostalcode'];
        $address_billing['city'] = $order['billingcity'];
        $address_billing['telephone'] = $order['billingphonenumber'];
        $address_billing['fax'] = $order['billingfaxnumber'];
        if($order['billingstate']){
            $bil_region_id = false;
            if(strlen($customer['state']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($order['billingstate'], $country_bill_code);
                if($region->getId()){
                    $bil_region_id = $region->getId();
                }
            } else {
                $bil_region_id = $this->getRegionId($order['billingstate'], $country_bill_code);
            }
            if($bil_region_id){
                $address_billing['region_id'] = $bil_region_id;
            }
            $address_billing['region'] = $order['billingstate'];
        } else {
            $address_billing['region_id'] = 0;
        }

        $address_shipping['firstname'] = $order['shipfirstname'];
        $address_shipping['lastname'] = $order['shiplastname'];
        $address_shipping['company'] = $order['shipcompanyname'];
        $address_shipping['email'] = $customer['emailaddress'];
        $address_shipping['country_id'] = $country_ship_code;
        $address_shipping['street'] = $order['shipaddress1'] . "\n" . $order['shipaddress2'];
        $address_shipping['postcode'] = $order['shippostalcode'];
        $address_shipping['city'] = $order['shipcity'];
        $address_shipping['telephone'] = $order['shipphonenumber'];
        $address_shipping['fax'] = $order['shipfaxnumber'];
        if($order['shipstate']){
            $ship_region_id = false;
            if(strlen($customer['state']) == 2){
                $region = Mage::getModel('directory/region')->loadByCode($order['shipstate'], $country_ship_code);
                if($region->getId()){
                    $ship_region_id = $region->getId();
                }
            } else {
                $ship_region_id = $this->getRegionId($order['shipstate'], $country_ship_code);
            }
            if($ship_region_id){
                $address_shipping['region_id'] = $ship_region_id;
            }
            $address_shipping['region'] = $order['shipstate'];
        } else {
            $address_shipping['region_id'] = 0;
        }
        $discount = array();
        if($ordDtl){
            foreach($ordDtl as $order_detail){
                if($order_detail['discounttype']){
                    $discount = $order_detail;
                    continue ;
                }
                $cart = array();
                $product_id = $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $order_detail['productcode']);
                if($product_id){
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_detail['productname'];
                $cart['sku'] = $order_detail['productcode'];
                $cart['price'] = $order_detail['productprice'];
                $cart['original_price'] = $order_detail['productprice'];
                $cart['qty_ordered'] = $order_detail['quantity'];
                $cart['row_total'] = $order_detail['totalprice'];
                if($order_detail['options']){
                    $product_opt = array();
                    $options = str_replace('][', ',', $order_detail['options']);
                    $options = explode(',', $options);
                    foreach($options as $key => $option){
                        $option = str_replace('[', '', $option);
                        $option = str_replace(']', '', $option);
                        $optVal = explode(':', $option);
                        $opt_data = array(
                            'label' => isset($optVal[0])? $optVal[0] : " ",
                            'value' => isset($optVal[1])? $optVal[1] : " ",
                            'print_value' => isset($optVal[1])? $optVal[1] : " ",
                            'option_id' => 'option_' . $key,
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $product_opt[] = $opt_data;
                    }
                    $cart['product_options'] = serialize(array('options' => $product_opt));
                }
                $carts[]= $cart;
            }
        }

        $customer_id = $this->getIdDescCustomer($customer['customerid']);
        $order_status_map = array(
            'New' => 'pending',
            'Pending' => 'pending',
            'Processing' => 'processing',
            'Payment Declined' => 'payment_review',
            'Awaiting Payment' => 'pending_payment',
            'Ready to Ship' => 'processing',
            'Pending Shipment' => 'processing',
            'Partially Shipped' => 'processing',
            'Shipped' => 'complete',
            'Partially Backordered' => 'processing',
            'Backordered' => 'processing',
            'See Line Items' => 'pending',
            'See Order Notes' => 'pending',
            'Partially Returned' => 'closed',
            'Returned' => 'closed',
            'Cancelled' => 'canceled'
        );
        $order_status_id = 'pending';
        foreach ($order_status_map as $order_vls => $order_wp){
            if ($order['orderstatus'] == $order_vls)
                $order_status_id = $order_wp;
        }
        $tax_amount = $order['salestax1'] + $order['salestax2'] + $order['salestax3'];
        $discount_amount = $discount ? abs($discount['totalprice']) : 0;
        $store_id = $this->_notice['config']['languages'][1];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $ship_amount = $order['totalshippingcost'];
        $sub_total = $order['paymentamount'] - $tax_amount + $discount_amount - $ship_amount;

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if($customer_id){
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $customer['emailaddress'];
        $order_data['customer_firstname'] = $customer['firstname'];
        $order_data['customer_lastname'] = $customer['lastname'];
        $order_data['customer_group_id'] = 1;
        $order_data['status'] = $order_status_id;
        $order_data['state'] = $this->getOrderStateByStatus($order_status_id);
        $order_data['subtotal'] = $this->incrementPriceToImport($sub_total);
        $order_data['base_subtotal'] =  $order_data['subtotal'];
        $order_data['shipping_amount'] = $ship_amount;
        $order_data['base_shipping_amount'] = $ship_amount;
        $order_data['base_shipping_invoiced'] = $ship_amount;
        $order_data['shipping_description'] = "Shipping";
        $order_data['tax_amount'] = $tax_amount;
        $order_data['base_tax_amount'] = $tax_amount;
        $order_data['discount_amount'] = $discount_amount;
        $order_data['base_discount_amount'] = $discount_amount;
        $order_data['grand_total'] = $this->incrementPriceToImport($order['paymentamount']);
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
        $order_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['orderdate']));

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['orderid'];
        $custom = $this->_custom->convertOrderCustom($this, $order);
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
     * @param array $order : One row of object in function getOrders
     * @return boolean
     */
    public function afterSaveOrder($order_mage_id, $data, $order){
        if(parent::afterSaveOrder($order_mage_id, $data, $order)){
            return ;
        }
        $order_status_data = array();
        $order_status_map = array(
            'New' => 'pending',
            'Pending' => 'pending',
            'Processing' => 'processing',
            'Payment Declined' => 'payment_review',
            'Awaiting Payment' => 'pending_payment',
            'Ready to Ship' => 'processing',
            'Pending Shipment' => 'processing',
            'Partially Shipped' => 'processing',
            'Shipped' => 'complete',
            'Partially Backordered' => 'processing',
            'Backordered' => 'processing',
            'See Line Items' => 'pending',
            'See Order Notes' => 'pending',
            'Partially Returned' => 'closed',
            'Returned' => 'closed',
            'Cancelled' => 'canceled'
        );
        foreach ($order_status_map as $order_vls => $order_wp){
            if ($order['orderstatus'] == $order_vls)
                $order_status_id = $order_wp;
        }
        $order_status_data['status'] = $order_status_id;
        $order_status_data['state'] = $this->getOrderStateByStatus($order_status_id);
        $order_status_data['comment'] = "<b>Reference order #".$order['orderid']."</b><br />";
        $order_status_data['comment'] .= "<b>Payment method Id: </b>".$order['paymentmethodid']."<br />";
        $order_status_data['comment'] .= "<b>Shipping method Id: </b> ".$order['shippingmethodid']."<br />";
        $order_status_data['comment'] .= "<b>Order Notes: </b>".$order['order_comments'];
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date('Y-m-d H:i:s', strtotime($order['orderdate']));
        $order_status_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['orderdate']));
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }

    /**
     * Get main data use for import review
     */
    public function getReviews(){
        $id_src = $this->_notice['reviews']['id_src'];
        $limit = $this->_notice['setting']['reviews'];
        $rev_table = $this->getTableName(self::VLS_REV);
        $query = "SELECT * FROM {$rev_table} WHERE folder = '{$this->_folder}' AND reviewid > {$id_src} ORDER BY reviewid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        return $result;
    }

    /**
     * Get primary key of source review main
     *
     * @param array $review : One row of object in function getReviews
     * @return int
     */
    public function getReviewId($review){
        return $review['reviewid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviews
     * @return array
     */
    public function convertReview($review){
        if(LitExtension_CartImport_Model_Custom::REVIEW_CONVERT){
            return $this->_custom->convertReviewCustom($this, $review);
        }
        $product_id = $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, strtolower($review['productcode']));
        if(!$product_id){
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Review Id = {$review['reviewid']} import failed. Error: Product code = {$review['productcode']} not imported!")
            );
        }
        $review_name = $review['name'];
        if(!$review_name && $review['customerid'] && $this->_notice['config']['import']['customers']){
            $customer = $this->selectTableRow(self::VLS_CUS, array(
                'folder' => $this->_notice['config']['folder'],
                'customerid' => $review['customerid']
            ));
            if($customer){
                $review_name = $customer['firstname'] . " " . $customer['lastname'];
            }
        }
        if(!$review_name){
            $review_name = ' ';
        }
        $customer_id = $this->getIdDescCustomer($review['customerid']);
        $store_id = $this->_notice['config']['languages'][1];
        $data = array();
        $data['entity_pk_value'] = $product_id;
        $data['status_id'] = ($review['active'] == 'Y')? 1 : 3;
        $data['title'] = $review['reviewtitle'];
        $data['detail'] = $review['reviewdescription'];
        $data['entity_id'] = 1;
        $data['stores'] = array($store_id);
        $data['customer_id'] = $customer_id ? $customer_id : null;
        $data['nickname'] = $review_name;
        $data['rating'] = $review['rate'];
        $data['created_at'] = date('Y-m-d H:i:s', strtotime($review['lastmodified']));
        $data['review_id_import'] = $review['reviewid'];
        $custom = $this->_custom->convertOrderCustom($this, $review);
        if($custom){
            $data = array_merge($data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $data
        );
    }

    /**
     * TODO : Extend function
     */

    /**
     * Setup table for import
     */
    protected function _setupStorageCsv(){
        $custom_setup = $this->_custom->storageCsvCustom($this);
        if($custom_setup && $custom_setup['result'] == 'error'){
            return $custom_setup;
        }
        $volusion_setup = Mage::getStoreConfig('lecaip/setup/volusion');
        if($volusion_setup < self::VLS_VERSION){
            $setup = true;
            $tableDrop = $this->getListTableDrop();
            foreach($tableDrop as $table_drop){
                $this->dropTable($table_drop);
            }
            $tables = $queries = array();
            $creates = array(
                'exchangeRatesTableConstruct',
                'taxesTableConstruct',
                'categoriesTableConstruct',
                'productsTableConstruct',
                'optionCategoriesTableConstruct',
                'optionsTableConstruct',
                'kitsTableConstruct',
                'kitLinksTableConstruct',
                'customersTableConstruct',
                'ordersTableConstruct',
                'orderDetailsTableConstruct',
                'reviewsTableConstruct'
            );
            foreach($creates as $create){
                $tables[] = $this->$create();
            }
            foreach($tables as $table){
                $table_query = $this->arrayToCreateSql($table);
                if($table_query['result'] != 'success'){
                    $table_query['msg'] = $this->consoleError($table_query['msg']);
                    return $table_query;
                }
                $queries[] = $table_query['query'];
            }
            foreach($queries as $query){
                if(!$this->writeQuery($query)){
                    $setup = false;
                }
            }
            if($setup){
                Mage::getModel('core/config')->saveConfig('lecaip/setup/volusion', self::VLS_VERSION);
            } else {
                return array(
                    'result' => 'error',
                    'msg' => $this->consoleError("Could not created table to storage data.")
                );
            }
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_clearStorageCsv';
        $this->_notice['csv_import']['msg'] = "";
        return $this->_notice['csv_import'];
    }

    public function getListTableDrop(){
        $tables = $this->_getTablesTmp();
        $custom = $this->_custom->getListTableDropCustom($tables);
        $result = $custom ? $custom : $tables;
        return $result;
    }
    /**
     * Construct of table currency
     */
    public function exchangeRatesTableConstruct(){
        return array(
            'table' => self::VLS_CUR,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'er_id' => 'BIGINT',
                'currency' => 'VARCHAR(255)',
                'symbol' => 'VARCHAR(255)',
                'exchangerate' => 'VARCHAR(255)',
                'isdefault' => 'VARCHAR(5)',
                'lastmodified' => 'VARCHAR(255)',
                'paypal_currencycode' => 'VARCHAR(255)'
            ),
            'validation' => array('er_id', 'currency')
        );
    }

    /**
     * Construct of table taxes
     */
    public function taxesTableConstruct(){
        return array(
            'table' => self::VLS_TAX,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'taxid' => 'BIGINT',
                'taxstateshort' => 'VARCHAR(255)',
                'taxstatelong' => 'VARCHAR(255)',
                'taxcountry' => 'VARCHAR(255)',
                'tax1_title' => 'TEXT',
                'tax2_title' => 'TEXT',
                'tax3_title' => 'TEXT',
                'tax1_percent' => 'TEXT',
                'tax2_percent' => 'TEXT',
                'tax3_percent' => 'TEXT',
            ),
            'validation' => array('taxid')
        );
    }

    /**
     * Construct of table categories
     */
    public function categoriesTableConstruct(){
        return array(
            'table' => self::VLS_CAT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'categoryid' => 'BIGINT',
                'parentid' => 'BIGINT',
                'categoryname' => 'TEXT',
                'categoryorder' => 'VARCHAR(255)',
                'categoryvisible' => 'VARCHAR(255)',
                'metatag_title' => 'TEXT',
                'metatag_description' => 'TEXT',
                'link_title_tag' => 'TEXT',
                'categorydescriptionshort' => 'TEXT',
                'categorydescription' => 'TEXT',
                'metatag_keywords' => 'TEXT',
                'hidden' => 'VARCHAR(5)'
            ),
            'validation' => array('categoryid', 'categoryname')
        );
    }

    /**
     * Construct of table products
     */
    public function productsTableConstruct(){
        return array(
            'table' => self::VLS_PRO,
            'rows' => array(
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'productcode' => 'VARCHAR(255)',
                'productname' => 'TEXT',
                'hideproduct' => 'VARCHAR(5)',
                'stockstatus' => 'VARCHAR(255)',
                'lastmodified' => 'VARCHAR(255)',
                'ischildofproductcode' => 'VARCHAR(255)',
                'productnameshort' => 'VARCHAR(255)',
                'productweight' => 'VARCHAR(255)',
                'recurringprice' => 'VARCHAR(255)',
                'productprice' => 'VARCHAR(255)',
                'listprice' => 'VARCHAR(255)',
                'saleprice' => 'VARCHAR(255)',
                'metatag_title' => 'TEXT',
                'metatag_description' => 'TEXT',
                'photo_subtext' => 'TEXT',
                'photo_alttext' => 'TEXT',
                'setupcost' => 'VARCHAR(255)',
                'productdescriptionshort' => 'TEXT',
                'productdescription' => 'TEXT',
                'metatag_keywords' => 'TEXT',
                'categoryids' => 'TEXT',
                'optionids' => 'TEXT',
                'photourl' => 'TEXT',
                'photourl_large' => 'TEXT',
                'donotallowbackorders' => 'VARCHAR(10)'
            ),
            'validation' => array('productcode')
        );
    }

    /**
     * Construct of table option categories
     */
    public function optionCategoriesTableConstruct(){
        return array(
            'table' => self::VLS_OPT_CAT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT',
                'headinggroup' => 'VARCHAR(255)',
                'optioncategoriesdesc' => 'VARCHAR(255)',
                'displaytype' => 'VARCHAR(255)',
            ),
            'validation' => array('id')
        );
    }

    /**
     * Construct of table options
     */
    public function optionsTableConstruct(){
        return array(
            'table' => self::VLS_OPT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'id' => 'BIGINT',
                'optioncatid' => 'BIGINT',
                'optionsdesc' => 'VARCHAR(255)',
                'pricediff' => 'VARCHAR(255)'
            ),
            'validation' => array('id', 'optioncatid')
        );
    }

    /**
     * Construct of table kits
     */
    public function kitsTableConstruct(){
        return array(
            'table' => self::VLS_KIT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'kit_id' => 'BIGINT',
                'kit_type' => 'VARCHAR(255)',
                'kit_productcode' => 'VARCHAR(255)',
                'kit_isproductcode' => 'VARCHAR(255)',
                'kit_qty' => 'VARCHAR(255)',
                'lastmodified' => 'VARCHAR(255)',
                'lastmodby' => 'VARCHAR(255)',
                'kit_orderby' => 'VARCHAR(255)'
            ),
            'validation' => array('kit_id', 'kit_type', 'kit_productcode')
        );
    }

    /**
     * Construct of table kits lnk
     */
    public function kitLinksTableConstruct(){
        return array(
            'table' => self::VLS_KIT_LNK,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'kitlnk_id' => 'BIGINT',
                'kit_id' => 'BIGINT',
                'kitlnk_productcode' => 'VARCHAR(255)',
                'kitlnk_optionid' => 'BIGINT',
                'kitlnk_qty' => 'VARCHAR(255)',
                'kitlnk_pricediff' => 'VARCHAR(255)'
            ),
            'validation' => array('kitlnk_id', 'kit_id', 'kitlnk_optionid')
        );
    }

    /**
     * Construct of table customer
     */
    public function customersTableConstruct(){
        return array(
            'table' => self::VLS_CUS,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'customerid' => 'BIGINT',
                'accesskey' => 'VARCHAR(5)',
                'firstname' => 'VARCHAR(255)',
                'lastname' => 'VARCHAR(255)',
                'companyname' => 'VARCHAR(255)',
                'billingaddress1' => 'VARCHAR(255)',
                'billingaddress2' => 'VARCHAR(255)',
                'city' => 'VARCHAR(255)',
                'state' => 'VARCHAR(255)',
                'postalcode' => 'VARCHAR(255)',
                'country' => 'VARCHAR(255)',
                'phonenumber' => 'VARCHAR(255)',
                'faxnumber' => 'VARCHAR(255)',
                'emailaddress' => 'VARCHAR(255)',
                'emailsubscriber' => 'VARCHAR(5)',
                'lastmodified' => 'VARCHAR(255)'
            ),
            'validation' => array('customerid')
        );
    }

    /**
     * Construct of table order
     */
    public function ordersTableConstruct(){
        return array(
            'table' => self::VLS_ORD,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'orderid' => 'BIGINT',
                'customerid' => 'BIGINT',
                'billingcompanyname' => 'VARCHAR(255)',
                'billingfirstname' => 'VARCHAR(255)',
                'billinglastname' => 'VARCHAR(255)',
                'billingaddress1' => 'VARCHAR(255)',
                'billingaddress2' => 'VARCHAR(255)',
                'billingcity' => 'VARCHAR(255)',
                'billingstate' => 'VARCHAR(255)',
                'billingpostalcode' => 'VARCHAR(255)',
                'billingcountry' => 'VARCHAR(255)',
                'billingphonenumber' => 'VARCHAR(255)',
                'billingfaxnumber' => 'VARCHAR(255)',
                'shipcompanyname' => 'VARCHAR(255)',
                'shipfirstname' => 'VARCHAR(255)',
                'shiplastname' => 'VARCHAR(255)',
                'shipaddress1' => 'VARCHAR(255)',
                'shipaddress2' => 'VARCHAR(255)',
                'shipcity' => 'VARCHAR(255)',
                'shipstate' => 'VARCHAR(255)',
                'shippostalcode' => 'VARCHAR(255)',
                'shipcountry' => 'VARCHAR(255)',
                'shipphonenumber' => 'VARCHAR(255)',
                'shipfaxnumber' => 'VARCHAR(255)',
                'shippingmethodid' => 'VARCHAR(255)',
                'totalshippingcost' => 'VARCHAR(255)',
                'salestaxrate' => 'VARCHAR(255)',
                'paymentamount' => 'VARCHAR(255)',
                'paymentmethodid' => 'VARCHAR(255)',
                'cardholdersname' => 'VARCHAR(255)',
                'creditcardexpdate' => 'VARCHAR(255)',
                'creditcardauthorizationnumber' => 'VARCHAR(255)',
                'creditcardtransactionid' => 'VARCHAR(255)',
                'bankname' => 'VARCHAR(255)',
                'orderdate' => 'VARCHAR(255)',
                'orderstatus' => 'VARCHAR(255)',
                'total_payment_received' => 'VARCHAR(255)',
                'total_payment_authorized' => 'VARCHAR(255)',
                'salestax1' => 'VARCHAR(255)',
                'salestax2' => 'VARCHAR(255)',
                'salestax3' => 'VARCHAR(255)',
                'ordernotes' => 'TEXT',
                'order_comments' => 'TEXT',
                'orderdateutc' => 'VARCHAR(255)'
            ),
            'validation' => array('orderid')
        );
    }

    /**
     * Construct of table order details
     */
    public function orderDetailsTableConstruct(){
        return array(
            'table' => self::VLS_ORD_DTL,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'orderdetailid' => 'BIGINT',
                'orderid' => 'BIGINT',
                'productcode' => 'VARCHAR(255)',
                'productname' => 'VARCHAR(255)',
                'quantity' => 'VARCHAR(255)',
                'productprice' => 'VARCHAR(255)',
                'totalprice' => 'VARCHAR(255)',
                'optionids' => 'VARCHAR(255)',
                'options' => 'TEXT',
                'discounttype' => 'VARCHAR(255)',
                'discountvalue' => 'VARCHAR(255)'
            ),
            'validation' => array('orderdetailid', 'orderid', 'productcode')
        );
    }

    /**
     * Construct of table review
     */
    public function reviewsTableConstruct(){
        return array(
            'table' => self::VLS_REV,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'reviewid' => 'BIGINT',
                'lastmodified' => 'VARCHAR(255)',
                'productcode' => 'VARCHAR(255)',
                'reviewtitle' => 'VARCHAR(255)',
                'rate' => 'INT(5)',
                'customerid' => 'BIGINT',
                'name' => 'VARCHAR(255)',
                'active' => 'VARCHAR(5)',
                'reviewdescription' => 'TEXT'
            ),
            'validation' => array('reviewid', 'productcode')
        );
    }

    /**
     * Clear data if exit in database
     */
    protected function _clearStorageCsv(){
        $tables = $this->_getTablesTmp();
        $folder = $this->_folder;
        foreach($tables as $table){
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
            $this->writeQuery($query);
        }
        $this->_notice['csv_import']['function'] = '_storageCsvExchangeRates';
        return array(
            'result' => 'process',
            'msg' => ''
        );
    }

    /**
     * Storage Csv ExchangeRates to database
     */
    protected function _storageCsvExchangeRates(){
        return $this->_storageCsvByType('exchangeRates', 'taxes');
    }

    /**
     * Storage Csv taxes to database
     */
    protected function _storageCsvTaxes(){
        return $this->_storageCsvByType('taxes', 'categories');
    }

    /**
     * Storage Csv categories to database
     */
    protected function _storageCsvCategories(){
        return $this->_storageCsvByType('categories', 'products');
    }

    /**
     * Storage Csv products to database
     */
    protected function _storageCsvProducts(){
        return $this->_storageCsvByType('products', 'optionCategories', false, false, array('id'));
    }

    /**
     * Storage Csv option categories to database
     */
    protected function _storageCsvOptionCategories(){
        return $this->_storageCsvByType('optionCategories', 'customers', 'options');
    }

    /**
     * Storage Csv options to database
     */
    protected function _storageCsvOptions(){
        return $this->_storageCsvByType('options', 'kits');
    }

    /**
     * Storage Csv kits to database
     */
    protected function _storageCsvKits(){
        return $this->_storageCsvByType('kits', 'kitLinks');
    }

    /**
     * Storage Csv kits lnk to database
     */
    protected function _storageCsvKitLinks(){
        return $this->_storageCsvByType('kitLinks', 'customers');
    }

    /**
     * Storage Csv customers to database
     */
    protected function _storageCsvCustomers(){
        return $this->_storageCsvByType('customers', 'orders');
    }

    /**
     * Storage Csv orders to database
     */
    protected function _storageCsvOrders(){
        return $this->_storageCsvByType('orders', 'orderDetails');
    }

    /**
     * Storage Csv order details to database
     */
    protected function _storageCsvOrderDetails(){
        return $this->_storageCsvByType('orderDetails', 'reviews');
    }

    /**
     * Storage Csv reviews to database
     */
    protected function _storageCsvReviews(){
        return $this->_storageCsvByType('reviews', 'reviews', false, true);
    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($category_id){
        $category_table = $this->getTableName(self::VLS_CAT);
        $query = "SELECT * FROM {$category_table} WHERE folder = '{$this->_folder}' AND categoryid = {$category_id}";
        $categories = $this->readQuery($query);
        if($categories['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        $category = isset($categories['data'][0]) ? $categories['data'][0] : false;
		if(!$category){
			return array(
                'result' => 'warning',
            );
		}
        $convert = $this->convertCategory($category);
        if($convert['result'] != 'success'){
            return array(
                'result' => 'warning',
            );
        }
        $data = $convert['data'];
        $category_ipt = $this->_process->category($data);
        if($category_ipt['result'] == 'success'){
            $this->categorySuccess($category_id, $category_ipt['mage_id']);
            $this->afterSaveCategory($category_ipt['mage_id'], $data, $category);
        } else {
            $category_ipt['result'] = 'warning';
        }
        return $category_ipt;
    }

    /**
     * Check product has children product in product table
     */
    protected function _checkProductHasChild($product){
        $product_table = $this->getTableName(self::VLS_PRO);
        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND ischildofproductcode = '{$product['productcode']}'";
        $result = $this->readQuery($query);
        if($result['result'] != 'success'){
            return false;
        }
        if(!empty($result['data'])){
            return true;
        }
        return false;
    }

    /**
     * Convert data of src cart to magento
     */
    protected function _convertProduct($product){
        $pro_data = $category_ids = array();
        if($product['categoryids']){
            $categoryIds = explode(',', $product['categoryids']);
            foreach($categoryIds as $category_id){
                $category_id_desc = $this->getIdDescCategory($category_id);
                if($category_id_desc){
                    $category_ids[] = $category_id_desc;
                }
            }
        }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = $this->createProductSku($product['productcode'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['productname'];
        $pro_data['description'] = $this->changeImgSrcInText($product['productdescription'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['short_description'] = $this->changeImgSrcInText($product['productdescriptionshort'], $this->_notice['config']['add_option']['img_des']);
        $pro_data['meta_title'] = $product['metatag_title'];
        $pro_data['meta_keyword'] = $product['metatag_keywords'];
        $pro_data['meta_description'] = $product['metatag_description'];
        $pro_data['weight'] = $product['productweight'];
        $pro_data['status'] = ($product['hideproduct'] == 'Y') ? false : true;
        $pro_data['price'] = $product['productprice'] ? $product['productprice'] : 0;
        $pro_data['tax_class_id'] = 0;
        if($product['saleprice']){
            $pro_data['special_price'] = $product['saleprice'];
        }
        $pro_data['create_at'] = date('Y-m-d H:i:s', strtotime($product['lastmodified']));
        $pro_data['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $manage_stock = true;
        if($product['stockstatus'] === null || $product['stockstatus'] == ''){
            $manage_stock = false;
        }
        if($this->_notice['config']['add_option']['stock'] && $product['stockstatus'] < 1){
            $manage_stock = false;
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $manage_stock,
            'use_config_manage_stock' => $manage_stock,
            'qty' => ($product['stockstatus'])? $product['stockstatus'] : 0,
            'backorders' => ($product['donotallowbackorders'] == 'N') ? 0 : 1
        );
        if($product['listprice']){
            $pro_data['msrp'] = $product['listprice'];
        }
        $img = (strpos($product['photourl'], 'nophoto.gif') === false && $product['photourl_large']) ? $product['photourl_large'] : $product['photourl'];
        if($img){
            $img = strtolower($img);
            $image_convert = $this->convertUrlToDownload($img, $this->_cart_url);
            if($image_convert){
                $img_path = $this->downloadImage($image_convert['domain'], $image_convert['path'], 'catalog/product', false, true);
                if($img_path){
                    $pro_data['image_import_path'] = array('path' => $img_path, 'label' => $product['photo_alttext']);
                }
            }
        }
        if($this->_seo){
            $seo = $this->_seo->convertProductSeo($this, $product);
            if($seo){
                $pro_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product);
        if($custom){
            $pro_data = array_merge($pro_data, $custom);
        }
        return array(
            'result' => 'success',
            'data' => $pro_data
        );
    }

    /**
     * Import and create data for configurable product
     */
    protected function _importChildrenProduct($parent){
        $product_table = $this->getTableName(self::VLS_PRO);
        $kit_table = $this->getTableName(self::VLS_KIT);
        $kit_link_table = $this->getTableName(self::VLS_KIT_LNK);
        $option_table = $this->getTableName(self::VLS_OPT);
        $option_cat_table = $this->getTableName(self::VLS_OPT_CAT);
        $product_query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND ischildofproductcode = '{$parent['productcode']}'";
        $proChild = $this->readQuery($product_query);
        if($proChild['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        $kit_query = "SELECT * FROM {$kit_table} WHERE folder = '{$this->_folder}' AND kit_productcode = '{$parent['productcode']}' ORDER BY kit_orderby ASC";
        $kits = $this->readQuery($kit_query);
        if($kits['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        $kitIds = $this->duplicateFieldValueFromList($kits['data'], 'kit_id');
        $kit_id_con = $this->arrayToInCondition($kitIds);
        $kit_link_query = "SELECT * FROM {$kit_link_table} WHERE folder = '{$this->_folder}' AND kit_id IN {$kit_id_con}";
        $kitLinks = $this->readQuery($kit_link_query);
        if($kitLinks['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        $optionIds = $this->duplicateFieldValueFromList($kitLinks['data'], 'kitlnk_optionid');
        $option_id_con = $this->arrayToInCondition($optionIds);
        $option_query = "SELECT * FROM {$option_table} WHERE folder = '{$this->_folder}' AND id IN {$option_id_con}";
        $options = $this->readQuery($option_query);
        if($options['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        $optionCatIds = $this->duplicateFieldValueFromList($options['data'], 'optioncatid');
        $option_cat_id_con = $this->arrayToInCondition($optionCatIds);
        $option_cat_query = "SELECT * FROM {$option_cat_table} WHERE folder = '{$this->_folder}' AND id IN {$option_cat_id_con}";
        $optionCat = $this->readQuery($option_cat_query);
        if($optionCat['result'] != 'success'){
            return $this->errorDatabase(true);
        }
        if(empty($kits['data']) || empty($kitLinks['data']) || empty($options['data']) || empty($optionCat['data'])){
            return array(
                'result' => 'success',
                'data' => array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
                )
            );
        }
        $attrIpt = $this->_importAttribute($options, $optionCat);
        if($attrIpt['result'] != 'success'){
            return array(
                'result' => "warning",
                'msg' => $this->consoleWarning("Product Code = {$parent['productcode']} import failed. Error: Product attribute could not be created!")
            );
        }
        if($attrIpt['type'] == 'change'){
            return array(
                'result' => "success",
                'data' => array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
                )
            );
        }
        $configurable_products_data = $configurable_attributes_data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        foreach($proChild['data'] as $pro_child){
            $pro_child_ipt_id = $this->getIdDescProduct($pro_child['id']);
            if($pro_child_ipt_id){
                // do nothing
            } else {
                $pro_child_data = array(
                    'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
					'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                );
                $pro_child_convert = $this->_convertProduct($pro_child);
                if($pro_child_convert['result'] != 'success'){
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product Code = {$parent['productcode']} import failed. Error: Product children could not create!(Error code: Product children data not found.)")
                    );
                }
                $pro_child_data = array_merge($pro_child_convert['data'], $pro_child_data);
                $pro_child_ipt = $this->_process->product($pro_child_data);
                if($pro_child_ipt['result'] != 'success'){
                    return array(
                        'result' => "warning",
                        'msg' => $this->consoleWarning("Product code = {$parent['productcode']} import failed. Error: Product children could not create!(Error code: " . $pro_child_ipt['msg'] . ". )")
                    );
                }
                $this->productSuccess($pro_child['id'], $pro_child_ipt['mage_id'], $pro_child['productcode']);
                $pro_child_ipt_id = $pro_child_ipt['mage_id'];
            }
            $pro_child_kit = $this->getRowFromListByField($kits['data'], 'kit_isproductcode', $pro_child['productcode']);
            if(!$pro_child_kit){
                continue;
            }
            $pro_child_kit_link = $this->getListFromListByField($kitLinks['data'], 'kit_id', $pro_child_kit['kit_id']);
            if(!$pro_child_kit_link){
                continue ;
            }
            foreach($pro_child_kit_link as $pro_child_kit_link_row){
                $pro_child_opt = $this->getRowFromListByField($options['data'], 'id', $pro_child_kit_link_row['kitlnk_optionid']);
                if(!$pro_child_opt){
                    continue ;
                }
                if(!isset($attrIpt['data'][$pro_child_opt['optioncatid']])){
                    continue ;
                }
                $pro_child_attr = $attrIpt['data'][$pro_child_opt['optioncatid']];
                $key = 'option_' . $pro_child_opt['id'];
                if(isset($pro_child_attr['data']['option_ids'][$key])){
                    $opt_id = $pro_child_attr['data']['option_ids'][$key];
                    $attr_id = $pro_child_attr['data']['attribute_id'];
                    $this->setProAttrSelect($entity_type_id, $attr_id, $pro_child_ipt_id, $opt_id);
                    $pro_attr_data = array(
                        'label' => isset($pro_child_attr['opt_label'][$key]) ? $pro_child_attr['opt_label'][$key] : " ",
                        'attribute_id' => $attr_id,
                        'value_index' => $opt_id,
                    );
                    $configurable_products_data[$pro_child_ipt_id][] = $pro_attr_data;
                }
            }
        }
        foreach($attrIpt['data'] as $key => $attr_ipt){
            $attr_data = array(
                'label' => $attr_ipt['label'],
                'use_default' => 1,
                'attribute_id' => $attr_ipt['data']['attribute_id'],
                'attribute_code' => $attr_ipt['data']['attribute_code'],
                'frontend_label' => $attr_ipt['label'],
                'store_label' => $attr_ipt['label'],
                'html_id' => 'configurable__attribute_' . $key,
            );
            $option_ids = $attr_ipt['data']['option_ids'];
            $values = array();
            foreach($option_ids as $opt_key => $opt_value){
                $opt_id = str_replace('option_', '', $opt_key);
                $opt_kit_link = $this->getRowFromListByField($kitLinks['data'], 'kitlnk_optionid', $opt_key);
                if(is_int($opt_kit_link['kitlnk_pricediff'])){
                    $pricing_value = $opt_kit_link['kitlnk_pricediff'];
                } else {
                    $opt = $this->getRowFromListByField($options['data'], 'id', $opt_id);
                    $pricing_value = $opt['pricediff'];
                }
                $value = array(
                    'label' => isset($attr_ipt['opt_label'][$opt_key]) ? $attr_ipt['opt_label'][$opt_key] : " ",
                    'attribute_id' => $attr_ipt['data']['attribute_id'],
                    'value_index' => $opt_value,
                    'is_percent' => 0,
                    'pricing_value' => $pricing_value,
                );
                $values[] = $value;
            }
            $attr_data['values'] = $values;
            $configurable_attributes_data[] = $attr_data;
        }
        $data = array(
            'configurable_products_data' => $configurable_products_data,
            'configurable_attributes_data' => $configurable_attributes_data,
            'can_save_configurable_attributes' => true,
        );
        return array(
            'result' => "success",
            'data' => $data
        );
    }

    /**
     * Import attribute for create configurable product
     */
    protected function _importAttribute($options, $optionCat){
        $data = array();
        $entity_type_id = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        foreach($optionCat['data'] as $option_cat){
            $opts = $this->getListFromListByField($options['data'], 'optioncatid', $option_cat['id']);
            if(!$opts){
                continue;
            }
            $attr_save = $attr_data = array();
            $attr_data = array(
                'entity_type_id' => $entity_type_id,
                'attribute_code' => $this->joinTextToKey($option_cat['optioncategoriesdesc'], 27, '_'),
                'attribute_set_id' => $this->_notice['config']['attribute_set_id'],
                'frontend_input' => 'select',
                'frontend_label' => array($option_cat['optioncategoriesdesc']),
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            );
            $attr_save['label'] = $option_cat['optioncategoriesdesc'];
            $values = $opt_label = array();
            foreach($opts as $opt){
                $key = 'option_' . $opt['id'];
                $values[$key] = array(
                    0 => $opt['optionsdesc']
                );
                $opt_label[$key] = $opt['optionsdesc'];
            }
            $attr_data['option']['value'] = $values;
            $attr_save['opt_label'] = $opt_label;
            $attr_ipt = $this->_process->attribute($attr_data, array(
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'is_configurable' => true,
                'apply_to' => array()
            ));
            if(!$attr_ipt){
                return array(
                    'result' => "warning",
                    'msg' => ""
                );
            }
            $attr_save['data'] = $attr_ipt;
            $data[$option_cat['id']] = $attr_save;
        }
        if(!$data){
            return array(
                'result' => 'success',
                'type' => 'change',
                'data' => array()
            );
        }
        return array(
            'result' => 'success',
            'type' => '',
            'data' => $data
        );
    }

    /**
     * Get id_desc by type and value
     */
    protected function _getLeCaIpImportIdDescByValue($type, $value){
        $result = $this->selectTableRow(self::TABLE_IMPORT, array(
            'folder' => $this->_folder,
            'type' => $type,
            'value' => $value
        ));
        if(!$result){
            return false;
        }
        return (isset($result['id_desc'])) ? $result['id_desc'] : false;
    }

    protected function _getTablesTmp(){
        return array(
            self::VLS_CUR,
            self::VLS_TAX,
            self::VLS_CAT,
            self::VLS_PRO,
            self::VLS_OPT_CAT,
            self::VLS_OPT,
            self::VLS_KIT,
            self::VLS_KIT_LNK,
            self::VLS_CUS,
            self::VLS_ORD,
            self::VLS_ORD_DTL,
            self::VLS_REV
        );
    }
}