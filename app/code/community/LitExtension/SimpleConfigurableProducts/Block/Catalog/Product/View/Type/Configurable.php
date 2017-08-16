<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_SimpleConfigurableProducts_Block_Catalog_Product_View_Type_Configurable
    extends Mage_Catalog_Block_Product_View_Type_Configurable
{
    public function getJsonConfig()
    {
        $config = Zend_Json::decode(parent::getJsonConfig());

        $childProducts = array();
		
		$taxHelper  = Mage::helper('tax');
        $taxCalculation = Mage::getSingleton('tax/calculation');
        if (!$taxCalculation->getCustomer() && Mage::registry('current_customer')) {
            $taxCalculation->setCustomer(Mage::registry('current_customer'));
        }
		$includeTax = $taxHelper->priceIncludesTax();
		$showIncludeTax = $taxHelper->displayPriceIncludingTax();
		$showBothPrices = $taxHelper->displayBothPrices();

        foreach ($this->getAllowProducts() as $product) {
            $productId  = $product->getId();
            $childProducts[$productId] = array(
                "price" => $this->_registerJsPrice($this->_convertPrice($product->getPrice())),
                "finalPrice" => $this->_registerJsPrice($this->_convertPrice($product->getFinalPrice()))
            );

//            if (Mage::getStoreConfig('scp_options/product_page/change_name')) {
//                $childProducts[$productId]["productName"] = $product->getName();
//            }
//            if (Mage::getStoreConfig('scp_options/product_page/change_description')) {
//                $childProducts[$productId]["description"] = $this->helper('catalog/output')->productAttribute($product, $product->getDescription(), 'description');
//            }
//            if (Mage::getStoreConfig('scp_options/product_page/change_short_description')) {
//                $childProducts[$productId]["shortDescription"] = $this->helper('catalog/output')->productAttribute($product, nl2br($product->getShortDescription()), 'short_description');
//            }
//
//            if (Mage::getStoreConfig('scp_options/product_page/change_attributes')) {
//                $childBlock = $this->getLayout()->createBlock('catalog/product_view_attributes');
//                $childProducts[$productId]["productAttributes"] = $childBlock->setTemplate('catalog/product/view/attributes.phtml')
//                    ->setProduct($product)
//                    ->toHtml();
//            }
			$tax_class_id = $product->getTaxClassId();

			$_request = $taxCalculation->getRateRequest(false, false, false);
            $_request->setProductClassId($tax_class_id);
            $defaultTax = $taxCalculation->getRate($_request);

            $_request = $taxCalculation->getRateRequest();
            $_request->setProductClassId($tax_class_id);
            $currentTax = $taxCalculation->getRate($_request);
            $taxConfig = array(
                'includeTax' => $includeTax,
                'showIncludeTax' => $showIncludeTax,
                'showBothPrices' => $showBothPrices,
                'defaultTax' => $defaultTax,
                'currentTax' => $currentTax,
            );
            $childProducts[$productId]['taxConfig'] = $taxConfig;

        }

        if (is_array($config['attributes'])) {
            foreach ($config['attributes'] as $attributeID => &$info) {
                if (is_array($info['options'])) {
                    foreach ($info['options'] as &$option) {
                        unset($option['price']);
                    }
                    unset($option);
                }
            }
            unset($info);
        }

        $p = $this->getProduct();
        $config['childProducts'] = $childProducts;
        if ($p->getMaxPossibleFinalPrice() != $p->getFinalPrice()) {
            $config['priceFromLabel'] = $this->__('Price From:');
        } else {
            $config['priceFromLabel'] = $this->__('');
        }
        $config['ajaxBaseUrl'] = Mage::getUrl('lescp/ajax/');
        $config['productName'] = $p->getName();
        $config['description'] = $this->helper('catalog/output')->productAttribute($p, $p->getDescription(), 'description');
        $config['shortDescription'] = $this->helper('catalog/output')->productAttribute($p, nl2br($p->getShortDescription()), 'short_description');

        $childBlock = $this->getLayout()->createBlock('catalog/product_view_attributes');
        $config["productAttributes"] = $childBlock->setTemplate('catalog/product/view/attributes.phtml')
            ->setProduct($this->getProduct())
            ->toHtml();

        /*if (Mage::getStoreConfig('scp_options/product_page/show_price_ranges_in_options')) {
            $config['showPriceRangesInOptions'] = true;
            $config['rangeToLabel'] = $this->__('to');
        }*/
        return Zend_Json::encode($config);
    }
}
