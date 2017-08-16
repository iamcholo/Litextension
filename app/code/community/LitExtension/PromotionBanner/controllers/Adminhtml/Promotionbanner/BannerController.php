<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Adminhtml_PromotionBanner_BannerController extends Mage_Adminhtml_Controller_Action {

    public function preDispatch() {
        parent::preDispatch();
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('promotionbanner/banner');
    }

    protected function _initBanner() {
        $bannerId = (int) $this->getRequest()->getParam('id');
        $banner = Mage::getModel('promotionbanner/banner');
        if ($bannerId) {
            $banner->load($bannerId);
            $banner = $this->_convertStringToArray($banner);
        }
        Mage::register('current_banner', $banner);
        return $banner;
    }

    public function indexAction() {
        $this->loadLayout();
        $this->_title(Mage::helper('promotionbanner')->__('PromotionBanner'))
                ->_title(Mage::helper('promotionbanner')->__('Banner'));
        $this->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout()->renderLayout();
    }

    public function editAction() {
        $bannerId = $this->getRequest()->getParam('id');
        $banner = $this->_initBanner();
        if ($bannerId && !$banner->getId()) {
            $this->_getSession()->addError(Mage::helper('promotionbanner')->__('This banner no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $banner->setData($data);
        }
        Mage::register('banner_data', $banner);
        $this->loadLayout();
        $this->_title(Mage::helper('promotionbanner')->__('PromotionBanner'))
                ->_title(Mage::helper('promotionbanner')->__('Banner'));
        if ($banner->getId()) {
            $this->_title($banner->getTitle());
        } else {
            $this->_title(Mage::helper('promotionbanner')->__('Add banner'));
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
        if ($data = $this->getRequest()->getPost('banner')) {
            try {
                $data = $this->_filterPostData($data);
                $data = $this->_convertArrayToString($data);
                $banner = $this->_initBanner();
                $banner->addData($data);
                $easing_in_id = $data['easing_in_id'];
                $easing_in = $this->_getEasingName($easing_in_id);
                $banner->setData('easing_in', $easing_in);
                $easing_out_id = $data['easing_out_id'];
                $easing_out = $this->_getEasingName($easing_out_id);
                $banner->setData('easing_out', $easing_out);
                $banner->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('promotionbanner')->__('Banner was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $banner->getId()));
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
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('There was a problem saving the banner.'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('Unable to find banner to save.'));
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $banner = Mage::getModel('promotionbanner/banner');
                $banner->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('promotionbanner')->__('Banner was successfully deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('There was an error deleteing banner.'));
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('Could not find banner to delete.'));
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $bannerIds = $this->getRequest()->getParam('banner');
        if (!is_array($bannerIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('Please select banner to delete.'));
        } else {
            try {
                foreach ($bannerIds as $bannerId) {
                    $banner = Mage::getModel('promotionbanner/banner');
                    $banner->setId($bannerId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('promotionbanner')->__('Total of %d banner were successfully deleted.', count($bannerIds)));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('There was an error deleteing banner.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction() {
        $bannerIds = $this->getRequest()->getParam('banner');
        if (!is_array($bannerIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('Please select banner.'));
        } else {
            try {
                foreach ($bannerIds as $bannerId) {
                    $banner = Mage::getSingleton('promotionbanner/banner')->load($bannerId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d banner were successfully updated.', count($bannerIds)));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionbanner')->__('There was an error updating banner.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    protected function _uploadAndGetName($input, $destinationFolder, $data) {
        try {
            if (isset($data[$input]['delete'])) {
                return '';
            } else {
                $uploader = new Varien_File_Uploader($input);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);
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

    protected function _getEasingName($id) {
        switch ($id) {
            case "1":
                $easing_name = "easeInQuad";
                break;
            case "2":
                $easing_name = "easeOutQuad";
                break;
            case "3":
                $easing_name = "easeInOutQuad";
                break;
            case "4":
                $easing_name = "easeInCubic";
                break;
            case "5":
                $easing_name = "easeOutCubic";
                break;
            case "6":
                $easing_name = "easeInOutCubic";
                break;
            case "7":
                $easing_name = "easeInQuart";
                break;
            case "8":
                $easing_name = "easeOutQuart";
                break;
            case "9":
                $easing_name = "easeInOutQuart";
                break;
            case "10":
                $easing_name = "easeInQuint";
                break;
            case "11":
                $easing_name = "easeOutQuint";
                break;
            case "12":
                $easing_name = "easeInOutQuint";
                break;
            case "13":
                $easing_name = "easeInSine";
                break;
            case "14":
                $easing_name = "easeOutSine";
                break;
            case "15":
                $easing_name = "easeInOutSine";
                break;
            case "16":
                $easing_name = "easeInExpo";
                break;
            case "17":
                $easing_name = "easeOutExpo";
                break;
            case "18":
                $easing_name = "easeInOutExpo";
                break;
            case "19":
                $easing_name = "easeInCirc";
                break;
            case "20":
                $easing_name = "easeOutCirc";
                break;
            case "21":
                $easing_name = "easeInOutCirc";
                break;
            case "22":
                $easing_name = "easeInElastic";
                break;
            case "23":
                $easing_name = "easeOutElastic";
                break;
            case "24":
                $easing_name = "easeInOutElastic";
                break;
            case "25":
                $easing_name = "easeInBack";
                break;
            case "26":
                $easing_name = "easeOutBack";
                break;
            case "27":
                $easing_name = "easeInOutBack";
                break;
            case "28":
                $easing_name = "easeInBounce";
                break;
            case "29":
                $easing_name = "easeOutBounce";
                break;
            case "30":
                $easing_name = "easeInOutBounce";
                break;
            default:
                $easing_name = "easeInQuad";
        }
        return $easing_name;
    }

    protected function _filterPostData($data) {
        $data = $this->_filterDates($data, array('start_date', 'end_date'));
        return $data;
    }

    protected function _convertArrayToString($data){
        $showAt = $data['shows'];
        $show = serialize($showAt);
        $data['show_at'] = $show;
        return $data;
    }

    protected function _convertStringToArray($data){
        $show = $data['show_at'];
        $result = unserialize($show);
        $data['show_at'] = $result;
        return $data;
    }
}