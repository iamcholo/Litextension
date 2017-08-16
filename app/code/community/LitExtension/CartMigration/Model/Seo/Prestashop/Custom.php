<?php

/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
class LitExtension_CartMigration_Model_Seo_Prestashop_Custom {

    public function getCategoriesExtQuery($cart, $categories) {
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt) {
        $result = array(
            'lang' => "SELECT * FROM _DBPRF_lang",
        );
        return $result;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt) {
        $result = array();
        $cat_desc = $cart->getListFromListByField($categoriesExt['object']['categories_lang'], 'id_category', $category['id_category']);
        $notice = $cart->getNotice();
        $store_def = $notice['config']['languages'][$notice['config']['default_lang']];
        $cat_def = $cart->getRowValueFromListByField($cat_desc, 'id_lang', $notice['config']['default_lang'], 'link_rewrite');
        foreach ($categoriesExt['object']['lang'] as $cat_lang) {
            $lang[$cat_lang['id_lang']] = $cat_lang['iso_code'];
        }
        $lang_id_def = $notice['config']['default_lang'];
        if ($cat_def) {
            $path = $category['id_category'] . "-" . $cat_def;
            $result[] = array(
                'store_id' => $store_def,
                'request_path' => $lang[$lang_id_def] . '/' . $path
            );
        }
        
        if ($cat_desc) {
            foreach ($notice['config']['languages'] as $lang_id => $store_id) {
                $link_lang = $cart->getRowValueFromListByField($cat_desc, 'id_lang', $lang_id, 'link_rewrite');
                $path = $category['id_category'] . "-" . $link_lang;
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $lang[$lang_id] . '/' . $path
                );
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products) {
        return false;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt) {
        $categoryIds = $cart->duplicateFieldValueFromList($productsExt['object']['category_product'], 'id_category');
        $category_id_con = $cart->arrayToInCondition($categoryIds);
        $ext_rel_query = array(
            'category_lang' => "SELECT * FROM _DBPRF_category_lang WHERE id_category IN {$category_id_con}",
            'lang' => "SELECT * FROM _DBPRF_lang",
        );
        return $ext_rel_query;
    }

    public function convertProductSeo($cart, $product, $productsExt) {
        $result = array();
        foreach ($productsExt['object']['lang'] as $cat_lang) {
            $lang[$cat_lang['id_lang']] = $cat_lang['iso_code'];
        }
        $pro_desc = $cart->getListFromListByField($productsExt['object']['product_lang'], 'id_product', $product['id_product']);
        if (!$pro_desc) {
            return $result;
        }
        $notice = $cart->getNotice();
        //$store_id = $notice['config']['languages'][$notice['config']['default_lang']];
        $lang_id_def = $notice['config']['default_lang'];
        foreach ($notice['config']['languages'] as $lang_id => $store_id) {
            $link_lang = $cart->getRowValueFromListByField($pro_desc, 'id_lang', $lang_id, 'link_rewrite');
            $path = $product['id_product'] . "-" . $link_lang . ".html";
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $lang[$lang_id] . '/' . $path
            );
            if ($lang_id == $notice['config']['default_lang']) {
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $lang[$lang_id_def] . '/' . $path
                );
            }
        }
        $proCat = $cart->getListFromListByField($productsExt['object']['category_product'], 'id_product', $product['id_product']);

        if ($proCat) {
            foreach ($proCat as $pro_cat) {
                $category = $cart->getListFromListByField($productsExt['object']['category_lang'], 'id_category', $pro_cat['id_category']);

                if ($category) {
                    foreach ($notice['config']['languages'] as $lang_id => $store_id) {
                        $link_lang_cat = $cart->getRowValueFromListByField($category, 'id_lang', $lang_id, 'link_rewrite');
                        $link_lang_pro = $cart->getRowValueFromListByField($pro_desc, 'id_lang', $lang_id, 'link_rewrite');
                        $path = $link_lang_cat . "/" . $product['id_product'] . "-" . $link_lang_pro . ".html";
                        $result[] = array(
                            'store_id' => $store_id,
                            'request_path' => $lang[$lang_id] . '/' . $path
                        );
                        if ($lang_id == $notice['config']['default_lang']) {
                            $result[] = array(
                                'store_id' => $store_id,
                                'request_path' => $lang[$lang_id_def] . '/' . $path
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }

}
