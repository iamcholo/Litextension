<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
 
class LitExtension_SimpleConfigurableProducts_Model_CatalogIndex_Data_Configurable
    extends Mage_CatalogIndex_Model_Data_Configurable
{
    protected $_haveChildren = array(
                        Mage_CatalogIndex_Model_Retreiver::CHILDREN_FOR_TIERS=>true,
                        Mage_CatalogIndex_Model_Retreiver::CHILDREN_FOR_PRICES=>true,
                        Mage_CatalogIndex_Model_Retreiver::CHILDREN_FOR_ATTRIBUTES=>true,
                        );

    public function getFinalPrice($product, $store, $group)
    {
        return false;
    }
}
