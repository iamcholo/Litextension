<?php
require_once Mage::getModuleDir('controllers', 'LitExtension_Wysiwyg').'/Adminhtml/UtilsController.php';

class LitExtension_Wysiwyg_Adminhtml_UploadController extends LitExtension_Wysiwyg_Adminhtml_UtilsController
{
    public function uploadAction(){
        Mage::getModel('lewysiwyg/upload')->Upload();
    }
}