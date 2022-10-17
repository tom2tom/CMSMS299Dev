<?php
/*
Procedure to list all user-plugins (a.k.a. UDT's)
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Lone;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$pmod = check_permission($userid, 'Manage User Plugins');
$access = $pmod || check_permission($userid, 'View UserTag Help');

$ops = Lone::get('UserTagOperations');
$items = $ops->ListUserTags();
foreach ($items as $id=>$name) {
    $data = $ops->GetUserTag($name, 'description');
    $tags[] = [
        'id' => $id, //fake id <= $ops::MAXFID for UDTFiles
        'name' => $name,
        'description' => $data['description'] ?? null,
    ];
}

$themeObject = Lone::get('Theme');

$iconadd = $themeObject->DisplayImage('icons/system/newobject.png', _la('add'),'','','systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.png', _la('edit'),'','','systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.png', _la('delete'),'','','systemicon');
$iconinfo = $themeObject->DisplayImage('icons/system/help.png', _la('parameters'),'','','systemicon');

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = Lone::get('Smarty');
$smarty->assign([
    'access' => $access,
    'pmod' => $pmod,
    'addurl' => 'openusertag.php',
    'editurl' => 'openusertag.php',
    'iconadd' => $iconadd,
    'iconedit' => $iconedit,
    'icondel' => $icondel,
    'iconinfo' => $iconinfo,
    'tags' => $tags,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

if ($access || $pmod) {
//    $nonce = get_csp_token();
    $out = <<<EOS
<script type="text/javascript">
//<![CDATA[

EOS;
    if ($access) {
        $close = _la('close');
        $out .= <<<EOS
function getParms(tagname) {
 var dlg = $('#params_dlg');
 $.get('usertagparams.php{$urlext}', {
  name: tagname
 }, function(data) {
  dlg.find('#params').html(data);
  cms_dialog(dlg, {
   modal: true,
   width: 'auto',
   buttons: {
    '$close': function() {
     cms_dialog($(this), 'destroy');
    }
   }
  });
 },
 'html');
 dlg.find('#namer').text(tagname);
}

EOS;
    }
    if ($pmod) {
        $confirm = addcslashes(_la('confirm_delete_usrplg'), "'\n\r");
        $out .= <<<EOS
function doDelete(tagname) {
 cms_confirm('$confirm').done(function() {
  var u = 'deleteusertag.php{$urlext}&name=' + tagname;
  window.location.replace(u); // no go-back
 });
}

EOS;
    }
    $out .= <<<EOS
//]]>
</script>
EOS;
}
add_page_foottext($out);

$content = $smarty->fetch('listusertags.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
