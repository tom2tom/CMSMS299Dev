<?php
/*
Ghostgum - an admin theme for CMS Made Simple
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Tom Phane and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\LangOperations;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\RequestParameters;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\StylesMerger;
use CMSMS\UserParams;
use CMSMS\Utils;
use const CMS_ADMIN_PATH;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function check_permission;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_path_to_url;
use function CMSMS\get_page_foottext;
use function CMSMS\get_page_headtext;
use function get_userid;
use function lang;
use function munge_string_to_url;

class GhostgumTheme extends AdminTheme
{
	/**
	 * For theme exporting/importing
	 * @ignore
	 */
	const THEME_VERSION = '0.8';

	/**
	 * @ignore
	 */
	private $_havetree = null;

	/**
	 * Hook accumulator-function to nominate runtime 'resources' to be
	 * included in the header of each displayed admin page
	 *
	 * @return 2-member array (not typed to support back-compatible themes)
	 * [0] = array of data for js vars, members like varname=>varvalue
	 * [1] = array of string(s) for includables
	 */
	public function AdminHeaderSetup()
	{
		list($vars, $add_list) = parent::AdminHeaderSetup();

		$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
		$rel_url = strtr($rel, DIRECTORY_SEPARATOR, '/');
		$fn = 'style';
		if (NlsOperations::get_language_direction() == 'rtl') {
			if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
		$incs = cms_installed_jquery(true, true, true, true);

		$csm = new StylesMerger();
		$csm->queue_matchedfile('normalize.css', 1);
		$csm->queue_file($incs['jquicss'], 2);
//		$csm->queue_matchedfile('flex-grid-lite.css', 2);
		$csm->queue_matchedfile('grid-960.css', 2); // deprecated since 2.99
		$out = $csm->page_content('', false, true);
		$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$rel_url}/css/{$fn}.css" />

EOS;
		//DEBUG
		$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$rel_url}/css/superfishnav.css" />

EOS;
		if (is_file(__DIR__.DIRECTORY_SEPARATOR.'extcss'.DIRECTORY_SEPARATOR.$fn.'.css')) {
			$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$rel_url}/extcss/{$fn}.css" />

EOS;
		}

		$jsm = new ScriptsMerger();
		$jsm->queue_file($incs['jqcore'], 1);
//		if (CMS_DEBUG) {
		$jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this or keep if (CMS_DEBUG)
//		}
		$jsm->queue_file($incs['jqui'], 1);
		$jsm->queue_matchedfile('jquery.cmsms_admin.js', 2);
		$out .= $jsm->page_content();
		$jsm->reset(); // start another merger-file
		$jsm->queue_matchedfile('jquery.ui.touch-punch.js', 1);
		$jsm->queue_matchedfile('jquery.toast.js', 1);
		$jsm->queue_matchedfile('jquery.basictable.js', 1); //TESTER
		$p = __DIR__.DIRECTORY_SEPARATOR.'js';
		$jsm->queue_matchedfile('jquery.alertable.js', 2, $p);
		$jsm->queue_matchedfile('standard.js', 3, $p);
		$out .= $jsm->page_content('', false, true);

		$add_list[] = $out;
//		$vars[] = anything needed ?;
		return [$vars, $add_list];
	}

	public function display_login_page()
	{
		$auth_module = AppParams::get('loginmodule', ModuleOperations::STD_LOGIN_MODULE);
		$mod = SingleItem::ModuleOperations()->get_module_instance($auth_module, '', true);
		if ($mod) {
			$data = $mod->fetch_login_panel();
		} else {
			die('System error');
		}

		$smarty = SingleItem::Smarty();
		$smarty->assign($data);

		//extra shared parameters for the form TODO get from the current login-module
		$config = SingleItem::Config(); // for the inclusion
		$fp = cms_join_path(dirname(__DIR__), 'assets', 'function.extraparms.php');
		require_once $fp;
		$smarty->assign($tplvars);

//TODO  ensure $smarty->assign('lang_code', AppParams::get('frontendlang'));
/* N/A
		//extra theme-specific parameters for the form
		$fp = __DIR__ . DIRECTORY_SEPARATOR . 'function.extraparms.php';
		if (is_file($fp)) {
			require_once $fp;
			if (!empty($tplvars)) {
				$smarty->assign($tplvars);
			}
		}
*/
		$fn = 'style';
		if (NlsOperations::get_language_direction() == 'rtl') {
			if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
		$out = <<<EOS
 <link rel="stylesheet" type="text/css" href="themes/Ghostgum/css/{$fn}.css" />

EOS;
//		get_csp_token(); //setup CSP header (result not used)
		$tpl = ' <script type="text/javascript" src="%s"></script>'.PHP_EOL;
		// scripts: jquery, jquery-ui
		$incs = cms_installed_jquery(true, false, true, false);
		$url = cms_path_to_url($incs['jqcore']);
		$out .= sprintf($tpl, $url);
		$url = cms_path_to_url($incs['jqui']);
		$out .= sprintf($tpl, $url);
		$url = 'themes/Ghostgum/js/login.min.js'; // TODO cms_get_...()
		$out .= sprintf($tpl, $url);

		$smarty->assign('header_includes', $out) //NOT into bottom (to avoid UI-flash)
		  ->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates')
		  ->display('login.tpl');
	}

	/**
	 * @param mixed $section_name nav-menu-section name (string), but
	 *  usually null to use the whole menu
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_menu_page($section_name)
	{
		$smarty = SingleItem::Smarty();
		if ($section_name) {
//			$smarty->assign('section_name', $section_name);
			$nodes = $this->get_navigation_tree($section_name, 0);
		} else {
			$nodes = $this->get_navigation_tree(null, 3, 'root:view:dashboard');
		}
//		$this->_havetree = $nodes; //block further tree-data changes
		$smarty->assign('nodes', $nodes)
		  ->assign('pagetitle', $this->title) //not used in current template
		  ->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);

		$config = SingleItem::Config();
		$smarty->assign('admin_url', $config['admin_url'])
		  ->assign('theme', $this);

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		return $smarty->fetch('topcontent.tpl');
	}

	/**
	 * @param string $html page content to be processed
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_page($html)
	{
		$smarty = SingleItem::Smarty();
		$userid = get_userid(false);

		// setup titles etc
//		$tree =
			$this->get_navigation_tree(); //TODO if section

		/* possibly-cached value-names
		'pagetitle'
		'extra_lang_params'
		'module_help_type'
		'module_help_url'
		'pageicon'
		'page_crumbs'
		*/
		// prefer cached parameters, if any
		// module name
		$module_name = $this->get_value('module_name');
		if (!$module_name) {
			$module_name = RequestParameters::get_request_values('module'); // maybe null
		}
		$smarty->assign('module_name', $module_name);

		$module_help_type = $this->get_value('module_help_type');
		// module_help_url
		if ($module_name && ($module_help_type || $module_help_type === null) &&
			!UserParams::get_for_user($userid, 'hide_help_links', 0)) {
			if (($module_help_url = $this->get_value('module_help_url'))) {
				$smarty->assign('module_help_url', $module_help_url);
			}
		}

		// page title
		$alias = $title = $this->get_value('pagetitle');
		$subtitle = '';
		if ($title && !$module_help_type) {
			// if not doing module help, maybe translate the string
			if (LangOperations::lang_key_exists('admin', $title)) {
				$extra = $this->get_value('extra_lang_params');
				if (!$extra) {
					$extra = [];
				}
				$title = lang($title, $extra);
			}
//			$subtitle = TODO
		} else {
			$title = $this->get_active_title(); // try for the active-menu-item title
			if ($title) {
				$subtitle = $this->subtitle;
			} elseif ($module_name) {
				$mod = Utils::get_module($module_name);
				$title = $mod->GetFriendlyName();
				$subtitle = $mod->GetAdminDescription();
/*			} else {
				// no title, get one from the breadcrumbs.
				$bc = $this->get_breadcrumbs();
				if ($bc) {
					$title = $bc[count($bc) - 1]['title'];
				}
*/
			}
		}
		if (!$title) $title = '';
		$smarty->assign('pagetitle', $title)
		  ->assign('subtitle', $subtitle)
		  ->assign('pagealias', munge_string_to_url($alias)); // page alias

		// icon
		if ($module_name && ($icon_url = $this->get_value('module_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="'.$module_name.'" class="module-icon" />';
		} elseif ($module_name && $title) {
			$tag = $this->get_module_icon($module_name, ['alt'=>$module_name, 'class'=>'module-icon']);
		} elseif (($icon_url = $this->get_value('page_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="TODO" class="TODO" />';
		} else {
			$tag = ''; //TODO get icon for admin operation
		}
		$smarty->assign('pageicon', $tag);

		$config = SingleItem::Config();
		// site logo
		$sitelogo = AppParams::get('site_logo');
		if ($sitelogo) {
			if (!preg_match('~^\w*:?//~', $sitelogo)) {
				$sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
			}
			$smarty->assign('sitelogo', $sitelogo);
		}

		// custom support-URL?
		$url = AppParams::get('site_help_url');
		if ($url) {
			$smarty->assign('site_help_url', $url);
		}

		// preferences UI
		if (check_permission($userid,'Manage My Settings')) {
			$smarty->assign('mysettings', 1)
			  ->assign('myaccount', 1); //TODO maybe a separate check
		}

		// bookmarks UI
		if (UserParams::get_for_user($userid, 'bookmarks') && check_permission($userid,'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks();
			$smarty->assign('marks', $marks);
		}

		$smarty->assign('header_includes', get_page_headtext())
		  ->assign('bottom_includes', get_page_foottext())
		// other variables
		//strip inappropriate closers cuz we're putting it in the middle somewhere
		  ->assign('content', str_replace('</body></html>', '', $html))

		  ->assign('admin_url', $config['admin_url'])
		  ->assign('assets_url', $config['admin_url'] . '/themes/assets')
		  ->assign('theme', $this);
		// navigation menu data
		if (!$this->_havetree) {
			$smarty->assign('nav', $this->get_navigation_tree());
		} else {
			$smarty->assign('nav', $this->_havetree);
		}
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$user = SingleItem::UserOperations()->LoadUserByID($userid);
		$smarty->assign('username', $user->username);
		// selected language
		$lang = UserParams::get_for_user($userid, 'default_cms_language');
		if (!$lang) $lang = AppParams::get('frontendlang');
		$smarty->assign('lang_code', $lang)
		  ->assign('lang_dir', NlsOperations::get_language_direction()); // language direction

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		return $smarty->fetch('pagetemplate.tpl');
	}

	public function DisplayImage($image, $alt = '', $width = '', $height = '', $class = null, $attrs = [])
	{
		//[.../]icons/system/* are processed here, custom handling is needed for in-sprite svg's
		if (strpos($image, 'system') === false) {
			return parent::DisplayImage($image, $alt, $width, $height, $class, $attrs);
		}
		$n = basename(dirname($image));
		if ($n != 'system') {
			return parent::DisplayImage($image, $alt, $width, $height, $class, $attrs);
		}
		if (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$image)) { //absolute
			if (!startwith($image, __DIR__)) {
				return parent::DisplayImage($image, $alt, $width, $height, $class, $attrs);
			}
		}
		$n = trim(basename($image));
		$p = strrpos($n, '.');
		if ($p !== false) {
			$type = substr($n, 0, $p);
		} else {
			$type = $n;
		}

		unset($attrs['width'], $attrs['height']);
		$extras = array_merge(['class'=>$class, 'alt'=>$alt, 'title'=>''], $attrs);
		if (!$extras['alt']) {
			if ($extras['title']) {
				$extras['alt'] = $extras['title'];
			} else {
				$extras['alt'] = $type;
			}
		}

		$res = '<svg';
		foreach ($extras as $key => $value) {
			if ($value !== '' && $key != 'title') {
				$res .= " $key=\"$value\"";
			}
		}
		$res .= ">\n";
		if ($extras['title']) $res .= "<title>{$extras['title']}</title>\n";
		$res .= "<use xlink:href=\"themes/Ghostgum/images/icons/system/sprite.svg#{$type}\"/>\n</svg>";
		return $res;
	}
}
