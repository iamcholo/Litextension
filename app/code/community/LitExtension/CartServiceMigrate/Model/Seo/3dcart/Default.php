<?php

/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartServiceMigrate_Model_Seo_3dcart_Default {

    public function getCategoriesExtSeo($cart, $categories, $categoriesExt) {
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt) {
        $result = array();
        $notice = $cart->getNotice();
        $path = $this->_toUrl($category['category_name']);
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path . '_c_' . $category['id'] . '.html'
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
        $path = $this->_toUrl($product['name']);
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path . '_p_' . $product['catalogid'] . '.html'
            );
        }
        return $result;
    }

    public function _toUrl($string){
        $string = preg_replace('/\s+/', ' ', $string);
        $pattern = "([[:punct:]]+)";
        $string = preg_replace($pattern, '', $string);
        $string = preg_replace("/([[:space:]]|[[:blank:]])/", '-', $string);
        return $string;
    }
}
