<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Oscommerce_Custom{

    public function getCategoriesExtQuery($cart, $categories){
        $parentIds = $cart->duplicateFieldValueFromList($categories['object'], 'parent_id');
        $parent_id_con = $cart->arrayToInCondition($parentIds);
        $categoriesIds = $cart->duplicateFieldValueFromList($categories['object'], 'categories_id');
        $categories_id_query = $cart->arrayToInCondition($categoriesIds);
        $result = array(
            'parent2' => "SELECT * FROM _DBPRF_categories WHERE categories_id IN {$parent_id_con}",
            'categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN $categories_id_query"
        );
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $catDesc = $cart->getListFromListByField($categoriesExt['object']['categories_description'], 'categories_id', $category['categories_id']);
        if($catDesc){
            $notice = $cart->getNotice();
            foreach($notice['config']['languages'] as $lang_id => $store_id){
                $cat_seo = $cart->getRowFromListByField($catDesc, 'language_id', $lang_id);
                if($cat_seo && $cat_seo['categories_name'] != ''){
                    $request_path = $this->_toUrl($cat_seo['categories_name']) . '-c-';
                    $id = $category['categories_id'];
                    if($category['parent_id']){
                        $id = $category['parent_id'] . '_' . $id;
                        $parent2 = $cart->getRowFromListByField($categoriesExt['object']['parent2'], 'categories_id', $category['parent_id']);
                        if($parent2 && $parent2['parent_id']){
                            $id = $parent2['parent_id'] . '_' . $id;
                        }
                    }
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $request_path . $id . '.html'
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
            'products_description' => "SELECT * FROM _DBPRF_products_description WHERE products_id IN {$pro_id_con}"
        );
        return $result;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $proDesc = $cart->getListFromListByField($productsExt['object']['products_description'], 'products_id', $product['products_id']);
        if($proDesc){
            $notice = $cart->getNotice();
            foreach($notice['config']['languages'] as $lang_id => $store_id){
                $pro_seo = $cart->getRowFromListByField($proDesc, 'language_id', $lang_id);
                if($pro_seo && $pro_seo['products_name'] != ''){
                    $request_path = $this->_toUrl($pro_seo['products_name']);
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $request_path . '-p-' . $product['products_id'] . '.html'
                    );
                }
            }
        }
        return $result;
    }
    
    /** Function */
    protected function _toUrl($name){
        $keywords = array('C5', 'C6', 'C7', 'and', 'Z06', 'Lug ', 'Nut ', 'In', 'LED', 'Car', 'Mat');
        $text = $name;
        foreach ($keywords as $key_word){
            $text = str_replace($key_word, '', $text);
        } 
        $text_array = explode(' ', $text);
        foreach ($text_array as $value) {
            if(is_numeric($value)){
                $text = str_replace($value, '', $text);
            }
        }
        $text = preg_replace('/[^A-Za-z0-9 ]/', '', $text);
        $text = preg_replace('/\s+/', ' ',$text);
        $text = rtrim($text, ' ');
        $text = str_replace(' ', '-', $text);
        $text = str_replace('--', '-', $text);
        $text = strtolower($text);
        return $text;
    }
}