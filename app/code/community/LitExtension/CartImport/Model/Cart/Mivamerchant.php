<?php

/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartImport_Model_Cart_Mivamerchant
    extends LitExtension_CartImport_Model_Cart
{

    const MIVA_CAT = 'lecaip_miva_category';
    const MIVA_PRO = 'lecaip_miva_product';
    const MIVA_CUS = 'lecaip_miva_customer';
    const MIVA_ORD = 'lecaip_miva_order';
    const MIVA_ORD_ID = 'lecaip_miva_order_id';
    const MIVA_PROVIDE = 'lecaip_miva_provide';

    protected $_demo_limit = array(
        'categories' => 10,
        'products' => 10,
        'customers' => 10,
        'orders' => 10,
//        'provide' => 10,
    );

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List file to upload
     */
    public function getListUpload()
    {
        $products_guide = '<ol>
            <li>Log into Miva Merchant admin panel. The path for export differs depending on which version you are using.</li>
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>In Miva x.5 go to <b>Utilities -&gt; Export Data -&gt; Export Products to Flat File</b>.</li>
                <li>In Miva x.9 - <b>Menu -&gt; Data Management -&gt; Export Products to Flat File</b>.</li>
              </ol>
            </div>
            <li>Name the file as <b><i>products.dat</i></b>, set <b>Legacy Import Product From Flat File</b> Format and <b>Tab</b> as File Delimiter.</li>
            <li>Fill in the box to <b>Export Field Names as Header</b>.</li>
            <li>In Category Settings, check on <b>Include Category List</b>.</li>
            <li>Press <b>Export</b> button and save the file to your computer.</li>
            <li>Upload this data to Migration Wizard and proceed to the next step.</li>
          </ol>';
        $categories_guide = '<ol>
              <li>Log into Miva Merchant admin panel. The path for export differs depending on which version you are using.</li>
              <div class="notice_message">
                <ol style="list-style: square; padding-left: 20px;">
                  <li>In Miva x.5 go to <b>Utilities -&gt; Export Data -&gt; Export Categories to Flat File</b>.</li>
                  <li>In Miva x.9 - <b>Menu -&gt; Data Management -&gt;Export Categories to Flat File</b>.</li>
                </ol>
              </div>
              <li>Name the file as <b><i>categories.dat</i></b>, set <b>Legacy Import Categories From Flat File</b> Format and <b>Tab</b> as File Delimiter.</li>
              <li>Fill in the box to <b>Export Field Names as Header</b>.</li>
              <li>Select <b>Code, Name, Active, Parent Category Code</b> fields to export.</li>
              <li>Press <b>Export button</b> and save the file to your computer</li>
              <li>Upload this data to Migration Wizard and proceed to the next step.</li>
            </ol>';
        $customers_guide = '<ol>
            <li>Log into Miva Merchant admin panel. The path for export differs depending on which version you are using.</li>
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>In Miva x.5 go to <b>Utilities -&gt; Export Data -&gt; Export Customers to Flat File</b>.</li>
                <li>In Miva x.9 - <b>Menu -&gt; Data Management -&gt;Export Customers to Flat File</b>.</li>
              </ol>
            </div>
            <li>Name the file as <b><i>customers.dat</i></b>, set <b>Legacy Import Customers From Flat File</b> Format and <b>Tab</b> as File Delimiter.</li>
            <li>Fill in the box to <b>Export Field Names as Header</b>.</li>
            <li>Select <b>Login Pass; Recovery Email Ship; First Name Ship; Last Name Ship; Email Ship; Phone Ship; Fax Ship; Company Ship; Address Ship; City Ship; State Ship; Zip Ship; Country Bill; First Name Bill; Last Name Bill; Phone Bill; Fax Bill; Email Bill; Company Bill; Address Bill; City Bill; State Bill; Zip Bill; Country</b> fields to export.</li>
            <li>Press <b>Export button</b> and save the file to your computer.</li>
            <li>Upload this data to Migration Wizard and proceed to the next step.</li>
          </ol>';
        $provide_guide = '<ol>
              <li>Log into Miva Merchant admin panel. The path for export differs depending on which version you are using.</li>
              <div class="notice_message">
                <ol style="list-style: square; padding-left: 20px;">
                  <li>In Miva x.5 go to <b>Utilities -&gt; Export Data -&gt; Export Attributes to XML File</b>.</li>
                  <li>In Miva x.9 - <b>Menu -&gt; Data Management -&gt; Export Attributes to XML File</b>.</li>
                </ol>
              </div>
              <li>Name the file as <b><i>provide.xml</i></b>  and Press Export button.</li>
              <li>Download the file to your computer and save it in the place that is easy for you to find.</li>
              <li>Upload XML to Migration Wizard and proceed to the next step.</li>
            </ol>';
        $orders_guide = '<ol>
            <li>Log into Miva Merchant admin panel. The path for export differs depending on which version you are using.</li>
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>In Miva x.5 go to <b>Utilities -&gt; Export Data -&gt; Export Orders to Flat File</b>.</li>
                <li>In Miva x.9 - <b>Menu -&gt; Data Management -&gt;Export Orders to Flat File</b>.</li>
              </ol>
            </div>
            <li>Name the file as <b><i>orders.dat</i></b>, and set <b>Tab</b> as File Delimiter.</li>
            <li>Fill in the box to <b>Export Field Names as Header</b>.</li>
            <li>Press <b>Export button</b> and save the file to your computer.</li>
            <li>Upload this data to Migration Wizard and proceed to the next step.</li>
          </ol>';
        $upload = array(
            array('value' => 'products', 'label' => "Products"),
            array('value' => 'guide', 'label' => $products_guide),
            array('value' => 'provide', 'label' => "Attributes"),
            array('value' => 'guide', 'label' => $provide_guide),
            array('value' => 'categories', 'label' => "Categories"),
            array('value' => 'guide', 'label' => $categories_guide),
            array('value' => 'customers', 'label' => "Customers"),
            array('value' => 'guide', 'label' => $customers_guide),
            array('value' => 'orders', 'label' => "Orders"),
            array('value' => 'guide', 'label' => $orders_guide),

        );
        return $upload;
    }

    /**
     * Clear database of previous import
     */
    public function clearPreSection()
    {
        $tables = array(
            self::MIVA_CAT,
            self::MIVA_PRO,
            self::MIVA_CUS,
            self::MIVA_PROVIDE,
            self::MIVA_ORD,
            self::MIVA_ORD_ID
        );
        $folder = $this->_folder;
        foreach ($tables as $table) {
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
        return array('dat', 'xml');
    }

    /**
     * Get file name upload by value list upload
     */
    public function getUploadFileName($upload_name)
    {
        $name = '';
        if ($upload_name == 'provide') {
            $name = $upload_name . '.xml';
        } else {
            $name = $upload_name . '.dat';
        }
        return $name;
    }

    /**
     * Config and show warning after user upload file
     */
    public function getUploadInfo($up_msg)
    {

        $files = array_filter($this->_notice['config']['files']);
        if (!empty($files)) {
            $this->_notice['config']['import_support']['manufacturers'] = false;
            $this->_notice['config']['import_support']['reviews'] = false;
            $this->_notice['config']['config_support']['currency_map'] = false;
            $this->_notice['config']['import_support']['taxes'] = false;
            $this->_notice['config']['config_support']['country_map'] = false;
            $this->_notice['config']['config_support']['order_status_map'] = false;
            if (!$this->_notice['config']['files']['categories']) {
                $this->_notice['config']['config_support']['category_map'] = false;
                $this->_notice['config']['import_support']['categories'] = false;
            }
            if (!$this->_notice['config']['files']['products']) {
                $this->_notice['config']['config_support']['attribute_map'] = false;
                $this->_notice['config']['import_support']['products'] = false;
            }

            if (!$this->_notice['config']['files']['customers']) {
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['customers'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
            }
            if (!$this->_notice['config']['files']['orders']) {
                $this->_notice['config']['config_support']['order_status_map'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
            }
            if (!$this->_notice['config']['files']['customers']
                && !$this->_notice['config']['files']['orders']
            ) {
                $this->_notice['config']['config_support']['country_map'] = false;
            }
            foreach ($files as $type => $upload) {
                if ($upload) {
                    $func_construct = $type . "TableConstruct";
                    $construct = $this->$func_construct();
                    $validate = isset($construct['validation']) ? $construct['validation'] : false;

                    if ($type == 'provide') {
                        $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.xml';
                    } else {
                        $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.dat';
                    }

                    $readFile = $this->readDat($_file, 0, 1, false, $type);
                    if ($readFile['result'] == 'success') {

                        foreach ($readFile['data'] as $item) {
                            if ($validate) {
                                foreach ($validate as $row) {
                                    if (!in_array($row, $item['title'])) {
                                        $up_msg[$type] = array(
                                            'elm' => '#ur-' . $type,
                                            'msg' => "<div class='uir-warning'> File uploaded has incorrect structure $row</div>"
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!isset($files['products'])) {
                $up_msg['reviews'] = array(
                    'elm' => '#ur-reviews',
                    'msg' => "<div class='uir-warning'> Product not uploaded.</div>"
                );
            }

            if (isset($files['orders']) && !isset($files['customers'])) {
                $up_msg['orders'] = array(
                    'elm' => '#ur-orders',
                    'msg' => "<div class='uir-warning'> Customer not uploaded.</div>"
                );
            }

            $this->_notice['csv_import']['function'] = '_setupStorage'; // function chạy sau khi nhán next
        }
        return array(
            'result' => 'success',
            'msg' => $up_msg
        );
    }

    /**
     * Router and work with csv file
     */
    public function storageCsv()
    {
        $function = $this->_notice['csv_import']['function'];
        if (!$function) {
            return array(
                'result' => 'success',
                'msg' => ''
            );
        }
        return $this->$function();
    }

    /**
     * Process and get data use for config display
     *
     * @return array : Response as success or error with msg
     */
    public function displayConfig()
    {
        $parent = parent::displayConfig();
        if ($parent["result"] != "success") {
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
            'Cancel Order'
        );
        $country_data = array();

        if ($this->_notice['config']['files']['customers']) {
            $cus_table = $this->getTableName(self::MIVA_CUS);
            $cus_country_query_bil = "SELECT CUSTOMER_BILLING_COUNTRY FROM {$cus_table} WHERE folder = '{$this->_folder}' GROUP BY CUSTOMER_BILLING_COUNTRY";
            $cus_country_query_ship = "SELECT CUSTOMER_SHIPPING_COUNTRY FROM {$cus_table} WHERE folder = '{$this->_folder}' GROUP BY CUSTOMER_SHIPPING_COUNTRY";
            $cus_country_bil = $this->readQuery($cus_country_query_bil);
            $cus_country_ship = $this->readQuery($cus_country_query_ship);
            if ($cus_country_bil['result'] != 'success' || $cus_country_ship['result'] != 'success') {
                return $this->errorDatabase();
            }
            $cus_country_bil_name = $this->duplicateFieldValueFromList($cus_country_bil['data'], 'CUSTOMER_BILLING_COUNTRY');
            $cus_country_ship_name = $this->duplicateFieldValueFromList($cus_country_ship['data'], 'CUSTOMER_SHIPPING_COUNTRY');
            if ($cus_country_bil_name) {
                $country_data = array_merge($country_data, $cus_country_bil_name);
            }
            if ($cus_country_ship_name) {
                $country_data = array_merge($country_data, $cus_country_ship_name);
            }
        }
        if ($this->_notice['config']['files']['orders']) {
            $ord_table = $this->getTableName(self::MIVA_ORD);
            $ord_country_query_bil = "SELECT BILL_CNTRY FROM {$ord_table} WHERE folder = '{$this->_folder}' GROUP BY BILL_CNTRY";
            $ord_country_query_ship = "SELECT SHIP_CNTRY FROM {$ord_table} WHERE folder = '{$this->_folder}' GROUP BY SHIP_CNTRY";
            $ord_country_bil = $this->readQuery($ord_country_query_bil);
            $ord_country_ship = $this->readQuery($ord_country_query_ship);
            if ($ord_country_bil['result'] != 'success' || $ord_country_ship['result'] != 'success') {
                return $this->errorDatabase();
            }
            $ord_country_bil_name = $this->duplicateFieldValueFromList($ord_country_bil['data'], 'BILL_CNTRY');
            $ord_country_ship_name = $this->duplicateFieldValueFromList($ord_country_ship['data'], 'SHIP_CNTRY');
            if ($ord_country_bil_name) {
                $country_data = array_merge($country_data, $ord_country_bil_name);
            }
            if ($ord_country_ship_name) {
                $country_data = array_merge($country_data, $ord_country_ship_name);
            }
        }
        if ($country_data) {
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
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $response['result'] = 'success';
        return $response;
    }

    /**
     * Save config of use in config step to notice
     */
    public function displayConfirm($params)
    {
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
    public function displayImport()
    {
        $category_table = $this->getTableName(self::MIVA_CAT);
        $product_table = $this->getTableName(self::MIVA_PRO);
        $customer_table = $this->getTableName(self::MIVA_CUS);
        $order_id_table = $this->getTableName(self::MIVA_ORD_ID);
        $provide_table = $this->getTableName(self::MIVA_PROVIDE);
        $queries = array(
            'categories' => "SELECT COUNT(1) AS count FROM {$category_table} WHERE folder = '{$this->_folder}'",
            'products' => "SELECT COUNT(1) AS count FROM {$product_table} WHERE folder = '{$this->_folder}'",// AND (ischildofproductcode IS NULL OR ischildofproductcode = '')
            'customers' => "SELECT COUNT(1) AS count FROM {$customer_table} WHERE folder = '{$this->_folder}'",
            'orders' => "SELECT COUNT(1) AS count FROM {$order_id_table} WHERE folder = '{$this->_folder}';", //COUNT(DISTINCT(ORDER_ID))
        );
        $data = array();
        foreach ($queries as $type => $query) {
            $read = $this->readQuery($query);
            if ($read['result'] != 'success') {
                return $this->errorDatabase();
            }
            $count = $this->arrayToCount($read['data'], 'count');
            $data[$type] = $count;
        }

        $data = $this->_limit($data);
        foreach ($data as $type => $count) {
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
     * Config currency
     */
    public function configCurrency()
    {
        return array(
            'result' => 'success'
        );
    }

    /**
     * Get data of main table use import category
     */
    public function getCategories()
    {
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $cat_table = $this->getTableName(self::MIVA_CAT);
        $query = "SELECT * FROM {$cat_table} WHERE folder = '{$this->_folder}' AND categoryid > {$id_src} ORDER BY categoryid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
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
    public function getCategoryId($category)
    {
        return $category['categoryid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $category : One row of object in function getCategories
     * @return array
     */
    public function convertCategory($category)
    {
        if (LitExtension_CartImport_Model_Custom::CATEGORY_CONVERT) {
            return $this->_custom->convertCategoryCustom($this, $category);
        }

        if ($category['CATEGORY_PARENT_CODE'] == '' || $category['CATEGORY_PARENT_CODE'] == null) {
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $category_table = $this->getTableName(self::MIVA_CAT);
            $query = "SELECT * FROM {$category_table} WHERE folder = '{$this->_folder}' AND CATEGORY_CODE = '{$category['CATEGORY_PARENT_CODE']}'";
            $categories = $this->readQuery($query);
            if($categories['result'] != 'success'){
                return $this->errorDatabase(true);
            }
            $category_parent = $categories['data'][0];
            $cat_parent_id = $this->getIdDescCategory($category_parent['categoryid']);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($category_parent['categoryid']);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Code = {$category_parent['CATEGORY_PARENT_CODE']} import failed. Error: Could not import parent category id = {$category_parent['categoryid']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }

        }
        $cat_data = array();
        $cat_data['name'] = $category['CATEGORY_NAME'];
//        $cat_data['description']  = $category['categorydescription'];
        $cat_data['meta_title'] = $category['CATEGORY_PAGE_TITLE'];
        $cat_data['meta_keywords'] = $category['keywords'];
        $cat_data['meta_description'] = $category['description'];

        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);

        $cat_data['path'] = $pCat->getPath();

        $cat_image = $category['category_title_image'];
        $cat_image = str_replace(' ', '%20', $cat_image);
        if ($img_path = $this->downloadImage($this->_cart_url, $cat_image, 'catalog/category')) {
            $cat_data['image'] = $img_path;
        }

        $cat_data['is_active'] = ($category['CATEGORY_ACTIVE'] == '1') ? true : false;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = ($category['CATEGORY_ACTIVE'] == '1') ? 1 : 0;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        if ($this->_seo) {
            $seo = $this->_seo->convertCategorySeo($this, $category);
            if ($seo) {
                $cat_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertCategoryCustom($this, $category);
        if ($custom) {
            $cat_data = array_merge($cat_data, $custom);
        }

        return array(
            'result' => 'success',
            'data' => $cat_data
        );
    }

    /**
     * Import category with data convert in function convertCategory
     *
     * @param array $data : Data of function convertCategory
     * @param array $category : One row of object in function getCategories
     * @return array
     */
    public function importCategory($data, $category)
    {
        if (LitExtension_CartImport_Model_Custom::CATEGORY_IMPORT) {
            return $this->_custom->importCategoryCustom($this, $data, $category);
        }
        $id_src = $this->getCategoryId($category);
        $categoryIpt = $this->_process->category($data);
        if ($categoryIpt['result'] == 'success') {
            $id_desc = $categoryIpt['mage_id'];
            $this->categorySuccess($id_src, $id_desc,$category['CATEGORY_CODE']);
        } else {
            $categoryIpt['result'] = 'warning';
            $msg = "Category Id = {$id_src} import failed. Error: " . $categoryIpt['msg'];
            $categoryIpt['msg'] = $this->consoleWarning($msg);
        }
        return $categoryIpt;
    }

    /**
     * Process before import products
     */
    public function prepareImportProducts()
    {
        parent::prepareImportProducts();
        $this->_notice['extend']['website_ids'] = $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
    }

    /**
     * Get data of main table use for import product
     */
    public function getProducts()
    {
        $id_src = $this->_notice['products']['id_src'];
        $limit = $this->_notice['setting']['products'];
        $product_table = $this->getTableName(self::MIVA_PRO);
        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND id > {$id_src} ORDER BY id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
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
    public function getProductId($product)
    {
        return $product['id'];
    }

    /**
     * Check product has been imported
     *
     * @param array $product : One row of object in function getProducts
     * @return boolean
     */
    public function checkProductImport($product)
    {
        $product_code = $product['PRODUCT_CODE'];

        return $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $product_code);
    }

    /**
     * Convert source data to data import
     *
     * @param array $product : One row of object in function getProducts
     * @return array
     */
    public function convertProduct($product)
    {
        if (LitExtension_CartImport_Model_Custom::PRODUCT_CONVERT) {
            return $this->_custom->convertProductCustom($this, $product);
        }
//        $pro_has_child = $this->_checkProductHasChild($product);
//        if($pro_has_child){
//            $config_data = $this->_importChildrenProduct($product);
//            if($config_data['result'] != 'success'){
//                return $config_data;
//            }
//            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
//            $pro_data = array_merge($pro_data, $config_data['data']);
//        } else {
        $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
//        }
        $pro_convert = $this->_convertProduct($product);
        if ($pro_convert['result'] != 'success') {
            return $pro_convert;
        }
        $pro_data = array_merge($pro_data, $pro_convert['data']);
        return array(
            'result' => "success",
            'data' => $pro_data
        );
    }

    /**
     * Convert data of src cart to magento
     */
    protected function _convertProduct($product)
    {
        $pro_data = $category_ids = array();
        if ($product['CATEGORY_CODES'] != '') {

            $categories_code = explode(',', $product['CATEGORY_CODES']);
            foreach ($categories_code as $category_code) {
                $category_id_desc = $this->_getLeCaIpImportIdDescByValue(self::TYPE_CATEGORY,$category_code);
                if ((int)$category_id_desc) {
                    $category_ids[] = (int)$category_id_desc;
                }
            }

        }
        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['category_ids'] = $category_ids;
        $pro_data['sku'] = $this->createProductSku($product['PRODUCT_SKU'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['PRODUCT_NAME'];
        $desc = nl2br($product['PRODUCT_DESC']);
        $desc = str_replace(array('\r\n','\r','\n'),'',$desc);
        $short_desc = nl2br($product['short_description']);
        $short_desc = str_replace(array('\r\n','\r','\n'),'',$short_desc);
        $pro_data['description'] = $desc;
        $pro_data['short_description'] = $short_desc;
//        $pro_data['meta_title'] = $product['metatag_title'];
        $pro_data['meta_keyword'] = $product['keywords'];
        $pro_data['meta_description'] = nl2br($product['description']);
        $pro_data['weight'] = $product['PRODUCT_WEIGHT'];
        $pro_data['status'] = $product['PRODUCT_ACTIVE'] ? $product['PRODUCT_ACTIVE'] : 2;
        $pro_data['price'] = $product['PRODUCT_PRICE'] ? $product['PRODUCT_PRICE'] : 0;
        $pro_data['tax_class_id'] = 0;
        if ($product['PRODUCT_COST']) {
            $pro_data['cost'] = $product['PRODUCT_COST'];
        }
//        $pro_data['create_at'] = date('Y-m-d H:i:s', strtotime($product['lastmodified']));
//        $pro_data['visibility'] = ($product['hideproduct'] == 'Y') ? Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE : Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => 0,
            'use_config_manage_stock' => 0,
            'qty' => 0,
        );


//        if($product['listprice']){
//            $pro_data['msrp'] = $product['listprice'];
//        }
        if ($product['PRODUCT_IMAGE'] != '') {
            $img = $product['PRODUCT_IMAGE'];
        } else {
            $img = $product['PRODUCT_THUMBNAIL'];
        }

        if ($img) {
            $img_url = str_replace($this->_cart_url, '', $img);
            $img_url = str_replace(' ', '%20', $img_url);
            $img_path = $this->downloadImage($this->_cart_url, $img_url, 'catalog/product', false, true);
            if ($img_path) {
                $pro_data['image_import_path'] = array('path' => $img_path, 'label' => '');
            }
        }
        if ($this->_seo) {
            $seo = $this->_seo->convertProductSeo($this, $product);
            if ($seo) {
                $pro_data['seo_url'] = $seo;
            }
        }
        $custom = $this->_custom->convertProductCustom($this, $product);
        if ($custom) {
            $pro_data = array_merge($pro_data, $custom);
        }
        return array(
            'result' => 'success',
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
    public function importProduct($data, $product)
    {   
        if (LitExtension_CartImport_Model_Custom::PRODUCT_IMPORT) {
            return $this->_custom->importProductCustom($this, $data, $product);
        }
        $id_src = $this->getProductId($product);
        $productIpt = $this->_process->product($data);
        if ($productIpt['result'] == 'success') {
            $id_desc = $productIpt['mage_id'];
            $this->productSuccess($id_src, $id_desc, $product['PRODUCT_CODE']);
        } else {
            $productIpt['result'] = 'warning';
            $msg = "Product code = {$product['PRODUCT_CODE']} import failed. Error: " . $productIpt['msg'];
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
    public function afterSaveProduct($product_mage_id, $data, $product)
    {

        if (parent::afterSaveProduct($product_mage_id, $data, $product)) {
            return;
        }
        // import Custom Options
        $provide_table = $this->getTableName(self::MIVA_PROVIDE);
        $option_query = "SELECT * FROM {$provide_table} WHERE folder = '{$this->_folder}' AND product_code = '{$product['PRODUCT_CODE']}' AND attribute_code is null";
        $options_cat = $this->readQuery($option_query);
        if ($options_cat['result'] != 'success' || empty($options_cat['data'])) {
//            var_dump($options_cat);exit();
            return;
        }
        if ($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $option_data = array();
            $types = array(
                'swatch-select' => 'drop_down',
                'select' => 'drop_down',
                'checkbox' => 'checkbox',
                'radio' => 'radio',
                'text' => 'field',
                'memo' => 'area'
            );
            foreach ($options_cat['data'] as $option_cat) {
                $display_type = $option_cat['Type'];
                $is_required = ($option_cat['Required'] == 'Yes') ? 1 : 0;
                $opt_data = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($types[$display_type]),
                    'type' => $types[$display_type],
                    'is_require' => $is_required,
                    'title' => $option_cat['Prompt']
                );
                $option_child_query = "SELECT * FROM {$provide_table} WHERE folder = '{$this->_folder}' AND product_code = '{$product['PRODUCT_CODE']}' AND attribute_code = '{$option_cat['Code']}'";
                $option_child = $this->readQuery($option_child_query);

                if (in_array($display_type, array('swatch-select', 'select', 'radio'))) {
                    $opt_data['values'] = array();
                    if (count($option_child['data'])) {
                        foreach ($option_child['data'] as $opt_child) {
                            $value = array(
                                'option_type_id' => -1,
                                'title' => strip_tags($opt_child['Prompt']),
                                'price' => $opt_child['Price'],
                                'price_type' => 'fixed',
                            );
                            $opt_data['values'][] = $value;
                        }
                    }
                } elseif (in_array($display_type, array('checkbox', 'text', 'memo'))) {
                    $opt_data['values'] = array();
                    $value = array(
                        'option_type_id' => -1,
                        'title' => strip_tags($option_cat['Prompt']),
                        'price' => $option_cat['Price'],
                        'price_type' => 'fixed',
                    );
                    $opt_data['values'][] = $value;
                }

                $option_data[] = $opt_data;
            }
            if ($option_data) {
                $this->importProductOption($product_mage_id, $option_data);
            }
        }
        //end import Custom Options
    }

    /**
     * Get data of main table use for import customer
     */
    public function getCustomers()
    {
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $customer_table = $this->getTableName(self::MIVA_CUS);
        $query = "SELECT * FROM {$customer_table} WHERE folder = '{$this->_folder}' AND customerid > {$id_src} ORDER BY customerid ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
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
    public function getCustomerId($customer)
    {
        return $customer['customerid'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $customer : One row of object in function getCustomers
     * @return array
     */
    public function convertCustomer($customer)
    {
        if (LitExtension_CartImport_Model_Custom::CUSTOMER_CONVERT) {
            return $this->_custom->convertCustomerCustom($this, $customer);
        }
        $cus_data = array();
        if ($this->_notice['config']['add_option']['pre_cus']) {
            $cus_data['id'] = $customer['customerid'];
        }

        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['CUSTOMER_LOST_PASSWORD_EMAIL'];
        $cus_data['firstname'] = $customer['CUSTOMER_SHIPPING_FIRST_NAME'] ? $customer['CUSTOMER_SHIPPING_FIRST_NAME'] : " ";
        $cus_data['lastname'] = $customer['CUSTOMER_SHIPPING_LAST_NAME'] ? $customer['CUSTOMER_SHIPPING_LAST_NAME'] : " ";
        $cus_data['created_at'] = null;
//        $cus_data['is_subscribed'] = ($customer['emailsubscriber'] ==  'Y') ? 1 : 0;
        $cus_data['group_id'] = 1;
        $custom = $this->_custom->convertCustomerCustom($this, $customer);
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
     * @param array $customer : One row of object function getCustomers
     * @return boolean
     */
    public function afterSaveCustomer($customer_mage_id, $data, $customer)
    {
        if (parent::afterSaveCustomer($customer_mage_id, $data, $customer)) {
            return;
        }
        $pass = 'miva:' . base64_encode($customer['CUSTOMER_PASSWORD']);
        $this->_importCustomerRawPass($customer_mage_id, $pass);
        $address = array();
        $address['firstname'] = $customer['CUSTOMER_BILLING_FIRST_NAME'] ? $customer['CUSTOMER_BILLING_FIRST_NAME'] : " ";
        $address['lastname'] = $customer['CUSTOMER_BILLING_LAST_NAME'] ? $customer['CUSTOMER_BILLING_LAST_NAME'] : " ";
        if ($customer['CUSTOMER_BILLING_COUNTRY']) {
            $country_id = $customer['CUSTOMER_BILLING_COUNTRY'];
        } else if ($customer['CUSTOMER_SHIPPING_COUNTRY']) {
            $country_id = $customer['CUSTOMER_SHIPPING_COUNTRY'];
        } else {
            $country_id = 'US';
        }
        $address['country_id'] = $country_id;
        $address['street'] = $customer['CUSTOMER_BILLING_ADDRESS'] . "\n" . $customer['CUSTOMER_BILLING_ADDRESS2'];
        $address['postcode'] = $customer['CUSTOMER_BILLING_ZIP'];
        $address['city'] = $customer['CUSTOMER_BILLING_CITY'];
        $address['telephone'] = $customer['CUSTOMER_BILLING_PHONE'];
        $address['company'] = $customer['CUSTOMER_BILLING_COMPANY'];
        $address['fax'] = $customer['CUSTOMER_BILLING_FAX'];

        if ($customer['CUSTOMER_BILLING_STATE']) {
            if (strlen($customer['CUSTOMER_BILLING_STATE']) == 2) {
                $region_id = $this->getRegionIdByStateCode($customer['CUSTOMER_BILLING_STATE'], $customer['CUSTOMER_BILLING_COUNTRY']);
            }else{
                $region_id = $this->getRegionId($customer['CUSTOMER_BILLING_STATE'], $customer['CUSTOMER_BILLING_COUNTRY']);
            }
            if ($region_id) {
                $address['region_id'] = $region_id;
            }else{
                $address['region'] = $customer['CUSTOMER_BILLING_STATE'];
            }
        } else {
            $address['region_id'] = 0;
        }

        $address_ipt = $this->_process->address($address, $customer_mage_id);
        //Shipping address
        $address_shipping = array();
        $address_shipping['firstname'] = $customer['CUSTOMER_SHIPPING_FIRST_NAME'] ? $customer['CUSTOMER_SHIPPING_FIRST_NAME'] : " ";
        $address_shipping['lastname'] = $customer['CUSTOMER_SHIPPING_LAST_NAME'] ? $customer['CUSTOMER_SHIPPING_LAST_NAME'] : " ";

        $address_shipping['country_id'] = $customer['CUSTOMER_SHIPPING_COUNTRY'];
        $address_shipping['street'] = $customer['CUSTOMER_SHIPPING_ADDRESS'] . "\n" . $customer['CUSTOMER_SHIPPING_ADDRESS2'];
        $address_shipping['postcode'] = $customer['CUSTOMER_SHIPPING_ZIP'];
        $address_shipping['city'] = $customer['CUSTOMER_SHIPPING_CITY'];
        $address_shipping['telephone'] = $customer['CUSTOMER_SHIPPING_PHONE'];
        $address_shipping['company'] = $customer['CUSTOMER_SHIPPING_COMPANY'];
        $address_shipping['fax'] = $customer['CUSTOMER_SHIPPING_FAX'];

        if ($customer['CUSTOMER_SHIPPING_STATE']) {
            if (strlen($customer['CUSTOMER_SHIPPING_STATE']) == 2) {
                $region_id = $this->getRegionIdByStateCode($customer['CUSTOMER_SHIPPING_STATE'], $customer['CUSTOMER_SHIPPING_COUNTRY']);
            } else {
                $region_id = $this->getRegionId($customer['CUSTOMER_SHIPPING_STATE'], $customer['CUSTOMER_SHIPPING_COUNTRY']);
            }
            if ($region_id) {
                $address_shipping['region_id'] = $region_id;
            }else{
                $address_shipping['region'] = $customer['CUSTOMER_SHIPPING_STATE'];
            }
        } else {
            $address_shipping['region_id'] = 0;
        }

        $address_ipt_shipping = $this->_process->address($address_shipping, $customer_mage_id);
        $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
        if ($address_ipt['result'] == 'success') {
            try {
                $cus->setDefaultBilling($address_ipt['mage_id']);
                $cus->save();
            } catch (Exception $e) {
            }
        } else {
            return array(
                'result' => 'error',
                'msg' => $this->consoleWarning($customer_mage_id)
            );
        }
        if ($address_ipt_shipping['result'] == 'success') {
            try {
                $cus->setDefaultShipping($address_ipt_shipping['mage_id']);
                $cus->save();
            } catch (Exception $e) {
            }
        } else {
            return array(
                'result' => 'error',
                'msg' => $this->consoleWarning($customer_mage_id)
            );
        }

    }

    /**
     * Get data use for import order
     */
    public function getOrders()
    {
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $order_table = $this->getTableName(self::MIVA_ORD_ID);
        $query = "SELECT * FROM {$order_table} WHERE folder = '{$this->_folder}' AND ORDER_ID > {$id_src} ORDER BY ORDER_ID ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
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
    public function getOrderId($order)
    {
        return $order['ORDER_ID'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $order : One row of object in function getOrders
     * @return array
     */
    public function convertOrder($order)
    {
        if (LitExtension_CartImport_Model_Custom::ORDER_CONVERT) {
            return $this->_custom->convertOrderCustom($this, $order);
        }
//        $cus_table = $this->getTableName(self::MIVA_CUS);
//        $email = $order['BILL_EMAIL'] ? $order['BILL_EMAIL'] : $order['SHIP_EMAIL'];
//        $customers = $this->readQuery("SELECT * FROM {$cus_table} WHERE folder = '{$this->_folder}' AND CUSTOMER_LOST_PASSWORD_EMAIL = '{$email}' ");
//        if($customers['result'] != 'success' || empty($customers['data'])){
//            return array(
//                'result' => 'warning',
//                'msg' => $this->consoleWarning("Order id = {$order['ORDER_ID']} import failed. Error: Customer not import!")
//            );
//        }
        $ord_table = $this->getTableName(self::MIVA_ORD);
        $ordDtlSrc = $this->readQuery("SELECT * FROM {$ord_table} WHERE folder = '{$this->_folder}' AND ORDER_ID = {$order['ORDER_ID']} AND PROD_ATTR = ''");
        $ordDtl = ($ordDtlSrc['result'] == 'success') ? $ordDtlSrc['data'] : array();
        $order = $ordDtlSrc['data'][0];

        $data = $address_billing = $address_shipping = $carts = array();

        $address_billing['firstname'] = $order['BILL_FNAME'];
        $address_billing['lastname'] = $order['BILL_LNAME'];
        $address_billing['company'] = $order['BILL_COMP'];
        $address_billing['email'] = $order['BILL_EMAIL'];
        $bil_country_id = $order['BILL_CNTRY'] ? $order['BILL_CNTRY'] : 'US';
        $address_billing['country_id'] = $bil_country_id;
        $address_billing['street'] = $order['BILL_ADDR'] . "\n" . $order['BILL_ADDR2'];
        $address_billing['postcode'] = $order['BILL_ZIP'];
        $address_billing['city'] = $order['BILL_CITY'];
        $address_billing['telephone'] = $order['BILL_PHONE'];
        $address_billing['fax'] = $order['BILL_FAX'];
        if ($order['BILL_STATE']) {
            $bil_region_id = $this->getRegionIdByStateCode($order['BILL_STATE'], $bil_country_id);
            if ($bil_region_id) {
                $address_billing['region_id'] = $bil_region_id;
            }elseif($bil_region_id = $this->getRegionId($order['BILL_STATE'], $bil_country_id)){
                $address_billing['region_id'] = $bil_region_id;
            }
            $address_billing['region'] = $order['BILL_STATE'];
        } else {
            $address_billing['region_id'] = 0;
        }

        $address_shipping['firstname'] = $order['SHIP_FNAME'];
        $address_shipping['lastname'] = $order['SHIP_LNAME'];
        $address_shipping['company'] = $order['SHIP_COMP'];
        $address_shipping['email'] = $order['SHIP_EMAIL'];
        $ship_country_id = $order['SHIP_CNTRY'] ? $order['SHIP_CNTRY'] : 'US';
        $address_shipping['country_id'] = $ship_country_id;
        $address_shipping['street'] = $order['SHIP_ADDR'] . "\n" . $order['SHIP_ADDR2'];
        $address_shipping['postcode'] = $order['SHIP_ZIP'];
        $address_shipping['city'] = $order['SHIP_CITY'];
        $address_shipping['telephone'] = $order['SHIP_PHONE'];
        $address_shipping['fax'] = $order['SHIP_FAX'];
        if ($order['SHIP_STATE']) {
            $ship_region_id = $this->getRegionIdByStateCode($order['SHIP_STATE'], $ship_country_id);
            if ($ship_region_id) {
                $address_shipping['region_id'] = $ship_region_id;
            }elseif($bil_region_id = $this->getRegionId($order['SHIP_STATE'], $bil_country_id)){
                $address_shipping['region_id'] = $ship_region_id;
            }
            $address_shipping['region'] = $order['SHIP_STATE'];
        } else {
            $address_shipping['region_id'] = 0;
        }

        $sub_total = 0;
        $discount = array();
        if ($ordDtl) {
            $old_orderid_table = 0;
            foreach ($ordDtl as $order_detail) {
                $ord_table = $this->getTableName(self::MIVA_ORD);
                $ordDtlOption = $this->readQuery("SELECT * FROM {$ord_table} WHERE folder = '{$this->_folder}' AND ORDER_ID = {$order['ORDER_ID']} AND PROD_CODE = '{$order_detail['PROD_CODE']}'  AND PROD_ATTR != '' AND order_id_table < {$order_detail['order_id_table']} AND order_id_table > {$old_orderid_table}");
                $old_orderid_table = $order_detail['order_id_table'];
                $ordOptions = ($ordDtlOption['result'] == 'success') ? $ordDtlOption['data'] : array();
                $cart = array();
                $product_id = $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $order_detail['PROD_CODE']);
                if ($product_id) {
                    $cart['product_id'] = $product_id;
                }
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_detail['PROD_NAME'];
                $cart['sku'] = $order_detail['PROD_SKU'];
                $cart['price'] = $order_detail['PROD_PRICE'];
                $cart['original_price'] = $order_detail['PROD_PRICE'];
                $cart['qty_ordered'] = $order_detail['PROD_QUANT'];
                $custom_options_price = 0;
                if ($ordOptions) {

                    $product_opt = array();
                    foreach ($ordOptions as $key => $ordOption) {
                        $custom_options_price += $ordOption['OPT_PRICE'];
                        $opt_data = array(
                            'label' => isset($ordOption['PROD_ATTR']) ? $ordOption['PROD_ATTR'] : " ",
                            'value' => isset($ordOption['PROD_OPT']) ? $ordOption['PROD_OPT'] : " ",
                            'print_value' => isset($ordOption['PROD_OPT']) ? $ordOption['PROD_OPT'] : " ",
                            'option_id' => 'option_' . $key,
                            'option_type' => 'drop_down',
                            'option_value' => 0,
                            'custom_view' => false
                        );
                        $product_opt[] = $opt_data;
                    }

                    $cart['product_options'] = serialize(array('options' => $product_opt));
                } else {
                    $order_detail['PROD_PRICE'] * $order_detail['PROD_QUANT'];
                }
                $sub_total += $cart['row_total'] = ($custom_options_price + $order_detail['PROD_PRICE']) * $order_detail['PROD_QUANT'];
                $carts[] = $cart;
            }
        }

        $customer = mage::getModel('customer/customer')->getCollection()
            ->addAttributeToFilter('email', array('in' => array($order['SHIP_EMAIL'], $order['BILL_EMAIL'])))
            ->getFirstItem();
        $customer_id = $customer->getId();
        $customer = mage::getModel('customer/customer')->load($customer_id);
//        $order_status_id_config = $this->getArrayValueByValueArray($order['orderstatus'], $this->_notice['config']['order_status_data'], $this->_notice['config']['order_status']);
        $order_status_id = 'complete';
        $tax_amount = $order['ORDER_TAX'];
        $discount_amount = 0;
        $store_id = $this->_notice['config']['languages'][1];
        $store_currency = $this->getStoreCurrencyCode($store_id);
        $ship_amount = $order['ORDER_SHIP'];
//        $sub_total = $order['paymentamount'] - $tax_amount + $discount_amount - $ship_amount;

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $customer->getEmail();
        $order_data['customer_firstname'] = $customer->getFirstname();
        $order_data['customer_lastname'] = $customer->getLastname();
        $order_data['customer_group_id'] = 1;
        $order_data['status'] = $order_status_id;
        $order_data['state'] = $this->getOrderStateByStatus($order_status_id);
        $order_data['subtotal'] = $this->incrementPriceToImport($sub_total);
        $order_data['base_subtotal'] = $sub_total;
        $order_data['shipping_amount'] = $ship_amount;
        $order_data['base_shipping_amount'] = $ship_amount;
        $order_data['base_shipping_invoiced'] = $ship_amount;
        $order_data['shipping_description'] = "Shipping";
        $order_data['tax_amount'] = $tax_amount;
        $order_data['base_tax_amount'] = $tax_amount;
//        $order_data['discount_amount'] = $discount_amount;
//        $order_data['base_discount_amount'] = $discount_amount;
        $order_data['grand_total'] = $this->incrementPriceToImport($order['ORDER_TOTL']);
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
        $order_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['ORDER_DATE'] . ' ' . $order['ORDER_TIME']));

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['ORDER_ID'];
        $custom = $this->_custom->convertOrderCustom($this, $order);
        if ($custom) {
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
    public function afterSaveOrder($order_mage_id, $data, $order)
    {

    }

    /**
     * Get main data use for import review
     */
    public function getReviews()
    {

    }

    /**
     * Get primary key of source review main
     *
     * @param array $review : One row of object in function getReviews
     * @return int
     */
    public function getReviewId($review)
    {

    }

    /**
     * Convert source data to data import
     *
     * @param array $review : One row of object in function getReviews
     * @return array
     */
    public function convertReview($review)
    {

    }

    /**
     * TODO: STORAGE DATA
     */

    public function _setupStorage()
    {
        $setup = true;
        $tableDrop = $this->getListTableDrop();
        foreach ($tableDrop as $table_drop) {
            $this->dropTable($table_drop);
        }
        $tables = $queries = array();
        $creates = array(
            'categoriesTableConstruct',
            'productsTableConstruct',
            'customersTableConstruct',
            'ordersTableConstruct',
            'orderidsTableConstruct',
            'provideTableConstruct'
        );
        foreach ($creates as $create) {
            $tables[] = $this->$create();
        }
        foreach ($tables as $table) {
            $table_query = $this->arrayToCreateSql($table);
            if ($table_query['result'] != 'success') {
                $table_query['msg'] = $this->consoleError($table_query['msg']);
                return $table_query;
            }
            $queries[] = $table_query['query'];
        }
        foreach ($queries as $query) {
            if (!$this->writeQuery($query)) {
                $setup = false;
            }
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_clearStorageCsv';
        $this->_notice['csv_import']['msg'] = "";
        return $this->_notice['csv_import'];
    }

    public function getListTableDrop()
    {
        $tables = $this->_getTablesTmp();
        $custom = $this->_custom->getListTableDropCustom($tables);
        $result = $custom ? $custom : $tables;
        return $result;
    }

    /**
     * Construct of table categories
     */
    public function categoriesTableConstruct()
    {
        return array(
            'table' => self::MIVA_CAT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'categoryid' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'CATEGORY_CODE' => 'VARCHAR(255)',
                'CATEGORY_NAME' => 'text',
                'CATEGORY_PAGE_TITLE' => 'text',
                'CATEGORY_ACTIVE' => 'INT(11)',
                'CATEGORY_PARENT_CODE' => 'VARCHAR(255)',
                'ALTERNATE_DISPLAY_PAGE' => 'text',
                'CATEGORY_CANONICAL_URI' => 'text',
                'footer' => 'text',
                'header' => 'text',
                'category_title_image' => 'text',
                'category_tree_image' => 'text',
                'description' => 'text',
                'keywords' => 'text',
            ),
            'validation' => array('CATEGORY_CODE')
        );
    }

    /**
     * Construct of table products
     */
    public function productsTableConstruct()
    {
        return array(
            'table' => self::MIVA_PRO,
            'rows' => array(
                'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'PRODUCT_CODE' => 'VARCHAR(255)',
                'PRODUCT_SKU' => 'VARCHAR(255)',
                'PRODUCT_NAME' => 'TEXT',
                'CATEGORY_CODES' => 'VARCHAR(255)',
                'CANONICAL_CATEGORY_CODE' => 'VARCHAR(255)',
                'ALTERNATE_DISPLAY_PAGE' => 'TEXT',
                'PRODUCT_PRICE' => 'VARCHAR(255)',
                'PRODUCT_COST' => 'VARCHAR(255)',
                'PRODUCT_WEIGHT' => 'VARCHAR(255)',
                'PRODUCT_DESC' => 'TEXT',
                'PRODUCT_TAXABLE' => 'VARCHAR(255)',
                'PRODUCT_ACTIVE' => 'VARCHAR(255)',
                'PRODUCT_THUMBNAIL' => 'VARCHAR(255)',
                'PRODUCT_IMAGE' => 'VARCHAR(255)',
                'PRODUCT_PAGE_TITLE' => 'VARCHAR(255)',
                'CANONICAL_URI' => 'VARCHAR(255)',
                'SHIPPING_WIDTH' => 'VARCHAR(255)',
                'SHIPPING_LENGTH' => 'VARCHAR(255)',
                'SHIPPING_HEIGHT' => 'VARCHAR(255)',
                'SEPARATE_PACKAGE' => 'VARCHAR(255)',
                'LIMIT_SHIPPING_METHODS' => 'VARCHAR(255)',
                'SHIPPING_METHODS' => 'VARCHAR(255)',
                'description' => 'VARCHAR(255)',
                'keywords' => 'VARCHAR(255)',
                'options' => 'VARCHAR(255)',
                'footer' => 'TEXT',
                'header' => 'TEXT',
                'product_type' => 'VARCHAR(255)',
                'short_description' => 'TEXT',
                'specs' => 'VARCHAR(255)'
            ),
            'validation' => array('PRODUCT_CODE')
        );
    }

    /**
     * Construct of table customer
     */
    public function customersTableConstruct()
    {
        return array(
            'table' => self::MIVA_CUS,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'customerid' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'CUSTOMER_LOGIN' => 'VARCHAR(255)',
                'CUSTOMER_PASSWORD' => 'VARCHAR(255)',
                'CUSTOMER_LOST_PASSWORD_EMAIL' => 'VARCHAR(255)',
                'CUSTOMER_BUSINESS_ACCOUNT' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_FIRST_NAME' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_LAST_NAME' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_ADDRESS' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_ADDRESS2' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_CITY' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_STATE' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_ZIP' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_COUNTRY' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_EMAIL' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_PHONE' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_FAX' => 'VARCHAR(255)',
                'CUSTOMER_SHIPPING_COMPANY' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_FIRST_NAME' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_LAST_NAME' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_ADDRESS' => 'text',
                'CUSTOMER_BILLING_ADDRESS2' => 'text',
                'CUSTOMER_BILLING_CITY' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_STATE' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_ZIP' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_COUNTRY' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_EMAIL' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_PHONE' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_FAX' => 'VARCHAR(255)',
                'CUSTOMER_BILLING_COMPANY' => 'VARCHAR(255)'
            ),
            'validation' => array('CUSTOMER_LOGIN')
        );
    }

    /**
     * Construct of table provide
     */
    public function provideTableConstruct()
    {
        return array(
            'table' => self::MIVA_PROVIDE,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'product_code' => 'VARCHAR(255)',
                'attribute_code' => 'VARCHAR(255)',
                'Code' => 'VARCHAR(255)',
                'Type' => 'VARCHAR(255)',
                'Prompt' => 'VARCHAR(255)',
                'Image' => 'VARCHAR(255)',
                'Price' => 'VARCHAR(255)',
                'Cost' => 'VARCHAR(255)',
                'Weight' => 'VARCHAR(255)',
                'Required' => 'VARCHAR(255)',
                'Inventory' => 'VARCHAR(255)'
            ),
            'validation' => array('product_code')
        );
    }

    /**
     * Construct of table order
     */
    public function ordersTableConstruct()
    {
        return array(
            'table' => self::MIVA_ORD,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'order_id_table' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'ORDER_ID' => 'BIGINT',
                'PROCESSED' => 'VARCHAR(255)',
                'ORDER_DATE' => 'VARCHAR(255)',
                'ORDER_TIME' => 'VARCHAR(255)',
                'SHIP_FNAME' => 'VARCHAR(255)',
                'SHIP_LNAME' => 'VARCHAR(255)',
                'SHIP_EMAIL' => 'VARCHAR(255)',
                'SHIP_COMP' => 'VARCHAR(255)',
                'SHIP_PHONE' => 'VARCHAR(255)',
                'SHIP_FAX' => 'VARCHAR(255)',
                'SHIP_ADDR' => 'VARCHAR(255)',
                'SHIP_ADDR2' => 'VARCHAR(255)',
                'SHIP_CITY' => 'VARCHAR(255)',
                'SHIP_STATE' => 'VARCHAR(255)',
                'SHIP_ZIP' => 'VARCHAR(255)',
                'SHIP_CNTRY' => 'VARCHAR(255)',
                'BILL_FNAME' => 'VARCHAR(255)',
                'BILL_LNAME' => 'VARCHAR(255)',
                'BILL_EMAIL' => 'VARCHAR(255)',
                'BILL_COMP' => 'VARCHAR(255)',
                'BILL_PHONE' => 'VARCHAR(255)',
                'BILL_FAX' => 'VARCHAR(255)',
                'BILL_ADDR' => 'VARCHAR(255)',
                'BILL_ADDR2' => 'VARCHAR(255)',
                'BILL_CITY' => 'VARCHAR(255)',
                'BILL_STATE' => 'VARCHAR(255)',
                'BILL_ZIP' => 'VARCHAR(255)',
                'BILL_CNTRY' => 'VARCHAR(255)',
                'PROD_CODE' => 'VARCHAR(255)',
                'PROD_SKU' => 'VARCHAR(255)',
                'PROD_NAME' => 'VARCHAR(255)',
                'PROD_UPSLD' => 'VARCHAR(255)',
                'PROD_PRICE' => 'VARCHAR(255)',
                'PROD_QUANT' => 'VARCHAR(255)',
                'PROD_ATTR' => 'VARCHAR(255)',
                'PROD_OPT' => 'VARCHAR(255)',
                'OPT_PRICE' => 'VARCHAR(255)',
                'ORDER_TAX' => 'VARCHAR(255)',
                'ORDER_SHIP' => 'VARCHAR(255)',
                'SHIP_METHOD' => 'VARCHAR(255)',
                'ORDER_TOTL' => 'VARCHAR(255)'
            ),
            'validation' => array('ORDER_ID')
        );
    }

    /**
     * Construct of table order
     */
    public function orderidsTableConstruct()
    {
        return array(
            'table' => self::MIVA_ORD_ID,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'ORDER_ID' => 'BIGINT',
            ),
            'validation' => array('ORDER_ID')
        );
    }

    protected function _clearStorageCsv()
    {
        $tables = $this->_getTablesTmp();
        $folder = $this->_folder;
        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
            $this->writeQuery($query);
        }
        $this->_notice['csv_import']['function'] = '_storageCsvCategories';
        return array(
            'result' => 'process',
            'msg' => ''
        );
    }

    /**
     * Storage Dat categories to database
     */
    protected function _storageCsvCategories()
    {
        return $this->_storageDatByType('categories', 'customers');
    }

    /**
     * Storage Dat customers to database
     */
    protected function _storageCsvCustomers()
    {
        return $this->_storageDatByType('customers', 'products');
    }

    /**
     * Storage Dat products to database
     */
    protected function _storageCsvProducts()
    {
        return $this->_storageDatByType('products', 'provide', false, false, array('id'));
    }

    /**
     * Storage Dat products attributes to database
     */
    protected function _storageCsvProvide()
    {
        return $this->_storageDatByType('provide', 'orders', false, false);
    }

    /**
     * Storage Dat orders to database
     */
    protected function _storageCsvOrders()
    {
        return $this->_storageDatByType('orders', 'orderids', false, true);
    }

    /**
     * TODO : Extend function
     */
    protected function _getTablesTmp()
    {
        return array(
            self::MIVA_CAT,
            self::MIVA_PRO,
            self::MIVA_CUS,
            self::MIVA_ORD,
            self::MIVA_ORD_ID
        );
    }

    /**
     * Get id_desc by type and value
     */
    protected function _getLeCaIpImportIdDescByValue($type, $value)
    {
        $table_name = $this->getTableName(self::TABLE_IMPORT);
        $result = $this->selectTableRow($table_name, array(
            'domain' => $this->_cart_url,
            'type' => $type,
            'value' => $value
        ));
        if (!$result) {
            return false;
        }
        return (isset($result['id_desc'])) ? $result['id_desc'] : false;
    }

//    protected function _checkProductHasChild($product){
//        $product_table = $this->getTableName(self::MIVA_PRO);
//        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND ischildofproductcode = '{$product['PRODUCT_CODE']}'";
//        $result = $this->readQuery($query);
//        if($result['result'] != 'success'){
//            return false;
//        }
//        if(!empty($result['data'])){
//            return true;
//        }
//        return false;
//    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($category_id)
    {
        $category_table = $this->getTableName(self::MIVA_CAT);
        $query = "SELECT * FROM {$category_table} WHERE folder = '{$this->_folder}' AND categoryid = '{$category_id}'";

        $categories = $this->readQuery($query);

        if ($categories['result'] != 'success') {
            return $this->errorDatabase(true);
        }
        $category = $categories['data'][0];
        $convert = $this->convertCategory($category);
        if ($convert['result'] != 'success') {
            return array(
                'result' => 'warning',
            );
        }

        $data = $convert['data'];
        $category_ipt = $this->_process->category($data);
        if ($category_ipt['result'] == 'success') {
            $this->categorySuccess($category_id, $category_ipt['mage_id'],$category['CATEGORY_CODE']);
            $this->afterSaveCategory($category_ipt['mage_id'], $data, $category);
        } else {
            $category_ipt['result'] = 'warning';
        }
        return $category_ipt;
    }

    protected function _storageDatByType($type, $next, $success = false, $finish = false, $unset = array())
    {
        if (!$success) {
            $success = $next;
        }
        if (!$this->_notice['config']['files'][$type]) {
            if ($finish) {
                $this->_notice['csv_import']['result'] = 'success';
            } else {
                $this->_notice['csv_import']['result'] = 'process';
            }
            $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($next);
            $this->_notice['csv_import']['msg'] = '';
            $this->_notice['csv_import']['count'] = 0;
            return $this->_notice['csv_import'];
        }
        $start = $this->_notice['csv_import']['count'];
        $demo = $this->_limitDemoModel($type);
        if ($type == 'provide') {
            $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.xml';
        } else {
            $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.dat';
        }

        $readCsv = $this->readDat($_file, $start, $this->_notice['setting']['csv'], $demo);
        if ($readCsv['result'] != 'success') {
            $readCsv['msg'] = $this->consoleError($readCsv['msg']);
            return $readCsv;
        }
        $allowData = array();
        $fn_construct = $type . 'TableConstruct';
        if ($type == 'orders') {
            $table_orderids = 'orderidsTableConstruct';
        }
        $table = $this->$fn_construct();
        if (LitExtension_CartImport_Model_Custom::CSV_IMPORT) {
            $allowData = $this->_custom->storageCsvCustom($this);
        }
        $validation = false;
        if (!$allowData) {
            $rows = $table['rows'];
            $validation = isset($table['validation']) ? $table['validation'] : false;
            if ($unset) {
                $rows = $this->unsetListArray($unset, $rows);
            }
            $allowData = array_keys($rows);
            $custom_allow = $this->_custom->storageCsvCustom($this);
            if ($custom_allow) {
                $allowData = array_merge($allowData, $custom_allow);
            }
        }
        foreach ($readCsv['data'] as $item) {
            $data = $this->syncCsvTitleRow($item['title'], $item['row']);
            if (!empty($data)) {
                if ($validation) {
                    foreach ($validation as $column_name) {
                        if (!isset($data[$column_name]) || !$data[$column_name]) {
                            continue 2;
                        }
                    }
                }
                $data = $this->addConfigToArray($data);
                $insert = $this->insertTable($table['table'], $data, $allowData);
                if ($type == 'orders') {
                    $query = "SELECT * FROM `{$this->getTableName('lecaip_miva_order_id')}` WHERE folder = '{$this->_folder}' AND ORDER_ID = '{$data['ORDER_ID']}'";

                    $result = $this->readQuery($query);
                    if ($result['result'] != 'success' || empty($result['data'])) {
                        $this->insertTable(self::MIVA_ORD_ID, $data, array('folder', 'domain', 'ORDER_ID'));
                    }

                }
                if (!$insert) {
                    return array(
                        'result' => 'error',
                        'msg' => $this->consoleError('Could not import data to database.')
                    );
                }
            }
        }
        if ($readCsv['finish']) {
            if ($finish) {
                $this->_notice['csv_import']['result'] = 'success';
            } else {
                $this->_notice['csv_import']['result'] = 'process';
            }
            $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($success);
            $this->_notice['csv_import']['msg'] = $this->consoleSuccess("Finish importing " . $type);
            $this->_notice['csv_import']['count'] = 0;
            return $this->_notice['csv_import'];
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($type);
        $this->_notice['csv_import']['msg'] = '';
        $this->_notice['csv_import']['count'] = $readCsv['count'];
        return $this->_notice['csv_import'];
    }

    /**
     * TODO : import data
     */

    /**
     * Import data from dat, xml to database
     */
    public function readDat($file_path, $start, $limit = 10, $total = false, $type = '')
    {

        if (!is_file($file_path)) {
            return array(
                'result' => 'error',
                'msg' => 'Path not exists'
            );
        }
        try {

            $finish = false;
            $count = 0;
            ini_set('auto_detect_line_endings', true);
            $data = array();
            $path_info = pathinfo($file_path);

            if ($path_info['extension'] == 'dat') {
                $dat = fopen($file_path, 'r');
                $end = $start + $limit;
                $csv_title = "";

                while (!feof($dat)) {
                    if ($total && $count > $total) {
                        $finish = true;
                        break;
                    }
                    if ($count > $end) {
                        break;
                    }
                    $line = fgetcsv($dat, 0, "\t");
                    //                $line = explode("\t",$line_tmp);

                    if ($count == 0) {
                        $csv_title = $line;
                    }
                    if ($start < $count && $count <= $end) {
                        $data[] = array(
                            'title' => $csv_title,
                            'row' => $line
                        );
                    }
                    $count++;
                }


                if (!$finish && ($count - 1) < $end) {
                    $finish = true;
                }
                return array(
                    'result' => 'success',
                    'data' => $data,
                    'count' => $end,
                    'finish' => $finish
                );

                fclose($dat);
            } elseif ($path_info['extension'] == 'xml') {
                $data_file = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', file_get_contents($file_path));
                $result = utf8_encode($data_file);
                $new_content = '<ProductAttribute>' . $result . '</ProductAttribute>';
                                
                $xml = simplexml_load_string($new_content, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
                $title = array('product_code', 'attribute_code', 'Code', 'Type', 'Prompt', 'Image', 'Price', 'Cost', 'Weight', 'Required', 'Inventory');
//                $data[] = array(
//                    'title' => $title,
//                    'row' => $title
//                );
                foreach ($xml->ProductAttribute_Add as $attr) {
                    $line = array();
                    $line[] = $attr['product_code'];
                    $line[] = @$attr['attribute_code'];
                    $line[] = $attr->Code;
                    $line[] = $attr->Type;
                    $line[] = $attr->Prompt;
                    $line[] = $attr->Image;
                    $line[] = $attr->Price;
                    $line[] = $attr->Cost;
                    $line[] = $attr->Weight;
                    $line[] = $attr->Required;
                    $line[] = $attr->Inventory;
                    $data[] = array(
                        'title' => $title,
                        'row' => $line,

                    );
                    $count++;
                }
                foreach ($xml->ProductAttributeOption_Add as $attr_option) {
                    $line = array();
                    $line[] = $attr_option['product_code'];
                    $line[] = $attr_option['attribute_code'];
                    $line[] = $attr_option->Code;
                    $line[] = @$attr_option->Type;
                    $line[] = $attr_option->Prompt;
                    $line[] = $attr_option->Image;
                    $line[] = $attr_option->Price;
                    $line[] = $attr_option->Cost;
                    $line[] = $attr_option->Weight;
                    $line[] = @$attr_option->Required;
                    $line[] = @$attr_option->Inventory;
                    $data[] = array(
                        'title' => $title,
                        'row' => $line,

                    );
                    $count++;
                }

                return array(
                    'result' => 'success',
                    'data' => $data,
                    'count' => $count,
                    'finish' => true
                );
            }

        } catch (Exception $e) {
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }

    /**
     * Get region id by name state and country iso code 2
     */
    public function getRegionIdByStateCode($state_code , $country){
        $result = null;
        $regions = Mage::getModel('directory/region')
            ->getCollection()
            ->addFieldToFilter('code', $state_code)
            ->addFieldToFilter('country_id', $country)
            ->getFirstItem();
        if($regions->getId()){
            $result = $regions->getId();
        } else{
            $result = 0;
        }
        return $result;
    }
}