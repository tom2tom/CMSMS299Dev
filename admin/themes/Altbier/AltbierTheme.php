<?php
/*
Altbier - an admin-console theme for CMS Made Simple
Derived in 2018 by John Beatrice <johnnyb [AT] cmsmadesimple [DOT] org>
from the work provided to the community by:
 Goran Ilic (ja@ich-mach-das.at)
 Robert Campbell

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or (at your
option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS; // TODO PHP5.4+ OK if pre-2.99?

//use CMSMS\RequestParameters; //2.99+
//use Throwable; 2.99+
//use function CMSMS\sanitizeVal; // 2.99+
use CMSMS\AdminAlerts\Alert;
use CMSMS\AppParams;
use CMSMS\LangOperations;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\StylesMerger;
use CMSMS\UserParams;
use CMSMS\Utils;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function _la;
use function check_permission;
use function cleanValue;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_path_to_url;
use function cmsms;
use function CMSMS\get_page_foottext;
use function CMSMS\get_page_headtext;
use function get_userid;
use function lang;
use function munge_string_to_url;

class AltbierTheme extends AdminTheme
{
	/**
	 * For theme exporting/importing
	 * @ignore
	 */
	const THEME_VERSION = '0.6';
	/**
	 * TODO variable(s) for this to generate e.g.
	 * e.g. <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	 * @ignore
	 */
	const AWESOME_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css';

	/**
	 * @ignore
	 */
	private $_havetree = null;

	// 2.99+ will access these via parent-class
	protected $_errors = [];
	protected $_messages = [];

	/**
	 * Determine whether this is running on CMSMS 2.99+
	 */
	protected function currentversion() : bool
	{
		static $flag = null;
		if ($flag === null) {
			$flag = method_exists($this, 'RecordNotice');
		}
		return $flag;
	}

	/**
	 * Hook accumulator-function to nominate runtime resources, which will be
	 * included in the header of each displayed admin page
	 *
	 * @return 2-member array
	 * [0] = array of data for js vars, members like varname=>varvalue
	 * [1] = array of string(s) for includables
	 */
	public function AdminHeaderSetup()
	{
		list($vars, $add_list) = parent::AdminHeaderSetup();

		$config = cmsms()->GetConfig();
		$admin_path = $config['admin_path'];

		$rel = substr(__DIR__, strlen($admin_path) + 1);
		$rel_url = strtr($rel,DIRECTORY_SEPARATOR,'/');

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
		$jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this or keep if (CMS_DEBUG)
//		}
		$jsm->queue_file($incs['jqui'], 1);
		$jsm->queue_matchedfile('jquery.cmsms_admin.js', 2);
		$out .= $jsm->page_content();
		$jsm->reset(); //start another merger-file
		$jsm->queue_matchedfile('jquery.ui.touch-punch.js', 1);
		$jsm->queue_matchedfile('jquery.toast.js', 1);
		$jsm->queue_matchedfile('standard.js', 3, __DIR__.DIRECTORY_SEPARATOR.'includes');
		$out .= $jsm->page_content('', false, true);

		$add_list[] = $out;
//		$vars[] = anything needed ?;
		return [$vars, $add_list];
	}

	public function ShowHeader($title_name, $extra_lang_params = [], $link_text = '', $module_help_type = FALSE)
	{
		if ($this->currentversion()) {
			parent::ShowHeader($title_name, $extra_lang_params, $link_text, $module_help_type);
		} else { // pre 2.99

		if ($title_name) $this->set_value('pagetitle', $title_name);
		if ($extra_lang_params) $this->set_value('extra_lang_params', $extra_lang_params);
		$this->set_value('module_help_type', $module_help_type);

		$config = cmsms()->GetConfig();
		if ($module_help_type) {
			// help for a module.
			$module = '';
			if (isset($_REQUEST['module'])) {
				$module = $_REQUEST['module'];
			} elseif (isset($_REQUEST['mact'])) {
				$tmp = explode(',', $_REQUEST['mact']);
				$module = $tmp[0];
			}

			// get the image url.
			$icon = "modules/{$module}/images/icon.gif";
			$path = cms_join_path($config['root_path'], $icon);
			if (is_file($path)) {
				$url = $config->smart_root_url() . '/' . $icon;
				$this->set_value('module_icon_url', $url);
			}

			// set the module help url (this should be supplied TO the theme)
			$module_help_url = $this->get_module_help_url();
			$this->set_value('module_help_url', $module_help_url);
		}

		$bc = $this->get_breadcrumbs();
		if ($bc) {
			for ($i = 0, $n = count($bc); $i < $n; $i++) {
				$rec = $bc[$i];
				$title = $rec['title'];
				if ($module_help_type && $i + 1 == count($bc)) {
					$module_name = '';
					if (!empty($_GET['module'])) {
						$module_name = trim($_GET['module']);
					} else {
						$tmp = explode(',', $_REQUEST['mact']);
						$module_name = $tmp[0];
					}
					$orig_module_name = $module_name;
					$module_name = preg_replace('/([A-Z])/', '_$1', $module_name);
					$module_name = preg_replace('/_([A-Z])_/', '$1', $module_name);
					if ($module_name[0] == '_')
						$module_name = substr($module_name, 1);
				} else {
					if (($p = strrchr($title, ':')) !== FALSE) {
						$title = substr($title, 0, $p);
					}
					// find the key of the item with this title.
					$title_key = $this->find_menuitem_by_title($title);
				}
			} // for-loop
		}

		} // pre-2.99
	}

	/**
	 * Get URL's for installed jquery, jquery-ui & related css
	 * Only for pre-2.99 operation
	 * @return 3-member array
	 */
	protected function find_installed_jq()
	{
		$config = cmsms()->GetConfig();

		$fp = cms_join_path(CMS_ROOT_PATH,'lib','jquery','css','*','jquery-ui*.css');
		$m = glob($fp, GLOB_NOSORT|GLOB_NOESCAPE);
		//find highest version
		$best = '0';
		$use = false;
		foreach ($m as $fn) {
			$file = basename($fn);
			preg_match('~(\d[\d\.]+\d)~', $file, $matches);
			if (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$p = basename(dirname($m[0]));
		$jqcss = CMS_ROOT_URL.'/lib/jquery/ccs/'.$p.'/'.$use;

		$fp = cms_join_path(CMS_ROOT_PATH,'lib','jquery','js');
		$allfiles = scandir($fp);
		$m = preg_grep('~^jquery\-ui\-\d[\d\.]+\d([\.\-]custom)?(\.min)?\.js$~', $allfiles);
		//find highest version
		$best = '0';
		$use = reset($m);
		foreach ($m as $file) {
			preg_match('~(\d[\d\.]+\d)~', $file, $matches);
			if (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$jqui = CMS_ROOT_URL.'/lib/jquery/js/'.$use;

		$m = preg_grep('~^jquery\-\d[\d\.]+\d(\.min)?\.js$~', $allfiles);
		//find highest version
		$best = '0';
		$use = reset($m);
		foreach ($m as $file) {
			preg_match('~(\d[\d\.]+\d)~', $file, $matches);
			if (version_compare($best, $matches[1]) < 0) {
				$best = $matches[1];
				$use = $file;
			}
		}
		$jqcore = CMS_ROOT_URL.'/lib/jquery/js/'.$use;

		return [$jqcss, $jqui, $jqcore];
	}

	public function display_login_page()
	{
		$gCms = cmsms();
		$smarty = $gCms->GetSmarty();

		$fp = cms_join_path(__DIR__, 'css', 'all.min.css');
		if (is_file($fp)) {
			$url = cms_path_to_url($fp);
		} else {
			$url = self::AWESOME_CDN; // TODO variable(s) for CDN URL and SRI hash
		}
		$smarty->assign('font_includes', '<link rel="stylesheet" href="'.$url.'" />');

		if ($this->currentversion()) {
			$auth_module = AppParams::get('loginmodule', ModuleOperations::STD_LOGIN_MODULE);
			$mod = SingleItem::ModuleOperations()->get_module_instance($auth_module, '', true);
			if ($mod) {
				$data = $mod->fetch_login_panel();
				if (isset($data['infomessage'])) $data['message'] = $data['infomessage'];
				if (isset($data['warnmessage'])) $data['warning'] = $data['warnmessage'];
				if (isset($data['errmessage'])) $data['error'] = $data['errmessage'];
			} else {
				exit('System error');
			}

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

//TODO	ensure $smarty->assign('lang_code', AppParams::get('frontendlang'));
			$fn = 'style';
			if (NlsOperations::get_language_direction() == 'rtl') {
				if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
					$fn .= '-rtl';
				}
			}
			// scripts: jquery, jquery-ui
			$incs = cms_installed_jquery();
			$url = cms_path_to_url($incs['jquicss']);
			$out = <<<EOS
<link rel="stylesheet" type="text/css" href="$url" />
<link rel="stylesheet" type="text/css" href="themes/Altbier/css/{$fn}.css" />

EOS;
//			get_csp_token(); //setup CSP header (result not used)
			$tpl = '<script type="text/javascript" src="%s"></script>'.PHP_EOL;
			$url = cms_path_to_url($incs['jqcore']);
			$out .= sprintf($tpl,$url);
			$url = cms_path_to_url($incs['jqui']);
			$out .= sprintf($tpl,$url);
			$out .= sprintf($tpl,'themes/Altbier/includes/login.min.js');
		} else {
			$params = func_get_args();
			if (!empty($params)) {
				$smarty->assign($params[0]);
			}

			$config = $gCms->GetConfig();
			//extra setup/parameters for the form
			$fp = __DIR__ . DIRECTORY_SEPARATOR . 'function.login.php';
			require $fp;
			if (!empty($tplvars)) {
				$smarty->assign($tplvars);
			}

			$dir = ''; //TODO or '-rtl'
			list($jqcss, $jqui, $jqcore) = $this->find_installed_jq();
			$out = <<<EOS
<link rel="stylesheet" href="$jqcss" />
<link rel="stylesheet" href="themes/Altbier/css/style{$dir}.css" />
<link rel="stylesheet" href="loginstyle.php" />
<script type="text/javascript" src="$jqcore"></script>
<script type="text/javascript" src="$jqui"></script>
<script type="text/javascript" src="themes/Altbier/includes/login.min.js"></script>

EOS;
		} // pre 2.99

		$smarty->assign('header_includes', $out)
		  ->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates')
		  ->display('login.tpl');
	}

	/**
	 * @param mixed $section_name nav-menu-section name (string),
	 *  but usually null to use the whole menu
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_menu_page($section_name)
	{
		$flag = $this->currentversion();

		$smarty = cmsms()->GetSmarty();
		if ($section_name) {
			$smarty->assign('section_name', $section_name);
			if ($flag) {
				$nodes = $this->get_navigation_tree($section_name, 0);
				$smarty->assign('pagetitle', $this->title);
			} else {
				$nodes = $this->get_navigation_tree($section_name, -1, FALSE);
				$smarty->assign('pagetitle', _la($section_name)); //CHECKME
			}
		} elseif ($flag) {
			$nodes = $this->get_navigation_tree(null, 3, 'root:view:dashboard');
		} else {
			$nodes = $this->get_navigation_tree(-1, 2, FALSE);
		}
//		$this->_havetree = $nodes; //block further tree-data changes
		$smarty->assign('nodes', $nodes);

		$config = cmsms()->GetConfig();
		$smarty->assign('admin_url', $config['admin_url'])
		  ->assign('theme', $this);

		// is the website down for maintenance?
		if (AppParams::get('site_downnow')) {
			$smarty->assign('is_sitedown', 1);
		}

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		return $smarty->fetch('topcontent.tpl');
	}

	/**
	 * @param string $html page content to be processed
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_page($html)
	{
		$flag = $this->currentversion();

		$smarty = cmsms()->GetSmarty();
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
			if (isset($_REQUEST['mact'])) {
				$module_name = explode(',', $_REQUEST['mact'])[0];
			}
		}
		$smarty->assign('module_name', $module_name); // maybe null

		$module_help_type = $this->get_value('module_help_type');
		// module_help_url
		if ($module_name && ($module_help_type || $module_help_type === null) &&
			!UserParams::get_for_user($userid,'hide_help_links', 0)) {
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
				$title = _la($title, $extra);
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
			$tag = '<img src="'.$icon_url.'" alt="'.basename($icon_url).'" class="TODO" />';
		} else {
			$name = $this->get_active('name');
			$tag = ''; // TODO icon for admin operation func($name) ?
		}
		$smarty->assign('pageicon', $tag);

		$config = cmsms()->GetConfig();
		// site logo
		$sitelogo = AppParams::get('site_logo');
		if ($sitelogo) {
			if (!preg_match('~^\w*:?//~', $sitelogo)) {
				$sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
			}
			$smarty->assign('sitelogo', $sitelogo);
		}

		//custom support-URL
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
		if (UserParams::get_for_user($userid, 'bookmarks') && check_permission($userid, 'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks();
			$smarty->assign('marks', $marks);
		}

		$secureparam = CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];
		// other variables
		$smarty->assign('admin_url', $config['admin_url'])
		  ->assign('content', str_replace('</body></html>', '', $html))
		  ->assign('theme', $this)
		  ->assign('secureparam', $secureparam);
		$user = SingleItem::UserOperations()->LoadUserByID($userid);
		$smarty->assign('username', $user->username);
		// user-selected language
		$lang = UserParams::get_for_user($userid, 'default_cms_language');
		if (!$lang) $lang = AppParams::get('frontendlang');
		$smarty->assign('lang_code', $lang);
		// language direction
		$lang = NlsOperations::get_current_language();
		$info = NlsOperations::get_language_info($lang);
		$smarty->assign('lang_dir', $info->direction());

		$fp = cms_join_path(__DIR__, 'css', 'all.min.css');
		if (is_file($fp)) {
			$url = cms_path_to_url($fp); // TODO 2.99+
		} else {
			$url = self::AWESOME_CDN; // TODO variable(s) for CDN URL and SRI hash
		}
		$smarty->assign('font_includes', '<link rel="stylesheet" href="'.$url.'" />');

		if ($flag) {
			$smarty->assign('header_includes', get_page_headtext())
			 ->assign('bottom_includes', get_page_foottext());
		} else {
			// replicate AdminHeaderSetup(), with different js
			// no CSP-related attrs for html5shiv - old IE incapable!
			$dir = ''; //TODO or '-rtl'
			list($jqcss, $jqui, $jqcore) = $this->find_installed_jq();
			$smarty->assign('header_includes', <<<EOS
<link rel="stylesheet" href="$jqcss" />
<link rel="stylesheet" href="style.php?{$secureparam}" />
<link rel="stylesheet" href="themes/Altbier/css/style{$dir}.css" />
<script type="text/javascript" src="$jqcore"></script>
<script type="text/javascript" src="$jqui"></script>
//TODO jquery ancillaries
<script type="text/javascript" src="themes/Altbier/includes/standard.min.js"></script>
<!--[if lt IE 9]>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
<![endif]-->

EOS
);
			if ($this->_errors)
				$smarty->assign('errors', $this->_errors);
			if ($this->_messages)
				$smarty->assign('messages', $this->_messages);
		}

		// is the website set down for maintenance?
		if (AppParams::get('site_downnow')) {
			$smarty->assign('is_sitedown', 1);
		}

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR .'templates');
		return $smarty->fetch('pagetemplate.tpl');
	}

	// for pre-2.99 compatibility

	public function ShowErrors($errors, $get_var = '')
	{
/*		if ($this->currentversion()) {
			$this->RecordNotice('error', $errors, '', false, $get_var);
		} else {
*/
		// cache errors for use in the template.
		if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_errors[] = lang(cleanValue($one)); // pre-2.99
				}
			} else {
				$this->_errors[] = lang(cleanValue($_GET[$get_var]));
			}
		} elseif (is_array($errors)) {
			foreach ($errors as $one) {
				$this->_errors[] = $one;
			}
		} elseif (is_string($errors)) {
			$this->_errors[] = $errors;
		}
		return '<!-- Altbier::ShowErrors() called -->';

//		} //pre 2.99
	}

	public function ShowMessage($message, $get_var = '')
	{
/*		if ($this->currentversion()) {
			$this->RecordNotice('success', $message, '', false, $get_var);
		} else {
*/
		// cache message for use in the template.
		if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_messages[] = lang(cleanValue($one));
				}
			} else {
				$this->_messages[] = lang(cleanValue($_GET[$get_var]));
			}
		} elseif (is_array($message)) {
			foreach ($message as $one) {
				$this->_messages[] = $one;
			}
		} elseif (is_string($message)) {
			$this->_messages[] = $message;
		}

//		} // pre 2.99
	}

	public function do_toppage($section_name)
	{
		echo $this->fetch_menu_page($section_name);
	}

	public function do_login($params)
	{
		$this->display_login_page($params);
	}

	public function postprocess($html)
	{
		return $this->fetch_page($html);
	}

	public function get_my_alerts()
	{
		return Alert::load_my_alerts();
	}
}
