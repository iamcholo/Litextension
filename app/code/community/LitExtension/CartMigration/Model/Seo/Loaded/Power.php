<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Seo_Loaded_Power {
    
    public function getCategoriesExtQuery($cart, $categories) {
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $category_ids_query = $cart->arrayToInCondition($categoryIds);
        $notice = $cart->getNotice();
        $ext_query = array(
            "m1_seourls_keywords" => "SELECT * FROM _DBPRF_m1_seourls_keywords WHERE entity_type = 'category' AND language = {$notice['config']['default_lang']} AND entity_id IN {$category_ids_query}"
        );
        return $ext_query;
    }
    
    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt) {
        return false;
    }
    
    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_m1_info = $cart->getRowFromListByField($categoriesExt['object']['m1_seourls_keywords'], 'entity_id', $category['categories_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if ($cat_m1_info) {
            $path = $this->_convertUrl($cat_m1_info['keyword']);
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path . '.html'
            );
        }
        return $result;
    }
    
    public function getProductsExtQuery($cart, $products){
        $productIds = $cart->duplicateFieldValueFromList($products['object'], 'products_id');
        $product_ids_query = $cart->arrayToInCondition($productIds);
        $notice = $cart->getNotice();
        $ext_query = array(
            "m1_seourls_keywords" => "SELECT * FROM _DBPRF_m1_seourls_keywords WHERE entity_type = 'product' AND language = {$notice['config']['default_lang']} AND entity_id IN {$product_ids_query}"
        );
        return $ext_query;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        return false;
    }
    
    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_m1_info = $cart->getRowFromListByField($productsExt['object']['m1_seourls_keywords'], 'entity_id', $product['products_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if ($pro_m1_info) {
            $path = $this->_convertUrl($pro_m1_info['keyword']);
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path . '.html'
            );
        }
        return $result;
    }
    
    protected function _convertUrl($text) {
        $url = str_replace(' ', '-', strtolower($text));
        $string = preg_replace('/-{2,}/', '-', $url);
        $string = trim($string, "-");
        return $string;
    }
}