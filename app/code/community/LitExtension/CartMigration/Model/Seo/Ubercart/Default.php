<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Ubercart_Default{

    public function getCategoriesExtQuery($cart, $categories){
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $notice = $cart->getNotice();
        $seo_cat = $cart->getRowFromListByField($categoriesExt['object']['url_alias'], 'source', 'taxonomy/term/'.$category['tid']);
        if($seo_cat){
            $path =  $seo_cat['alias'];
        }else{
            $path = 'taxonomy/term/'.$category['tid'];
        }
        foreach($notice['config']['languages'] as $lang_id => $store_id){
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
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $notice = $cart->getNotice();
        $seo_prd = $cart->getRowFromListByField($productsExt['object']['url_alias'], 'source', 'node/'.$product['nid']);
        if($seo_prd){
            $path = $seo_prd['alias'];
        }else{
            $path = 'node/'.$product['nid'];
        }
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;

        return false;
    }
}