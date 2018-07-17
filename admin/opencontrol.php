<?php
/*
Procedure to add or edit a folder-controlset
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\internal\Smarty;
use FilePicker\ProfileException;

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$access = check_permission($userid, 'Modify Site Preferences');
if (!$access) {
    exit;
}

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

cleanArray($_POST);
if (isset($_POST['cancel'])) {
    redirect('listcontrols.php'.$urlext);
}

$themeObject = cms_utils::get_theme_object();
$setid = $_GET['setid'] ?? $_POST['setid'] ?? -1;
$setid = (int)$setid;

$ob = new CMSMS\FolderProfile();

if (isset($_POST['submit'])) {
    try {
//TODO update all $ob params from $POST
        $ob->save();
        $themeObject->ParkNotice('success', lang('TODO'));
        redirect('listcontrols.php'.$urlext);
    } catch (ProfileException $e) {
        $themeObject->RecordNotice('error', $e->GetMessage());
        $set = new stdClass();
        //TODO populate from current data
    }
} elseif ($setid > 0) {
    $db = CmsApp::get_instance()->GetDb();
    $row = $db->GetRow('SELECT id,name,data FROM '.CMS_DB_PREFIX.'controlsets WHERE id=?',[$setid]);
    if ($row) {
        $set = new stdClass();
        $set->id = $row['id'];
        $set->name = $row['name'];
        $detail = unserialize($row['data']);
        foreach ($detail as $k => $v) {
            $set->$k = $v;
        }
    } else {
        $themeObject->ParkNotice('error', 'TODO');
        redirect('listcontrols.php'.$urlext);
    }
} else {
    $set = (object)$ob->getRawData();
    $set->id = -1;
    $set->name = '';
    $set->reltop = '';
}

// get selectors for filetypes, sortfields
//  FileType:: enum
// sort: 'name','size','date' & optionally appended ',a[sc]' or ',d[esc]'
// get selectors for inc users, exc users, inc groups, exc groups
// CmsFormUtils::create_select([$parms]);

$smarty = Smarty::get_instance();
$smarty->assign('selfurl', basename(__FILE__));
$smarty->assign('urlext', $urlext);
$smarty->assign('set', $set);

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
 cms_equalWidth($('.boxchild label'));
});
//]]>
</script>
EOS;
$themeObject->add_footertext($js);

include_once 'header.php';
$smarty->display('opencontrol.tpl');
include_once 'footer.php';
