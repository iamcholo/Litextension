<?php
class LitExtension_Onestepcheckout_JsonController extends Mage_Core_Controller_Front_Action{

	const XML_PATH_DEFAULT_PAYMENT = 'onestepcheckout/default/payment';

	/* @var $_order Mage_Sales_Model_Order */
	protected $_order;




	/**
	 * Get Order by quoteId
	 *
	 * @return Mage_Sales_Model_Order
	 */
	protected function _getOrder(){
		if (is_null($this->_order)) {
			$this->_order = Mage::getModel('sales/order')->load($this->getOnepage()->getQuote()->getId(), 'quote_id');
			if (!$this->_order->getId()) {
				throw new Mage_Payment_Model_Info_Exception(Mage::helper('core')->__("Can not create invoice. Order was not found."));
			}
		}
		return $this->_order;
	}

	/**
	 * Create invoice
	 *
	 * @return Mage_Sales_Model_Order_Invoice
	 */
	protected function _initInvoice()
	{
		$items = array();
		foreach ($this->_getOrder()->getAllItems() as $item) {
			$items[$item->getId()] = $item->getQtyOrdered();
		}
		/* @var $invoice Mage_Sales_Model_Service_Order */
		$invoice = Mage::getModel('sales/service_order', $this->_getOrder())->prepareInvoice($items);
		$invoice->setEmailSent(true)->register();

		Mage::register('current_invoice', $invoice);
		return $invoice;
	}



	protected function _getCart(){
		return Mage::getSingleton('checkout/cart');
	}


	protected function _getSession(){
		return Mage::getSingleton('checkout/session');
	}

	protected function _getQuote(){
		return $this->_getCart()->getQuote();
	}

	/**
	 * Get one page checkout model
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	public function getOnepage(){
		return Mage::getSingleton('checkout/type_onepage');
	}

	protected function _ajaxRedirectResponse(){
		$this->getResponse()
			->setHeader('HTTP/1.1', '403 Session Expired')
			->setHeader('Login-Required', 'true')
			->sendResponse();
		return $this;
	}

	/**
	 * Validate ajax request and redirect on failure
	 *
	 * @return bool
	 */
	protected function _expireAjax(){

		if (!$this->getRequest()->isAjax()){
			$this->_redirectUrl(Mage::getBaseUrl('link', true));
			return;
		}

//		if (!$this->getOnepage()->getQuote()->hasItems() || $this->getOnepage()->getQuote()->getHasError() || $this->getOnepage()->getQuote()->getIsMultiShipping()) {
//			foreach($this->getOnepage()->getQuote()->getErrors() as $item){
//                $msg_error = $item->getCode();
//            }
//            return $msg_error;
//		}
//
//		$action = $this->getRequest()->getActionName();
//		if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true) && !in_array($action, array('index', 'progress'))) {
//				$this->_ajaxRedirectResponse();
//				return true;
//		}

		return false;
	}

	/**
	 * Get shipping method step html
	 *
	 * @return string
	 */
	protected function _getShippingMethodsHtml(){

        $this->loadLayout();
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
        $update->setCacheId(uniqid("onestepcheckout_onepage_shippingmethod"));
		$update->load('onestepcheckout_index_index');
		$layout->generateXml();
		$layout->generateBlocks();
		$shippingMethods = $layout->getBlock('checkout.onepage.shipping_method');
		$shippingMethods->setTemplate('le_onestepcheckout/onepage/shipping_method.phtml');
		return $shippingMethods->toHtml();
	}

	/**
	 * Get payments method step html
	 *
	 * @return string
	 */
	protected function _getPaymentMethodsHtml(){

		/** UPDATE PAYMENT METHOD **/
		$defaultPaymentMethod = Mage::getStoreConfig(self::XML_PATH_DEFAULT_PAYMENT);
		$_cart = $this->_getCart();
		$_quote = $_cart->getQuote();
		$_quote->getPayment()->setMethod($defaultPaymentMethod);
		$_quote->setTotalsCollectedFlag(false)->collectTotals();
		$_quote->save();
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
        $update->setCacheId(uniqid("onestepcheckout_onepage_paymentmethod"));
		$update->load('checkout_onepage_paymentmethod');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getOutput();
		return $output;
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


	private function checkNewslatter(){
		$data = $this->getRequest()->getParams();
		if (isset($data['is_subscribed']) && $data['is_subscribed']==1){
			Mage::getSingleton('core/session')->setIsSubscribed(true);
		}else{
			Mage::getSingleton('core/session')->unsIsSubscribed();
		}
	}


	public function saveBillingAction(){

		if ($this->_expireAjax()) {
			return;
		}


		if ($this->getRequest()->isPost()) {

			$data = $this->getRequest()->getPost('billing', array());


			if (!Mage::getSingleton('customer/session')->isLoggedIn()){
				if (isset($data['create_account']) && $data['create_account']==1){
					$this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
				}else{
					$this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
					unset($data['customer_password']);
					unset($data['confirm_password']);
				}
			}else{
				$this->getOnepage()->saveCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
			}



			$this->checkNewslatter();


			$customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

			if (isset($data['email'])) {
				$data['email'] = trim($data['email']);
			}
			$result = $this->getOnepage()->saveBilling($data, $customerAddressId);

			if (!isset($result['error'])) {
				/* check quote for virtual */
				if ($this->getOnepage()->getQuote()->isVirtual()) {
					$result['isVirtual'] = true;
				};

				//load shipping methods block if shipping as billing;
				$data = $this->getRequest()->getPost('billing', array());
                $result['step'] = 'save_billing';
				if (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
					$result['step'] = 'save_billing_use_for_shipping';
//                    $result['payments'] = $this->_getPaymentMethodsHtml();
//                    $result['review'] = $this->_getReviewHtml();
				}


			}else{

				$responseData['error'] = true;
				$responseData['message'] = $result['message'];
			}

			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		}
	}


	/**
	 * Shipping save action
	 */
	public function saveShippingAction(){
		if ($this->_expireAjax()) {
            return;
        }

		//TODO create response if post not exist
		$responseData = array();

		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost('shipping', array());
			$customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
			$result = $this->getOnepage()->saveShipping($data, $customerAddressId);

		}

		if (isset($resultBilling['error'])){
			$responseData['error'] = true;
			$responseData['message'] = $result['message'];
			$responseData['messageBlock'] = 'shipping';
		}else{

			$responseData['shipping'] = $this->_getShippingMethodsHtml();
		    $responseData['step'] = 'save_shipping';
		}

		$this->getResponse()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));

	}



	/**
	 * Shipping method save action
	 */
	public function saveShippingMethodAction(){
		if ($this->_expireAjax()) {
            return;
        }
        $responseData = array();

		if ($this->getRequest()->isPost()) {

			$this->checkNewslatter();

			$data = $this->getRequest()->getPost('shipping_method', '');
			$result = $this->getOnepage()->saveShippingMethod($data);
			/*
			 $result will have erro data if shipping method is empty
			*/
			if(!$result) {
				Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
											array('request'=>$this->getRequest(),
											'quote'=>$this->getOnepage()->getQuote())
									);

				$this->getOnepage()->getQuote()->collectTotals();
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                $responseData['shipping_method'] = $this->_getShippingMethodsHtml();
                $responseData['payments'] = $this->_getPaymentMethodsHtml();
				$responseData['review'] = $this->_getReviewHtml();
				/*$result['update_section'] = array(
						'name' => 'payment-method',
						'html' => $this->_getPaymentMethodsHtml()
				);*/
			}
			$this->getOnepage()->getQuote()->collectTotals()->save();



			$this->getResponse()->setHeader('Content-type','application/json', true);
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
		}
	}

	public function reviewAction(){
		if ($this->_expireAjax()) {
			return;
		}
		$responseData = array();
		$responseData['review'] = $this->_getReviewHtml();
		$this->getResponse()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
	}


	public function paymentsAction(){
		if ($this->_expireAjax()) {
			return;
		}
		$responseData = array();
		$responseData['payments'] = $this->_getPaymentMethodsHtml();
		$this->getResponse()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
	}


	public function savePaymentAction()
	{
		if ($this->_expireAjax()) {
            return;
        }

		try {
			/*if (!$this->getRequest()->isPost()) {
				$this->_ajaxRedirectResponse();
				return;
			}*/

			// set payment to quote
			$result = array();
			$data = $this->getRequest()->getPost('payment', array());
			$result = $this->getOnepage()->savePayment($data);

			// get section and redirect data
			$redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
			if (empty($result['error'])) {

				$this->loadLayout('checkout_onepage_review');

				$result['review'] = $this->_getReviewHtml();

			}
			if ($redirectUrl) {
				$result['redirect'] = $redirectUrl;
			}
		} catch (Mage_Payment_Exception $e) {
			if ($e->getFields()) {
				$result['fields'] = $e->getFields();
			}
			$result['error'] = $e->getMessage();
		} catch (Mage_Core_Exception $e) {
			$result['error'] = $e->getMessage();
		} catch (Exception $e) {
			Mage::logException($e);
			$result['error'] = $this->__('Unable to set Payment Method.');
		}

		$this->getResponse()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}



	public function saveOrderAction()
	{
		//if (!$this->_validateFormKey()) {
			//$this->_redirect('*/*');
			//return;
		//}
		if ($this->_expireAjax()) {
            return;
        }
        $delivery  = $this->getRequest()->getParam('shipping_arrival_date');
        if (!empty($delivery)){
            Mage::getSingleton('core/session')->setOpcDeliveryDate($delivery);
        }else{
            Mage::getSingleton('core/session')->unsOpcDeliveryDate($delivery);
        }
		$version = Mage::getVersionInfo();

		$result = array();
		try {
//			$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
//			if ($requiredAgreements) {
//				$postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
//				$diff = array_diff($requiredAgreements, $postedAgreements);
//				if ($diff) {
//					$result['error'] = $this->__('Please agree to all the terms and conditions before placing the order.');
//					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
//					return;
//				}
//			}

			$data = $this->getRequest()->getPost('payment', array());
			if ($data) {
				/** Magento CE 1.8 version**/
				if ($version['minor'] == 8){

					$data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
					| Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
					| Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
					| Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
					| Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;

				}
				$this->getOnepage()->getQuote()->getPayment()->importData($data);
			}

			$this->getOnepage()->saveOrder();

			/** Magento CE 1.6 version**/
			if ($version['minor']==6){
				$storeId = Mage::app()->getStore()->getId();
				$paymentHelper = Mage::helper("payment");
				$zeroSubTotalPaymentAction = $paymentHelper->getZeroSubTotalPaymentAutomaticInvoice($storeId);
				if ($paymentHelper->isZeroSubTotal($storeId)
				&& $this->_getOrder()->getGrandTotal() == 0
				&& $zeroSubTotalPaymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
				&& $paymentHelper->getZeroSubTotalOrderStatus($storeId) == 'pending') {
					$invoice = $this->_initInvoice();
					$invoice->getOrder()->setIsInProcess(true);
					$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
					$transactionSave->save();
				}
			}

			$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();

		} catch (Mage_Payment_Model_Info_Exception $e) {

			$message = $e->getMessage();

			if (!empty($message)) {
				$result['error_messages'] = $message;
			}

			$result['payment'] = $this->_getPaymentMethodsHtml();

		} catch (Mage_Core_Exception $e) {
			Mage::logException($e);

			Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());

			$result['error'] = $e->getMessage();

			$gotoSection = $this->getOnepage()->getCheckout()->getGotoSection();
			if ($gotoSection) {
				$this->getOnepage()->getCheckout()->setGotoSection(null);
			}

			$updateSection = $this->getOnepage()->getCheckout()->getUpdateSection();

			if ($updateSection) {
				$this->getOnepage()->getCheckout()->setUpdateSection(null);
			}

		} catch (Exception $e) {
			Mage::logException($e);
			Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
			$result['error'] = $this->__('There was an error processing your order. Please contact us or try again later.');
		}

		$this->getOnepage()->getQuote()->save();
		/**
		 * when there is redirect to third party, we don't want to save order yet.
		 * we will save the order in return action.
		*/
		if (isset($redirectUrl)) {
			$result['redirect'] = $redirectUrl;
		}else{
			$result['redirect'] = Mage::getUrl('checkout/onepage/success', array('_secure'=>true)) ;
		}


		$this->getResponse()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}


	/** TODO MOVE TO CUSTOMER CONTROLLER **/
	protected function _getSessionCustomer(){
		return Mage::getSingleton('customer/session');
	}

	public function forgotpasswordAction(){
		$response = array();
		$email = (string) $this->getRequest()->getPost('email');

		if ($email) {
			if (!Zend_Validate::is($email, 'EmailAddress')) {
				$this->_getSessionCustomer()->setForgottenEmail($email);

				$response['error'] = 1;
				$response['message'] = $this->__('Invalid email address.');
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
				return;
			}

			/** @var $customer Mage_Customer_Model_Customer */
			$customer = Mage::getModel('customer/customer')
					->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
					->loadByEmail($email);

			if ($customer->getId()) {
				try {
					$newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
					$customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
					$customer->sendPasswordResetConfirmationEmail();
				} catch (Exception $exception) {

					$response['error'] = 1;
					$response['message'] = $exception->getMessage();
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));

					return;
				}
			}
			$response['message']  = Mage::helper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('customer')->htmlEscape($email));
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;
		} else {


			$response['error'] = 1;
			$response['message'] = $this->__('Please enter your email.');
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));

			return;
		}
	}

	public function commentAction(){
		if ($this->_expireAjax()) {
			return;
		}
		$comment  = $this->getRequest()->getParam('comment');
		if (!empty($comment)){
			Mage::getSingleton('core/session')->setOpcOrderComment($comment);
		}else{
			Mage::getSingleton('core/session')->unsOpcOrderComment($comment);
		}
		return;
	}

    public function updateCartAction()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $cartData = $this->getRequest()->getParam('cart');
        if (is_array($cartData)) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }
            $cart = Mage::getSingleton('checkout/cart');
            $cartData = $cart->suggestItemsQty($cartData);
            $cart_update = $cart->updateItems($cartData);
            if(!$this->getOnepage()->getQuote()->getHasError()){
                $cart_update->save();
            }
        }
        $checkoutSession->setCartWasUpdated(true);
        if ($cart->getQuote()->getItemsCount() == 0) {
            $result['cart_is_empty'] = true;
        } else {
            $result['review'] = $this->_getReviewHtml();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function clearCartItemAction()
    {
        $id = (int)$this->getRequest()->getPost('id');
        if ($id) {
            $cart = Mage::getSingleton('checkout/cart');
            $checkoutSession = Mage::getSingleton('checkout/session');
            try {
                $cart->removeItem($id)
                    ->save();
                $checkoutSession->setCartWasUpdated(true);
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Cannot remove the item.'));
                Mage::logException($e);
            }

        }
        if ($cart->getQuote()->getItemsCount() == 0) {
            $result['cart_is_empty'] = true;
        } else {
            $result['review'] = $this->_getReviewHtml();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function updateLocationAction(){
        $countryId = $this->getRequest()->getPost('country_id', null);
        $postCode = $this->getRequest()->getPost('post_code', null);
        $region = $this->getRequest()->getPost('region', null);
        $regionId = $this->getRequest()->getPost('region_id', null);
        $cityId = $this->getRequest()->getPost('city_id', null);
        if ($countryId) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setCountryId($countryId);
            $this->getOnepage()->getQuote()->getShippingAddress()->setCountryId($countryId)
                ->setCollectShippingRates(true);
        }
        if ($postCode) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setPostcode($postCode);
            $this->getOnepage()->getQuote()->getShippingAddress()->setPostcode($postCode)
                ->setCollectShippingRates(true);
        }
        if ($region) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setRegion($region);
            $this->getOnepage()->getQuote()->getShippingAddress()->setRegion($region)
                ->setCollectShippingRates(true);
        }
        if ($regionId) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setRegionId($regionId);
            $this->getOnepage()->getQuote()->getShippingAddress()->setRegionId($regionId)
                ->setCollectShippingRates(true);
        }
        if ($cityId) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setCity($cityId);
            $this->getOnepage()->getQuote()->getShippingAddress()->setCity($cityId)
                ->setCollectShippingRates(true);
        }
        $result['shipping'] = $this->_getShippingMethodsHtml();
        $result['payments'] = $this->_getPaymentMethodsHtml();
        $result['review'] = $this->_getReviewHtml();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function validateEmailAction(){
        $email = $this->getRequest()->getPost('email',null);
        $result['email_exist'] = false;
        $quote = new Mage_Sales_Model_Quote();
        $store = $quote->getStoreId();
        $guestCheckout = Mage::getStoreConfigFlag(Mage_Checkout_Helper_Data::XML_PATH_GUEST_CHECKOUT, $store);
        if($email && $guestCheckout != 1){
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);
            if ($customer->getId()) {
                $result['email_exist'] = true;
                $result['err_content'] = 'Email address already registered. Please log in or use another email address.';
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}