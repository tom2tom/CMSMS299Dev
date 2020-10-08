<?php
/*
Ghostgum - an admin theme for CMS Made Simple
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Tom Phane and all other contributors from the CMSMS Development Team
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS;

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\internal\GetParameters;
use CMSMS\LangOperations;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\StylesMerger;
use CMSMS\UserOperations;
use CMSMS\UserParams;
use CMSMS\Utils;
use const CMS_ADMIN_PATH;
use const CMS_SCRIPTS_PATH;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const TMP_CACHE_LOCATION;
use function check_permission;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_path_to_url;
use function cmsms;
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
	 * @since 2.9
	 * @return 2-member array (not typed to support back-compatible themes)
	 * [0] = array of data for js vars, members like varname=>varvalue
	 * [1] = array of string(s) for includables
	 */
	public function AdminHeaderSetup()
	{
		list($vars, $add_list) = parent::AdminHeaderSetup();

		$config = AppSingle::Config();
		$admin_url = $config['admin_url'];
		$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
		$rel_url = strtr($rel, DIRECTORY_SEPARATOR, '/');
//      $base_url = $admin_url . strtr($rel, DIRECTORY_SEPARATOR, '/');
		$fn = 'style';
		if (NlsOperations::get_language_direction() == 'rtl') {
			if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
		$incs = cms_installed_jquery(true, true, true, true);
		$url = cms_path_to_url($incs['jquicss']);
		// css files which include relative paths/urls cannot be relocated
		$csm = new StylesMerger();
		$csm->queue_matchedfile('normalize.css', 1);
		$csm->queue_matchedfile('flex-grid-lite.css', 2);
//		$csm->queue_matchedfile('grid-960.css', 2);

		$out = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url}" />

EOS;
		$out .= $csm->page_content();
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
		$p = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR;
//		$jsm->queue_matchedfile('jquery.cmsms_admin.js', 2); N/A
//		$jsm->queue_file($p.'jquery.cmsms_admin.js', 2);
//		$jsm->queue_file($p.'jquery.cmsms_admin.min.js', 2);
		$out .= $jsm->page_content('', false, false);
		//DEBUG
		$u = $config['root_url'];
		$out .= <<<EOS
<script type="text/javascript" src="{$u}/lib/js/jquery.cmsms_admin.js"></script>

EOS;
		$jsm->reset();
		$jsm->queue_matchedfile('jquery.ui.touch-punch.js', 1); //OR .min for production
		$jsm->queue_matchedfile('jquery.toast.min.js', 1);
		$jsm->queue_matchedfile('jquery.basictable.min.js', 1); //TESTER

		$p = __DIR__.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR;
//		$jsm->queue_file($p.'jquery.alertable.js', 2);
		$jsm->queue_file($p.'jquery.alertable.min.js', 2); // for production
//		$jsm->queue_file($p.'standard.js', 3);
//		$jsm->queue_matchedfile('standard.js', 3, $p); // for production
		$out .= $jsm->page_content();
		//DEBUG
		$out .= <<<EOS
<script type="text/javascript" src="{$rel_url}/js/standard.js"></script>

EOS;
		$add_list[] = $out;
//      $vars[] = anything needed ?;
		return [[], $add_list];
	}

	/**
	 * Hook first-result-function to report the default 'main' css class
	 * to be applied to generated context menus when this theme is in operation.
	 *
	 * @since 2.9
	 * @return string
	 */
	public function MenuCssClassname()
	{
		return 'ContextMenu';
	}

	/**
	 * @param mixed $section_name nav-menu-section name (string), but
	 *  usually null to use the whole menu
	 */
	public function do_toppage($section_name)
	{
		$smarty = AppSingle::Smarty();
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

		$config = AppSingle::Config();
		$smarty->assign('admin_url', $config['admin_url'])
		  ->assign('theme', $this);

		//custom support-URL?
		$url = AppParams::get('site_help_url');
		if ($url) {
			$smarty->assign('site_help_url', $url);
		}

		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR. 'templates';
		$smarty->display('topcontent.tpl');
		$smarty->template_dir = $otd;
	}

	protected function render_minimal($tplname, $bodyid = null)
	{
		$incs = cms_installed_jquery(true, true, true, false);
		$jsm = new ScriptsMerger();
		$jsm->queue_file($incs['jqcore'], 1);
//		if (CMS_DEBUG) {
		$jsm->queue_file($incs['jqmigrate'], 1); // for developmant phase, at least
//		}
		$jsm->queue_file($incs['jqui'], 1);
		$fn = $jsm->render_scripts('', false, false);
		$url = cms_path_to_url(TMP_CACHE_LOCATION);
		$header_includes = <<<EOS
<script type="text/javascript" src="{$url}/{$fn}"></script>

EOS;
		$url = AppSingle::Config()['admin_url'];
		$lang = NlsOperations::get_current_language();
		$info = NlsOperations::get_language_info($lang);
		$smarty = cmsms()->GetSmarty();
		$otd = $smarty->GetTemplateDir();
		$smarty->SetTemplateDir(__DIR__.DIRECTORY_SEPARATOR.'templates');

		$smarty->assign('admin_root', $url)
		 ->assign('theme_root', $url.'/themes/Ghostgum')
		 ->assign('title', $this->title)
		 ->assign('lang_dir', $info->direction())
		 ->assign('header_includes', $header_includes)
//		 ->assign('bottom_includes', '') // TODO
		 ->assign('bodyid', $bodyid)
		 ->assign('content', $this->get_content());

		$out = $smarty->fetch($tplname);
		$smarty->SetTemplateDir($otd);
		return $out;
	}

	/**
	 * @todo this has been migrated more-or-less verbatim from old marigold
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function do_minimal($bodyid = null) : string
	{
		return $this->render_minimal('minimal.tpl', $bodyid);
	}

	/**
	 * @todo this has been migrated more-or-less verbatim from old marigold
	 * @param mixed $bodyid Optional id for page 'body' element. Default null
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function do_loginpage($bodyid = null)
	{
		return $this->render_minimal('login-minimal.tpl', $bodyid);
	}

	/**
	 * @param  mixed $params For parent-compatibility only, unused.
	 */
	public function do_login($params = null)
	{
		$auth_module = AppParams::get('loginmodule', ModuleOperations::STD_LOGIN_MODULE);
		$modinst = ModuleOperations::get_instance()->get_module_instance($auth_module, '', true);
		if ($modinst) {
			$data = $modinst->StageLogin();
		} else {
			die('System error');
		}

		$smarty = AppSingle::Smarty();
		$smarty->assign($data);

		//extra shared parameters for the form
		$config = AppSingle::Config(); //also need by the inclusion
		$fp = cms_join_path($config['admin_path'], 'themes', 'assets', 'function.extraparms.php');
		require_once $fp;
		$smarty->assign($tplvars);

//TODO  ensure $smarty->assign('lang_code', AppParams::get('frontendlang'));

		//extra theme-specific parameters for the form
		$fp = cms_join_path(__DIR__, 'function.extraparms.php');
		if (is_file($fp)) {
			require_once $fp;
			if (!empty($tplvars)) {
				$smarty->assign($tplvars);
			}
		}

		$tpl = '<script type="text/javascript" src="%s"></script>'."\n";

		// scripts: jquery, jquery-ui
		$incs = cms_installed_jquery(true, false, true, false);
		$url = cms_path_to_url($incs['jqcore']);
		$out = sprintf($tpl, $url);
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
		$smarty = AppSingle::Smarty();
		$uid = get_userid(false);

		// setup titles etc
//      $tree =
			$this->get_navigation_tree(); //TODO if section

		// prefer cached parameters, if any
		// module name
		$module_name = $this->get_value('module_name');
		if (!$module_name) {
			$params = (new GetParameters())->get_request_values('module');
			if (!$params) exit;
			$module_name = $params['module']; // maybe null
		}
		$smarty->assign('module_name', $module_name);

		$module_help_type = $this->get_value('module_help_type');
		// module_help_url
		if ($module_name && ($module_help_type || $module_help_type === null) &&
			!UserParams::get_for_user($uid, 'hide_help_links', 0)) {
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
//          $subtitle = TODO
		} else {
			$title = $this->get_active_title(); // try for the active-menu-item title
			if ($title) {
				$subtitle = $this->subtitle;
			} elseif ($module_name) {
				$modinst = Utils::get_module($module_name);
				$title = $modinst->GetFriendlyName();
				$subtitle = $modinst->GetAdminDescription();
/*          } else {
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

		// page alias
		  ->assign('pagealias', munge_string_to_url($alias));

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

		$config = AppSingle::Config();
		// site logo
		$sitelogo = AppParams::get('site_logo');
		if ($sitelogo) {
			if (!preg_match('~^\w*:?//~', $sitelogo)) {
				$sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
			}
			$smarty->assign('sitelogo', $sitelogo);
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

		$smarty->assign('header_includes', $this->get_headtext())
		  ->assign('bottom_includes', $this->get_footertext())

		// other variables
		//strip inappropriate closers cuz we're putting it in the middle somewhere
		  ->assign('content', str_replace('</body></html>', '', $html));

		$smarty->assign('admin_url', $config['admin_url'])
		  ->assign('assets_url', $config['admin_url'] . '/themes/assets')

		  ->assign('theme', $this);
		// navigation menu data
		if (!$this->_havetree) {
			$smarty->assign('nav', $this->get_navigation_tree());
		} else {
			$smarty->assign('nav', $this->_havetree);
		}
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$user = UserOperations::get_instance()->LoadUserByID($uid);
		$smarty->assign('username', $user->username);
		// selected language
		$lang = UserParams::get_for_user($uid, 'default_cms_language');
		if (!$lang) $lang = AppParams::get('frontendlang');
		$smarty->assign('lang_code', $lang)
		// language direction
		  ->assign('lang_dir', NlsOperations::get_language_direction());
		// custom support-URL?
		$url = AppParams::get('site_help_url');
		if ($url) {
			$smarty->assign('site_help_url', $url);
		}

		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$_contents = $smarty->fetch('pagetemplate.tpl');
		$smarty->template_dir = $otd;
		return $_contents;
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
