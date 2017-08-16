<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
 
class LitExtension_SimpleConfigurableProducts_Block_Catalog_Product_Price
    extends Mage_Catalog_Block_Product_Price
{

    public function _toHtml() {
        $htmlToInsertAfter = '<div class="price-box">';
        if ($this->getTemplate() == 'catalog/product/price.phtml') {
            $product = $this->getProduct();
            if (is_object($product) && $product->isConfigurable()) {
                $extraHtml = '<span class="label" id="configurable-price-from-'
                . $product->getId()
                . $this->getIdSuffix()
                . '"><span class="configurable-price-from-label">';

                if ($product->getMaxPossibleFinalPrice() != $product->getFinalPrice()) {
                    $extraHtml .= $this->__('Price From:');
                }
                $extraHtml .= '</span></span>';
                $priceHtml = parent::_toHtml();
                return substr_replace($priceHtml, $extraHtml, strpos($priceHtml, $htmlToInsertAfter)+strlen($htmlToInsertAfter),0);
            }
	    }
        return parent::_toHtml();
    }
}
