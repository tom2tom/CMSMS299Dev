<?php
/*
Procedure to list the site's folder-controlsets
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

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$access = check_permission($userid, 'Manage Site Preferences');
if (!$access) {
    exit;
}

cleanArray($_GET);

if (!empty($_GET['delete'])) {
    try {
        $setid = (int) get_parameter_value($_GET, 'setid');
        if ($setid < 1) {
            throw new LogicException('Invalid id provided to delete operation');
        }

//        $profile = $this->_dao->loadById($setid);
        if (!$set) {
            throw new LogicException('Invalid id provided to delete operation');
        }

        $default = cms_siteprefs::get('defaultcontrolset', -1);
        if ($default == $profile->id) {
            cms_siteprefs::set('defaultcontrolset', -999);
        }

//        $this->_dao->delete($profile);
    } catch (Exception $e) {
//        $this->SetError($e->GetMessage());
    }
} elseif (!empty($_GET['default'])) {
    cms_siteprefs::set('defaultcontrolset', (int)$_GET['setid']);
}

$default = cms_siteprefs::get('defaultcontrolset', -1);  // $this->_dao->getDefaultProfileId());
$sets = [];
$ob = new CMSMS\FolderProfile();
$data = $ob->get_all();
if ($data) {
    $tz = (!empty($config['timezone'])) ? $config['timezone'] : 'UTC';
    $dt = new DateTime(null, new DateTimeZone($tz));
    $fmt = cms_siteprefs::get('defaultdateformat', 'Y-m-d');
    foreach ($data as &$row) {
        $oneset = new stdClass();
        $oneset->id = $row['id'];
        $oneset->name = $row['name'];
        $oneset->reltop = $row['top'] ?? '';
        $dt->modify($row['create_date']);
        $oneset->created = $dt->format($fmt);
        $dt->modify($row['modified_date']);
        $oneset->modified = $dt->format($fmt);
        $sets[] = $oneset;
    }
    unset($row);
}

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$addurl = 'opencontrol.php'.$urlext.'&amp;setid=-1';
$editurl = 'opencontrol.php'.$urlext;
$delurl = basename(__FILE__).$urlext.'&amp;delete=1';
$defaulturl = basename(__FILE__).$urlext.'&amp;default=1';

$themeObject = cms_utils::get_theme_object();
$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', lang('add'), '', '', 'systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'), '', '', 'systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'), '', '', 'systemicon');
$iconyes = $themeObject->DisplayImage('icons/system/true.gif', lang('yes'), '', '', 'systemicon');
$iconno = $themeObject->DisplayImage('icons/system/false.gif', lang('no'), '', '', 'systemicon');

$smarty = Smarty::get_instance();
$smarty->assign([
    'addurl' => $addurl,
    'editurl' => $editurl,
    'deleteurl' => $delurl,
    'defaulturl' => $defaulturl,
    'iconadd' => $iconadd,
    'iconedit' => $iconedit,
    'icondel' => $icondel,
    'iconyes' => $iconyes,
    'iconyes' => $iconno,
    'dfltset_id' => $default,
    'sets' => $sets,
]);

include_once 'header.php';
$smarty->display('listcontrols.tpl');
include_once 'footer.php';
