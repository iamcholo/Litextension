<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Opencart_Default{

    public function getCategoriesExtQuery($cart, $categories){
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'category_id');
        $category_ids_query = $this->_arrayToInConditionCategory($categoryIds);
        $ext_rel_query = array(
            "url_alias" => "SELECT * FROM _DBPRF_url_alias WHERE query IN {$category_ids_query}"
        );
        return $ext_rel_query;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_desc = $cart->getRowFromListByField($categoriesExt['object']['url_alias'], 'query', 'category_id='.$category['category_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($cat_desc){
            $path = $cat_desc['keyword'];
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        return false;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $productIds = $cart->duplicateFieldValueFromList($products['object'], 'product_id');
        $product_ids_query = $this->_arrayToInConditionProduct($productIds);
        $ext_rel_query = array(
            "url_alias" => "SELECT * FROM _DBPRF_url_alias WHERE query IN {$product_ids_query}"
        );
        return $ext_rel_query;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_desc = $cart->getRowFromListByField($productsExt['object']['url_alias'], 'query', 'product_id='.$product['product_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($pro_desc){
            $path = $pro_desc['keyword'];
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
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