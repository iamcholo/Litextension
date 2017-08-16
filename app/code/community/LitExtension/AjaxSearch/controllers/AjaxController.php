<?php
/**
 * @project     AjaxSearch
 * @package     LitExtension_AjaxSearch
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxSearch_AjaxController extends Mage_Core_Controller_Front_Action
{
    const TEXTAREA_CODE = 'textarea';
    const SEARCHABLE_STATUS = '1';

    protected $_collection = null;

    public static function findNearest($haystack, $needles, $offset)
    {
        $haystackL = strtolower($haystack);
        $nearestWord = '';
        $nearestPos = 999999;
        foreach ($needles as $needle)
            if ($needle
                && false !== ($pos = strpos($haystackL, strtolower($needle), $offset))
                && $nearestPos > $pos
            ) {
                $nearestPos = $pos;
                $nearestWord = substr($haystack, $pos, strlen($needle));
            }
        if ($nearestWord) return array('pos' => $nearestPos, 'word' => $nearestWord);
        else return false;
    }

    public static function decorateWords($words, $subject, $before, $after)
    {
        $replace = array();
        for ($pos = 0; $pos < strlen($subject) && (false !== $nearest = self::findNearest($subject, $words, $pos));) {
            $replace[$nearest['pos']] = $nearest['word'];
            $pos = $nearest['pos'] + strlen($nearest['word']);
        }

        $res = '';
        $pos = 0;
        foreach ($replace as $start => $word) {
            $res .= substr($subject, $pos, $start - $pos) . $before . $word . $after;
            $pos = $start + strlen($word);
        }
        $res .= substr($subject, $pos);

        return $res;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ajaxsearch/product');
        $collection->addAttributeToSelect('*');
        return $collection;
    }

    protected function _postProcessCollection()
    {
        $this->_collection->addUrlRewrites()
            ->addMinimalPrice()
            ->addFinalPrice()
            ->groupByAttribute('entity_id');
        return $this;
    }

    public function indexAction()
    {

        $numproducts = Mage::getStoreConfig('ajaxsearch/general/showprod');
        $linknewtab = Mage::getStoreConfig('ajaxsearch/general/linknewtab');
        $imagesize = Mage::getStoreConfig('ajaxsearch/general/imagesize');
        $showinfo = Mage::getStoreConfig('ajaxsearch/general/showinfo');
        $message_default = Mage::getStoreConfig('ajaxsearch/general/no_result_message');
        $search_in_categories = Mage::getStoreConfig('ajaxsearch/general/search_in_categories');
        $highlight = Mage::getStoreConfig('ajaxsearch/general/highlight');
        if (!is_numeric($numproducts)) $numproducts = 6;
        if (!is_numeric($imagesize)) $imagesize = 85;
        if ($linknewtab == 1) {
            $blank = ' target="_blank"';
        } else {
            $blank = '';
        }

        if (isset($_POST['key']) && $_POST['key'] != '') {
            $sta = true;
            $html = "";

            $qParam = $_POST['key'];
            $storeId = Mage::app()->getStore()->getId();

            $q = Mage::helper('core')->htmlEscape($qParam);
            $q = htmlspecialchars_decode($q);

            $allAttributes = LitExtension_AjaxSearch_Model_System_Config_Source_Attribute::getProductAttributeList();

            $searchedWords = explode(' ', trim($q));

            for ($i = 0; $i < count($searchedWords); $i++) {
                if (strlen($searchedWords[$i]) < 2 || preg_match('(:)', $searchedWords[$i]))
                    unset($searchedWords[$i]);
            }

            if (is_null($this->_collection) || !$this->_collection) {

                $this->_collection = $this->_prepareCollection();
                $fulltext = false;

                $searchableAttributes = explode(',', Mage::getStoreConfig('ajaxsearch/general/attrsearch'));
                $attributes = array();
                foreach ($searchableAttributes as $attrId) {
                    if (array_key_exists($attrId, $allAttributes))
                        $attributes[$attrId] = $allAttributes[$attrId]['type'];
                    $aasd = Mage::getModel('eav/entity_attribute')->load($attrId);
                    if ($aasd->getData('frontend_input') == self::TEXTAREA_CODE) {
                        if ($aasd->getData('is_searchable') == self::SEARCHABLE_STATUS) {
                            $fulltext = true;
                        }
                    }
                }
                $productIds = false;
                try {
                    if ($fulltext) {
                        $productIds = LitExtension_AjaxSearch_Model_System_Config_Source_Attribute::getProductIds($attributes, $searchedWords, $storeId);

                    } else {
                        $productIds = LitExtension_AjaxSearch_Model_System_Config_Source_Attribute::getProductIds2($q, $storeId);
                    }

                } catch (Exception $e) {
                }
                if (!$productIds || empty($productIds)) {
                    $sta = false;
                }else{
                    $this->_collection->addFilterByIds($productIds);
                    $visibility = array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                    $this->_collection
                        ->addStoreFilter($storeId)
                        ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                        ->setVisibility($visibility);
                }

            }

            $this->_postProcessCollection();

            $this->_collection->setPageSize($numproducts);

            if ($search_in_categories == 1) {

                $ret_cat = array();
                $ret_cat[] = array(
                    'attribute' => 'name',
                    'like' => '%' . $q . '%',
                );
                $collection_cat = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect("*")
                    ->addAttributeToFilter($ret_cat);
            }

            if (count($this->_collection) <= 0 && count($collection_cat) <= 0) {
                $sta = false;
            }

            if ($sta == false) {
                $html .= "<div class='le-search-side'><div class='count-result'>" . $message_default . "</div></div>";
            } else {

                $html .= '<div class="le-search-side"><ul class="le-search-ul">';
                if (count($this->_collection) > 0) {
                    $html .= '<div class = "prd-result">'.$this->__("Products").'</div>';

                    foreach ($this->_collection as $_product) {
                        $productUrl = $_product->getProductUrl();
                        if ($highlight == 1) {
                            $prd_name = str_ireplace($q, '<span class = "highlight-keys">' . $q . '</span>', $_product['name']);
                            $formattedPrice = str_ireplace($q, '<span class = "highlight-keys">' . $q . '</span>', Mage::helper('core')->currency($_product['price'], true, false));
                            $special_price = str_ireplace($q, '<span class = "highlight-keys">' . $q . '</span>', Mage::helper('core')->currency($_product['special_price'], true, false));
                            $short_desc = str_ireplace($q, '<span class = "highlight-keys">' . $q . '</span>', $_product['short_description']);
                        } else {
                            $prd_name = $_product['name'];
                            $formattedPrice = Mage::helper('core')->currency($_product['price'], true, false);
                            $special_price = Mage::helper('core')->currency($_product['special_price'], true, false);
                            $short_desc = $_product['short_description'];
                        }

                        if($_product['image'] == 'no_selection'){
                            $block = new Mage_Catalog_Block_Product_List();
                            if($_product['small_image'] == 'no_selection'){
                                if($_product['thumbnail'] == 'no_selection'){
                                    $_img_url = $_product->getImageUrl();
                                }else{
                                    $_img_url = $block->helper('catalog/image')->init($_product, 'thumbnail');
                                }
                            }else{
                                $_img_url = $block->helper('catalog/image')->init($_product, 'small_image');
                            }
                        }else{
                            $_img_url = Mage::helper('catalog/image')->init($_product, 'thumbnail');//$_product->getImageUrl();
                        }

                        $html .= '<a href="' . $productUrl . '" ' . $blank . '><li>';
                        $html .= '<div class="le-search-images">';

                        $html .= '<img src="' . $_img_url . '" style="width:' . $imagesize . 'px;"/>';
                        $html .= '</div>';

                        $html .= '<div class="le-search-right">';
                        $html .= '<h2 class="product-name"><a href="' . $productUrl . '" ' . $blank . ' title="' . $_product['name'] . '">' . $prd_name . '</a></h2>';
                        if (strpos($showinfo, 'price') !== false) {
                            if ($_product['special_price'] == null) {
                                $html .= '<p><span class="regular-price"><span class="price">' . $formattedPrice . '</span></span></p>';
                            } else {
                                $html .= '<p class="old-price"><span class="price-label">Regular Price: </span><span class="price">' . $formattedPrice .
                                    '</span></p><p class="special-price"><span class="price-label">Special Price: </span><span class="price">' . $special_price . '</span></p>';
                            }
                        }
                        if (strpos($showinfo, 'shortdecs') !== false) {
                            $html .= '<p>' . $short_desc . '</p>';
                        }

                        $html .= '</div>';
                        $html .= '</li></a>';
                    }

                }
                if (count($collection_cat) > 0) {
                    $html .= '<div class = "cat-result">'.$this->__("Categories").'</div>';
                    foreach ($collection_cat as $k => $cat) {
                        if ($highlight == 1) {
                            $cat_name = str_ireplace($q, '<span class = "highlight-keys">' . $q . '</span>', $cat->getName());
                        } else {
                            $cat_name = $cat->getName();
                        }
                        $html .= '<a href="' . $cat->getUrl() . '" ' . $blank . '><li>';
                        $html .= '<div class = "result-namecat">' . $cat_name . '</div></li></a>';
                    }
                }
                $mess_prd = '';
                $mess_cat = '';
                $count = count($this->_collection) + count($collection_cat);
//                if ($count >= 1) {
//                    if (count($this->_collection) <= 1) {
//                        $mess_prd = $this->__('%d Product',count($this->_collection));
//                    } else {
//                        $mess_prd = count($this->_collection) . ' Products';
//                    }
//                    if (count($collection_cat) <= 1) {
//                        $mess_cat = count($collection_cat) . ' Category';
//                    } else {
//                        $mess_cat = count($collection_cat) . ' Categories';
//                    }
//                }
                if ($count >= 1) {
                    if ($search_in_categories == 1) {
                        //                    $mess_result = $mess_prd . ' and ' . $mess_cat . ' Found';
                        if(count($this->_collection)<= 1 && count($collection_cat) <= 1){
                            $mess_result = $this->__('%s Product and %s Category Found',count($this->_collection),count($collection_cat));
                        }elseif(count($this->_collection)<= 1 && count($collection_cat) > 1){
                            $mess_result = $this->__('%s Product and %s Categories Found',count($this->_collection),count($collection_cat));
                        }elseif (count($this->_collection)> 1 && count($collection_cat) <= 1) {
                            $mess_result = $this->__('%s Products and %s Category Found',count($this->_collection),count($collection_cat));
                        }  elseif(count($this->_collection)> 1 && count($collection_cat) > 1) {
                            $mess_result = $this->__('%s Products and %s Categories Found',count($this->_collection),count($collection_cat));
                        }
                    } else{
                        if (count($this->_collection) <= 1) {
                            $mess_result = $this->__('%s Product Found',count($this->_collection));
                        } else {
                            $mess_result = $this->__('%s Products Found',count($this->_collection));
                        }
//                        $mess_result = $mess_prd . ' Found';
                    }
                }
                $html .= '</ul><div class="count-result">' . $mess_result . '</div></div>';
            }

            echo $html;
        }
    }

}

?>