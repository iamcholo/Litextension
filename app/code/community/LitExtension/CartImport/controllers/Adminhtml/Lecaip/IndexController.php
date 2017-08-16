<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Adminhtml_Lecaip_IndexController
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
        return Mage::getSingleton('admin/session')->isAllowed('litextension/cartimport');
    }

    public function indexAction(){
        $this->_initCart();
        $this->_notice['setting'] = Mage::getStoreConfig('lecaip/general');
        $this->loadLayout();
        $this->_setActiveMenu('litextension')
            ->_title(Mage::helper('lecaip')->__('Cart Importer'));
        $this->getLayout()->getBlock('lecaip.index')->setNotice($this->_notice);
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
            $this->_redirect('adminhtml/lecaip_index/index');
        }
        return ;
    }

    protected function _resume(){
        $response = $this->_defaultResponse();
        $this->_initCart();
        $this->_notice['msg_start'] = $this->_cart->consoleSuccess("Resuming ...");
        $this->_notice['setting'] = Mage::getStoreConfig('lecaip/general');
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecaip/adminhtml_index', 'lecaip.import', array('template' => 'litextension/cartimport/import.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase());
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _displayUpload(){
        $cart_type = $this->getRequest()->getParam('cart_type');
        $response = $this->_defaultResponse();
        $response['result'] = 'show';
        if(!$cart_type){
            $response['html'] = "Cart type isn't supporting.";
            $this->_responseAjaxJson($response);
            return ;
        }
        $router = Mage::getModel('lecaip/cart');
        $model = "lecaip/" . $router->getCart($cart_type);
        $cart = Mage::getModel($model);
        $upload = $cart->getListUpload();
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecaip/adminhtml_index', 'lecaip.upload', array('template' => 'litextension/cartimport/upload.phtml'));
        $html = "";
        if($block){
            $html = $block->setListUpload($upload)->setCartType($cart_type)->toHtml();
        }
        $response['html'] = $html;
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _upload(){
        $response = $this->_defaultResponse();
        $router = Mage::getModel('lecaip/cart');
        $notice = $this->_getNotice($router);
        if($notice['config']['folder']){
            $pre_folder = Mage::getBaseDir('media') . '/litextension/cartimport/' . $notice['config']['folder'];
            $router->deleteDir($pre_folder);
            if($notice['config']['cart_type']){
                $model = 'lecaip/' . $router->getCart($notice['config']['cart_type']);
                $cart = Mage::getModel($model);
                $cart->setNotice($notice);
                $cart->clearPreSection();
            }
        }
        $del = $this->_deleteNotice($router);
        if(!$del){
            $this->_responseAjaxJson($router->errorDatabase());
            return ;
        }
        $this->_notice = $this->_getNotice($router);
        $params = $this->getRequest()->getParams();
        $this->_notice['config']['cart_type'] = $params['cart_type'];
        $this->_notice['config']['cart_url'] = trim(rtrim($params['cart_url'], '/'));
        $this->_notice['config']['folder'] = LitExtension_CartImport_Model_Custom::FOLDER_UPLOAD ? LitExtension_CartImport_Model_Custom::FOLDER_UPLOAD : $router->createFolderUpload($this->_notice['config']['cart_url']);
        $model = "lecaip/" . $router->getCart($this->_notice['config']['cart_type']);
        $this->_cart = Mage::getModel($model);
        $allowExtension = $this->_cart->getAllowExtensions();
        $list_upload = $this->_cart->getListUpload();
        $this->_notice['config']['file_data'] = $list_upload;
        $folder_upload = Mage::getBaseDir('media') . LitExtension_CartImport_Model_Cart::FOLDER_SUFFIX . $this->_notice['config']['folder'];
        $upload_msg = array();
        foreach($list_upload as $item){
            $upload_name = $item['value'];
            if(isset($_FILES[$upload_name])){
                $upload_name_file = $this->_cart->getUploadFileName($upload_name);
                $result = $this->_uploadFile($_FILES[$upload_name], $folder_upload, $upload_name_file, $allowExtension);
                if($result['result'] == 'success'){
                    $this->_notice['config']['files'][$upload_name] = true;
                    $upload_msg[$upload_name] = array(
                        'elm' => '#ur-' . $upload_name,
                        'msg' => "<div class='uir-success'> Uploaded successfully.</div>"
                    );
                } else {
                    $this->_notice['config']['files'][$upload_name] = false;
                    $this->_notice['config']['upload_success'] = false;
                    $upload_msg[$upload_name] = array(
                        'elm' => '#ur-' . $upload_name,
                        'msg' => "<div class='uir-warning'> Upload failed.</div>"
                    );
                }
            } else {
                $this->_notice['config']['files'][$upload_name] = false;
            }
        }
        $this->_cart->setNotice($this->_notice);
        $upload_info = $this->_cart->getUploadInfo($upload_msg);
        $this->_notice = $this->_cart->getNotice();
        $response['msg'] = $upload_info['msg'];
        $response['result'] = 'success';
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase());
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _setup(){
        $this->_initCart();
        $this->_notice = $this->_cart->getNotice();
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecaip/adminhtml_index', 'lecaip.csv', array('template' => 'litextension/cartimport/csv.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response = $this->_defaultResponse();
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase());
            return ;
        }
        try{
            Mage::getModel('core/config')->saveConfig('lecupd/general/type', $this->_notice['config']['cart_type']);
            Mage::getModel('core/config')->saveConfig('lecupd/general/url', $this->_notice['config']['cart_url']);
        }catch (Exception $e){}
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _csv(){
        $this->_initCart();
        $response = $this->_cart->storageCsv();
        $this->_notice = $this->_cart->getNotice();
        if($response['result'] == 'success'){
            $result = $this->_cart->displayConfig();
            $this->_notice = $this->_cart->getNotice();
            if($result['result'] != 'success'){
                 $this->_responseAjaxJson($result);
                return ;
            }
            $this->loadLayout();
            $block = $this->getLayout()->createBlock('lecaip/adminhtml_index', 'lecaip.config', array('template' => 'litextension/cartimport/config.phtml'));
            $html = "";
            if($block){
                $html = $block->setNotice($this->_notice)->toHtml();
            }
            $response['html'] = $html;
        }
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase(true));
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _config(){
        $response = $this->_defaultResponse();
        $this->_initCart();
        $params = $this->getRequest()->getParams();
        $result = $this->_cart->displayConfirm($params);
        if($result['result'] != 'success'){
            $this->_responseAjaxJson($result);
            return ;
        }
        $this->_notice = $this->_cart->getNotice();
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecaip/adminhtml_index', 'lecaip.confirm', array('template' => 'litextension/cartimport/confirm.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase());
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _confirm(){
        $response = $this->_defaultResponse();
        $this->_initCart();
        $result = $this->_cart->displayImport();
        if($result['result'] != 'success'){
            $this->_responseAjaxJson($result);
            return ;
        }
        $this->_notice = $this->_cart->getNotice();
        if($this->_notice['config']['add_option']['clear_data']){
            $msg = $this->_cart->consoleSuccess("Clearing store ...");
        } else {
            $msg = $this->_cart->getMsgStartImport('taxes');
        }
        $this->_notice['msg_start'] = $msg;
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('lecaip/adminhtml_index', 'lecaip.import', array('template' => 'litextension/cartimport/import.phtml'));
        $html = "";
        if($block){
            $html = $block->setNotice($this->_notice)->toHtml();
        }
        $response['result'] = 'success';
        $response['html'] = $html;
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase());
            return  ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _clear(){
        $this->_initCart();
        $response = $this->_cart->clearStore();
        $this->_notice = $this->_cart->getNotice();
        $save = $this->_saveNotice();
        if(!$save){
            $this->_responseAjaxJson($this->_cart->errorDatabase(true));
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
    }

    protected function _currencies(){
        $response = $this->_defaultResponse();
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
            $this->_responseAjaxJson($response);
            return ;
        }
        $response['result'] = 'success';
        $this->_responseAjaxJson($response);
        return ;
    }

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
                $this->_cart->updateApi();
                $this->_notice['is_running'] = false;
                $response['msg'] .= $this->_cart->consoleSuccess('Finished migration!');
            }
            $notice = $this->_cart->getNotice();
            $this->_notice['extend'] = $notice['extend'];
            $save_user = $this->_saveNotice();
            if(!$save_user){
                $response = $this->_cart->errorDatabase();
                $this->_responseAjaxJson($response);
                return ;
            }
            $response['result'] = 'no-import';
            $this->_responseAjaxJson($response);
            return ;
        }
        $total = $this->_notice[$action]['total'];
        $imported = $this->_notice[$action]['imported'];
        $error = $this->_notice[$action]['error'];
        $id_src = $this->_notice[$action]['id_src'];
        $simple_action = $this->_simple_action[$action];
        $next_action = $this->_next_action[$action];
        if($imported < $total){
            $fn_get_main = 'get' . ucfirst($action);
            $fn_get_id = 'get' .ucfirst($simple_action) . 'Id';
            $fn_check_import = 'check' . ucfirst($simple_action) . 'Import';
            $fn_convert = 'convert' . ucfirst($simple_action);
            $fn_import = 'import' . ucfirst($simple_action);
            $fn_after_save = 'afterSave' . ucfirst($simple_action);

            $mains = $this->_cart->$fn_get_main();
            if($mains['result'] != 'success'){
                $this->_responseAjaxJson($mains);
                return ;
            }
            foreach($mains['data'] as $main){
                if($imported >= $total){
                    break ;
                }
                $id_src = $this->_cart->$fn_get_id($main);
                $imported++;
                if($this->_cart->$fn_check_import($main)){
                    continue ;
                }
                $convert = $this->_cart->$fn_convert($main);
                if($convert['result'] == 'error'){
                    $this->_responseAjaxJson($convert);
                    return ;
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
                $data = $convert['data'];
                $import = $this->_cart->$fn_import($data, $main);
                if($import['result'] == 'error'){
                    $this->_responseAjaxJson($import);
                    return ;
                }
                if($import['result'] != 'success'){
                    $error++;
                    $response['msg'] .= $import['msg'];
                    continue ;
                }
                $mage_id = $import['mage_id'];
                $this->_cart->$fn_after_save($mage_id, $data, $main);
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
            $this->_cart->updateApi();
            $this->_notice['is_running'] = false;
        }
        $save_user = $this->_saveNotice();
        if(!$save_user){
            $response = $this->_cart->errorDatabase();
            $this->_responseAjaxJson($response);
            return ;
        }
        $this->_responseAjaxJson($response);
        return ;
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
        $router = Mage::getModel('lecaip/cart');
        $this->_notice = $this->_getNotice($router);
        $cart_type = $this->_notice['config']['cart_type'];
        $model = "lecaip/" . $router->getCart($cart_type);
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
        if(LitExtension_CartImport_Model_Custom::DEMO_MODE){
            $notice = $session->getLeCaIp();
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
        if(LitExtension_CartImport_Model_Custom::DEMO_MODE){
            $session->setLeCaIp($this->_notice);
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
        if(LitExtension_CartImport_Model_Custom::DEMO_MODE){
            $session->unsLeCaIp();
            return true;
        } else {
            return $router->deleteUserNotice($this->_user_id);
        }
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

    /**
     * Upload file
     */
    protected function _uploadFile($input, $desc, $name = null ,$allowExt = array()){
        try{
            $uploader = new Varien_File_Uploader($input);
            $uploader->setAllowRenameFiles(false);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);
            if($allowExt){
                $uploader->setAllowedExtensions($allowExt);
            }
            $result = $uploader->save($desc, $name);
            return array(
                'result' => 'success',
                'data' => $result
            );
        }catch (Exception $e){
            return array(
                'result' => 'error',
                'msg' => $e->getMessage()
            );
        }
    }

}