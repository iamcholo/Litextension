<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_SimpleConfigurableProducts_Block_Adminhtml_Catalog_Product_Edit_Tab_Super_Config_Grid
    extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config_Grid
{

    protected function _prepareCollection()
    {
        $allowProductTypes = array();
        foreach (Mage::getConfig()->getNode('global/catalog/product/type/configurable/allow_product_types')->children() as $type) {
            $allowProductTypes[] = $type->getName();
        }

        $product = $this->_getProduct();
        $collection = $product->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('price')
            ->addFieldToFilter('attribute_set_id',$product->getAttributeSetId())
            ->addFieldToFilter('type_id', $allowProductTypes)
			->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner');

        Mage::getModel('cataloginventory/stock_item')->addCatalogInventoryToProductCollection($collection);

        foreach ($product->getTypeInstance(true)->getUsedProductAttributes($product) as $attribute) {
            $collection->addAttributeToSelect($attribute->getAttributeCode());
            $collection->addAttributeToFilter($attribute->getAttributeCode(), array('notnull'=>1));
        }

        $this->setCollection($collection);

        if ($this->isReadonly()) {
            $collection->addFieldToFilter('entity_id', array('in' => $this->_getSelectedProducts()));
        }

        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        return $this;
    }
}
