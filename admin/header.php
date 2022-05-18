<?php
/*
Shared stage of admin-page-top display (used e.g. after an action is run)
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you may redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\Lone;
use CMSMS\StylesheetOperations;
use CMSMS\UserParams;
use CMSMS\Utils;
use function CMSMS\log_error;
use function CMSMS\sendheaders;

// variables needed here and in-scope for hook-functions
if (!AppState::test(AppState::LOGIN_PAGE)) {
	if (!isset($userid)) {
		$userid = get_userid();
	}
}
if (!isset($themeObject)) {
	$themeObject = Lone::get('Theme');
}

if (!isset($smarty)) {
	$smarty = Lone::get('Smarty');
}
if (!isset($config)) {
	$config = Lone::get('Config');
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
				add_page_headtext($list, false); //prepend For theme css after non-theme css, themes must self-manage their styles
			}
		}
	}
}

if (isset($mact_mod)) {
	// we're running via moduleinterface script
	if ($mact_mod->HasAdmin()) {
		$txt = $mact_mod->AdminStyle();
		if ($txt) {
			add_page_headtext('<style>'.PHP_EOL.$txt.PHP_EOL.'</style>'.PHP_EOL, false);
		}
	}
	$txt = $mact_mod->GetHeaderHTML();
	if ($txt) {
		add_page_headtext($txt);
	}
}

// define js runtime variables, including $vars[] set here, if any
require_once __DIR__.DIRECTORY_SEPARATOR.'jsruntime.php';
add_page_headtext($js, false); // prepend (might be needed anywhere during page construction)
// TODO prepend any CSP header here, before 1st on-page javascript
//e.g. add_page_headtext(<<<EOS
//<meta http-equiv="Content-Security-Policy" content="script-src 'self' 'sha256-{$hash}' 'unsafe-inline' ">
//EOS
//, false); // prepend (might be needed anywhere during page construction)
//$txt = 'TODO';
//add_page_headtext($txt, false);

/*
Setup for required page-content (aka rich-text) editors
This must be performed after page-content generation which creates such
textarea(s) (i.e. also after template-fetching if the template includes
textarea-tag(s))
*/
$list = FormUtils::get_requested_wysiwyg_modules();
if ($list) {
	$n = 10;
	foreach ($list as $modname => $info) {
		$mod = Utils::get_module($modname);
		if (!is_object($mod)) {
			log_error('Content editor module could not be instantiated', $modname);
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
					if (!in_array($name, $tmpnames)) {
						$tmpnames[] = $name;
					}
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
			$cssname = $rec['stylesheet'] ?? null;

			if ($cssname == FormUtils::NONE) {
				$cssname = null;
			}
			if (!$cssname || !is_array($cssnames) || !in_array($cssname, $cssnames) || $selector == FormUtils::NONE) {
				$need_generic = true;
				continue;
			}

			$selector = '#'.$selector;
			$params = [
				'htmlclass' => $rec['class'] ?? '',
				'htmlid' => $rec['id'] ?? '',
				'workid' => 'edit_work'.$n,
				'edit' => true,
				'handle' => 'editor'.$n,
				'stylesheet' => $cssname
			];

			try {
				$out = $mod->WYSIWYGGenerateHeader($selector, $cssname, $params);
				// module may do direct-header/footer injection, in which case nothing returned here
				if ($out) {
					add_page_headtext($out);
				}
			} catch (Throwable $t) {
				log_error("'$modname' content editor error'", $t->getMessage());
			}
			$n++;
		}
		// do we need a generic textarea ?
		if ($need_generic) {
			$params = [
				'workid' => 'edit_work'.$n,
				'edit' => true,
				'handle' => 'editor'.$n,
			];
			try {
				$out = $mod->WYSIWYGGenerateHeader('', '', $params); // default selector, no styling
				if ($out) {
					add_page_headtext($out);
				}
			} catch (Throwable $t) {
				log_error("'$modname' content editor error'", $t->getMessage());
			}
		}
		$n++;
	}
}

/*
Setup for required syntax-highlight editors
See comment above about when this must be performed
*/
$list = FormUtils::get_requested_syntax_modules();
if ($list) {
	$n = 100;
	foreach ($list as $modname => $info) {
		$mod = Utils::get_module($modname);
		if (is_object($mod)) {
			$rec = reset($info);
			$params = [
				'htmlclass' => $rec['class'] ?? '',
				'htmlid' => $rec['id'] ?? '',
				'workid' => 'edit_work'.$n,
				'edit' => true,
				'handle' => 'editor'.$n,
				'typer' => $rec['wantedsyntax'] ?? '',
//				'theme' => '',
			];
			try {
				$out = $mod->SyntaxGenerateHeader($params);
				// module may do direct-header/footer injection, in which case nothing returned here
				if ($out) {
					add_page_headtext($out);
				}
				$n++;
			} catch (Throwable $t) {
				log_error("'$modname' syntax editor error'", $t->getMessage());
			}
		} else {
			log_error('Syntax editor module could not be instantiated', $modname);
		}
	}
}

if (cmsms()->JOBTYPE == 0) {
	sendheaders();
}

if (isset($config['show_performance_info'])) {
	$starttime = microtime();
}
//if (!isset($USE_OUTPUT_BUFFERING) || $USE_OUTPUT_BUFFERING) { undocumented, unused
	@ob_start();
//}

if (!isset($USE_THEME) || $USE_THEME) {
	if (!AppState::test(AppState::LOGIN_PAGE)) {
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);

		$notify = UserParams::get_for_user($userid, 'enablenotifications', 1);
		// display notification stuff from modules
		// TODO this should be controlled by $notify
		$ignoredmodules = explode(',', UserParams::get_for_user($userid, 'ignoredmodules'));

		if( $notify && AppParams::get('enablenotifications', 1) ) {
			// display a sitedown warning
			$sitedown_file = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'SITEDOWN';
			if (is_file($sitedown_file)) {
				$sitedown_message = _la('sitedownwarning', $sitedown_file);
				$themeObject->RecordNotice('warn', $sitedown_message);
			}
		}
	}

	$themeObject->do_header();
//} else {
//    echo '<!-- admin theme disabled -->';
}
