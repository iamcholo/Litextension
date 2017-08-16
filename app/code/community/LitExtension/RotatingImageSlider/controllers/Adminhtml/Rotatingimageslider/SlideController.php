<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Adminhtml_RotatingImageSlider_SlideController extends Mage_Adminhtml_Controller_Action {
    
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('rotatingimageslider/slide');
    }
    
    protected function _initRotatingimageslider() {
        $rotatingimagesliderId = (int) $this->getRequest()->getParam('id');
        $rotatingimageslider = Mage::getModel('rotatingimageslider/slide');
        if ($rotatingimagesliderId) {
            $rotatingimageslider->load($rotatingimagesliderId);
        }
        Mage::register('current_rotatingimageslider', $rotatingimageslider);
        return $rotatingimageslider;
    }

    public function indexAction() {
        $this->loadLayout();
        $this->_title(Mage::helper('rotatingimageslider')->__('RotatingImageSlider'))
                ->_title(Mage::helper('rotatingimageslider')->__('Rotatingimageslider'));
        $this->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout()->renderLayout();
    }

    public function editAction() {
        $rotatingimagesliderId = $this->getRequest()->getParam('id');
        $rotatingimageslider = $this->_initRotatingimageslider();
        if ($rotatingimagesliderId && !$rotatingimageslider->getId()) {
            $this->_getSession()->addError(Mage::helper('rotatingimageslider')->__('This slide no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $rotatingimageslider->setData($data);
        }
        Mage::register('rotatingimageslider_data', $rotatingimageslider);
        $this->loadLayout();
        $this->_title(Mage::helper('rotatingimageslider')->__('RotatingImageSlider'))
                ->_title(Mage::helper('rotatingimageslider')->__('Rotatingimageslider'));
        if ($rotatingimageslider->getId()) {
            $this->_title($rotatingimageslider->getName());
        } else {
            $this->_title(Mage::helper('rotatingimageslider')->__('Add slide'));
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
        if ($data = $this->getRequest()->getPost('rotatingimageslider')) {
            try {
                $rotatingimageslider = $this->_initRotatingimageslider();
                $rotatingimageslider->addData($data);
                $imageName = $this->_uploadAndGetName('image', Mage::helper('rotatingimageslider/image_rotatingimageslider')->getImageBaseDir(), $data. $this->_getImageExtension());
                if ($imageName != "") {
                    $rotatingimageslider->setData('image', $imageName);
                } else {
                    if ($this->getRequest()->getParam('id') != null) {
                        $imageName = $data['image_tmp'];
                        $rotatingimageslider->setData('image', $imageName);
                    }
                }
                $imgPath = Mage::getBaseUrl('media') . "rotatingimageslider/image" . $imageName;
                $filethumbgrid = '<img src="' . $imgPath . '" border="0" width="75" height="75" />';
                $rotatingimageslider->setData('filethumbgrid', $filethumbgrid);
                $group_id = $data['group_id'];
                $groupname = $this->_getGroupName($group_id);
                $rotatingimageslider->setData('groupname', $groupname);
                $rotatingimageslider->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rotatingimageslider')->__('Slide was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $rotatingimageslider->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                if (isset($data['image']['value'])) {
                    $data['image'] = $data['image']['value'];
                }
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                if (isset($data['image']['value'])) {
                    $data['image'] = $data['image']['value'];
                }
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('There was a problem saving the slide.'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('Unable to find slide to save.'));
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $rotatingimageslider = Mage::getModel('rotatingimageslider/slide');
                $rotatingimageslider->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rotatingimageslider')->__('Slide was successfully deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('There was an error deleteing slide.'));
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('Could not find slide to delete.'));
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $rotatingimagesliderIds = $this->getRequest()->getParam('rotatingimageslider');
        if (!is_array($rotatingimagesliderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('Please select slide to delete.'));
        } else {
            try {
                foreach ($rotatingimagesliderIds as $rotatingimagesliderId) {
                    $rotatingimageslider = Mage::getModel('rotatingimageslider/slide');
                    $rotatingimageslider->setId($rotatingimagesliderId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rotatingimageslider')->__('Total of %d slide were successfully deleted.', count($rotatingimagesliderIds)));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('There was an error deleteing slide.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction() {
        $rotatingimagesliderIds = $this->getRequest()->getParam('rotatingimageslider');
        if (!is_array($rotatingimagesliderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('Please select slide.'));
        } else {
            try {
                foreach ($rotatingimagesliderIds as $rotatingimagesliderId) {
                    $rotatingimageslider = Mage::getSingleton('rotatingimageslider/slide')->load($rotatingimagesliderId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d slide were successfully updated.', count($rotatingimagesliderIds)));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rotatingimageslider')->__('There was an error updating slide.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    protected function _uploadAndGetName($input, $destinationFolder, $data , $extensions = null) {
        try {
            if (isset($data[$input]['delete'])) {
                return '';
            } else {
                $uploader = new Varien_File_Uploader($input);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);
                if($extensions){
                    $uploader->setAllowedExtensions($extensions);
                }
                $result = $uploader->save($destinationFolder);
                return $result['file'];
            }
        } catch (Exception $e) {
            if ($e->getCode() != Varien_File_Uploader::TMP_NAME_EMPTY) {
                throw $e;
            } else {
                if (isset($data[$input]['value'])) {
                    return $data[$input]['value'];
                }
            }
        }
        return '';
    }

    public function _getGroupName($id) {

        switch ($id) {
            case "1":
                $group_name = "Group 1";
                break;
            case "2":
                $group_name = "Group 2";
                break;
            case "3":
                $group_name = "Group 3";
                break;
            case "4":
                $group_name = "Group 4";
                break;
            default:
                $group_name = "Group 1";
        }

        return $group_name;
    }

    protected function _getImageExtension(){
        return array('img', 'png', 'jpeg');
    }

}