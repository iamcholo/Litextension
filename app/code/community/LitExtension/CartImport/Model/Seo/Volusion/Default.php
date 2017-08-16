<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Seo_Volusion_Default{

    const SEO_ENABLE = false; //Enable Search Engine Friendly URLs
    const HYPER_LINK = false; //Use Hyphens (-) instead of Underscores (_)

    public function convertCategorySeo($cart, $category){
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            if(self::SEO_ENABLE){
                $path = $category['link_title_tag'] ? $category['link_title_tag'] : 'category';
                // trim space in start - end
                $path = trim($path, ' ');
                // change special character to space
                $path = preg_replace('/[^a-zA-Z0-9 _-]+/', ' ', $path);
                // change multi space to single space
                $path = preg_replace('/\s+/', ' ',$path);
                // change space to hyphen
                $path = str_replace(' ', '-', $path);
                // change multi hyphen to single hyphen
                $path = preg_replace('/-+/', '-',$path);
                if(self::HYPER_LINK){
                    $path = str_replace('_', '-', $path);
                }
                $path .= '-s/' . $category['categoryid'] . '.htm';
                // change multi hyphen to hyphen
                $path = preg_replace('/-+/', '-', $path);
            } else {
                $path = 'SearchResults.asp?Cat=' . $category['categoryid'];
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

    public function convertProductSeo($cart, $product){
        $result = array();
        $notice = $cart->getNotice();
        foreach($notice['config']['languages'] as $lang_id => $store_id){
            if(self::SEO_ENABLE){
                $path = $product['productnameshort'] ? $product['productnameshort'] : 'product';
                // trim space in start - end
                $path = trim($path, ' ');
                // change special character to space
                $path = preg_replace('/[^a-zA-Z0-9 _-]+/', ' ', $path);
                // change multi space to single space
                $path = preg_replace('/\s+/', ' ',$path);
                // change space to hyphen
                $path = str_replace(' ', '-', $path);
                // change multi hyphen to single hyphen
                $path = preg_replace('/-+/', '-',$path);
                if(self::HYPER_LINK){
                    $path = str_replace('_', '-', $path);
                }
                $path .= '-p/' . strtolower($product['productcode']) . '.htm';
                // change multi hyphen to hyphen
                $path = preg_replace('/-+/', '-', $path);
            } else {
                $path = 'ProductDetails.asp?ProductCode=' . $product['productcode'];
            }
            $result[] = array(
                'store_id' => $store_id,
                'request_path' => $path
            );
        }
        return $result;
    }

}