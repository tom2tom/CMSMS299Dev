<?php
#procedure to display all user-groups
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CMSMS\AppState;
use CMSMS\GroupOperations;
use CMSMS\UserOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$access = check_permission($userid, 'Manage Groups');
$padd = $access || check_permission($userid, 'Add Groups');

$gCms = cmsms();
$userops = UserOperations::get_instance();
$groupops = GroupOperations::get_instance();
$grouplist = $groupops->LoadGroups();
$n = count($grouplist);
$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 20;

if ($n > $limit) {
    $pagination = pagination($page, $n, $limit); //TODO
    $minsee = $page * $limit - $limit;
    $maxsee = $page * $limit - 1;
} else {
    $pagination = null;
    $minsee = 0;
    $maxsee = $n;
}

$themeObject = cms_utils::get_theme_object();

$icontrue = $themeObject->DisplayImage('icons/system/true.gif', lang('true'), '', '', 'systemicon');
$iconfalse = $themeObject->DisplayImage('icons/system/false.gif', lang('false'), '', '', 'systemicon');
$iconassign = $themeObject->DisplayImage('icons/system/groupassign.gif', lang('assignments'), '', '', 'systemicon');
$iconperms = $themeObject->DisplayImage('icons/system/permissions.gif', lang('permissions'), '', '', 'systemicon');
$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', lang('add'), '', '', 'systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'), '', '', 'systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'), '', '', 'systemicon');

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'access' => $access,
    'padd' => $padd,
    'addurl' => 'addgroup.php',
    'assignurl' => 'changegroupassign.php',
    'deleteurl' => 'deletegroup.php',
    'editurl' => 'editgroup.php',
    'permurl' => 'changegroupperm.php',
    'selfurl' => $selfurl,
	'extraparms' => $extras,
    'urlext' => $urlext,
    'grouplist' => $grouplist,
    'iconadd' => $iconadd,
    'iconassign' => $iconassign,
    'icondel' => $icondel,
    'iconedit' => $iconedit,
    'iconfalse' => $iconfalse,
    'iconperms' => $iconperms,
    'icontrue' => $icontrue,
    'maxsee' => $maxsee,
    'minsee' => $minsee,
    'pagination' => $pagination,
]);

include_once 'header.php';
$smarty->display('listgroups.tpl');
include_once 'footer.php';
