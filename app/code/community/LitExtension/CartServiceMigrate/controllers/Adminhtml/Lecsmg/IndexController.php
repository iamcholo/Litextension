<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartServiceMigrate_Adminhtml_Lecsmg_IndexController
    extends Mage_Adminhtml_Controller_Action{

    protected $_user_id = null;
    protected $_cart = null;
    protected $_notice = null;
    protected $_import_action = array('taxes', 'manufacturers', 'categories', 'products', 'customers', 'orders', 'reviews');
    protected $_next_action = array(
        'taxes' => 'manufacturers',
        'manufacturers' => 'categories',
        'categories' => 'products',
        'products' => 'customers',
        'customers' => 'orders',
        'orders' => 'reviews',
        'reviews' => false
    );
    protected $_simple_action = array(
        'taxes' => 'tax',
        'manufacturers' => 'manufacturer',
        'categories' => 'category',
        'products' => 'product',
        'customers' => 'customer',
        'orders' => 'order',
        'reviews' => 'review'
    );

    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('litextension/cartservicemigrate');
    }

    public function indexAction(){
        $this->_initCart();
        $this->_notice['setting'] = Mage::getStoreConfig('lecsmg/general');
        $this->loadLayout();
        $this->_setActiveMenu('litextension')
            ->_title(Mage::helper('lecsmg')->__('Cart Service Migrate'));
        $this->getLayout()->getBlock('lecsmg.index')->setNotice($this->_notice);
        $this->renderLayout();
    }

    /**
     * Router to function process by params action
     */
    public function importAction(){
        $params = $this->getRequest()->getParams();
        if(isset($params['action']) && $params['action'] != ''){
            $action = $params['action'];
            if(in_array($action, $this->_import_action)){
                $this->_import($action);
            } else {
                $function = '_'.$action;
                $this->$function();
            }
        } else {
            $this->_redirect('adminhtml/lecsmg_index/index');
        }
        return ;
    }

    protected function _api(){
        $cart_type = $this->getRequest()->getParam('cart_type');
        $response = $this->_defaultResponse();
        $response['result'] = 'show';
        if(!$cart_type){
            $response['html'] = "Cart type isn't supporting.";
            $this->_responseAjaxJson($response);
            return ;
        }
        $router = Mage::getModel('lecsmg/cart');
        $model = "lecsmg/" . $router->getCart($cart_type);
        $cart = Mage::getModel($model);
        $api_data = $cart->getApiData();
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecsmg/adminhtml_index', 'lecsmg.api', array('template' => 'litextension/cartservicemigrate/api.phtml'));
        $html = "";
        if($block){
            $html = $block->setApiData($api_data)->toHtml();
        }
        $response['html'] = $html;
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _resume(){
        $response = $this->_defaultResponse();
        $this->_initCart();
        $this->_notice['msg_start'] = $this->_cart->consoleSuccess("Resuming ...");
        $this->_notice['setting'] = Mage::getStoreConfig('lecsmg/general');
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecsmg/adminhtml_index', 'lecsmg.import', array('template' => 'litextension/cartservicemigrate/import.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        $this->_setStartResumeTime();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase());
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    /**
     * Show display to success step setup in admin gui
     */
    protected function _setup(){
        $response = array();
        $router = Mage::getModel('lecsmg/cart');
        $del = $this->_deleteNotice($router);
        if(!$del){
            return $this->_responseAjaxJson($router->errorDatabase());
        }
        $this->_notice = $this->_getNotice($router);
        $params = $this->getRequest()->getParams();
        $this->_notice['config']['cart_type'] = $params['cart_type'];
        $this->_notice['config']['cart_url'] = trim(rtrim($params['cart_url'], '/'));
        $this->_notice['config']['api'] = $params['api'];
        $model = "lecsmg/" . $router->getCart($this->_notice['config']['cart_type']);
        $this->_cart = Mage::getModel($model);
        $this->_cart->setNotice($this->_notice);
        $result = $this->_cart->displayConfig();
        if($result['result'] != 'success'){
            return $this->_responseAjaxJson($result);
        }
        $this->_notice = $this->_cart->getNotice();
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecsmg/adminhtml_index', 'lecsmg.config', array('template' => 'litextension/cartservicemigrate/config.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            return $this->_responseAjaxJson($router->errorDatabase());
        }
        try{
            Mage::getModel('core/config')->saveConfig('lecupd/general/type', $this->_notice['config']['cart_type']);
            Mage::getModel('core/config')->saveConfig('lecupd/general/url', $this->_notice['config']['cart_url']);
        }catch (Exception $e){}
        return $this->_responseAjaxJson($response);
    }

    /**
     * Show display to success step config in admin gui
     */
    protected function _config(){
        $response = array();
        $this->_initCart();
        $params = $this->getRequest()->getParams();
        $result = $this->_cart->displayConfirm($params);
        if($result['result'] != 'success'){
            return $this->_responseAjaxJson($result);
        }
        $this->_notice = $this->_cart->getNotice();
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecsmg/adminhtml_index', 'lecsmg.confirm', array('template' => 'litextension/cartservicemigrate/confirm.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            return $this->_responseAjaxJson($this->_cart->errorDatabase());
        }
        return $this->_responseAjaxJson($response);
    }

    /**
     * Show display to success step confirm in admin gui
     */
    protected function _confirm(){
        $this->_initCart();
        $response = array();
        $result = $this->_cart->displayImport();
        if($result['result'] != 'success'){
            return $this->_responseAjaxJson($result);
        }
        $this->_notice = $this->_cart->getNotice();
        if($this->_notice['config']['add_option']['clear_data']){
            $msg = $this->_cart->consoleSuccess("Clearing store ...");
        } else {
            $msg = $this->_cart->getMsgStartImport('taxes');
        }
        $this->_notice['msg_start'] = $msg;
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecsmg/adminhtml_index', 'lecsmg.import', array('template' => 'litextension/cartservicemigrate/import.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            return $this->_responseAjaxJson($this->_cart->errorDatabase());
        }
        return $this->_responseAjaxJson($response);
    }
    /**
     * Process action clear store
     */
    protected function _clear(){
        $this->_initCart();
        $response = $this->_cart->clearStore();
        $this->_notice = $this->_cart->getNotice();
        $save = $this->_saveNotice();
        if(!$save){
            return $this->_responseAjaxJson($this->_cart->errorDatabase(true));
        }
        return $this->_responseAjaxJson($response);
    }

    /**
     * Process config currencies
     */
    protected function _currencies(){
        $this->_initCart();
        $this->_cart->configCurrency();
        $this->_notice = $this->_cart->getNotice();
        if($this->_notice['config']['import']['taxes']){
            $this->_cart->prepareImportTaxes();
        }
        $this->_notice['taxes']['time_start'] = time();
        $this->_notice['fn_resume'] = 'importTaxes';
        $save_user = $this->_saveNotice();
        if(!$save_user){
            $response = $this->_cart->errorDatabase();
            return $this->_responseAjaxJson($response);
        }
        $response = array('result' => 'success');
        return $this->_responseAjaxJson($response);
    }

    /**
     * Process import by action
     */
    protected function _import($action){
        $this->_initCart();
        $response = $this->_defaultResponse();
        $this->_notice['is_running'] = true;
        if(!$this->_notice['config']['import'][$action]){
            $next_action = $this->_next_action[$action];
            if($next_action && $this->_notice['config']['import'][$next_action]){
                $prepare_next = 'prepareImport' . ucfirst($next_action);
                $this->_cart->$prepare_next();
                $this->_notice[$next_action]['time_start'] = time();
            }
            if($next_action){
                $fn_resume = 'import' . ucfirst($next_action);
                $this->_notice['fn_resume'] = $fn_resume;
            }
            if($action == 'reviews'){
                $this->_notice['is_running'] = false;
                if(!LitExtension_CartServiceMigrate_Model_Custom::DEMO_MODE){
                    $this->_cart->saveRecentNotice($this->_notice);
                }
                $this->_cart->updateApi();
                $response['msg'] .= $this->_cart->consoleSuccess('Finished migration!');
            }
            $notice = $this->_cart->getNotice();
            $this->_notice['extend'] = $notice['extend'];
            $save_user = $this->_saveNotice();
            if(!$save_user){
                $response = $this->_cart->errorDatabase();
                return $this->_responseAjaxJson($response);
            }
            $response['result'] = 'no-import';
            return $this->_responseAjaxJson($response);
        }
        $total = $this->_notice[$action]['total'];
        $imported = $this->_notice[$action]['imported'];
        $error = $this->_notice[$action]['error'];
        $id_src = $this->_notice[$action]['id_src'];
        $simple_action = $this->_simple_action[$action];
        $next_action = $this->_next_action[$action];
        if($imported < $total){
            $fn_get_main = 'get' . ucfirst($action) . 'Main';
            $fn_get_ext = 'get' . ucfirst($action) . 'Ext';
            $fn_get_id = 'get' .ucfirst($simple_action) . 'Id';
            $fn_check_import = 'check' . ucfirst($simple_action) . 'Import';
            $fn_convert = 'convert' . ucfirst($simple_action);
            $fn_import = 'import' . ucfirst($simple_action);
            $fn_after_save = 'afterSave' . ucfirst($simple_action);
            $fn_addition = 'addition' . ucfirst($simple_action);

            $mains = $this->_cart->$fn_get_main();
            if($mains['result'] != 'success'){
                return $this->_responseAjaxJson($mains);
            }
            $ext = $this->_cart->$fn_get_ext($mains);
            if($ext['result'] != 'success'){
                return $this->_responseAjaxJson($ext);
            }
            foreach($mains['data'] as $main){
                if($imported >= $total){
                    break ;
                }
                $id_src = $this->_cart->$fn_get_id($main, $ext);
                $imported++;
                if($this->_cart->$fn_check_import($main, $ext)){
                    continue ;
                }
                $convert = $this->_cart->$fn_convert($main, $ext);
                if($convert['result'] == 'error'){
                    return $this->_responseAjaxJson($convert);
                }
                if($convert['result'] == 'warning'){
                    $error++;
                    $response['msg'] .= $convert['msg'];
                    continue ;
                }
                if($convert['result'] == 'pass'){
                    continue ;
                }
                if($convert['result'] == 'wait'){
                    $notice = $this->_cart->getNotice();
                    $this->_notice['extend'] = $notice['extend'];
                    $response['result'] = 'process';
                    $response[$action] = $this->_notice[$action];
                    $save_user = $this->_saveNotice();
                    if(!$save_user){
                        $response = $this->_cart->errorDatabase();
                        $this->_responseAjaxJson($response);
                        return ;
                    }
                    $this->_responseAjaxJson($response);
                    return ;
                }
                if($convert['result'] == 'success'){
                    $data = $convert['data'];
                    $import = $this->_cart->$fn_import($data, $main, $ext);
                    if($import['result'] == 'error'){
                        return $this->_responseAjaxJson($import);
                    }
                    if($import['result'] != 'success'){
                        $error++;
                        $response['msg'] .= $import['msg'];
                        continue ;
                    }
                    $mage_id = $import['mage_id'];
                    $this->_cart->$fn_after_save($mage_id, $data, $main, $ext);
                }
                if($convert['result'] == 'addition'){
                    $data = $convert['data'];
                    $add_result = $this->_cart->$fn_addition($data, $main, $ext);
                    if($add_result['result'] != 'success'){
                        $notice = $this->_cart->getNotice();
                        $this->_notice['extend'] = $notice['extend'];
                        $response['result'] = 'process';
                        $response[$action] = $this->_notice[$action];
                        $save_user = $this->_saveNotice();
                        if(!$save_user){
                            $response = $this->_cart->errorDatabase();
                            $this->_responseAjaxJson($response);
                            return ;
                        }
                        $this->_responseAjaxJson($response);
                        return ;
                    }
                }
            }
            $response['result'] = 'process';
            $this->_notice[$action]['point'] = $this->_cart->getPoint($total, $imported);
        } else {
            $response['result'] = 'success';
            $msg_time = $this->_cart->createTimeToShow(time() - $this->_notice[$action]['time_start']);
            $response['msg'] .= $this->_cart->consoleSuccess('Finished importing ' . $action . '! Run time: ' . $msg_time);
            $response['msg'] .= $this->_cart->getMsgStartImport($next_action);
            if($next_action){
                $this->_notice[$next_action]['time_start'] = time();
            }
            $this->_notice[$action]['finish'] = true;
            $this->_notice[$action]['point'] = $this->_cart->getPoint($total, $imported, true);
            if($next_action){
                $this->_notice['fn_resume'] = 'import' . ucfirst($next_action);
            }
            if($next_action && $this->_notice['config']['import'][$next_action]){
                $fn_prepare = 'prepareImport' . ucfirst($next_action);
                $this->_cart->$fn_prepare();
            }
        }
        $this->_notice[$action]['imported'] = $imported;
        $this->_notice[$action]['id_src'] = $id_src;
        $this->_notice[$action]['error'] = $error;
        $response[$action] = $this->_notice[$action];
        $notice = $this->_cart->getNotice();
        $this->_notice['extend'] = $notice['extend'];
        if($action == 'reviews' && $response['result'] == 'success'){
            $this->_notice['is_running'] = false;
            if(!LitExtension_CartServiceMigrate_Model_Custom::DEMO_MODE){
                $this->_cart->saveRecentNotice($this->_notice);
            }
            $this->_cart->updateApi();
        }
        $save_user = $this->_saveNotice();
        if(!$save_user){
            $response = $this->_cart->errorDatabase();
            return $this->_responseAjaxJson($response);
        }
        return $this->_responseAjaxJson($response);
    }

    /**
     * Process after finish migration
     */
    protected function _finish(){
        $this->_initCart();
        $response = $this->_cart->finishImport();
        $this->_deleteNotice($this->_cart);
        return $this->_responseAjaxJson($response);
    }

    /**
     * Router to model cart process migration
     */
    protected function _initCart(){
        $router = Mage::getModel('lecsmg/cart');
        $this->_notice = $this->_getNotice($router);
        $cart_type = $this->_notice['config']['cart_type'];
        $model = "lecsmg/" . $router->getCart($cart_type);
        $this->_cart = Mage::getModel($model);
        $this->_cart->setNotice($this->_notice);
        return $this;
    }

    /**
     * Get migration notice by mode
     */
    protected function _getNotice($router){
        $session = Mage::getSingleton('admin/session');
        if($user = $session->getUser()){
            $this->_user_id = $user->getUserId();
        }
        if(LitExtension_CartServiceMigrate_Model_Custom::DEMO_MODE){
            $notice = $session->getLeCSMg();
        } else {
            $notice = $router->getUserNotice($this->_user_id);
        }
        if(!$notice){
            $notice = $router->getDefaultNotice();
        }
        return $notice;
    }

    /**
     * Save migration notice by mode
     */
    protected function _saveNotice(){
        $session = Mage::getSingleton('admin/session');
        $user = $session->getUser();
        if($user){
            $this->_user_id = $user->getUserId();
        }
        if(LitExtension_CartServiceMigrate_Model_Custom::DEMO_MODE){
            $session->setLeCSMg($this->_notice);
            return true;
        } else {
            return $this->_cart->saveUserNotice($this->_user_id, $this->_notice);
        }
    }

    /**
     * Delete migration notice by mode
     */
    protected function _deleteNotice($router){
        $session = Mage::getSingleton('admin/session');
        if(!$this->_user_id){
            if($user = $session->getUser()){
                $this->_user_id = $user->getUserId();
            }
        }
        if(LitExtension_CartServiceMigrate_Model_Custom::DEMO_MODE){
            $session->unsLeCSMg();
            return true;
        } else {
            return $router->deleteUserNotice($this->_user_id);
        }
    }
    
    protected function _setStartResumeTime() {
        $session = Mage::getSingleton('admin/session');
        $start_time = microtime(true);
        $session->setLeCSTime($start_time);
    }

    /**
     * Convert array to json and response
     */
    protected function _responseAjaxJson($data){
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
        return ;
    }

    /**
     * Construct of response
     */
    protected function _defaultResponse(){
        return array(
            'result' => '',
            'msg' => '',
            'html' => '',
            'elm' => ''
        );
    }

}