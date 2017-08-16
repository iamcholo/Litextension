<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Seo_Zencart_Ultimate{

    // config in admin menu of osCommerce: Configuration > Ultimate SEO
    const SEO_URL_CPATH = 'auto'; //Generate cPath parameters (off || auto)
    const SEO_URL_END = '.html'; //Rewritten URLs end with
    const SEO_URL_FORMAT = 'original'; //Format of rewritten URLs (original || parent)
    const SEO_URL_CATEGORY_DIR = 'off'; //Categories as directories (off || short || full)
    const SEO_URLS_FILTER_PCRE = ''; //Enter PCRE filter rules for generated URLs
    const SEO_URLS_FILTER_CHARS = ''; //Enter special character conversions
    const SEO_URLS_REMOVE_CHARS = 'punctuation'; //Remove these characters from URLs (non-alphanumerical || punctuation)
    const SEO_URLS_FILTER_SHORT_WORDS = 0; //Filter Short Words
    protected $_convertTable = array();

    public function getCategoriesExtQuery($cart, $categories){
        $result = false;
        $parentIds = $cart->duplicateFieldValueFromList($categories['object'], 'parent_id');
        $parentIds = $this->_filterParentId($parentIds);
        if($parentIds){
            $parent_id_con = $cart->arrayToInCondition($parentIds);
            $result = array(
                'seo_categories' => "SELECT categories_id, parent_id FROM _DBPRF_categories WHERE categories_id IN {$parent_id_con}",
                'seo_categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$parent_id_con}"
            );
        }
        return $result;
    }

    public function getCategoriesExtRelQuery($cart, $categories, $categoriesExt){
        $result = false;
        if(isset($categoriesExt['object']['seo_categories'])){
            $parentIds = $cart->duplicateFieldValueFromList($categoriesExt['object']['seo_categories'], 'parent_id');
            $parentIds = $this->_filterParentId($parentIds);
            if($parentIds){
                $parent_id_con = $cart->arrayToInCondition($parentIds);
                $result = array(
                    'seo_categories_2' => "SELECT categories_id, parent_id FROM _DBPRF_categories WHERE categories_id IN {$parent_id_con}",
                    'seo_categories_description_2' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$parent_id_con}"
                );
            }
        }
        return $result;
    }

    public function convertCategorySeo($cart, $category, $categoriesExt){
        $result = false;
        $parent_cat_id_lv1 = $parent_cat_id_lv2 = 0;
        $parent_cat_data_all = array(
            'result' => 'success',
            'categories' => array(),
            'categories_description' => array()
        );
        $parent_cat_path_all = array(
            'result' => 'success',
            'ip' => '',
            'np' => ''
        );
        $parent_cat_id = $category['parent_id'];
        $cat_path = $category['categories_id'];
        if($parent_cat_id){
            $cat_path = $parent_cat_id . "_" . $cat_path;
            if(self::SEO_URL_CATEGORY_DIR == 'full'){
                $parent_cat_data_all['categories'] = $this->_addCategoryData($categoriesExt['object']['seo_categories'], $parent_cat_data_all['categories']);
                $parent_cat_data_all['categories_description'] = $this->_addCategoryData($categoriesExt['object']['seo_categories_description'], $parent_cat_data_all['categories_description']);
            }
            $parent_cat_data = $cart->getRowFromListByField($categoriesExt['object']['seo_categories'], 'categories_id', $parent_cat_id);
            if($parent_cat_data && $parent_cat_id_lv1 = $parent_cat_data['parent_id']){
                $cat_path = $parent_cat_id_lv1 . "_" . $cat_path;
                if(self::SEO_URL_CATEGORY_DIR == 'full'){
                    $parent_cat_data_all['categories'] = $this->_addCategoryData($categoriesExt['object']['seo_categories_2'], $parent_cat_data_all['categories']);
                    $parent_cat_data_all['categories_description'] = $this->_addCategoryData($categoriesExt['object']['seo_categories_description_2'], $parent_cat_data_all['categories_description']);
                }
                $parent_cat_data_lv1 = $cart->getRowFromListByField($categoriesExt['object']['seo_categories_2'], 'categories_id', $parent_cat_id_lv1);
                if($parent_cat_data_lv1 && $parent_cat_id_lv2 = $parent_cat_data_lv1['parent_id']){
                    $cat_path = $parent_cat_id_lv2 . "_" . $cat_path;
                    $parent_cat_data_all = $this->_getCategoriesParent($cart, $parent_cat_id_lv2, $parent_cat_data_all);
                    if($parent_cat_data_all['result'] == 'success'){
                        if(self::SEO_URL_CATEGORY_DIR == 'off' || self::SEO_URL_CATEGORY_DIR == 'short'){
                            $parent_cat_path_all = $this->_pathCategory($cart, $parent_cat_id_lv2, $parent_cat_data_all, $parent_cat_path_all);
                            if($parent_cat_path_all['result'] == 'success'){
                                $cat_path = $parent_cat_path_all['ip'] . $cat_path;
                            }
                        }
                    }
                }
            }
        }
        $catDesc = $cart->getListFromListByField($categoriesExt['object']['categories_description'], 'categories_id', $category['categories_id']);
        if(!$catDesc){
            return $result;
        }
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $path = "";
            $cat_desc = $cart->getRowFromListByField($catDesc, 'language_id', $lang_id);
            if(!$cat_desc){
                continue ;
            }
            $cat_name = $cat_desc['categories_name'];
            if(self::SEO_URL_CATEGORY_DIR == 'off' || self::SEO_URL_CATEGORY_DIR == 'short'){
                if($parent_cat_id && self::SEO_URL_FORMAT == "parent"){
                    $parentCatDesc = $cart->getListFromListByField($categoriesExt['object']['seo_categories_description'], 'categories_id', $parent_cat_id);
                    $parent_cat_desc = $cart->getRowFromListByField($parentCatDesc, 'language_id', $lang_id);
                    if($parent_cat_desc){
                        $cat_name = $parent_cat_desc['categories_name'] . " " . $cat_name;
                    }
                }
                $path = $this->_toUrl($cat_name);
                $path .= "-c-" . $cat_path;
                if(self::SEO_URL_CATEGORY_DIR == 'off'){
                    $path .= self::SEO_URL_END;
                } else {
                    $path .= "/";
                }
            } else {
                $ip = $category['categories_id'];
                $url = $this->_toUrl($cat_name);
                if($parent_cat_id){
                    $parent_cat_path_all = $this->_dirCategory($cart, $parent_cat_id, $parent_cat_data_all, $parent_cat_path_all, $lang_id);
                    if($parent_cat_path_all['result'] == 'success'){
                        $ip = $parent_cat_path_all['ip'] . $ip;
                        $url = $parent_cat_path_all['np'] . $url;
                    }
                }
                $path = $url . "-c-" . $ip . "/";
            }
            if($path != ""){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $path
                );
            }
        }
        return $result;
    }

    public function getProductsExtQuery($cart, $products){
        return false;
    }

    public function getProductsExtRelQuery($cart, $products, $productsExt){
        $catIds = $cart->duplicateFieldValueFromList($productsExt['object']['products_to_categories'], 'categories_id');
        $cat_id_con = $cart->arrayToInCondition($catIds);
        $result = array(
            'seo_categories' => "SELECT categories_id, parent_id FROM _DBPRF_categories WHERE categories_id IN {$cat_id_con}",
            'seo_categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$cat_id_con}"
        );
        return $result;
    }

    public function convertProductSeo(LitExtension_CartMigration_Model_Cart $cart, $product, $productsExt){
        $result = false;
        $proToCat = $cart->getListFromListByField($productsExt['object']['products_to_categories'], 'products_id', $product['products_id']);
        $catIds = $cart->duplicateFieldValueFromList($proToCat, 'categories_id');
        $cat_data_all = array(
            'result' => 'success',
            'categories' => array(),
            'categories_description' => array()
        );
        $cat_path_all = array(
            'result' => 'success',
            'ip' => '',
            'np' => ''
        );
        if(!(self::SEO_URL_FORMAT == 'original' && self::SEO_URL_CATEGORY_DIR == 'off' && self::SEO_URL_CPATH == 'off')){
            $cat_data_all['categories'] = $this->_addCategoryData($productsExt['object']['seo_categories'], $cat_data_all['categories']);
            $cat_data_all['categories_description'] = $this->_addCategoryData($productsExt['object']['seo_categories_description'], $cat_data_all['categories_description']);
            $cat_data_all = $this->_getCategoriesParent($cart, $catIds, $cat_data_all);
        }
        $proDesc = $cart->getListFromListByField($productsExt['object']['products_description'], 'products_id', $product['products_id']);
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            $pro_desc = $cart->getRowFromListByField($proDesc, 'language_id', $lang_id);
            if(!$pro_desc){
                continue ;
            }
            $data = array();
            $pro_name = $pro_desc['products_name'];
            $pro_name = str_replace(' - ', '-', $pro_name);
            $path = $this->_toUrl($pro_name);
            $path .= "-p-" . $product['products_id'] . self::SEO_URL_END;
            if(self::SEO_URL_CATEGORY_DIR == 'off'){
                if(self::SEO_URL_FORMAT == 'original'){
                    if(self::SEO_URL_CPATH == 'off'){
                        $data[] = $path;
                    } else {
                        foreach($catIds as $cat_id){
                            $cat_path_all = $this->_pathCategory($cart, $cat_id, $cat_data_all, array(
                                'result' => 'success',
                                'ip' => $cat_id,
                                'np' => ''
                            ));
                            if($cat_path_all['result'] == 'success'){
                                $cat_path = $cat_path_all['ip'];
                                $data[] = $path . "?cPath=" . $cat_path;
                            }
                        }
                    }
                }
            } else {
                $catDesc = array();
                if(self::SEO_URL_CATEGORY_DIR == 'short'){
                    $catDesc = $cart->getListFromListByField($cat_data_all['categories_description'], 'language_id', $lang_id);
                }
                foreach($catIds as $cat_id){
                    if(self::SEO_URL_CATEGORY_DIR == 'short'){
                        $cat_path_all = $this->_pathCategory($cart, $cat_id, $cat_data_all, array(
                            'result' => 'success',
                            'ip' => $cat_id,
                            'np' => ''
                        ));
                        if($cat_path_all['result'] != 'success'){
                            continue ;
                        }
                        $cat_desc = $cart->getRowFromListByField($catDesc, 'categories_id', $cat_id);
                        if(!$cat_desc){
                            continue ;
                        }
                        $cat_name = $cat_desc['categories_name'];
                        if(self::SEO_URL_FORMAT == 'parent'){
                            $category = $cart->getRowFromListByField($cat_data_all['categories'], 'categories_id', $cat_id);
                            if($category){
                                $parent_cat_id = $category['parent_id'];
                                if($parent_cat_id){
                                    $parent_cat_desc = $cart->getRowFromListByField($catDesc, 'categories_id', $parent_cat_id);
                                    if($parent_cat_desc){
                                        $cat_name = $parent_cat_desc['categories_name'] . $cat_name;
                                    }
                                }
                            }
                        }
                        $url = $this->_toUrl($cat_name);
                        $url .= "-c-" . $cat_path_all['ip'] . "/" . $path;
                        $data[] = $url;
                    } else {
                        $cat_path_all = $this->_dirCategory($cart, $cat_id, $cat_data_all, array(
                            'result' => 'success',
                            'ip' => '',
                            'np' => ''
                        ), $lang_id);
                        if($cat_data_all['result'] == 'success'){
                            $url = $cat_data_all['np'] . $path;
                            $data[] = $url;
                        }
                    }
                }
            }
            $data = array_unique($data);
            foreach($data as $path){
                $result[] = array(
                    'store_id' => $store_id,
                    'request_path' => $path
                );
            }
        }
        return $result;
    }

############################################### Extend function ################################################

    protected function _toUrl($string){
        $convertSpecial = $this->_specialToConvertTable();
        if($convertSpecial){
            $string = strtr($string, $convertSpecial);
        }
        $convertPcre = $this->_pcreToConvertTable();
        if($convertPcre){
            $string = strtr($string, $convertSpecial);
        }
        if($this->_convertTable){
            $string = strtr($string, $this->_convertTable);
        }
        $pattern = (self::SEO_URLS_REMOVE_CHARS == 'non-alphanumerical') ? '/[^a-zA-Z0-9\s]/' : '/[!"#$%&\'()*+,.\/:;<=>?@[\\\]^_`{|}~]/';
        $string = preg_replace($pattern, '', strtolower($string));
        $string = preg_replace("/\s+/", '-', $string);
        if(self::SEO_URLS_FILTER_SHORT_WORDS){
            $string = $this->_shortName($string, self::SEO_URLS_FILTER_SHORT_WORDS);
        }
        return $string;
    }

    protected function _shortName($str, $limit){
        $foo = @explode('-', $str);
        foreach($foo as $index => $value){
            switch (true){
                case ( strlen($value) <= $limit ):
                    continue;
                default:
                    $container[] = $value;
                    break;
            }
        }
        $container = ( sizeof($container) > 1 ? implode('-', $container) : $str );
        return $container;
    }

    protected function _specialToConvertTable(){
        if(!self::SEO_URLS_FILTER_CHARS){
            return false;
        }
        $data = array();
        $items = explode(',', self::SEO_URLS_FILTER_CHARS);
        foreach($items as $item){
            $split = explode('=>', $item);
            if(isset($split[0]) && isset($split[1])){
                $data[$split[0]] = $split[1];
            }
        }
        return $data;
    }

    protected function _pcreToConvertTable(){
        if(!self::SEO_URLS_FILTER_PCRE){
            return false;
        }
        $data = array();
        $items = explode(',', self::SEO_URLS_FILTER_PCRE);
        foreach($items as $item){
            $split = explode('=>', $item);
            if(isset($split[0]) && isset($split[1])){
                $data[$split[0]] = $split[1];
            }
        }
        return $data;
    }

    protected function _filterParentId($catIds){
        if(!$catIds){
            return false;
        }
        $data = array();
        foreach($catIds as $cat_id){
            if($cat_id){
                $data[] = $cat_id;
            }
        }
        return $data;
    }

    protected function _pathCategory($cart, $cat_id, $data, $result){
        if($result['result'] == 'error'){
            return $result;
        }
        $parent_cat = $cart->getRowFromListByField($data['categories'], 'categories_id', $cat_id);
        if(!$parent_cat){
            return array(
                'result' => 'error'
            );
        }
        $parent_cat_id = $parent_cat['parent_id'];
        if($parent_cat_id == 0){
            return $result;
        }
        $result['ip'] = $parent_cat_id . "_" . $result['ip'];
        $result = $this->_pathCategory($cart, $parent_cat_id, $data, $result);
        return $result;
    }

    protected function _dirCategory($cart, $cat_id, $data, $result, $lang_id){
        if($result['result'] == 'error'){
            return $result;
        }
        $parent_cat = $cart->getRowFromListByField($data['categories'], 'categories_id', $cat_id);
        if(!$parent_cat){
            return array(
                'result' => 'error'
            );
        }
        $parent_cat_id = $parent_cat['parent_id'];
        if($parent_cat_id != 0){
            $result = $this->_dirCategory($cart, $parent_cat_id, $data, $result, $lang_id);
        }
        $parentCatDes = $cart->getListFromListByField($data['categories_description'], 'categories_id', $parent_cat_id);
        $parent_cat_des = $cart->getRowFromListByField($parentCatDes, 'language_id', $lang_id);
        if($parent_cat_des){
            $ip = $result['ip'] . $cat_id ;
            $name = $parent_cat_des['categories_name'];
            $path = $this->_toUrl($name);
            $path .= "-c-" . $ip;
            $result['np'] = $result['np'] . $path ."/";
            $result['ip'] = $ip . "_";
            return $result;
        } else {
            return array(
                'result' => 'error'
            );
        }
    }

    protected function _getCategoriesParent($cart, $catIds, $data){
        if($data['result'] == 'error'){
            return $data;
        }
        if(!is_array($catIds)){
            $catIds = array($catIds);
        }
        $catIds = $this->_filterParentId($catIds);
        $catIds = array_unique($catIds);
        $cat_id_con = $cart->arrayToInCondition($catIds);
        $query = array(
            'categories' => "SELECT categories_id, parent_id FROM _DBPRF_categories WHERE categories_id IN {$cat_id_con}",
            'categories_description' => "SELECT * FROM _DBPRF_categories_description WHERE categories_id IN {$cat_id_con}"
        );
        $result = $cart->getDataImportByQuery($query);
        if(!$result || $result['result'] != 'success'){
            return array(
                'result' => 'error'
            );
        }
        foreach($result['object']['categories'] as $row){
            $data['categories'][] = $row;
        }
        foreach($result['object']['categories_description'] as $row){
            $data['categories_description'][] = $row;
        }
        $parentCatIds = $cart->duplicateFieldValueFromList($result['object']['categories'], 'parent_id');
        $parentCatIds = $this->_filterParentId($parentCatIds);
        if($parentCatIds){
            $data = $this->_getCategoriesParent($cart, $parentCatIds, $data);
        }
        return $data;
    }

    protected function _addCategoryData($src, $desc){
        if(!$src){
            return $desc;
        }
        foreach($src as $item){
            $desc[] = $item;
        }
        return $desc;
    }
}