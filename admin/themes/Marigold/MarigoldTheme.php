<?php
# Marigold - an admin theme for CMS Made Simple
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

namespace CMSMS;

use cms_config;
use cms_siteprefs;
use cms_userprefs;
use cms_utils;
use CmsApp;
use CMSMS\LangOperations;
use CMSMS\AdminUtils;
use CMSMS\ModuleOperations;
use CMSMS\ScriptManager;
use CMSMS\UserOperations;
use CmsNlsOperations;
use const CMS_ADMIN_PATH;
use const CMS_SCRIPTS_PATH;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const TMP_CACHE_LOCATION;
use function check_permission;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_path_to_url;
use function get_userid;
use function lang;
use function munge_string_to_url;

class MarigoldTheme extends ThemeBase
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
	 * @since 2.3
	 * @return 2-member array (not typed to support back-compatible themes)
	 * [0] = array of data for js vars, members like varname=>varvalue
     * [1] = array of string(s) for includables
	 */
	public function AdminHeaderSetup()
	{
		list($vars, $add_list) = parent::AdminHeaderSetup();

		$config = cms_config::get_instance(); //also used by included file
		$admin_url = $config['admin_url'];
		$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
		$rel_url = strtr($rel,DIRECTORY_SEPARATOR,'/');
		$fn = 'style';
		if (CmsNlsOperations::get_language_direction() == 'rtl') {
			if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
		$incs = cms_installed_jquery(true, true, true, true);
		$url = cms_path_to_url($incs['jquicss']);
		$out = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />
<link rel="stylesheet" type="text/css" href="{$rel_url}/css/{$fn}.css" />

EOS;
		if (is_file(__DIR__.DIRECTORY_SEPARATOR.'extcss'.DIRECTORY_SEPARATOR.$fn.'.css')) {
			$out .= <<<EOS
<link rel="stylesheet" type="text/css" href="{$rel_url}/extcss/{$fn}.css" />

EOS;
		}

		$sm = new ScriptManager();
		$sm->queue_file($incs['jqcore'], 1);
		$sm->queue_file($incs['jqmigrate'], 1); //in due course, omit this ?
		$sm->queue_file($incs['jqui'], 1);
        $p = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR;
		$sm->queue_file($p.'jquery.cms_admin.min.js', 2);
	    $out .= $sm->render_inclusion('', false, false);

		global $CMS_LOGIN_PAGE;
		if (isset($_SESSION[CMS_USER_KEY]) && !isset($CMS_LOGIN_PAGE)) {
			$sm->reset();
			require_once CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'jsruntime.php';
            $sm->queue_string($_out_);
		    $out .= $sm->render_inclusion('', false, false);
		}

		$sm->reset();
		$sm->queue_matchedfile('jquery.ui.touch-punch.min.js', 1);
		$sm->queue_matchedfile('jquery.toast.min.js', 1);
		$p = __DIR__.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR;
		$sm->queue_file($p.'standard.js', 3); //OR .min for production
	    $out .= $sm->render_inclusion();

		$add_list[] = $out;
//		$vars[] = anything needed ?;

		return [$vars, $add_list];
	}

	/**
	 * @param mixed $section_name nav-menu-section name (string), but
	 *  usually null to use the whole menu
	 */
	public function do_toppage($section_name)
	{
		$smarty = CmsApp::get_instance()->GetSmarty();
		if ($section_name) {
//			$smarty->assign('section_name', $section_name);
			$nodes = $this->get_navigation_tree($section_name, 0);
		} else {
			$nodes = $this->get_navigation_tree(null, 3, 'root:view:dashboard');
		}
//		$this->_havetree = $nodes; //block further tree-data changes
		$smarty->assign('nodes', $nodes);
		$smarty->assign('pagetitle', $this->title); //not used in current template
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);

		$config = cms_config::get_instance();
		$smarty->assign('admin_url', $config['admin_url']);
		$smarty->assign('theme', $this);
		$smarty->assign('theme_path',__DIR__);
		$smarty->assign('theme_root', $config['admin_url'].'/themes/Marigold');

		//custom support-URL?
		$url = cms_siteprefs::get('site_help_url');
		if ($url) {
			$smarty->assign('site_help_url', $url);
		}
		// is the website set down for maintenance?
		if (cms_siteprefs::get('enablesitedownmessage')) {
			$smarty->assign('is_sitedown', 1);
		}

		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR. 'templates';
		$smarty->display('topcontent.tpl');
		$smarty->template_dir = $otd;
	}

	public function do_minimal()
	{
		$incs = cms_installed_jquery(true, false, true, false);
		$sm = new ScriptManager();
		$sm->queue_file($incs['jqcore'], 1);
		$sm->queue_file($incs['jqui'], 1);
		$fn = $sm->render_scripts('', false, false);
		$url = cms_path_to_url(TMP_CACHE_LOCATION);

		$smarty = CmsApp::get_instance()->GetSmarty();
		$smarty->assign('dynamic_js', <<<EOS
<script type="text/javascript" src="{$url}/{$fn}"></script>

EOS
);
		$smarty->assign('content',$this->get_content());
		$smarty->assign('title',$this->title);
		$smarty->assign('subtitle',$this->subtitle);
		$config = cms_config::get_instance();
		$smarty->assign('admin_root', $config['admin_url']);
		$smarty->assign('theme_path', __DIR__);
		$smarty->assign('theme_root', $config['admin_url'].'/themes/Marigold');
		$smarty->assign('footer', $this->do_footer());
		return $smarty->fetch('minimal.tpl');
	}

/* ALTERNATE APPROACH TO LOGIN PROCESSING - NOT YET EVALUATED
	public function do_login($params = null)
	{
		$auth_module = cms_siteprefs::get('loginmodule', 'CoreAdminLogin');
		$modinst = ModuleOperations::get_instance()->get_module_instance($auth_module, '', true);
		if ($modinst) {
			$data = $modinst->StageLogin(); //returns only if further processing is needed
		} else {
			die('System error');
		}

		$smarty = CmsApp::get_instance()->GetSmarty();
		$smarty->assign($data);
		$config = cms_config::get_instance();
		// TODO
		$smarty->assign('content', $this->get_content());
		$smarty->assign('title', $this->title);
		$smarty->assign('subtitle', $this->subtitle);
		$smarty->assign('admin_root', $config['admin_url']);
		$smarty->assign('theme_root', $config['admin_url'].'/themes/Marigold');
		$smarty->assign('theme_path', __DIR__);
		$smarty->assign('footer', $this->do_footer());
		$smarty->assign('lang_code', cms_siteprefs::get('frontendlang'));
		$smarty->assign('pageid', $pageid);
		$smarty->assign('dynamic_headtext', $this->get_headtext());

		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$smarty->display('login.tpl');
	}
*/
	/**
	 * @param  mixed $params For parent-compatibility only, unused.
	 */
	public function do_login($params = null)
	{
		$auth_module = cms_siteprefs::get('loginmodule', 'CoreAdminLogin');
		$modinst = ModuleOperations::get_instance()->get_module_instance($auth_module, '', true);
		if ($modinst) {
			$data = $modinst->StageLogin(); //returns only if further processing is needed
		} else {
			die('System error');
		}

		$smarty = CmsApp::get_instance()->GetSmarty();
		$smarty->assign($data);

		//extra shared parameters for the form
		$config = cms_config::get_instance(); //also need by the inclusion
		$fp = cms_join_path($config['admin_path'], 'themes', 'assets', 'function.extraparms.php');
		require_once $fp;
		$smarty->assign($tplvars);

		//extra theme-specific setup
		$fp = cms_join_path(__DIR__, 'function.extraparms.php');
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
		$smarty->assign('font_includes', '<link rel="stylesheet" href="'.$url.'" />');

		// css: jquery-ui and scripts: jquery, jquery-ui
		$incs = cms_installed_jquery();
		$url = cms_path_to_url($incs['jquicss']);
		$dir = ''; //TODO or '-rtl'
		$out = <<<EOS
<link rel="stylesheet" href="$url" />
<link rel="stylesheet" href="themes/Marigold/css/style{$dir}.css" />

EOS;
		$tpl = '<script type="text/javascript" src="%s"></script>'."\n";
		$url = cms_path_to_url($incs['jqcore']);
		$out .= sprintf($tpl, $url);
		$url = cms_path_to_url($incs['jqui']);
		$out .= sprintf($tpl, $url);

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
		$smarty = CmsApp::get_instance()->GetSmarty();
        $uid = get_userid(false);

		// setup titles etc
//		$tree =
			$this->get_navigation_tree(); //TODO if section

		// prefer cached parameters, if any
		// module name
		$module_name = $this->get_value('module_name');
		if (!$module_name && isset($_REQUEST['mact'])) {
			$module_name = explode(',', $_REQUEST['mact'])[0];
		}
		$smarty->assign('module_name', $module_name);

		$module_help_type = $this->get_value('module_help_type');
		// module_help_url
		if ($module_name && ($module_help_type || $module_help_type === null) &&
			!cms_userprefs::get_for_user($uid,'hide_help_links', 0)) {
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
				$modinst = cms_utils::get_module($module_name);
				$title = $modinst->GetFriendlyName();
				$subtitle = $modinst->GetAdminDescription();
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
		$smarty->assign('pagetitle', $title);
		$smarty->assign('subtitle', $subtitle);

		// page alias
		$smarty->assign('pagealias', munge_string_to_url($alias));

		// icon
		if ($module_name && ($icon_url = $this->get_value('module_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="'.$module_name.'" class="module-icon" />';
		} elseif ($module_name && $title) {
			$tag = AdminUtils::get_module_icon($module_name, ['alt'=>$module_name, 'class'=>'module-icon']);
		} elseif (($icon_url = $this->get_value('page_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="TODO" class="TODO" />';
		} else {
			$tag = ''; //TODO get icon for admin operation
		}
		$smarty->assign('pageicon', $tag);

        $config = cms_config::get_instance();
		// site logo
		$sitelogo = cms_siteprefs::get('sitelogo');
		if ($sitelogo) {
			if (!preg_match('~^\w*:?//~', $sitelogo)) {
				$sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
			}
			$smarty->assign('sitelogo', $sitelogo);
		}

		// preferences UI
		if (check_permission($uid,'Manage My Settings')) {
			$smarty->assign('mysettings', 1);
			$smarty->assign('myaccount', 1); //TODO maybe a separate check
		}

		// bookmarks UI
		if (cms_userprefs::get_for_user($uid, 'bookmarks') && check_permission($uid,'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks();
			$smarty->assign('marks', $marks);
		}

		$fp = cms_join_path(__DIR__, 'css', 'font-awesome.min.css');
		if (is_file($fp)) {
			$url = cms_path_to_url($fp);
		} else {
			$url = self::AWESOME_CDN; // TODO variable CDN URL
		}
		$smarty->assign('font_includes', '<link rel="stylesheet" href="'.$url.'" />');

		$smarty->assign('header_includes', $this->get_headtext());
		$smarty->assign('bottom_includes', $this->get_footertext());

		// other variables
		//strip inappropriate closers cuz we're putting it in the middle somewhere
		$smarty->assign('content', str_replace('</body></html>', '', $html));

		$smarty->assign('admin_url', $config['admin_url']);
		$smarty->assign('assets_url', $config['admin_url'] . '/themes/assets');

		$smarty->assign('theme', $this);
		// navigation menu data
		if (!$this->_havetree) {
			$smarty->assign('nav', $this->get_navigation_tree());
		} else {
			$smarty->assign('nav', $this->_havetree);
		}
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$userops = UserOperations::get_instance();
		$user = $userops->LoadUserByID($uid);
		$smarty->assign('username', $user->username);
		// selected language
		$lang = cms_userprefs::get_for_user($uid, 'default_cms_language');
		if (!$lang) $lang = cms_siteprefs::get('frontendlang');
		$smarty->assign('lang_code', $lang);
		// language direction
		$smarty->assign('lang_dir', CmsNlsOperations::get_language_direction());
		// is the website down for maintenance?
		if (cms_siteprefs::get('enablesitedownmessage')) {
			$smarty->assign('is_sitedown', 1);
		}

		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$_contents = $smarty->fetch('pagetemplate.tpl');
		$smarty->template_dir = $otd;
		return $_contents;
	}
}
