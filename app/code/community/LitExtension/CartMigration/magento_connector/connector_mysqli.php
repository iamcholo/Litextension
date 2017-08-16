<?php

define('LECM_TOKEN', '123456');

class LECM_Connector {

    var $action = null;
    var $adapter = null;
    var $response = null;

    function __construct() {
        $this->action = $this->_getAction();
        $this->adapter = $this->_getAdapter();
        $this->response = $this->_getResponse();
    }

    function run() {		
        if(empty($_GET)){
            echo "Connector Installed";
            return ;
        }
        if (!$this->_checkToken()) {
            $this->response->error('Token is false !', null);
            return;
        }

        $this->action->setConnector($this);
        $this->action->run();
    }

   function _getAdapter() {
        $adapter = new LECM_Connector_Adapter();
        return $adapter;
    }

   function _getResponse() {
        $response = new LECM_Connector_Response();
        return $response;
    }

   function _getAction() {
        $action = new LECM_Connector_Action();
        return $action;
    }

   function _checkToken() {
        if (isset($_GET['token']) && $_GET['token'] == LECM_TOKEN) {
            return true;
        } else {
            return false;
        }
    }

}

class LECM_Connector_Action {

    var $type = null;
    var $connector = null;

   function __construct() {
        
    }

   function setConnector($connector) {
        $this->connector = $connector;
    }

   function _getActionType($action_type) {
        $action = null;
        $action_type = strtolower($action_type);
        $class_name = __CLASS__ . '_' . ucfirst($action_type);
        if (class_exists($class_name)) {
            $action = new $class_name();
        }
        return $action;
    }

   function run() {
        if (isset($_GET['action']) && $action = $this->_getActionType($_GET['action'])) {
            $action->setConnector($this->connector);
            $action->run();
        } else {
            $response = $this->connector->response;
            $response->createResponse('error', 'Action not found !', null);
            return;
        }
    }

   function _getResponse() {
        return $this->connector->response;
    }

   function _getAdapter() {
        return $this->connector->adapter;
    }

   function _getCart() {
        $adapter = $this->_getAdapter();
        $cart = $adapter->getCart();
        return $cart;
    }

}

class LECM_Connector_Action_Check extends LECM_Connector_Action {

   function __construct() {
        parent::__construct();
    }

   function run() {
        $response = $this->_getResponse();
        $adapter = $this->_getAdapter();
        $cart = $this->_getCart();
        $obj['cms'] = $adapter->detectCartType();
        if ($cart) {
            $obj['image_category'] = $cart->imageDirCategory;
            $obj['image_product'] = $cart->imageDirProduct;
            $obj['image_manufacturer'] = $cart->imageDirManufacturer;
            $obj['table_prefix'] = $cart->tablePrefix;
            $obj['version'] = $cart->version;
            $obj['charset'] = $cart->char_set;
            $obj['blowfish_key'] = $cart->blowfish_key;
            $obj['cookie_key'] = $cart->cookie_key;
            $connect = $cart->connect();
            if ($connect && $char_set = $this->_checkDatabaseExist($connect)) {
                if($obj['charset'] == ''){
                    $obj['charset'] = $char_set;
                }
                $obj['connect'] = array(
                    'result' => 'success',
                    'msg' => 'Successful connect to database !'
                );
            } else {
                $obj['connect'] = array(
                    'result' => 'error',
                    'msg' => 'Not connect to database !'
                );
            }
        }
        $response->success('Successful check CMS !', $obj);
        return;
    }

   function _checkDatabaseExist($connect){
        $query = "SHOW VARIABLES LIKE \"ch%\"";
        $rows = array();
        $char = null;
        $result = @mysqli_query($connect, $query);
        while ($row = @mysqli_fetch_array($result)) {
            $rows[] = $row;
        }
        foreach($rows as $row){
            if($row['Variable_name'] == 'character_set_database'){
                $char_set = $row['Value'];
            }
            if(strpos($row['Value'], 'utf8') !== false){
                $char = 'utf8';
                break ;
            }
        }
        if(!$char){ $char = $char_set;}
        return $char;
    }

}

class LECM_Connector_Action_File_Content extends LECM_Connector_Action {

   function __construct() {
        parent::__construct();
    }

   function run() {
        $obj = array();
        $response = $this->_getResponse();
        if(isset($_REQUEST['files'])){
            $files = base64_decode($_REQUEST['files']);
            if(isset($_REQUEST['serialize']) && $_REQUEST['serialize']){
                $files = unserialize($files);
            }
            if(is_string($files)){
                $path = LECM_STORE_BASE_DIR.$files;
                if(file_exists($path)){
                    $content = @file_get_contents($path);
                    $obj[] = $content;
                }
            }
            if(is_array($files)){
                foreach ($files as $file){
                    $path = LECM_STORE_BASE_DIR.$file;
                    if(file_exists($path)){
                        $content = @file_get_contents($path);
                        $obj[] = $content;
                    }
                }
            }
        }
        $response->success(null, $obj);
        return ;
    }

}

class LECM_Connector_Action_Query extends LECM_Connector_Action {

   function __construct() {
        parent::__construct();
    }

   function run() {
        $obj = array();
        $response = $this->_getResponse();
        $cart = $this->_getCart();
        if ($cart) {
            $connect = $cart->connect();
            if ($connect && isset($_REQUEST['query'])) {
                if(isset($_REQUEST['char_set'])){
                    $char_set = base64_decode($_REQUEST['char_set']);
                    @mysqli_query($connect, "SET NAMES " . @mysqli_real_escape_string($connect, $char_set));
                    @mysqli_query($connect, "SET CHARACTER SET " . @mysqli_real_escape_string($connect, $char_set));
                    @mysqli_query($connect, "SET CHARACTER_SET_CONNECTION=" . @mysqli_real_escape_string($connect, $char_set));
                    //@mysqli_query($connect, "SET CHARACTER_SET_CLIENT=".@mysqli_real_escape_string($connect, $char_set));
                }
                $query = base64_decode($_REQUEST['query']);
                if(isset($_REQUEST['serialize']) && $_REQUEST['serialize']){
                    $query = unserialize($query);
                    foreach($query as $key => $string){
                        $obj[$key] = $this->_getData($string, $connect);
                    }
                } else {
                    $obj = $this->_getData($query, $connect);
                }
                $response->success(null, $obj);
                return;
            } else {
                $response->error('Can\'t connect to database or not run query !', null);
                return;
            }
        } else {
            $response->error('CMS Cart not found !', null);
            return;
        }
    }

   function _getData($query, $connect){
        $rows = array();
        $res = @mysqli_query($connect, $query);
        while($row = @mysqli_fetch_array($res)){
            $rows[] = $row;
        }
        return $rows;
    }
}

class LECM_Connector_Adapter {

    var $cart = null;
    var $Host = 'localhost';
    var $Port = '3306';
    var $Username = 'root';
    var $Password = '';
    var $Dbname = '';
    var $tablePrefix = '';
    var $imageDir = '';
    var $imageDirCategory = '';
    var $imageDirProduct = '';
    var $imageDirManufacturer = '';
    var $version = '';
    var $char_set = '';
    var $cookie_key = '';
    var $blowfish_key = '';

   function __construct() {
        
    }

   function getCart() {
        $cart_type = $this->detectCartType();
        $this->cart = $this->_getCartType($cart_type);
        return $this->cart;
    }

   function _getCartType($cart_type) {
        $cart = null;
        $cart_type = strtolower($cart_type);
        $class_name = __CLASS__ . '_' . ucfirst($cart_type);
        if (class_exists($class_name)) {
            $cart = new $class_name();
        }
        return $cart;
    }

   function detectCartType() {

        if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php')) {

            // ZenCart
            if (file_exists(LECM_STORE_BASE_DIR . 'ipn_main_handler.php')) {
                return 'zencart';
            }

            // XtCommerce v3
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.org.php')) {
                return 'xtcommerce';
            }

            // Loaded Commerce v6
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure_dist.php')) {
                return 'loaded';
            }

            // TomatoCart
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'toc_constants.php')) {
                return 'tomatocart';
            }

            // OsCommerce
            return 'oscommerce';
        }

        // VirtueMart
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . '/components/com_virtuemart/virtuemart.php'))
        ) {
            return 'virtuemart';
        }
        
        //Mijoshop
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . 'components/com_mijoshop/opencart/config.php'))
        ) {
            return 'opencart';
        }

        // WordPress
        if (file_exists(LECM_STORE_BASE_DIR . 'wp-config.php')) {
            // WooCommerce
            $wooCommerceDir = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/woocommerce*', GLOB_ONLYDIR);
            if (is_array($wooCommerceDir) && count($wooCommerceDir) > 0) {
                return 'woocommerce';
            }
            //marketpress
            $marketpressDir = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/wordpress-ecommerce/marketpress-includes*', GLOB_ONLYDIR);
            if (is_array($marketpressDir) && count($marketpressDir) > 0) {
                return 'marketpress';
            }
            // WP eCommerce
            return 'wpecommerce';
        }

        // XtCommerce v4
        if (file_exists(LECM_STORE_BASE_DIR . 'conf/config.php')) {
            return 'xtcommerce';
        }

        if (file_exists(LECM_STORE_BASE_DIR . 'config.php')) {

            // OpenCart
            if ((file_exists(LECM_STORE_BASE_DIR . 'system/startup.php') || (file_exists(LECM_STORE_BASE_DIR . 'common.php')) || (file_exists(LECM_STORE_BASE_DIR . 'library/locator.php')))
            ) {
                return 'opencart';
            }

            //Cs-Cart
            if (file_exists(LECM_STORE_BASE_DIR . 'config.local.php') || file_exists(LECM_STORE_BASE_DIR . 'partner.php')
            ) {
                return 'cscart';
            }

            // XCart
            return 'xcart';
        }
        
        //Prestashop
        if (file_exists(LECM_STORE_BASE_DIR . 'config/settings.inc.php')) {
            return 'prestashop';
        }

        // Loaded Commerce v7
        if (file_exists(LECM_STORE_BASE_DIR . 'includes/config.php')) {
            if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml')) {
                return 'magento';
            }
            return 'loaded';
        }
        
        // Cube Cart
        if (file_exists(LECM_STORE_BASE_DIR . 'includes/global.inc.php')) {
            return 'cubecart';
        }

       if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml')) {
           return 'magento';
       }

       // Interspire
       if (file_exists(LECM_STORE_BASE_DIR . 'config/config.php')) {
           return 'interspire';
       }

       // XCart 5
       if (file_exists(LECM_STORE_BASE_DIR . 'etc/config.php')) {
           return 'xcart';
       }
       
       //Oxid eShop
        if (file_exists(LECM_STORE_BASE_DIR . 'config.inc.php')){
            return 'oxideshop';
        }
       //Drupal Cart
       if (file_exists(LECM_STORE_BASE_DIR . 'sites/default/settings.php') && file_exists(LECM_STORE_BASE_DIR . 'sites/all/modules/commerce/commerce.api.php')) {
           return 'drupalcart';
       }

       // Uber Cart
       if (file_exists(LECM_STORE_BASE_DIR . 'sites/default/settings.php') && file_exists(LECM_STORE_BASE_DIR . 'sites/all/modules/ubercart/uc_store/uc_store.api.php')) {
           return 'ubercart';
       }

       return 'Not detect cart !';
    }

    function setHostPort($source) {
        $source = trim($source);

        if ($source == '') {
            $this->Host = 'localhost';
            return;
        }

        $conf = explode(':', $source);
        if (isset($conf[0]) && isset($conf[1])) {
            $this->Host = $conf[0];
            $this->Port = $conf[1];
        } elseif ($source[0] == '/') {
            $this->Host = 'localhost';
            $this->Port = $source;
        } else {
            $this->Host = $source;
        }
    }

   function connect() {
        $triesCount = 10;
        $link = null;
        $host = $this->Host . ($this->Port ? ':' . $this->Port : '');
        while (!$link) {
            if (!$triesCount--) {
                break;
            }
            $link = @mysqli_connect($host, $this->Username, $this->Password, $this->Dbname);
            if (!$link) {
                sleep(2);
            }
        }

        return $link;
    }

    function getCartVersionFromDb($field, $tableName, $where)
    {
        $_link = null;
        $version = '';

        $_link = $this->connect();
        if (!$_link) {
            return $version;
        }

        $sql = 'SELECT ' . $field . ' AS version FROM ' . $this->tablePrefix . $tableName . ' WHERE ' . $where;

        $query = mysqli_query($_link, $sql);

        if ($query !== false) {
            $row = mysqli_fetch_assoc($query);

            $version = $row['version'];
        }

        return $version;
    }

}

error_reporting( E_ALL ^ E_NOTICE );
class LECM_Connector_Adapter_Oxideshop extends LECM_Connector_Adapter{
    
    function getShopBasePath(){
        return dirname(__FILE__) . '/';
    }

    function __construct(){
        parent::__construct();
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'config.inc.php');
        preg_match("/this->dbHost = '(.*)'/",$config,$match);
        $this->setHostPort($match[1]);
        preg_match("/this->dbName = '(.*)'/",$config,$match);
        $this->Dbname = $match[1];
        preg_match("/this->dbUser = '(.*)'/",$config,$match);
        $this->Username = $match[1];
        preg_match("/this->dbPwd = '(.*)'/",$config,$match);
        $this->Password = $match[1];
        $this->version = $this->getCartVersionFromDb('OXVERSION', 'oxshops', "OXACTIVE = 1");
        $this->imageDir = 'out/pictures/master/';
        $this->imageDirCategory = $this->imageDir . 'category/thumb';
        $this->imageDirProduct = $this->imageDir . 'product';
        $this->imageDirManufacturer = $this->imageDir . 'manufacturer';
        //Languages
        if (file_exists(LECM_STORE_BASE_DIR . 'bootstrap.php')){
            @require_once LECM_STORE_BASE_DIR . 'bootstrap.php';
            $ox_lang = new oxLang();
            $languages = $ox_lang->getLanguageArray();
            $this->cookie_key = $languages;
        }
        if(file_exists(LECM_STORE_BASE_DIR . 'core' . DIRECTORY_SEPARATOR . 'oxlang.php')){
            $this->imageDir = 'out/pictures';
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
        }
    }
}

class LECM_Connector_Adapter_Oscommerce extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();

        @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
        $this->setHostPort(DB_SERVER);
        $this->Username = DB_SERVER_USERNAME;
        $this->Password = DB_SERVER_PASSWORD;
        $this->Dbname = DB_DATABASE;
        $this->imageDir = DIR_WS_IMAGES;
        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        if (defined('DIR_WS_PRODUCT_IMAGES')) {
            $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
        }
        if (defined('DIR_WS_ORIGINAL_IMAGES')) {
            $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
        }
    }

}

class LECM_Connector_Adapter_Zencart extends LECM_Connector_Adapter{

   function __construct(){
        parent::__construct();

        @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
        $this->Username  = DB_SERVER_USERNAME;
        $this->Password  = DB_SERVER_PASSWORD;
        $this->Dbname    = DB_DATABASE;
        $this->setHostPort(DB_SERVER);
        $this->tablePrefix = DB_PREFIX;
        $this->imageDir = DIR_WS_IMAGES;
        $this->imageDirCategory    = $this->imageDir;
        $this->imageDirProduct      = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        if (defined('DIR_WS_PRODUCT_IMAGES')) {
            $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
        }
        if (defined('DIR_WS_ORIGINAL_IMAGES')) {
            $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
        }
        if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php')) {
            @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php';
            $major = PROJECT_VERSION_MAJOR;
            $minor = PROJECT_VERSION_MINOR;
            if (defined('EXPECTED_DATABASE_VERSION_MAJOR') && EXPECTED_DATABASE_VERSION_MAJOR != '') {
                $major = EXPECTED_DATABASE_VERSION_MAJOR;
            }
            if (defined('EXPECTED_DATABASE_VERSION_MINOR') && EXPECTED_DATABASE_VERSION_MINOR != '') {
                $minor = EXPECTED_DATABASE_VERSION_MINOR;
            }
            if ($major != '' && $minor != '') {
                $this->version = $major . '.' . $minor;
            }
        }
        $this->char_set = (defined('DB_CHARSET'))? DB_CHARSET : "";

    }
}

class LECM_Connector_Adapter_Virtuemart extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();

        @require_once LECM_STORE_BASE_DIR . '/configuration.php';
        $config = new JConfig();
        $this->setHostPort($config->host);
        $this->Username = $config->user;
        $this->Password = $config->password;
        $this->Dbname = $config->db;
        $this->tablePrefix = $config->dbprefix;

        $this->imageDir = 'components/com_virtuemart/shop_image/';
        $this->imageDirCategory    = $this->imageDir.'category/';
        $this->imageDirProduct      = $this->imageDir.'product/';
        $this->imageDirManufacturer = $this->imageDir.'manufacturer/';

        if (is_dir( LECM_STORE_BASE_DIR . 'images/stories/virtuemart/product')) {
            $this->imageDir = 'images/stories/virtuemart/';
            $this->imageDirCategory      = $this->imageDir . 'category/';
            $this->imageDirProduct    = $this->imageDir . 'product/';
            $this->imageDirManufacturer = $this->imageDir.'manufacturer/';
        }
        if (file_exists(LECM_STORE_BASE_DIR . '/administrator/components/com_virtuemart/version.php')) {
            $ver = file_get_contents(LECM_STORE_BASE_DIR . '/administrator/components/com_virtuemart/version.php');
            if (preg_match('/\$RELEASE.+\'(.+)\'/', $ver, $match) != 0) {
                $this->version = (string) $match[1];
            }
        }
    }

}

class LECM_Connector_Adapter_Woocommerce extends LECM_Connector_Adapter{
   function __construct(){
        parent::__construct();

        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');

        preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->Dbname = $match[1];
        preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->Username = $match[1];
        preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->Password = $match[1];
        preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->setHostPort($match[1]);
        preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->char_set = $match[1];
        preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
        $this->tablePrefix = $match[1];
        $this->imageDir = 'wp-content/uploads/';
        $this->imageDirCategory    = $this->imageDir;
        $this->imageDirProduct      = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        $this->version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'woocommerce_db_version'");
    }
}

class LECM_Connector_Adapter_Marketpress extends LECM_Connector_Adapter{
   function __construct(){
        parent::__construct();

        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');

        preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->Dbname = $match[1];
        preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->Username = $match[1];
        preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->Password = $match[1];
        preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->setHostPort($match[1]);
        preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->char_set = $match[1];
        preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
        $this->tablePrefix = $match[1];
        $this->imageDir = 'wp-content/uploads/';
        $this->imageDirCategory    = $this->imageDir;
        $this->imageDirProduct      = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        $this->version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'mp_version'");
    }
}

class LECM_Connector_Adapter_Xtcommerce extends LECM_Connector_Adapter{

   function __construct(){
        parent::__construct();
        if(!file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.org.php')){
            define('_VALID_CALL', 'TRUE');
            define('_SRV_WEBROOT', 'TRUE');
            @require_once LECM_STORE_BASE_DIR . 'conf' . DIRECTORY_SEPARATOR . 'config.php';
            @require_once LECM_STORE_BASE_DIR . 'conf' . DIRECTORY_SEPARATOR . 'paths.php';

            $this->Username = _SYSTEM_DATABASE_USER;
            $this->Password = _SYSTEM_DATABASE_PWD;
            $this->Dbname = _SYSTEM_DATABASE_DATABASE;
            $this->setHostPort(_SYSTEM_DATABASE_HOST);
            $this->tablePrefix = DB_PREFIX . '_';
            $this->imageDir = _SRV_WEB_IMAGES;

            $this->imageDirCategory    = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $version = $this->getCartVersionFromDb('config_value', 'config', "config_key = '_SYSTEM_VERSION'");
            if ($version != '') {
                $this->version = $version;
            }
        } else {
            @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
            $this->setHostPort(DB_SERVER);
            $this->Username = DB_SERVER_USERNAME;
            $this->Password = DB_SERVER_PASSWORD;
            $this->Dbname = DB_DATABASE;
            $this->imageDir = DIR_WS_IMAGES;
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            if (defined('DIR_WS_PRODUCT_IMAGES')) {
                $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
            }
            if (defined('DIR_WS_ORIGINAL_IMAGES')) {
                $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
            }
            $this->version = '3.0.0';
        }
    }
}

class LECM_Connector_Adapter_Xcart extends LECM_Connector_Adapter{

    function __construct(){
        parent::__construct();
        define('XCART_START', 1);

        if (file_exists(LECM_STORE_BASE_DIR . 'config.php')) {
            $config = file_get_contents(LECM_STORE_BASE_DIR . 'config.php');

            preg_match('/\$sql_host.+\'(.+)\';/', $config, $match);
            $this->setHostPort($match[1]);
            preg_match('/\$sql_user.+\'(.+)\';/', $config, $match);
            $this->Username = $match[1];
            preg_match('/\$sql_db.+\'(.+)\';/', $config, $match);
            $this->Dbname = $match[1];
            preg_match('/\$sql_password.+\'(.*)\';/', $config, $match);
            $this->Password = $match[1];
            $this->tablePrefix = 'xcart_';
            $this->imageDir = 'images/'; // xcart starting from 4.1.x hardcodes images location
            $this->imageDirCategory    = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;

            $this->version = $this->getCartVersionFromDb('value', 'config', "name = 'version'");
            preg_match('/\$blowfish_key.+\'(.*)\';/', $config, $match);
            $this->cookie_key = $match[1];
        }
        else{
            //X Cart 5
            if (file_exists(LECM_STORE_BASE_DIR . 'top.inc.php')){
                @require_once LECM_STORE_BASE_DIR . 'top.inc.php';
                $config = XLite::getInstance()->getOptions(array('database_details'));
                $this->setHostPort($config['hostspec']);
                $this->Username = $config['username'];
                $this->Dbname = $config['database'];
                $this->Password = $config['password'];
                $this->tablePrefix = $config['table_prefix'];
                $this->imageDir = 'images/';
                $this->imageDirCategory    = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->version = $this->getCartVersionFromDb('value', 'config', "name = 'version'");
            }
        }
    }
}

class LECM_Connector_Adapter_Opencart extends LECM_Connector_Adapter{

   function __construct() {
        parent::__construct();
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php'))) {
            @require_once LECM_STORE_BASE_DIR . 'configuration.php';
            $config = new JConfig();
            $this->setHostPort($config->host);
            $this->Username = $config->user;
            $this->Password = $config->password;
            $this->Dbname = $config->db;
            //$this->tablePrefix = $config->dbprefix;
            $first_prefix = $config->dbprefix;

            $configFileContent = $baseFileContent = '';
            if (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php')) {
                $configFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php');
            }
            if (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop/base.php')) {
                $baseFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop/base.php');
            }

            preg_match("/define\(\"\DB_PREFIX\"\, \'(.+)\'\)/", $configFileContent, $match);
            $second_prefix = str_replace("#__", "", $match[1]);
            $this->tablePrefix = $first_prefix . $second_prefix;
            $this->imageDir = 'components/com_mijoshop/opencart/image/';
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            preg_match('/\$version.+\'(.+)\';/', $baseFileContent, $match);
            $this->version = $match[1];
        } else {
            @require_once LECM_STORE_BASE_DIR . 'config.php';

            if (defined('DB_HOST')) {
                $this->setHostPort(DB_HOST);
            } else {
                $this->setHostPort(DB_HOSTNAME);
            }

            if (defined('DB_USER')) {
                $this->Username = DB_USER;
            } else {
                $this->Username = DB_USERNAME;
            }

            $this->Password = DB_PASSWORD;

            if (defined('DB_NAME')) {
                $this->Dbname = DB_NAME;
            } else {
                $this->Dbname = DB_DATABASE;
            }
            $this->tablePrefix = DB_PREFIX;
            $this->imageDir = '/image/';
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;

            $indexFileContent = '';
            $startupFileContent = '';

            if (file_exists(LECM_STORE_BASE_DIR . '/index.php')) {
                $indexFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/index.php');
            }

            if (file_exists(LECM_STORE_BASE_DIR . '/system/startup.php')) {
                $startupFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/system/startup.php');
            }

            if (preg_match("/define\('\VERSION\'\, \'(.+)\'\)/", $indexFileContent, $match) == 0) {
                preg_match("/define\('\VERSION\'\, \'(.+)\'\)/", $startupFileContent, $match);
            }

            if (count($match) > 0) {
                $this->version = $match[1];
            }
        }
    }
}

class LECM_Connector_Adapter_Loaded extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();
        if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'config.php')) {
            @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'config.php';
        } else {
            @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
        }

        if (defined('DB_SERVER')) {
            $this->setHostPort(DB_SERVER);
        } else {
            $this->setHostPort(DB_HOSTNAME);
        }

        if (defined('DB_SERVER_USERNAME')) {
            $this->Username = DB_SERVER_USERNAME;
        } else {
            $this->Username = DB_USERNAME;
        }

        $this->Password = DB_SERVER_PASSWORD;

        if (defined('DB_DATABASE')) {
            $this->Dbname = DB_DATABASE;
        } else {
            $this->Dbname = DB_DATABASE;
        }
        $this->tablePrefix = defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : "";
        if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'config.php')) {
            $this->imageDir = '/images/';
            $this->imageDirCategory = $this->imageDir . 'categories/';
            $this->imageDirProduct = $this->imageDir . 'products/originals/';
            $this->imageDirManufacturer = $this->imageDir . 'manufacturers/';
            $this->version = '7.0.0';
        } else {
            $this->imageDir = '/images/';
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $this->version = '6.5.0';
        }
    }

}

class LECM_Connector_Adapter_WPecommerce extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();

        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');
        preg_match("/define\(\'DB_NAME\', \'(.+)\'\);/", $config, $match);
        $this->Dbname = $match[1];
        preg_match("/define\(\'DB_USER\', \'(.+)\'\);/", $config, $match);
        $this->Username = $match[1];
        preg_match("/define\(\'DB_PASSWORD\', \'(.*)\'\);/", $config, $match);
        $this->Password = $match[1];
        preg_match("/define\(\'DB_HOST\', \'(.+)\'\);/", $config, $match);
        $this->setHostPort($match[1]);
        preg_match("/(table_prefix)(.*)(')(.*)(')(.*)/", $config, $match);
        $this->tablePrefix = $match[4];

        $version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'wpsc_version'");
        if ($version != '') {
            $this->version = $version;
        } else {
            if (file_exists(LECM_STORE_BASE_DIR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-shopping-cart' . DIRECTORY_SEPARATOR . 'wp-shopping-cart.php')) {
                $conf = file_get_contents(LECM_STORE_BASE_DIR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-shopping-cart' . DIRECTORY_SEPARATOR . 'wp-shopping-cart.php');
                preg_match("/define\('WPSC_VERSION.*/", $conf, $match);
                if (isset($match[0]) && !empty($match[0])) {
                    preg_match("/\d.*/", $match[0], $project);
                    if (isset($project[0]) && !empty($project[0])) {
                        $version = $project[0];
                        $version = str_replace(array(' ', '-', '_', "'", ');', ')', ';'), '', $version);
                        if ($version != '') {
                            $this->version = strtolower($version);
                        }
                    }
                }
            }
        }

        if (file_exists(LECM_STORE_BASE_DIR . 'wp-content/plugins/shopp/Shopp.php') || file_exists(LECM_STORE_BASE_DIR . 'wp-content/plugins/wp-e-commerce/editor.php')
        ) {
            $this->imageDir = 'wp-content/uploads/wpsc/';
            $this->imageDirCategory = $this->imageDir . 'category_images/';
            $this->imageDirProduct = $this->imageDir . 'product_images/';
            $this->manufacturersImagesDir = $this->imageDir;
        } elseif (file_exists(LECM_STORE_BASE_DIR . 'wp-content/plugins/wp-e-commerce/wp-shopping-cart.php')) {
            $this->imageDir = 'wp-content/uploads/';
            $this->imageDirCategory = $this->imageDir . 'wpsc/category_images/';
            $this->imageDirProduct = $this->imageDir;
            $this->manufacturersImagesDir = $this->imageDir;
        } else {
            $this->imageDir = 'images/';
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->manufacturersImagesDir = $this->imageDir;
        }
    }

}

class LECM_Connector_Adapter_Cscart extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();
        $config = file_get_contents(LECM_STORE_BASE_DIR . '/config.local.php');
        preg_match("/config\[\'db_host\'\].+\'(.+)\';/", $config, $match);
        $this->setHostPort($match[1]);
        preg_match("/config\[\'db_user\'\].+\'(.+)\';/", $config, $match);
        $this->Username = $match[1];
        preg_match("/config\[\'db_password\'\].+\'(.*)\';/", $config, $match);
        $this->Password = $match[1];
        preg_match("/config\[\'db_name\'\].+\'(.+)\';/", $config, $match);
        $this->Dbname = $match[1];
        preg_match("/config\[\'table_prefix\'\].+\'(.+)\';/", $config, $match);
        if ($match) {
            $this->tablePrefix = $match[1];
        } else {
            $this->tablePrefix = 'cscart_';
        }
        $this->imageDir = '/images/detailed/';
        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        $config_local = file_get_contents(LECM_STORE_BASE_DIR . '/config.php');
        preg_match("/define\(\'PRODUCT_VERSION\', \'(.+)\'\);/", $config_local, $match);
        $this->version = $match[1];
    }

}

class LECM_Connector_Adapter_Prestashop extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();
        @require_once LECM_STORE_BASE_DIR . '/config/settings.inc.php';

        if (defined('_DB_SERVER_')) {
            $this->setHostPort(_DB_SERVER_);
        } else {
            $this->setHostPort(DB_HOSTNAME);
        }

        if (defined('_DB_USER_')) {
            $this->Username = _DB_USER_;
        } else {
            $this->Username = DB_USERNAME;
        }

        $this->Password = _DB_PASSWD_;

        if (defined('_DB_NAME_')) {
            $this->Dbname = _DB_NAME_;
        } else {
            $this->Dbname = DB_DATABASE;
        }
        $this->tablePrefix = _DB_PREFIX_;
        $this->imageDir = '/img/';
        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        $this->version = _PS_VERSION_;
        $this->cookie_key = _COOKIE_KEY_;
    }

}

class LECM_Connector_Adapter_Magento extends LECM_Connector_Adapter {
   function __construct() {
        parent::__construct();
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml');
        preg_match("/\<host\>\<\!\[CDATA\[(.+)\]\]>\<\/host\>/", $config, $match);
        $this->setHostPort($match[1]);
        preg_match("/\<username\>\<\!\[CDATA\[(.+)\]\]>\<\/username\>/", $config, $match);
        $this->Username = $match[1];
        preg_match("/\<password\>\<\!\[CDATA\[(.*)\]\]>\<\/password\>/", $config, $match);
        $this->Password = $match[1];
        preg_match("/\<dbname\>\<\!\[CDATA\[(.+)\]\]>\<\/dbname\>/", $config, $match);
        $this->Dbname = $match[1];
        preg_match("/\<table_prefix\>\<\!\[CDATA\[(.*)\]\]>\<\/table_prefix\>/", $config, $match);
        $this->tablePrefix = $match[1];
        $this->imageDir = '/media/catalog/';
        $this->imageDirCategory = $this->imageDir . 'category/';
        $this->imageDirProduct = $this->imageDir . 'product/';
        $this->imageDirManufacturer = $this->imageDir;
		if (file_exists(LECM_STORE_BASE_DIR . 'app/Mage.php')) {
			$ver = file_get_contents(LECM_STORE_BASE_DIR . 'app/Mage.php');
			if (preg_match("/getVersionInfo[^}]+\'major\' *=> *\'(\d+)\'[^}]+\'minor\' *=> *\'(\d+)\'[^}]+\'revision\' *=> *\'(\d+)\'[^}]+\'patch\' *=> *\'(\d+)\'[^}]+}/s", $ver, $match) == 1) {
				$mageVersion = $match[1] . '.' . $match[2] . '.' . $match[3] . '.' . $match[4];
				$this->version = $mageVersion;
				unset($match);
			}
		}
    }
}

class LECM_Connector_Adapter_Cubecart extends LECM_Connector_Adapter {

   function __construct() {
        parent::__construct();
        $config = file_get_contents(LECM_STORE_BASE_DIR . '/includes/global.inc.php');
        preg_match("/glob\[\'dbhost\'\].+\'(.+)\';/", $config, $match);
        $this->setHostPort($match[1]);
        preg_match("/glob\[\'dbusername\'\].+\'(.+)\';/", $config, $match);
        $this->Username = $match[1];
        preg_match("/glob\[\'dbpassword\'\].+\'(.*)\';/", $config, $match);
        $this->Password = $match[1];
        preg_match("/glob\[\'dbdatabase\'\].+\'(.+)\';/", $config, $match);
        $this->Dbname = $match[1];
        preg_match("/glob\[\'dbprefix\'\].+\'(.+)\';/", $config, $match);
        if ($match && $match[1]) {
            $this->tablePrefix = $match[1] . 'CubeCart_';
        } else {
            $this->tablePrefix = 'CubeCart_';
        }
        $this->imageDir = '/images/source/';
        if (file_exists(LECM_STORE_BASE_DIR . '/ini.inc.php')) {
            $config_local = file_get_contents(LECM_STORE_BASE_DIR . '/ini.inc.php');
        } else {
            $config_local = file_get_contents(LECM_STORE_BASE_DIR . '/includes/ini.inc.php');
        }
        preg_match("/define\(\'CC_VERSION\', \'(.+)\'\);/", $config_local, $match);
        if ($match) {
            $this->version = $match[1];
        } else {
            preg_match("/ini\[\'ver\'\].+\'(.*)\';/", $config_local, $match);
            $this->version = $match[1];
            $this->imageDir = '/images/uploads/';
        }
        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
    }

}

class LECM_Connector_Adapter_Drupalcart extends LECM_Connector_Adapter {

    function __construct() {
        parent::__construct();
        @include_once LECM_STORE_BASE_DIR . 'sites/default/settings.php';
        if(isset($databases)){
            $_database = $databases['default']['default'];

            $this->setHostPort($_database['host']);

            $this->Username = $_database['username'];

            $this->Password = $_database['password'];

            $this->Dbname = $_database['database'];

            $this->tablePrefix = $_database['prefix'];
            $this->version = '1.5';
        }
        $this->imageDir = '/sites/default/files/';

        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
    }

}


class LECM_Connector_Adapter_Ubercart extends LECM_Connector_Adapter {

    function __construct() {
        parent::__construct();
        @include_once LECM_STORE_BASE_DIR . 'sites/default/settings.php';
        if(isset($databases)){
            $_database = $databases['default']['default'];

            $this->setHostPort($_database['host']);

            $this->Username = $_database['username'];

            $this->Password = $_database['password'];

            $this->Dbname = $_database['database'];

            $this->tablePrefix = $_database['prefix'];
            $this->version = '3.6';
        }else{
            $db_url = str_replace('mysqli://','',$db_url);
            $info = explode('/',$db_url);
            $this->Dbname = $info[1];
            $info2 = explode('@',$info[0]);
            $this->setHostPort($info2[1]);
            $info3 = explode(':',$info2[0]);
            $this->Username = $info3[0];
            if(isset($info3[1])){
                $this->Password = $info3[1];
            }else{
                $this->Password = '';
            }
            $this->tablePrefix  = $db_prefix;
            $this->version = '2.13';
        }
        $this->imageDir = '/sites/default/files/';

        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
    }

}
class LECM_Connector_Adapter_Interspire extends LECM_Connector_Adapter {

    public function __construct() {
        parent::__construct();

        @require_once LECM_STORE_BASE_DIR . 'config/config.php';
        $this->setHostPort($GLOBALS['ISC_CFG']['dbServer']);
        $this->Username = $GLOBALS['ISC_CFG']['dbUser'];
        $this->Password = $GLOBALS['ISC_CFG']['dbPass'];
        $this->Dbname = $GLOBALS['ISC_CFG']['dbDatabase'];
        $this->tablePrefix = $GLOBALS['ISC_CFG']['tablePrefix'];
        $this->imageDir = $GLOBALS['ISC_CFG']['ImageDirectory'];
        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;

        define('DEFAULT_LANGUAGE_ISO2',$GLOBALS['ISC_CFG']['Language']);

        $version = $this->getCartVersionFromDb('database_version', $GLOBALS['ISC_CFG']['tablePrefix'] . 'config', '1');
        if ($version != '') {
            $this->version = $version;
        }
    }

}


class LECM_Connector_Response {

   function __construct() {
        
    }

   function createResponse($result, $msg, $obj) {
        $response = array();
        $response['result'] = $result;
        $response['msg'] = $msg;
        $response['object'] = $obj;
        echo base64_encode(serialize($response));
        return;
    }

   function error($msg = null, $obj = null) {
        $this->createResponse('error', $msg, $obj);
    }

   function success($msg = null, $obj = null) {
        $this->createResponse('success', $msg, $obj);
    }

}

error_reporting(1);

if (!isset($_SERVER)) {
    $_GET = &$HTTP_GET_VARS;
    $_POST = &$HTTP_POST_VARS;
    $_ENV = &$HTTP_ENV_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
    $_COOKIE = &$HTTP_COOKIE_VARS;
    $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
}

define('LECM_ROOT_BASE_NAME', basename(getcwd()));
define('LECM_CONNECTOR_BASE_DIR', dirname(__FILE__));
define('LECM_STORE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

$connector = new LECM_Connector();
$connector->run();
