<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Adminhtml_ItemSlider_SlidesController extends Mage_Adminhtml_Controller_Action{

	protected function _initItemslider(){
		$itemsliderId  = (int) $this->getRequest()->getParam('id');
		$itemslider	= Mage::getModel('itemslider/slides');
		if ($itemsliderId) {
			$itemslider->load($itemsliderId);
		}
		Mage::register('current_itemslider', $itemslider);
		return $itemslider;
	}

	public function indexAction() {
		$this->loadLayout();
		$this->_title(Mage::helper('itemslider')->__('Sliders'))
			 ->_title(Mage::helper('itemslider')->__('Manage Sliders'));
		$this->renderLayout();
	}

	public function gridAction() {
//		$this->loadLayout()->renderLayout();
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('itemslider/adminhtml_slides_grid')->toHtml()
        );
	}

	public function editAction() {
		$itemsliderId	= $this->getRequest()->getParam('id');
		$itemslider  	= $this->_initItemslider();
		if ($itemsliderId && !$itemslider->getId()) {
			$this->_getSession()->addError(Mage::helper('itemslider')->__('This Sliders no longer exists.'));
			$this->_redirect('*/*/');
			return;
		}
		$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
		if (!empty($data)) {
			$itemslider->setData($data);
		}
		Mage::register('itemslider_data', $itemslider);
		$this->loadLayout();
		$this->_title(Mage::helper('itemslider')->__('Sliders'))
			 ->_title(Mage::helper('itemslider')->__('Manage Sliders'));
		if ($itemslider->getId()){
			$this->_title($itemslider->getGroupName());
		}
		else{
			$this->_title(Mage::helper('itemslider')->__('Add Slider'));
		}
		if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) { 
			$this->getLayout()->getBlock('head')->setCanLoadTinyMce(true); 
		}
		$this->renderLayout();
	}

	public function newAction() {
		$this->_forward('edit');
	}

	public function saveAction() {
		if ($data = $this->getRequest()->getPost('itemslider')) {
			try {
				$itemslider = $this->_initItemslider();
				$itemslider->addData($data);
				$itemslider->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itemslider')->__('Slider was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);
				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $itemslider->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} 
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
			catch (Exception $e) {
				Mage::logException($e);
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was a problem saving the Slider.'));
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
		}
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Unable to find Slider to save.'));
		$this->_redirect('*/*/');
	}

	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0) {
			try {
				$itemslider = Mage::getModel('itemslider/slides');
				$itemslider->setId($this->getRequest()->getParam('id'))->delete();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itemslider')->__('Slider was successfully deleted.'));
				$this->_redirect('*/*/');
				return; 
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error deleteing Slider.'));
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				Mage::logException($e);
				return;
			}
		}
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Could not find Slider to delete.'));
		$this->_redirect('*/*/');
	}

	public function massDeleteAction() {
		$itemsliderIds = $this->getRequest()->getParam('slider');
		if(!is_array($itemsliderIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Please select manage Sliders to delete.'));
		}
		else {
			try {
				foreach ($itemsliderIds as $itemsliderId) {
					$itemslider = Mage::getModel('itemslider/slides');
					$itemslider->setId($itemsliderId)->delete();
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itemslider')->__('Total of %d manage Sliders were successfully deleted.', count($itemsliderIds)));
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error deleteing manage Sliders.'));
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/index');
	}

	public function massStatusAction(){
		$itemsliderIds = $this->getRequest()->getParam('slider');
		if(!is_array($itemsliderIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Please select Slider.'));
		} 
		else {
			try {
				foreach ($itemsliderIds as $itemsliderId) {
				$itemslider = Mage::getSingleton('itemslider/slides')->load($itemsliderId)
							->setStatus($this->getRequest()->getParam('status'))
							->setIsMassupdate(true)
							->save();
				}
				$this->_getSession()->addSuccess($this->__('Total of %d Slider were successfully updated.', count($itemsliderIds)));
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error updating Slider.'));
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/index');
	}

}