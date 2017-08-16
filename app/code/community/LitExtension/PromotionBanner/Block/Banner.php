<?php
/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_PromotionBanner_Block_Banner extends Mage_Core_Block_Template{

    protected $_banners;
    protected $_bannerIds;
    protected $_bannerHome = null;
    protected $_bannerProduct = null;
    protected $_bannerCatalog = null;
    protected $_bannerCms = null;
    protected $_bannerCustomer = null;
    protected $_bannerChechout = null;
    protected $_bannerCart = null;
    protected $_modelPages = null;

    protected function _construct(){
        parent::_construct();
        $this->_modelPages = Mage::getModel('promotionbanner/pages');
        $this->_addBannerIds();
        $this->_addBanners();
        $this->setTemplate('le_promotionbanner/banner.phtml');
    }

    protected function _addBanners(){
        $this->_addBanner(new LitExtension_PromotionBanner_Block_Widget());
    }

    protected function _addBanner(LitExtension_PromotionBanner_Block_Widget $banner){
        $this->_banners = $banner;
    }

    protected function getBanners(){
        return $this->_banners;
    }

    protected function getBannerIds(){
        return $this->_bannerIds;
    }

    protected function _setBannerHome(){
        if(Mage::helper('promotionbanner/banner')->isHomePage() == true){
            $this->_bannerHome = $this->_modelPages->getHomePageBanner();
        }
    }

    protected function getBannerHome(){
        return $this->_bannerHome;
    }

    protected function _setBannerProduct(){
        if(Mage::helper('promotionbanner/banner')->isProductPage() == true){
            $this->_bannerProduct = $this->_modelPages->getProductPageBanner();
        }
    }

    protected function getBannerProduct(){
        return $this->_bannerProduct;
    }

    protected function _setBannerCatalog(){
        if(Mage::helper('promotionbanner/banner')->isCatalogPage() == true){
            $this->_bannerCatalog = $this->_modelPages->getCatalogPageBanner();
        }
    }

    protected function getBannerCatalog(){
        return $this->_bannerCatalog;
    }

    protected function _setBannerCms(){
        if(Mage::helper('promotionbanner/banner')->isCmsPage() == true){
            $this->_bannerCms = $this->_modelPages->getCmsPageBanner();
        }
    }

    protected function getBannerCms(){
        return $this->_bannerCms;
    }

    protected function _setBannerCustomer(){
        if(Mage::helper('promotionbanner/banner')->isCustomerPage() == true){
            $this->_bannerCustomer = $this->_modelPages->getCustomerPageBanner();
        }
    }

    protected function getBannerCustomer(){
        return $this->_bannerCustomer;
    }

    protected function _setBannerCheckout(){
        if(Mage::helper('promotionbanner/banner')->isCheckoutPage() == true){
            $this->_bannerChechout = $this->_modelPages->getCheckoutPageBanner();
        }
    }

    protected function getBannerCheckout(){
        return $this->_bannerChechout;
    }

    protected function _setBannerCart(){
        if(Mage::helper('promotionbanner/banner')->isCartPage() == true){
            $this->_bannerCart = $this->_modelPages->getCartPageBanner();
        }
    }

    protected function getBannerCart(){
        return $this->_bannerCart;
    }

    protected function _addBannerIds(){
        $this->_setBannerHome();
        $this->_setBannerProduct();
        $this->_setBannerCatalog();
        $this->_setBannerCms();
        $this->_setBannerCustomer();
        $this->_setBannerCheckout();
        $this->_setBannerCart();
        $this->_setBannerIds();
    }

    protected function _setBannerIds(){
        $banners = $this->_modelPages->getBanners();
        foreach($banners as $banner){
            if($this->_checkShowBanner($banner['promotionbanner_id']) == true){
                $this->_bannerIds[] = $banner['promotionbanner_id'];
            }
        }
    }

    protected function _checkShowBanner($id){
        $result = false;
        if( in_array($id, (array) $this->_bannerHome) ||
            in_array($id, (array) $this->_bannerProduct) ||
            in_array($id, (array) $this->_bannerCatalog) ||
            in_array($id, (array) $this->_bannerCms) ||
            in_array($id, (array) $this->_bannerCustomer) ||
            in_array($id, (array) $this->_bannerChechout) ||
            in_array($id, (array) $this->_bannerCart)
        ){
            $result = true;
        }
        return $result;
    }
}