<?php
require_once Mage::getModuleDir('controllers', 'LitExtension_Wysiwyg') . '/Adminhtml/UtilsController.php';

class LitExtension_Wysiwyg_Adminhtml_AjaxcallsController extends LitExtension_Wysiwyg_Adminhtml_UtilsController
{
    public function indexAction()
    {
        $helper = Mage::helper('lewysiwyg');

        if (isset($_SESSION['RF']['language_file']) && file_exists($_SESSION['RF']['language_file'])) {
            include($_SESSION['RF']['language_file']);
        } else {
            die('Language file is missing!');
        }

        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'view':
                    if (isset($_GET['type'])) {
                        $_SESSION['RF']["view_type"] = $_GET['type'];
                    } else {
                        die('view type number missing');
                    }
                    break;
                case 'filter':
                    if (isset($_GET['type'])) {
                        if (isset($remember_text_filter) && $remember_text_filter)
                            $_SESSION['RF']["filter"] = $_GET['type'];
                    } else {
                        die('view type number missing');
                    }
                    break;
                case 'sort':
                    if (isset($_GET['sort_by'])) {
                        $_SESSION['RF']["sort_by"] = $_GET['sort_by'];
                    }

                    if (isset($_GET['descending'])) {
                        $_SESSION['RF']["descending"] = $_GET['descending'] === "TRUE";
                    }
                    break;
                case 'image_size': // not used
                    $pos = strpos($_POST['path'], $helper->setUploadDir());
                    if ($pos !== FALSE) {
                        $info = getimagesize(substr_replace($_POST['path'], $helper->setCurrentPath(), $pos, strlen($helper->setUploadDir())));
                        echo json_encode($info);
                    }
                    break;
                case 'save_img':
                    $info = pathinfo($_POST['name']);

                    if (strpos($_POST['path'], '/') === 0
                        || strpos($_POST['path'], '../') !== FALSE
                        || strpos($_POST['path'], './') === 0
                        || strpos($_POST['url'], 'http://featherfiles.aviary.com/') !== 0
                        || $_POST['name'] != fix_filename($_POST['name'], $helper->setTransliteration(), $helper->setConvertSpaces(), $helper->setReplaceWith())
                        || !in_array(strtolower($info['extension']), array('jpg', 'jpeg', 'png'))
                    ) {
                        die('wrong data');
                    }

                    $image_data = $this->get_file_by_url($_POST['url']);
                    if ($image_data === FALSE) {
                        die(lang_Aviary_No_Save);
                    }

                    file_put_contents($helper->setCurrentPath() . $_POST['path'] . $_POST['name'], $image_data);

                    $this->create_img($helper->setCurrentPath() . $_POST['path'] . $_POST['name'], $helper->setThumbsBasePath() . $_POST['path'] . $_POST['name'], 122, 91);
                    // TODO something with this function cause its blowing my mind
                    $this->new_thumbnails_creation($helper->setCurrentPath() . $_POST['path'], $helper->setCurrentPath() . $_POST['path'] . $_POST['name'], $_POST['name'], $helper->setCurrentPath(), $helper->setRelativeImageCreation(), $helper->setRelativePathFromCurrentPos(), $helper->setRelativeImageCreationNameToPrepend(), $helper->setRelativeImageCreationNameToAppend(), $helper->setRelativeImageCreationWidth(), $helper->setRelativeImageCreationHeight(), $helper->setRelativeImageCreationOption(), $helper->setFixedImageCreation(), $helper->setFixedPathFromFilemanager(), $helper->setFixedImageCreationNameToPrepend(), $helper->setFixedImageCreationToAppend(), $helper->setFixedImageCreationWidth(), $helper->setFixedImageCreationHeight(), $helper->setFixedImageCreationOption());
                    break;
                case 'extract':
                    if (strpos($_POST['path'], '/') === 0 || strpos($_POST['path'], '../') !== FALSE || strpos($_POST['path'], './') === 0) {
                        die('wrong path');
                    }

                    $path = $helper->setCurrentPath() . $_POST['path'];
                    $info = pathinfo($path);
                    $base_folder = $helper->setCurrentPath() . fix_dirname($_POST['path']) . "/";

                    switch ($info['extension']) {
                        case "zip":
                            $zip = new ZipArchive;
                            if ($zip->open($path) === TRUE) {
                                //make all the folders
                                for ($i = 0; $i < $zip->numFiles; $i++) {
                                    $OnlyFileName = $zip->getNameIndex($i);
                                    $FullFileName = $zip->statIndex($i);
                                    if (substr($FullFileName['name'], -1, 1) == "/") {
                                        $this->create_folder($base_folder . $FullFileName['name']);
                                    }
                                }
                                //unzip into the folders
                                for ($i = 0; $i < $zip->numFiles; $i++) {
                                    $OnlyFileName = $zip->getNameIndex($i);
                                    $FullFileName = $zip->statIndex($i);

                                    if (!(substr($FullFileName['name'], -1, 1) == "/")) {
                                        $fileinfo = pathinfo($OnlyFileName);
                                        if (in_array(strtolower($fileinfo['extension']), $helper->setExt())) {
                                            copy('zip://' . $path . '#' . $OnlyFileName, $base_folder . $FullFileName['name']);
                                        }
                                    }
                                }
                                $zip->close();
                            } else {
                                die(lang_Zip_No_Extract);
                            }

                            break;

                        case "gz":
                            $p = new PharData($path);
                            $p->decompress(); // creates files.tar

                            break;

                        case "tar":
                            // unarchive from the tar
                            $phar = new PharData($path);
                            $phar->decompressFiles();
                            $files = array();
                            $this->check_files_extensions_on_phar($phar, $files, '', $helper->setExt());
                            $phar->extractTo($helper->setCurrentPath() . fix_dirname($_POST['path']) . "/", $files, TRUE);

                            break;

                        default:
                            die(lang_Zip_Invalid);
                    }
                    break;
                case 'media_preview':
                    $preview_file = $_GET["file"];
                    $info = pathinfo($preview_file);
                    echo $html = '<div id="jp_container_1" class="jp-video " style="margin:0 auto;">
                        <div class="jp-type-single">
                            <div id="jquery_jplayer_1" class="jp-jplayer"></div>
                            <div class="jp-gui">
                                <div class="jp-video-play">
                                    <a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
                                </div>
                                <div class="jp-interface">
                                    <div class="jp-progress">
                                        <div class="jp-seek-bar">
                                            <div class="jp-play-bar"></div>
                                        </div>
                                    </div>
                                    <div class="jp-current-time"></div>
                                    <div class="jp-duration"></div>
                                    <div class="jp-controls-holder">
                                        <ul class="jp-controls">
                                            <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                                            <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                                            <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                                            <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                                            <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
                                            <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                                        </ul>
                                        <div class="jp-volume-bar">
                                            <div class="jp-volume-bar-value"></div>
                                        </div>
                                        <ul class="jp-toggles">
                                            <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
                                            <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
                                            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
                                            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
                                        </ul>
                                    </div>
                                    <div class="jp-title" style="display:none;">
                                        <ul>
                                            <li></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="jp-no-solution">
                                <span>Update Required</span>
                                To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                            </div>
                        </div>
                    </div>';

                    if (in_array(strtolower($info['extension']), $helper->setExtMusic())) {
                        echo $js = '<script type="text/javascript">
                            $(document).ready(function(){

                                $("#jquery_jplayer_1").jPlayer({
                                    ready: function () {
                                        $(this).jPlayer("setMedia", {
                                            title:"' . $_GET['title'] . '",
                                            mp3: "' . $preview_file . '",
                                            m4a: "' . $preview_file . '",
                                            oga: "' . $preview_file . '",
                                            wav: "' . $preview_file . '"
                                        });
                                    },
                                    swfPath: "js",
                                    solution:"html,flash",
                                    supplied: "mp3, m4a, midi, mid, oga,webma, ogg, wav",
                                    smoothPlayBar: true,
                                    keyEnabled: false
                                });
                            });
                        </script>';

                    } elseif (in_array(strtolower($info['extension']), $helper->setExtVideo())) {
                        echo $js2 = '<script type="text/javascript">
                            $(document).ready(function(){

                                $("#jquery_jplayer_1").jPlayer({
                                    ready: function () {
                                        $(this).jPlayer("setMedia", {
                                            title:"' . $_GET['title'] . '",
                                            m4v: "' . $preview_file . '",
                                            ogv: "' . $preview_file . '"
                                        });
                                    },
                                    swfPath: "js",
                                    solution:"html,flash",
                                    supplied: "mp4, m4v, ogv, flv, webmv, webm",
                                    smoothPlayBar: true,
                                    keyEnabled: false
                                });

                            });
                        </script>';
                    }
                    break;
                case 'copy_cut':
                    if ($_POST['sub_action'] != 'copy' && $_POST['sub_action'] != 'cut') {
                        die('wrong sub-action');
                    }

                    if (trim($_POST['path']) == '' || trim($_POST['path_thumb']) == '') {
                        die('no path');
                    }

                    $path = $helper->setCurrentPath() . $_POST['path'];

                    if (is_dir($path)) {
                        // can't copy/cut dirs
                        if ($helper->setCopyCutDirs() === FALSE) {
                            die(sprintf(lang_Copy_Cut_Not_Allowed, ($_POST['sub_action'] == 'copy' ? lcfirst(lang_Copy) : lcfirst(lang_Cut)), lang_Folders));
                        }

                        // size over limit
                        if ($helper->setCopyCutMaxSize() !== FALSE && is_int($helper->setCopyCutMaxSize())) {
                            if (($helper->setCopyCutMaxSize() * 1024 * 1024) < foldersize($path)) {
                                die(sprintf(lang_Copy_Cut_Size_Limit, ($_POST['sub_action'] == 'copy' ? lcfirst(lang_Copy) : lcfirst(lang_Cut)), $helper->setCopyCutMaxSize()));
                            }
                        }

                        // file count over limit
                        if ($helper->setCopyCutMaxCount() !== FALSE && is_int($helper->setCopyCutMaxCount())) {
                            if ($helper->setCopyCutMaxCount() < filescount($path)) {
                                die(sprintf(lang_Copy_Cut_Count_Limit, ($_POST['sub_action'] == 'copy' ? lcfirst(lang_Copy) : lcfirst(lang_Cut)), $helper->setCopyCutMaxCount()));
                            }
                        }
                    } else {
                        // can't copy/cut files
                        if ($helper->setCopyCutFiles() === FALSE) {
                            die(sprintf(lang_Copy_Cut_Not_Allowed, ($_POST['sub_action'] == 'copy' ? lcfirst(lang_Copy) : lcfirst(lang_Cut)), lang_Files));
                        }
                    }

                    $_SESSION['RF']['clipboard']['path'] = $_POST['path'];
                    $_SESSION['RF']['clipboard']['path_thumb'] = $_POST['path_thumb'];
                    $_SESSION['RF']['clipboard_action'] = $_POST['sub_action'];
                    break;
                case 'clear_clipboard':
                    $_SESSION['RF']['clipboard'] = NULL;
                    $_SESSION['RF']['clipboard_action'] = NULL;
                    break;
                case 'chmod':
                    $path = $helper->setCurrentPath() . $_POST['path'];
                    if ((is_dir($path) && $helper->setChmodDirs() === FALSE) ||
                        (is_file($path) && $helper->setChmodFiles() === FALSE) ||
                        ($this->is_function_callable("chmod") === FALSE)
                    ) {
                        die(sprintf(lang_File_Permission_Not_Allowed, (is_dir($path) ? lcfirst(lang_Folders) : lcfirst(lang_Files))));
                    } else {
                        $perm = decoct(fileperms($path) & 0777);
                        $perm_user = substr($perm, 0, 1);
                        $perm_group = substr($perm, 1, 1);
                        $perm_all = substr($perm, 2, 1);

                        $ret = '<div id="files_permission_start">
				<form id="chmod_form">
					<table class="file-perms-table">
						<thead>
							<tr>
								<td></td>
								<td>r&nbsp;&nbsp;</td>
								<td>w&nbsp;&nbsp;</td>
								<td>x&nbsp;&nbsp;</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>' . lang_User . '</td>
								<td><input id="u_4" type="checkbox" data-value="4" data-group="user" onChange="chmod_logic();"' . (chmod_logic_helper($perm_user, 4) ? " checked" : "") . '></td>
								<td><input id="u_2" type="checkbox" data-value="2" data-group="user" onChange="chmod_logic();"' . (chmod_logic_helper($perm_user, 2) ? " checked" : "") . '></td>
								<td><input id="u_1" type="checkbox" data-value="1" data-group="user" onChange="chmod_logic();"' . (chmod_logic_helper($perm_user, 1) ? " checked" : "") . '></td>
							</tr>
							<tr>
								<td>' . lang_Group . '</td>
								<td><input id="g_4" type="checkbox" data-value="4" data-group="group" onChange="chmod_logic();"' . (chmod_logic_helper($perm_group, 4) ? " checked" : "") . '></td>
								<td><input id="g_2" type="checkbox" data-value="2" data-group="group" onChange="chmod_logic();"' . (chmod_logic_helper($perm_group, 2) ? " checked" : "") . '></td>
								<td><input id="g_1" type="checkbox" data-value="1" data-group="group" onChange="chmod_logic();"' . (chmod_logic_helper($perm_group, 1) ? " checked" : "") . '></td>
							</tr>
							<tr>
								<td>' . lang_All . '</td>
								<td><input id="a_4" type="checkbox" data-value="4" data-group="all" onChange="chmod_logic();"' . (chmod_logic_helper($perm_all, 4) ? " checked" : "") . '></td>
								<td><input id="a_2" type="checkbox" data-value="2" data-group="all" onChange="chmod_logic();"' . (chmod_logic_helper($perm_all, 2) ? " checked" : "") . '></td>
								<td><input id="a_1" type="checkbox" data-value="1" data-group="all" onChange="chmod_logic();"' . (chmod_logic_helper($perm_all, 1) ? " checked" : "") . '></td>
							</tr>
							<tr>
								<td></td>
								<td colspan="3"><input type="text" name="chmod_value" id="chmod_value" value="' . $perm . '" data-def-value="' . $perm . '"></td>
							</tr>
						</tbody>
					</table>';

                        if (is_dir($path)) {
                            $ret .= '<div>' . lang_File_Permission_Recursive . '
							<ul>
								<li><input value="none" name="apply_recursive" type="radio" checked> ' . lang_No . '</li>
								<li><input value="files" name="apply_recursive" type="radio"> ' . lang_Files . '</li>
								<li><input value="folders" name="apply_recursive" type="radio"> ' . lang_Folders . '</li>
								<li><input value="both" name="apply_recursive" type="radio"> ' . lang_Files . ' & ' . lang_Folders . '</li>
							</ul>
							</div>';
                        }

                        $ret .= '</form></div>';

                        echo $ret;
                    }
                    break;
                case 'get_lang':
                    if (!file_exists('lang/languages.php')) {
                        die(lang_Lang_Not_Found);
                    }

                    require_once 'lang/languages.php';
                    if (!isset($languages) || !is_array($languages)) {
                        die(lang_Lang_Not_Found);
                    }

                    $curr = $_SESSION['RF']['language'];

                    $ret = '<select id="new_lang_select">';
                    foreach ($languages as $code => $name) {
                        $ret .= '<option value="' . $code . '"' . ($code == $curr ? ' selected' : '') . '>' . $name . '</option>';
                    }
                    $ret .= '</select>';

                    echo $ret;

                    break;
                case 'change_lang':
                    $choosen_lang = $_POST['choosen_lang'];

                    if (!file_exists('lang/' . $choosen_lang . '.php')) {
                        die(lang_Lang_Not_Found);
                    }

                    $_SESSION['RF']['language'] = $choosen_lang;
                    $_SESSION['RF']['language_file'] = 'lang/' . $choosen_lang . '.php';

                    break;
                case 'get_file': // preview or edit
                    $sub_action = $_GET['sub_action'];
                    $preview_mode = $_GET["preview_mode"];

                    if ($sub_action != 'preview' && $sub_action != 'edit') {
                        die("wrong action");
                    }

                    $selected_file = ($sub_action == 'preview' ? $_GET['file'] : $helper->setCurrentPath() . $_POST['path']);
                    $info = pathinfo($selected_file);

                    if (!file_exists($selected_file)) {
                        die(lang_File_Not_Found);
                    }

                    if ($preview_mode == 'text') {
                        $is_allowed = ($sub_action == 'preview' ? $helper->setPreviewTextFiles() : $helper->setEditTextFiles());
                        $allowed_file_exts = ($sub_action == 'preview' ? $helper->setPreviewableTextFileExts() : $helper->setEditableTextFileExts());
                    } elseif ($preview_mode == 'viewerjs') {
                        $is_allowed = $helper->setViewerjsEnabled();
                        $allowed_file_exts = $helper->setViewerjsFileExts();
                    } elseif ($preview_mode == 'google') {
                        $is_allowed = $helper->setGoogledocEnabled();
                        $allowed_file_exts = $helper->setGoogledocFileExts();
                    }

                    if (!isset($allowed_file_exts) || !is_array($allowed_file_exts)) {
                        $allowed_file_exts = array();
                    }

                    if (!in_array($info['extension'], $allowed_file_exts)
                        || !isset($is_allowed)
                        || $is_allowed === FALSE
                        || !is_readable($selected_file)
                    ) {
                        die(sprintf(lang_File_Open_Edit_Not_Allowed, ($sub_action == 'preview' ? strtolower(lang_Open) : strtolower(lang_Edit))));
                    }

                    if ($sub_action == 'preview') {
                        if ($preview_mode == 'text') {
                            // get and sanities
                            $data = stripslashes(htmlspecialchars(file_get_contents($selected_file)));
                            if (!in_array($info['extension'], $helper->setPreviewableTextFileExtsNoPrettify())) {
                                echo '<script src="https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js?lang=' . $info['extension'] . '&skin=sunburst"></script>';
                                echo '<div class="text-center"><strong>' . $info['basename'] . '</strong></div><pre class="prettyprint">' . $data . '</pre>';
                            } else {
                                echo '<div class="text-center"><strong>' . $info['basename'] . '</strong></div><pre class="no-prettify">' . $data . '</pre>';
                            }
                        } elseif ($preview_mode == 'viewerjs') {
                            echo '<iframe id="viewer" src="ViewerJS/#../' . $_GET["file"] . '" allowfullscreen="" webkitallowfullscreen="" class="viewer-iframe"></iframe>';

                        } elseif ($preview_mode == 'google') {
                            $url_file = $helper->setBaseUrl() . $helper->setUploadDir() . str_replace($helper->setCurrentPath(), '', $_GET["file"]);
                            $googledoc_url = urlencode($url_file);
                            $googledoc_html = "<iframe src=\"http://docs.google.com/viewer?url=" . $googledoc_url . "&embedded=true\" class=\"google-iframe\"></iframe>";
                            echo '<div class="text-center"><strong>' . $info['basename'] . '</strong></div>' . $googledoc_html . '';
                        }
                    } else {
                        $data = stripslashes(htmlspecialchars(file_get_contents($selected_file)));
                        echo '<textarea id="textfile_edit_area" style="width:100%;height:300px;">' . $data . '</textarea>';
                    }

                    break;
                default:
                    die('no action passed');
            }
        } else {
            die('no action passed');
        }
    }
}