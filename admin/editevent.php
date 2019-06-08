<?php
#procedure to modify an event
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

use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\UserPluginOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
	redirect('listevents.php'.$urlext);
	return;
}

$userid = get_userid();
$access = check_permission($userid, 'Modify Events');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup	lang('noaccessto', lang('modifyeventhandler'))
    return;
}

$action = '';
$description = '';
$event = '';
$handler = '';
$sender = '';
$sendername = '';

if ($access) {
	$icondown = $themeObject->DisplayImage('icons/system/arrow-d.gif', lang('down'),'','','systemicon');
	$iconup = $themeObject->DisplayImage('icons/system/arrow-u.gif', lang('up'),'','','systemicon');
	$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'),'','','systemicon');

	if (isset($_POST['add'])) {
		// we're adding some funky event handler
		$sender = trim(cleanValue($_POST['originator']));
		$event = trim(cleanValue($_POST['event']));
		$handler = trim(cleanValue($_POST['handler']));
		if ($sender && $event && $handler) {
			if (strncmp($handler, 'm:', 2) == 0) {
				$handler = substr($handler, 2);
				Events::AddStaticHandler($sender, $event, [$handler, ''], 'M');
			} else {
				Events::AddStaticHandler($sender, $event, ['', $handler], 'U');
			}
		}
	} else {
		// perhaps we're processing a link-click up/down/delete
		//TODO clear events cache(s) when relevant

		if (!empty($_GET['action'])) $action = trim(cleanValue($_GET['action']));
		if (!empty($_GET['originator'])) $sender = trim(cleanValue($_GET['originator']));
		if (!empty($_GET['event'])) $event = trim(cleanValue($_GET['event']));
		if (!empty($_GET['handler'])) $handler = (int)$_GET['handler'];
		if (!empty($_GET['order'])) {
			$cur_order = (int)$_GET['order'];
		} else {
			$cur_order = -1;
		}

		switch ($action) {
		case 'up':
			// move an item up (decrease its order)
			// increases the previous order, and decreases the current handler id
			if (!$handler || $cur_order < 1) {
//				$themeObject->RecordNotice('error', lang('someerror')); //TODO useless
				return;
			}
			Events::OrderHandlerUp($handler);
			break;

		case 'down':
			// move an item down (increase its order)
			// decreases the next order, and increases the current handler id
			if (!$handler || $cur_order < 1) {
				return;
			}
			Events::OrderHandlerDown($handler);
			break;

		case 'delete':
			if (!$handler) {
//				$themeObject->RecordNotice('error', lang('missingparams')); //TODO useless befor return
				return;
			}
			Events::RemoveEventHandlerById($handler);
			break;

		default:
			// unknown action
			break;
		} // switch
	} // not adding

	if ($sender == 'Core') {
		$sendername = lang('core');
		$description = Events::GetEventDescription($event);
	} else {
		$objinstance = cms_utils::get_module($sender);
		$sendername  = $objinstance->GetFriendlyName();
		$description = $objinstance->GetEventDescription($event);
	}

	// get the handlers for this event
	$handlers = Events::ListEventHandlers($sender, $event);

	// get all available handlers
	$allhandlers = null;
	// some of them being user-defined tags
	$ops = UserPluginOperations::get_instance();
	$plugins = $ops->get_list();
	foreach ($plugins as $plugin_name) {
		$allhandlers[$plugin_name] = $plugin_name;
	}
	// and others being modules
	$allmodules = $ops->GetInstalledModules();
	foreach ($allmodules as $key) {
		if ($key == $sendername) continue;
		$modinstance = $ops->get_module_instance($key);
		if ($modinstance && $modinstance->HandlesEvents()) {
			$allhandlers[$key] = 'm:'.$key;
		}
	}
} else {
	$allhandlers = null;
	$handlers = null;
	$icondel = null;
	$icondown = null;
	$iconup = null;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
	'access' => $access,
	'allhandlers' => $allhandlers,
	'description' => $description,
	'event' => $event, //name
	'handlers' => $handlers,
	'icondel' => $icondel,
	'icondown' => $icondown,
	'iconup' => $iconup,
	'originator' => $sender, //internal name
	'originname' => $sendername, //public/friendly name
	'selfurl' => $selfurl,
    'extraparms' => $extras,
	'urlext' => $urlext,
]);

include_once 'header.php';
$smarty->display('editevent.tpl');
include_once 'footer.php';
