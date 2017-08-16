<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
 
class LitExtension_SimpleConfigurableProducts_Block_Catalog_Product_View_Attributes extends
    Mage_Catalog_Block_Product_View_Attributes
{

    public function setProduct($product) {
        $this->_product = $product;
        return $this;
    }
}
