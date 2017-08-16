<?php

class LitExtension_Wysiwyg_Helper_Data extends Mage_Core_Helper_Abstract{

    public function setBaseUrl(){
        $base_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'wysiwyg';
        return $base_url;
    }

    public function setUploadDir(){
        $upload_dir = Mage::getBaseDir(). DS .'media/wysiwyg/'; // path from base_url to base of upload folder (with start and final /)
        return $upload_dir;
    }

    public function setSrcUploadDir(){
        $src_upload_dir = '/'; // path from base_url to base of upload folder (with start and final /)
        return $src_upload_dir;
    }

    public function setCurrentPath(){
        $current_path = Mage::getBaseDir(). DS .'media/wysiwyg/'; // relative path from filemanager folder to upload folder (with final /)
//        $current_path = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .'wysiwyg/';
        return $current_path;
    }
    public function setThumbsBasePath(){
        $thumbs_base_path = Mage::getBaseDir(). DS .'media/thumbs/'; // relative path from filemanager folder to thumbs folder (with final /)
        return $thumbs_base_path;
    }

    public function setThumbsBaseUrl(){
        $thumbs_base_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'thumbs/'; // relative path from filemanager folder to thumbs folder (with final /)
        return $thumbs_base_url;
    }
    public function setSourceBaseUrl(){
        $source_base_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'wysiwyg/'; // relative path from filemanager folder to thumbs folder (with final /)
        return $source_base_url;
    }

    public function setAccessKeys(){
        $access_keys = array();
        return $access_keys;
    }
    public function setMaxSizeUpload(){
        $MaxSizeUpload = 100; //Mb
        if ((int)(ini_get('post_max_size')) < $MaxSizeUpload){
            $MaxSizeUpload = (int)(ini_get('post_max_size'));
        }
        return $MaxSizeUpload;
    }

    public function setDefaultLanguage(){
        $default_language 	= "en_EN"; //default language file name
        return $default_language;
    }
    public function setIconTheme(){
        $icon_theme = "ico"; //ico or ico_dark you can cusatomize just putting a folder inside filemanager/img
        return $icon_theme;
    }
    public function setShowFolderSize(){
        $show_folder_size 	= TRUE; //Show or not show folder size in list view feature in filemanager (is possible, if there is a large folder, to greatly increase the calculations)
        return $show_folder_size;
    }
    public function setShowSortingBar(){
        $show_sorting_bar 	= TRUE; //Show or not show sorting feature in filemanager
        return $show_sorting_bar;
    }
    public function setTransliteration(){
        $transliteration = FALSE; //active or deactive the transliteration (mean convert all strange characters in A..Za..z0..9 characters)
        return $transliteration;
    }
    public function setConvertSpaces(){
        $convert_spaces  = FALSE; //convert all spaces on files name and folders name with _
        return $convert_spaces;
    }
    public function setReplaceWith(){
        $replace_with  = "_"; //convert all spaces on files name and folders name this value
        return $replace_with;
    }
    public function setLazyLoadingFileNumberThreshold(){
        $lazy_loading_file_number_threshold = 0;
        return $lazy_loading_file_number_threshold;
    }
    public function setImageMmaxWidth(){
        $image_max_width  = 0;
        return $image_max_width;
    }
    public function setImageMaxHeight(){
        $image_max_height = 0;
        return $image_max_height;
    }
    public function setImageMaxMode(){
        $image_max_mode = 'auto';
        return $image_max_mode;
    }
    public function setImageResizing(){
        $image_resizing = FALSE;
        return $image_resizing;
    }
    public function setImageResizingWidth(){
        $image_resizing_width  = 0;
        return $image_resizing_width;
    }
    public function setImageResizingHeight(){
        $image_resizing_height = 0;
        return $image_resizing_height;
    }
    public function setImageResizingMode(){
        $image_resizing_mode = 'auto';
        return $image_resizing_mode;
    }
    public function setImageResizingOverride(){
        $image_resizing_override = FALSE;
        return $image_resizing_override;
    }
    public function setDefaultView(){
        $default_view = 0;
        return $default_view;
    }
    public function setEllipsisTitleAfterAirstRow(){
        $ellipsis_title_after_first_row = TRUE;
        return $ellipsis_title_after_first_row;
    }
    public function setDeleteFiles(){
        $delete_files = TRUE;
        return $delete_files;
    }
    public function setCreateFolders(){
        $create_folders	= TRUE;
        return $create_folders;
    }
    public function setDeleteFolders(){
        $delete_folders = TRUE;
        return $delete_folders;
    }
    public function setUploadFiles(){
        $upload_files = TRUE;
        return $upload_files;
    }
    public function setRenameFiles(){
        $rename_files = TRUE;
        return $rename_files;
    }
    public function setRenameFolders(){
        $rename_folders = TRUE;
        return $rename_folders;
    }
    public function setDuplicateFiles(){
        $duplicate_files = TRUE;
        return $duplicate_files;
    }
    public function setCopyCutFiles(){
        $copy_cut_files = TRUE;
        return $copy_cut_files;
    }
    public function setCopyCutDirs(){
        $copy_cut_dirs = TRUE;
        return $copy_cut_dirs;
    }
    public function setChmodFiles(){
        $chmod_files = TRUE;
        return $chmod_files;
    }
    public function setChmodDirs(){
        $chmod_dirs = TRUE;
        return $chmod_dirs;
    }
    public function setPreviewTextFiles(){
        $preview_text_files = TRUE;
        return $preview_text_files;
    }
    public function setEditTextFiles(){
        $edit_text_files = TRUE;
        return $edit_text_files;
    }
    public function setCreateTextFiles(){
        $create_text_files = FALSE;
        return $create_text_files;
    }
    public function setPreviewableTextFileExts(){
        $previewable_text_file_exts = array('txt', 'log', 'xml','html','css','htm','js');
        return $previewable_text_file_exts;
    }
    public function setPreviewableTextFileExtsNoPrettify(){
        $previewable_text_file_exts_no_prettify = array('txt', 'log');
        return $previewable_text_file_exts_no_prettify;
    }
    public function setEditableTextFileExts(){
        $editable_text_file_exts = array('txt', 'log', 'xml','html','css','htm','js');
        return $editable_text_file_exts;
    }
    public function setGoogledocEnabled(){
        $googledoc_enabled = TRUE;
        return $googledoc_enabled;
    }
    public function setGoogledocFileExts(){
        $googledoc_file_exts = array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx');
        return $googledoc_file_exts;
    }
    // Preview with Viewer.js
    public function setViewerjsEnabled(){
        $viewerjs_enabled = TRUE;
        return $viewerjs_enabled;
    }
    public function setViewerjsFileExts(){
        $viewerjs_file_exts = array('pdf', 'odt', 'odp', 'ods');
        return $viewerjs_file_exts;
    }

    public function setCopyCutMaxSize(){
        $copy_cut_max_size	 = 100;
        return $copy_cut_max_size;
    }
    public function setCopyCutMaxCount(){
        $copy_cut_max_count	 = 200;
        return $copy_cut_max_count;
    }
    public function setExtImg(){
        $ext_img = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'); //Images
        return $ext_img;
    }
    public function setExtFile(){
        $ext_file = array('doc', 'docx','rtf', 'pdf', 'xls', 'xlsx', 'txt', 'csv','html','xhtml','psd','sql','log','fla','xml','ade','adp','mdb','accdb','ppt','pptx','odt','ots','ott','odb','odg','otp','otg','odf','ods','odp','css','ai'); //Files
        return $ext_file;
    }
    public function setExtVideo(){
        $ext_video = array('mov', 'mpeg', 'm4v', 'mp4', 'avi', 'mpg','wma',"flv","webm"); //Video
        return $ext_video;
    }
    public function setExtMusic(){
        $ext_music = array('mp3', 'm4a', 'ac3', 'aiff', 'mid','ogg','wav'); //Audio
        return $ext_music;
    }
    public function setExtMisc(){
        $ext_misc = array('zip', 'rar','gz','tar','iso','dmg'); //Archives
        return $ext_misc;
    }

    public function setExt(){
        $ext = array_merge($this->setExtImg(), $this->setExtFile(), $this->setExtMisc(), $this->setExtVideo(),$this->setExtMusic()); //allowed extensions
        return $ext;
    }

    /******************
     * AVIARY config
     *******************/
    public function setAviaryActive(){
        $aviary_active 	= FALSE;
        return $aviary_active;
    }
    public function setAviarySecret(){
        $aviary_secret	= "m6xaym5q42rpw433";
        return $aviary_secret;
    }
    public function setAviaryOptions(){
        $aviary_options = array(
            'apiKey' => 'dvh8qudbp6yx2bnp',
            'apiVersion' => 3,
            'language' => 'en'
        );
        return $aviary_options;
    }

    public function setFileNumberLimitJs(){
        $file_number_limit_js = 500;
        return $file_number_limit_js;
    }
    public function setHiddenFolders(){
        $hidden_folders = array();
        return $hidden_folders;
    }
    public function setHiddenFiles(){
        $hidden_files = array('config.php');
        return $hidden_files;
    }
    public function setJavaUpload(){
        $java_upload = TRUE;
        return $java_upload;
    }
    public function setJAVAMaxSizeUpload(){
        $JAVAMaxSizeUpload = 200; //Gb
        return $JAVAMaxSizeUpload;
    }
    public function setFixedImageCreation(){
        $fixed_image_creation = FALSE; //activate or not the creation of one or more image resized with fixed path from filemanager folder
        return $fixed_image_creation;
    }
    public function setFixedPathFromFilemanager(){
        $fixed_path_from_filemanager = array('../test/','../test1/'); //fixed path of the image folder from the current position on upload folder
        return $fixed_path_from_filemanager;
    }
    public function setFixedImageCreationNameToPrepend(){
        $fixed_image_creation_name_to_prepend = array('','test_'); //name to prepend on filename
        return $fixed_image_creation_name_to_prepend;
    }
    public function setFixedImageCreationToAppend(){
        $fixed_image_creation_to_append = array('_test',''); //name to appendon filename
        return $fixed_image_creation_to_append;
    }
    public function setFixedImageCreationWidth(){
        $fixed_image_creation_width = array(300,400); //width of image (you can leave empty if you set height)
        return $fixed_image_creation_width;
    }
    public function setFixedImageCreationHeight(){
        $fixed_image_creation_height = array(200,''); //height of image (you can leave empty if you set width)
        return $fixed_image_creation_height;
    }
    public function setFixedImageCreationOption(){
        $fixed_image_creation_option = array('crop','auto'); //set the type of the crop
        return $fixed_image_creation_option;
    }
    public function setRelativeImageCreation(){
        $relative_image_creation = FALSE; //activate or not the creation of one or more image resized with relative path from upload folder
        return $relative_image_creation;
    }
    public function setRelativePathFromCurrentPos(){
        $relative_path_from_current_pos = array('thumb/','thumb/'); //relative path of the image folder from the current position on upload folder
        return $relative_path_from_current_pos;
    }
    public function setRelativeImageCreationNameToPrepend(){
        $relative_image_creation_name_to_prepend = array('','test_'); //name to prepend on filename
        return $relative_image_creation_name_to_prepend;
    }
    public function setRelativeImageCreationNameToAppend(){
        $relative_image_creation_name_to_append = array('_test',''); //name to append on filename
        return $relative_image_creation_name_to_append;
    }
    public function setRelativeImageCreationWidth(){
        $relative_image_creation_width = array(300,400); //width of image (you can leave empty if you set height)
        return $relative_image_creation_width;
    }
    public function setRelativeImageCreationHeight(){
        $relative_image_creation_height = array(200,''); //height of image (you can leave empty if you set width)
        return $relative_image_creation_height;
    }
    public function setRelativeImageCreationOption(){
        $relative_image_creation_option = array('crop','crop'); //set the type of the crop
        return $relative_image_creation_option;
    }
    public function setRememberTextFilter(){
        $remember_text_filter = FALSE;
        return $remember_text_filter;
    }

    /**
     * Apply cms template filter
     * @param $content
     * @return mixed
     */
    public function applyTemplateFilter($content){
        $model = (string)Mage::getConfig()->getNode(Mage_Cms_Helper_Data::XML_NODE_BLOCK_TEMPLATE_FILTER);
        $templateFilter = Mage::getModel($model);
        return $templateFilter->filter($content);
    }

    public function cleanDirectives($contentValue){
        preg_match_all('/({{media(.*?)}})/', $contentValue, $matches);

        foreach($matches[0] as $match){
            $change = str_replace('"', '\'', $match);
            $contentValue = str_replace($match, $change, $contentValue);
        }

        return $contentValue;
    }
}