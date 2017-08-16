<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class LitExtension_SimpleConfigurableProducts_Block_Catalog_Product_View
    extends Mage_Catalog_Block_Product_View {
    
    public function getJsonConfig() {
        $config = Zend_Json::decode(parent::getJsonConfig());
        $product = $this->getProduct();
        if($product->isConfigurable()) {
            $config['currentTax'] = 0;
            $config['defaultTax'] = 0;
        }
        return Mage::helper('core')->jsonEncode($config);
    }
}

