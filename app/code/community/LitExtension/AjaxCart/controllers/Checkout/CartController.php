<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

class LitExtension_AjaxCart_Checkout_CartController extends Mage_Checkout_CartController {

    public function addAction() {
        $params = $this->getRequest()->getParams();

        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {

            $cart = $this->_getCart();
            $success = false;
            $message = $this->__('There has been a problem adding your product.');
            $topLinks = '';
            $cartSidebar = '';
            $show_pdt = '';

            try {
                if (isset($params['qty'])) {
                    $filter = new Zend_Filter_LocalizedToNormalized(
                            array('locale' => Mage::app()->getLocale()->getLocaleCode())
                    );
                    $params['qty'] = $filter->filter($params['qty']);
                }

                $product = $this->_initProduct();
                $related = $this->getRequest()->getParam('related_product');
                $product_id = $product->getId();
                $ajaxcartAbstract = Mage::getModel('leajct/abstract');
                $show_pdt = $ajaxcartAbstract->getShowProductHtml($product_id);

                if (!$product) {
                    $message = $this->__('Sorry, product not currently available.');
                } else {
                    $cart->addProduct($product, $params);
                    if (!empty($related)) {
                        $cart->addProductsByIds(explode(',', $related));
                    }
                    $cart->save();

                    $this->_getSession()->setCartWasUpdated(true);

                    Mage::dispatchEvent('checkout_cart_add_product_complete', array(
                        'product' => $product,
                        'request' => $this->getRequest(),
                        'response' => $this->getResponse()
                            )
                    );

                    if (!$cart->getQuote()->getHasError()) {
                        $success = true;
                        $message = $this->__('%s was added to your shopping cart.', $product->getName());

                        $this->loadLayout();
                        $block_topLinks = $this->getLayout()->getBlock('top.links');
                        //$block_topLinks = $this->getLayout()->getBlock('header');
                        $block_cartSidebar = $this->getLayout()->getBlock('cart_sidebar');
                        if ($block_topLinks) {
                            $topLinks = $block_topLinks->toHtml();
                        }
                        if ($block_cartSidebar) {
                            $cartSidebar = $block_cartSidebar->toHtml();
                        }
                    }
                }
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'message' => $message,
                            'topLinks' => $topLinks,
                            'cartSidebar' => $cartSidebar,
                            'show_html' => $show_pdt
                                )
                        )
                );
            } catch (Mage_Core_Exception $e) {
                if ($this->_getSession()->getUseNotice(true)) {
                    $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
                } else {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message) {
                        
                    }
                }
                $url = $this->_getSession()->getRedirectUrl(true);
                if ($url) {
                    $success = false;
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                                'success' => $success,
                                'url' => $url
                                    )
                            )
                    );
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            parent::addAction();
        }
    }

    public function deleteAction() {
        $params = $this->getRequest()->getParams();

        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            $id = (int) $this->getRequest()->getParam('id');
            $topLinks = '';
            $cartSidebar = '';
            $show_pdt = '';
            if ($id) {
                try {

                    $cartHelper = Mage::helper('checkout/cart');
                    $items = $cartHelper->getCart()->getItems();
                    foreach ($items as $item) {
                        if ($item->getItemId() == $id) {
                            $pdt_id = $item->getProductId();
                        }
                    }
                    $_product = Mage::getModel('catalog/product')->load($pdt_id);
                    $message = $this->__('%s was remove to your shopping cart.', $_product->getName());
                    $this->_getCart()->removeItem($id)
                            ->save();
                    $success = true;
                    $this->loadLayout();
                    $block_topLinks = $this->getLayout()->getBlock('top.links');
                    //$block_topLinks = $this->getLayout()->getBlock('header');
                    $block_cartSidebar = $this->getLayout()->getBlock('cart_sidebar');
                    if ($block_topLinks) {
                        $topLinks = $block_topLinks->toHtml();
                    }
                    if ($block_cartSidebar) {
                        $cartSidebar = $block_cartSidebar->toHtml();
                    }
                    $ajaxcartAbstract = Mage::getModel('leajct/abstract');
                    $show_pdt = $ajaxcartAbstract->getShowProductHtml($pdt_id);
                } catch (Exception $e) {
                    $this->_getSession()->addError($this->__('Cannot remove the item.'));
                    Mage::logException($e);
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        'success' => $success,
                        'message' => $message,
                        'topLinks' => $topLinks,
                        'cartSidebar' => $cartSidebar,
                        'show_html' => $show_pdt
                            )
                    )
            );
        } else {
            return parent::deleteAction();
        }
    }

}