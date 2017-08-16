<?php
require_once Mage::getModuleDir('controllers', 'LitExtension_Wysiwyg').'/Adminhtml/UtilsController.php';

class LitExtension_Wysiwyg_Adminhtml_DownloadController extends LitExtension_Wysiwyg_Adminhtml_UtilsController
{
    public function indexAction(){
        $ext_img = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'); //Images
        $ext_file = array('doc', 'docx','rtf', 'pdf', 'xls', 'xlsx', 'txt', 'csv','html','xhtml','psd','sql','log','fla','xml','ade','adp','mdb','accdb','ppt','pptx','odt','ots','ott','odb','odg','otp','otg','odf','ods','odp','css','ai'); //Files
        $ext_video = array('mov', 'mpeg', 'm4v', 'mp4', 'avi', 'mpg','wma',"flv","webm"); //Video
        $ext_music = array('mp3', 'm4a', 'ac3', 'aiff', 'mid','ogg','wav'); //Audio
        $ext_misc = array('zip', 'rar','gz','tar','iso','dmg'); //Archives

        $ext = array_merge($ext_img, $ext_file, $ext_misc, $ext_video,$ext_music); //allowed extensions

        if(strpos($_POST['path'],'/')===0
            || strpos($_POST['path'],'../')!==FALSE
            || strpos($_POST['path'],'./')===0)
            die('wrong path');

        if(strpos($_POST['name'],'/')!==FALSE)
            die('wrong path');

        $path= Mage::helper('lewysiwyg')->setUploadDir() . $_POST['path'];
        $name=$_POST['name'];

        $info=pathinfo($name);
        if(!in_array($this->fix_strtolower($info['extension']), $ext)){
            die('wrong extension');
        }

        header('Pragma: private');
        header('Cache-control: private, must-revalidate');
        header("Content-Type: application/octet-stream");
        header("Content-Length: " .(string)(filesize($path.$name)) );
        header('Content-Disposition: attachment; filename="'.($name).'"');
        readfile($path.$name);

        exit;
    }
}