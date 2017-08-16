<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Xcart_Default{

    public function getCategoriesExtQuery($cart, $categories){
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'categoryid');
        $cat_id_in_query = $cart->arrayToInCondition($categoryIds);
        $ext_query = array(
            'clean_urls' => "SELECT * FROM _DBPRF_clean_urls WHERE resource_id IN {$cat_id_in_query} AND resource_type = 'C'"
        );
        return $ext_query;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_desc = $cart->getRowFromListByField($categoriesExt['object']['clean_urls'], 'resource_id', $category['categoryid']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($cat_desc){
            $path = $cat_desc['clean_url'];
            foreach($notice['config']['languages'] as $lang_id => $store_id){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $path
                );
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        $proIds = $cart->duplicateFieldValueFromList($products['object'], 'productid');
        $proIds_in_query = $cart->arrayToInCondition($proIds);
        $ext_query = array(
            'clean_urls' => "SELECT * FROM _DBPRF_clean_urls WHERE resource_id IN {$proIds_in_query} AND resource_type = 'P'"
        );
        return $ext_query;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_desc = $cart->getRowFromListByField($productsExt['object']['clean_urls'], 'resource_id', $product['productid']);
        if($pro_desc) {
            $notice = $cart->getNotice();
            $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
            $path = $pro_desc['clean_url'];
            foreach($notice['config']['languages'] as $lang_id => $store_id){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $path
                );
            }
        }
        return $result;
    }
}