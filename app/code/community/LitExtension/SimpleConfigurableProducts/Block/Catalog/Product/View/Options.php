<?php

class LitExtension_SimpleConfigurableProducts_Block_Catalog_Product_View_Options
    extends Mage_Catalog_Block_Product_View_Options
{
    protected $_parentproduct;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getProduct()
    {
        if (!$this->_product) {
            if (Mage::registry('current_product') && Mage::registry('parent_product')) {
                $this->_product = Mage::registry('current_product');
                $this->_parentproduct = Mage::registry('parent_product');
            }
            elseif (Mage::registry('current_product')) {
                $this->_product = Mage::registry('current_product');
            } else {
                $this->_product = Mage::getSingleton('catalog/product');
            }
        }
        return $this->_product;
    }
    
    /*public function getOptions()
    {
        $options = array();
        foreach ($this->getProduct() as $product) {
            $options = array_merge($options, $product->getOptions());
        }
        return $options;
    }*/
    
    public function getJsonConfig()
    {
        $config = array();

        foreach ($this->getOptions() as $option) {
            /* @var $option Mage_Catalog_Model_Product_Option */
            $priceValue = 0;
            if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                $_tmpPriceValues = array();
                foreach ($option->getValues() as $value) {
                    /* @var $value Mage_Catalog_Model_Product_Option_Value */
                    $id = $value->getId();
                    $_tmpPriceValues[$id] = $this->_getPriceConfiguration($value);
                }
                $priceValue = $_tmpPriceValues;
            } else {
                $priceValue = $this->_getPriceConfiguration($option);
            }
            $config[$option->getId()] = $priceValue;
        }
//        if ($this->_parentproduct) {
//            foreach ($this->_parentproduct->getOptions() as $option) {
//                $priceValue = 0;
//                if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
//                    $_tmpPriceValues = array();
//                    foreach ($option->getValues() as $value) {
//                        $id = $value->getId();
//                        $_tmpPriceValues[$id] = $this->_getPriceConfiguration($value);
//                    }
//                    $priceValue = $_tmpPriceValues;
//                } else {
//                    $priceValue = $this->_getPriceConfiguration($option);
//                }
//                $config[$option->getId()] = $priceValue;
//            }
//        }
        //Tax
        $taxHelper  = Mage::helper('tax');
        $taxCalculation = Mage::getSingleton('tax/calculation');
        if (!$taxCalculation->getCustomer() && Mage::registry('current_customer')) {
            $taxCalculation->setCustomer(Mage::registry('current_customer'));
        }

        $_request = $taxCalculation->getRateRequest(false, false, false);
        $_request->setProductClassId($this->_product->getTaxClassId());
        $defaultTax = $taxCalculation->getRate($_request);

        $_request = $taxCalculation->getRateRequest();
        $_request->setProductClassId($this->_product->getTaxClassId());
        $currentTax = $taxCalculation->getRate($_request);
        $taxConfig = array(
            'includeTax'        => $taxHelper->priceIncludesTax(),
            'showIncludeTax'    => $taxHelper->displayPriceIncludingTax(),
            'showBothPrices'    => $taxHelper->displayBothPrices(),
            'defaultTax'        => $defaultTax,
            'currentTax'        => $currentTax,
            'inclTaxTitle'      => Mage::helper('catalog')->__('Incl. Tax')
        );
        $config['taxConfig'] = $taxConfig;
        //endtax

        return Mage::helper('core')->jsonEncode($config);
    }
}