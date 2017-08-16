<?php
/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_PromotionBanner_Model_Pages{

    protected $_banners;

    public function __construct(){
        $this->_banners = $this->_getProBanner();
    }

    protected function _getProBanner(){
        return Mage::getModel('promotionbanner/banner')->getCollection()->addFieldToFilter('status',1);
    }

    public function getBanners(){
        return $this->_banners;
    }

    public function getHomePageBanner(){
        $data = $this->_checkValueInShowAt(1);
        return $data;
    }

    public function getProductPageBanner(){
        $data = $this->_checkValueInShowAt(2);
        return $data;
    }

    public function getCatalogPageBanner(){
        $data = $this->_checkValueInShowAt(3);
        return $data;
    }

    public function getCmsPageBanner(){
        $data = $this->_checkValueInShowAt(4);
        return $data;
    }

    public function getCustomerPageBanner(){
        $data = $this->_checkValueInShowAt(5);
        return $data;
    }

    public function getCheckoutPageBanner(){
        $data = $this->_checkValueInShowAt(6);
        return $data;
    }

    public function getCartPageBanner(){
        $data = $this->_checkValueInShowAt(7);
        return $data;
    }

    protected function _convertShowAt($banner){
        $showAt = array();
        $show_at = $banner['show_at'];
        $showAt = unserialize($show_at);
        return $showAt;
    }

    protected function _checkValueInShowAt($value){
        $data = array();
        foreach($this->_banners as $banner){
            $showAt = $this->_convertShowAt($banner);
            if(in_array($value, (array) $showAt)){
                $data[] = $banner['promotionbanner_id'];
            }
        }
        return $data;
    }
}