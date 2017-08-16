<?php
class LitExtension_Onestepcheckout_Block_Onepage_Shipping extends Mage_Checkout_Block_Onepage_Shipping{

    public function getDefaultField($key){
        $fdefault = Mage::getStoreConfig('onestepcheckout/origin_shipping_settings/'.$key);
        if($key == 'region_id' && $fdefault == 0) return null;
        return $fdefault;

    }

    public function getCountryHtmlSelect($type)
    {
        $countryId = Mage::getStoreConfig('onestepcheckout/origin_shipping_settings/country_id');
        if (is_null($countryId)) {
            $countryId = Mage::helper('core')->getDefaultCountry();
        }
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($type.'[country_id]')
            ->setId($type.':country_id')
            ->setTitle(Mage::helper('checkout')->__('Country'))
            ->setClass('validate-select')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());
        if ($type === 'shipping') {
            $select->setExtraParams('onchange="if(window.shipping)shipping.setSameAsBilling(false);"');
        }

        return $select->getHtml();
    }
}