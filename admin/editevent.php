<?php
#procedure to modify an event
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\internal\Smarty;
use CMSMS\ModuleOperations;

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if (isset($_POST['cancel'])) {
	redirect('listevents.php'.$urlext);
	return;
}

$userid = get_userid();
$access = check_permission($userid, 'Modify Events');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup	lang('noaccessto', lang('editeventhandler'))
    return;
}

$action = '';
$module = '';
$modulename = '';
$event = '';
$description = '';
$handler = '';

if ($access) {
	$icondown = $themeObject->DisplayImage('icons/system/arrow-d.gif', lang('down'),'','','systemicon');
	$iconup = $themeObject->DisplayImage('icons/system/arrow-u.gif', lang('up'),'','','systemicon');
	$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'),'','','systemicon');

	if (isset($_POST['add'])) {
		// we're adding some funky event handler
		$module = trim(cleanValue($_POST['module']));
		$event = trim(cleanValue($_POST['event']));
		$handler = trim(cleanValue($_POST['handler']));
		if ($module && $event && $handler) {
			if (strncmp($handler,'m:',2) == 0) {
				$handler = substr($handler, 2);
				Events::AddEventHandler($module, $event, false, $handler);
			} else {
				Events::AddEventHandler($module, $event, $handler);
			}
		}
	} else {
		// we're processing a link-click up/down/delete
		//TODO clear events cache(s) when relevant
		$cur_order = -1;
		if (!empty($_GET['action'])) $action = trim(cleanValue($_GET['action']));
		if (!empty($_GET['module'])) $module = trim(cleanValue($_GET['module']));
		if (!empty($_GET['event'])) $event = trim(cleanValue($_GET['event']));
		if (!empty($_GET['handler'])) $handler = (int)$_GET['handler'];
		if (!empty($_GET['order'])) $cur_order = (int)$_GET['order'];
		if ($module == '' || $event == '' || $action == '') {
//			$themeObject->RecordNotice('error', lang('missingparams')); //TODO useless before return
			return;
		}

		switch ($action) {
		case 'up':
			// move an item up (decrease its order)
			// increases the previous order, and decreases the current handler id
			if(!$handler || $cur_order < 1) {
//				$themeObject->RecordNotice('error', lang('someerror')); //TODO useless
				return;
			}
			Events::OrderHandlerUp($handler);
			break;

		case 'down':
			// move an item down (increase its order)
			// decreases the next order, and increases the current handler id
			if(!$handler || $cur_order < 1) {
//				$themeObject->RecordNotice('error', lang('someerror')); //TODO useless before return
				return;
			}
			Events::OrderHandlerDown($handler);
			break;

		case 'delete':
			if(!$handler) {
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

	if ($module == 'Core') {
		$description = Events::GetEventDescription($event);
		$modulename = lang('core');
	} else {
		$objinstance = cms_utils::get_module($module);
		$description = $objinstance->GetEventDescription($event);
		$modulename  = $objinstance->GetFriendlyName();
	}

	// get the handlers for this event
	$handlers = Events::ListEventHandlers($module, $event);

	// get all available handlers
	$allhandlers = null;
	// some of them being user-defined tags
	$mgr = cmsms()->GetSimplePluginOperations();
	$plugins = $mgr->get_list();
	if ($plugins) {
		foreach ($plugins as $plugin_name) {
			$allhandlers[$plugin_name] = $plugin_name;
		}
	}
	// and others being modules
	$modops = ModuleOperations::get_instance();
	$allmodules = $modops->GetInstalledModules();
	foreach ($allmodules as $key) {
		if ($key == $modulename) continue;
		$modinstance = $modops->get_module_instance($key);
		if ($modinstance && $modinstance->HandlesEvents()) {
			$allhandlers[$key] = 'm:'.$key;
		}
	}
} else {
	$handlers = null;
	$allhandlers = null;
	$icondown = null;
	$iconup = null;
	$icondel = null;
}

$selfurl = basename(__FILE__);

$smarty = Smarty::get_instance();
$smarty->assign([
	'access' => $access,
	'allhandlers' => $allhandlers,
	'description' => $description,
	'event' => $event, //name
	'handlers' => $handlers,
	'icondel' => $icondel,
	'icondown' => $icondown,
	'iconup' => $iconup,
	'module' => $module, //internal name
	'modulename' => $modulename, //public/friendly name
	'urlext' => $urlext,
	'selfurl' => $selfurl,
]);

include_once 'header.php';
$smarty->display('editevent.tpl');
include_once 'footer.php';

