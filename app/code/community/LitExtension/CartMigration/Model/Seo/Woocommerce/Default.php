<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Woocommerce_Default{

    const SEO_CAT_PARENT_PATH = false;
    const SEO_CAT_TO_PRODUCT = false;

    public function getCategoriesExtQuery($cart, $categories){
        if(self::SEO_CAT_PARENT_PATH){
            $catParentIds = $cart->duplicateFieldValueFromList($categories['object'], 'parent');
            $cat_parent_ids_con = $cart->arrayToInCondition($catParentIds);
            $ext_query = array(
                'seo_categories' => "SELECT * FROM _DBPRF_term_taxonomy as tx
                      LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                      WHERE tx.taxonomy = 'product_cat' AND tx.term_id IN {$cat_parent_ids_con}"
            );
            return $ext_query;
        }
        return false;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        if(self::SEO_CAT_PARENT_PATH) {
            $catParentIds = $cart->duplicateFieldValueFromList($categoriesExt['object']['seo_categories'], 'parent');
            $cat_parent_ids_con = $cart->arrayToInCondition($catParentIds);
            $ext_rel_query = array(
                'seo_categories_2' => "SELECT * FROM _DBPRF_term_taxonomy as tx
                          LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                          WHERE tx.taxonomy = 'product_cat' AND tx.term_id IN {$cat_parent_ids_con}"
            );
            return $ext_rel_query;
        }
        return false;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = array();
        $notice = $cart->getNotice();
        if(self::SEO_CAT_PARENT_PATH){
            $seo_path_all = array();
            if($category['parent'] > 0){
                $seo_cat = $cart->getRowFromListByField($categoriesExt['object']['seo_categories'], 'term_id', $category['parent']);
                if($seo_cat){
                    $seo_path_all[] = $seo_cat['slug'];
                }
                if($seo_cat['parent'] > 0){
                    $seo_cat_second = $cart->getRowFromListByField($categoriesExt['object']['seo_categories_2'], 'term_id', $seo_cat['parent']);
                    if($seo_cat_second){
                        $seo_path_all[] = $seo_cat_second['slug'];
                    }
                    if($seo_cat_second['parent'] > 0){
                        $tmp = $this->_getCategoriesParent($cart, $seo_cat_second['parent'], array());
                        $seo_path_all = array_merge($seo_path_all, $tmp);
                    }
                }
            }
            $seo_path_all = array_reverse($seo_path_all);
            $seo_path_all = implode('/', $seo_path_all);
            if($seo_path_all){
                $seo_path_all = $seo_path_all . '/';
            }
            $path = "product-category/" . $seo_path_all . $category['slug'];
        }else{
            $path = "product-category/" . $category['slug'];
        }
        foreach($notice['config']['languages'] as $lang_id => $store_id){
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
        return false;
    }

    public function convertProductSeo($cart, $product, $productsExt){
        $result = $pro_url = array();
        $notice = $cart->getNotice();
        if(self::SEO_CAT_TO_PRODUCT){
            $proTermRelationship = $cart->getListFromListByField($productsExt['object']['term_relationship'], 'object_id', $product['ID']);
            $proCat = $cart->getListFromListByField($proTermRelationship, 'taxonomy', 'product_cat');
            if ($proCat) {
                foreach ($proCat as $pro_cat) {
                    $seo_path_all = array();
                    if($pro_cat['parent'] > 0){
                        $tmp = $this->_getCategoriesParent($cart, $pro_cat['parent'], array());
                        $seo_path_all = array_merge($seo_path_all, $tmp);
                        $seo_path_all = array_reverse($seo_path_all);
                        $seo_path_all = implode('/', $seo_path_all);
                        if($seo_path_all){
                            $seo_path_all = $seo_path_all . '/';
                        }
                        $pro_url[] = "product/" . $seo_path_all . $pro_cat['slug'] . '/' . $product['post_name'];
                    }
                }
            }else{
                $pro_url[] = "product" . $product['post_name'];
            }
        }else{
            $pro_url[] = "product/" . $product['post_name'];
        }
        if($pro_url) {
            foreach ($pro_url as $path) {
                foreach($notice['config']['languages'] as $lang_id => $store_id){
                    $result[] = array(
                        'store_id' => $store_id,
                        'request_path' => $path
                    );
                }
            }
        }
        return $result;
    }

    protected function _getCategoriesParent($cart, $cat_parent_id, $data){
        $query = array(
            'categories' => "SELECT * FROM _DBPRF_term_taxonomy as tx
                      LEFT JOIN _DBPRF_terms AS t ON t.term_id = tx.term_id
                      WHERE tx.taxonomy = 'product_cat' AND tx.term_id = {$cat_parent_id}",
        );
        $result = $cart->getDataImportByQuery($query);
        if(!$result || $result['result'] != 'success'){
            return array(
                'result' => 'error'
            );
        }
        $obj = $result['object']['categories'][0];
        $data[] = $obj['slug'];
        if($obj['parent'] != 0){
            $data = $this->_getCategoriesParent($cart, $obj['parent'], $data);
        }
        return $data;
    }
}