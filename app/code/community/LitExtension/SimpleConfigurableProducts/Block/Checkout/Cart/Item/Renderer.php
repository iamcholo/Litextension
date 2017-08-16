<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
 
class LitExtension_SimpleConfigurableProducts_Block_Checkout_Cart_Item_Renderer
    extends Mage_Checkout_Block_Cart_Item_Renderer
{
    protected function getConfigurableProductParentId()
    {
        if ($this->getItem()->getOptionByCode('cpid')) {
            return $this->getItem()->getOptionByCode('cpid')->getValue();
        }
        try {
            $buyRequest = unserialize($this->getItem()->getOptionByCode('info_buyRequest')->getValue());
            if(!empty($buyRequest['cpid'])) {
                return $buyRequest['cpid'];
            }
        } catch (Exception $e) {
        }
        return null;
    }

    protected function getConfigurableProductParent()
    {
        return Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($this->getConfigurableProductParentId());
    }

    public function getProduct()
    {
        return Mage::getModel('catalog/product')
           ->setStoreId(Mage::app()->getStore()->getId())
                ->load($this->getItem()->getProductId());
    }

    public function getProductName()
    {
        if ($this->getConfigurableProductParentId()) {
            return $this->getConfigurableProductParent()->getName();
        } else {
            return parent::getProductName();
        }
    }
	
	public function getProductSku()
	{
		if ($this->getConfigurableProductParentId()) {
            return $this->getConfigurableProductParent()->getSku();
        } else {
			return $this->getProduct()->getSku();
		}
	}

    public function hasProductUrl()
    {
        if ($this->getConfigurableProductParentId()) {
            return true;
        } else {
            return parent::hasProductUrl();
        }
    }

    public function getProductUrl()
    {
        if ($this->getConfigurableProductParentId()) {
            return $this->getConfigurableProductParent()->getProductUrl();
        } else {
            return parent::getProductUrl();
        }
    }

    public function getOptionList()
    {
        $options = false;
        $options = parent::getOptionList();

		if ($this->getConfigurableProductParentId()) {
			$attributes = $this->getConfigurableProductParent()
				->getTypeInstance()
				->getUsedProductAttributes();
			foreach($attributes as $attribute) {
				$options[] = array(
					'label' => $attribute->getFrontendLabel(),
					'value' => $this->getProduct()->getAttributeText($attribute->getAttributeCode()),
					'option_id' => $attribute->getId(),
				);
			}
		}
        return $options;
    }

    public function getProductThumbnail()
    {
        if (!$this->getConfigurableProductParentId()) {
           return parent::getProductThumbnail();
        }
        $product = $this->getConfigurableProductParent();
        return $this->helper('catalog/image')->init($product, 'thumbnail');
    }
}
