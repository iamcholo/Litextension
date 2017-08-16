<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Prestashop_Default{

    public function getCategoriesExtQuery($cart, $categories){
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_desc = $cart->getRowFromListByField($categoriesExt['object']['categories_lang'], 'id_category', $category['id_category']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if($cat_desc){
            $path = $category['id_category'] . "-" .$cat_desc['link_rewrite'];
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
        $categoryIds = $cart->duplicateFieldValueFromList($productsExt['object']['category_product'], 'id_category');
        $category_id_con = $cart->arrayToInCondition($categoryIds);
        $ext_rel_query = array(
            'category_lang' => "SELECT * FROM _DBPRF_category_lang WHERE id_category IN {$category_id_con}"
        );
        return $ext_rel_query;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $pro_desc = $cart->getRowFromListByField($productsExt['object']['product_lang'], 'id_product', $product['id_product']);
        if(!$pro_desc){
            return $result;
        }
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $path = $product['id_product'] . "-" . $pro_desc['link_rewrite'];
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => $path
        );
        $proCat = $cart->getListFromListByField($productsExt['object']['category_product'], 'id_product', $product['id_product']);

        if($proCat){
            foreach($proCat as $pro_cat){
                $category = $cart->getRowFromListByField($productsExt['object']['category_lang'], 'id_category', $pro_cat['id_category']);

                if($category){
                    $path = $category['link_rewrite'] . "/" . $product['id_product'] . "-" . $pro_desc['link_rewrite'];
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $path
                    );
                }
            }
        }

        return $result;
    }
}