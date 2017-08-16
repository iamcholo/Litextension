<?php
class LitExtension_Onestepcheckout_Block_Wrapper extends  Mage_Core_Block_Template{
	
	
	const XML_PATH_DEFAULT_SHIPPING = 'onestepcheckout/default/shipping';
	
	const XML_PATH_GEO_COUNTRY = 'onestepcheckout/geo/country';
	
	const XML_PATH_GEO_CITY = 'onestepcheckout/geo/city';
	
	
	
	/**
	 * Get one page checkout model
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	public function getOnepage(){
		return Mage::getSingleton('checkout/type_onepage');
	}
	
	protected function _getReviewHtml(){
		//clear cache aftr change collection - if no magento can't find product in review block
		Mage::app()->getCacheInstance()->cleanType('layout');
		
	
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_review');
		$layout->generateXml();
		$layout->generateBlocks();
		$review = $layout->getBlock('root');
		$review->setTemplate('le_onestepcheckout/onepage/review/info.phtml');
		
		return $review->toHtml();
	}

    protected function _getCheckoutSubmitHtml(){
        $layout = $this->getLayout();
        $layout->generateXml();
        $layout->generateBlocks();
        $review = $layout->getBlock('root');
        $review->setTemplate('le_onestepcheckout/onepage/review/checkout_submit.phtml');
        return $review->toHtml();
    }
	
	protected function _getCart(){
		return Mage::getSingleton('checkout/cart');
	}

	
	public function getJsonConfig() {
	
		$config = array ();
		$params = array (
				'_secure' => true
		);	
		$config['baseUrl'] = Mage::getBaseUrl('link', true);
		$config['isLoggedIn'] = (int) Mage::getSingleton('customer/session')->isLoggedIn();
		$config['geoCountry'] =  Mage::getStoreConfig(self::XML_PATH_GEO_COUNTRY) ? Mage::helper('onestepcheckout/country')->get() : false;
		$config['geoCity'] =  Mage::getStoreConfig(self::XML_PATH_GEO_CITY) ? Mage::helper('onestepcheckout/city')->get() : false;
		$config['comment'] = Mage::helper('onestepcheckout')->isShowComment();
		return Mage::helper ( 'core' )->jsonEncode ( $config );
	}
	
	
}