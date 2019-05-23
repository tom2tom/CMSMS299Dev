<?php
#procedure to display recorded events and their details
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Events;

$CMS_ADMIN_PAGE = 1;
//$CMS_LOAD_ALL_PLUGINS = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$access = check_permission($userid, "Modify Events");
$senderfilter = (isset($_POST['senderfilter'])) ? $_POST['senderfilter'] : '';
if ($senderfilter == lang('all')) $senderfilter = '';

$senders = [];
$events = Events::ListEvents();
if (is_array($events)) {
    foreach($events as &$one) {
        if (!in_array($one['originator'], $senders)) {
            $senders[] = $one['originator'];
        }
        if ($one['originator'] == 'Core') {
            $one['description'] = Events::GetEventDescription($one['event_name']);
        } else {
            $modsend = cms_utils::get_module($one['originator']);
            $one['description'] = $modsend->GetEventDescription($one['event_name']);
        }
    }
    unset($one);
    sort($senders, SORT_NATURAL);
    $senders = [-1=>lang('all')] + $senders;
}

$themeObject = cms_utils::get_theme_object();

if ($access) {
    $iconedit = $themeObject->DisplayImage('icons/system/edit.gif',lang('modifyeventhandlers'),'','','systemicon');
} else {
    $iconedit = null;
}
$iconinfo = $themeObject->DisplayImage('icons/system/info.png', lang('help'),'','','systemicon');

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'access' => $access,
    'editurl' => 'editevent.php',
    'events' => $events,
    'helpurl' => 'eventhelp.php',
    'iconedit' => $iconedit,
    'iconinfo' => $iconinfo,
    'senders' => $senders,
    'senderfilter' => $senderfilter,
    'selfurl' => $selfurl,
	'extraparms' => $extras,
    'urlext' => $urlext,
]);

include_once 'header.php';
$smarty->display('listevents.tpl');
include_once 'footer.php';
