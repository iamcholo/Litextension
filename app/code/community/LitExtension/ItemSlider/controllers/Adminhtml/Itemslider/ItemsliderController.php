<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Adminhtml_ItemSlider_ItemsliderController extends Mage_Adminhtml_Controller_Action{

	protected function _initItemslider(){
		$itemsliderId  = (int) $this->getRequest()->getParam('id');
		$itemslider	= Mage::getModel('itemslider/group');
		if ($itemsliderId) {
			$itemslider->load($itemsliderId);
		}
		Mage::register('current_itemslider', $itemslider);
		return $itemslider;
	}

	public function indexAction() {
		$this->loadLayout();
		$this->_title(Mage::helper('itemslider')->__('ItemSlider'))
			 ->_title(Mage::helper('itemslider')->__('Manage Slider Tabs'));
		$this->renderLayout();
	}

	public function gridAction() {
		$this->loadLayout()->renderLayout();
	}

	public function editAction() {
		$itemsliderId	= $this->getRequest()->getParam('id');
		$itemslider  	= $this->_initItemslider();
		if ($itemsliderId && !$itemslider->getId()) {
			$this->_getSession()->addError(Mage::helper('itemslider')->__('This Slider Tab no longer exists.'));
			$this->_redirect('*/*/');
			return;
		}
		$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
		if (!empty($data)) {
			$itemslider->setData($data);
		}
		Mage::register('itemslider_data', $itemslider);
		$this->loadLayout();
		$this->_title(Mage::helper('itemslider')->__('ItemSlider'))
			 ->_title(Mage::helper('itemslider')->__('Manage Slider Tabs'));
		if ($itemslider->getId()){
			$this->_title($itemslider->getGroupName());
		}
		else{
			$this->_title(Mage::helper('itemslider')->__('Add Slider Tab'));
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
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itemslider')->__('Slider Tab was successfully saved'));
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
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was a problem saving the Slider Tab.'));
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
		}
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Unable to find Slider Tab to save.'));
		$this->_redirect('*/*/');
	}

	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0) {
			try {
				$itemslider = Mage::getModel('itemslider/group');
				$itemslider->setId($this->getRequest()->getParam('id'))->delete();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itemslider')->__('Slider Tab was successfully deleted.'));
				$this->_redirect('*/*/');
				return; 
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error deleteing Slider Tab.'));
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				Mage::logException($e);
				return;
			}
		}
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Could not find Slider Tab to delete.'));
		$this->_redirect('*/*/');
	}

	public function massDeleteAction() {
		$itemsliderIds = $this->getRequest()->getParam('itemslider');
		if(!is_array($itemsliderIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Please select manage Slider Tabs to delete.'));
		}
		else {
			try {
				foreach ($itemsliderIds as $itemsliderId) {
					$itemslider = Mage::getModel('itemslider/group');
					$itemslider->setId($itemsliderId)->delete();
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itemslider')->__('Total of %d manage Slider Tabs were successfully deleted.', count($itemsliderIds)));
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error deleteing manage Slider Tabs.'));
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/index');
	}

	public function massStatusAction(){
		$itemsliderIds = $this->getRequest()->getParam('itemslider');
		if(!is_array($itemsliderIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Please select manage Slider Tabs.'));
		} 
		else {
			try {
				foreach ($itemsliderIds as $itemsliderId) {
				$itemslider = Mage::getSingleton('itemslider/group')->load($itemsliderId)
							->setStatus($this->getRequest()->getParam('status'))
							->setIsMassupdate(true)
							->save();
				}
				$this->_getSession()->addSuccess($this->__('Total of %d manage Slider Tabs were successfully updated.', count($itemsliderIds)));
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error updating manage Slider Tabs.'));
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/index');
	}

	public function massItemTypeAction(){
		$itemsliderIds = $this->getRequest()->getParam('itemslider');
		if(!is_array($itemsliderIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Please select manage Slider Tabs.'));
		} 
		else {
			try {
				foreach ($itemsliderIds as $itemsliderId) {
				$itemslider = Mage::getSingleton('itemslider/itemslider')->load($itemsliderId)
							->setItemType($this->getRequest()->getParam('flag_item_type'))
							->setIsMassupdate(true)
							->save();
				}
				$this->_getSession()->addSuccess($this->__('Total of %d manage Slider Tabs were successfully updated.', count($itemsliderIds)));
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error updating manage Slider Tabs.'));
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/index');
	}

	public function massEnableLinkAction(){
		$itemsliderIds = $this->getRequest()->getParam('itemslider');
		if(!is_array($itemsliderIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('Please select manage Slider Tabs.'));
		} 
		else {
			try {
				foreach ($itemsliderIds as $itemsliderId) {
				$itemslider = Mage::getSingleton('itemslider/group')->load($itemsliderId)
							->setEnableLink($this->getRequest()->getParam('flag_enable_link'))
							->setIsMassupdate(true)
							->save();
				}
				$this->_getSession()->addSuccess($this->__('Total of %d manage Slider Tabs were successfully updated.', count($itemsliderIds)));
			}
			catch (Mage_Core_Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itemslider')->__('There was an error updating manage Slider Tabs.'));
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/index');
	}

}