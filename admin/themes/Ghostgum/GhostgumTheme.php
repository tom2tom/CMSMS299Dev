<?php
# Ghostgum - an admin theme for CMS Made Simple
# Copyright (C) 2018 Tom Phane <tomph@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\internal\Smarty;
use CMSMS\AdminUtils;

class GhostgumTheme extends CmsAdminThemeBase
{
	/**
	 * @ignore
	 */
	private $_havetree = null;

	/**
	 * Hook function to nominate runtime resources, which will be included in the header of each displayed admin page
	 *
	 * @param array $vars assoc. array of js-variable names and their values
	 * @param array $add_list array of strings representing includables
	 * @return array 2-members, which are the supplied params after any updates
	 */
	public function AdminHeaderSetup(array $vars, array $add_list) : array
	{
		list($vars, $add_list) = parent::AdminHeaderSetup($vars, $add_list);

		$config = cms_config::get_instance();
		$root_url = $config['admin_url'];
		$assets_url = $root_url . '/themes/assets/';
		$rel = substr(__DIR__, strlen($config['admin_path']));
		$base_url = $root_url . strtr($rel,DIRECTORY_SEPARATOR,'/');
//		$script_url = CMS_SCRIPTS_URL;
		$fn = 'style';
		$dir = CmsNlsOperations::get_language_direction();
		if ($dir == 'rtl') {
			if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
		//TODO
/*        if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
			$fn .= '_ie';
		}
*/
		//TODO relevant tile color #f79838 = orange
		$out = <<<EOS
<meta name="msapplication-TileColor" content="#f79838" />
<meta name="msapplication-TileImage" content="{$assets_url}images/ms-application-icon.png" />
<link rel="shortcut icon" href="{$assets_url}images/cmsms-favicon.ico" />
<link rel="apple-touch-icon" href="{$assets_url}images/apple-touch-icon-iphone.png" />
<link rel="apple-touch-icon" sizes="72x72" href="{$assets_url}images/apple-touch-icon-ipad.png" />
<link rel="apple-touch-icon" sizes="114x114" href="{$assets_url}images/apple-touch-icon-iphone4.png" />
<link rel="apple-touch-icon" sizes="144x144" href="{$assets_url}images/apple-touch-icon-ipad3.png" />

EOS;
		list ($jqui, $jqcss) = cms_jqueryui_local();
		$url = AdminUtils::path_to_url($jqcss);
		$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />
<link rel="stylesheet" type="text/css" href="{$base_url}/css/{$fn}.css" />

EOS;
		if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'extcss'.DIRECTORY_SEPARATOR.$fn.'.css')) {
			$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$base_url}/extcss/{$fn}.css" />

EOS;
		}
		$tpl = '<script type="text/javascript" src="%s"></script>'."\n";
		list ($jqcore, $jqmigrate) = cms_jquery_local();

		$sm = new \CMSMS\ScriptManager();
		$sm->queue_script($jqcore, 1);
		$sm->queue_script($jqmigrate, 1);
		$sm->queue_script($jqui, 1);
        $p = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR;
		$sm->queue_script($p.'jquery.cms_admin.js', 2); //OR .min for production
		$fn = $sm->render_scripts('', false, false);
		$url = AdminUtils::path_to_url(TMP_CACHE_LOCATION).'/'.$fn;
		$out .= sprintf($tpl,$url);

		global $CMS_LOGIN_PAGE;
		if( isset($_SESSION[CMS_USER_KEY]) && !isset($CMS_LOGIN_PAGE) ) {
			$sm->reset();
			require_once CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'jsruntime.php';
            $sm->queue_string($_out_);
			$fn = $sm->render_scripts('', false, false);
			$url = AdminUtils::path_to_url(TMP_CACHE_LOCATION).'/'.$fn;
			$out .= sprintf($tpl,$url);
		}

		$sm->reset();
		$sm->queue_script($p.'jquery.ui.touch-punch.min.js', 1);
		$sm->queue_script($p.'jquery.toast.js', 1); //OR .min for production
        $p = __DIR__.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR;
		$sm->queue_script($p.'jquery.alertable.js', 2); //OR .min for production
		$sm->queue_script($p.'standard.js', 3); //OR .min for production
		$fn = $sm->render_scripts();
		$url = AdminUtils::path_to_url(TMP_CACHE_LOCATION).'/'.$fn;
		$out .= sprintf($tpl,$url);

//<script type="text/javascript" src="{$assets_url}js/jquery.responsivetable.js"></script> TESTER
		$out .= <<<EOS
<!--[if lt IE 9]>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
<script type="text/javascript" src="{$base_url}/js/libs/jquery-extra-selectors.js"></script>
<script type="text/javascript" src="{$base_url}/js/libs/selectivizr-min.js"></script>
<![endif]-->
EOS;
		$add_list[] = $out;
//		$vars[] = anything needed ?;

		return [$vars, $add_list];
	}

	/**
	 * Record some parameters for later use. Such parameters may not be used by
	 * a theme (and are not, by this one, so this method is really a demo)
	 *
	 * @param string $title_name        Displayable content, or a lang key, for the title-text to be displayed
	 * @param array  $extra_lang_params Optional extra string(s) to be supplied (with $title_key) to lang()
	 * @param string $link_text         Optional link ... TODO
	 * @param mixed  $module_help_type  Optional flag, one of true/false/'both'
	 */
	public function ShowHeader($title_name, $extra_lang_params = [], $link_text = '', $module_help_type = false)
	{
/*		if ($title_name) {
			$this->set_value('pagetitle', $title_name);
			if (is_array($extra_lang_params) && count($extra_lang_params)) {
				$this->set_value('extra_lang_params', $extra_lang_params);
			}
		}

		$this->set_value('module_help_type', $module_help_type);
		if ($module_help_type) {
			// set the module help url TODO supply this TO the theme
			$module_help_url = $this->get_module_help_url();
			$this->set_value('module_help_url', $module_help_url);
		}

		// are we processing a module action?
		// TODO maybe cache this in $this->_modname ??
		if (isset($_REQUEST['module'])) {
			$module = $_REQUEST['module'];
		} elseif (isset($_REQUEST['mact'])) {
			$module = explode(',', $_REQUEST['mact'])[0];
		} else {
			$module = '';
		}

		if ($module) {
			$tag = AdminUtils::get_module_icon($module, ['alt'=>$module, 'class'=>'module-icon']);
		} else {
			$tag = ''; //TODO get icon for admin operation
			//$tag = $this->get_active_icon());
		}
		$this->set_value('icon_tag', $tag);

		//TODO figure this out ... is it useful?
		$bc = $this->get_breadcrumbs();
		if ($bc) {
			$n = count($bc);
			for ($i = 0; $i < $n; ++$i) {
				$rec = $bc[$i];
				$title = $rec['title'];
				if ($module_help_type && $i + 1 == $n) {
					$module_name = $module;
					$module_name = preg_replace('/([A-Z])/', "_$1", $module_name);
					$module_name = preg_replace('/_([A-Z])_/', "$1", $module_name);
					if ($module_name[0] == '_') {
						$module_name = substr($module_name, 1);
					}
				} else {
					if (($p = strrchr($title, ':')) !== false) {
						$title = substr($title, 0, $p);
					}
					// find the key of the item with this title.
//unused			$title_key = $this->find_menuitem_by_title($title);
				}
			} // for loop
		}
*/
	}

	/**
	 * @param mixed $section_name nav-menu-section name (string), but usually
	 *  null to use the whole menu
	 */
	public function do_toppage($section_name)
	{
		$smarty = Smarty::get_instance();
		if ($section_name) {
			$nodes = $this->get_navigation_tree($section_name, 0);
//			$smarty->assign('section_name', $section_name);
		} else {
			$nodes = $this->get_navigation_tree(null, 2, 'root:view:dashboard');
		}
//		$this->_havetree = $nodes; //block further tree-data changes
		$smarty->assign('nodes', $nodes);
		$smarty->assign('pagetitle', $this->title); //not used in current template
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$smarty->assign('config', cms_config::get_instance());
		$smarty->assign('theme', $this);

		// is the website set down for maintenance?
		if (get_site_preference('enablesitedownmessage'))  {
			$smarty->assign('is_sitedown', 'true');
		}

		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR. 'templates';
		$smarty->display('topcontent.tpl');
		$smarty->template_dir = $otd;
	}

	/**
	 * Display and process a login form
	 * @since 2.3, there is no $params argument
	 */
	public function do_login()
	{
		$smarty = \CMSMS\internal\Smarty::get_instance();

		if (1) {
			// process the supplied inputs TODO skip if this is 1st-pass
			require_once __DIR__.DIRECTORY_SEPARATOR.'login.php';
		}

		if (!empty($params)) $smarty->assign($params);

		$tpl = '<script type="text/javascript" src="%s"></script>'."\n";

		// the only needed scripts are: jquery, jquery-ui, and our custom login
		list ($jqcore, $jqmigrate) = cms_jquery_local();
		$url = AdminUtils::path_to_url($jqcore);
		$out = sprintf($tpl,$url);
		list ($jqui, $jqcss) = cms_jqueryui_local();
		$url = AdminUtils::path_to_url($jqui);
		$out .= sprintf($tpl,$url);
		$out .= <<<EOS
<!--[if lt IE 9]>
<!-- html5 for old IE -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
<![endif]-->

EOS;
		$url = AdminUtils::path_to_url(__DIR__);
		$url .= '/js/login.js';
		$out .= sprintf($tpl,$url);

		$smarty->assign('header_includes', $out); //NOT into bottom (to avoid UI-flash)
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$smarty->display('login.tpl');
	}

	/**
	 * @param string $html page content to be processed
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function postprocess($html)
	{
		$smarty = Smarty::get_instance();
		// page logo, if any
		$sitelogo = cms_siteprefs::get('sitelogo');
		if ($sitelogo) {
			if( !preg_match('~^\w*:?//~',$sitelogo) ) {
				$sitelogo = CMS_ROOT_URL.'/'.$sitelogo;
			}
			$smarty->assign('sitelogo', $sitelogo);
		}
/*
		$module_help_type = $this->get_value('module_help_type');
		// get a page title
		$title = $this->get_value('pagetitle');
		if ($title) {
			if (!$module_help_type) {
				// if not doing module help, translate the string.
				$extra = $this->get_value('extra_lang_params');
				if (!$extra) {
					$extra = [];
				}
	 			$title = lang($title, $extra);
			}
		} else {
			if ($this->title) {
				$title = $this->title;
			} else {
				// no title, get one from the breadcrumbs.
				$bc = $this->get_breadcrumbs();
				if (is_array($bc) && count($bc)) {
					$title = $bc[count($bc) - 1]['title'];
				}
			}
			if (!$title) $title = '';
		}
*/
//		$tree =
			$this->get_navigation_tree(); //TODO if section
		// page title and alias
		$title = $this->get_active_title();
		$smarty->assign('pagetitle', $title);
		$smarty->assign('subtitle', $this->subtitle);
//		$alias = $this->get_value(??) else munge CHECKME
//		$smarty->assign('pagealias', munge_string_to_url($title));

		// module name, if any
		if (isset($_REQUEST['module'])) {
			$module = $_REQUEST['module'];
		} elseif (isset($_REQUEST['mact'])) {
			$module = explode(',', $_REQUEST['mact'])[0];
		} else {
			$module = '';
		}
		$smarty->assign('module_name', $module);

		if ($module) {
			$tag = AdminUtils::get_module_icon($module, ['alt'=>$module, 'class'=>'module-icon']);
			// module_help_url?
			if (!cms_userprefs::get_for_user(get_userid(),'hide_help_links',0)) {
				if (($module_help_url = $this->get_module_help_url())) {
					$smarty->assign('module_help_url', $module_help_url);
				}
			}
		} else {
			$tag = ''; //TODO get icon for admin operation
			//$tag = $this->get_active_icon());
		}
		$smarty->assign('icon_tag', $tag);

		// my preferences
		if (check_permission(get_userid(),'Manage My Settings')) {
			$smarty->assign('mysettings',1);
			$smarty->assign('myaccount',1); //TODO maybe a separate check
		}

		// if bookmarks
		if (cms_userprefs::get_for_user(get_userid(), 'bookmarks') && check_permission(get_userid(),'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks();
			$smarty->assign('marks', $marks);
		}

		$smarty->assign('header_includes', $this->get_headtext());
		$smarty->assign('bottom_includes', $this->get_footertext());

		// and some other common variables
		//strip inappropriate closers cuz we're putting it in the middle somewhere
		$smarty->assign('content', str_replace('</body></html>', '', $html));

		$smarty->assign('config', cms_config::get_instance());
		$smarty->assign('theme', $this);
		// navigation menu data
		if (!$this->_havetree) {
			$smarty->assign('nav', $this->get_navigation_tree(null, 2));
		} else {
			$smarty->assign('nav', $this->_havetree);
		}
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$userops = UserOperations::get_instance();
		$smarty->assign('user', $userops->LoadUserByID(get_userid()));
		// user selected language
		$smarty->assign('lang',cms_userprefs::get_for_user(get_userid(), 'default_cms_language'));
		// language direction
		$smarty->assign('lang_dir', CmsNlsOperations::get_language_direction());
		// is the website down for maintenance?
		if (get_site_preference('enablesitedownmessage')) {
			$smarty->assign('is_sitedown', 'true');
		}

		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$_contents = $smarty->fetch('pagetemplate.tpl');
		$smarty->template_dir = $otd;
		return $_contents;
	}

	/* REDUNDANT ?? */
	public function get_my_alerts()
	{
		return \CMSMS\AdminAlerts\Alert::load_my_alerts();
	}
}
