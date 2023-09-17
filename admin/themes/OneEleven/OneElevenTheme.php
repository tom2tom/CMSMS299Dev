<?php
/*
OneEleven - an Admin Console theme for CMS Made Simple
Copyright (C) 2012-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Goran Ilic, Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace CMSMS; // TODO OK if pre-3.0?

//use CMSMS\RequestParameters; //3.0+
//use CMSMS\StylesMerger;
//use CMSMS\UserOperations;
//use Throwable; //3.0+
//use const TMP_CACHE_LOCATION;
//use function CMSMS\sanitizeVal; // 3.0+
use CMSMS\AdminAlerts\Alert;
use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\LangOperations;
use CMSMS\Lone;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\UserParams;
use CMSMS\Utils;
use const CMS_ADMIN_PATH;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function add_page_headtext;
use function check_permission;
use function cleanValue;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_module_path;
use function cms_path_to_url;
use function cmsms;
use function CMSMS\get_page_foottext;
use function CMSMS\get_page_headtext;
use function get_userid;
use function lang;
use function munge_string_to_url;

class OneElevenTheme extends AdminTheme
{
	/**
	 * For theme exporting/importing
	 * @ignore
	 */
	const THEME_VERSION = '1.1';

	/**
	 * @ignore
	 */
//	private $_havetree = [];

	// 3.0+ will access these via parent-class
	protected $_errors = [];
	protected $_messages = [];

	/**
	 * Determine whether this is running on CMSMS 3.0+
	 */
	protected function currentversion(): bool
	{
		static $cvflag = null;
		if ($cvflag === null) {
			$cvflag = method_exists($this, 'RecordNotice');
		}
		return $cvflag;
	}

	/**
	 * Hook accumulator-function to nominate runtime resources, which
	 * will be included in the header of each displayed admin page
	 *
	 * @since 3.0
	 * @return 2-member array
	 * [0] = array of data for js vars, members like varname=>varvalue
	 * [1] = array of string(s) for includables
	 */
	public function AdminHeaderSetup(): array
	{
		list($vars, $add_list) = parent::AdminHeaderSetup();

		$incs = cms_installed_jquery(true, true, true, false);
/*		$url = cms_path_to_url($incs['jquicss']);
		$out = <<<EOS
<link rel="stylesheet" href="{$url}">

EOS;
*/
		$out = '';
		//back-compatible jQUI styling
		$after = <<<'EOS'
<link rel="stylesheet" href="themes/OneEleven/styles/default-cmsms/jquery-ui-1.12.1.custom.min.css">

EOS;
		// main css files might include relative URLs, so cannot be merged
//		if (!defined('CMS_ADMIN_PATH')) {
//			$config = cmsms()->GetConfig();
//			$admin_path = $config['admin_path'];
//			$rel = substr(__DIR__, strlen($admin_path) + 1);
//		} else {
		$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
		$rel_url = strtr($rel, '\\', '/');
		$n = strlen(__DIR__) + 1;
		$files = $this->get_styles();
		foreach ($files as $fp) {
			$extra = substr($fp, $n);
			$sufx = strtr($extra, '\\', '/');
			$after .= <<<EOS
<link rel="stylesheet" href="{$rel_url}/{$sufx}">

EOS;
		}
		add_page_headtext($after); // append this lot

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
		$jsm->queue_matchedfile('standard.js', 3, __DIR__.DIRECTORY_SEPARATOR.'includes');
		$out .= $jsm->page_content('', false, true);

		$add_list[] = $out;
//		$vars[] = anything needed ?;
		return [$vars, $add_list];
	}

	public function ShowHeader($title_name, $extra_lang_params = [], $link_text = '', $module_help_type = false)
	{
		if ($this->currentversion()) {
			parent::ShowHeader($title_name, $extra_lang_params, $link_text, $module_help_type);
		} else { // pre 3.0

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
					$modname = '';
					if (!empty($_GET['module'])) {
						$modname = trim($_GET['module']);
					} else {
						$tmp = explode(',', $_REQUEST['mact']);
						$modname = $tmp[0];
					}
					$orig_module_name = $modname;
					$modname = preg_replace('/([A-Z])/', '_$1', $modname);
					$modname = preg_replace('/_([A-Z])_/', '$1', $modname);
					if ($modname[0] == '_')
						$modname = substr($modname, 1);
				} else {
					if (($p = strrchr($title, ':')) !== false) {
						$title = substr($title, 0, $p);
					}
					// find the key of the item with this title.
					$title_key = $this->find_menuitem_by_title($title);
				}
			} // for-loop
		}

		} // pre-3.0
	}

	/**
	 * Get URL's for installed jquery, jquery-ui & related css
	 * Only for pre-3.0 operation
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
		$jqcss = CMS_ROOT_URL. '/lib/jquery/ccs/'.$p.'/'.$use;

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
		$jqui = CMS_ROOT_URL. '/lib/jquery/js/'.$use;

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
		$jqcore = CMS_ROOT_URL. '/lib/jquery/js/'.$use;

		return [$jqcss, $jqui, $jqcore];
	}

	public function display_login_page()
	{
		if ($this->currentversion()) {
			$auth_module = AppParams::get('loginmodule', ModuleOperations::STD_LOGIN_MODULE);
			$mod = Lone::get('ModuleOperations')->get_module_instance($auth_module, '', true);
			if ($mod) {
				$data = $mod->fetch_login_panel();
				if (isset($data['infomessage'])) { $data['message'] = $data['infomessage']; unset($data['infomessage']); }
				if (isset($data['warnmessage'])) { $data['warning'] = $data['warnmessage']; unset($data['warnmessage']); }
				if (isset($data['errmessage'])) { $data['error'] = $data['errmessage']; unset($data['errmessage']); }
			} else {
				exit('System error');
			}

			$smarty = Lone::get('Smarty');
			$smarty->assign($data);

			//extra shared parameters for the form TODO get from the current login-module
			$config = Lone::get('Config'); // for the inclusion
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

			$files = $this->get_styles();

			if (isset($tplvars['lang_dir']) && $tplvars['lang_dir'] == 'rtl') {
				if (0) { //no rtl in $files
					$tplvars['lang_dir'] == 'ltr';
				}
			} else {
				$dir = 'ltr';
				if (0) { //rtl in $files
					$dir = 'rtl';
				}
				$smarty->assign('lang_dir', $dir);
//TODO ensure	$smarty->assign('lang_code', AppParams::get('frontendlang'));
			}

			// scripts: jquery, jquery-ui
			$incs = cms_installed_jquery();
			$url = cms_path_to_url($incs['jquicss']);
			$out = <<<EOS
<link rel="stylesheet" href="$url">

EOS;
			$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
			$rel_url = strtr($rel, '\\', '/');
			$n = strlen(__DIR__) + 1;
			foreach ($files as $fp) {
				$extra = substr($fp, $n);
				$sufx = strtr($extra, '\\', '/');
				$out .= <<<EOS
<link rel="stylesheet" href="{$rel_url}/{$sufx}">

EOS;
			}
//			$nonce = get_csp_token(); //setup CSP header (result not used)
			$tpl = '<script src="%s"></script>'.PHP_EOL;
			$url = cms_path_to_url($incs['jqcore']);
			$out .= sprintf($tpl,$url);
			$url = cms_path_to_url($incs['jqui']);
			$out .= sprintf($tpl,$url);
			$out .= sprintf($tpl,'themes/OneEleven/includes/login.min.js');
		} else { // old CMSMS
			$gCms = cmsms();
			$smarty = $gCms->GetSmarty();
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

			if (NlsOperations::get_language_direction() == 'rtl') { // TODO for old CMSMS
				$smarty->assign('lang_dir', 'rtl');
				$dir = '-rtl';
			} else {
				$smarty->assign('lang_dir', 'ltr');
				$dir = '';
			}

			list($jqcss, $jqui, $jqcore) = $this->find_installed_jq();
			$out = <<<EOS
<link rel="stylesheet" href="$jqcss">
<link rel="stylesheet" href="themes/OneEleven/css/style{$dir}.css">
<link rel="stylesheet" href="loginstyle.php">
<script src="$jqcore"></script>
<script src="$jqui"></script>
<script src="themes/OneEleven/includes/login.min.js"></script>

EOS;
		} // pre 3.0

		$smarty->assign('header_includes', $out) //NOT into bottom (to avoid UI-flash)
		  ->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'layouts')
		  ->display('login.tpl');
	}

	/**
	 * @param string $section_name nav-menu-section name,
	 *  usually empty to use the whole menu
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_menu_page(string $section_name): ?string
	{
		$flag = $this->currentversion();

		// Get page-icons map in $_topaliases array
		include_once __DIR__.DIRECTORY_SEPARATOR.'function.pageicons.php';

		$smarty = cmsms()->GetSmarty(); // OR if $flag Lone::get('Smarty')
		if ($section_name) {
			$smarty->assign('section_name', $section_name);
			if ($flag) {
				$nodes = $this->get_navigation_tree($section_name, 0);
				$smarty->assign('pagetitle', $this->title);
			} else { // old CMSMS
				$nodes = $this->get_navigation_tree($section_name, -1, false);
				$smarty->assign('pagetitle', lang($section_name)); //CHECKME
			}
		} elseif ($flag) {
			$nodes = $this->get_navigation_tree(null, 3, 'root:view:dashboard');
		} else {
			$nodes = $this->get_navigation_tree(-1, 2, false);
		}
		foreach ($nodes as &$one) {
			$modname = $one['module'] ?? '';
			if ($modname) { // TODO support all relevant formats e.g. svg
				$ext = 'png';
				$path = cms_module_path($modname, true).DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'icon.png';
				if (!file_exists($path)) {
					$ext = 'gif';
					$path = substr($path, 0, -3) . 'gif';
				}
				if (file_exists($path)) {
					$one['img'] = cms_path_to_url($path);
				} else {
					$one['img'] = "themes/{$this->themeName}/images/icons/topfiles/modules.png";
				}
			} else {
				$nm = $one['name'];
				if (isset($_topaliases[$nm])) {
					$one['img'] = "themes/{$this->themeName}/images/icons/topfiles/{$_topaliases[$nm]}";
				}
			}
		}
		unset($one);
//		$this->_havetree = $nodes; //block further tree-data changes

		$config = cmsms()->GetConfig(); // OR if $flag Lone::get('Config');
		$smarty->assign('admin_url', $config['admin_url'])
		  ->assign('nodes', $nodes)
		  ->assign('theme', $this);

		// is the website down for maintenance?
		if (AppParams::get('site_downnow')) {
			$smarty->assign('is_sitedown', 1);
		}

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'layouts', -1);
		return $smarty->fetch('topcontent.tpl');
	}

	/**
	 * @param string $content page content to be processed
	 * @return string (or maybe null if $smarty->fetch() fails?)
	 */
	public function fetch_page(string $content): ?string
	{
		$flag = $this->currentversion();

		$smarty = cmsms()->GetSmarty(); // OR if $flag Lone::get('Smarty');
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
		$modname = $this->get_value('module_name');
		if (!$modname) {
			if ($flag) {
				$modname = RequestParameters::get_request_values('module');
			} elseif (isset($_REQUEST['mact'])) {
				$modname = explode(',', $_REQUEST['mact'])[0];
			}
		}
		$smarty->assign('module_name', $modname); // maybe null

		$module_help_type = $this->get_value('module_help_type');
		// module_help_url
		if ($modname && ($module_help_type || $module_help_type === null) &&
			!UserParams::get_for_user($userid,'hide_help_links', 0)) {
			if (($module_help_url = $this->get_value('module_help_url'))) {
				$smarty->assign('module_help_url', $module_help_url);
			}
		}

		// page title(s) and alias
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
		} elseif (!$title) {
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
//		} else {
//			$subtitle = TODO
		}
		if (!$title) $title = '';
		$smarty->assign('pagetitle', $title)
		  ->assign('subtitle', $subtitle)
		  ->assign('pagealias', munge_string_to_url($alias));

		// icon
		if ($modname && ($icon_url = $this->get_value('module_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="'.$modname.'" class="module-icon">';
		} elseif ($modname && $title) {
			$tag = $this->get_module_icon($modname, ['alt'=>$modname, 'class'=>'module-icon']);
		} elseif (($icon_url = $this->get_value('page_icon_url'))) {
			$tag = '<img src="'.$icon_url.'" alt="'.basename($icon_url).'" class="TODO">';
		} else {
			$name = $this->get_active('name');
			$tag = ($name) ? $this->DisplayImage("icons/topfiles/$name.png", $name) : '';
		}
		$smarty->assign('pageicon', $tag);

		$config = cmsms()->GetConfig(); // OR if flag Lone::get('Config');
		// site logo?
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
			$smarty->assign('mysettings', 1);
			$smarty->assign('myaccount', 1); //TODO maybe a separate check
		}

		// bookmarks UI
		if (UserParams::get_for_user($userid, 'bookmarks') && check_permission($userid, 'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks();
			$smarty->assign('marks', $marks);
		}

		$secureparam = CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];
		// other variables
		$smarty->assign('admin_url', $config['admin_url']);
		$smarty->assign('content', str_replace('</body></html>', '', $content));
		$smarty->assign('theme', $this);
		$smarty->assign('secureparam', $secureparam);
		$userops = Lone::get('UserOperations');
		$user = $userops->LoadUserByID($userid);
		$smarty->assign('username', $user->username); //TODO only if user != effective user
		// language attribute : prefer user-selected
		$lang = UserParams::get_for_user($userid, 'default_cms_language');
		if (!$lang) {
			$lang = NlsOperations::get_current_language();
		}
		if ($lang) {
			$lang = NlsOperations::get_lang_attribute($lang);
		} else {
			$lang = '';
		}
		$smarty->assign('lang_code', $lang);
		// language direction
		$lang = NlsOperations::get_current_language();
		$info = NlsOperations::get_language_info($lang);
		$lang_dir = $info->direction();
		if ($lang_dir == 'rtl') {
			if (!is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'style-rtl.css')) { // TODO or .min
				$lang_dir = 'ltr';
			}
		}
		$smarty->assign('lang_dir', $lang_dir);

		if ($flag) {
			$smarty->assign('header_includes', get_page_headtext());
			$smarty->assign('bottom_includes', get_page_foottext());
		} else { // old CMSMS
			// replicate AdminHeaderSetup(), with different js
			// no CSP-related attrs for html5shiv - old IE incapable!
			$dir = ($lang_dir == 'ltr') ? '' : '-rtl';
			list($jqcss, $jqui, $jqcore) = $this->find_installed_jq();
			$smarty->assign('header_includes', <<< EOS
<link rel="stylesheet" href="$jqcss">
<link rel="stylesheet" href="style.php?{$secureparam}">
<link rel="stylesheet" href="themes/OneEleven/css/style{$dir}.css">
<script src="$jqcore"></script>
<script src="$jqui"></script>
//TODO jquery ancillaries
<script src="themes/OneEleven/includes/standard.min.js"></script>
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

		$smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'layouts');
		return $smarty->fetch('pagetemplate.tpl');
	}

	// for pre-3.0 compatibility

	public function ShowErrors(/*mixed */$errors, string $get_var = ''): string
	{
/*		if ($this->currentversion()) {
			$this->RecordNotice('error', $errors, '', false, $get_var);
		} else {
*/
		// cache errors for use in the template.
		if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_errors[] = lang(cleanValue($one)); // pre-3.0
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
		return '<!-- OneEleven::ShowErrors() called -->';

//		} //pre 3.0
	}

	public function ShowMessage(/*mixed */$message, string $get_var = ''): string
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
		return '';
//		} // pre 3.0
	}

	public function do_toppage(string $section_name)
	{
		echo $this->fetch_menu_page($section_name);
	}

	public function do_login(array $params)
	{
		$this->display_login_page($params);
	}

	public function postprocess(string $content)
	{
		return $this->fetch_page($content);
	}

	public function get_my_alerts(): array
	{
		return Alert::load_my_alerts();
	}
}
