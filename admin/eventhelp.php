<?php
#procedure to display recorded events and their details
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

use CMSMS\AppState;
use CMSMS\Events;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

//$urlext = get_secure_param();
if (empty($_GET['module'])) {
	return;
}
if (empty($_GET['event'])) {
    return;
}

$module = cleanValue($_GET['module']);
$event = cleanValue($_GET['event']);
if ($module == 'Core') {
	$desctext = Events::GetEventDescription($event);
	$text = Events::GetEventHelp($event);
} else {
    $modinstance = cms_utils::get_module($module);
    if (is_object($modinstance)) {
		$desctext = $modinstance->GetEventDescription($event);
		$text = $modinstance->GetEventHelp($event);
    } else {
        $desctext = '';
        $text = '';
	}
}
$hlist = Events::ListEventHandlers($module,$event);

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
	'desctext' => $desctext,
	'event' => $event,
	'hlist' => $hlist,
	'text' => $text,
]);

include_once 'header.php';
$smarty->display('eventhelp.tpl');
include_once 'footer.php';
