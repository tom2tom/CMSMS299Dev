<?php
/*
Script to display recorded data about folder control-sets
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FolderControlOperations;
use CMSMS\SingleItem;
use CMSMS\Utils;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid(false);
$pmod = check_permission($userid, 'Modify Site Preferences');

$smarty = SingleItem::Smarty();
$allsets = FolderControlOperations::load_all(); // TODO as arrays ?

if ($allsets) {
    $dfltid = FolderControlOperations::get_default_profile_id();
    if ($pmod) {
        if (!empty($_GET['default'])) {
            $id = (int)$_GET['default']; // simple sanitize
            FolderControlOperations::set_default($id);
        } elseif (!empty($_GET['delete'])) {
            $id = (int)$_GET['delete']; // simple sanitize
            FolderControlOperations::delete($id);
            if ($dfltid == $id) {
                // default was deleted
                $dfltid = 0;
                $themeObject = Utils::get_theme_object();
                $msg = _ld('controlsets', 'new_default');
                $themeObject->RecordNotice('warn', $msg);
            }
        }
    }

    $msg = _ld('controlsets', 'confirm_delete');
    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('.delete').on('click', function(e) {
  e.preventDefault();
  cms_confirm_linkclick(this,$msg);
  return false;
 });
});
//]]>
</script>
EOS;
    add_page_foottext($js);

    $themeObject = Utils::get_theme_object();
    $iconyes = $themeObject->DisplayImage('icons/system/true', 'yes', '', '', 'systemicon', ['title' => _la('yes')]);
    $iconno = $themeObject->DisplayImage('icons/system/false', 'no', '', '', 'systemicon', ['title' => _la('no')]);
    $t = _ld('controlsets', 'edit_set');
    $iconedit = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon', ['title' => $t]);
    $t = _ld('controlsets', 'add_set');
    $iconadd = $themeObject->DisplayImage('icons/system/newobject', $t, '', '', 'systemicon', ['title' => $t]);
    $t = _ld('controlsets', 'delete_set');
    $icondelete = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon', ['title' => $t]);

    $selfurl = basename(__FILE__);
    $openurl = 'openaxscontrol.php';
    $urlext = get_secure_param();

    $smarty->assign([
     'selfurl' => $selfurl,
     'openurl' => $openurl,
     'urlext' => $urlext,
     'pmod' => $pmod,
     'ctrlsets' => $allsets,
     'dfltset_id' => $dfltid,
     'iadd' => $iconadd,
     'idel' => $icondelete,
     'iedt' => $iconedit,
     'iyes' => $iconyes,
     'ino' => $iconno,
    ]);
} else {
    $themeObject = Utils::get_theme_object();
    $t = _ld('controlsets', 'add_set');
    $iconadd = $themeObject->DisplayImage('icons/system/newobject', $t, '', '', 'systemicon', ['title' => $t]);
    $openurl = 'openaxscontrol.php';
    $urlext = get_secure_param();

    $smarty->assign([
     'openurl' => $openurl,
     'urlext' => $urlext,
     'pmod' => $pmod,
     'ctrlsets' => null,
     'iadd' => $iconadd,
    ]);
}

$content = $smarty->fetch('listaxscontrols.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
