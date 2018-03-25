<?php
#module-action-request processing for CMSMS
#Copyright (C) 2004-2014 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2015-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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
$CMS_MODULE_PAGE=1;

$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);
$starttime = microtime();

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();
$userid = get_userid();

$id = 'm1_';
$module = '';
$action = 'defaultadmin';
//UNUSED $suppressOutput = false;
if (isset($_REQUEST['mact'])) {
    $mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
    $ary = explode(',', $mact, 4);
    $module = $ary[0] ?? '';
    $id = $ary[1] ?? 'm1_';
    $action = $ary[2] ?? '';
}

$modinst = ModuleOperations::get_instance()->get_module_instance($module);
if( !$modinst ) {
    trigger_error('Module '.$module.' not found in memory. This could indicate that the module is in need of upgrade or that there are other problems');
    redirect('index.php?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);
}

if( isset($_REQUEST['showtemplate']) && ($_REQUEST['showtemplate'] == 'false')) {
    // for simplicity and compatibility with the frontend.
    $USE_THEME = false;
} elseif( $modinst->SuppressAdminOutput($_REQUEST) || isset($_REQUEST['suppressoutput']) ) {
    $USE_THEME = false;
} else {
    $USE_THEME = true;
}

$smarty = CMSMS\internal\Smarty::get_instance();

$params = ModuleOperations::get_instance()->GetModuleParameters($id);
$content = null;
if ($USE_THEME) {
    $themeObject = cms_utils::get_theme_object();
    $themeObject->set_action_module($module);

    $txt = $modinst->GetHeaderHTML($action);
    if ($txt) {
        $themeObject->add_headtext($txt);
    }
	if ($modinst->HasAdmin()) {
	    $txt = $modinst->AdminStyle();
		if ($txt) {
            $themeObject->add_headtext($txt);
		}
	}

    include_once 'header.php';
    // module output
    echo $modinst->DoActionBase($action, $id, $params, null, $smarty);

    if (!empty($params['module_error'])) $themeObject->RecordMessage('error', $params['module_error']);
    if (!empty($params['module_message'])) $themeObject->RecordMessage('success', $params['module_message']);

    include_once 'footer.php';
} else {
    // no theme output.
    echo $modinst->DoActionBase($action, $id, $params, null, $smarty);
}

//FUTURE USE \CMSMS\HookManager::do_hook('PostRequest');
