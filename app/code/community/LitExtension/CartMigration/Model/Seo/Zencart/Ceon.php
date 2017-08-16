<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Zencart_Ceon{

    public function getCategoriesExtQuery($cart, $categories){
        $categoryId = $cart->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $category_id_con = $cart->arrayToInCondition($categoryId);
        $result = array(
            'ceon_uri_mappings' => "SELECT * FROM _DBPRF_ceon_uri_mappings WHERE main_page = 'index' AND associated_db_id IN {$category_id_con}"
        );
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $uriMap = $cart->getListFromListByField($categoriesExt['object']['ceon_uri_mappings'], 'associated_db_id', $category['categories_id']);
        if(!$uriMap){
            return $result;
        }
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            foreach($uriMap as $uri_map){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => trim($uri_map['uri'], '/')
                );
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        $productId = $cart->duplicateFieldValueFromList($products['object'], 'products_id');
        $product_id_con = $cart->arrayToInCondition($productId);
        $result = array(
            'ceon_uri_mappings' => "SELECT * FROM _DBPRF_ceon_uri_mappings WHERE main_page = 'product_info' AND associated_db_id IN {$product_id_con}"
        );
        return $result;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $uriMap = $cart->getListFromListByField($productsExt['object']['ceon_uri_mappings'], 'associated_db_id', $product['products_id']);
        if(!$uriMap){
            return $result;
        }
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            foreach($uriMap as $uri_map){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => trim($uri_map['uri'], '/')
                );
            }
        }
        return $result;
    }
}