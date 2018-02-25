<?php
# Marigold - an admin theme for CMS Made Simple
# Copyright (C) 2012 Goran Ilic <ja@ich-mach-das.at>
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

class MarigoldTheme extends CmsAdminThemeBase
{
	/**
	 *
	 * @param string $title_name
	 * @param array $extra_lang_params
	 * @param string $link_text
	 * @param bool $module_help_type
	 */
	public function ShowHeader($title_name, $extra_lang_params = [], $link_text = '', $module_help_type = FALSE)
	{
		if ($title_name) {
			$this->set_value('pagetitle', $title_name);
		}
		if (is_array($extra_lang_params) && count($extra_lang_params)) {
			$this->set_value('extra_lang_params', $extra_lang_params);
		}
		$this->set_value('module_help_type', $module_help_type);

		// get the image url
		if ($module_help_type) {
			// help for a module
			$module = '';
			if (isset($_REQUEST['module'])) {
				$module = $_REQUEST['module'];
			} elseif (isset($_REQUEST['mact'])) {
				$tmp = explode(',', $_REQUEST['mact']);
				$module = $tmp[0];
			}

			$base = cms_module_path($module).DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR;
			$path = $base.'icon.png';
			if (!file_exists($path)) {
				$path = $base.'icon.gif';
				if (!file_exists($path)) {
					$path = '';
				}
			}
			if ($path) {
				$config = \cms_config::get_instance();
				$url = str_replace([$config['root_path'],DIRECTORY_SEPARATOR],[$config['root_url'],'/'],$path);
			} else {
				$url = '';
			}
			$this->set_value('module_icon_url', $url);

			// set the module help url (this should be supplied TO the theme)
			$module_help_url = $this->get_module_help_url();
			$this->set_value('module_help_url', $module_help_url);
		}

		$bc = $this->get_breadcrumbs();
		if ($bc) {
			$n = count($bc);
			for ($i = 0; $i < $n; ++$i) {
				$rec = $bc[$i];
				$title = $rec['title'];
				if ($module_help_type && $i + 1 == $n) {
					$module_name = '';
					if (!empty($_GET['module'])) {
						$module_name = trim($_GET['module']);
					} else {
						$tmp = explode(',', $_REQUEST['mact']);
						$module_name = $tmp[0];
					}
					$orig_module_name = $module_name;
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
	}

	public function do_header()
	{
	}

	public function do_footer()
	{
	}

	/**
	 *
	 * @param type $section_name
	 */
	public function do_toppage($section_name)
	{
		$smarty = Smarty::get_instance();
		if ($section_name) {
			$smarty->assign('section_name', $section_name);
			$smarty->assign('pagetitle', lang($section_name));
			$smarty->assign('nodes', $this->get_navigation_tree($section_name, -1, FALSE));
		} else {
			$nodes = $this->get_navigation_tree(-1, 2, FALSE);
			$smarty->assign('nodes', $nodes);
		}

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
	 *
	 * @param array $params UNUSED
	 */
	public function do_login($params)
	{
		include __DIR__ . DIRECTORY_SEPARATOR . 'login.php'; //various init's, including $smarty
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$smarty->display('login.tpl');
	}

	/**
	 *
	 * @param type $html
	 * @return string (or maybe null?)
	 */
	public function postprocess($html)
	{
		$smarty = Smarty::get_instance();
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

		// page title and alias
		$smarty->assign('pagetitle', $title);
		$smarty->assign('subtitle',$this->subtitle);
//		$alias = $this->get_value(??) else munge CHECKME
		$smarty->assign('pagealias', munge_string_to_url($title));

		// module name?
		if (($module_name = $this->get_value('module_name'))) {
			$smarty->assign('module_name', $module_name);
		}

		// module icon?
		if (($module_icon_url = $this->get_value('module_icon_url'))) {
			$smarty->assign('module_icon_url', $module_icon_url);
		}

		// module_help_url?
		if (!cms_userprefs::get_for_user(get_userid(),'hide_help_links',0)) {
			if (($module_help_url = $this->get_value('module_help_url'))) {
				$smarty->assign('module_help_url', $module_help_url);
			}
		}

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

		$smarty->assign('headertext',$this->get_headtext());
		$smarty->assign('footertext',$this->get_footertext());

		// and some other common variables
		$smarty->assign('content', str_replace('</body></html>', '', $html));
		$smarty->assign('config', cms_config::get_instance());
		$smarty->assign('theme', $this);
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$userops = UserOperations::get_instance();
		$smarty->assign('user', $userops->LoadUserByID(get_userid()));
		// get user selected language
		$smarty->assign('lang',cms_userprefs::get_for_user(get_userid(), 'default_cms_language'));
		// get language direction
		$lang = CmsNlsOperations::get_current_language();
		$info = CmsNlsOperations::get_language_info($lang);
		$smarty->assign('lang_dir',$info->direction());

		if (is_array($this->_errors) && count($this->_errors)) {
			$smarty->assign('errors', $this->_errors);
		}
		if (is_array($this->_successes) && count($this->_successes)) {
			$smarty->assign('messages', $this->_successes);
		}
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

	public function get_my_alerts()
	{
		return \CMSMS\AdminAlerts\Alert::load_my_alerts();
	}
}
