<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
require_once Mage::getModuleDir('controllers', 'Mage_Catalog') . DS . 'Product' . DS . 'CompareController.php';

class LitExtension_AjaxCart_Catalog_Product_CompareController extends Mage_Catalog_Product_CompareController {

    public function addAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            $productId = (int) $this->getRequest()->getParam('product');
            $success = true;
            $compare_html = '';
            $message = '';
            if ($productId && (Mage::getSingleton('log/visitor')->getId() || Mage::getSingleton('customer/session')->isLoggedIn())
            ) {
                $product = Mage::getModel('catalog/product')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->load($productId);

                if ($product->getId()) {
                    Mage::getSingleton('catalog/product_compare_list')->addProduct($product);
                    Mage::dispatchEvent('catalog_product_compare_add_product', array('product' => $product));
                }

                Mage::helper('catalog/product_compare')->calculate();
                $message = $this->__('%1$s has been added to your compare.', $product->getName());
            }
            $this->loadLayout();
            $block_compare = $this->getLayout()->getBlock('catalog.compare.sidebar');
            if ($block_compare) {
                $compare_html = $block_compare->toHtml();
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        'success' => $success,
                        'html' => $compare_html,
                        'message' => $message
                            )
                    )
            );
        } else {
            parent::addAction();
        }
    }

    public function removeAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            $success = false;
            $compare_html = '';
            $message = '';

            if ($productId = (int) $this->getRequest()->getParam('product')) {
                $product = Mage::getModel('catalog/product')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->load($productId);

                if ($product->getId()) {
                    $item = Mage::getModel('catalog/product_compare_item');
                    if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                        $item->addCustomerData(Mage::getSingleton('customer/session')->getCustomer());
                    } elseif ($this->_customerId) {
                        $item->addCustomerData(
                                Mage::getModel('customer/customer')->load($this->_customerId)
                        );
                    } else {
                        $item->addVisitorId(Mage::getSingleton('log/visitor')->getId());
                    }

                    $item->loadByProduct($product);

                    if ($item->getId()) {
                        $item->delete();
                        Mage::dispatchEvent('catalog_product_compare_remove_product', array('product' => $item));
                        Mage::helper('catalog/product_compare')->calculate();
                        $message = $this->__('%1$s has been remove to your compare.', $product->getName());
                    }
                    $this->loadLayout();
                    $block_compare = $this->getLayout()->getBlock('catalog.compare.sidebar');
                    if ($block_compare) {
                        $compare_html = $block_compare->toHtml();
                    }
                }
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        'success' => $success,
                        'html' => $compare_html,
                        'message' => $message
                            )
                    )
            );
        } else {
            parent::removeAction();
        }
    }

    public function clearAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            $success = false;
            $compare_html = '';
            $items = Mage::getResourceModel('catalog/product_compare_item_collection');

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $items->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());
            } elseif ($this->_customerId) {
                $items->setCustomerId($this->_customerId);
            } else {
                $items->setVisitorId(Mage::getSingleton('log/visitor')->getId());
            }

            $session = Mage::getSingleton('catalog/session');

            try {
                $items->clear();
                $success = true;
                $this->loadLayout();
                $block_compare = $this->getLayout()->getBlock('catalog.compare.sidebar');
                if ($block_compare) {
                    $compare_html = $block_compare->toHtml();
                }
            } catch (Mage_Core_Exception $e) {
                
            } catch (Exception $e) {
                
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        'success' => $success,
                        'html' => $compare_html
                            )
                    )
            );
        } else {
            parent::clearAction();
        }
    }

}
