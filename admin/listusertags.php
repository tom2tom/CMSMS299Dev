<?php
#procedure to list all user-defined plugins (formerly called UDT's, user tags)
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

$CMS_ADMIN_PAGE=1;
$CMS_LOAD_ALL_PLUGINS=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();
$pmod = check_permission($userid, 'Modify User Plugins');
$access = $pmod || check_permission($userid, 'View Tag Help');

$ops = new UserPluginOperations();
$patn = $ops->file_path('*');
$files = glob($patn, GLOB_NOESCAPE);
$tags = [];

foreach ($files as $fp) {
    $name = basename($fp, '.php');
    $meta = $ops->get_meta_data($name, '*');
    $tags[] = [
        'name' => $name,
        'description' => $meta['description'] ?? null,
        'help' => !empty($meta['parameters']),
    ];
}

$themeObject = cms_utils::get_theme_object();

$iconadd = $themeObject->DisplayImage('icons/system/newobject.png', lang('add'),'','','systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.png', lang('edit'),'','','systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.png', lang('delete'),'','','systemicon');
$iconinfo = $themeObject->DisplayImage('icons/system/help.png', lang('parameters'),'','','systemicon');

$selfurl = basename(__FILE__);

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'access' => $access,
    'addurl' => 'openusertag.php',
    'editurl' => 'openusertag.php',
    'iconadd' => $iconadd,
    'iconedit' => $iconedit,
    'icondel' => $icondel,
    'iconinfo' => $iconinfo,
    'tags' => $tags,
    'urlext' => $urlext,
    'selfurl' => $selfurl,
    'pmod' => $pmod,
]);

if ($access || $pmod) {
    $out = <<<EOS
<script type="text/javascript">
//<![CDATA[

EOS;
    if ($access) {
        $close = lang('close');
        $out .= <<<EOS
function getParms(tagname) {
 var dlg = $('#params_dlg');
 $.get('usertagparams.php{$urlext}', {
  name: tagname
 }, function(data) {
  dlg.find('#params').html(data);
  cms_dialog(dlg, {
   buttons: [{
   text: '$close',
   icon: 'ui-icon-cancel',
    click: function() {
     $(this).dialog('destroy');
    }
   }],
   modal: true,
   width: 'auto'
  });
 },
 'html');
 dlg.find('#namer').text(tagname);
}

EOS;
    }
    if ($pmod) {
        $confirm = json_encode(lang('confirm_delete_udt'));
        $out .= <<<EOS
function doDelete(tagname) {
 cms_confirm($confirm).done(function() {
  var u = 'deleteusertag.php{$urlext}&name=' + tagname;
  window.location.replace(u);
 });
}

EOS;
    }
    $out .= <<<EOS
//]]>
</script>
EOS;
}
$themeObject->add_footertext($out);

include_once 'header.php';
$smarty->display('listusertags.tpl');
include_once 'footer.php';
