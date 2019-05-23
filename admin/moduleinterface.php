<?php
#module-action request-processing for CMSMS
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

use CMSMS\HookManager;
use CMSMS\ModuleOperations;

//REMINDER: vars defined here might be used as globals by downstream hook functions

$CMS_ADMIN_PAGE = 1;
//$CMS_MODULE_PAGE = 1;

$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);
$starttime = microtime();

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if ($CMS_JOB_TYPE < 2) {
	check_login();
//  $userid = get_userid();
} else {
	//TODO security for async ? $params[CMS_SECURE_PARAM_NAME] == $_SESSION[CMS_USER_KEY] etc
}

if (isset($_REQUEST['mact'])) {
	$mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
	$ary = explode(',', $mact, 4);
	$module = $ary[0] ?? '';
	$id = $ary[1] ?? 'm1_';
	$action = $ary[2] ?? '';
} else {
	redirect('index.php');
}

$modops = new ModuleOperations();
$modinst = $modops->get_module_instance($module);
if (!$modinst) {
	trigger_error('Module '.$module.' not found. This could indicate that the module is awaiting upgrade, or that there are other problems');
	redirect('index.php');
}
if ($modinst->SuppressAdminOutput($_REQUEST)) {
	if ($CMS_JOB_TYPE == 0) {
		$CMS_JOB_TYPE = 1; //too bad about irrelevant includes
	}
}

$params = $modops->GetModuleParameters($id);

switch ($CMS_JOB_TYPE) {
	case 0:
		$themeObject = cms_utils::get_theme_object();
		$themeObject->set_action_module($module);
		// create a dummy template to be a proxy-parent for the action
		$smarty = CmsApp::get_instance()->GetSmarty();
        $template = $smarty->createTemplate('string:DUMMY PARENT');

		// retrieve and park the action-output first, in case the action also generates header content
		ob_start();
		echo $modinst->DoActionBase($action, $id, $params, null, $template);
		$content = ob_get_contents();
		ob_end_clean();

		include_once 'header.php';
		// back into the buffer,  now that 'pre-content' things are in place
		echo $content;

		if (!empty($params['module_error'])) $themeObject->RecordNotice('error', $params['module_error']);
		if (!empty($params['module_message'])) $themeObject->RecordNotice('success', $params['module_message']);

		include_once 'footer.php';
		break;
	case 1: // not full-page output
		$smarty =  CmsApp::get_instance()->GetSmarty();
        $template = $smarty->createTemplate('string:DUMMY PARENT');
		echo $modinst->DoActionBase($action, $id, $params, null, $template);
		break;
	case 2:	//minimal
		$fp = $modinst->GetModulePath().DIRECTORY_SEPARATOR.'action.'.$action.'.php';
		if (is_file($fp)) {
			$dojob = Closure::bind(function($filepath, $id, $params)
			{
				// variables in scope for convenience
				$gCms = CmsApp::get_instance();
				$db = $gCms->GetDb();
				$config = $gCms->GetConfig();
				include $filepath;
			}, $modinst, $modinst);
			$dojob($fp, $id, $params);
		}
}

HookManager::do_hook_simple('PostRequest');
