<?php
/**
 * @project     AjaxLogin
 * @package LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getAttributeFileUrl($fileName, $view = false, $front = false, $customerId = null)
    {
        // files directory
        $fileDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'customer';
        $this->checkAndCreateDir($fileDir);

        if ($view) { // URL for download
            if (file_exists($fileDir . DIRECTORY_SEPARATOR . $fileName)) {
                if ($front) {
                    return Mage::getModel('core/url')->getUrl('ajaxlogin/attachment/viewfile', array('customer' => $customerId, 'image' => Mage::helper('core')->urlEncode($fileName)));
                } else {
                    return Mage::helper('adminhtml')->getUrl('adminhtml/customer/viewfile', array('image' => Mage::helper('core')->urlEncode($fileName)));
                }
            }
            return '';
        } else { // Path for upload/download
            return $fileDir . DIRECTORY_SEPARATOR;
        }
    }

    public function checkAndCreateDir($path)
    {
        if(!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    public function cleanFileName($fileName)
    {
        return explode(DS, $fileName);
    }

    public function redirect404($frontController)
    {
        $frontController->getResponse()
            ->setHeader('HTTP/1.1','404 Not Found');
        $frontController->getResponse()
            ->setHeader('Status','404 File not found');

        $pageId = Mage::getStoreConfig('web/default/cms_no_route');
        if (!Mage::helper('cms/page')->renderPage($frontController, $pageId)) {
            $frontController->_forward('defaultNoRoute');
        }
    }
}
