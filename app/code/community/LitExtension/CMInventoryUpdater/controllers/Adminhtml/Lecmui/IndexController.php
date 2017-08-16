<?php
/**
 * @project: CMInventoryUpdater
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CMInventoryUpdater_Adminhtml_Lecmui_IndexController
    extends Mage_Adminhtml_Controller_Action
{
    protected $_cart;
    protected $_notice;

    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('litextension/cartmigration/inventoryupdater');
    }

    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    public function updateAction(){
        $messages = array();
        $params = $this->getRequest()->getParams();
        if(!isset($params['cart_type']) || !isset($params['cart_url'])){
            $this->_redirect('adminhtml/lecmui_index/index');
            return ;
        }
        $router = Mage::getModel('lecmui/cart');
        $default_notice = $router->getDefaultNotice();
        $router->setNotice($default_notice);
        $prepareConfig = $router->prepareConfig($params);
        $this->_notice = $router->getNotice();
        if($prepareConfig['result'] == 'success'){
            $model_cart = "lecmui/" . $router->getCart($this->_notice['config']['cart_type'], $this->_notice['config']['cart_version']);
            $this->_cart = Mage::getModel($model_cart);
            $this->_cart->setNotice($this->_notice);
            $update = $this->_cart->update();
            $messages = $update['msg'];
        } else {
            $messages = $prepareConfig['msg'];
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('lecmui.update')->setNotice($this->_notice)->setMessages($messages);
        $this->renderLayout();
    }

}