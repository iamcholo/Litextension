<?php

/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartServiceMigrate_Model_Seo_Shopify_Default {

    public function getCategoriesExtSeo($cart, $categories, $categoriesExt) {
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt) {
        $result = array();
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => "collections/" . $category['handle']
        );
        return $result;
    }

    public function getProductsExtSeo($cart, $products, $productsExt) {
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt) {
        $result = array();
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => "products/" . $product['handle']
        );
        if ($pro_to_cat = $productsExt['data']['main'][$product['id']]['custom_category']) {
            foreach ($pro_to_cat as $collection) {
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => "collections/" . $collection['handle'] . "/products/" . $product['handle']
                );
            }
        }
        if ($pro_to_cat_smart = $productsExt['data']['main'][$product['id']]['smart_category']) {
            foreach ($pro_to_cat_smart as $collection) {
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => "collections/" . $collection['handle'] . "/products/" . $product['handle']
                );
            }
        }
        return $result;
    }

}
