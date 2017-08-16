<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Cscart_Default{

    public function getCategoriesExtQuery($cart, $categories){
        $result = false;
        $notice = $cart->getNotice();
        $catIds = $cart->duplicateFieldValueFromList($categories['object'], 'category_id');
        if($catIds){
            $cat_id_con = $cart->arrayToInCondition($catIds);
            $result = array(
                'seo_names' => "SELECT * FROM _DBPRF_seo_names WHERE type = 'c' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$cat_id_con}",
                'seo_redirects' => "SELECT * FROM _DBPRF_seo_redirects WHERE type = 'c' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$cat_id_con}"
            );
        }
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        $result = false;
        $notice = $cart->getNotice();
        $parentIds = $cart->duplicateFieldValueFromList($categoriesExt['object']['seo_names'], 'path');
        $parentIds = $this->_splitParentId($parentIds);
        if($parentIds){
            $parent_id_con = $cart->arrayToInCondition($parentIds);
            $result = array(
                'seo_names_2' => "SELECT * FROM _DBPRF_seo_names WHERE type = 'c' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$parent_id_con}"
            );
        }
        return $result;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $cat_seo = $cart->getRowFromListByField($categoriesExt['object']['seo_names'], 'object_id', $category['category_id']);
        $path = $cat_seo['name'];
        if($cat_seo['path']) {
            $parent_path = explode('/', $cat_seo['path']);
            $parent_path = array_reverse($parent_path);
            foreach ($parent_path as $parent) {
                $parent_seo = $cart->getRowFromListByField($categoriesExt['object']['seo_names_2'], 'object_id', $parent);
                $path = $parent_seo['name'] . '/' . $path;
            }
        }
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => $path
        );
        $path_redirect = $cart->getListFromListByField($categoriesExt['object']['seo_redirects'], 'object_id', $category['category_id']);
        if($path_redirect) {
            foreach ($path_redirect as $child_path) {
                $seo_path = ltrim($child_path['src'], '/');
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $seo_path
                );
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        $result = false;
        $notice = $cart->getNotice();
        $proIds = $cart->duplicateFieldValueFromList($products['object'], 'product_id');
        if($proIds){
            $pro_id_con = $cart->arrayToInCondition($proIds);
            $result = array(
                'seo_names' => "SELECT * FROM _DBPRF_seo_names WHERE type = 'p' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$pro_id_con}",
                'seo_redirects' => "SELECT * FROM _DBPRF_seo_redirects WHERE type = 'p' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$pro_id_con}"
            );
        }
        return $result;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $result = false;
        $notice = $cart->getNotice();
        $catIds = $cart->duplicateFieldValueFromList($productsExt['object']['seo_names'], 'path');
        $catIds = $this->_splitParentId($catIds);
        if($catIds){
            $cat_id_con = $cart->arrayToInCondition($catIds);
            $result = array(
                'seo_names_2' => "SELECT * FROM _DBPRF_seo_names WHERE type = 'c' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$cat_id_con}"
            );
        }
        return $result;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = array();
        $product_seo = $cart->getRowFromListByField($productsExt['object']['seo_names'], 'object_id', $product['product_id']);
        $path = $product_seo['name'];
        if($product_seo['path']) {
            $parent_path = explode('/', $product_seo['path']);
            $parent_path = array_reverse($parent_path);
            foreach ($parent_path as $parent) {
                $parent_seo = $cart->getRowFromListByField($productsExt['object']['seo_names_2'], 'object_id', $parent);
                $path = $parent_seo['name'] . '/' . $path;
            }
        }
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $result[] = array(
            'store_id' => $store_id,
            'request_path' => $path
        );
        $path_redirect = $cart->getListFromListByField($productsExt['object']['seo_redirects'], 'object_id', $product['product_id']);
        if($path_redirect) {
            foreach ($path_redirect as $child_path) {
                $seo_path = ltrim($child_path['src'], '/');
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $seo_path
                );
            }
        }
        return $result;
    }

############################################### Extend function ################################################

    protected function _splitParentId($catIds){
        if(!$catIds){
            return false;
        }
        $data = array();
        foreach($catIds as $cat_id){
            $parents = explode('/', $cat_id);
            foreach ($parents as $value) {
                $data[] = $value;
            }
        }
        $data = array_unique($data);
        return $data;
    }

}