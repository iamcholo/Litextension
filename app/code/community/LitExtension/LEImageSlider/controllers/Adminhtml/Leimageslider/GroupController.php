<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Adminhtml_LEImageSlider_GroupController extends Mage_Adminhtml_Controller_Action {

    public function preDispatch() {
        parent::preDispatch();
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('leimageslider/group');
    }

    protected function _initLeimageslider() {
        $leimagesliderId = (int) $this->getRequest()->getParam('id');
        $leimageslider = Mage::getModel('leimageslider/group');
        if ($leimagesliderId) {
            $leimageslider->load($leimagesliderId);
        }
        Mage::register('current_leimageslider', $leimageslider);
        return $leimageslider;
    }

    public function indexAction() {

        $this->loadLayout();
        $this->_title(Mage::helper('leimageslider')->__('Image Slider'))
                ->_title(Mage::helper('leimageslider')->__('Image Slider'));
        $this->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout()->renderLayout();
    }

    public function editAction() {
        $leimagesliderId = $this->getRequest()->getParam('id');
        $leimageslider = $this->_initLeimageslider();
        if ($leimagesliderId && !$leimageslider->getId()) {
            $this->_getSession()->addError(Mage::helper('leimageslider')->__('This group no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $leimageslider->setData($data);
        }
        Mage::register('leimageslider_data', $leimageslider);
        $this->loadLayout();
        $this->_title(Mage::helper('leimageslider')->__('Image Slider'))
                ->_title(Mage::helper('leimageslider')->__('Image slider'));
        if ($leimageslider->getId()) {
            $this->_title($leimageslider->getId());
        } else {
            $this->_title(Mage::helper('leimageslider')->__('Add group'));
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
        if ($data = $this->getRequest()->getPost('leimageslider')) {
            try {
                $leimageslider = $this->_initLeimageslider();
                $leimageslider->addData($data);
                $leimageslider->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('leimageslider')->__('Group was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $leimageslider->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('There was a problem saving the group.'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('Unable to find group to save.'));
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $leimageslider = Mage::getModel('leimageslider/group');
                $leimageslider->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('leimageslider')->__('Group was successfully deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('There was an error deleteing group.'));
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('Could not find group to delete.'));
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $leimagesliderIds = $this->getRequest()->getParam('leimageslider');
        if (!is_array($leimagesliderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('Please select group to delete.'));
        } else {
            try {
                foreach ($leimagesliderIds as $leimagesliderId) {
                    $leimageslider = Mage::getModel('leimageslider/group');
                    $leimageslider->setId($leimagesliderId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('leimageslider')->__('Total of %d group were successfully deleted.', count($leimagesliderIds)));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('There was an error deleteing group.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction() {
        $leimagesliderIds = $this->getRequest()->getParam('leimageslider');
        if (!is_array($leimagesliderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('Please select group.'));
        } else {
            try {
                foreach ($leimagesliderIds as $leimagesliderId) {
                    $leimageslider = Mage::getSingleton('leimageslider/group')->load($leimagesliderId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d group were successfully updated.', count($leimagesliderIds)));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('There was an error updating group.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

}