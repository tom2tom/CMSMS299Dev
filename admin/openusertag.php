<?php
#procedure to add or edit a user-defined-tag / user-plugin
#Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\UserPluginOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

if (isset($_POST['cancel'])) {
    redirect('listusertags.php'.$urlext);
}

$themeObject = cms_utils::get_theme_object();

if (isset($_POST['submit']) || isset($_POST['apply']) ) {
    $err = false;
    $tagname = cleanValue($_POST['tagname']);
    $oldname = cleanValue($_POST['oldtagname']);

    $ops = new UserPluginOperations();
    if ($oldname == '-1' || $oldname !== $tagname ) {
        if (!$ops->is_valid_plugin_name($tagname)) {
            $themeObject->RecordNotice('error', lang('udt_exists'));
            $err = true;
        }
    }

//? send :: adduserdefinedtagpre
//? send :: edituserdefinedtagpre
    $meta = ['name' => $tagname];
    //these are sanitized downstream, before storage ?
    $val = $_POST['description'];
    if ($val) $meta['description'] = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_BACKTICK);
    $val = $_POST['parameters'];
    if ($val) $meta['parameters'] = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_BACKTICK);
    $val = $_POST['license'];
    if ($val) $meta['license'] = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_BACKTICK);

    if ($ops->save($tagname, $meta, $_POST['code'])) {
//? send :: adduserdefinedtagpost
//? send :: edituserdefinedtagpost
    } else {
        $msg = ($oldname == '-1') ? lang('errorinserting_utd') : lang('errorupdating_udt');
        $themeObject->RecordNotice('error', $msg);
        $err = true;
    }

    if (isset($_POST['submit']) && !$err) {
        $msg = ($oldname == '-1') ? lang('added_udt') : lang('udt_updated');
        $themeObject->ParkNotice('success', $msg);
        redirect('listusertags.php'.$urlext);
    }
} elseif (isset($_GET['tagname'])) {
    $tagname = cleanValue($_GET['tagname']);
} else {
    redirect('listusertags.php'.$urlext);
}

if ($tagname != '-1') {
    $ops = new UserPluginOperations();
    list($meta, $code) = $ops->get($tagname);
} else {
    $meta = [];
    $code = '';
}

$edit = check_permission($userid, 'Modify User Plugins');
//TODO also $_GET['mode'] == 'edit'

$content = get_editor_script(['edit'=>$edit, 'htmlid'=>'code', 'typer'=>'php']);
if (!empty($content['head'])) {
    $themeObject->add_headtext($content['head']);
}
$js = $content['foot'] ?? '';

if ($edit) {
    $s1 = json_encode(lang('error_udt_name_chars'));
    $s2 = json_encode(lang('noudtcode'));
    $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('#userplugin button[name="submit"], #userplugin button[name="apply"]').on('click', function(ev) {
  var v = $('#name').val();
  if (v === '' || !v.match(/^[a-zA-Z_][0-9a-zA-Z_]*$/)) {
   ev.preventDefault();
   cms_notify('error', $s1);
   return false;
  }
  v = getcontent().trim();
  if (v === '') {
   ev.preventDefault();
   cms_notify('error', $s2);
   return false;
  }
  setcontent(v);
 });
});
//]]>
</script>

EOS;
}
$themeObject->add_footertext($js);

$selfurl = basename(__FILE__);

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'name' => $tagname,
    'description' => $meta['description'] ?? null,
    'parameters' => $meta['parameters'] ?? null,
    'license' => $meta['license'] ?? null,
    'code' => $code,
    'urlext' => $urlext,
    'selfurl' => $selfurl,
]);

include_once 'header.php';
$smarty->display('openusertag.tpl');
include_once 'footer.php';
