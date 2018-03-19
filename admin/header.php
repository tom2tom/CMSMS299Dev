<?php
#Initial shared stage of admin-page display
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

$userid = get_userid(); //also checks login status
debug_buffer('before theme load');
$themeObject = cms_utils::get_theme_object();
debug_buffer('after theme load');
$smarty = CMSMS\internal\Smarty::get_instance();
$config = \cms_config::get_instance();

$out = \CMSMS\HookManager::do_hook_accumulate('admin_add_headtext');
if ($out) {
    foreach ($out as $one) {
        $one = trim($one);
        if ($one) $themeObject->add_headtext($one);
    }
}

cms_admin_sendheaders();

if (isset($config['show_performance_info'])) {
    $starttime = microtime();
}
if (!isset($USE_OUTPUT_BUFFERING) || $USE_OUTPUT_BUFFERING) {
    @ob_start();
}

if (!isset($USE_THEME) || $USE_THEME) {
    $smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);

    // Display notification stuff from modules
    // should be controlled by preferences or something
    $ignoredmodules = explode(',',cms_userprefs::get_for_user($userid,'ignoredmodules'));
    if( cms_siteprefs::get('enablenotifications',1) && cms_userprefs::get_for_user($userid,'enablenotifications',1) ) {
        // Display a warning sitedownwarning
        $sitedown_message = lang('sitedownwarning', TMP_CACHE_LOCATION . '/SITEDOWN');
        $sitedown_file = TMP_CACHE_LOCATION . '/SITEDOWN';
        if (file_exists($sitedown_file)) $themeObject->AddNotification(1,'Core',$sitedown_message);
    }

    $themeObject->do_header();
//} else {
    //echo '<!-- admin theme disabled -->';
}
