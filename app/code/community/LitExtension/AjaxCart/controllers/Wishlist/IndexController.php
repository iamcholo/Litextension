<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
require_once Mage::getModuleDir('controllers', 'Mage_Wishlist') . DS . 'IndexController.php';

class LitExtension_AjaxCart_Wishlist_IndexController extends Mage_Wishlist_IndexController {

    function addAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            if (Mage::helper('leajct')->checkMageVersion() == true) {
                $wishlist = $this->_getWishlist();
                $success = false;
                $wishlist_html = '';
                $topLinks = '';
                $message = '';
                if (!$wishlist) {
                    return $this->norouteAction();
                }

                $session = Mage::getSingleton('customer/session');

                $productId = (int) $this->getRequest()->getParam('product');
                if (!$productId) {
                    $this->_redirect('*/');
                    return;
                }

                $product = Mage::getModel('catalog/product')->load($productId);
                if (!$product->getId() || !$product->isVisibleInCatalog()) {
                    $this->_redirect('*/');
                    return;
                }

                try {
                    $requestParams = $this->getRequest()->getParams();
                    if ($session->getBeforeWishlistRequest()) {
                        $requestParams = $session->getBeforeWishlistRequest();
                        $session->unsBeforeWishlistRequest();
                    }
                    $buyRequest = new Varien_Object($requestParams);

                    $result = $wishlist->addNewItem($product, $buyRequest);
                    if (is_string($result)) {
                        Mage::throwException($result);
                    }
                    $wishlist->save();

                    Mage::dispatchEvent(
                            'wishlist_add_product', array(
                        'wishlist' => $wishlist,
                        'product' => $product,
                        'item' => $result
                            )
                    );

                    $referer = $session->getBeforeWishlistUrl();
                    if ($referer) {
                        $session->setBeforeWishlistUrl(null);
                    } else {
                        $referer = $this->_getRefererUrl();
                    }

                    $session->setAddActionReferer($referer);

                    Mage::helper('wishlist')->calculate();
                    $message = $this->__('%1$s has been added to your wishlist.', $product->getName());
                    $success = true;
                    $this->loadLayout();
                    $block_wishlist = $this->getLayout()->getBlock('wishlist_sidebar');
                    $block_topLinks = $this->getLayout()->getBlock('top.links');
                    //$block_topLinks = $this->getLayout()->getBlock('header');
                    if ($block_wishlist) {
                        $wishlist_html = $block_wishlist->toHtml();
                    }
                    if ($block_topLinks) {
                        $topLinks = $block_topLinks->toHtml();
                    }
                } catch (Mage_Core_Exception $e) {
                    
                } catch (Exception $e) {
                    
                }

                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'html' => $wishlist_html,
                            'topLinks' => $topLinks,
                            'message' => $message
                                )
                        )
                );
            } else {

                $session = Mage::getSingleton('customer/session');
                $wishlist = $this->_getWishlist();
                $success = false;
                $wishlist_html = '';
                $topLinks = '';
                $message = '';
                if (!$wishlist) {
                    $this->_redirect('*/');
                    return;
                }

                $productId = (int) $this->getRequest()->getParam('product');
                if (!$productId) {
                    $this->_redirect('*/');
                    return;
                }

                $product = Mage::getModel('catalog/product')->load($productId);
                if (!$product->getId() || !$product->isVisibleInCatalog()) {
                    $session->addError($this->__('Cannot specify product.'));
                    $this->_redirect('*/');
                    return;
                }

                try {
                    $wishlist->addNewItem($product->getId());
                    $wishlist->save();

                    Mage::dispatchEvent('wishlist_add_product', array('wishlist' => $wishlist, 'product' => $product));

                    if ($referer = $session->getBeforeWishlistUrl()) {
                        $session->setBeforeWishlistUrl(null);
                    } else {
                        $referer = $this->_getRefererUrl();
                    }

                    $session->setAddActionReferer($referer);

                    Mage::helper('wishlist')->calculate();
                    $message = $this->__('%1$s has been added to your wishlist.', $product->getName());
                    $success = true;
                    $this->loadLayout();
                    $block_wishlist = $this->getLayout()->getBlock('wishlist_sidebar');
                    $block_topLinks = $this->getLayout()->getBlock('top.links');
                    //$block_topLinks = $this->getLayout()->getBlock('header');
                    if ($block_wishlist) {
                        $wishlist_html = $block_wishlist->toHtml();
                    }
                    if ($block_topLinks) {
                        $topLinks = $block_topLinks->toHtml();
                    }
                } catch (Mage_Core_Exception $e) {
                    
                } catch (Exception $e) {
                    
                }
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'html' => $wishlist_html,
                            'topLinks' => $topLinks,
                            'message' => $message
                                )
                        )
                );
            }
        } else {
            parent::addAction();
        }
    }

    public function removeAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            $success = false;
            $wishlist_html = '';
            $topLinks = '';
            $message = '';
            if (Mage::helper('leajct')->checkMageVersion() == true) {
                $id = (int) $this->getRequest()->getParam('item');
                $item = Mage::getModel('wishlist/item')->load($id);
                $_product = Mage::getModel('catalog/product')->load($item->getProductId());
                if (!$item->getId()) {
                    return $this->norouteAction();
                }
                $wishlist = $this->_getWishlist($item->getWishlistId());
                if (!$wishlist) {
                    return $this->norouteAction();
                }
                try {
                    $item->delete();
                    $wishlist->save();
                    $message = $this->__('%s has been removed to your wishlist.', $_product->getName());
                    $success = true;
                } catch (Mage_Core_Exception $e) {
                    
                } catch (Exception $e) {
                    
                }

                Mage::helper('wishlist')->calculate();
                $this->loadLayout();
                $block_wishlist = $this->getLayout()->getBlock('wishlist_sidebar');
                $block_topLinks = $this->getLayout()->getBlock('top.links');
                //$block_topLinks = $this->getLayout()->getBlock('header');
                if ($block_wishlist) {
                    $wishlist_html = $block_wishlist->toHtml();
                }
                if ($block_topLinks) {
                    $topLinks = $block_topLinks->toHtml();
                }

                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'html' => $wishlist_html,
                            'topLinks' => $topLinks,
                            'message' => $message,
                                )
                        )
                );
            } else {
                $id = (int) $this->getRequest()->getParam('item');
                $item = Mage::getModel('wishlist/item')->load($id);
                $_product = Mage::getModel('catalog/product')->load($item->getProductId());
                if (!$item->getId()) {
                    return $this->norouteAction();
                }
                $wishlist = $this->_getWishlist($item->getWishlistId());
                if (!$wishlist) {
                    return $this->norouteAction();
                }
                try {
                    $item->delete();
                    $wishlist->save();
                    $message = $this->__('%s has been removed to your wishlist.', $_product->getName());
                    $success = true;
                } catch (Mage_Core_Exception $e) {
                    
                } catch (Exception $e) {
                    
                }

                Mage::helper('wishlist')->calculate();
                $this->loadLayout();
                $block_wishlist = $this->getLayout()->getBlock('wishlist_sidebar');
                $block_topLinks = $this->getLayout()->getBlock('top.links');
                //$block_topLinks = $this->getLayout()->getBlock('header');
                if ($block_wishlist) {
                    $wishlist_html = $block_wishlist->toHtml();
                }
                if ($block_topLinks) {
                    $topLinks = $block_topLinks->toHtml();
                }

                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'html' => $wishlist_html,
                            'topLinks' => $topLinks,
                            'message' => $message
                                )
                        )
                );
            }
        } else {
            parent::removeAction();
        }
    }

    public function cartAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {
            $success = false;
            $show_pdt = '';
            $message = '';
            if (Mage::helper('leajct')->checkMageVersion() == true) {
                $itemId = (int) $this->getRequest()->getParam('item');

                $item = Mage::getModel('wishlist/item')->load($itemId);
                if (!$item->getId()) {
                    return $this->_redirect('*/*');
                }
                $wishlist = $this->_getWishlist($item->getWishlistId());
                if (!$wishlist) {
                    return $this->_redirect('*/*');
                }

                $qty = $this->getRequest()->getParam('qty');
                if (is_array($qty)) {
                    if (isset($qty[$itemId])) {
                        $qty = $qty[$itemId];
                    } else {
                        $qty = 1;
                    }
                }
                $qty = $this->_processLocalizedQty($qty);
                if ($qty) {
                    $item->setQty($qty);
                }

                $session = Mage::getSingleton('wishlist/session');
                $cart = Mage::getSingleton('checkout/cart');

                $redirectUrl = Mage::getUrl('*/*');

                try {
                    $options = Mage::getModel('wishlist/item_option')->getCollection()
                            ->addItemFilter(array($itemId));
                    $item->setOptions($options->getOptionsByItem($itemId));

                    $buyRequest = Mage::helper('catalog/product')->addParamsToBuyRequest(
                            $this->getRequest()->getParams(), array('current_config' => $item->getBuyRequest())
                    );

                    $item->mergeBuyRequest($buyRequest);
                    $item->addToCart($cart, true);
                    $cart->save()->getQuote()->collectTotals();
                    $wishlist->save();
                    $product_id = $item->getProductId();
                    $ajaxcartAbstract = Mage::getModel('leajct/abstract');
                    $show_pdt = $ajaxcartAbstract->getShowProductHtml($product_id);

                    $_product = Mage::getModel('catalog/product')->load($product_id);
                    if ($_product) {
                        $message = $this->__('%s was added to your shopping cart.', $_product->getName());
                    }
                    Mage::helper('wishlist')->calculate();

                    if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
                        $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
                    } else if ($this->_getRefererUrl()) {
                        $redirectUrl = $this->_getRefererUrl();
                    }
                    Mage::helper('wishlist')->calculate();
                    $success = true;
                } catch (Mage_Core_Exception $e) {
                    $success = false;
                    if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                        
                    } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                        $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
                    } else {
                        $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
                    }
                } catch (Exception $e) {
                    
                }

                Mage::helper('wishlist')->calculate();

                $url_wishlist = Mage::getUrl('wishlist/index/index');
                $pdt_id = $item->getProductId();
                $url_product = Mage::getUrl('catalog/product/view') . '?id=' . $pdt_id . '&options=cart';
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'message' => $message,
                            'url_wishlist' => $url_wishlist,
                            'url_redirect' => $redirectUrl,
                            'url_product' => $url_product,
                            'itemId' => $itemId,
                            'show_html' => $show_pdt
                                )
                        )
                );
            } else {

                $wishlist = $this->_getWishlist();
                if (!$wishlist) {
                    return $this->_redirect('*/*');
                }

                $itemId = (int) $this->getRequest()->getParam('item');
                $item = Mage::getModel('wishlist/item')->load($itemId);

                if (!$item->getId() || $item->getWishlistId() != $wishlist->getId()) {
                    return $this->_redirect('*/*');
                }

                $session = Mage::getSingleton('wishlist/session');
                $cart = Mage::getSingleton('checkout/cart');

                $redirectUrl = Mage::getUrl('*/*');

                try {
                    $item->addToCart($cart, true);
                    $cart->save()->getQuote()->collectTotals();
                    $wishlist->save();
                    $product_id = $item->getProductId();
                    $ajaxcartAbstract = Mage::getModel('leajct/abstract');
                    $show_pdt = $ajaxcartAbstract->getShowProductHtml($product_id);

                    $_product = Mage::getModel('catalog/product')->load($product_id);
                    if ($_product) {
                        $message = $this->__('%s was added to your shopping cart.', $_product->getName());
                    }

                    Mage::helper('wishlist')->calculate();
                    $success = true;
                    if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
                        $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
                    } else if ($this->_getRefererUrl()) {
                        $redirectUrl = $this->_getRefererUrl();
                    }
                } catch (Mage_Core_Exception $e) {
                    $success = false;
                    if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                        
                    } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                        $redirectUrl = $item->getProductUrl();
                        $item->delete();
                    } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_IS_GROUPED_PRODUCT) {
                        $redirectUrl = $item->getProductUrl();
                        $item->delete();
                    } else {
                        
                    }
                } catch (Exception $e) {
                    
                }

                Mage::helper('wishlist')->calculate();
                $url_wishlist = Mage::getUrl('wishlist/index/index');
                $pdt_id = $item->getProductId();
                $url_product = Mage::getUrl('catalog/product/view') . '?id=' . $pdt_id . '&options=cart';
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'message' => $message,
                            'url_wishlist' => $url_wishlist,
                            'url_redirect' => $redirectUrl,
                            'url_product' => $url_product,
                            'itemId' => $itemId,
                            'show_html' => $show_pdt
                                )
                        )
                );
            }
        } else {
            parent::cartAction();
        }
    }

    public function indexAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params['le_ajaxcart']) && $params['le_ajaxcart']) {

            $topLinks = '';
            $cartSidebar = '';
            $wishlist_html = '';
            $success = true;
            if (Mage::helper('leajct')->checkMageVersion() == true) {
                if (!$this->_getWishlist()) {
                    return $this->norouteAction();
                }
                $this->loadLayout();
                $block_topLinks = $this->getLayout()->getBlock('top.links');
                //$block_topLinks = $this->getLayout()->getBlock('header');
                $block_cartSidebar = $this->getLayout()->getBlock('cart_sidebar');
                $block_wishlist = $this->getLayout()->getBlock('customer.wishlist');
                if ($block_topLinks) {
                    $topLinks = $block_topLinks->toHtml();
                }
                if ($block_cartSidebar) {
                    $cartSidebar = $block_cartSidebar->toHtml();
                }
                if ($block_wishlist) {
                    $wishlist_html = $block_wishlist->toHtml();
                }
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'topLinks' => $topLinks,
                            'cartSidebar' => $cartSidebar,
                            'html' => $wishlist_html
                                )
                        )
                );
            } else {
                $this->_getWishlist();
                $this->loadLayout();

                $session = Mage::getSingleton('customer/session');
                $block = $this->getLayout()->getBlock('customer.wishlist');
                $referer = $session->getAddActionReferer(true);
                if ($block) {
                    $block->setRefererUrl($this->_getRefererUrl());
                    if ($referer) {
                        $block->setRefererUrl($referer);
                    }
                }

                $this->_initLayoutMessages('customer/session');
                $this->_initLayoutMessages('checkout/session');
                $this->_initLayoutMessages('catalog/session');
                $this->_initLayoutMessages('wishlist/session');
                $block_topLinks = $this->getLayout()->getBlock('top.links');
                //$block_topLinks = $this->getLayout()->getBlock('header');
                $block_cartSidebar = $this->getLayout()->getBlock('cart_sidebar');
                $block_wishlist = $this->getLayout()->getBlock('customer.wishlist');
                if ($block_topLinks) {
                    $topLinks = $block_topLinks->toHtml();
                }
                if ($block_cartSidebar) {
                    $cartSidebar = $block_cartSidebar->toHtml();
                }
                if ($block_wishlist) {
                    $wishlist_html = $block_wishlist->toHtml();
                }
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                            'success' => $success,
                            'topLinks' => $topLinks,
                            'cartSidebar' => $cartSidebar,
                            'html' => $wishlist_html
                                )
                        )
                );
            }
        } else {
            parent::indexAction();
        }
    }

}
