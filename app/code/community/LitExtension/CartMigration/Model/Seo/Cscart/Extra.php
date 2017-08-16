<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Cscart_Extra{

    public function getCategoriesExtQuery($cart, $categories){
        $result = false;
        $notice = $cart->getNotice();
        $parentIds = $cart->duplicateFieldValueFromList($categories['object'], 'id_path');
        $parentIds = $this->_splitParentId($parentIds);
        if($parentIds){
            $parent_id_con = $cart->arrayToInCondition($parentIds);
            $result = array(
                'seo_names' => "SELECT * FROM _DBPRF_seo_names WHERE type = 'c' AND lang_code = '{$notice['config']['default_lang']}' AND object_id IN {$parent_id_con}"
            );
        }
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        if ($category['id_path']) {
            $path = '';
            $parent_path = explode('/', $category['id_path']);
            $cat_seo = $cart->getListFromListByListField($categoriesExt['object']['seo_names'], 'object_id', $parent_path);
            foreach ($parent_path as $parent) {
                $parent_seo = $cart->getRowValueFromListByField($cat_seo, 'object_id', $parent, 'name');
                $path = $path . '/' . $parent_seo;
            }
            $path = ltrim($path, '/');
            $notice = $cart->getNotice();
            $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path . '.html'
            );
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
                'products_categories_seo' => "SELECT pc.product_id, c.id_path FROM _DBPRF_products_categories as pc LEFT JOIN _DBPRF_categories as c ON pc.category_id = c.category_id WHERE pc.product_id IN {$pro_id_con}"
            );
        }
        return $result;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $result = false;
        $notice = $cart->getNotice();
        $catIds = $cart->duplicateFieldValueFromList($productsExt['object']['products_categories_seo'], 'id_path');
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
		$notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $product_seo = $cart->getRowFromListByField($productsExt['object']['seo_names'], 'object_id', $product['product_id']);
        $path = $product_seo['name'];
        $cats_path = $cart->getListFromListByField($productsExt['object']['products_categories_seo'], 'product_id', $product['product_id']);
        if($cats_path) {
            foreach ($cats_path as $cat_path) {
                $path = $product_seo['name'];
                $parent_path = explode('/', $cat_path['id_path']);
                $parent_path = array_reverse($parent_path);
                foreach ($parent_path as $parent) {
                    $parent_seo = $cart->getRowFromListByField($productsExt['object']['seo_names_2'], 'object_id', $parent);
                    $path = $parent_seo['name'] . '/' . $path;
                }
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $path . '.html'
                );
            }
        } else {
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path . '.html'
            );
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