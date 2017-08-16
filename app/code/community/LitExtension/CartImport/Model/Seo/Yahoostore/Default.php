<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Seo_Yahoostore_Default{

    const SEO_ENABLE = true; //Enable Search Engine Friendly URLs
    const HYPER_LINK = false; //Use Hyphens (-) instead of Underscores (_)

    public function convertProductSeo($cart, $product){
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $path = strtolower($product['id']) . '.html';
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

}