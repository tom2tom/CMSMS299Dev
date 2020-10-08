<?php
#Shared stage of admin-page-top display (used after action is run)
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

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\StylesheetOperations;
use CMSMS\UserParams;
use CMSMS\Utils;

// variables needed here and in-scope for hook-functions
if (!AppState::test_state(AppState::STATE_LOGIN_PAGE)) {
	if (!isset($userid)) {
		$userid = get_userid(); //also checks login status
	}
}
if (!isset($themeObject)) {
	$themeObject = Utils::get_theme_object();
}

if (!isset($smarty)) {
	$smarty = AppSingle::Smarty();
}
if (!isset($config)) {
	$config = AppSingle::Config();
}

$aout = HookOperations::do_hook_accumulate('AdminHeaderSetup');
if ($aout) {
	$vars = [];
	foreach($aout as $bundle) {
		if ($bundle[0]) {
			// NOTE all variables' keys and values will be json-encoded before dispatch to browser
			$vars = array_merge($vars, $bundle[0]);
		}

		if ($bundle[1]) {
			foreach($bundle[1] as $list) {
				add_page_headtext($list);
			}
		}
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

// define js runtime variables, including $vars[] set here, if any
require_once __DIR__.DIRECTORY_SEPARATOR.'jsruntime.php';
add_page_headtext($js, false); // prepend (might be needed anywhere during page construction)

// setup for required page-content (aka rich-text) editors
// this must be performed after page-content generation which creates
// such textarea(s) (i.e. also after template-fetching if the template
// includes textarea-tag(s))
$list = FormUtils::get_requested_wysiwyg_modules();
if ($list) {
	foreach ($list as $module_name => $info) {
		$obj = Utils::get_module($module_name);
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
			} catch (Throwable $e) {}
		}
		// do we need a generic textarea ?
		if ($need_generic) {
			try {
				$out = $obj->WYSIWYGGenerateHeader(); //deprecated API
				if ($out) { add_page_headtext($out); }
			} catch (Throwable $e) {}
		}
	}
}

// setup for required syntax-highlight editors
// see comment above about when this must be performed
$list = FormUtils::get_requested_syntax_modules();
if ($list) {
	foreach ($list as $one) {
		$obj = Utils::get_module($one);
		if (is_object($obj)) {
			try {
				$out = $obj->SyntaxGenerateHeader(); //deprecated API
				if ($out) { add_page_headtext($out); }
			} catch (Throwable $t) {}
		}
	}
}

if (AppSingle::App()->JOBTYPE == 0) {
	cms_admin_sendheaders();
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

		$notify = UserParams::get_for_user($userid,'enablenotifications',1);
		// display notification stuff from modules
		// TODO this should be controlled by $notify
		$ignoredmodules = explode(',',UserParams::get_for_user($userid,'ignoredmodules'));

		if( $notify && AppParams::get('enablenotifications',1) ) {
			// display a sitedown warning
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
