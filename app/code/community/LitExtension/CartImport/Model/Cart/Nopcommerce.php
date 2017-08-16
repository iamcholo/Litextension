<?php

class LitExtension_CartImport_Model_Cart_Nopcommerce
    extends LitExtension_CartImport_Model_Cart
{

    const NOP_MANU = 'lecaip_nopcommerce_manufacturer';
    const NOP_CAT = 'lecaip_nopcommerce_category';
    const NOP_PRD = 'lecaip_nopcommerce_product';
    const NOP_IMG = 'lecaip_nopcommerce_product_image';
    const NOP_CUS = 'lecaip_nopcommerce_customer';
    const NOP_ORD = 'lecaip_nopcommerce_order';
    const TABLE_IMPORTS = 'lecaip/import';
    const TYPE_MAN_ATTR = 'man_attr';


    protected $_demo_limit = array(
        'manufacturers' => 10,
        'categories' => 10,
        'products' => 10,
        'customers' => 10,
        'orders' => 10,
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
        $guide = '<p>Please export your Nopcommerce data to XML file and upload it in fields below.</p>
                    <strong>Proceed with the following steps for required data tables:</strong>
                    <ol>
                    <li>Sign in to your Nopcommerce admin account.</li>';

        $manu_guide = '<li>Go to <b>Catalog -&gt; Manufacturers</b> and click on <b>"Export to XML"</b> button.</li>';

        $cate_guide = '<li>Go to <b>Catalog -&gt; Categories -&gt; List</b> and click on <b>"Export to XML"</b> button.</li>';

        $pro_guide = '<li>Go to <b>Catalog -&gt; Products -&gt; Manage Products</b> and:<br>
        - Click on <b>"Export to XML(all found)"</b> button to export all products.<br>
        - Check the product IDs which you want to export and then click on <b>"Export to XML(selected)"</b> button to export selected products.
                    </li>';
        $img_guide = '<li>Go to <b>Catalog -&gt; Products -&gt; Manage Products</b> and:<br>
        - Click on <b>"Export to Excel(all found)"</b> button to export all products.<br>
        - Check the product IDs which you want to export and then click on <b>"Export to Excel(selected)"</b> button to export selected products.<br>
        - Save file excel as .csv.
                    </li>';

        $cus_guide = '<li>Go to <b>Customers -&gt; Customers -&gt;</b> and:<br>
        -Click on <b>"Export to XML(all found)"</b> button to export all customers.<br>
        -Check the customer IDs which you want to export and then click on <b>"Export to XML(selected)"</b> button to export selected customers.
                    </li>';

        $ord_guide = '<li>Go to <b>Sales -&gt; Orders </b> and:<br>
        - Click on <b>"Export to XML(all found)"</b> button to export all orders.<br>
        - Check the order IDs which you want to export and then click on <b>"Export to XML(selected)"</b> button to export selected orders.
        </li>
        <li>Upload saved file in the corresponding field below.</li>
        <li>Please note if the size of downloaded XML file exceeds <?php echo ini_get("upload_max_filesize"); ?>B limit (<a href="https://www.google.com/search?q=change+php+upload+file+size" target="_blank">How to change this limit</a>)</li>
        </ol>';

        $upload = array(
            array('value' => 'guide', 'label' => $guide),
            array('value' => 'manufacturers', 'label' => "Manufacturers"),
            array('value' => 'guide', 'label' => $manu_guide),
            array('value' => 'categories', 'label' => "Categories"),
            array('value' => 'guide', 'label' => $cate_guide),
            array('value' => 'products', 'label' => "Products"),
            array('value' => 'guide', 'label' => $pro_guide),
            array('value' => 'images', 'label' => "Product Images"),
            array('value' => 'guide', 'label' => $img_guide),
            array('value' => 'customers', 'label' => "Customers"),
            array('value' => 'guide', 'label' => $cus_guide),
            array('value' => 'orders', 'label' => "Orders"),
            array('value' => 'guide', 'label' => $ord_guide)
        );
        return $upload;
    }

    public function clearPreSection()
    {
        $tables = array(
            self::NOP_MANU,
            self::NOP_CAT,
            self::NOP_PRD,
            self::NOP_IMG,
            self::NOP_CUS,
            self::NOP_ORD,
        );
        $folder = $this->_folder;
        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
            $this->writeQuery($query);
        }
        return;
    }


    public function getAllowExtensions()
    {
        return array('xml', 'csv');
    }

    public function getUploadFileName($upload_name)
    {
        $name = '';
        if ($upload_name != 'images') {
            $name = $upload_name . '.xml';
        } else {
            $name = $upload_name . '.csv';
        }
        return $name;
    }

    public function getUploadInfo($up_msg)
    {
        $files = array_filter($this->_notice['config']['files']);
        if (!empty($files)) {

            $this->_notice['config']['import_support']['reviews'] = false;
            $this->_notice['config']['config_support']['currency_map'] = false;
            $this->_notice['config']['import_support']['taxes'] = false;
            $this->_notice['config']['config_support']['country_map'] = false;
            $this->_notice['config']['config_support']['order_status_map'] = false;

            if (!$this->_notice['config']['files']['manufacturers']) {
                $this->_notice['config']['import_support']['manufacturers'] = false;
            }

            if (!$this->_notice['config']['files']['categories']) {
                $this->_notice['config']['config_support']['category_map'] = false;
                $this->_notice['config']['import_support']['categories'] = false;
            }

            if (!$this->_notice['config']['files']['products']) {
                $this->_notice['config']['config_support']['attribute_map'] = false;
                $this->_notice['config']['import_support']['products'] = false;
                $this->_notice['config']['import_support']['orders'] = false;
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

            if (!$this->_notice['config']['files']['customers'] && !$this->_notice['config']['files']['orders']) {
                $this->_notice['config']['config_support']['country_map'] = false;
            }

            foreach ($files as $type => $upload) {
                if ($upload) {
                    $func_construct = $type . "TableConstruct";
                    $construct = $this->$func_construct();
                    $validate = isset($construct['validation']) ? $construct['validation'] : false;
                    $folder_upload = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'];
                    if ($type != 'images') {
                        $_file = $folder_upload . '/' . $type . '.xml';
                        $readFile = $this->readXml2($_file, 0, 1, false, $type);
                    } else {
                        $_file = $folder_upload . '/' . $type . '.csv';
                        $readFile = $this->readXml2($_file, 0, 1, false, $type);
                    }
                    if ($readFile['result'] == 'success') {
                        foreach ($readFile['data'] as $item) {
                            if ($validate) {
                                foreach ($validate as $row) {
                                    if (!in_array($row, array_keys($item))) {
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
            if (isset($files['orders']) && !isset($files['customers'])) {
                $up_msg['orders'] = array(
                    'elm' => '#ur-orders',
                    'msg' => "<div class='uir-warning'> Customer not uploaded.</div>"
                );
            }
            if (isset($files['orders']) && !isset($files['products'])) {
                $up_msg['orders'] = array(
                    'elm' => '#ur-orders',
                    'msg' => "<div class='uir-warning'> Products not uploaded.</div>"
                );
            }
            $this->_notice['csv_import']['function'] = '_setupStorageCsv';
        }
        return array(
            'result' => 'success',
            'msg' => $up_msg
        );
    }


    public function displayConfig()
    {
        $parent = parent::displayConfig();
        if ($parent['result'] != "success") {
            return $parent;
        }
        $response = array();
        $attribute_data = array(1 => 'Default');
        $category_data = array("Root category");
        $languages_data = array(1 => "Default language");
        $order_status_data = array(
            'Pending',
            'Processing',
            'On Hold',
            'Completed',
            'Cancelled',
            'Refunded',
            'Failed'
        );
        $this->_notice['config']['config_support']['customer_group_map'] = false;
        $this->_notice['config']['default_lang'] = 1;
        $this->_notice['config']['category_data'] = $category_data;
        $this->_notice['config']['attribute_data'] = $attribute_data;
        $this->_notice['config']['languages_data'] = $languages_data;
        $this->_notice['config']['order_status_data'] = $order_status_data;
        $response['result'] = 'success';
        return $response;
    }

    //code 1234
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
        $manufacturer_table = $this->getTableName(self::NOP_MANU);
        $category_table = $this->getTableName(self::NOP_CAT);
        $product_table = $this->getTableName(self::NOP_PRD);
        $customer_table = $this->getTableName(self::NOP_CUS);
        $order_table = $this->getTableName(self::NOP_ORD);
        $queries = array(
            'manufacturers' => "SELECT COUNT(1) AS count FROM {$manufacturer_table} WHERE folder = '{$this->_folder}'",
            'categories' => "SELECT COUNT(1) AS count FROM {$category_table} WHERE folder = '{$this->_folder}'",
            'products' => "SELECT COUNT(1) AS count FROM {$product_table} WHERE folder = '{$this->_folder}'",
            'customers' => "SELECT COUNT(1) AS count FROM {$customer_table} WHERE folder = '{$this->_folder}'",
            'orders' => "SELECT COUNT(1) AS count FROM {$order_table} WHERE folder = '{$this->_folder}'"
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
        if (LitExtension_CartImport_Model_Custom::CLEAR_IMPORT) {
            $del = $this->deleteTable(self::TABLE_IMPORT, array(
                'folder' => $this->_folder
            ));
            if (!$del) {
                return $this->errorDatabase();
            }
        }
        return array(
            'result' => 'success'
        );
    }

    public function storageCsv()
    {
        if (LitExtension_CartImport_Model_Custom::CSV_STORAGE) {
            return $this->_custom->storageCsvCustom($this);
        }
        $function = $this->_notice['csv_import']['function'];
        if (!$function) {
            return array(
                'result' => 'success',
                'msg' => ''
            );
        }
        return $this->$function();
    }

    public function _setupStorageCsv()
    {
        $custom_setup = $this->_custom->storageCsvCustom($this);
        if ($custom_setup && $custom_setup['result'] == 'error') {
            return $custom_setup;
        }
        $setup = true;
        $tables = $queries = array();
        $creates = array(
            'manufacturersTableConstruct',
            'categoriesTableConstruct',
            'productsTableConstruct',
            'customersTableConstruct',
            'ordersTableConstruct',
            'imagesTableConstruct',
        );
        foreach ($creates as $create) {
            $tables[] = $this->$create();
        }
        foreach ($tables as $table) {
            $table_query = $this->arrayToCreateSql($table);
            if ($table_query['result'] != 'success') {
                $table_query['msg'] = $this->consoleMsgError($table_query['msg']);
                return $table_query;
            }
            $queries[] = $table_query['query'];
        }
        foreach ($queries as $query) {
            if (!$this->writeQuery($query)) {
                $setup = false;
            }
        }
        if ($setup) {
            //
        } else {
            return array(
                'result' => 'error',
                'msg' => $this->consoleMsgError("Could not created table to storage data.")
            );
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_clearStorageCsv';
        $this->_notice['csv_import']['msg'] = "";
        return $this->_notice['csv_import'];
    }

    public function prepareImportManufacturers()
    {
        parent::prepareImportManufacturers();
    }

    public function getManufacturers()
    {
        $id_src = $this->_notice['manufacturers']['id_src'];
        $limit = $this->_notice['setting']['manufacturers'];
        $manu_table = $this->getTableName(self::NOP_MANU);
        $query = "SELECT * FROM {$manu_table} WHERE folder = '{$this->_folder}' AND ManufacturerId > {$id_src} ORDER BY ManufacturerId ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
            return $this->errorDatabase(true);
        }
        return $result;
    }

    /**
     * Get primary key of source manufacturer
     *
     * @param array $manufacturer : One row of object in function getManufacturers
     * @return int
     */
    public function getManufacturerId($manufacturer)
    {
        return $manufacturer['ManufacturerId'];
    }

    /**
     * Convert source data to data import
     *
     * @param array $manufacturer : One row of object in function getManufacturers
     * @return array
     */
    public function convertManufacturer($manufacturer)
    {
        if (LitExtension_CartImport_Model_Custom::MANUFACTURER_CONVERT) {
            return $this->_custom->convertManufacturerCustom($this, $manufacturer);
        }
        $query = "SELECT attribute_id FROM " . $this->getTableName('eav_attribute') . " WHERE attribute_code = 'manufacturer'";
        $data = $this->readQuery($query);

        $manu_data = array(
            'attribute_id' => $data['data']['0']['attribute_id']
        );
        $manu_data['value']['option'] = array(
            0 => $manufacturer['Name'],
            1 => $manufacturer['Name']
        );

        $custom = $this->_custom->convertManufacturerCustom($this, $manufacturer);
        if ($custom) {
            $manu_data = array_merge($manu_data, $custom);
        }

        return array(
            'result' => 'success',
            'data' => $manu_data
        );
    }

    /**
     * Get data of main table use for import customer
     */
    public function getCustomers()
    {
        $id_src = $this->_notice['customers']['id_src'];
        $limit = $this->_notice['setting']['customers'];
        $customer_table = $this->getTableName(self::NOP_CUS);
        $query = "SELECT * FROM {$customer_table} WHERE folder = '{$this->_folder}' AND CustomerId > {$id_src} ORDER BY CustomerId ASC LIMIT {$limit}";
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
        return $customer['CustomerId'];
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
        $cus_data['id'] = $customer['CustomerId'];
        $cus_data['website_id'] = $this->_notice['config']['website_id'];
        $cus_data['email'] = $customer['Email'];
        $cus_data['firstname'] = $customer['FirstName'] ? $customer['FirstName'] : " ";
        $cus_data['lastname'] = $customer['LastName'] ? $customer['LastName'] : " ";
        $cus_data['created_at'] = date('Y-m-d H:i:s');
        $cus_data['is_subscribed'] = ($customer['IsRegistered'] == 'True') ? 1 : 0;
        $cus_data['group_id'] = 1;
        $cus_data['gender'] = $customer['Gender'] == 'M' ? 1 : 2;
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
        $address = array();
        $password = $customer['Password'] . ':' . $customer['PasswordSalt'];
        $this->_importCustomerRawPass($customer_mage_id, $password);
        $address['firstname'] = $customer['FirstName'] ? $customer['FirstName'] : " ";
        $address['lastname'] = $customer['LastName'] ? $customer['LastName'] : " ";
        /*$country_id_config = $this->getArrayValueByValueArray($customer['country'], $this->_notice['config']['countries_data'], $this->_notice['config']['countries']);
        $country_id = $country_id_config ? $country_id_config : 'US';*/

        $address['country_id'] = 'US';
        $address['street'] = $customer['StreetAddress'] . "\n" . $customer['StreetAddress2'];
        $address['postcode'] = $customer['ZipPostalCode'];
        $address['city'] = $customer['City'];
        $address['telephone'] = $customer['Phone'];
        $address['company'] = $customer['Company'];
        $address['fax'] = $customer['Fax'];
        $address['taxvat'] = $customer['VatNumber'];
        $address['region_id'] = 0;

        $address_ipt = $this->_process->address($address, $customer_mage_id);
        if ($address_ipt['result'] == 'success') {
            try {
                $cus = Mage::getModel('customer/customer')->load($customer_mage_id);
                $cus->setDefaultBilling($address_ipt['mage_id']);
                $cus->setDefaultShipping($address_ipt['mage_id']);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Get data of main table use import category
     */
    public function getCategories()
    {
        $id_src = $this->_notice['categories']['id_src'];
        $limit = $this->_notice['setting']['categories'];
        $cat_table = $this->getTableName(self::NOP_CAT);
        $query = "SELECT * FROM {$cat_table} WHERE folder = '{$this->_folder}' AND Id > {$id_src} ORDER BY Id ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
            return $this->errorDatabase(true);
        }
        if ($this->_notice['config']['add_option']['seo_url'] && $this->_notice['config']['add_option']['seo_plugin']) {
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
        return $category['Id'];
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
        if ($category['ParentCategoryId'] == 0) {
            $cat_parent_id = $this->_notice['config']['root_category_id'];
        } else {
            $cat_parent_id = $this->getIdDescCategory($category['ParentCategoryId']);
            if (!$cat_parent_id) {
                $parent_ipt = $this->_importCategoryParent($category['ParentCategoryId']);
                if ($parent_ipt['result'] == 'error') {
                    return $parent_ipt;
                } else if ($parent_ipt['result'] == 'warning') {
                    return array(
                        'result' => 'warning',
                        'msg' => $this->consoleWarning("Category Id = {$category['Id']} import failed. Error: Could not import parent category id = {$category['ParentCategoryId']}")
                    );
                } else {
                    $cat_parent_id = $parent_ipt['mage_id'];
                }
            }
        }
        $cat_data = array();
        $cat_data['name'] = $category['Name'];
        $cat_data['description'] = $category['Description'];
        $cat_data['meta_title'] = $category['MetaTitle'];
        $cat_data['meta_keywords'] = $category['MetaKeywords'];
        $cat_data['meta_description'] = $category['MetaDescription'];
        $pCat = Mage::getModel('catalog/category')->load($cat_parent_id);
        $cat_data['path'] = $pCat->getPath();
        $url_none_http = $this->removeHttp(strtolower($this->_cart_url));
        $cart_url = 'http' . $url_none_http;
        $cat_data['is_active'] = ($category['Published'] == 'True') ? true : false;
        $cat_data['is_anchor'] = 0;
        $cat_data['include_in_menu'] = ($category['IncludeInTopMenu'] == 'True') ? 1 : 0;
        $cat_data['display_mode'] = Mage_Catalog_Model_Category::DM_PRODUCT;
        $cat_data['position'] = $category['DisplayOrder'];
        if ($this->_seo) {
            $seo = $this->_seo->convertCategorySeo($this, $category);
            if ($seo) {
                $cat_data['seo_url'] = $seo;
            }
        }

        if ($category['PictureId']) {
            $pics = array();

            $typesAllow = array('jpg', 'jpeg', 'gif', 'png');
            $nameImage = $category['Name'];
            $nameImage = str_replace('.', '', $nameImage);
            $nameImage = str_replace('$', '', $nameImage);
            $nameImage = str_replace("'", '', $nameImage);
            $nameImage = str_replace('"', '', $nameImage);
            $nameImage = str_replace('â€', '', $nameImage);
            $nameImage = str_replace('â€œ', '', $nameImage);
            // change special character to space
            $nameImage = preg_replace('/[^a-zA-Z0-9_-]+/', ' ', $nameImage);
            // change multi space to single space
            $nameImage = preg_replace('/\s+/', ' ', $nameImage);
            // change space to hyphen
            $nameImage = str_replace(' ', '-', $nameImage);
            // change multi hyphen to single hyphen
            $nameImage = preg_replace('/-+/', '-', $nameImage);
            $nameImage = strtolower($nameImage);

            $url_product_image = $this->_cart_url;
            $_url = '';
            foreach ($typesAllow as $_type) {
                $url1 = $url_product_image . '/content/images/thumbs/' . str_pad($category['PictureId'], 7, '0', STR_PAD_LEFT) . '_' . $nameImage . '.' . $_type;
                $url2 = $url_product_image . '/content/images/thumbs/' . str_pad($category['PictureId'], 7, '0', STR_PAD_LEFT) . '_' . $nameImage . '_100.' . $_type;

                if ($this->imageExists($url1)) {
                    $_url = $url1;
                    break;
                } elseif ($this->imageExists($url2)) {
                    $_url = $url2;
                    break;
                }
            }

            if ($_url) {
                $_url = $this->takeParametersForDownloadImageCat($_url);
                $cat_data['image'] = $_url;
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

    public function imageExists($url)
    {
        $header = @get_headers($url, 1);
        if (!$header) {
            return false;
        }
        $string = $header[0];
        if (strpos($string, "404")) {
            return false;
        }
        return true;
    }

    /**
     * Import parent category if not exists by id
     */
    protected function _importCategoryParent($category_id)
    {
        $category_table = $this->getTableName(self::NOP_CAT);
        $query = "SELECT * FROM {$category_table} WHERE folder = '{$this->_folder}' AND Id = {$category_id}";
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
            $this->categorySuccess($category_id, $category_ipt['mage_id']);
            $this->afterSaveCategory($category_ipt['mage_id'], $data, $category);
        } else {
            $category_ipt['result'] = 'warning';
        }
        return $category_ipt;
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
        $product_table = $this->getTableName(self::NOP_PRD);
        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND ProductId > {$id_src} ORDER BY ProductId ASC LIMIT {$limit}";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
            return $this->errorDatabase(true);
        }
        if ($this->_notice['config']['add_option']['seo_url'] && $this->_notice['config']['add_option']['seo_plugin']) {
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
        return $product['ProductId'];
    }

    /**
     * Check product has been imported
     *
     * @param array $product : One row of object in function getProducts
     * @return boolean
     */
    public function checkProductImport($product)
    {
        $product_code = $product['SKU'] == '' ? $product['SEName'] : $product['SKU'];
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
        $pro_has_child = $this->_checkProductHasChild($product);
        if ($pro_has_child) {
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
        } else {
            $pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        }
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
     * Check product has children product in product table
     */
    protected function _checkProductHasChild($product)
    {
        $product_table = $this->getTableName(self::NOP_PRD);
        $query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND ParentGroupedProductId = '{$product['ProductId']}'";
        $result = $this->readQuery($query);
        if ($result['result'] != 'success') {
            return false;
        }
        if (!empty($result['data'])) {
            return true;
        }
        return false;
    }

    /**
     * Convert data of src cart to magento
     */
    protected function _convertProduct($product)
    {
        $pro_data = $category_ids = $productOptions = $tierPrices = array();

        if ($product['ProductCategories']) {
            $procate = unserialize($product['ProductCategories']);
            $procate = unserialize($procate['ProductCategory']);

            if (isset($procate['CategoryId'])) {
                $categoryIds[] = $procate['CategoryId'];
            } else {
                foreach ($procate as $pc) {
                    $pc = unserialize($pc);
                    $categoryIds[] = $pc['CategoryId'];
                }
            }

            foreach ($categoryIds as $category_id) {
                $category_id_desc = $this->getIdDescCategory($category_id);
                if ($category_id_desc) {
                    $category_ids[] = $category_id_desc;
                }
            }
        }

        if ($product['ProductManufacturers']) {
            $promanu = unserialize($product['ProductManufacturers']);
            $promanu = unserialize($promanu['ProductManufacturer']);

            $manu_id = $promanu['ManufacturerId'];
            $manu_table = $this->getTableName(self::NOP_MANU);
            $manu = $this->readQuery("SELECT * FROM {$manu_table} WHERE ManufacturerId = {$manu_id}");
            $name = $manu['data'][0]['Name'];

            $eav_attribute_option = $this->getTableName('eav_attribute_option');
            $query = "SELECT o.option_id FROM {$eav_attribute_option} as o,eav_attribute_option_value as ov WHERE o.option_id = ov.option_id AND ov.value = '{$name}'";
            $data = $this->readQuery($query);
            $pro_data['manufacturer'] = $data['data'][1]['option_id'];
        }


        if ($product['TierPrices']) {
            $data_tierPrices = unserialize($product['TierPrices']);
            $data_tierPrices = unserialize($data_tierPrices['TierPrice']);
            $src_data = array();
            if (isset($data_tierPrices['TierPriceId'])) {

                $src_data[] = $data_tierPrices;
            } else {
                foreach ($data_tierPrices as $data_tierPrice) {
                    $src_data[] = unserialize($data_tierPrice);
                }
            }
            //var_dump($data_tierPrices);
            foreach ($src_data as $srcdata_tierPrice) {
                $tierPrice = array();
                $tierPrice['website_id'] = $srcdata_tierPrice['StoreId'];
                $tierPrice['cust_group'] = $srcdata_tierPrice['CustomerRoleId'];
                $tierPrice['price_qty'] = $srcdata_tierPrice['Quantity'];
                $tierPrice['price'] = $srcdata_tierPrice['Price'];
                $tierPrices[] = $tierPrice;
            }
        }


        if ($product['ProductAttributes']) {
            // get ProductAttributeMapping value
            $attr_data = unserialize($product['ProductAttributes']);
            $attr_data = unserialize($attr_data['ProductAttributeMapping']);
            $data = array();
            if (isset($attr_data['ProductAttributeMappingId'])) {
                $data[] = $attr_data;
            } else {
                foreach ($attr_data as $attr_dat) {
                    $data[] = unserialize($attr_dat);
                }
            }

            // get ProductAttribute value
            foreach ($data as $attr) {
                $product_option_data = array();
                $product_option_data['id'] = $attr['ProductAttributeId'];
                $product_option_data['option_id'] = $attr['ProductAttributeId'];
                $product_option_data['title'] = $attr['ProductAttributeName'];
                $product_option_data['is_require'] = $attr['IsRequired'] == 'True' ? 1 : 0;
                $option_type = $this->_getOptionTypeById($attr['AttributeControlTypeId']);
                $product_option_data['type'] = $option_type;
                $product_option_data['sort_order'] = $attr['DisplayOrder'];

                if ($attr['ProductAttributeValues'] != "") {
                    $optionValues = unserialize($attr['ProductAttributeValues']);
                    $optionValues = unserialize($optionValues['ProductAttributeValue']);
                    $productOptionValues = array();
                    if (isset($optionValues['ProductAttributeValueId'])) {
                        $product_option_value_data = array();
                        $product_option_value_data['option_type_id'] = $optionValues['ProductAttributeValueId'];
                        $product_option_value_data['sku'] = $optionValues['ProductAttributeValueId'];
                        $product_option_value_data['title'] = $optionValues['Name'];
                        $product_option_value_data['price'] = abs($optionValues['PriceAdjustment']);
                        $product_option_value_data['sort_order'] = $optionValues['DisplayOrder'];
                        $productOptionValues[] = $product_option_value_data;
                    } else {
                        foreach ($optionValues as $optionValue) {
                            $option_cat_value_data = unserialize($optionValue);
                            $product_option_value_data = array();
                            $product_option_value_data['option_type_id'] = $option_cat_value_data['ProductAttributeValueId'];
                            $product_option_value_data['sku'] = $option_cat_value_data['ProductAttributeValueId'];
                            $product_option_value_data['title'] = $option_cat_value_data['Name'];
                            $product_option_value_data['price'] = abs($option_cat_value_data['PriceAdjustment']);
                            $product_option_value_data['sort_order'] = $option_cat_value_data['DisplayOrder'];
                            $productOptionValues[] = $product_option_value_data;
                        }
                    }

                    $product_option_data['values'] = $productOptionValues;

                }

                $productOptions[] = $product_option_data;
            }


            // end code
        }
        if ($product['SKU'] != ''){
            $product_images = $this->selectTable(self::NOP_IMG, array(
                'folder' => $this->_folder,
                'domain' => $this->_cart_url,
                'Name' => $product['Name'],
                'SKU' => $product['SKU']
            ));
        } else {
            $product_images = $this->selectTable(self::NOP_IMG, array(
                'folder' => $this->_folder,
                'domain' => $this->_cart_url,
                'Name' => $product['Name'],
            ));
        }
        $image_convert = array();
        if ($product_images) {
            $image = $product_images[0];
            $image_convert['domain'] = $this->_cart_url;
            if ($image['Picture1']) {
                $image_convert['path'] = strstr($image['Picture1'], 'content');
                $img_path = $this->downloadImage($image_convert['domain'], $image_convert['path'], 'catalog/product', false, true);
                if ($img_path) {
                    $pro_data['image_import_path'] = array(
                        'path' => $img_path,
                        'label' => $product['photo_alttext']
                    );
                }
            }
            if ($image['Picture2']) {
                $image_convert['path'] = strstr($image['Picture2'], 'content');
                $img_path = $this->downloadImage($image_convert['domain'], $image_convert['path'], 'catalog/product', false, true);
                if ($img_path) {
                    $pro_data['image_gallery'][] = array(
                        'path' => $img_path,
                        'label' => $product['photo_alttext']
                    );
                }
            }
            if ($image['Picture3']) {
                $image_convert['path'] = strstr($image['Picture3'], 'content');
                $img_path = $this->downloadImage($image_convert['domain'], $image_convert['path'], 'catalog/product', false, true);
                if ($img_path) {
                    $pro_data['image_gallery'][] = array(
                        'path' => $img_path,
                        'label' => $product['photo_alttext']
                    );
                }
            }
        }

        $pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
        $pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
        $pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
        $pro_data['sku'] = $product['SKU'] == '' ? $this->createProductSku($product['SEName'], $this->_notice['config']['languages']) : $this->createProductSku($product['SKU'], $this->_notice['config']['languages']);
        $pro_data['name'] = $product['Name'];
        $pro_data['meta_title'] = $product['MetaTitle'];
        $pro_data['meta_keyword'] = $product['MetaKeywords'];
        $pro_data['meta_description'] = $product['MetaDescription'];
        $pro_data['weight'] = $product['Weight'];
        $pro_data['status'] = $product['Published'] == 'True' ? Mage_Catalog_Model_Product_Status::STATUS_ENABLED : Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
        $pro_data['price'] = $product['Price'] ? $product['Price'] : 0;
        $pro_data['tax_class_id'] = 0;
        if ($product['SpecialPrice']) {
            $pro_data['special_price'] = $product['SpecialPrice'];
        }
        $pro_data['special_from_date'] = $product['SpecialPriceStartDateTimeUtc'] ? date('Y-m-d H:i:s', strtotime($product['SpecialPriceStartDateTimeUtc'])) : null;
        $pro_data['special_to_date'] = $product['SpecialPriceEndDateTimeUtc'] ? date('Y-m-d H:i:s', strtotime($product['SpecialPriceEndDateTimeUtc'])) : null;
        $pro_data['create_at'] = date('Y-m-d H:i:s', strtotime($product['CreatedOnUtc']));
        $pro_data['visibility'] = ($product['VisibleIndividually'] == 'False') ? Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE : Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
        $manage_stock = true;
        if ($this->_notice['config']['add_option']['stock'] && $product['StockQuantity'] < 1) {
            $manage_stock = false;
        }
        $pro_data['stock_data'] = array(
            'is_in_stock' => 1,
            'manage_stock' => $manage_stock,
            'use_config_manage_stock' => $manage_stock,
            'qty' => ($product['StockQuantity']) ? $product['StockQuantity'] : 0,
            'backorders' => 1,
        );
        if ($this->_seo) {
            $seo = $this->_seo->convertProductSeo($this, $product);
            if ($seo) {
                $pro_data['seo_url'] = $seo;
            }
        }

        $pro_data['tier_price'] = $tierPrices;
        $pro_data['options'] = $productOptions;

        if ($product['ParentGroupedProductId'] != 0) {
            $prd_table = $this->getTableName(self::NOP_PRD);
            $product = $this->readQuery("SELECT * FROM {$prd_table} WHERE ProductId =  {$product['ParentGroupedProductId']}");
            $data = $product['data'][0];
            $pro_data['description'] = $data['FullDescription'];
            $pro_data['short_description'] = $data['ShortDescription'];
            $product['ProductCategories'] = $data['ProductCategories'];
            $procate = unserialize($product['ProductCategories']);
            $procate = unserialize($procate['ProductCategory']);

            if (isset($procate['CategoryId'])) {
                $categoryIds[] = $procate['CategoryId'];
            } else {
                foreach ($procate as $pc) {
                    $pc = unserialize($pc);
                    $categoryIds[] = $pc['CategoryId'];
                }
            }

            foreach ($categoryIds as $category_id) {
                $category_id_desc = $this->getIdDescCategory($category_id);
                if ($category_id_desc) {
                    $category_ids[] = $category_id_desc;
                }
            }
            $pro_data['category_ids'] = $category_ids;
        } else {
            $pro_data['description'] = $product['FullDescription'];
            $pro_data['short_description'] = $product['ShortDescription'];
            $pro_data['category_ids'] = $category_ids;
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
        $product_code = $product['SKU'] == '' ? $product['SEName'] : $product['SKU'];
        if ($productIpt['result'] == 'success') {
            $id_desc = $productIpt['mage_id'];
            $this->productSuccess($id_src, $id_desc, $product_code);
        } else {
            $productIpt['result'] = 'warning';
            $msg = "Product code = {$product_code} import failed. Error: " . $productIpt['msg'];
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
        if ($data['type_id'] != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $option_data = array();
            foreach ($data['options'] as $option_cat) {
                $opt_data = array(
                    'previous_group' => Mage::getModel('catalog/product_option')->getGroupByType($option_cat['type']),
                    'type' => $option_cat['type'],
                    'is_require' => $option_cat['is_require'],
                    'title' => $option_cat['title']
                );

                if (in_array($option_cat['type'], array('drop_down', 'checkbox', 'radio'))) {
                    $opt_data['values'] = array();
                    if (count($option_cat['values'])) {
                        foreach ($option_cat['values'] as $opt_child) {
                            $value = array(
                                'option_type_id' => -1,
                                'title' => strip_tags($opt_child['title']),
                                'price' => $opt_child['price'],
                                'price_type' => 'fixed',
                            );
                            $opt_data['values'][] = $value;
                        }
                    }
                } elseif (in_array($option_cat['type'], array('field', 'area'))) {

                    $value = array(
                        'title' => strip_tags($option_cat['title']),
                        'price' => 0,
                        'price_type' => 'fixed',
                        'sku' => '',
                        'max_characters' => 255
                    );
                    $opt_data = array_merge($opt_data, $value);
                }
                $option_data[] = $opt_data;
            }
            if ($option_data) {
                $this->importProductOption($product_mage_id, $option_data);
            }
        }
        //end import Custom Options

        // if($data['type_id'] == Mage_Catalog_Model_Product_Type::TYPE_GROUPED){
        //     $group = array();

        //     $prd_table = $this->getTableName(self::NOP_PRD);
        //     $query = "SELECT * FROM {$prd_table} WHERE  ParentGroupedProductId = '{$product['ProductId']}'";
        //     $data = $this->readQuery($query);

        //     $child_products = $data['data'];
        //     foreach ($child_products as $child) {
        //         $check_imported = $this->getMageIdProduct($child['ProductId']);
        //         if ($check_imported) {
        //             $group[] = $check_imported;
        //         }
        //     }

        //     $products_links = Mage::getModel('catalog/product_link_api');
        //     foreach ($group as $value) {
        //         $products_links->assign("grouped", $product_mage_id, $value);
        //     }

        // }

        if ($data['type_id'] == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $parent_id = $product['ParentGroupedProductId'];
            if ($parent_id != 0) {

                $parent_mage_id = $this->getMageIdProduct($parent_id);
                $child_mage_id = $this->getMageIdProduct($product['ProductId']);

                // assign grouped
                $products_links = Mage::getModel('catalog/product_link_api');
                $products_links->assign("grouped", $parent_mage_id, $child_mage_id);

            }

        }

    }

    /**
     * Get data use for import order
     */
    public function getOrders()
    {
        $id_src = $this->_notice['orders']['id_src'];
        $limit = $this->_notice['setting']['orders'];
        $order_table = $this->getTableName(self::NOP_ORD);
        $query = "SELECT * FROM {$order_table} WHERE folder = '{$this->_folder}' AND OrderId > {$id_src} ORDER BY OrderId ASC LIMIT {$limit}";
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
        return $order['OrderId'];
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
        $cus_table = $this->getTableName(self::NOP_CUS);
        $customers = $this->readQuery("SELECT * FROM {$cus_table} WHERE folder = '{$this->_folder}' AND CustomerId = {$order['CustomerId']} ");
        if ($customers['result'] != 'success' || empty($customers['data'])) {
            return array(
                'result' => 'warning',
                'msg' => $this->consoleWarning("Order id = {$order['OrderId']} import failed. Error: Customer not import!")
            );
        }

        $customer = $customers['data'][0];

        $country_bill_code = $country_ship_code = 'US';
        $data = $address_billing = $address_shipping = $carts = array();

        $address_billing['firstname'] = $customer['FirstName'];
        $address_billing['lastname'] = $customer['LastName'];
        $address_billing['company'] = $customer['Company'];
        $address_billing['email'] = $customer['Email'];
        $address_billing['country_id'] = $country_bill_code;
        $address_billing['street'] = $customer['StreetAddress'] . "\n" . $customer['StreetAddress2'];
        $address_billing['postcode'] = $customer['ZipPostalCode'];
        $address_billing['city'] = $customer['City'];
        $address_billing['telephone'] = $customer['Phone'];
        $address_billing['fax'] = $customer['Fax'];
        $address_billing['region_id'] = 0;


        $address_shipping['firstname'] = $customer['FirstName'];
        $address_shipping['lastname'] = $customer['LastName'];
        $address_shipping['company'] = $customer['Company'];
        $address_shipping['email'] = $customer['Email'];
        $address_shipping['country_id'] = $country_bill_code;
        $address_shipping['street'] = $customer['StreetAddress'] . "\n" . $customer['StreetAddress2'];
        $address_shipping['postcode'] = $customer['ZipPostalCode'];
        $address_shipping['city'] = $customer['City'];
        $address_shipping['telephone'] = $customer['Phone'];
        $address_shipping['fax'] = $customer['Fax'];
        $address_shipping['region_id'] = 0;

        if (isset($order['OrderItems'])) {
            $order_details = array();
            $orderItems = unserialize($order['OrderItems']);
            $orderItems = unserialize($orderItems['OrderItem']);
            if (isset($orderItems['Id'])) {
                $order_details[] = $orderItems;
            } else {
                foreach ($orderItems as $orderItem) {
                    $ord = unserialize($orderItem);
                    $order_details[] = $ord;
                }
            }

            foreach ($order_details as $order_detail) {
                $cart = array();
                $cart['product_id'] = $order_detail['ProductId'];
                // get Product's SKU
                $prd_table = $this->getTableName(self::NOP_PRD);
                $product = $this->readQuery("SELECT * FROM {$prd_table} WHERE ProductId =  {$cart['product_id']}");
                $data = $product['data'][0];

                $cart['sku'] = $data['SKU'] == '' ? $data['SEName'] : $data['SKU'];
                $cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
                $cart['name'] = $order_detail['ProductName'];
                $cart['price'] = $order_detail['UnitPriceInclTax'];
                $cart['original_price'] = $order_detail['UnitPriceExclTax'];
                $cart['qty_ordered'] = $order_detail['Quantity'];
                $cart['row_total'] = $order_detail['PriceInclTax'];
                $carts[] = $cart;
            }
        }

        $customer_id = $this->getIdDescCustomer($customer['CustomerId']);
        $order_status_map = array(
            '10' => 'pending',
            '20' => 'processing',
            '30' => 'complete'
        );
        $order_status_id = 'pending';
        foreach ($order_status_map as $order_nop => $order_mage) {
            if ($order['OrderStatusId'] == $order_nop)
                $order_status_id = $order_mage;
        }
        $tax_amount = $order['OrderTax'];
        $discount_amount = $order['OrderDiscount'];
        $store_id = $this->_notice['config']['languages'][1];
        $ship_amount = $order['OrderShippingInclTax'];
        $sub_total = $order['OrderTotal'] - $tax_amount + $discount_amount - $ship_amount;

        $order_data = array();
        $order_data['store_id'] = $store_id;
        if ($customer_id) {
            $order_data['customer_id'] = $customer_id;
            $order_data['customer_is_guest'] = false;
        } else {
            $order_data['customer_is_guest'] = true;
        }
        $order_data['customer_email'] = $customer['Email'];
        $order_data['customer_firstname'] = $customer['FirstName'];
        $order_data['customer_lastname'] = $customer['LastName'];
        $order_data['customer_group_id'] = 1;
        $order_data['status'] = $order_status_id;
        $order_data['state'] = $this->getOrderStateByStatus($order_status_id);
        $order_data['subtotal'] = $this->incrementPriceToImport($sub_total);
        $order_data['base_subtotal'] = $order_data['subtotal'];
        $order_data['shipping_amount'] = $ship_amount;
        $order_data['base_shipping_amount'] = $ship_amount;
        $order_data['base_shipping_invoiced'] = $ship_amount;
        $order_data['shipping_description'] = "Shipping";
        $order_data['tax_amount'] = $tax_amount;
        $order_data['base_tax_amount'] = $tax_amount;
        $order_data['discount_amount'] = $discount_amount;
        $order_data['base_discount_amount'] = $discount_amount;
        $order_data['grand_total'] = $this->incrementPriceToImport($order['OrderTotal']);
        $order_data['base_grand_total'] = $order_data['grand_total'];
        $order_data['base_total_invoiced'] = $order_data['grand_total'];
        $order_data['total_paid'] = $order_data['grand_total'];
        $order_data['base_total_paid'] = $order_data['grand_total'];
        $order_data['base_to_global_rate'] = true;
        $order_data['base_to_order_rate'] = true;
        $order_data['store_to_base_rate'] = true;
        $order_data['store_to_order_rate'] = true;
        // $order_data['base_currency_code'] = $store_currency['base'];
        // $order_data['global_currency_code'] = $store_currency['base'];
        // $order_data['store_currency_code'] = $store_currency['base'];
        // $order_data['order_currency_code'] = $store_currency['base'];
        $order_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['CreatedOnUtc']));

        $data['address_billing'] = $address_billing;
        $data['address_shipping'] = $address_shipping;
        $data['order'] = $order_data;
        $data['carts'] = $carts;
        $data['order_src_id'] = $order['OrderId'];
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
        if (parent::afterSaveOrder($order_mage_id, $data, $order)) {
            return;
        }
        $order_status_data = array();
        $order_status_map = array(
            '10' => 'pending',
            '20' => 'processing',
            '30' => 'complete'
        );
        foreach ($order_status_map as $order_nop => $order_mage) {
            if ($order['OrderStatusId'] == $order_nop)
                $order_status_id = $order_mage;
        }
        $order_status_data['status'] = $order_status_id;
        $order_status_data['state'] = $this->getOrderStateByStatus($order_status_id);
        $order_status_data['comment'] = "<b>Reference order #" . $order['OrderId'] . "</b><br />";
        $order_status_data['comment'] .= "<b>Payment method : </b>" . $order['PaymentMethodSystemName'] . "<br />";
        $order_status_data['comment'] .= "<b>Shipping method : </b> " . $order['ShippingMethod'] . "<br />";
        $order_status_data['is_customer_notified'] = 1;
        $order_status_data['updated_at'] = date('Y-m-d H:i:s', strtotime($order['CreatedOnUtc']));
        $order_status_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['CreatedOnUtc']));
        $this->_process->ordersComment($order_mage_id, $order_status_data);
    }


    /*
        Table construct
    */

    public function manufacturersTableConstruct()
    {
        return array(
            'table' => self::NOP_MANU,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'ManufacturerId' => 'BIGINT',
                'Name' => 'VARCHAR(255)',
                'Description' => 'text',
                'ManufacturerTemplateId' => 'VARCHAR(255)',
                'MetaKeywords' => 'VARCHAR(255)',
                'MetaDescription' => 'text',
                'MetaTitle' => 'VARCHAR(255)',
                'SEName' => 'VARCHAR(255)',
                'PictureId' => 'VARCHAR(255)',
                'PageSize' => 'VARCHAR(255)',
                'AllowCustomersToSelectPageSize' => 'VARCHAR(255)',
                'PageSizeOptions' => 'VARCHAR(255)',
                'PriceRanges' => 'VARCHAR(255)',
                'Published' => 'VARCHAR(255)',
                'Deleted' => 'VARCHAR(255)',
                'DisplayOrder' => 'VARCHAR(255)',
                'CreatedOnUtc' => 'VARCHAR(255)',
                'UpdatedOnUtc' => 'VARCHAR(255)',
                'ManuProducts' => 'VARCHAR(255)',
            ),
            'validation' => array('ManufacturerId')
        );
    }


    public function categoriesTableConstruct()
    {
        return array(
            'table' => self::NOP_CAT,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'Id' => 'BIGINT',
                'Name' => 'VARCHAR(255)',
                'Description' => 'text',
                'CategoryTemplateId' => 'VARCHAR(255)',
                'MetaKeywords' => 'VARCHAR(255)',
                'MetaDescription' => 'text',
                'MetaTitle' => 'VARCHAR(255)',
                'SeName' => 'VARCHAR(255)',
                'ParentCategoryId' => 'VARCHAR(255)',
                'PictureId' => 'VARCHAR(255)',
                'PageSize' => 'VARCHAR(255)',
                'AllowCustomersToSelectPageSize' => 'VARCHAR(255)',
                'PageSizeOptions' => 'VARCHAR(255)',
                'PriceRanges' => 'VARCHAR(255)',
                'ShowOnHomePage' => 'VARCHAR(255)',
                'IncludeInTopMenu' => 'VARCHAR(255)',
                'Published' => 'VARCHAR(255)',
                'Deleted' => 'VARCHAR(255)',
                'DisplayOrder' => 'VARCHAR(255)',
                'CreatedOnUtc' => 'VARCHAR(255)',
                'UpdatedOnUtc' => 'VARCHAR(255)',
                'CateProducts' => 'VARCHAR(255)',
                'SubCate' => 'TEXT',
            ),
            'validation' => array('Id')
        );
    }


    public function productsTableConstruct()
    {
        return array(
            'table' => self::NOP_PRD,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'ProductId' => 'BIGINT',
                'ProductTypeId' => 'VARCHAR(255)',
                'ParentGroupedProductId' => 'VARCHAR(255)',
                'VisibleIndividually' => 'VARCHAR(255)',
                'Name' => 'VARCHAR(255)',
                'ShortDescription' => 'text',
                'FullDescription' => 'text',
                'VendorId' => 'VARCHAR(255)',
                'ProductTemplateId' => 'VARCHAR(255)',
                'ShowOnHomePage' => 'VARCHAR(255)',
                'MetaKeywords' => 'VARCHAR(255)',
                'MetaDescription' => 'text',
                'MetaTitle' => 'VARCHAR(255)',
                'SEName' => 'VARCHAR(255)',
                'AllowCustomerReviews' => 'VARCHAR(255)',
                'SKU' => 'VARCHAR(255)',
                'ManufacturerPartNumber' => 'VARCHAR(255)',
                'RequireOtherProducts' => 'VARCHAR(255)',
                'RequiredProductIds' => 'VARCHAR(255)',
                'AutomaticallyAddRequiredProducts' => 'VARCHAR(255)',
                'IsDownload' => 'VARCHAR(255)',
                'DownloadId' => 'VARCHAR(255)',
                'UnlimitedDownloads' => 'VARCHAR(255)',
                'MaxNumberOfDownloads' => 'VARCHAR(255)',
                'IsRecurring' => 'VARCHAR(255)',
                'RecurringCycleLength' => 'VARCHAR(255)',
                'RecurringCyclePeriodId' => 'VARCHAR(255)',
                'RecurringTotalCycles' => 'VARCHAR(255)',
                'IsRental' => 'VARCHAR(255)',
                'RentalPriceLength' => 'VARCHAR(255)',
                'RentalPricePeriodId' => 'VARCHAR(255)',
                'IsShipEnabled' => 'VARCHAR(255)',
                'IsFreeShipping' => 'VARCHAR(255)',
                'ShipSeparately' => 'VARCHAR(255)',
                'AdditionalShippingCharge' => 'VARCHAR(255)',
                'DeliveryDateId' => 'VARCHAR(255)',
                'IsTaxExempt' => 'VARCHAR(255)',
                'TaxCategoryId' => 'VARCHAR(255)',
                'ManageInventoryMethodId' => 'VARCHAR(255)',
                'StockQuantity' => 'VARCHAR(255)',
                'DisplayStockAvailability' => 'VARCHAR(255)',
                'DisplayStockQuantity' => 'VARCHAR(255)',
                'MinStockQuantity' => 'VARCHAR(255)',
                'LowStockActivityId' => 'VARCHAR(255)',
                'NotifyAdminForQuantityBelow' => 'VARCHAR(255)',
                'OrderMinimumQuantity' => 'VARCHAR(255)',
                'OrderMaximumQuantity' => 'VARCHAR(255)',
                'AllowedQuantities' => 'VARCHAR(255)',
                'DisableBuyButton' => 'VARCHAR(255)',
                'DisableWishlistButton' => 'VARCHAR(255)',
                'AvailableForPreOrder' => 'VARCHAR(255)',
                'PreOrderAvailabilityStartDateTimeUtc' => 'VARCHAR(255)',
                'Price' => 'VARCHAR(255)',
                'OldPrice' => 'VARCHAR(255)',
                'ProductCost' => 'VARCHAR(255)',
                'SpecialPrice' => 'VARCHAR(255)',
                'SpecialPriceStartDateTimeUtc' => 'VARCHAR(255)',
                'SpecialPriceEndDateTimeUtc' => 'VARCHAR(255)',
                'Weight' => 'VARCHAR(255)',
                'Length' => 'VARCHAR(255)',
                'Width' => 'VARCHAR(255)',
                'Height' => 'VARCHAR(255)',
                'Published' => 'VARCHAR(255)',
                'CreatedOnUtc' => 'VARCHAR(255)',
                'UpdatedOnUtc' => 'VARCHAR(255)',
                'ProductDiscounts' => 'VARCHAR(255)',
                'TierPrices' => 'text',
                'ProductAttributes' => 'text',
                'ProductPictures' => 'text',
                'ProductCategories' => 'text',
                'ProductManufacturers' => 'text',
                'ProductSpecificationAttributes' => 'text'
            ),
            'validation' => array('ProductId')
        );
    }

    public function customersTableConstruct()
    {
        return array(
            'table' => self::NOP_CUS,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'CustomerId' => 'BIGINT',
                'CustomerGuid' => 'VARCHAR(255)',
                'Email' => 'VARCHAR(255)',
                'Username' => 'VARCHAR(255)',
                'Password' => 'VARCHAR(255)',
                'PasswordFormatId' => 'VARCHAR(255)',
                'PasswordSalt' => 'VARCHAR(255)',
                'IsTaxExempt' => 'VARCHAR(255)',
                'AffiliateId' => 'VARCHAR(255)',
                'VendorId' => 'VARCHAR(255)',
                'Active' => 'VARCHAR(255)',
                'IsGuest' => 'VARCHAR(255)',
                'IsRegistered' => 'VARCHAR(255)',
                'IsAdministrator' => 'VARCHAR(255)',
                'IsForumModerator' => 'VARCHAR(255)',
                'FirstName' => 'VARCHAR(255)',
                'LastName' => 'VARCHAR(255)',
                'Gender' => 'VARCHAR(255)',
                'Company' => 'VARCHAR(255)',
                'CountryId' => 'text',
                'StreetAddress' => 'text',
                'StreetAddress2' => 'VARCHAR(255)',
                'ZipPostalCode' => 'VARCHAR(255)',
                'City' => 'VARCHAR(255)',
                'StateProvinceId' => 'VARCHAR(255)',
                'Phone' => 'VARCHAR(255)',
                'Fax' => 'VARCHAR(255)',
                'VatNumber' => 'VARCHAR(255)',
                'VatNumberStatusId' => 'VARCHAR(255)',
                'TimeZoneId' => 'VARCHAR(255)',
                'AvatarPictureId' => 'VARCHAR(255)',
                'ForumPostCount' => 'VARCHAR(255)',
                'Signature' => 'VARCHAR(255)',
            ),
            'validation' => array('CustomerId')
        );
    }

    public function ordersTableConstruct()
    {
        return array(
            'table' => self::NOP_ORD,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'OrderId' => 'BIGINT',
                'OrderGuid' => 'VARCHAR(255)',
                'StoreId' => 'VARCHAR(255)',
                'CustomerId' => 'VARCHAR(255)',
                'OrderStatusId' => 'VARCHAR(255)',
                'PaymentStatusId' => 'VARCHAR(255)',
                'ShippingStatusId' => 'VARCHAR(255)',
                'CustomerLanguageId' => 'VARCHAR(255)',
                'CustomerTaxDisplayTypeId' => 'VARCHAR(255)',
                'CustomerIp' => 'VARCHAR(255)',
                'OrderSubtotalInclTax' => 'VARCHAR(255)',
                'OrderSubtotalExclTax' => 'VARCHAR(255)',
                'OrderSubTotalDiscountInclTax' => 'VARCHAR(255)',
                'OrderSubTotalDiscountExclTax' => 'VARCHAR(255)',
                'OrderShippingInclTax' => 'VARCHAR(255)',
                'OrderShippingExclTax' => 'VARCHAR(255)',
                'PaymentMethodAdditionalFeeInclTax' => 'VARCHAR(255)',
                'PaymentMethodAdditionalFeeExclTax' => 'VARCHAR(255)',
                'TaxRates' => 'VARCHAR(255)',
                'OrderTax' => 'VARCHAR(255)',
                'OrderTotal' => 'VARCHAR(255)',
                'RefundedAmount' => 'VARCHAR(255)',
                'OrderDiscount' => 'VARCHAR(255)',
                'CurrencyRate' => 'VARCHAR(255)',
                'CustomerCurrencyCode' => 'VARCHAR(255)',
                'AffiliateId' => 'VARCHAR(255)',
                'AllowStoringCreditCardNumber' => 'VARCHAR(255)',
                'CardType' => 'VARCHAR(255)',
                'CardName' => 'VARCHAR(255)',
                'CardNumber' => 'VARCHAR(255)',
                'MaskedCreditCardNumber' => 'VARCHAR(255)',
                'CardCvv2' => 'VARCHAR(255)',
                'CardExpirationMonth' => 'VARCHAR(255)',
                'CardExpirationYear' => 'VARCHAR(255)',
                'PaymentMethodSystemName' => 'VARCHAR(255)',
                'AuthorizationTransactionId' => 'VARCHAR(255)',
                'AuthorizationTransactionCode' => 'VARCHAR(255)',
                'AuthorizationTransactionResult' => 'VARCHAR(255)',
                'CaptureTransactionId' => 'VARCHAR(255)',
                'CaptureTransactionResult' => 'VARCHAR(255)',
                'SubscriptionTransactionId' => 'VARCHAR(255)',
                'PaidDateUtc' => 'VARCHAR(255)',
                'ShippingMethod' => 'VARCHAR(255)',
                'ShippingRateComputationMethodSystemName' => 'VARCHAR(255)',
                'CustomValuesXml' => 'VARCHAR(255)',
                'VatNumber' => 'VARCHAR(255)',
                'Deleted' => 'VARCHAR(255)',
                'CreatedOnUtc' => 'VARCHAR(255)',
                'OrderItems' => 'text',
            ),
            'validation' => array('OrderId')
        );
    }

    public function imagesTableConstruct()
    {
        return array(
            'table' => self::NOP_IMG,
            'rows' => array(
                'folder' => 'VARCHAR(255)',
                'domain' => 'VARCHAR(255)',
                'Name' => 'VARCHAR(255)',
                'SKU' => 'VARCHAR(255)',
                'Picture1' => 'VARCHAR(255)',
                'Picture2' => 'VARCHAR(255)',
                'Picture3' => 'VARCHAR(255)'
            )
        );
    }

    protected function _clearStorageCsv()
    {
        $folder = $this->_folder;
        $tables = array(
            self::NOP_MANU,
            self::NOP_CAT,
            self::NOP_PRD,
            self::NOP_CUS,
            self::NOP_ORD,
            self::NOP_IMG
        );
        foreach ($tables as $table) {
            $table_name = $this->getTableName($table);
            $query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
            $this->writeQuery($query);
        }
        $this->_notice['csv_import']['function'] = '_storageCsvManufacturers';
        return array(
            'result' => 'process',
            'msg' => ''
        );
    }


    protected function _storageCsvManufacturers()
    {
        return $this->_storageDatByType('manufacturers', 'categories', false, false);
    }

    protected function _storageCsvCategories()
    {
        return $this->_storageDatByType('categories', 'products', false, false);
    }

    protected function _storageCsvProducts()
    {
        return $this->_storageDatByType('products', 'customers', false, false);
    }

    protected function _storageCsvCustomers()
    {
        return $this->_storageDatByType('customers', 'orders', false, false);
    }

    protected function _storageCsvOrders()
    {
        return $this->_storageDatByType('orders', 'images', false, false);
    }

    protected function _storageCsvImages()
    {
        return $this->_storageDatByType('images', 'images', false, true);
    }

    public function _storageDatByType($type, $next, $success = false, $finish = false, $unset = array())
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
        if ($type != 'images') {
            $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.xml';
            $readXml = $this->readXml2($_file, $start, $this->_notice['setting']['csv'], $demo, $type);
        } else {
            $_file = Mage::getBaseDir('media') . self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
            $readXml = $this->readCsv($_file, $start, $this->_notice['setting']['csv'], $demo, $type);
        }

        if ($readXml['result'] != 'success') {
            $readXml['msg'] = $this->consoleError($readXml['msg']);
            return $readXml;
        }

        $allowData = array();
        $fn_construct = $type . 'TableConstruct';
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

        if ($type != 'images') {
            foreach ($readXml['data'] as $item) {
                $data = $item;
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
                    if (!$insert) {
                        return array(
                            'result' => 'error',
                            'msg' => $this->consoleError('Could not import data to database.')
                        );
                    }

                    if ($type == 'categories') {
                        if ($data['SubCategories']) {
                            $subCategories = unserialize($data['SubCategories']);
                            $subCategories = unserialize($subCategories['Category']);
                            $this->storgeChildCategory($subCategories, $allowData, $table);
                        }
                    }

                }
            }
        } else {
            foreach($readXml['data'] as $item){
                $data = $this->syncCsvTitleRow($item['title'], $item['row']);
                if(!empty($data)){
                    if($validation){
                        foreach($validation as $column_name){
                            if(!isset($data[$column_name]) || !$data[$column_name]){
                                continue 2;
                            }
                        }
                    }
                    $data = $this->addConfigToArray($data);
                    $insert = $this->insertTable($table['table'], $data, $allowData);
                    if(!$insert){
                        return array(
                            'result' => 'error',
                            'msg' => $this->consoleError('Could not import csv to database.')
                        );
                    }
                }
            }
        }
        if ($readXml['finish']) {
            if ($finish) {
                $this->_notice['csv_import']['result'] = 'success';
            } else {
                $this->_notice['csv_import']['result'] = 'process';
            }
            $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($success);
            $this->_notice['csv_import']['msg'] = $this->consoleSuccess("Finish import " . $type);
            $this->_notice['csv_import']['count'] = 0;
            return $this->_notice['csv_import'];
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_storageCsv' . ucfirst($type);
        $this->_notice['csv_import']['msg'] = '';
        $this->_notice['csv_import']['count'] = $readXml['count'];
        return $this->_notice['csv_import'];
    }

    public function storgeChildCategory($subCategories, $allowData, $table)
    {
        $data = array();
        if (isset($subCategories['Id'])) {
            $data[] = $subCategories;
        } else {
            foreach ($subCategories as $subCate) {
                $data[] = unserialize($subCate);
            }

            foreach ($data as $_data) {
                if ($_data['SubCategories']) {
                    $subCates = unserialize($_data['SubCategories']);
                    $subCates = unserialize($subCates['Category']);
                    $this->storgeChildCategory($subCates, $allowData, $table);
                }
                // $_data['data'] = serialize($_data);
                $_data = $this->addConfigToArray($_data);
                // $_data = $this->setListArray($allowData, $_data);

                $insert = $this->insertTable($table['table'], $_data, $allowData);

                if (!$insert) {
                    return array(
                        'result' => 'error',
                        'msg' => $this->consoleError('Could not import xml to database.')
                    );
                }
            }
        }
    }


    /**
     * Get id_desc by type and value
     */
    protected function _getLeCaIpImportIdDescByValue($type, $value)
    {
        $result = $this->selectTableRow(self::TABLE_IMPORT, array(
            'folder' => $this->_folder,
            'type' => $type,
            'value' => $value
        ));
        if (!$result) {
            return false;
        }
        return (isset($result['id_desc'])) ? $result['id_desc'] : false;
    }

    public function xml2array($xmlObject)
    {
        $data = (array)$xmlObject;
        if (count($data)) {
            foreach ($data as $index => $node) {
                if ((is_object($node) && count((array)$node)) || is_array($node)) {
                    $result[$index] = serialize($this->xml2array($node));
                } elseif (is_object($node) && !count((array)$node)) {
                    $result[$index] = '';
                } else {
                    $result[$index] = $node;
                }
            }
        } else {
            $result = '';
        }

        return $result;
    }

    public function readCsv($file_path, $start, $limit = 10, $total = false){
        if(!is_file($file_path)){
            return array(
                'result' => 'error',
                'msg' => 'Path not exists'
            );
        }
        try{
            $finish = false;
            $count = 0;
            $csv = fopen($file_path, 'r');
            $end = $start + $limit;
            $csv_title = "";
            $data = array();
            while (!feof($csv)){
                if($total && $count > $total){
                    $finish = true;
                    break ;
                }
                if($count > $end){
                    break ;
                }
                $line = fgetcsv($csv);
                if ($count == 0) {
                    $csv_title = $line;
                }
                if($start < $count && $count <= $end){
                    $data[] = array(
                        'title' => $csv_title,
                        'row' => $line
                    );
                }
                $count++;
            }
            fclose($csv);
            if(!$finish && ($count - 1) <$end){
                $finish = true;
            }
            return array(
                'result' => 'success',
                'data' => $data,
                'count' => $end,
                'finish' => $finish
            );
        } catch (Exception $e){
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }

    public function readXml2($file_path, $start, $limit = 10, $total = false, $type = '')
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

            $xmlObject = simplexml_load_file($file_path);

            $data = array();
            $objectType = $this->getObjectByType($type);

            $end = $start + $limit;
            foreach ($xmlObject->$objectType as $row) {
                if ($total && $count > $total) {
                    $finish = true;
                    break;
                }
                if ($count > $end) {
                    break;
                }

                if ($start <= $count && $count < $end) {
                    $data[] = $this->xml2array($row);
                }

                $count++;
            }

            if (!$finish && $count < $end) {
                $finish = true;
            }
            return array(
                'result' => 'success',
                'data' => $data,
                'count' => $end,
                'finish' => $finish
            );
        } catch (Exception $e) {
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }

    public function getObjectByType($type)
    {
        $result = '';
        if ($type == 'products') {
            $result = 'Product';
        } elseif ($type == 'categories') {
            $result = 'Category';
        } elseif ($type == 'manufacturers') {
            $result = 'Manufacturer';
        } elseif ($type == 'customers') {
            $result = 'Customer';
        } elseif ($type == 'orders') {
            $result = 'Order';
        }
        return $result;
    }

    /**
     * get Option Type
     */
    protected function _getOptionTypeById($AttributeControlTypeId)
    {
        $attr_type = array(
            '1' => 'drop_down',//'dropdown-list',
            '2' => 'radio',//'Radio-button-list',
            '3' => 'checkbox',//'Checkboxes',
            '4' => 'field',//'Textbox',
            '10' => 'area',//'Multiline-textbox',
            '20' => 'date',//'Date-picker',
            '30' => 'file',//'File-upload',
            '40' => 'drop_down',//'Color-squares',
            '50' => 'checkbox' //'Read-only-checkboxes'
        );
        if (isset($attr_type[$AttributeControlTypeId])) {
            return $attr_type[$AttributeControlTypeId];
        } else {
            return 0;
        }
//        $AttributeControlTypeId
    }


    public function printError($str)
    {
        echo "<pre>";
        var_dump($str);
        echo "</pre>";

        die();
    }

    public function takeParametersForDownloadImageCat($_url)
    {
        $a = explode("/", $_url);
        $url_1 = $a[sizeof($a) - 1];
        $url_11 = $a[sizeof($a) - 2];
        unset($a[sizeof($a) - 1]);
        unset($a[sizeof($a) - 1]);
        $url_1 = $url_11 . "/" . $url_1;
        $url_2 = implode("/", $a);
        $url_2 .= "/";
        $_url = $this->downloadImage($url_2, $url_1, 'catalog/category');
        return $_url;
    }

    public function takeParametersForDownloadImageProduct($_url)
    {
        $a = explode("/", $_url);
        $url_1 = $a[sizeof($a) - 1];
        $url_11 = $a[sizeof($a) - 2];
        unset($a[sizeof($a) - 1]);
        unset($a[sizeof($a) - 1]);
        $url_1 = $url_11 . "/" . $url_1;
        $url_2 = implode("/", $a);
        $url_2 .= "/";
        $_url = $this->downloadImage($url_2, $url_1, 'catalog/product', false, true);
        return $_url;
    }

    /**
     * Get magento product id import by src id
     */
    public function getMageIdProduct($id_import)
    {
        $result = $this->_selectLeCaMgImport(array(
            'domain' => $this->_cart_url,
            'type' => 'product',
            'folder' => $this->_folder,
            'id_src' => $id_import
        ));
        if (!$result) {
            return false;
        }
        return $result['id_desc'];
    }

    protected function _selectLeCaMgImport($where)
    {
        return $this->selectTableRow(self::TABLE_IMPORTS, $where);
    }

    public function selectTableRow($table, $where)
    {
        $result = $this->selectTable($table, $where);
        if (!$result) {
            return false;
        }
        return (isset($result[0])) ? $result[0] : false;
    }

    public function selectTable($table, $where = array())
    {
        $where_query = $this->arrayToWhereCondition($where);
        $table_name = $this->getTableName($table);
        $query = "SELECT * FROM " . $table_name . " WHERE " . $where_query;
        try {
            $result = $this->_read->fetchAll($query);
            return $result;
        } catch (Exception $e) {
            echo $e->getMessage();
            die();
            return false;
        }
    }
}