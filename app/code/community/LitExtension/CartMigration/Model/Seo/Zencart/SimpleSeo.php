<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Zencart_SimpleSeo{

    public function getCategoriesExtQuery($cart, $categories){
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $cat_id_con = $cart->arrayToInCondition($categoryIds);
        $ext_query = array(
            'le_url_cache_categories' => "SELECT * FROM le_url_cache_categories WHERE categories_id IN {$cat_id_con}"
        );
        return $ext_query;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $catSeo = $cart->getListFromListByField($categoriesExt['object']['le_url_cache_categories'], 'categories_id', $category['categories_id']);
        $catPath = $cart->duplicateFieldValueFromList($catSeo, 'path');
        $notice = $cart->getNotice();
        if($catPath){
            foreach($catPath as $cat_path){
                foreach($notice['config']['languages'] as $lang_id => $store_id){
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $cat_path
                    );
                }
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        $productIds = $cart->duplicateFieldValueFromList($products['object'], 'products_id');
        $pro_ids_query = $cart->arrayToInCondition($productIds);
        $ext_query = array(
            'le_url_cache_products' => "SELECT * FROM le_url_cache_products WHERE products_id IN {$pro_ids_query}"
        );
        return $ext_query;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $catIds = $cart->duplicateFieldValueFromList($productsExt['object']['products_to_categories'], 'categories_id');
        $cat_id_con = $cart->arrayToInCondition($catIds);
        $result = array(
            'seo_categories' => "SELECT * FROM le_url_cache_categories WHERE categories_id IN {$cat_id_con}",
        );
        return $result;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = $proPath = array();
        $notice = $cart->getNotice();
        $proToCat = $cart->getListFromListByField($productsExt['object']['products_to_categories'], 'products_id', $product['products_id']);
        $catIds = $cart->duplicateFieldValueFromList($proToCat, 'categories_id');
        $catSeo = array();
        if($catIds){
            foreach($catIds as $cat_id){
                if($listCatSeo = $cart->getListFromListByField($productsExt['object']['seo_categories'], 'categories_id', $cat_id)){
                    $catSeo = array_merge($listCatSeo, $catSeo);
                }
            }
        }
        $proSeo = $cart->getListFromListByField($productsExt['object']['le_url_cache_products'], 'products_id', $product['products_id']);
        foreach($proSeo as $pro_seo){
            $catPath = $cart->getListFromListByField($catSeo, 'lang', $pro_seo['lang']);
            if($catPath){
                foreach($catPath as $cat_path){
                    $proPath[] = $cat_path['path'] . '/' . $pro_seo['path'];
                }
            }else{
                $proPath[] = $pro_seo['path'];
            }
        }
        $proPath = array_unique($proPath);
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            foreach($proPath as $pro_path){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $pro_path
                );
            }
        }
        return $result;
    }
}