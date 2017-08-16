<?php
require_once Mage::getModuleDir('controllers', 'LitExtension_Wysiwyg').'/Adminhtml/UtilsController.php';

class LitExtension_Wysiwyg_Adminhtml_DialogController extends LitExtension_Wysiwyg_Adminhtml_UtilsController{

    const USE_ACCESS_KEYS = false;

    public function dialogAction(){
        $helper = Mage::helper('lewysiwyg');
        $source_upload_dir = $helper->setUploadDir();
        $thumb_upload_dir = $helper->setThumbsBasePath();
        $file = new Varien_Io_File();

        if ($source_upload_dir && !file_exists($source_upload_dir)){
            $file->mkdir($source_upload_dir, 0777);
        }
        if ($thumb_upload_dir && !file_exists($thumb_upload_dir)){
            $file->mkdir($thumb_upload_dir, 0777);
        }

        if (self::USE_ACCESS_KEYS == TRUE){
            if (!isset($_GET['akey'], $access_keys) || empty($access_keys)){
                die('Access Denied!');
            }

            $_GET['akey'] = strip_tags(preg_replace( "/[^a-zA-Z0-9\._-]/", '', $_GET['akey']));

            if (!in_array($_GET['akey'], $access_keys)){
                die('Access Denied!');
            }

        }

        $_SESSION['RF']["verify"] = "RESPONSIVEfilemanager";

        if(isset($_POST['submit'])){
            Mage::getModel('lewysiwyg/upload')->Upload();
        }
        else {
            $this->loadLayout();
            $block = $this->getLayout()->createBlock(
                'Mage_Core_Block_Template',
                'lewysiwyg/adminhtml_dialog',
                array('template' => 'le_wysiwyg/index.phtml')
            );
            echo $block->toHtml();
        }
    }

    public function indexAction()
    {}
}