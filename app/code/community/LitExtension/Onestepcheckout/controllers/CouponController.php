<?php

class LitExtension_Onestepcheckout_CouponController extends Mage_Core_Controller_Front_Action{

	
	/**
	 * Retrieve shopping cart model object
	 *
	 * @return Mage_Checkout_Model_Cart
	 */
	protected function _getCart(){
		return Mage::getSingleton('checkout/cart');
	}
	
	/**
	 * Get checkout session model instance
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getSession(){
		return Mage::getSingleton('checkout/session');
	}
	
	/**
	 * Get current active quote instance
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	protected function _getQuote(){
		return $this->_getCart()->getQuote();
	}
	
	
	public function couponPostAction(){
		
		$responseData = array();
		/**
		 * No reason continue with empty shopping cart
		 */
		if (!$this->_getCart()->getQuote()->getItemsCount()) {
			$this->_redirect('checkout/cart');
			return;
		}
	
		$couponCode = (string) $this->getRequest()->getParam('coupon_code');
		if ($this->getRequest()->getParam('remove') == 1) {
			$couponCode = '';
		}
		$oldCouponCode = $this->_getQuote()->getCouponCode();
	
		if (!strlen($couponCode) && !strlen($oldCouponCode)) {
			$responseData['message'] = $this->__('Coupon code is not valid.');
            $responseData['status'] =  $this->__('invalid');
			$this->getResponse()->setHeader('Content-type','application/json', true);
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
			return;
		}
	
		try {
			$this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
			$this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
				->collectTotals()
				->save();
	
			
			
			if ($couponCode) {
				if ($couponCode == $this->_getQuote()->getCouponCode()) {
					$responseData['message'] = $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
                    $responseData['status'] =  $this->__('success');
				}else {				
					$responseData['message'] = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
                    $responseData['status'] =  $this->__('invalid');
				}
			} else {
				$responseData['message'] =  $this->__('Coupon code was canceled.');
				$responseData['status'] =  $this->__('success');
			}
			
			$layout= $this->getLayout();
			$block = $layout->createBlock('checkout/cart_coupon');
			$block->setTemplate('le_onestepcheckout/onepage/coupon.phtml');
			$responseData['coupon'] = $block->toHtml();
            $responseData['review'] = $this->_getReviewHtml();
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$responseData['message'] =  $this->__('Cannot apply the coupon code.');
            $responseData['status'] =  $this->__('invalid');
			Mage::logException($e);
		}
		
		
		$this->getResponse()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
		
	}

    /**
     * Get review step html
     *
     * @return string
     */
    protected function _getReviewHtml(){

        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->setCacheId(uniqid("onestepcheckout_onepage_review"));
        $update->load('checkout_onepage_review');
        $layout->generateXml();
        $layout->generateBlocks();
        $review = $layout->getBlock('root');
        $review->setTemplate('le_onestepcheckout/onepage/review/info.phtml');
        return $review->toHtml();
    }
}