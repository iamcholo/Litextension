<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Adminhtml_LEImageSlider_SlideController extends Mage_Adminhtml_Controller_Action {

    public function preDispatch() {
        parent::preDispatch();
        $group = $this->getRequest()->getParam('group');
        if(!isset($group) || $group == ''){
            $this->_redirect('*/leimageslider_group/index');
            return ;
        }
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('leimageslider/group');
    }

    protected function _initLeimageslider() {
        $leimagesliderId = (int) $this->getRequest()->getParam('id');
        $leimageslider = Mage::getModel('leimageslider/slide');
        if ($leimagesliderId) {
            $leimageslider->load($leimagesliderId);
        }
        Mage::register('current_leimageslider', $leimageslider);
        return $leimageslider;
    }

    public function gridAction() {
        $this->loadLayout()->renderLayout();
    }

    public function editAction() {
        $leimagesliderId = $this->getRequest()->getParam('id');
        $leimageslider = $this->_initLeimageslider();
        if ($leimagesliderId && !$leimageslider->getId()) {
            $this->_getSession()->addError(Mage::helper('leimageslider')->__('This slide no longer exists.'));
            $this->_redirectToGroup();
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
            $this->_title(Mage::helper('leimageslider')->__('Add Image'));
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
                $imageName = $this->_uploadAndGetName('image', Mage::helper('leimageslider/image_leimageslider')->getImageBaseDir(), $data, $this->_getImageExtension());
                if ($imageName != "") {
                    $leimageslider->setData('image', $imageName);
                } else{
                    if($this->getRequest()->getParam('id') != null){
                        $imageName = $data['image_tmp'];
                        $leimageslider->setData('image', $imageName);
                    }
                }
                $imgPath = Mage::getBaseUrl('media') . "leimageslider/image" . $imageName;
                $filethumbgrid = '<img src="' . $imgPath . '" border="0" style="max-width: 140px; max-height: 70px;"  />';
                $leimageslider->setData('filethumbgrid', $filethumbgrid);
                if($this->getRequest()->getParam('group')){
                    $leimageslider->setData('group_id',$this->getRequest()->getParam('group'));
                }
                $leimageslider->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('leimageslider')->__('Image was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirectToEdit();
                    return;
                }
                if($this->getRequest()->getParam('next')){
                    $this->_redirectToGroup();
                    return;
                }
                $this->_redirectToGroup();
                return;
            } catch (Mage_Core_Exception $e) {
                if (isset($data['image']['value'])) {
                    $data['image'] = $data['image']['value'];
                }
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirectToEdit();
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                if (isset($data['image']['value'])) {
                    $data['image'] = $data['image']['value'];
                }
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('There was a problem saving the image.'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirectToEdit();
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('Unable to find image to save.'));
        $this->_redirectToGroup();
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $leimageslider = Mage::getModel('leimageslider/slide');
                $leimageslider->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('leimageslider')->__('Image was successfully deleted.'));
                $this->_redirectToGroup();
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirectToEdit();
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('There was an error deleteing image.'));
                $this->_redirectToEdit();
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('leimageslider')->__('Could not find image to delete.'));
        $this->_redirectToGroup();
    }

    protected function _uploadAndGetName($input, $destinationFolder, $data, $extensions = null) {
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

    protected function _redirectToGroup(){
        $this->_redirect('*/leimageslider_group/edit', array('id' => $this->getRequest()->getParam('group'), 'slide' => true));
        return ;
    }

    protected function _redirectToEdit(){
        $this->_redirect('*/*/edit', array('id' =>$this->getRequest()->getParam('id'), 'group' => $this->getRequest()->getParam('group')));
        return;
    }

    protected function _getImageExtension(){
        return array('jpg', 'png', 'jpeg');
    }
}