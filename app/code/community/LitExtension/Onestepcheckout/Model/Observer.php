<?php
class LitExtension_Onestepcheckout_Model_Observer{

	public function newsletter($observer){
		$_session = Mage::getSingleton('core/session');

		$newsletterFlag = $_session->getIsSubscribed();
		if ($newsletterFlag==true){
			
			$email = $observer->getEvent()->getOrder()->getCustomerEmail();
			
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
	        if($subscriber->getStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED &&
	                $subscriber->getStatus() != Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
	            $subscriber->setImportMode(true)->subscribe($email);
	            
	            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
	            $subscriber->sendConfirmationSuccessEmail();
	        }
			
		}
		
	}
	
	
	public function applyComment($observer){
		$comment = Mage::getSingleton('core/session')->getOpcOrderComment();
        $delivery = Mage::getSingleton('core/session')->getOpcDeliveryDate();
        if(!empty($delivery)){
            $delivery = "<strong>Delivery Date: </strong>".$delivery.'</br>';
        }
        $comment_delivery = $delivery.$comment;
		if ((Mage::helper('onestepcheckout')->isShowComment() && Mage::helper('onestepcheckout')->getEnabledFields() ) || !empty($comment)){
            $this->saveToCommentOrder($observer,$comment_delivery);
		}else{
            if(!empty($delivery)){
                $this->saveToCommentOrder($observer,$delivery);
            }
        }
	}

    protected function saveToCommentOrder($observer,$key){
        $order = $observer->getData('order');
        try{
            $order->addStatusHistoryComment($key)->setIsVisibleOnFront(true)->setIsCustomerNotified(true);
            $order->save();
            $order->sendOrderUpdateEmail(true, $key);
        }catch(Exception $e){
            Mage::logException($e);
        }
    }

    public function membershipControllerActionPostDispatch($observer)
    {
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        if(Mage::helper('onestepcheckout')->skipShoppingCartPage() &&  Mage::helper('checkout')->canOnepageCheckout() && $quote->hasItems() && !$quote->getHasError() && $quote->validateMinimumAmount()){
            if($observer->getEvent()->getControllerAction()->getFullActionName() == 'checkout_cart_index')
            {
                Mage::dispatchEvent("add_to_cart_after", array('request' => $observer->getControllerAction()->getRequest()));
            }
        }
    }

    public function membershipCheckout($observer)
    {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage', array('_secure'=>true)));
    }
}