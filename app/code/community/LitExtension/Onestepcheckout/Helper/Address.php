<?php

class LitExtension_Onestepcheckout_Helper_Address extends Mage_Core_Helper_Abstract
{
    /**
     * Retrive sorted and grouped fields by rows
     * @return array
     * <code>
     *  array(
     *      array(
     *          'name'
     *      ),
     *      array(
     *          'email',
     *          'company'
     *      ),
     *      ...
     *  )
     * </code>
     */
    public function getSortedFields()
    {
        $result = array();
        $fields = Mage::getStoreConfig('onestepcheckout/address_form_order');
//        asort($fields);

        $i = 0;
        $prevOrder = 0;
        foreach ($fields as $field => $order) {
            if ($order - $prevOrder > 0) {
                $i++;
            }
            $prevOrder = $order;
            $result[$i][] = $field;
        }

        return $result;
    }

    public function getAddressesHtmlSelect($type, $addressBlock)
    {
        if ($addressBlock->isCustomerLoggedIn()) {
            $options = array();
            foreach ($addressBlock->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('oneline')
                );
            }

            $addressId = $addressBlock->getAddress()->getCustomerAddressId();
            if (empty($addressId) && !$addressBlock->getAddress()->getCountryId()) {
                if ($type=='billing') {
                    $address = $addressBlock->getCustomer()->getPrimaryBillingAddress();
                } else {
                    $address = $addressBlock->getCustomer()->getPrimaryShippingAddress();
                }
                if ($address) {
                    $addressId = $address->getId();
                }
            } elseif (empty($addressId)) {
                $addressId = ''; // if customer selected "New Address" option
            }

            $select = $addressBlock->getLayout()->createBlock('core/html_select')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setClass('address-select')
                ->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
                ->setValue($addressId)
                ->setOptions($options);

            $select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }
        return '';
    }
}
