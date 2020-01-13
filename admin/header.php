<?php
#Shared stage of admin-page-top display (used after action is run)
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

use CMSMS\AppState;
use CMSMS\FormUtils;
use CMSMS\HookManager;
use CMSMS\StylesheetOperations;

// variables for general use
if (!AppState::test_state(AppState::STATE_LOGIN_PAGE)) {
	$userid = get_userid(); //also checks login status
}
if (!isset($themeObject)) {
	$themeObject = cms_utils::get_theme_object();
}
if (!isset($smarty)) {
	$smarty = CmsApp::get_instance()->GetSmarty();
}
$config = cms_config::get_instance();

$aout = HookManager::do_hook_accumulate('AdminHeaderSetup');
if ($aout) {
	$out = '';
	foreach($aout as $bundle) {
		if ($bundle[0]) {
			//NOTE downstream must ensure var keys and values are formatted for js
			foreach($bundle[0] as $key => $value) {
				$out .= "cms_data.{$key} = {$value};\n";
			}
		}

		if ($bundle[1]) {
			foreach($bundle[1] as $list) {
				add_page_headtext($list);
			}
		}
	}

	if ($out) {
		add_page_headtext(<<<EOT
<script type="text/javascript">
//<![CDATA[

EOT
		);
		add_page_headtext($out);
		add_page_headtext(<<<EOT
//]]>
</script>

EOT
		);
	}
}

if (isset($modinst)) {
	if ($modinst->HasAdmin()) {
		$txt = $modinst->AdminStyle();
		if ($txt) {
			add_page_headtext($txt, false);
		}
	}
	$txt = $modinst->GetHeaderHTML($action);
	if ($txt) {
		add_page_headtext($txt);
	}
}

// setup for required rich-text-editors
// (must be after action/content generation, which might create such textarea(s))
$list = FormUtils::get_requested_wysiwyg_modules();
if ($list) {
	foreach ($list as $module_name => $info) {
		$obj = cms_utils::get_module($module_name);
		if (!is_object($obj)) {
			audit('','Core','rich-edit module '.$module_name.' requested, but could not be instantiated');
			continue;
		}

		$cssnames = [];
		foreach ($info as $rec) {
			if (!($rec['stylesheet'] == '' || $rec['stylesheet'] == FormUtils::NONE)) {
				$cssnames[] = $rec['stylesheet'];
			}
		}
		$cssnames = array_unique($cssnames);
		if ($cssnames) {
			$cssobs = StylesheetOperations::get_bulk_stylesheets($cssnames); //TODO not cached, use something lighter
			// adjust the cssnames array to contain only the stylesheets we actually found
			if ($cssobs) {
				$tmpnames = [];
				foreach ($cssobs as $stylesheet) {
					$name = $stylesheet->get_name();
					if (!in_array($name,$tmpnames)) $tmpnames[] = $name;
				}
				$cssnames = $tmpnames;
			} else {
				$cssnames = [];
			}
		}

		// initialize each 'specialized' textarea
		$need_generic = false;
		foreach ($info as $rec) {
			$selector = $rec['id'];
			$cssname = $rec['stylesheet'];

			if ($cssname == FormUtils::NONE) $cssname = null;
			if (!$cssname || !is_array($cssnames) || !in_array($cssname,$cssnames) || $selector == FormUtils::NONE) {
				$need_generic = true;
				continue;
			}

			$selector = 'textarea#'.$selector;
			try {
				$out = $obj->WYSIWYGGenerateHeader($selector,$cssname); //deprecated API
				if ($out) { add_page_headtext($out); }
			} catch (Exception $e) {}
		}
		// do we need a generic textarea ?
		if ($need_generic) {
			try {
				$out = $obj->WYSIWYGGenerateHeader(); //deprecated API
				if ($out) { add_page_headtext($out); }
			} catch (Exception $e) {}
		}
	}
}

// setup for required syntax hilighters
$list = FormUtils::get_requested_syntax_modules();
if ($list) {
	foreach ($list as $one) {
		$obj = cms_utils::get_module($one);
		if (is_object($obj)) {
			try {
				$out = $obj->SyntaxGenerateHeader(); //deprecated API
				if ($out) { add_page_headtext($out); }
			} catch (Exception $e) {}
		}
	}
}

if (CmsApp::get_instance()->JOBTYPE == 0) {
	cms_admin_sendheaders(); //TODO only for $CMS_JOB_TYPE < 1 ?
}

if (isset($config['show_performance_info'])) {
	$starttime = microtime();
}
if (!isset($USE_OUTPUT_BUFFERING) || $USE_OUTPUT_BUFFERING) {
	@ob_start();
}

if (!isset($USE_THEME) || $USE_THEME) {
	if (!AppState::test_state(AppState::STATE_LOGIN_PAGE)) {
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);

		// Display notification stuff from modules
		// should be controlled by preferences or something
		$ignoredmodules = explode(',',cms_userprefs::get_for_user($userid,'ignoredmodules'));
		if( cms_siteprefs::get('enablenotifications',1) && cms_userprefs::get_for_user($userid,'enablenotifications',1) ) {
			// Display a warning sitedownwarning
			$sitedown_file = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'SITEDOWN';
			if (is_file($sitedown_file)) {
				$sitedown_message = lang('sitedownwarning', $sitedown_file);
				$themeObject->RecordNotice('warn', $sitedown_message);
			}
		}
	}

	$themeObject->do_header();
//} else {
//    echo '<!-- admin theme disabled -->';
}
