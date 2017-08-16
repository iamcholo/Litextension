<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_SimpleConfigurableProducts_Model_Catalog_Product_Type_Configurable_Price
    extends Mage_Catalog_Model_Product_Type_Configurable_Price
{

    public function getMinimalPrice($product)
    {
        return $this->getPrice($product);
    }

    public function getMaxPossibleFinalPrice($product) {
        $price = $product->getMaxPrice();
        if ($price !== null) {
            return $price;
        }

        $childProduct = $this->getChildProductWithHighestPrice($product, "finalPrice");

        if (!$childProduct) {
            $childProduct = $this->getChildProductWithHighestPrice($product, "finalPrice", false);
        }

        if ($childProduct) {
            return $childProduct->getFinalPrice();
        }
        return false;
    }

    public function getFinalPrice($qty=null, $product)
    {
        $childProduct = $this->getChildProductWithLowestPrice($product, "finalPrice");
        if (!$childProduct) {
            $childProduct = $this->getChildProductWithLowestPrice($product, "finalPrice", false);
        }

        if ($childProduct) {
            $fp = $childProduct->getFinalPrice();
        } else {
            return false;
        }

        $product->setFinalPrice($fp);
        return $fp;
    }

    public function getPrice($product)
    {
        $price = $product->getIndexedPrice();
        if ($price !== null) {
            return $price;
        }

        $childProduct = $this->getChildProductWithLowestPrice($product, "finalPrice");
        if (!$childProduct) {
            $childProduct = $this->getChildProductWithLowestPrice($product, "finalPrice", false);
        }

        if ($childProduct) {
            return $childProduct->getPrice();
        }

        return false;
    }

    public function getChildProducts($product, $checkSalable=true)
    {
        static $childrenCache = array();
        $cacheKey = $product->getId() . ':' . $checkSalable;

        if (isset($childrenCache[$cacheKey])) {
            return $childrenCache[$cacheKey];
        }

        $childProducts = $product->getTypeInstance(true)->getUsedProductCollection($product);
        $childProducts->addAttributeToSelect(array('price', 'special_price', 'status', 'special_from_date', 'special_to_date'));

        if ($checkSalable) {
            $salableChildProducts = array();
            foreach($childProducts as $childProduct) {
                if($childProduct->isSalable()) {
                    $salableChildProducts[] = $childProduct;
                }
            }
            $childProducts = $salableChildProducts;
        }

        $childrenCache[$cacheKey] = $childProducts;
        return $childProducts;
    }

    public function getChildProductWithHighestPrice($product, $priceType, $checkSalable=true)
    {
        $childProducts = $this->getChildProducts($product, $checkSalable);
        if (count($childProducts) == 0) { #If config product has no children
            return false;
        }
        $maxPrice = 0;
        $maxProd = false;
        foreach($childProducts as $childProduct) {
            if ($priceType == "finalPrice") {
                $thisPrice = $childProduct->getFinalPrice();
            } else {
                $thisPrice = $childProduct->getPrice();
            }
            if($thisPrice > $maxPrice) {
                $maxPrice = $thisPrice;
                $maxProd = $childProduct;
            }
        }
        return $maxProd;
    }

    public function getChildProductWithLowestPrice($product, $priceType, $checkSalable=true)
    {
        $childProducts = $this->getChildProducts($product, $checkSalable);
        if (count($childProducts) == 0) {
            return false;
        }
        $minPrice = PHP_INT_MAX;
        $minProd = false;
        foreach($childProducts as $childProduct) {
            if ($priceType == "finalPrice") {
                $thisPrice = $childProduct->getFinalPrice();
            } else {
                $thisPrice = $childProduct->getPrice();
            }
            if($thisPrice < $minPrice) {
                $minPrice = $thisPrice;
                $minProd = $childProduct;
            }
        }
        return $minProd;
    }

    public function getTierPrice($qty=null, $product)
    {
        return array();
    }
}
