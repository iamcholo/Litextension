<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxCart_Model_Renderer {

    public function renderLEAJCView($layout) {
        $productView = array(
            'addto',
            'addtocart'
        );
        foreach ($productView as $view) {
            $block_product_view = $this->_getLEAJCBlockName($view);
            $template_product_view = $this->_getLEAJCTemplateView($view);
            if ($layout->getBlock($block_product_view)) {
                $layout->getBlock($block_product_view)->setTemplate($template_product_view);
            }
        }
    }

    public function renderLEAJCType($layout) {
        $productType = array(
            'simple',
            'configurable',
            'virtual',
            'bundle',
            'grouped',
            'downloadable'
        );
        foreach ($productType as $type) {
            $block_product_type = $this->_getLEAJCBlockName($type);
            $template_product_type = $this->_getLEAJCTemplateType($type);
            if ($layout->getBlock($block_product_type)) {
                $layout->getBlock($block_product_type)->setTemplate($template_product_type);
            }
        }
    }

    public function _getLEAJCTemplateType($product_type) {
        $result = 'litextension/ajaxcart/catalog/product/view/type/' . $product_type . '.phtml';
        return $result;
    }

    public function _getLEAJCTemplateView($product_view) {
        $result = 'litextension/ajaxcart/catalog/product/view/' . $product_view . '.phtml';
        return $result;
    }

    public function _getLEAJCBlockName($product_type) {
        $result = 'product.info.' . $product_type;
        return $result;
    }

    public function getLEAJCResponseHtml($layout) {
        $product_view_block = 'product.info';
        $product_view_template = 'litextension/ajaxcart/catalog/product/view.phtml';
        $html = '';
        if ($layout->getBlock($product_view_block)) {
            $block = $layout->getBlock($product_view_block)->setTemplate($product_view_template);
            $html = $block->toHtml();
        }
        return $html;
    }

}
