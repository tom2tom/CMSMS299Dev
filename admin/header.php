<?php
#Initial shared stage of admin-page display
#Copyright (C) 2004-2014 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2015-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

// variables for general use
if (empty($CMS_LOGIN_PAGE)) {
	$userid = get_userid(); //also checks login status
}
if (!isset($themeObject)) {
	$themeObject = cms_utils::get_theme_object();
}
if (!isset($smarty)) {
	$smarty = CMSMS\internal\Smarty::get_instance();
}
$config = cms_config::get_instance();

list($vars,$add_list) = \CMSMS\HookManager::do_hook('AdminHeaderSetup', [], []);
if ($add_list) {
    $themeObject->add_headtext(implode("\n",$add_list));
}
//NOTE downstream must ensure var keys and values are formatted for js
if ($vars) {
    $out = <<<EOT
<script type="text/javascript">
//<![CDATA[

EOT;
   foreach ($vars as $key => $value) {
       $out .= "cms_data.{$key} = {$value};\n";
   }
   $out .= <<<EOT
//]]>
</script>

EOT;
    $themeObject->add_headtext($out);
}

if (isset($modinst)) {
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
}

cms_admin_sendheaders(); //TODO is this $CMS_JOB_TYPE-related ?

if (isset($config['show_performance_info'])) {
    $starttime = microtime();
}
if (!isset($USE_OUTPUT_BUFFERING) || $USE_OUTPUT_BUFFERING) {
    @ob_start();
}

if (!isset($USE_THEME) || $USE_THEME) {
	if (empty($CMS_LOGIN_PAGE)) {
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);

		// Display notification stuff from modules
		// should be controlled by preferences or something
		$ignoredmodules = explode(',',cms_userprefs::get_for_user($userid,'ignoredmodules'));
		if( cms_siteprefs::get('enablenotifications',1) && cms_userprefs::get_for_user($userid,'enablenotifications',1) ) {
			// Display a warning sitedownwarning
			$sitedown_file = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'SITEDOWN';
			$sitedown_message = lang('sitedownwarning', $sitedown_file);
			if (file_exists($sitedown_file)) $themeObject->AddNotification(1,'Core',$sitedown_message);
		}
	}

    $themeObject->do_header();
//} else {
//    echo '<!-- admin theme disabled -->';
}
