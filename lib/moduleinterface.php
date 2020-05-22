<?php
#module-action request-processing for CMSMS
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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\internal\GetParameters;
use CMSMS\ModuleOperations;
use CMSMS\Utils;

//REMINDER: vars defined here might be used as globals by downstream hook functions
$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);
$starttime = microtime();

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

/*
TODO CIRCULAR WITH include.php
$ops = new GetParameters();
$params = $ops->decode_action_params();
$CMS_JOB_TYPE = $params[CMS_JOB_KEY] ?? 0
CmsApp::get_instance()->JOBTYPE = $CMS_JOB_TYPE;

BACK HERE ...
if ($CMS_JOB_TYPE < 2) {
	check_login();
}
*/

$ops = new GetParameters();
$params = $ops->decode_action_params();

if ($params) {
	$modops = ModuleOperations::get_instance();
	$module = $params['module'] ?? '';
	$modinst = $modops->get_module_instance($module);
	if (!$modinst) {
		trigger_error('Module '.$module.' not found. This could indicate that the module is awaiting upgrade, or that there are other problems');
		if ($CMS_JOB_TYPE == 0) {
			redirect('menu.php'); //TODO CMS_ROOT_URL | 'menu.php'
		} else {
			exit;
		}
	}
//	$params['id'] =
	$id = ($params['id'] ?? 'm1_');
//	$params['action'] =
	$action = ($params['action'] ?? '');
	unset($params['id'], $params['action']);
	unset($params['module'], $params['inline']);
	$params += $ops->retrieve_general_params($id);
} else {
	trigger_error('Module-action parameters not found');
	if ($CMS_JOB_TYPE == 0) {
		redirect('menu.php'); //TODO CMS_ROOT_URL | 'menu.php'
	} else {
		exit;
	}
}

if ($modinst->SuppressAdminOutput($_REQUEST)) {
	if ($CMS_JOB_TYPE == 0) {
		$CMS_JOB_TYPE = 1; //too bad about irrelevant includes
	}
}

switch ($CMS_JOB_TYPE) {
	case 0:
		$themeObject = Utils::get_theme_object();
		$themeObject->set_action_module($module);
		AppSingle::insert('Theme', $themeObject);
		$base = AppSingle::Config()['admin_path'].DIRECTORY_SEPARATOR;
		// create a dummy template to be a proxy-parent for the action's template
//		$smarty = AppSingle::Smarty();
//		$template = $smarty->createTemplate('string:DUMMY PARENT');
		$template = null;
		// retrieve and park the action-output first, in case the action also generates header content
		$content = $modinst->DoActionBase($action, $id, $params, null, $template);

		require $base.'header.php';
		// back into the buffer,  now that 'pre-content' things are in place
		echo $content;

		if (!empty($params['module_error'])) $themeObject->RecordNotice('error', $params['module_error']);
		if (!empty($params['module_message'])) $themeObject->RecordNotice('success', $params['module_message']);

		require $base.'footer.php';
		break;
	case 1: // not full-page output
		$themeObject = Utils::get_theme_object();
		$themeObject->set_action_module($module);
		AppSingle::insert('Theme', $themeObject);
//		$smarty =  AppSingle::Smarty();
//		$template = $smarty->createTemplate('string:DUMMY PARENT');
		$template = null;
		echo $modinst->DoActionBase($action, $id, $params, null, $template);
		break;
	case 2:	//minimal
		$fp = $modinst->GetModulePath().DIRECTORY_SEPARATOR.'action.'.$action.'.php';
		if (is_file($fp)) {
			$dojob = Closure::bind(function($filepath, $id, $params)
			{
				// variables in scope for convenience c.f. CMSModeule::DoAction()
				$gCms = CmsApp::get_instance();
				$db = $gCms->GetDb();
				$config = $gCms->GetConfig();
				//no $smarty (no template-processing)
				$uuid = $gCms->GetSiteUUID(); //since 2.9
				include $filepath;
			}, $modinst, $modinst);
			$dojob($fp, $id, $params);
		}
		Events::SendEvent('Core', 'PostRequest');
		exit;
}

Events::SendEvent('Core', 'PostRequest');
