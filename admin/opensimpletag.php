<?php
#procedure to add or edit a user-defined-tag / user-plugin
#Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\SimpleTagOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();

if (isset($_POST['cancel'])) {
    redirect('listsimpletags.php'.$urlext);
}

$themeObject = Utils::get_theme_object();

if (isset($_POST['submit']) || isset($_POST['apply']) ) {
    $err = false;
    $tagname = cleanValue($_POST['tagname']);
    $oldname = cleanValue($_POST['oldname']);
    $ops = SimpleTagOperations::get_instance();
//if exists $ops->DoEvent( add | edit simplepluginpre  etc)
    //these $_POST variables are sanitized downstream, before use
    $props = [
        'id' => (int)$_POST['id'],
        'name' => $tagname,
        'oldname' => $oldname,
        'code' => $_POST['code'] ?? null,
        'description' => $_POST['description'] ?? null,
        'parameters' => $_POST['parameters'] ?? null,
        'license' => $_POST['license'] ?? null,
        'detail' => 1 // specific message upon error
    ];
    $res = $ops->SetSimpleTag($tagname, $props);
    if ($res[0]) {
//if exists $ops->DoEvent( add | edit simplepluginpost etc)
    } else {
        $msg = $res[1];
        if ($msg) {
            if (strpos(' ', $msg) === false) $msg = lang($msg);
        } else {
            $msg = ($oldname == '') ? lang('error_splg_save') : lang('error_splg_update');
        }
        $themeObject->RecordNotice('error', $msg);
        $err = true;
    }

    if (isset($_POST['submit']) && !$err) {
        $msg = ($oldname == '-1') ? lang('added_splg') : lang('updated_splg');
        $themeObject->ParkNotice('success', $msg);
        redirect('listsimpletags.php'.$urlext);
    }
} elseif (isset($_GET['tagname'])) {
    $tagname = cleanValue($_GET['tagname']);
} else {
    redirect('listsimpletags.php'.$urlext);
}

if ($tagname != '-1') {
    if (!isset($ops)) $ops = SimpleTagOperations::get_instance();
    $props = $ops->GetSimpleTag($tagname, '*');
    if ($props) {
        $props['oldname'] = $tagname;
    } else {
        $themeObject->RecordNotice('error', lang('error_internal'));
        redirect('listsimpletags.php'.$urlext);
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

$userid = get_userid(false);
$edit = check_permission($userid, 'Manage Simple Plugins');

$pageincs = get_syntaxeditor_setup(['edit'=>$edit, 'htmlid'=>'code', 'typer'=>'php']);
if (!empty($pageincs['head'])) {
    add_page_headtext($pageincs['head']);
}
$js = $pageincs['foot'] ?? '';

if ($edit) {
    $s1 = json_encode(lang('error_splg_name'));
    $s2 = json_encode(lang('error_splg_nocode'));
    $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('#userplugin button[name="submit"], #userplugin button[name="apply"]').on('click', function(ev) {
  var v = $('#name').val();
  if (v === '' || !v.match(/^[a-zA-Z_\x80-\xff][0-9a-zA-Z_\x80-\xff]*$/)) {
   ev.preventDefault();
   cms_notify('error', $s1);
   return false;
  }
  v = geteditorcontent().trim();
  if (v === '') {
   ev.preventDefault();
   cms_notify('error', $s2);
   return false;
  }
  setpagecontent(v);
 });
});
//]]>
</script>

EOS;
}
add_page_foottext($js);

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
//id = -1 for new tag | dB id > 0 | fake id <= SimpleTagOperations::MAXFID for file
$extras['id'] = $props['id'];
$extras['oldname'] = $props['oldname'];

$smarty = AppSingle::Smarty();
$smarty->assign([
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'name' => $tagname,
    'description' => $props['description'] ?? '',
    'parameters' => $props['parameters'] ?? '',
    'code' => $props['code'] ?? '',
]);
if ($props['id'] > 0) {
    $smarty->assign('license', null); //hence not displayed for dB-stored plugin
} else {
    $smarty->assign('license', $props['license'] ?? '');
}

$content = $smarty->fetch('opensimpletag.tpl');
require './header.php';
echo $content;
require './footer.php';
