<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Loaded_Default{

    public function getCategoriesExtQuery($cart, $categories){
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $category_ids_query = $cart->arrayToInCondition($categoryIds);
        $notice = $cart->getNotice();
        $ext_rel_query = array(
            "permalinks" => "SELECT * FROM _DBPRF_permalinks WHERE type = 1 AND language_id = {$notice['config']['default_lang']} AND item_id IN {$category_ids_query}"
        );
        return $ext_rel_query;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_desc = $cart->getRowFromListByField($categoriesExt['object']['permalinks'], 'item_id', $category['categories_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($cat_desc){
            $path = 'category/' . $cat_desc['permalink'];
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
        $productIds = $cart->duplicateFieldValueFromList($products['object'], 'products_id');
        $product_ids_query = $cart->arrayToInCondition($productIds);
        $notice = $cart->getNotice();
        $ext_rel_query = array(
            "permalinks" => "SELECT * FROM _DBPRF_permalinks WHERE type = 2 AND language_id = {$notice['config']['default_lang']} AND item_id IN {$product_ids_query}"
        );
        return $ext_rel_query;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_desc = $cart->getRowFromListByField($productsExt['object']['permalinks'], 'item_id', $product['products_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($pro_desc){
            $path = 'product/' . $pro_desc['permalink'];
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }
    
}