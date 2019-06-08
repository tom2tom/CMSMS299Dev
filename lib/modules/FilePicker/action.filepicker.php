<?php
# FilePicker module action: filepicker
# Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

use CMSMS\FileType;
use FilePicker\PathAssistant;
use FilePicker\TemporaryProfileStorage;
use FilePicker\Utils;

if( !isset($gCms) ) exit;
//BAD in iframe if( !check_login(true) ) exit; // admin only.... but any admin

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

//
// initialization
//
$sesskey = cms_utils::hash_string(__FILE__);
if( isset($_GET['_enc']) ) {
    $parms = unserialize(base64_decode($_GET['_enc']), ['allowed_classes'=>false]);
    $_GET = array_merge($_GET,$parms);
    unset($_GET['_enc']);
}

try {
    $inst = trim(cleanValue(get_parameter_value($_GET,'inst')));
    $sig = trim(cleanValue(get_parameter_value($_GET,'sig')));
    $type = trim(cleanValue(get_parameter_value($_GET,'type'))); //TODO CHECKME Filetype enum value int
    $nosub = (int) get_parameter_value($_GET,'nosub');

    if( $sig ) {
        $profile = TemporaryProfileStorage::get($sig);
    }
    else {
        $profile = null;
    }
    if( !$profile ) {
        $profile = $this->get_default_profile();
    }
    if( !$sig && $type && $profile ) {
        $profile = $profile->overrideWith( [ 'type'=>$type ] );
        $sig = TemporaryProfileStorage::set($profile);
    }
    if( !$this->CheckPermission('Modify Files') ) {
        $profile = $profile->overrideWith( ['can_upload'=>false, 'can_delete'=>false, 'can_mkdir'=>false ] );
    }

    // get our absolute top directory, and its matching url
    $topdir = $profile->top;
    if( !$topdir ) {
        $topdir = $profile->top = $config['uploads_path'];
    }
    $assistant = new PathAssistant($config, $topdir);

    // get our current working directory relative to $topdir
    // use cwd stored in session first... then if necessary the profile topdir, then if necessary, the absolute topdir
    if( isset($_SESSION[$sesskey]) ) {
        $cwd = trim($_SESSION[$sesskey]);
    }
    else {
        $cwd = '';
    }
    if( !$cwd && $profile->top ) {
        $cwd = $assistant->to_relative($profile->top);
    }
    if( !$nosub && isset($_GET['subdir']) ) {
        $cwd .= DIRECTORY_SEPARATOR . filter_var($_GET['subdir'], FILTER_SANITIZE_STRING);
        $cwd = $assistant->to_relative($assistant->to_absolute($cwd));
    }
    // failsafe, if we don't have a valid working directory, set it to the $topdir;
    if( $cwd && !$assistant->is_valid_relative_path( $cwd ) ) {
        $cwd = '';
    }
    //if( $cwd ) $_SESSION[$sesskey] = $cwd;
    $_SESSION[$sesskey] = $cwd;

    // now we're set to go.
	$topurl = $assistant->get_top_url();
    $starturl = $assistant->relative_path_to_url($cwd);
    $startdir = $assistant->to_absolute($cwd);

    function get_thumbnail_tag(string $file, string $path, string $baseurl) : string
    {
        $imagepath = $path.DIRECTORY_SEPARATOR.'thumb_'.$file;
        if( is_file($imagepath) ) {
            return "<img src='".$baseurl.'/thumb_'.$file."' alt='".$file."' title='".$file."' />";
        }
        return '';
    }

    //
    // get file list TODO c.f. FilePicker\Utils::get_file_list()
    //
    $files = $thumbs = [];
    $filesizename = [' Bytes', ' KB', ' MB'];
    $items = scandir($startdir, SCANDIR_SORT_NONE);
    for( $name = reset($items); $name !== false; $name = next($items) ) {
        if( $name == '.' ) {
            continue;
        }
        $fullname = cms_join_path($startdir,$name);
        if( $name == '..' ) {
            if ($assistant->is_relative($fullname)) {
                continue;
            }
        }
        if( !$profile->show_hidden && ($name[0] == '.' || $name[0] == '_') ) {
            continue;
        }
        if( is_dir($fullname) && !$assistant->is_relative($fullname) ) {
            continue;
        }
        if( !$this->is_acceptable_filename( $profile, $fullname ) ) {
            continue;
        }

        $data = ['name' => $name, 'fullpath' => $fullname];
        $data['fullurl'] = $starturl.'/'.$name;
        $data['isdir'] = is_dir($fullname);
        if( $data['isdir'] ) {
            $data['isparent'] = ( $name == '..' );
            $data['relurl'] = $data['fullurl'];
            $data['ext'] = '';
            $data['is_image'] = false;
            $data['is_thumb'] = false;
            if( $name == '..' ) {
                $t = 'up'; //TODO or 'home'
            }
            else {
                $t = '';
            }
            $data['icon'] = Utils::get_file_icon($t,TRUE);
        }
        else {
            $data['isparent'] = false;
            $data['relurl'] = $assistant->to_relative($fullname);
            $data['ext'] = strtolower(substr($name,strrpos($name,'.')+1));
            $data['is_image'] = $this->_typehelper->is_image($fullname);
            $data['is_thumb'] = $this->_typehelper->is_thumb($name);
            $data['icon'] = Utils::get_file_icon($data['ext'],false);
        }
        $type = $this->_typehelper->get_file_type($fullname);
        $data['filetype'] = ($type) ? FileType::getName($type) : '';
        $data['dimensions'] = '';
        if( $data['is_image'] && !$data['is_thumb'] ) {
            $data['thumbnail'] = get_thumbnail_tag($name,$startdir,$starturl);
            $thumbs[] = 'thumb_'.$name;
            $imgsize = @getimagesize($fullname);
            if( $imgsize ) $data['dimensions'] = $imgsize[0].' x '.$imgsize[1];
        }
        $info = @stat($fullname);
        if( $info && $info['size'] > 0 ) {
            $data['size'] = round($info['size']/pow(1024, ($i = floor(log($info['size'], 1024)))), 2) . $filesizename[$i];
        }
        else {
            $data['size'] = null;
        }
        if( $data['isdir'] ) {
            $parms = [ 'subdir'=>$name, 'inst'=>$inst, 'sig'=>$sig ];
            if( $type ) $parms['type'] = $type;
            $url = $this->create_url($id,'filepicker',$returnid).'&'.CMS_JOB_KEY.'=1&_enc='.base64_encode(serialize($parms));
            $data['chdir_url'] = $url;
        }
        $files[$name] = $data;
    }

    if( $profile->show_thumbs && count($thumbs) ) {
        // remove thumbnails that are not orphaned
        foreach( $thumbs as $thumb ) {
            if( isset($files[$thumb]) ) unset($files[$thumb]);
        }
    }
    // done the loop, now sort TODO per Profile
    usort($files, function($file1,$file2) {
        if( $file1['isdir'] && !$file2['isdir'] ) return -1;
        if( !$file1['isdir'] && $file2['isdir'] ) return 1;
        return strnatcmp($file1['name'],$file2['name']);
    });

    $assistant2 = new PathAssistant($config,CMS_ROOT_PATH);
    $cwd_for_display = $assistant2->to_relative($startdir);

    $theme = cms_utils::get_theme_object();
    $baseurl = $this->GetModuleURLPath();
	// get the latest relevant css TODO just have one
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
        $out = <<<EOS
<link rel="stylesheet" type="text/css" href="{$baseurl}/lib/css/{$sel_file}" />

EOS;
    }
    else {
        $out = '';
    }

	$scripts = cms_installed_jquery(true,false,false,false);
    $url = cms_path_to_url($scripts['jqcore']);
    $out .= '<script type="text/javascript" src="'.$url.'"></script>'."\n";

    $url = $this->create_url($id,'ajax_cmd',$returnid,['forjs'=>1]);
    $url = str_replace('&amp;','&',$url).'&'.CMS_JOB_KEY.'=1';

    $lang = [];
    $lang['confirm_delete'] = $this->Lang('confirm_delete');
    $lang['ok'] = $this->Lang('ok');
    $lang['error_problem_upload'] = $this->Lang('error_problem_upload');
    $lang['error_failed_ajax'] = $this->Lang('error_failed_ajax');
    $lang_js = json_encode($lang);

    $js = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.fileupload.js"></script>
<script type="text/javascript" src="{$baseurl}/lib/js/filebrowser.js"></script>
<script type="text/javascript">
//<![CDATA[
$(function() {
 var filepicker = new CMSFileBrowser({
  cmd_url: '$url',
  cwd: '$cwd',
  sig: '$sig',
  inst: '$inst',
  lang: $lang_js
 });
});
//]]>
</script>

EOS;

    // this template generates a full page, for inclusion in an iframe
    $tpl = $smarty->createTemplate($this->GetTemplateResource('filepicker.tpl'),null,null,$smarty);

    $tpl->assign('module_url',$baseurl)
	 ->assign('topurl',$topurl)
     ->assign('cwd_for_display',$cwd_for_display)
     ->assign('files',$files)
     ->assign('inst',$inst)
     ->assign('profile',$profile)
     ->assign('headercontent',$out)
     ->assign('bottomcontent',$js);

    $tpl->display();
}
catch( Exception $e ) {
    audit('','FilePicker',$e->GetMessage());
    echo $smarty->errorConsole( $e, false );
}
