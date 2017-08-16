<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Oscommerce_Power{

    public function getCategoriesExtQuery($cart, $categories){
        $catIds = $cart->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $cat_id_con = $cart->arrayToInCondition($catIds);
        $result = array(
            'm1_seourls_keywords' => "SELECT * FROM _DBPRF_m1_seourls_keywords WHERE entity_type = 'category' AND entity_id IN {$cat_id_con}"
        );
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $catSeo = $cart->getListFromListByField($categoriesExt['object']['m1_seourls_keywords'], 'entity_id', $category['categories_id']);
        if($catSeo){
            $notice = $cart->getNotice();
            foreach($notice['config']['languages'] as $lang_id => $store_id){
                $cat_seo = $cart->getRowFromListByField($catSeo, 'language_id', $lang_id);
                if($cat_seo && $cat_seo['keyword'] != ''){
                    $request_path = $this->_toUrl($cat_seo['keyword']);
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $request_path
                    );
                }
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        $proIds = $cart->duplicateFieldValueFromList($products['object'], 'products_id');
        $pro_id_con = $cart->arrayToInCondition($proIds);
        $result = array(
            'm1_seourls_keywords' => "SELECT * FROM _DBPRF_m1_seourls_keywords WHERE entity_type = 'category' AND entity_id IN {$pro_id_con}"
        );
        return $result;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $proSeo = $cart->getListFromListByField($productsExt['object']['m1_seourls_keywords'], 'entity_id', $product['products_id']);
        if($proSeo){
            $notice = $cart->getNotice();
            foreach($notice['config']['languages'] as $lang_id => $store_id){
                $pro_seo = $cart->getRowFromListByField($proSeo, 'language_id', $lang_id);
                if($pro_seo && $pro_seo['keyword'] != ''){
                    $request_path = $this->_toUrl($pro_seo['keyword']);
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $request_path
                    );
                }
            }
        }
        return $result;
    }

    protected function _toUrl($text){
        $url = str_replace(' ', '-', $text);
        return $url;
    }
}