<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Seo_Opencart_Custom {

    public function getCategoriesExtQuery($cart, $categories) {
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt) {
        $categoryIds = $cart->duplicateFieldValueFromList($categories['object'], 'category_id');
        $parentIds = $cart->duplicateFieldValueFromList($categories['object'], 'parent_id');
        $allIds = array_merge($categoryIds, $parentIds);
        $category_ids_query = $this->_arrayToInConditionCategory($allIds);
        $ext_rel_query = array(
            "url_alias" => "SELECT * FROM _DBPRF_url_alias WHERE query IN {$category_ids_query}"
        );
        return $ext_rel_query;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt) {
        $result = array();
        $cat_desc = $cart->getRowFromListByField($categoriesExt['object']['url_alias'], 'query', 'category_id=' . $category['category_id']);
        $parent_des = $cart->getRowFromListByField($categoriesExt['object']['url_alias'], 'query', 'category_id=' . $category['parent_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if ($cat_desc) {
            $path = $cat_desc['keyword'];
            if ($parent_des) {
                $path = $parent_des['keyword'] . '/' . $path;
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products) {
        $productIds = $cart->duplicateFieldValueFromList($products['object'], 'product_id');
        $product_id = $cart->arrayToInCondition($productIds);
        $product_ids_query = $this->_arrayToInConditionProduct($productIds);
        $ext_query = array(
            "url_alias_1" => "SELECT * FROM _DBPRF_url_alias WHERE query IN {$product_ids_query}",
            "product_parent_cat" => "SELECT pc.*, c.parent_id FROM _DBPRF_product_to_category as pc LEFT JOIN _DBPRF_category as c ON pc.category_id = c.category_id WHERE pc.product_id IN {$product_id}"
        );
            return $ext_query;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt) {
        $catIds = $cart->duplicateFieldValueFromList($productsExt['object']['product_parent_cat'], 'category_id');
        $parentCatIds = $cart->duplicateFieldValueFromList($productsExt['object']['product_parent_cat'], 'parent_id');
        $allIds = array_merge($catIds, $parentCatIds);
        $category_ids_query = $this->_arrayToInConditionCategory($allIds);
        $ext_rel_query = array(
            "url_alias_2" => "SELECT * FROM _DBPRF_url_alias WHERE query IN {$category_ids_query}",
        );
        return $ext_rel_query;
    }

    public function convertProductSeo($cart, $product, $productsExt) {
        $result = array();
        $pro_desc = $cart->getRowFromListByField($productsExt['object']['url_alias_1'], 'query', 'product_id=' . $product['product_id']);
        $cat_desc = $cart->getListFromListByField($productsExt['object']['product_parent_cat'], 'product_id', $product['product_id']);
        $notice = $cart->getNotice();
        $store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        if ($pro_desc) {
            $path = $pro_desc['keyword'];
            if ($cat_desc) {
                foreach ($cat_desc as $row) {
                    $link = $cart->getRowFromListByField($productsExt['object']['url_alias_2'], 'query', 'category_id=' . $row['category_id']);
                    if ($link) {
                        $link_cat = $link['keyword'];
                        if ($row['parent_id']) {
                            $link_2 = $cart->getRowFromListByField($productsExt['object']['url_alias_2'], 'query', 'category_id=' . $row['parent_id']);
                            if ($link_2) {
                                $link_cat = $link_2['keyword'] . '/' . $link['keyword'];
                            }
                        }
                    } else {
                        continue;
                    }
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $link_cat . '/' . $path
                    );
                }
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

    /**
     * Convert category's array to in condition in mysql query
     */
    protected function _arrayToInConditionCategory($array) {
        if (empty($array)) {
            return "('null')";
        }
        $result = "('category_id=" . implode("','category_id=", $array) . "')";
        return $result;
    }

    /**
     * Convert product's array to in condition in mysql query
     */
    protected function _arrayToInConditionProduct($array) {
        if (empty($array)) {
            return "('null')";
        }
        $result = "('product_id=" . implode("','product_id=", $array) . "')";
        return $result;
    }

}
