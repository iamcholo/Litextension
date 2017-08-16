<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Opencart_MijoShop{

    public function getCategoriesExtQuery($cart, $categories){
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        //$categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'category_id');
        //$category_ids_query = $this->_arrayToInConditionCategory($categoryIds);
        $ext_rel_query = array(
            "categories" => "SELECT * FROM _DBPRF_category"
        );
        return $ext_rel_query;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $path = '';
        if($category['parent_id']){
            $path = $category['parent_id'] . '_' . $category['category_id'];
            $parent2 = $cart->getRowFromListByField($categoriesExt['object']['categories'], 'category_id', $category['parent_id']);
            if($parent2 && $parent2['parent_id']){
                $path = $parent2['parent_id'] . '_' . $path;
            }
        }else{
            $path = $category['category_id'] . '_' . $category['category_id'];
        }
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => 'index.php?option=com_mijoshop&route=product/category&path=' . $path
        );
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        return false;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $productIds = $cart->duplicateFieldValueFromList($products['object'], 'product_id');
        $product_ids_query = $this->arrayToInCondition($productIds);
        $ext_rel_query = array(
            "product_to_category" => "SELECT * FROM _DBPRF_product_to_category WHERE product_id IN {$product_ids_query}",
            "categories" => "SELECT * FROM _DBPRF_category"
        );
        return $ext_rel_query;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_cat = $cart->getRowFromListByField($productsExt['object']['product_to_category'], 'product_id', $product['product_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($pro_cat){
            $path = '';
            $category = $cart->getRowFromListByField($productsExt['object']['categories'], 'category_id', $pro_cat['category_id']);
            if($category && $category['parent_id']){
                $path = $category['parent_id'] . '_' . $pro_cat['category_id'];
                $parent2 = $cart->getRowFromListByField($productsExt['object']['categories'], 'category_id', $category['parent_id']);
                if($parent2 && $parent2['parent_id']){
                    $path = $parent2['parent_id'] . '_' . $path;
                }
            }else{
                $path = $pro_cat['category_id'] . '_' . $pro_cat['category_id'];
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => 'index.php?option=com_mijoshop&route=product/product&path=' . $path . '&product_id=' . $product['product_id']
            );
        }
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => 'index.php?option=com_mijoshop&route=product/product&product_id=' . $product['product_id']
        );
        
        return $result;
    }
    
    /**
     * Convert category's array to in condition in mysql query
     */
    protected function _arrayToInConditionCategory($array){
        if(empty($array)){
            return "('null')";
        }
        $result = "('category_id=".implode("','category_id=", $array)."')";
        return $result;
    }
    
    /**
     * Convert product's array to in condition in mysql query
     */
    protected function _arrayToInConditionProduct($array){
        if(empty($array)){
            return "('null')";
        }
        $result = "('product_id=".implode("','product_id=", $array)."')";
        return $result;
    }
}