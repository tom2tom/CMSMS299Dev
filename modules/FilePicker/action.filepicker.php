<?php
/*
FilePicker module action: filepicker
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Fernando Morgado and all other contributors from the CMSMS Development Team.

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

/*
This generates content (html, js, css) for a div intended for
standalone use, or for a whole page, intended for an iframe.
Those use a CMSFileBrowser js object with methods to interact with
the generated page and with a non-CMSMS file-upload js plugin,
currently dm-uplader (see https://github.com/danielm/uploader)
jQuery basictable is applied to the displayed table containing files/folders
*/

use CMSMS\Crypto;
use CMSMS\FileType;
use CMSMS\FolderControlOperations;
use CMSMS\FSControlValue;
use CMSMS\NlsOperations;
//use CMSMS\ScriptsMerger;
use FilePicker\PathAssistant;
use FilePicker\Utils;
use UnexpectedValueException;
use function CMSMS\log_error;

//if( some worthy test fails ) exit;
//BAD in iframe if( !check_login(true) ) exit; // admin only.... but any admin

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

//
// initialization
//
$contentonly = isset($params['content']) && cms_to_bool($params['content']);

if( !empty($params['_enc']) ) {
    $eparms = json_decode(base64_decode($params['_enc']), true); //has 'seldir'|'subdir', 'inst' etc no sanitize, rely on obfuscation to protect
    if( $eparms ) {
        $params = array_merge($params, $eparms);
        unset($params['_enc']);
    }
}

try {
/*
    //$mime & $extensions replicate profile properties, if a suitable profile exists now
    $mime = $params['mime'] ?? '';
    if( $mime ) {
        // defines filetypes to be displayed for potential upload c.f. $profile->file_extensions
        $mime = rawurldecode(trim($mime));
    }
    $extensions = $params['extensions'] ?? '';
    if( $extensions ) {
        // defines extensions of files' names to be displayed for potential upload c.f. $profile->file_mimes
        $extensions = rawurldecode(trim($extensions));
    }
*/
    $save = false;
    $inst = $params['inst'] ?? '';
    if( $inst ) {
        $profile = FolderControlOperations::get_cached($inst);
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
        if( is_numeric($stype) ) {
            $itype = (int)$stype;
        }
        else {
            $itype = FileType::getValue($stype);
        }
        if( FolderControlOperations::is_file_type_acceptable($profile, $itype) ) {
/*            $profile = $profile->overrideWith([
                'type' => $itype,
            ]);
            $save = true;
*/
        }
        else {
            if ($itype == $stype) { $stype = FileType::getName($itype); }
            throw new UnexpectedValueException("Browsing files of type '$stype' is not permitted");
        }
    }

    if( !$this->CheckPermission('Modify Files') ) {
        $profile = $profile->overrideWith([
            'can_upload' => FSControlValue::NO,
            'can_delete' => FSControlValue::NO,
            'can_mkdir' => FSControlValue::NO
        ]);
        $save = true;
    }
//TODO MAYBE 'id'=>0, //? an identifier corresponding to db table key? $db->genId() ?
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
        $inst = FolderControlOperations::store_cached($profile);
    }

    $typename = $params['type'] ?? $profile->typename; // TODO per $params['type'] iff consistent with $profile
    // uploader parameters
//    $mime = $params['mime'] ?? $profile->file_mimes; // TODO default per $params['type'] iff consistent with $profile
    //$mime = $this->_typehelper->get_file_type_mime(FileType::getValue($typename));
//    $extensions = $params['exts'] ?? $profile->file_extensions;
    //if (!($extensions || $mime)) $extensions = $this->_typehelper->get_file_type_extensions(FileType::getValue($typename));

    $assistant = new PathAssistant($config, $topdir);
    $sesskey = Crypto::hash_string(__FILE__);

    if( isset($params['seldir']) ) {
        $cwd = $params['seldir'];
        if( $cwd ) {
            $cwd = trim($cwd);
        }
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

        $nosub = isset($params['nosub']) && cms_to_bool($params['nosub']);
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
    // get file list c.f. Utils::get_file_list($this, $profile, $startdir)
    //
    $files = $thumbs = [];
    $dosize = function_exists('getimagesize'); // GD extension present
    $parts = explode(',', $this->Lang('sizecodes'));
    if( count($parts) !== 3 ) {
        $parts = ['Bytes', 'kB', 'MB'];
    }
    $filesizename = [' '.$parts[0], ' '.$parts[1], ' '.$parts[2]];
    $items = scandir($startdir, SCANDIR_SORT_NONE);
    for( $name = reset($items); $name !== false; $name = next($items) ) {
        if( $name == '.' || $name == '..' ) { // i.e. no .. (parent) item in list
            continue;
        }
        if( !$profile->show_hidden && ($name[0] == '.' || $name[0] == '_') ) {
            continue;
        }
        $fullname = cms_join_path($startdir, $name);
        if( is_dir($fullname) ) {
            // anything here?
        }
        elseif( !$this->is_acceptable_filename($profile, $fullname, $stype) ) {
            continue;
        }
        $data = ['name' => $name, 'fullpath' => $fullname];
        $data['fullurl'] = $starturl.'/'.$name;
        $info = @stat($fullname);
        if( $info && $info['size'] > 0 ) {
            $data['size'] = round($info['size']/(1024 ** ($i = floor(log($info['size'], 1024)))), 2) . $filesizename[$i];
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
/*          if( $name == '..' ) {
                $t = 'up'; //TODO or 'home'
            }
            else {
                $t = '';
            }
            $data['icon'] = Utils::get_file_icon($t, true);
*/
            $data['icon'] = Utils::get_file_icon('', true);
            $parms = [
                'subdir' => $name,
                'inst' => $inst,
                'type' => $typename,
//                'mime' => $mime,
//                'exts' => $extensions,
            ];
            $up = base64_encode(json_encode($parms,
                JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $url = $this->create_action_url($id, 'filepicker', ['_enc' => $up, 'forjs'=>1, CMS_JOB_KEY=>1]); // come back here
            $data['chdir_url'] = $url;
        }
        else {
            $data['isparent'] = false;
            $data['relurl'] = $assistant->to_relative($fullname);
            $data['ext'] = strtolower(substr($name, strrpos($name, '.') + 1));
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
                        if( $imgsize[0] <= 96 && $imgsize[1] <= 48 ) { //c.f. CMSMS\AppParams ['thumbnail_width', 'thumbnail_height']
                            $small = true;
                            $data['is_small'] = true;
                        }
                    }
                }
                if( !$small ) {
                    $data['is_thumb'] = $this->_typehelper->is_thumb($name);
                    $imagepath = $startdir.DIRECTORY_SEPARATOR.'thumb_'.$name;
                    $data['thumbnail'] = is_file($imagepath);
                    $data['icon'] = Utils::get_file_icon($data['ext'], false); // in case we're not showing thumbnails
                }
                $thumbs[] = 'thumb_'.$name;
            }
        }
        $files[$name] = $data;
    }

    if( $profile->show_thumbs && $thumbs ) {
        // remove thumbnails that are not orphaned
        foreach( $thumbs as $thumb ) {
            if( isset($files[$thumb]) ) { unset($files[$thumb]); }
        }
    }
    // done the loop, now sort
    usort($files, function($file1, $file2) use ($profile) {
        if( $file1['isdir'] && !$file2['isdir'] ) { return -1; }
        if( !$file1['isdir'] && $file2['isdir'] ) { return 1; }
        if( $profile->sort ) {
            return strnatcmp($file1['name'], $file2['name']);
        }
        return 0;
    });

    $assistant2 = new PathAssistant($config, CMS_ROOT_PATH);
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
        if( $p !== false ) {
            $parent = substr($cwd, 0, $p);
        }
        else {
            $parent = '';
        }
        $parms = [
            'seldir' => $parent,
            'inst' => $inst,
            'type' => $typename,
//            'mime' => $mime,
//            'exts' => $extensions,
        ];
        $up = base64_encode(json_encode($parms,
            JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $upurl = $this->create_action_url($id, 'filepicker', ['_enc'=>$up, 'forjs'=>1, CMS_JOB_KEY=>1]); // come back here
    }
    else {
        $up = '';
        $upurl = '';
    }

    $baseurl = $this->GetModuleURLPath();

    // NOTE to use actual (instead of fake) admin js, the full-page template needs
    // fully-populated $headercontent, just as if the iframe were generated by a normal admin request
    // c.f. admintheme::AdminHeaderSetup() etc which populate via an admin-request hooklist
    // i.e. also needs at least: admin + theme js & admin + theme css
/*
 	$frontend = CMSMS\is_frontend_request();
    if ($frontend) { $incs = cms_installed_jquery(true, true, true, true); }
    // no StylessMerger BUT TODO CSP support ??
    if ($frontend) {$url = cms_path_to_url($incs['jquicss']); }
    $url2 = cms_get_css('basictable.css');
    $url3 = cms_get_css('browsefiles.css');
    $headinc = <<<EOS
<link rel="stylesheet" href="$url"> if ($frontend)
<link rel="stylesheet" href="$url2">
<link rel="stylesheet" href="$url3">

EOS;

    $jsm = new ScriptsMerger();
    $jsm->queue_file($incs['jqcore'], 1);
//    if( CMS_DEBUG )
        $jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this ? or keep if( CMS_DEBUG )?
//    }
    if ($frontend) {$jsm->queue_file($incs['jqui'], 1);
    $jsm->queue_matchedfile('jquery.basictable.js', 2);
    $jsm->queue_matchedfile('jquery.dm-uploader.js', 2);
    if ($frontend) { $jsm->queue_matchedfile('adminlite.js', 2); }
    $jsm->queue_matchedfile('filebrowser.js', 2);
    $headinc .= $jsm->page_content();

    $lang_js = '{';
    foreach ([
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
    ] as $k => $v) {
        $lang_js .= $k.':'.json_encode($v).',';
    }
    $p = strlen($lang_js);
    $lang_js[$p-1] = '}';
    $url = $this->create_action_url($id, 'ajax_cmd', ['forjs'=>1, CMS_JOB_KEY=>1]);
/*
    if( $contentonly ) {
 //breakpoint: 2000 DEBUG normally forceResponsive: false
        $footinc = <<<EOS
 $('#fp-list').basictable({
  forceResponsive: false
 });
 $('.cmsfp_elem').attr('data-cmsfp-instance', '$inst');

EOS;
    }
    else {
* /
//breakpoint: 2000 DEBUG normally forceResponsive: false
        $footinc = <<<'EOS'
<script type="text/javascript">
//<![CDATA[
$(function() {
 var ifrm = $(document).find('iframe');
 ifrm.on('load', function() {
  ifrm.contents().find('#fp-list').basictable({
   forceResponsive: false
  });
 });

EOS;
//    }
        if( $extensions ) {
            $extjs = '["'.str_replace(',', '","', $extensions).'"]';
        }
        else {
            $extjs = '[]';
        }
//target: null,
        $footinc .= <<<EOS
 var browser = new CMSFileBrowser({
  cmd_url: '$url',
  content: $contentonly,
  cwd: '$cwd',
  cd_url: '$upurl',
  inst: '$inst',
  type: '$typename',
  mime: '$mime',
  extensions: $extjs,
  lang: $lang_js
 });

EOS;
//    if( !$contentonly ) {
        $footinc .= <<<'EOS'
});
//]]>
</script>

EOS;
//    }
/*    $footinc .= <<<'EOS'
</script>

EOS;
*/
    $tpl = $smarty->createTemplate($this->GetTemplateResource('filepicker.tpl')); //, null, null, $smarty);
    $tpl->assign([
     'cwd_for_display' => $cwd_for_display,
     'cwd_up' => ($cwd != false),
     'cwd_updata' => $up,
     'files' => $files,
     'inst' => $inst,
     'module_url' => $baseurl,
     'profile' => $profile,
     'topurl' => $topurl,
     'type' => $typename
    ]);
    if( $contentonly ) {
        $tpl->assign('getcontent', 1);
        $body = $tpl->fetch();
        //return ajax data
        echo json_encode([
          'cwd' => $cwd,
          'inst' => $inst,
          'body' => $body
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }
    else {
        list($headinc, $footinc) = Utils::get_browsedata($this, [
            'cwd' => $cwd,
            'upurl' => $upurl,
//            'exts' => $extensions,
            'inst' => $inst,
            'listurl' => $this->get_browser_url(),
//            'mime' => $mime,
            'typename' => $typename
            ], true);
        $tpl->assign([
         'headercontent' => $headinc,
         'bottomcontent' => $footinc
        ]);
        $tpl->display();
//$adbg = $tpl->fetch();
//file_put_contents(TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'iframe.html');
//echo $adbg;
    }
}
catch (Throwable $t) {
    log_error($t->GetMessage(), 'FilePicker::filepicker');
    $this->ShowErrorPage($t->GetMessage());
}
