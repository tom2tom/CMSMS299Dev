<?php
/*
Procedure to add or edit a user-defined-tag / user-plugin
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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

use CMSMS\Lone;
use function CMSMS\de_specialize;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();

if (isset($_POST['cancel'])) {
    redirect('listusertags.php'.$urlext);
}

// specialize any malicious <[/]textarea> tags in the supplied string
// so that it may be safely parked in a textarea for the user to see/edit
$munge = function(string $text): string {
    $matches = [];
    $clean = preg_replace_callback('~<\s*(/?)\s*(textarea)\s*>~i', function($matches) {
        $pre = ($matches[1]) ? '&sol;' : ''; // OR &#47;
        return '&lt;'.$pre.$matches[2].'>';
    }, $text);
    return $clean;
};
// and the reverse ... (tho' never actually useful as such!)
$unmunge = function(string $text): string {
    $matches = [];
    $clean = preg_replace_callback('~&lt;(&sol;|&#47;)?(textarea)>~i', function($matches) {
        $pre = ($matches[1]) ? '/' : '';
        return '<'.$pre.$matches[2].'>';
    }, $text);
    return $clean;
};

$themeObject = Lone::get('Theme');

if (isset($_POST['submit']) || isset($_POST['apply']) ) {
    //these $_POST variables are further-sanitized downstream,
    // before storage and before use
    $code = $_POST['code'] ?? ''; // preserve 'code' verbatim
    // i.e. as cleaned/delivered from backend then maybe altered by the user
    unset($_POST['code']);
    de_specialize_array($_POST);

    $err = false;
    $tagname = sanitizeVal($_POST['tagname'], CMSSAN_FILE); // UDT might be file-stored
    $oldname = sanitizeVal($_POST['oldname'], CMSSAN_FILE);

    $desc = (!empty($_POST['description'])) ? trim($_POST['description']) : null;
    if ($desc) { $desc = $unmunge($desc); } // AND nl2br() ? striptags() ?
    $parms = (!empty($_POST['parameters'])) ? trim($_POST['parameters']) : null;
    if ($parms) { $parms = $unmunge($parms); } // AND nl2br() ? striptags() ?
    $lic = (!empty($_POST['license'])) ? trim($_POST['license']) : null;
    if ($lic) { $lic = $unmunge($lic); } // AND nl2br() ? striptags() ?
    if ($code) { $code = $unmunge($code); } // AND nl2br() ? striptags() ?

    $ops = Lone::get('UserTagOperations');
//if event exists : $ops->DoEvent( add | edit userpluginpre  etc)
    $props = [
        'id' => (int)$_POST['id'],
        'name' => $tagname,
        'oldname' => $oldname,
        'code' => $code,
        'description' => $desc,
        'parameters' => $parms,
        'license' => $lic,
        'detail' => 1 // specific message upon error
    ];

    $res = $ops->SetUserTag($tagname, $props);
    if ((is_array($res) && $res[0]) || ($res && !is_array($res))) {
//if event exists : $ops->DoEvent( add | edit userpluginpost etc)
    } else {
        $msg = $res[1] ?? '';
        if ($msg) {
            if (strpos($msg, ' ') === false) { $msg = _la($msg); }
        } else {
            $msg = ($oldname === '') ? _la('error_usrplg_save') : _la('error_usrplg_update');
        }
        $themeObject->RecordNotice('error', $msg);
        $err = true;
    }

    if (isset($_POST['submit']) && !$err) {
        $msg = ($oldname == '-1') ? _la('added_usrplg') : _la('updated_usrplg');
        $themeObject->ParkNotice('success', $msg);
        redirect('listusertags.php'.$urlext);
    }
} elseif (isset($_GET['tagname'])) {
    $tmp = de_specialize($_GET['tagname']);
    $tagname = sanitizeVal($tmp, CMSSAN_FILE);
} else {
    redirect('listusertags.php'.$urlext);
}

if ($tagname != '-1') {
    if (!isset($ops)) { $ops = Lone::get('UserTagOperations'); }
    $props = $ops->GetUserTag($tagname, '*');
    if ($props) {
        $props['oldname'] = $tagname;
    } else {
        $themeObject->RecordNotice('error', _la('error_internal'));
        redirect('listusertags.php'.$urlext);
    }
} else {
    $props = [
        'id' => -1, //new-tag indicator
        'oldname' => '', //ditto
        'code' => '',
        'description' => '',
        'parameters' => '',
        'license' => ''
    ];
}

$desc = (!empty($props['description'])) ? specialize($props['description']) : '';
if ($desc) { $desc = $munge($desc); }
$parms = (!empty($props['parameters'])) ? specialize($props['parameters']) : '';
if ($parms) { $parms = $munge($parms); }
if ($props['id'] > 0) {
    $lic = ''; //hence not displayed for new | dB-stored plugin
} else {
    $lic = (!empty($props['license'])) ? specialize($props['license']) : '';
    if ($lic) { $lic = $munge($lic); }
}
$code = $props['code'] ?? ''; // all relevant cleanup downstream
if ($code) { $code = $munge($code); }

$userid = get_userid(false);
$edit = check_permission($userid, 'Manage User Plugins');

$pageincs = get_syntaxeditor_setup(['edit'=>$edit, 'htmlid'=>'code', 'typer'=>'php']);
if (!empty($pageincs['head'])) {
    add_page_headtext($pageincs['head']);
}
$js = $pageincs['foot'] ?? '';

if ($edit) {
//    $nonce = get_csp_token();
    $s1 = addcslashes(_la('error_usrplg_name'), "'\n\r");
    $s2 = addcslashes(_la('error_usrplg_nocode'), "'\n\r");
    $js .= <<<EOS
<script>
$(function() {
 $('#userplugin button[name="submit"], #userplugin button[name="apply"]').on('click', function(ev) {
  var v = $('#name').val();
  if (v === '' || !v.match(/^[a-zA-Z_\x80-\xff][0-9a-zA-Z_\x80-\xff]*$/)) {
   ev.preventDefault();
   cms_notify('error', '$s1');
   return false;
  }
  v = geteditorcontent().trim();
  if (v === '') {
   ev.preventDefault();
   cms_notify('error', '$s2');
   return false;
  }
  setpagecontent(v);
 });
});
</script>

EOS;
}
add_page_foottext($js);

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
//id = -1 for new tag | dB id > 0 | fake id <= UserTagOperations::MAXFID for file
$extras['id'] = $props['id'];
$extras['oldname'] = $props['oldname'];

$smarty = Lone::get('Smarty');
$smarty->assign([
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'name' => $tagname,
    'description' => $desc,
    'parameters' => $parms,
    'license' => $lic,
    'code' => $code,
]);

$content = $smarty->fetch('openusertag.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
