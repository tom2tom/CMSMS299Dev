<?php
#admin-request-start processing for CMSMS
#Copyright (C) 2004-2016 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2017-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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
#
#$Id$

$CMS_ADMIN_PAGE=1;
$CMS_MODULE_PAGE=1;

$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);
$starttime = microtime();

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();
$userid = get_userid();

$smarty = CMSMS\internal\Smarty::get_instance();

$Id$
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
} elseif( $modinst->SuppressAdminOutput($_REQUEST) != false || isset($_REQUEST['suppressoutput']) ) {
    $USE_THEME = false;
} else {
    $USE_THEME = true;
}

// module output
$params = ModuleOperations::get_instance()->GetModuleParameters($id);
$content = null;
if( $USE_THEME ) {
    $themeObject = cms_utils::get_theme_object();
    $themeObject->set_action_module($module);

    // get module output
    @ob_start();
    echo $modinst->DoActionBase($action, $id, $params, null, $smarty);
    $content = @ob_get_contents();
    @ob_end_clean();

    // deprecate this.
    $txt = $modinst->GetHeaderHTML($action);
    if( $txt ) $themeObject->add_headtext($txt);

    // call admin_add_headtext to get any admin data to add to the <head>
    $out = \CMSMS\HookManager::do_hook_accumulate('admin_add_headtext');
    if( $out ) {
        foreach( $out as $one ) {
            $one = trim($one);
            if( $one ) $themeObject->add_headtext($one);
        }
    }

    if ( !empty($params['module_message']) ) echo $themeObject->ShowMessage($params['module_message']);
    if ( !empty($params['module_error']) ) echo $themeObject->ShowErrors($params['module_error']);
    include_once 'header.php';

    // this is hackish
    echo '<div class="pagecontainer">';
    echo '<div class="pageoverflow">';
    $title = $themeObject->title;
    $module_help_type = 'both';
    if( $title ) $module_help_type = null;
    if( !$title ) $title = $themeObject->get_active_title();
    if( !$title ) $title = $modinst->GetFriendlyName();
    echo $themeObject->ShowHeader($title,[],'',$module_help_type).'</div>';
    echo $content;
    echo '</div>';
    include_once 'footer.php';
} else {
    // no theme output.
    echo $modinst->DoActionBase($action, $id, $params, '', $smarty);
}

//FUTURE USE \CMSMS\HookManager::do_hook('PostRequest');

