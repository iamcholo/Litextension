<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
require_once 'Mage/Catalog/controllers/ProductController.php';

class LitExtension_SimpleConfigurableProducts_AjaxController extends Mage_Catalog_ProductController
{
    public function coAction()
    {
       $product = $this->_initProduct();
       if (!empty($product)) {
           $this->loadLayout(false);
           $this->renderLayout();
       }
    }

    protected function _initProduct()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');
        $parentId   = (int) $this->getRequest()->getParam('pid');

        if (!$productId || !$parentId) {
            return false;
        }

        $parent = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($parentId);

        if (!Mage::helper('catalog/product')->canShow($parent)) {
            return false;
        }

        $childIds = $parent->getTypeInstance()->getUsedProductIds();
        if (!is_array($childIds) || !in_array($productId, $childIds)) {
            return false;
        }

        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId);
        if (!$product->getId()) {
            return false;
        }
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            Mage::register('current_category', $category);
        }
        $product->setCpid($parentId);
        Mage::register('current_product', $product);
        Mage::register('product', $product);
        return $product;
    }
}
