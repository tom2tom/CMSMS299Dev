<?php
/*
module-action request-processing for CMS Made Simple
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\Lone;
use CMSMS\RequestParameters;
//use CMSMS\Utils;
use function CMSMS\get_site_UUID;
//use function cmsms;

//$logfile = dirname(__DIR__).DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'debug.log';
//error_log('moduleinterface.php @start'."\n", 3, $logfile);
//error_log('moduleinterface.php REQUEST[]: '.json_encode($_REQUEST)."\n", 3, $logfile);

//REMINDER: vars defined here might be used as globals by downstream hook functions
$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);
$starttime = microtime();

require_once __DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
//error_log('moduleinterface.php @1'."\n", 3, $logfile);
AppState::set(AppState::ADMIN_PAGE); // TODO if frontend-module display
require_once __DIR__.DIRECTORY_SEPARATOR.'include.php';

//error_log('moduleinterface.php @2'."\n", 3, $logfile);

/*
TODO CIRCULAR WITH include.php
$params = RequestParameters::get_action_params();
$CMS_JOB_TYPE = $params[CMS_JOB_KEY] ?? 0
cmsms()->JOBTYPE = $CMS_JOB_TYPE;

BACK HERE ...
if ($CMS_JOB_TYPE < 2) {
	check_login();
}
*/
$params = RequestParameters::get_action_params();

if ($params) {
//	if (defined('ASYNCLOG')) {
//		error_log('moduleinterface.php parameters: '.json_encode($params)."\n", 3, ASYNCLOG);
//	}
	$modname = $params['module'] ?? '';
	$mact_mod = Lone::get('ModuleOperations')->get_module_instance($modname);
	if (!$mact_mod) {
		if ($CMS_JOB_TYPE == 0) {
			debug_to_log('Module '.$modname.' not found. This could indicate that the module is awaiting upgrade, or that there are other problems');
			redirect(cms_path_to_url(CMS_ADMIN_PATH).'/menu.php'); // OR $config['admin_url']
		} else {
			trigger_error('Module '.$modname.' not found. This could indicate that the module is awaiting upgrade, or that there are other problems');
			exit;
		}
	}
//	$params['id'] =
	$id = ($params['id'] ?? 'm1_');
//	$params['action'] =
	$action = ($params['action'] ?? '');
	unset($params['id'], $params['action']);
	unset($params['module'], $params['inline']);
	$params += RequestParameters::get_general_params($id);
} else {
//	if (defined('ASYNCLOG')) {
//		error_log('moduleinterface.php no action parameters'."\n", 3, ASYNCLOG);
//	}
	if ($CMS_JOB_TYPE == 0) {
		redirect(cms_path_to_url(CMS_ADMIN_PATH).'/menu.php');
	} else {
		trigger_error('Module-action parameters not found');
		exit;
	}
}

if ($mact_mod->SuppressAdminOutput($_REQUEST)) {
	if ($CMS_JOB_TYPE == 0) {
		$CMS_JOB_TYPE = 1; //too bad about irrelevant includes
//		cmsms()->JOBTYPE = $CMS_JOB_TYPE = 1;
	}
}

switch ($CMS_JOB_TYPE) {
	case 0:
		$themeObject = Lone::get('Theme');
		$themeObject->set_action_module($modname);
		Lone::insert('Theme', $themeObject);
		$base = Lone::get('Config')['admin_path'].DIRECTORY_SEPARATOR;
		// create a dummy template to be a proxy-parent for the action's template
//		$smarty = Lone::get('Smarty');
//		$template = $smarty->createTemplate('string:DUMMY PARENT');
		$template = null;
		// retrieve and park the action-output first, in case the action also generates header content
		$content = $mact_mod->DoActionBase($action, $id, $params, null, $template);

		require $base.'header.php';
		// back into the buffer,  now that 'pre-content' things are in place
		echo $content;

		if (!empty($params['module_error'])) $themeObject->RecordNotice('error', $params['module_error']);
		if (!empty($params['module_message'])) $themeObject->RecordNotice('success', $params['module_message']);

		require $base.'footer.php';
		break;
	case 1: // not full-page output
		$themeObject = Lone::get('Theme');
		$themeObject->set_action_module($modname);
		Lone::insert('Theme', $themeObject);
//		$smarty =  Lone::get('Smarty');
//		$template = $smarty->createTemplate('string:DUMMY PARENT');
		$template = null;
		echo $mact_mod->DoActionBase($action, $id, $params, null, $template);
		break;
	case 2:	//minimal
//		if (defined('ASYNCLOG')) {
//			error_log('moduleinterface.php @2'."\n", 3, ASYNCLOG);
//		}
		$fp = $mact_mod->GetModulePath().DIRECTORY_SEPARATOR.'action.'.$action.'.php';
		if (is_file($fp)) {
			$dojob = Closure::bind(function($filepath, $id, $params)
			{
				// variables in scope for convenience c.f. CMSModeule::DoAction()
				$gCms = Lone::get('App');
				$db = Lone::get('Db');
				$config = Lone::get('Config');
				//no $smarty (no template-processing)
				$uuid = get_site_UUID(); //since 3.0
				include $filepath;
			}, $mact_mod, $mact_mod);
			$dojob($fp, $id, $params);
		}
		Events::SendEvent('Core', 'PostRequest'); //needed (pre-exit) ?
		exit; //TODO other things to process?
}

Events::SendEvent('Core', 'PostRequest');
