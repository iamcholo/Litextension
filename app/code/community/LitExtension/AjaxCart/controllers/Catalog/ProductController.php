<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
require_once Mage::getModuleDir('controllers', 'Mage_Catalog') . DS . 'ProductController.php';

class LitExtension_AjaxCart_Catalog_ProductController extends Mage_Catalog_ProductController {

    public function viewAction() {
        $requestParams = $this->getRequest()->getParams();

        if (isset($requestParams['le_ajaxcart']) && $requestParams['le_ajaxcart']) {
            if (Mage::helper('leajct')->checkMageVersion() == true) {
                $categoryId = (int) $this->getRequest()->getParam('category', false);
                $productId = (int) $this->getRequest()->getParam('id');
                $specifyOptions = $this->getRequest()->getParam('options');

                $viewHelper = Mage::helper('catalog/product_view');

                $params = new Varien_Object();
                $params->setCategoryId($categoryId);
                $params->setSpecifyOptions($specifyOptions);

                try {
                    $viewHelper->prepareAndRender($productId, $this, $params);
                } catch (Exception $exc) {
                    
                }
                $this->loadLayout();
                $ajaxcartRenderer = Mage::getModel('leajct/renderer');
                $ajaxcartRenderer->renderLEAJCView($this->getLayout());
                $ajaxcartRenderer->renderLEAJCType($this->getLayout());
                $html = $ajaxcartRenderer->getLEAJCResponseHtml($this->getLayout());

                $success = true;
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'html' => $html
                                )
                        )
                );
            } else {
                $product = $this->_initProduct();
                Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));

                if ($this->getRequest()->getParam('options')) {
                    $notice = $product->getTypeInstance(true)->getSpecifyOptionMessage();
                    Mage::getSingleton('catalog/session')->addNotice($notice);
                }

                Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());
                $this->_initProductLayout($product);
                $this->_initLayoutMessages('catalog/session');
                $this->_initLayoutMessages('tag/session');
                $this->_initLayoutMessages('checkout/session');
                $ajaxcartRenderer = Mage::getModel('leajct/renderer');
                $ajaxcartRenderer->renderLEAJCView($this->getLayout());
                $ajaxcartRenderer->renderLEAJCType($this->getLayout());
                $html = $ajaxcartRenderer->getLEAJCResponseHtml($this->getLayout());

                $success = true;
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'html' => $html
                                )
                        )
                );
            }
        } else {
            return parent::viewAction();
        }
    }

}
