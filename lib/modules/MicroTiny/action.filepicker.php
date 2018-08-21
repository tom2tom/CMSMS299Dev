<?php
#MicroTiny module action: filepicker
#Copyright (C) 2009-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOpUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use FileManager\Utils;
use FilePicker\Utils as Utils2;
use MicroTiny\Utils as Utils3;

if( !isset($gCms) ) exit;
if( !check_login() ) exit; // admin only.... but any admin

//$handlers = ob_list_handlers();
//for ($cnt = 0; $cnt < sizeof($handlers); $cnt++) { ob_end_clean(); }

debug_to_log(__FILE__);
//
// initialization
//
$field = trim(get_parameter_value($_GET,'field'));
$type = 'any';
$filemanager = cms_utils::get_module('FileManager'); //TODO distentangle, use FilePickerProfile
if( isset($_GET['type']) ) {
	$tmp = strtolower(cleanValue(trim($_GET['type'])));
	if( $tmp == 'image' ) $type = 'image';
	elseif( $tmp == 'media' ) $type = 'media';
}

$cwd = Utils::get_cwd();
if( isset($_GET['subdir']) ) {
	// todo, make sure this can't go above /uploads
	$cwd .= DIRECTORY_SEPARATOR . cleanValue(trim($_GET['subdir']));
	Utils::set_cwd($cwd);
}

$startdir = Utils::join_path(CMS_ROOT_PATH,$cwd);
$starturl = CMS_ROOT_URL.'/'.$cwd; //TODO real url

function is_image(string $filename) : bool
{
	$ext = strtolower(substr($filename,strrpos($filename,'.')+1));
	return in_array($ext,
		['jpg','jpeg','gif','png','svg','bmp','wbmp','webp','ico']);
}

function is_media(string $filename) : bool
{
	$ext = strtolower(substr($filename,strrpos($filename,'.')+1));
	return in_array($ext,
		['swf','dcr','mov','qt','mpg','mp3','mp4','ogg','mpeg','wmp','avi','wmv','wm','asf','asx','wmx','rm','ra','ram']);
}

function accept_file(string $type, string $cwd, string $path, string $filename) : bool
{
// TODO replace filemanager, at least
	global $startdir, $filemanager;

	if( $filename == '.' ) return false;
	if( $filename == '..' ) return ( $cwd != $startdir );
	if( ($filename[0] == '.' || $filename[0] == '_') /*&& !$filemanager->GetPreference('showhiddenfiles') */ ) return false;
	if( is_dir(cms_join_path($path,$filename)) ) return true;

	switch( $type ) {
		case 'image':
			return is_image($filename);

		case 'media':
			return is_media($filename);

//		case 'file':
//		case 'any':
		default:
			return true;
	}
}

/*
 * A quick check for a file type based on extension
 * @param string $ext
 * @return string
 */
function get_filetype(string $ext) : string
{
//	TODO use CMSMS\FileTypeHelper for this
	$imgext = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', 'wbmp', 'webp']; // images
	$videoext = ['mov', 'mpeg', 'mp4', 'avi', 'mpg','wma', 'flv', 'webm', 'wmv', 'qt', 'ogg']; // videos
	$audioext = ['mp3', 'm4a', 'ac3', 'aiff', 'mid', 'wav']; // audio
	$archiveext = ['zip', 'rar', 'gz', 'xz', 'bz2', 'tar', 'iso', 'dmg']; // archives

	$ext = strtolower($ext);
	if(in_array($ext, $imgext)) {
		return 'image';
	} elseif(in_array($ext, $videoext)) {
		return 'video';
	} elseif(in_array($ext, $audioext)) {
		return 'audio';
	} elseif(in_array($ext, $archiveext)) {
		return 'archive';
	}
	return 'file';
}

//
// get our file list
//
$files = [];
$filesizename = [' Bytes', ' KB', ' MB'];
$dh = dir($startdir);
while( false !== ($filename = $dh->read()) ) {
	if( !$accept_file( $type, $cwd, $startdir, $filename ) ) continue;
	$fullname = cms_join_path($startdir,$filename);

	$file = [];
	$file['name'] = $filename;
	$file['fullpath'] = $fullname;
	$file['fullurl'] = $starturl.'/'.$filename;
	$file['isdir'] = is_dir($fullname);
	$file['ext'] = strtolower(substr($filename,strrpos($filename,".")+1));
	$file['is_image'] = is_image($filename);
	$file['icon'] = Utils2::get_file_icon($file['ext'],$file['isdir']);
	$file['filetype'] = get_filetype($file['ext']);
	$file['dimensions'] = '';
	if( $file['is_image'] ) {
		$file['thumbnail'] = Utils3::GetThumbnailFile($filename,$startdir,$starturl);
		$imgsize = @getimagesize($fullname);
		if( $imgsize ) $file['dimensions'] = $imgsize[0].' x '.$imgsize[1];
	}
	$info = @stat($fullname);
	if( $info && $info['size'] > 0) {
		$file['size'] = round($info['size']/pow(1024, ($i = floor(log($info['size'], 1024)))), 2) . $filesizename[$i];
	} else {
		$file['size'] = null;
	}
	if( $file['isdir'] ) {
		$url = $this->create_url($id,'filepicker',$returnid)."&cmsjobtype=1&subdir=$filename&type=$type&field=$field";
		$file['chdir_url'] = str_replace('&amp;','&',$url);
	}
	$files[] = $file;
}
// done the loop, now sort
usort($files, function (array $file1, array $file2)
{
	if ($file1["isdir"] && !$file2["isdir"]) return -1;
	if (!$file1["isdir"] && $file2["isdir"]) return 1;
	return strnatcasecmp($file1["name"],$file2["name"]);
});

/*
        $script_url = CMS_SCRIPTS_URL;
		$out = <<<EOS
<meta name="msapplication-TileColor" content="#f79838" />
<meta name="msapplication-TileImage" content="{$assets_url}images/ms-application-icon.png" />
<link rel="shortcut icon" href="{$assets_url}images/cmsms-favicon.ico" />
<link rel="apple-touch-icon" href="{$assets_url}images/apple-touch-icon-iphone.png" />
<link rel="apple-touch-icon" sizes="72x72" href="{$assets_url}images/apple-touch-icon-ipad.png" />
<link rel="apple-touch-icon" sizes="114x114" href="{$assets_url}images/apple-touch-icon-iphone4.png" />
<link rel="apple-touch-icon" sizes="144x144" href="{$assets_url}images/apple-touch-icon-ipad3.png" />

EOS;
		$scripts - cms_installed_jquery(bool $core = true, bool $migrate = false, bool $ui = true, bool $uicss = true);
		$url = AdminUtils::path_to_url($jqcss);
		$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />
<link rel="stylesheet" type="text/css" href="{$base_url}/css/{$fn}.css" />

EOS;
		if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'extcss'.DIRECTORY_SEPARATOR.$fn.'.css')) {
			$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$base_url}/extcss/{$fn}.css" />

EOS;
		}
		$tpl = '<script type="text/javascript" src="%s"></script>'."\n";
		$url = AdminUtils::path_to_url($jqcore);
		$out .= sprintf($tpl,$url);
		$url = AdminUtils::path_to_url($jqmigrate);
		$out .= sprintf($tpl,$url);
		$url = AdminUtils::path_to_url($jqui);
		$out .= sprintf($tpl,$url);
		$out .= <<<EOS
<script type="text/javascript" src="{$script_url}/jquery.cms_admin.js"></script>

EOS;
        global $CMS_LOGIN_PAGE;
        if ( isset($_SESSION[CMS_USER_KEY]) && !isset($CMS_LOGIN_PAGE) ) {
            //populate runtime data (i.e. cms_data{}) via ajax
            $url = cms_config::get_instance()['admin_url'];
            $url .= '/cms_js_setup.php?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		    $out .= sprintf($tpl,$url);
        }
//<script type="text/javascript" src="{$assets_url}js/jquery.responsivetable.js"></script> TESTER
/* action-specific inclusions elsewhere:
jquery.cmsms_autorefresh.js DONE
jquery.cmsms_dirtyform.js DONE
jquery.cmsms_hierselector.js DONE
jquery.cmsms_lock.js DONE
 jquery.mjs.nestedSortable.min.js DONE
* /
		$out .= <<<EOS
<script type="text/javascript" src="{$script_url}/jquery.cmsms_defer.js"></script>
<script type="text/javascript" src="{$script_url}/jquery.ui.touch-punch.min.js"></script>
<script type="text/javascript" src="{$script_url}/jquery.toast.js"></script>
<script type="text/javascript" src="{$base_url}/js/jquery.alertable.js"></script>
<script type="text/javascript" src="{$base_url}/js/standard.js"></script>
<!--[if lt IE 9]>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
<script type="text/javascript" src="{$base_url}/js/libs/jquery-extra-selectors.js"></script>
<script type="text/javascript" src="{$base_url}/js/libs/selectivizr-min.js"></script>
<![endif]-->
EOS;
*/

//this template constructs an entire page, not just action-specific content (for an iframe?)
$tpl = $smarty->createTemplate($this->GetTemplateResource('filepicker.tpl'),null,null,$smarty);

$tpl->assign('showthumbnails',(int)$filemanager->GetPreference('showthumbnails'))
 ->assign('startpath',$cwd)
 ->assign('field',$field)
 ->assign('files',$files)
 ->assign('type', $type);

$js = <<<EOS
//TODO removed 2.3 cms_jquery exclude='ui,cms_js_setup,ui_touch_punch,nestedSortable,json,cms_filepicker,migrate,cms_admin,cms_autorefresh,cms_dirtyform,cms_hiersel,cms_lock' append="`$mod->GetModuleURLPath()`/lib/js/tinymce/plugins/cmsms_filebrowser/filebrowser.js"
EOS;
$tpl->assign('bottomcontent', $js);

$tpl->display();

