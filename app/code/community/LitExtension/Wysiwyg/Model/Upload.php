<?php

class LitExtension_Wysiwyg_Model_Upload extends LitExtension_Wysiwyg_Model_Utils
{
    public function Upload()
    {
        $helper = Mage::helper('lewysiwyg');

//        if($_SESSION['RF']["verify"] != "RESPONSIVEfilemanager") die('forbiden');

        $current_path = $helper->setCurrentPath();
        $thumbs_base_path = $helper->setThumbsBasePath();
        if (isset($_POST['path'])) {
            $storeFolder = $_POST['path'];
            $storeFolderThumb = $_POST['path_thumb'];
        } else {
            $storeFolder = $current_path . @$_POST["fldr"]; // correct for when IE is in Compatibility mode
            $storeFolderThumb = $thumbs_base_path . $_POST["fldr"];
        }


        $path_pos = strpos($storeFolder, $current_path);
        $thumb_pos = strpos($storeFolderThumb, $thumbs_base_path);

        if ($path_pos !== 0
            || $thumb_pos !== 0
            || strpos($storeFolderThumb, '../', strlen($thumbs_base_path)) !== FALSE
            || strpos($storeFolderThumb, './', strlen($thumbs_base_path)) !== FALSE
            || strpos($storeFolder, '../', strlen($current_path)) !== FALSE
            || strpos($storeFolder, './', strlen($current_path)) !== FALSE
        )
            die('wrong path');


        $path = $storeFolder;
        $cycle = true;
        $max_cycles = 50;
        $i = 0;
        while ($cycle && $i < $max_cycles) {
            $i++;
            if ($path == $current_path) $cycle = false;
            if (file_exists($path . "config.php")) {
                require_once $path . "config.php";
                $cycle = false;
            }
            $path = $this->fix_dirname($path) . '/';
        }


        if (!empty($_FILES)) {
            $info = pathinfo($_FILES['file']['name']);
            if (in_array($this->fix_strtolower($info['extension']), $helper->setExt())) {
                $tempFile = $_FILES['file']['tmp_name'];

                $targetPath = $storeFolder;
                $targetPathThumb = $storeFolderThumb;
                $_FILES['file']['name'] = $this->fix_filename($_FILES['file']['name'], $helper->setTransliteration(), $helper->setConvertSpaces(), $helper->setReplaceWith());

                // Gen. new file name if exists
                if (file_exists($targetPath . $_FILES['file']['name'])) {
                    $i = 1;
                    $info = pathinfo($_FILES['file']['name']);

                    // append number
                    while (file_exists($targetPath . $info['filename'] . "_" . $i . "." . $info['extension'])) {
                        $i++;
                    }
                    $_FILES['file']['name'] = $info['filename'] . "_" . $i . "." . $info['extension'];
                }
                $targetFile = $targetPath . $_FILES['file']['name'];
                $targetFileThumb = $targetPathThumb . $_FILES['file']['name'];

                // check if image (and supported)
                if (in_array($this->fix_strtolower($info['extension']), $helper->setExtImg())) $is_img = true;
                else $is_img = false;

                // upload
                move_uploaded_file($tempFile, $targetFile);
                chmod($targetFile, 0755);

                if ($is_img) {
                    $memory_error = false;
                    if (!$this->create_img($targetFile, $targetFileThumb, 122, 91)) {
                        $memory_error = false;
                    } else {
                        // TODO something with this long function baaaah...
                        if (!$this->new_thumbnails_creation($targetPath, $targetFile, $_FILES['file']['name'], $current_path,
                            $helper->setRelativeImageCreation(), $helper->setRelativePathFromCurrentPos(),
                            $helper->setRelativeImageCreationNameToPrepend(), $helper->setRelativeImageCreationNameToAppend(),
                            $helper->setRelativeImageCreationWidth(), $helper->setRelativeImageCreationHeight(),
                            $helper->setRelativeImageCreationOption(), $helper->setFixedImageCreation(),
                            $helper->setFixedPathFromFilemanager(), $helper->setFixedImageCreationNameToPrepend(),
                            $helper->setFixedImageCreationToAppend(), $helper->setFixedImageCreationWidth(),
                            $helper->setFixedImageCreationHeight(), $helper->setFixedImageCreationOption())
                        ) {
                            $memory_error = false;
                        } else {
                            $imginfo = getimagesize($targetFile);
                            $srcWidth = $imginfo[0];
                            $srcHeight = $imginfo[1];

                            // resize images if set
                            if ($helper->setImageResizing()) {
                                if ($helper->setImageResizingWidth() == 0) { // if width not set
                                    if ($helper->setImageResizingHeight() == 0) {
                                        $image_resizing_width = $srcWidth;
                                        $image_resizing_height = $srcHeight;
                                    } else {
                                        $image_resizing_width = $helper->setImageResizingHeight() * $srcWidth / $srcHeight;
                                    }
                                } elseif ($helper->setImageResizingHeight() == 0) { // if height not set
                                    $image_resizing_height = $helper->setImageResizingWidth() * $srcHeight / $srcWidth;
                                }

                                // new dims and create
                                $srcWidth = $image_resizing_width;
                                $srcHeight = $image_resizing_height;
                                $this->create_img($targetFile, $targetFile, $image_resizing_width, $image_resizing_height, $helper->setImageResizingMode());
                            }
                            //max resizing limit control
                            $resize = false;
                            if ($helper->setImageMmaxWidth() != 0 && $srcWidth > $helper->setImageMmaxWidth() && $helper->setImageResizingOverride === FALSE) {
                                $resize = true;
                                $srcWidth = $helper->setImageMmaxWidth();
                                if ($helper->setImageMaxHeight() == 0) $srcHeight = $helper->setImageMmaxWidth() * $srcHeight / $srcWidth;
                            }
                            if ($helper->setImageMaxHeight() != 0 && $srcHeight > $helper->setImageMaxHeight() && $helper->setImageResizingOverride === FALSE) {
                                $resize = true;
                                $srcHeight = $helper->setImageMaxHeight();
                                if ($helper->setImageMmaxWidth() == 0) $srcWidth = $helper->setImageMaxHeight() * $srcWidth / $srcHeight;
                            }
                            if ($resize)
                                $this->create_img($targetFile, $targetFile, $srcWidth, $srcHeight, $helper->setImageResizingMode());
                        }
                    }
                    if ($memory_error) {
                        //error
                        unlink($targetFile);
                        header('HTTP/1.1 406 Not enought Memory', true, 406);
                        exit();
                    }
                }
            } else {
                header('HTTP/1.1 406 file not permitted', true, 406);
                exit();
            }
        } else {
            header('HTTP/1.1 405 Bad Request', true, 405);
            exit();
        }
        if (isset($_POST['submit'])) {
            $query = http_build_query(array(
                'type' => $_POST['type'],
                'lang' => $_POST['lang'],
                'popup' => $_POST['popup'],
                'field_id' => $_POST['field_id'],
                'fldr' => $_POST['fldr'],
            ));
            header("location: '" . Mage::helper("adminhtml")->getUrl("lewysiwyg/adminhtml_dialog/dialog") . "'?" . $query);
        }
    }
}