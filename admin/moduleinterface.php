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

//REMINDER: vars defined here might be used as globals by downstream hook functions

$CMS_ADMIN_PAGE=1;
$CMS_MODULE_PAGE=1;

$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);
$starttime = microtime();

if (isset($_REQUEST['cmsjobtype'])) {
    // for simplicity and compatibility with the frontend
    $type = (int)$_REQUEST['cmsjobtype'];
    $CMS_JOB_TYPE = min(max($type, 0), 2);
} elseif (
    // undocumented, deprecated, output-suppressor
    (isset($_REQUEST['showtemplate']) && $_REQUEST['showtemplate'] == 'false')
    || isset($_REQUEST['suppressoutput'])) {
    $CMS_JOB_TYPE = 1;
} else {
    //normal output
    $CMS_JOB_TYPE = 0;
}

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();
$userid = get_userid();

if (isset($_REQUEST['mact'])) {
    $mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
    $ary = explode(',', $mact, 4);
    $module = $ary[0] ?? '';
    $id = $ary[1] ?? 'm1_';
    $action = $ary[2] ?? '';
} else {
    $module = ''; // trigger error
}

$modops = ModuleOperations::get_instance();
$modinst = $modops->get_module_instance($module);
if (!$modinst) {
    trigger_error('Module '.$module.' not found in memory. This could indicate that the module is in need of upgrade or that there are other problems');
    redirect('index.php?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);
}
if ($modinst->SuppressAdminOutput($_REQUEST)) {
    $CMS_JOB_TYPE = 1; //too bad about irelevant includes
}

$params = $modops->GetModuleParameters($id);
$smarty = ($CMS_JOB_TYPE < 2) ? CMSMS\internal\Smarty::get_instance() : null;

if ($CMS_JOB_TYPE == 0) {
    $themeObject = cms_utils::get_theme_object();
    $themeObject->set_action_module($module);

    if ($modinst->HasAdmin()) {
        $txt = $modinst->AdminStyle();
        if ($txt) {
            $themeObject->add_headtext($txt);
        }
    }
    $txt = $modinst->GetHeaderHTML($action);
    if ($txt) {
        $themeObject->add_headtext($txt);
    }

    // retrieve and park the action output now, in case the action also generates header content
    ob_start();
    echo $modinst->DoActionBase($action, $id, $params, null, $smarty);
    $content = ob_get_contents();
    ob_end_clean();

    include_once 'header.php';
    // back into the buffer,  now that 'pre-content' things are in place
    echo $content;

    if (!empty($params['module_error'])) $themeObject->RecordNotice('error', $params['module_error']);
    if (!empty($params['module_message'])) $themeObject->RecordNotice('success', $params['module_message']);

    include_once 'footer.php';
} else {
    // 1 or 2 i.e. not full-page output
    echo $modinst->DoActionBase($action, $id, $params, null, $smarty);
}

//FUTURE USE \CMSMS\HookManager::do_hook('PostRequest');
