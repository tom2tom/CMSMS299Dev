<?php
/*
Procedure to modify an event
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\Lone;
use CMSMS\Utils;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
	redirect('listevents.php'.$urlext);
	return;
}

$userid = get_userid();
$access = check_permission($userid, 'Modify Events');

if (!$access) {
//TODO some pushed popup	_la('noaccessto', _la('modifyeventhandler'))
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$themeObject = Lone::get('Theme');
$action = '';
$description = '';
$event = '';
$handler = '';
$sender = '';
$sendername = '';

if (1) { //$access) {
	$icondown = $themeObject->DisplayImage('icons/system/arrow-d.gif', _la('down'),'','','systemicon');
	$iconup = $themeObject->DisplayImage('icons/system/arrow-u.gif', _la('up'),'','','systemicon');
	$icondel = $themeObject->DisplayImage('icons/system/delete.gif', _la('delete'),'','','systemicon');

	if (isset($_POST['add'])) {
		de_specialize_array($_POST);
		// we're adding some funky event-handler
		$sender = sanitizeVal($_POST['originator'], CMSSAN_FILE); // 'Core' | module-name
		$event = sanitizeVal($_POST['event'], CMSSAN_PURE); // letters only
		$handler = sanitizeVal($_POST['handler'], CMSSAN_PUNCT); // allow ':\' no need for '::' ?
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
		de_specialize_array($_GET);
		if (!empty($_GET['originator'])) {
			$sender = sanitizeVal($_GET['originator'], CMSSAN_FILE); // 'Core' | module-name
		}
		if (!empty($_GET['event'])) {
			$event = sanitizeVal($_GET['event'], CMSSAN_PURE); // letters only
		}
		if (!empty($_GET['handler'])) {
			$handler = sanitizeVal($_GET['handler'], CMSSAN_PUNCT); // allow ':\' no need for '::'
		}
		if (!empty($_GET['order'])) {
			$cur_order = (int)$_GET['order'];
		} else {
			$cur_order = -1;
		}

		$action = $_GET['action'] ?? ''; // no sanitizeVal() etc only explicit vals accepted
		switch ($action) {
		case 'up':
			// move an item up (decrease its order)
			// increases the previous order, and decreases the current handler id
			if (!$handler || $cur_order < 1) {
//				$themeObject->RecordNotice('error', _la('someerror')); //TODO useless
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
//				$themeObject->RecordNotice('error', _la('missingparams')); //TODO useless before return
				return;
			}
			Events::RemoveEventHandlerById($handler);
			break;

		default:
			// unknown action
			break;
		} // switch
	} // not adding

	$modops = Lone::get('ModuleOperations');

	if ($sender == 'Core') {
		$sendername = _la('core');
		$description = Events::GetEventDescription($event);
	} else {
		$mod = $modops->get_module_instance($sender);
		$sendername  = $mod->GetFriendlyName();
		$description = $mod->GetEventDescription($event);
	}

	// get the handlers for this event
	$handlers = Events::ListEventHandlers($sender, $event);

	// get all available event handlers
	$allhandlers = [];
	// user-defined tags (lowest priority, may be replaced by same-name below)
	$ops = Lone::get('UserTagOperations');
	$plugins = $ops->ListUserTags(); //UDTfiles included
	foreach ($plugins as $name) {
		$allhandlers[$name] = $name;
	}
	// module-tags
	$availmodules = $modops->GetInstalledModules();
	foreach ($availmodules as $name) {
		if ($name == $sendername) continue;
		$mod = $modops->get_module_instance($name);
		if ($mod && $mod->HandlesEvents()) {
			$allhandlers[$name] = 'm:'.$name;
		}
	}
	// TODO etc e.g. regular plugins, any callable ?
} else {
	$allhandlers = [];
	$handlers = [];
	$icondel = '';
	$icondown = '';
	$iconup = '';
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = Lone::get('Smarty');
$smarty->assign([
	'access' => $access,
	'allhandlers' => $allhandlers,
	'description' => specialize($description),
	'event' => $event, // internal name
	'handlers' => $handlers,
	'icondel' => $icondel,
	'icondown' => $icondown,
	'iconup' => $iconup,
	'originator' => $sender, // 'Core' or module name
	'originname' => specialize($sendername), //public/friendly name
	'selfurl' => $selfurl,
    'extraparms' => $extras,
	'urlext' => $urlext,
]);

$content = $smarty->fetch('editevent.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
