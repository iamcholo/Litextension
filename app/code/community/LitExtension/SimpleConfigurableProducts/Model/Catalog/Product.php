<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
 
class LitExtension_SimpleConfigurableProducts_Model_Catalog_Product
    extends Mage_Catalog_Model_Product
{
    public function getMaxPossibleFinalPrice()
    {
        if(is_callable(array($this->getPriceModel(), 'getMaxPossibleFinalPrice'))) {
            return $this->getPriceModel()->getMaxPossibleFinalPrice($this);
        } else {
            return parent::getMaxPrice();
        }
    }

    public function isVisibleInSiteVisibility()
    {
        if(is_callable(array($this->getTypeInstance(), 'hasConfigurableProductParentId'))
            && $this->getTypeInstance()->hasConfigurableProductParentId()) {
           return true;
        } else {
            return parent::isVisibleInSiteVisibility();
        }
    }


    public function getProductUrl($useSid = null)
    {
        if(is_callable(array($this->getTypeInstance(), 'hasConfigurableProductParentId'))
            && $this->getTypeInstance()->hasConfigurableProductParentId()) {

            $confProdId = $this->getTypeInstance()->getConfigurableProductParentId();
            return Mage::getModel('catalog/product')->load($confProdId)->getProductUrl();

        } else {
            return parent::getProductUrl($useSid);
        }
    }
}
