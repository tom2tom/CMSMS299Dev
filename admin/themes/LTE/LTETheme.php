<?php
/*
An admin theme for CMS Made Simple
Based on the OneEleven/Marigold themes for CMS Made Simple
Original author: Goran Ilic

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS; //2.99+

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\StylesMerger;
use CMSMS\UserParams;
use const CMS_ADMIN_PATH;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function check_permission;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_path_to_url;
use function get_userid;
use function lang;
use function munge_string_to_url;

class LTETheme extends AdminTheme
{
    const THEME_NAME = 'LTE';
    const THEME_VERSION = '0.3';

    public function AdminHeaderSetup()
    {
        list($vars, $add_list) = parent::AdminHeaderSetup();

		$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
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
        $modinst = AppSingle::ModuleOperations()->get_module_instance($auth_module, '', true);
        if ($modinst) {
            $data = $modinst->fetch_login_panel();
        } else {
            die('System error');
        }

        $smarty = AppSingle::Smarty();
        $smarty->assign($data);

		//extra shared parameters for the form TODO get from the current login-module
        $config = AppSingle::Config(); // for the inclusion
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
		$fn = 'style';
		if (NlsOperations::get_language_direction() == 'rtl') {
			if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
				$fn .= '-rtl';
			}
		}
        // css: jquery-ui and scripts: jquery, jquery-ui
        $incs = cms_installed_jquery();
        $url = cms_path_to_url($incs['jquicss']);
        $dir = ''; //TODO or '-rtl'
        $out = <<<EOS
<link rel="stylesheet" href="$url" />
<link rel="stylesheet" href="themes/LTE/css/{$fn}.css" />

EOS;
//        get_csp_token(); //setup CSP header (result not used)
        $tpl = '<script type="text/javascript" src="%s"></script>'.PHP_EOL;
        $url = cms_path_to_url($incs['jqcore']);
        $out .= sprintf($tpl, $url);
        $url = cms_path_to_url($incs['jqui']);
        $out .= sprintf($tpl, $url);

        $smarty->assign('header_includes', $out)
          ->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates')
          ->display('login.tpl');
    }

    public function fetch_menu_page($section_name)
    {
        if ($section_name) {
            $page_title = lang($section_name);
            $nodes = $this->get_navigation_tree($section_name, -1, FALSE);
        } else {
            $page_title = '';
            $section_name = '';
            $nodes = $this->get_navigation_tree(-1, 2, FALSE);
        }

        $config = AppSingle::Config();
        $smarty = AppSingle::Smarty();

        $smarty->assign([
            'admin_url' => $config['admin_url'],
            'config' => $config,
            'nodes' => $nodes,
            'page_title' => $page_title,
            'section_name' => $section_name,
            'theme' => $this,
            'theme_path' => __DIR__,
            'theme_url' => $config['admin_url'] . '/themes/' . self::THEME_NAME,
        ]);

        // is the website set down for maintenance?
        if (AppParams::get('enablesitedownmessage') == '1') {
            $smarty->assign('is_sitedown', 'true');
        }

        $smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
        return $smarty->fetch('topcontent.tpl');
    }

    public function fetch_page($html)
    {
		/* possibly-cached value-names
		'pagetitle'
		'extra_lang_params'
		'module_help_type'
		'module_help_url'
		'pageicon'
		'page_crumbs'
		*/
        $module_help_type = $this->get_value('module_help_type');

        // get a page title
        $alias = $title = $this->get_value('pagetitle');
        if ($title) {
            if (!$module_help_type) {
                // if not doing module help, translate the string.
                $extra = $this->get_value('extra_lang_params');
                if (!$extra) {
                    $extra = [];
                }
                $title = lang($title, $extra);
            }
        } elseif ($this->title) {
            $title = $this->title;
        } else {
            // no title, get one from the breadcrumbs.
            $bc = $this->get_breadcrumbs();
            if (is_array($bc) && count($bc)) {
                $title = $bc[count($bc) - 1]['title'];
            }
        }

        $smarty = AppSingle::Smarty();
        // page title and alias
        $smarty->assign('page_title', $title)
         ->assign('page_subtitle',$this->subtitle)
         ->assign('pagealias', (($alias) ? munge_string_to_url($alias) : ''));

        // module name?
        if (($module_name = $this->get_value('module_name'))) {
            $smarty->assign('module_name', $module_name); }

        // module icon?
        if (($module_icon_url = $this->get_value('module_icon_url'))) {
            $smarty->assign('module_icon_url', $module_icon_url); }

        $userid = get_userid();
        // module_help_url
        if( !UserParams::get_for_user($userid,'hide_help_links',0) ) {
            if (($module_help_url = $this->get_value('module_help_url'))) {
                $smarty->assign('module_help_url', $module_help_url); }
        }

        // my preferences
        if (check_permission($userid,'Manage My Settings')) {
            $smarty->assign('myaccount', 1); }

        // if bookmarks
        if (UserParams::get_for_user($userid, 'bookmarks') && check_permission($userid, 'Manage My Bookmarks')) {
            $all_marks = $this->get_bookmarks();
            $marks = [];
            $marks_cntrls = [];

            foreach ($all_marks as $one) {
                if ($one->bookmark_id > -1) {
                    $marks[] = $one;
                } else {
                    $marks_cntrls[] = $one;
                }
            }

            $smarty->assign([
                'marks' => $marks,
                'marks_cntrls' => $marks_cntrls,
            ]);
        }

        $smarty->assign('content', str_replace('</body></html>', '', $html))
         ->assign('headertext', $this->get_headtext())
         ->assign('footertext', $this->get_footertext());

        // and some other common variables
        $config = AppSingle::Config();
        $smarty->assign([
            'config' => $config,
            'admin_url' => $config['admin_url'],
            'theme' => $this,
            'theme_path' => __DIR__,
            'theme_url' => $config['admin_url'] . '/themes/' . self::THEME_NAME,
            'secureparam' => CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY],
        ]);

        $userops = AppSingle::UserOperations();
        $smarty->assign('user', $userops->LoadUserByID($userid));
        // get user selected language
        $smarty->assign('lang', UserParams::get_for_user($userid, 'default_cms_language'));
        // get language direction
        $lang = NlsOperations::get_current_language();
        $info = NlsOperations::get_language_info($lang);
        $smarty->assign('lang_dir', $info->direction());

        // is the website set down for maintenance?
        if (AppParams::get('enablesitedownmessage') == '1') {
            $smarty->assign('is_sitedown', 'true'); }

        $smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
        return $smarty->fetch('pagetemplate.tpl');
    }
} // end of class
