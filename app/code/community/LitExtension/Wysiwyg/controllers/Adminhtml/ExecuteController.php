<?php
require_once Mage::getModuleDir('controllers', 'LitExtension_Wysiwyg').'/Adminhtml/UtilsController.php';

class LitExtension_Wysiwyg_Adminhtml_ExecuteController extends LitExtension_Wysiwyg_Adminhtml_UtilsController
{
    public function indexAction(){
        $current_path = Mage::helper('lewysiwyg')->setUploadDir();
        $thumbs_base_path = Mage::getBaseDir(). DS .'media/wysiwyg/thumbs/';

        $ext_img = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'); //Images
        $ext_file = array('doc', 'docx','rtf', 'pdf', 'xls', 'xlsx', 'txt', 'csv','html','xhtml','psd','sql','log','fla','xml','ade','adp','mdb','accdb','ppt','pptx','odt','ots','ott','odb','odg','otp','otg','odf','ods','odp','css','ai'); //Files
        $ext_video = array('mov', 'mpeg', 'm4v', 'mp4', 'avi', 'mpg','wma',"flv","webm"); //Video
        $ext_music = array('mp3', 'm4a', 'ac3', 'aiff', 'mid','ogg','wav'); //Audio
        $ext_misc = array('zip', 'rar','gz','tar','iso','dmg'); //Archives

        $ext = array_merge($ext_img, $ext_file, $ext_misc, $ext_video,$ext_music); //allowed extensions
        $delete_files	 	  = TRUE;
        $create_folders	 	= TRUE;
        $delete_folders	 	= TRUE;
        $rename_files	 	  = TRUE;
        $rename_folders	 	= TRUE;
        $duplicate_files 	= TRUE;
        $chmod_files 	 	  = FALSE; // change file permissions
        $chmod_dirs		 	  = FALSE; // change folder permissions
        $edit_text_files 	  = TRUE; // eg.: txt, log etc.
        $create_text_files 	= FALSE; // only create files with exts. defined in $editable_text_file_exts

        $relative_image_creation                = FALSE; //activate or not the creation of one or more image resized with relative path from upload folder
        $relative_path_from_current_pos         = array('thumb/','thumb/'); //relative path of the image folder from the current position on upload folder
        $relative_image_creation_name_to_prepend= array('','test_'); //name to prepend on filename
        $relative_image_creation_name_to_append = array('_test',''); //name to append on filename

        $fixed_image_creation                   = FALSE; //activate or not the creation of one or more image resized with fixed path from filemanager folder
        $fixed_path_from_filemanager            = array('../test/','../test1/'); //fixed path of the image folder from the current position on upload folder
        $fixed_image_creation_name_to_prepend   = array('','test_'); //name to prepend on filename
        $fixed_image_creation_to_append         = array('_test',''); //name to appendon filename

        $helper = Mage::helper('lewysiwyg');

        $thumb_pos  = strpos($_POST['path_thumb'], $thumbs_base_path);

        if ($thumb_pos !=0
            || @strpos($_POST['path_thumb'],'../',strlen($thumbs_base_path)+$thumb_pos)!==FALSE
            || strpos($_POST['path'],'/')===0
            || strpos($_POST['path'],'../')!==FALSE
            || strpos($_POST['path'],'./')===0)
        {
            die('wrong path');
        }

        if (isset($_SESSION['RF']['language_file']) && file_exists($_SESSION['RF']['language_file'])){
            require_once($_SESSION['RF']['language_file']);
        }
        else {
            die('Language file is missing!');
        }

        $base = $current_path;
        $path = $current_path.$_POST['path'];
        $cycle = TRUE;
        $max_cycles = 50;
        $i = 0;
        while($cycle && $i<$max_cycles)
        {
            $i++;
            if ($path == $base)  $cycle=FALSE;

            if (file_exists(Mage::getBaseDir(). DS . 'js/tiny_mce4/filemanager/config/config.php'))
            {
                require_once(Mage::getBaseDir(). DS . 'js/tiny_mce4/filemanager/config/config.php');
                $cycle = FALSE;
            }
            $path = $this->fix_dirname($path)."/";
            $cycle = FALSE;
        }

        $path = $current_path.$_POST['path'];
        $path_thumb = $_POST['path_thumb'];
        if (isset($_POST['name']))
        {
            $name = $this->fix_filename($_POST['name'],$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith());
            if (strpos($name,'../') !== FALSE) die('wrong name');
        }

        $info = pathinfo($path);
        if (isset($info['extension']) && !(isset($_GET['action']) && $_GET['action']=='delete_folder') && !in_array(strtolower($info['extension']), $ext) && $_GET['action'] != 'create_file')
        {
            die('wrong extension');
        }

        if (isset($_GET['action']))
        {
            switch($_GET['action'])
            {
                case 'delete_file':
                    if ($delete_files){
                        unlink($path);
                        if (file_exists($path_thumb)) unlink($path_thumb);

                        $info=pathinfo($path);
                        if ($relative_image_creation){
                            foreach($relative_path_from_current_pos as $k=>$path)
                            {
                                if ($path!="" && $path[strlen($path)-1]!="/") $path.="/";

                                if (file_exists($info['dirname']."/".$path.$relative_image_creation_name_to_prepend[$k].$info['filename'].$relative_image_creation_name_to_append[$k].".".$info['extension']))
                                {
                                    unlink($info['dirname']."/".$path.$relative_image_creation_name_to_prepend[$k].$info['filename'].$relative_image_creation_name_to_append[$k].".".$info['extension']);
                                }
                            }
                        }

                        if ($fixed_image_creation)
                        {
                            foreach($fixed_path_from_filemanager as $k=>$path)
                            {
                                if ($path!="" && $path[strlen($path)-1] != "/") $path.="/";

                                $base_dir=$path.substr_replace($info['dirname']."/", '', 0, strlen($current_path));
                                if (file_exists($base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension']))
                                {
                                    unlink($base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension']);
                                }
                            }
                        }
                    }
                    break;
                case 'delete_folder':
                    if ($delete_folders){
                        if (is_dir($path_thumb))
                        {
                            $this->deleteDir($path_thumb);
                        }

                        if (is_dir($path))
                        {
                            $this->deleteDir($path);
                            if ($fixed_image_creation)
                            {
                                foreach($fixed_path_from_filemanager as $k=>$paths){
                                    if ($paths!="" && $paths[strlen($paths)-1] != "/") $paths.="/";

                                    $base_dir=$paths.substr_replace($path, '', 0, strlen($current_path));
                                    if (is_dir($base_dir)) $this->deleteDir($base_dir);
                                }
                            }
                        }
                    }
                    break;
                case 'create_folder':
                    if ($create_folders)
                    {
                        $this->create_folder( str_replace('/', DS, $this->fix_path($path,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith()) ),str_replace('/', DS , $this->fix_path($path_thumb,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith())) );
                        //$this->create_folder(fix_path($path,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith()),fix_path($path_thumb,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith()));
                    }
                    break;
                case 'rename_folder':
                    if ($rename_folders){
                        $name=$this->fix_filename($name,$helper->setTransliteration(),$helper->setConvertSpaces(),$helper->setReplaceWith());
                        $name=str_replace('.','',$name);

                        if (!empty($name)){
                            if (!$this->rename_folder($path,$name,$helper->setTransliteration(), $helper->setConvertSpaces())) die(lang_Rename_existing_folder);

                            $this->rename_folder($path_thumb,$name,$helper->setTransliteration(), $helper->setConvertSpaces());
                            if ($fixed_image_creation){
                                foreach($fixed_path_from_filemanager as $k=>$paths){
                                    if ($paths!="" && $paths[strlen($paths)-1] != "/") $paths.="/";

                                    $base_dir=$paths.substr_replace($path, '', 0, strlen($current_path));
                                    $this->rename_folder($base_dir,$name,$helper->setTransliteration(),$helper->setConvertSpaces());
                                }
                            }
                        }
                        else {
                            die(lang_Empty_name);
                        }
                    }
                    break;
                case 'create_file':
                    if ($create_text_files === FALSE) {
                        die(sprintf(lang_File_Open_Edit_Not_Allowed, strtolower(lang_Edit)));
                    }

                    if (!isset($editable_text_file_exts) || !is_array($editable_text_file_exts)){
                        $editable_text_file_exts = array();
                    }

                    // check if user supplied extension
                    if (strpos($name, '.') === FALSE){
                        die(lang_No_Extension.' '.sprintf(lang_Valid_Extensions, implode(', ', $editable_text_file_exts)));
                    }

                    // correct name
                    $old_name = $name;
                    $name=$this->fix_filename($name,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith());
                    if (empty($name))
                    {
                        die(lang_Empty_name);
                    }

                    // check extension
                    $parts = explode('.', $name);
                    if (!in_array(end($parts), $editable_text_file_exts)) {
                        die(lang_Error_extension.' '.sprintf(lang_Valid_Extensions, implode(', ', $editable_text_file_exts)));
                    }

                    // correct paths
                    $path = str_replace($old_name, $name, $path);
                    $path_thumb = str_replace($old_name, $name, $path_thumb);

                    // file already exists
                    if (file_exists($path)) {
                        die(lang_Rename_existing_file);
                    }

                    $content = $_POST['new_content'];

                    if (@file_put_contents($path, $content) === FALSE) {
                        die(lang_File_Save_Error);
                    }
                    else {
                        if ($this->is_function_callable('chmod') !== FALSE){
                            chmod($path, 0644);
                        }
                        echo lang_File_Save_OK;
                    }

                    break;
                case 'rename_file':
                    if ($rename_files){
                        $name=$this->fix_filename($name,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith());
                        if (!empty($name))
                        {
                            if (!$this->rename_file($path,$name,$helper->setTransliteration())) die(lang_Rename_existing_file);

                            $this->rename_file($path_thumb,$name,$helper->setTransliteration());

                            if ($fixed_image_creation)
                            {
                                $info=pathinfo($path);

                                foreach($fixed_path_from_filemanager as $k=>$paths)
                                {
                                    if ($paths!="" && $paths[strlen($paths)-1] != "/") $paths.="/";

                                    $base_dir = $paths.substr_replace($info['dirname']."/", '', 0, strlen($current_path));
                                    if (file_exists($base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension']))
                                    {
                                        $this->rename_file($base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension'],$fixed_image_creation_name_to_prepend[$k].$name.$fixed_image_creation_to_append[$k],$helper->setTransliteration());
                                    }
                                }
                            }
                        }
                        else {
                            die(lang_Empty_name);
                        }
                    }
                    break;
                case 'duplicate_file':
                    if ($duplicate_files)
                    {
                        $name=$this->fix_filename($name,$helper->setTransliteration(),$helper->setConvertSpaces(), $helper->setReplaceWith());
                        if (!empty($name))
                        {
                            if (!$this->duplicate_file($path,$name)) die(lang_Rename_existing_file);

                            $this->duplicate_file($path_thumb,$name);

                            if ($fixed_image_creation)
                            {
                                $info=pathinfo($path);
                                foreach($fixed_path_from_filemanager as $k=>$paths)
                                {
                                    if ($paths!="" && $paths[strlen($paths)-1] != "/") $paths.= "/";

                                    $base_dir=$paths.substr_replace($info['dirname']."/", '', 0, strlen($current_path));

                                    if (file_exists($base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension']))
                                    {
                                        $this->duplicate_file($base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension'],$fixed_image_creation_name_to_prepend[$k].$name.$fixed_image_creation_to_append[$k]);
                                    }
                                }
                            }
                        }
                        else
                        {
                            die(lang_Empty_name);
                        }
                    }
                    break;
                case 'paste_clipboard':
                    if ( ! isset($_SESSION['RF']['clipboard_action'], $_SESSION['RF']['clipboard']['path'], $_SESSION['RF']['clipboard']['path_thumb'])
                        || $_SESSION['RF']['clipboard_action'] == ''
                        || $_SESSION['RF']['clipboard']['path'] == ''
                        || $_SESSION['RF']['clipboard']['path_thumb'] == '')
                    {
                        die();
                    }

                    $action = $_SESSION['RF']['clipboard_action'];
                    $data = $_SESSION['RF']['clipboard'];
                    $data['path'] = $current_path.$data['path'];
                    $pinfo = pathinfo($data['path']);

                    // user wants to paste to the same dir. nothing to do here...
                    if ($pinfo['dirname'] == rtrim($path, '/')) {
                        die();
                    }

                    // user wants to paste folder to it's own sub folder.. baaaah.
                    if (is_dir($data['path']) && strpos($path, $data['path']) !== FALSE){
                        die();
                    }

                    // something terribly gone wrong
                    if ($action != 'copy' && $action != 'cut'){
                        die('no action');
                    }

                    // check for writability
                    if ($this->is_really_writable($path) === FALSE || $this->is_really_writable($path_thumb) === FALSE){
                        die(lang_Dir_No_Write.'<br/>'.str_replace('../','',$path).'<br/>'.str_replace('../','',$path_thumb));
                    }

                    // check if server disables copy or rename
                    if ($this->is_function_callable(($action == 'copy' ? 'copy' : 'rename')) === FALSE){
                        die(sprintf(lang_Function_Disabled, ($action == 'copy' ? lcfirst(lang_Copy) : lcfirst(lang_Cut))));
                    }

                    if ($action == 'copy')
                    {
                        $this->rcopy($data['path'], $path);
                        $this->rcopy($data['path_thumb'], $path_thumb);
                    }
                    elseif ($action == 'cut')
                    {
                        $this->rrename($data['path'], $path);
                        $this->rrename($data['path_thumb'], $path_thumb);

                        // cleanup
                        if (is_dir($data['path']) === TRUE){
                            $this->rrename_after_cleaner($data['path']);
                            $this->rrename_after_cleaner($data['path_thumb']);
                        }
                    }

                    // cleanup
                    $_SESSION['RF']['clipboard']['path'] = NULL;
                    $_SESSION['RF']['clipboard']['path_thumb'] = NULL;
                    $_SESSION['RF']['clipboard_action'] = NULL;

                    break;
                case 'chmod':
                    $mode = $_POST['new_mode'];
                    $rec_option = $_POST['is_recursive'];
                    $valid_options = array('none', 'files', 'folders', 'both');
                    $chmod_perm = (is_dir($path) ? $chmod_dirs : $chmod_files);

                    // check perm
                    if ($chmod_perm === FALSE) {
                        die(sprintf(lang_File_Permission_Not_Allowed, (is_dir($path) ? lcfirst(lang_Folders) : lcfirst(lang_Files))));
                    }

                    // check mode
                    if (!preg_match("/^[0-7]{3}$/", $mode)){
                        die(lang_File_Permission_Wrong_Mode);
                    }

                    // check recursive option
                    if (!in_array($rec_option, $valid_options)){
                        die("wrong option");
                    }

                    // check if server disabled chmod
                    if ($this->is_function_callable('chmod') === FALSE){
                        die(sprintf(lang_Function_Disabled, 'chmod'));
                    }

                    $mode = "0".$mode;
                    $mode = octdec($mode);

                    rchmod($path, $mode, $rec_option);

                    break;
                case 'save_text_file':
                    $content = $_POST['new_content'];
                    // $content = htmlspecialchars($content); not needed
                    // $content = stripslashes($content);

                    // no file
                    if (!file_exists($path)) {
                        die(lang_File_Not_Found);
                    }

                    // not writable or edit not allowed
                    if (!is_writable($path) || $edit_text_files === FALSE) {
                        die(sprintf(lang_File_Open_Edit_Not_Allowed, strtolower(lang_Edit)));
                    }

                    if (@file_put_contents($path, $content) === FALSE) {
                        die(lang_File_Save_Error);
                    }
                    else {
                        echo lang_File_Save_OK;
                    }

                    break;
                default:
                    die('wrong action');
            }
        }
    }
}

?>