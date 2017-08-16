<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Seo_Loaded_Custom {

    public function getCategoriesExtQuery($cart, $categories) {
        //$result = false;
        $parentIds = $cart->duplicateFieldValueFromList($categories['object'], 'parent_id');
        //if ($parentIds) {
        $parent_id_con = $cart->arrayToInCondition($parentIds);
        $result = array(
            'seo_categories' => "SELECT categories_id, parent_id FROM _DBPRF_categories WHERE categories_id IN {$parent_id_con}",
            'seo_categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$parent_id_con}"
        );
        //}
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt) {
        $result = false;
        if (isset($categoriesExt['object']['seo_categories'])) {
            $parentIds = $cart->duplicateFieldValueFromList($categoriesExt['object']['seo_categories'], 'parent_id');
            if ($parentIds) {
                $parent_id_con = $cart->arrayToInCondition($parentIds);
                $result = array(
                    'seo_categories_2' => "SELECT categories_id, parent_id FROM _DBPRF_categories WHERE categories_id IN {$parent_id_con}",
                    'seo_categories_description_2' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$parent_id_con}"
                );
            }
        }
        return $result;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt) {
        $result = array();
        $cat_name = $cart->getRowValueFromListByField($categoriesExt['object']['categories_description'], 'categories_id', $category['categories_id'], 'categories_name');
        $path_name = $this->_convertUrl($cat_name);
        $path_id = $category['categories_id'];
        $parent_id = $category['parent_id'];
        if ($parent_id) {
            $parent_name = $cart->getRowValueFromListByField($categoriesExt['object']['seo_categories_description'], 'categories_id', $parent_id, 'categories_name');
            $path_name_1 = $this->_convertUrl($parent_name);
            $path_name = $path_name_1 . '-' . $path_name;
            $path_id = $parent_id . '_' . $path_id;
            $parent_id_2 = $cart->getRowValueFromListByField($categoriesExt['object']['seo_categories'], 'categories_id', $parent_id, 'parent_id');
            if ($parent_id_2) {
                $parent_2_name = $cart->getRowValueFromListByField($categoriesExt['object']['seo_categories_description_2'], 'categories_id', $parent_id_2, 'categories_name');
                $path_name_2 = $this->_convertUrl($parent_2_name);
                $path_name = $path_name_2 . '-' . $path_name;
                $path_id = $parent_id_2 . '_' . $path_id;
            }
        }
        $notice = $cart->getNotice();
        $path_url = $path_name . "/c" . $path_id . "/index.html";
        foreach ($notice['config']['languages'] as $lang_id => $store_id) {
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path_url
            );
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products) {
        return false;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt) {
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt) {
        $result = array();
        $data = array();
        $cat_id = $cart->getListFromListByField($productsExt['object']['products_to_categories'], 'products_id', $product['products_id']);
        $path_id = 'p' . $product['products_id'];
        $product_name = $cart->getRowValueFromListByField($productsExt['object']['products_description'], 'products_id', $product['products_id'], 'products_name');
        $path_name = $this->_convertUrl($product_name);
        $data[] = $path_id . '/' . $path_name . "/product_info.html";
        foreach ($cat_id as $row) {
            $cat_url = $cart->_getCatUrl($row['categories_id']);
            if ($cat_url) {
                $path_cat = str_replace("/index.html", "", $cat_url);
                $data[] = $path_cat . '/' . $path_id . '/' . $path_name . "/product_info.html";
            }
        }
        $notice = $cart->getNotice();
        foreach ($notice['config']['languages'] as $lang_id => $store_id) {
            foreach ($data as $path) {
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $path
                );
            }
        }
        return $result;
    }

    protected function _convertUrl($text) {
        $url = str_replace(' ', '-', htmlspecialchars($text));
        $string = preg_replace('/-{2,}/', '-', $url);
        $string = trim($string, "-");
        return $string;
    }

}
