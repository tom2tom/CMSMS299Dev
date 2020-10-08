<?php
# FilePicker module action: filepicker
# Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Fernando Morgado and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

/*
 * This generates content (html, js, css) for a whole page, intended for
 * an iframe which can support directory-change and -display.
 * That page uses a non-CMSMS file-upload js plugin,
 * (see https://github.com/LPology/Simple-Ajax-Uploader)
 * and a CMSFileBrowser js object with methods to interact with the
 * generated page and uploader
 */

use CMSMS\Crypto;
use CMSMS\FileType;
use CMSMS\FSControlValue;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use FilePicker\PathAssistant;
use FilePicker\TemporaryProfileStorage;
use FilePicker\Utils;

if (!function_exists('cmsms')) exit;
//BAD in iframe if( !check_login(true) ) exit; // admin only.... but any admin

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

//
// initialization
//
if( !empty($params['_enc']) ) {
    $eparms = json_decode(base64_decode($params['_enc']), true); //'seldir'|'subdir','inst'
    if( $eparms ) {
		cleanArray($eparms);
        $params = array_merge($params, $eparms);
    }
}

try {
/*
	//$mime & $extensions replicate profile properties, if a suitable profile exists now
    $mime = $params['mime'] ?? '';
    if ($mime) {
		// defines filetypes to be displayed for potential upload c.f. $profile->file_extensions
        $mime = rawurldecode(trim($mime));
    }
    $extensions = $params['extensions'] ?? '';
    if ($extensions) {
        // defines extensions of files' names to be displayed for potential upload c.f. $profile->file_mimes
        $extensions = rawurldecode(trim($extensions));
    }
*/
	$save = false;
    $inst = $params['inst'] ?? '';
	if( $inst ) {
        $profile = TemporaryProfileStorage::get($inst);
    }
    else {
        $profile = null;
    }
    if( !$profile ) {
        $profile = $this->get_default_profile();
		$save = true;
    }

    $stype = $params['type'] ?? ''; //TODO form needs to send this if no inst
    if( $profile && !$inst && $stype ) {
        $itype = FileType::getValue($stype);
        $profile = $profile->overrideWith([
			'type'=>$itype,
		]);
		$save = true;
    }
    if( !$this->CheckPermission('Modify Files') ) {
        $profile = $profile->overrideWith([
            'can_upload' => FSControlValue::NO,
            'can_delete' => FSControlValue::NO,
            'can_mkdir' => FSControlValue::NO
        ]);
		$save = true;
    }
//TODO MAYBE 'id'=>0, //? an identifier corresponding to db table key? $db->GenId() ?
//           'name'=>'', //something useful?

    // get our absolute top directory
    $topdir = $profile->top;
    if( !$topdir ) {
        $topdir = $config['uploads_path'];
        $profile = $profile->overrideWith([
            'top' => $topdir
        ]);
		$save = true;
    }

	if( $save ) {
        $inst = TemporaryProfileStorage::set($profile);
	}

    $assistant = new PathAssistant($config, $topdir);
    $sesskey = Crypto::hash_string(__FILE__);

	$cwd = $params['seldir'] ?? '';
    if( $cwd ) {
        $cwd = trim($cwd);
    }
    else {
        // get our current working directory relative to $topdir
        // prefer: the value stored in session, then the profile topdir, then the absolute topdir
        if( isset($_SESSION[$sesskey]) ) {
            $cwd = trim($_SESSION[$sesskey]);
        }
        else {
            $cwd = '';
        }
        if( !$cwd && $profile->top ) {
            $cwd = $assistant->to_relative($profile->top);
        }

        $nosub = $params['nosub'] ?? false;
        if( !($nosub || empty($params['subdir'])) ) {
            $cwd .= DIRECTORY_SEPARATOR . $params['subdir'];
            $cwd = $assistant->to_relative($assistant->to_absolute($cwd));
        }
    }
    // if we still don't have a valid working directory, set it to the $topdir
    if( $cwd && !$assistant->is_valid_relative_path( $cwd ) ) {
        $cwd = '';
    }
    //if( $cwd ) $_SESSION[$sesskey] = $cwd;
    $_SESSION[$sesskey] = $cwd;

    // now we're set to go.
    $topurl = $assistant->get_top_url();
    $starturl = $assistant->relative_path_to_url($cwd);
    $startdir = $assistant->to_absolute($cwd);
    //
    // get file list c.f. Utils::get_file_list($profile,$startdir)
    //
    $files = $thumbs = [];
    $dosize = function_exists('getimagesize'); // GD extension present
    $filesizename = [' Bytes', ' kB', ' MB'];
    $items = scandir($startdir, SCANDIR_SORT_NONE);
    for( $name = reset($items); $name !== false; $name = next($items) ) {
        if( $name == '.' || $name == '..' ) { // i.e. no .. (parent) item in list
            continue;
        }
        if( !$profile->show_hidden && ($name[0] == '.' || $name[0] == '_') ) {
            continue;
        }
        $fullname = cms_join_path($startdir,$name);
        if( is_dir($fullname) ) {
            // anything here?
        }
        elseif( !$this->is_acceptable_filename( $profile, $fullname ) ) {
            continue;
        }
        $data = ['name' => $name, 'fullpath' => $fullname];
        $data['fullurl'] = $starturl.'/'.$name;
        $info = @stat($fullname);
        if( $info && $info['size'] > 0 ) {
            $data['size'] = round($info['size']/pow(1024, ($i = floor(log($info['size'], 1024)))), 2) . $filesizename[$i];
        }
        else {
            $data['size'] = '';
        }
        $data['dimensions'] = '';
        $data['isdir'] = is_dir($fullname);
        if( $data['isdir'] ) {
//            $data['filetype'] = null;
            $data['isparent'] = false; //( $name == '..' );
            $data['relurl'] = $data['fullurl'];
            $data['ext'] = '';
            $data['is_image'] = false;
            $data['is_thumb'] = false;
/*
            if( $name == '..' ) {
                $t = 'up'; //TODO or 'home'
            }
            else {
                $t = '';
            }
            $data['icon'] = Utils::get_file_icon($t,TRUE);
*/
            $data['icon'] = Utils::get_file_icon('',TRUE);

            $parms = [ 'subdir'=>$name, 'inst'=>$inst ];
            $up = base64_encode(json_encode($parms,
                JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE));
            $url = $this->create_url($id, 'filepicker', $returnid); // come back here
            $url = str_replace('&amp;', '&', $url).'&'.CMS_JOB_KEY.'=1&_enc='.$up;
            $data['chdir_url'] = $url;
        }
        else {
            $data['isparent'] = false;
            $data['relurl'] = $assistant->to_relative($fullname);
            $data['ext'] = strtolower(substr($name,strrpos($name,'.')+1));
            $itype = $this->_typehelper->get_file_type($fullname);
            $stype = FileType::getName($itype);
            $data['filetype'] = $stype;
            $data['is_image'] = $this->_typehelper->is_image($fullname);
            $data['is_svg'] = $data['ext'] === 'svg';
            if( $data['is_image'] && !$data['is_svg'] ) {
                $small = false;
                if( $dosize ) {
                    $imgsize = @getimagesize($fullname);
                    if( $imgsize && ($imgsize[0] || $imgsize[1]) ) {
                        $data['dimensions'] = $imgsize[0].' x '.$imgsize[1];
                        if( $imgsize[0] <= 96 && $imgsize[1] <= 48 ) { //c.f. cms_siteprefs ['thumbnail_width', 'thumbnail_height']
                            $small = true;
                            $data['is_small'] = true;
                        }
                    }
                }
                if( !$small ) {
                    $data['is_thumb'] = $this->_typehelper->is_thumb($name);
                    $imagepath = $startdir.DIRECTORY_SEPARATOR.'thumb_'.$name;
                    $data['thumbnail'] = is_file($imagepath);
                    $data['icon'] = Utils::get_file_icon($data['ext'],false); // in case we're not showing thumbnails
                }
                $thumbs[] = 'thumb_'.$name;
            }
        }
        $files[$name] = $data;
    }

    if( $profile->show_thumbs && count($thumbs) ) {
        // remove thumbnails that are not orphaned
        foreach( $thumbs as $thumb ) {
            if( isset($files[$thumb]) ) { unset($files[$thumb]); }
        }
    }
    // done the loop, now sort
    usort($files, function($file1,$file2) use ($profile) {
        if( $file1['isdir'] && !$file2['isdir'] ) { return -1; }
        if( !$file1['isdir'] && $file2['isdir'] ) { return 1; }
        if( $profile->sort ) { return strnatcmp($file1['name'],$file2['name']); }
        return 0;
    });

    $assistant2 = new PathAssistant($config,CMS_ROOT_PATH);
    $t = $assistant2->to_relative($startdir);
    if( strpos($t, DIRECTORY_SEPARATOR) > 0 ) {
        $parts = explode(DIRECTORY_SEPARATOR, $t);
        $dir = NlsOperations::get_language_direction();
        if( $dir == 'rtl' ) {
            $cwd_for_display = implode (' &#171; ', $parts); // << separator for rtl locale
        }
        else {
            $cwd_for_display = implode (' &#187; ', $parts); // >> for ltr
        }
    }
    else {
        $cwd_for_display = $t;
    }

    if( $cwd ) {
        //changing to the parent dir is valid
        $p = strrpos($cwd, DIRECTORY_SEPARATOR, 1);
        if ($p !== false) {
            $parent = substr($cwd, 0, $p);
        }
        else {
            $parent = '';
        }
        $parms = [ 'seldir'=>$parent, 'inst'=>$inst ];
        $up = base64_encode(json_encode($parms,
            JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE));
        $url = $this->create_url($id, 'filepicker', $returnid); // come back here
        $upurl = str_replace('&amp;', '&', $url).'&'.CMS_JOB_KEY.'=1&_enc='.$up;
    }
    else {
        $upurl = '';
    }

    $typename = $profile->typename;
	// input[file] parameters
	$mime = $profile->file_mimes;
	$extensions = $profile->file_extensions;
	if ($extensions) {
        $extjs = '["'.str_replace(',', '","', $extensions).'"]';
	}
    else {
        $extjs = '[]';
    }

    $baseurl = $this->GetModuleURLPath();

    // NOTE to use actual (instead of fake) admin js, the full-page template needs
    // fully-populated $headercontent, just as if the iframe were generated by a normal admin request
    // c.f. admintheme::AdminHeaderSetup() etc which populate via an admin-request hooklist
    // i.e. also needs at least: admin + theme js & admin + theme css

    $incs = cms_installed_jquery(true, true, true, true);
    $url = cms_path_to_url($incs['jquicss']);
    $headinc = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />

EOS;

    // get the latest relevant css
    $css_files = ['filepicker.css', 'filepicker.min.css'];
    $mtime = -1;
    $sel_file = null;
    $path = cms_join_path($this->GetModulePath(),'lib','css').DIRECTORY_SEPARATOR;
    foreach( $css_files as $name ) {
        $fp = $path.$name;
        if( is_file($fp) ) {
            $fmt = filemtime($fp);
            if( $fmt > $mtime ) {
                $mtime = $fmt;
                $sel_file = $name;
            }
        }
    }
    if( $sel_file ) {
        $headinc .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$baseurl}/lib/css/{$sel_file}" />

EOS;
    }

    $jsm = new ScriptsMerger();
    $jsm->queue_file($incs['jqcore'], 1);
//    if( CMS_DEBUG )
        $jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this ? or keep if (CMS_DEBUG)?
//    }
    $jsm->queue_file($incs['jqui'], 1);
//  $jsm->queue_file($path.'jquery.dm-uploader.min.js', 2);
//  $jsm->queue_file($path.'fakeadmin.js', 2);
//  $jsm->queue_file($path.'filebrowser.min.js', 2);
    $headinc .= $jsm->page_content('', false, false);

    $url = $this->create_url($id,'ajax_cmd',$returnid,['forjs'=>1]);
    $url = str_replace('&amp;','&',$url).'&'.CMS_JOB_KEY.'=1';

    //CHECKME lang() usage ok if not an admin request ?
    $lang = (object) [
        'cancel' => $this->Lang('cancel'),
        'choose' => $this->Lang('choose'),
        'clear' => $this->Lang('clear'),
        'confirm' => $this->Lang('confirm'),
        'confirm_delete' => $this->Lang('confirm_delete'),
        'error_ext' => $this->Lang('error_upload_ext', '%s'),
        'error_size' => $this->Lang('error_upload_size', '%s'),
        'error_type' => $this->Lang('error_upload_type', '%s'),
        'error_failed_ajax' => $this->Lang('error_failed_ajax'),
        'error_problem_upload' => $this->Lang('error_problem_upload'),
        'error_title' => $this->Lang('error_title'),
        'no' => $this->Lang('no'),
        'ok' => $this->Lang('ok'),
        'select_file' => $this->Lang('select_a_file'),
        'yes' => $this->Lang('yes'),
    ];
    $lang_js = json_encode($lang);

//BAD OLD <script type="text/javascript" src="{$baseurl}/lib/js/jquery.fileupload.js"></script>
    $footinc = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.dm-uploader.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/fakeadmin.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/filebrowser.js"></script>
<script type="text/javascript">
//<![CDATA[
$(function() {
 var filepicker = new CMSFileBrowser({
  cmd_url: '$url',
  cwd: '$cwd',
  cd_url: '$upurl',
  inst: '$inst',
  type: '$typename',
  mime: '$mime',
  extensions: $extjs,
  lang: $lang_js
 });
});
//]]>
</script>

EOS;
    // this template generates a full html page
    $tpl = $smarty->createTemplate($this->GetTemplateResource('filepicker.tpl')); //,null,null,$smarty);

    $tpl->assign('module_url',$baseurl)
     ->assign('topurl',$topurl)
     ->assign('cwd_for_display',$cwd_for_display)
     ->assign('cwd_up',$cwd != false)
     ->assign('files',$files)
     ->assign('inst',$inst)
     ->assign('profile',$profile)
     ->assign('headercontent',$headinc)
     ->assign('bottomcontent',$footinc);

    $tpl->display();
}
catch( Exception $e ) {
    audit('','FilePicker',$e->GetMessage());
    echo $smarty->errorConsole( $e, false );
}
return '';
