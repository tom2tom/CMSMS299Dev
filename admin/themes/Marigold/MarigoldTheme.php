<?php
/*
Marigold - an admin-console theme for CMS Made Simple
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AdminTheme;
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

class MarigoldTheme extends AdminTheme
{
	/**
	 * For theme exporting/importing
	 * @ignore
	 */
	const THEME_VERSION = '0.9';
	/**
	 + TODO variable for this e.g. better CDN
	 * e.g. 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'
	 *      'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'
	 * @ignore
	 */
	const AWESOME_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css';

	/**
	 * @ignore
	 */
	private $_havetree = null;

	/**
	 * Hook accumulator-function to nominate runtime resources, which will be
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
		$csm->queue_matchedfile('grid-960.css', 2); // deprecated since 2.99
		$out = $csm->page_content('', false, true);
		// can't merge these - they include relative URL's
		// TODO support .min versions if present
		$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$rel_url}/css/{$fn}.css" />

EOS;
		if (is_file(__DIR__.DIRECTORY_SEPARATOR.'extcss'.DIRECTORY_SEPARATOR.$fn.'.css')) {
			$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$rel_url}/extcss/{$fn}.css" />

EOS;
		}

		$jsm = new ScriptsMerger();
		$jsm->queue_file($incs['jqcore'], 1);
//		if (CMS_DEBUG) {
		$jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this ?
//		}
		$jsm->queue_file($incs['jqui'], 1);
		$jsm->queue_matchedfile('jquery.cmsms_admin.js', 2);
		$out .= $jsm->page_content();
		$jsm->reset(); // start another merger-file
		$jsm->queue_matchedfile('jquery.ui.touch-punch.js', 1);
		$jsm->queue_matchedfile('jquery.toast.js', 1);
		$jsm->queue_matchedfile('standard.js', 3, __DIR__.DIRECTORY_SEPARATOR.'includes');
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

		//extra theme-specific setup
		$fp = __DIR__ . DIRECTORY_SEPARATOR . 'function.extraparms.php';
		if (is_file($fp)) {
			require_once $fp;
			if (!empty($tplvars)) {
				$smarty->assign($tplvars);
			}
		}

		$fp = cms_join_path(__DIR__, 'css', 'font-awesome.min.css');
		if (is_file($fp)) {
			$url = cms_path_to_url($fp);
		} else {
			$url = self::AWESOME_CDN; // TODO variable CDN URL
		}
		$smarty->assign('font_includes', ' <link rel="stylesheet" href="'.$url.'" />');

		$fn = 'style';
		if (NlsOperations::get_language_direction() == 'rtl') {
			if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
		// css: jquery-ui and scripts: jquery, jquery-ui
		$incs = cms_installed_jquery();
		$url = cms_path_to_url($incs['jquicss']);
		$out = <<<EOS
 <link rel="stylesheet" href="$url" />
 <link rel="stylesheet" type="text/css" href="themes/Marigold/css/{$fn}.css" />

EOS;
//		get_csp_token(); //setup CSP header (result not used)
		$tpl = ' <script type="text/javascript" src="%s"></script>'.PHP_EOL;
		$url = cms_path_to_url($incs['jqcore']);
		$out .= sprintf($tpl, $url);
		$url = cms_path_to_url($incs['jqui']);
		$out .= sprintf($tpl, $url);
		$url = 'themes/Marigold/includes/login.min.js'; // TODO cms_get_...()
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
		  ->assign('theme', $this)
		  ->assign('theme_path',__DIR__)
		  ->assign('theme_root', $config['admin_url'].'/themes/Marigold');

		// is the website set down for maintenance?
		if (AppParams::get('site_downnow')) {
			$smarty->assign('is_sitedown', 1);
		}

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR. 'templates');
		return $smarty->fetch('topcontent.tpl');
	}

	/**
	 * @param string $html page content to be processed
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_page($html)
	{
		$smarty = SingleItem::Smarty();
		$uid = get_userid(false);

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
		$modname = $this->get_value('module_name');
		if (!$modname) {
			$modname = RequestParameters::get_request_values('module'); // maybe null
		}
		$smarty->assign('module_name', $modname);

		$module_help_type = $this->get_value('module_help_type');
		// module_help_url
		if ($modname && ($module_help_type || $module_help_type === null) &&
			!UserParams::get_for_user($uid,'hide_help_links', 0)) {
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
			} elseif ($modname) {
				$mod = Utils::get_module($modname);
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
		  ->assign('pagealias', munge_string_to_url($alias));		// page alias

		// icon
		if ($modname && ($icon_url = $this->get_value('module_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="'.$modname.'" class="module-icon" />';
		} elseif ($modname && $title) {
			$tag = $this->get_module_icon($modname, ['alt'=>$modname, 'class'=>'module-icon']);
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
		if (check_permission($uid,'Manage My Settings')) {
			$smarty->assign('mysettings', 1)
			  ->assign('myaccount', 1); //TODO maybe a separate check
		}

		// bookmarks UI
		if (UserParams::get_for_user($uid, 'bookmarks') && check_permission($uid,'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks();
			$smarty->assign('marks', $marks);
		}

		$fp = cms_join_path(__DIR__, 'css', 'font-awesome.min.css');
		if (is_file($fp)) {
			$url = cms_path_to_url($fp);
		} else {
			$url = self::AWESOME_CDN; // TODO variable CDN URL
		}
		$smarty->assign('font_includes', '<link rel="stylesheet" href="'.$url.'" />')
		  ->assign('header_includes', get_page_headtext())
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
		$user = SingleItem::UserOperations()->LoadUserByID($uid);
		$smarty->assign('username', $user->username);
		// selected language
		$lang = UserParams::get_for_user($uid, 'default_cms_language');
		if (!$lang) $lang = AppParams::get('frontendlang');
		$smarty->assign('lang_code', $lang)
		  ->assign('lang_dir', NlsOperations::get_language_direction()); // language direction

		// is the website down for maintenance?
		if (AppParams::get('site_downnow')) {
			$smarty->assign('is_sitedown', 1);
		}

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		return $smarty->fetch('pagetemplate.tpl');
	}
}
