<?php

class LitExtension_Onestepcheckout_Block_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing
{
    public function getDefaultField($key){
        $fdefault = Mage::getStoreConfig('onestepcheckout/origin_shipping_settings/'.$key);
        if($key == 'region_id' && $fdefault === 0) return null;
        return $fdefault;

    }

    public function getAddress()
    {
        $address = $this->getQuote()->getBillingAddress();
        if ($address)
            return $address;
        return parent::getAddress();

    }
}
