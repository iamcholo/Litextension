<?php

class LitExtension_Onestepcheckout_Block_Onepage_Shipping_Method_Available extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    public function getShippingRates()
    {

        if (empty($this->_rates)) {
            $countryCode = Mage::getStoreConfig('general/country/default');
            if ($countryCode && !$this->getAddress()->getCountryId())
                $this->getAddress()->setCountryId($countryCode);
            $this->getAddress()->setCollectShippingRates(true);
            $this->getAddress()->collectShippingRates()->save();

            $groups = $this->getAddress()->getGroupedAllShippingRates();
            /*
            if (!empty($groups)) {
                $ratesFilter = new Varien_Filter_Object_Grid();
                $ratesFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'price');

                foreach ($groups as $code => $groupItems) {
                    $groups[$code] = $ratesFilter->filter($groupItems);
                }
            }
            */

            return $this->_rates = $groups;
        }

        return $this->_rates;
    }

}