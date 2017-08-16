<?php

/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartServiceMigrate_Model_Seo_Bigcommerce_Default {

    public function getCategoriesExtSeo($cart, $categories, $categoriesExt) {
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt) {
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => ltrim($category['url'], '/')
            );
        }
        return $result;
    }

    public function getProductsExtSeo($cart, $products, $productsExt) {
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt) {
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => ltrim($product['custom_url'], '/')
            );
        }
        return $result;
    }

}
