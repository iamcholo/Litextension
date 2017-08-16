<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Cubecart_Default{

    public function getCategoriesExtQuery($cart, $categories){
        $result = false;
        $notice = $cart->getNotice();
        $catIds = $cart->duplicateFieldValueFromList($categories['object'], 'cat_id');
        if($catIds){
            $cat_id_con = $cart->arrayToInCondition($catIds);
            $result = array(
                'seo_urls' => "SELECT * FROM _DBPRF_seo_urls WHERE type = 'cat' AND item_id IN {$cat_id_con}",
            );
        }
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        $result = false;
        $notice = $cart->getNotice();
        return array();
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_url = $cart->getRowFromListByField($categoriesExt['object']['seo_urls'], 'item_id', $category['cat_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($cat_url){
            $path = $cat_url['path'] . '.html';
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        $result = false;
        $notice = $cart->getNotice();
        $proIds = $cart->duplicateFieldValueFromList($products['object'], 'product_id');
        if($proIds){
            $pro_id_con = $cart->arrayToInCondition($proIds);
            $result = array(
                'seo_urls' => "SELECT * FROM _DBPRF_seo_urls WHERE type = 'prod' AND item_id IN {$pro_id_con}",
            );
        }
        return $result;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $result = false;
        $notice = $cart->getNotice();
        return array();
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_url = $cart->getRowFromListByField($productsExt['object']['seo_urls'], 'item_id', $product['product_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($pro_url){
            $path = $pro_url['path'] . '.html';
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

}