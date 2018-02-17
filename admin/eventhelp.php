<?php
#procedure to display recorded events and their details
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
//$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (empty($_GET['module'])) {
	return;
}
if (empty($_GET['event'])) {
    return;
}

$module = $_GET['module'];
$event = $_GET['event'];
$desctext = '';
$text = '';
if ($module == 'Core') {
	$desctext = Events::GetEventDescription($event);
	$text = Events::GetEventHelp($event);
} else {
    $modinstance = cms_utils::get_module($module);
    if (is_object($modinstance)) {
		$desctext = $modinstance->GetEventDescription($event);
		$text = $modinstance->GetEventHelp($event);
    }
}
$hlist = Events::ListEventHandlers($module,$event);

include_once 'header.php';

$smarty->assign([
	'desctext' => $desctext,
	'event' => $event,
	'hlist' => $hlist,
	'text' => $text,
]);

$smarty->display('eventhelp.tpl');
include_once 'footer.php';

