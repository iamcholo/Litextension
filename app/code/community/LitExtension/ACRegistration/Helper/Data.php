<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_ACRegistration_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getAttributeFileUrl($fileName, $view = false, $front = false, $customerId = null)
    {
        // files directory
        $fileDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'customer';
        $this->checkAndCreateDir($fileDir);

        if ($view) { // URL for download
            if (file_exists($fileDir . DIRECTORY_SEPARATOR . $fileName)) {
                if ($front) {
                    return Mage::getModel('core/url')->getUrl('acregistration/attachment/viewfile', array('customer' => $customerId, 'image' => Mage::helper('core')->urlEncode($fileName)));
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
}
