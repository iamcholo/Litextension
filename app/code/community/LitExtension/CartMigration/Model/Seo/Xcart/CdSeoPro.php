<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Xcart_CdSeoPro{

    public function getCategoriesExtQuery($cart, $categories){
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'categoryid');
        $cat_id_in_query = $cart->arrayToInCondition($categoryIds);
        $ext_query = array(
            'cd_seo' => "SELECT * FROM wcm_cdseo WHERE cdseoReplaceID IN {$cat_id_in_query} AND cdseoType = 'category'"
        );
        return $ext_query;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_seo = $cart->getRowFromListByField($categoriesExt['object']['cd_seo'], 'cdseoReplaceID', $category['categoryid']);
        $notice = $cart->getNotice();
        if($cat_seo){
            $path = $cat_seo['cdseoUrl'];
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
            'cd_seo' => "SELECT * FROM wcm_cdseo WHERE cdseoReplaceID IN {$proIds_in_query} AND cdseoType = 'product'"
        );
        return $ext_query;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_seo = $cart->getRowFromListByField($productsExt['object']['cd_seo'], 'cdseoReplaceID', $product['productid']);
        if($pro_seo) {
            $path = $pro_seo['cdseoUrl'];
            $notice = $cart->getNotice();
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