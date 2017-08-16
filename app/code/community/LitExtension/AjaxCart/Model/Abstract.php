<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxCart_Model_Abstract {

    protected function _getBlockConfig() {
        $data = Mage::getStoreConfig('leajct/cfgblock');
        return $data;
    }

    protected function _getCountProductShow($count_pdt, $count_cfg) {
        if (empty($count_cfg) || $count_cfg == 0) {
            $count = $count_pdt;
        } else {
            if ($count_pdt <= $count_cfg) {
                $count = $count_pdt;
            } else {
                $count = $count_cfg;
            }
        }
        return $count;
    }

    protected function _getListProductFromCollection($_collection, $name_cfg) {
        $leajc_cfg = $this->_getBlockConfig();
        $count_cfg = $leajc_cfg['maxcount'];
        $data = '';
        $_object = Mage::getModel('catalog/product');
        $count = 0;
        if(is_object($_collection)){
            $count = $_collection->getSize();
        }
        if ($count && $leajc_cfg[$name_cfg] == 1) {
            $data .= '<ul>';
            $count_show = $this->_getCountProductShow($count, $count_cfg);
            $i = 1;
            foreach ($_collection as $pdt) {
                if ($i <= $count_show) {
                    if ($name_cfg == 'show_related') {
                        $pdt_id = $pdt;
                    } else {
                        $pdt_id = $pdt->getId();
                    }
                    $_pdt = $_object->load($pdt_id);
                    $pdt_name = $_pdt->getName();
                    $pdt_url = $_pdt->getProductUrl();
                    $pdt_url_image = Mage::helper('catalog/image')->init($_pdt, 'image')->keepFrame(false)->resize(100, 100);
                    $pdt_price = Mage::helper('core')->currency($_pdt->getPrice());
                    $pdt_addtocart = Mage::helper('checkout/cart')->getAddUrl($_pdt);
                    $data .= '<li>
                                <a class="product-image" title="' . $pdt_name . '" href="' . $pdt_url . '">
                                    <img src="' . $pdt_url_image . '" />
                                </a>
                                <div class="product-details">
                                    <h3 class="product-name">
                                        <a href="' . $pdt_url . '">' . $pdt_name . '</a>
                                    </h3>
                                    <div class="price-box">
                                        <span id="product-price-' . $pdt_id . '" class="regular-price">
                                            <span class="price">' . $pdt_price . '</span>
                                        </span>
                                    </div>
                                    <button class="button btn-cart" onclick="setLocation(' . $pdt_addtocart . ')" title="Add to Cart" type="button">
                                        <span><span>Add to Cart</span></span>
                                    </button>
                                </div>
                            </li>';
                    $i++;
                }
            }
            $data .= '</ul>';
        }
        return $data;
    }

    protected function _getListUpSell($product_id) {
        $_product = Mage::getModel('catalog/product')->load($product_id);
        $listUpSell = $_product->getUpSellProductCollection()->addStoreFilter();
        $data = $this->_getListProductFromCollection($listUpSell, 'show_upsell');
        return $data;
    }

    protected function _getListCrossSell($product_id) {
        $_product = Mage::getModel('catalog/product')->load($product_id);
        $listCrossSell = $_product->getCrossSellProducts();
        $data = $this->_getListProductFromCollection($listCrossSell, 'show_crosssel');
        return $data;
    }

    protected function _getListRelated($product_id) {
        $_product = Mage::getModel('catalog/product')->load($product_id);
        $listRelated = $_product->getRelatedProductIds();
        $data = $this->_getListProductFromCollection($listRelated, 'show_related');
        return $data;
    }

    public function getShowProductHtml($product_id) {
        $data = '';
        $data .= $this->_getListUpSell($product_id);
        $data .= $this->_getListCrossSell($product_id);
        $data .= $this->_getListRelated($product_id);
        return $data;
    }

}
