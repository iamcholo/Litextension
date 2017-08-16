<?php
/**
 * @project: CartImport
* @author : LitExtension
* @url    : http://litextension.com
* @email  : litextension@gmail.com
*/

class LitExtension_CartImport_Model_Seo_Squarespace_Default{

    const SEO_ENABLE = false; //Enable Search Engine Friendly URLs
    const HYPER_LINK = false; //Use Hyphens (-) instead of Underscores (_)

    public function convertCategorySeo($cart, $category){
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $path = ltrim($category['code'], '/');
            if (self::HYPER_LINK) {
                $path = str_replace('_', '-', $path);
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

    public function convertProductSeo($cart, $product){
        $result = array();
        $notice = $cart->getNotice();
        $url_prd = unserialize($product['url']);
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $path = ltrim($url_prd['fullPath'], '/');
            if (self::HYPER_LINK) {
                $path = str_replace('_', '-', $path);
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

}