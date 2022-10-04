<?php
/*
An admin theme for CMS Made Simple
Based on the OneEleven/Marigold themes for CMS Made Simple
Original author: Goran Ilic

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS; //3.0+

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\LangOperations;
use CMSMS\Lone;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\RequestParameters;
use CMSMS\ScriptsMerger;
use CMSMS\StylesMerger;
use CMSMS\UserParams;
use CMSMS\Utils;
use const CMS_ADMIN_PATH;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function _la;
use function add_page_headtext;
use function check_permission;
use function cms_installed_jquery;
use function cms_join_path;
use function cms_path_to_url;
use function CMSMS\get_page_foottext;
use function CMSMS\get_page_headtext;
use function get_userid;
use function munge_string_to_url;

class LTETheme extends AdminTheme
{
    const THEME_NAME = 'LTE';
    const THEME_VERSION = '0.3';

    public function AdminHeaderSetup()
    {
        list($vars, $add_list) = parent::AdminHeaderSetup();

        $incs = cms_installed_jquery(true, true, true, true);

        $csm = new StylesMerger();
        $csm->queue_matchedfile('normalize.css', 1);
        $csm->queue_matchedfile('grid-960.css', 2); //for modules, deprecated since 3.0
        $out = $csm->page_content();

        // jQUI css does, and theme-specific css files might, include relative URLs, so cannot be merged
        $url = cms_path_to_url($incs['jquicss']);
        $out .= <<<EOS
<link rel="stylesheet" href="$url">

EOS;
        $rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
        $rel_url = strtr($rel, '\\', '/');
        $n = strlen(__DIR__) + 1;
        $files = $this->get_styles();
        $after = '';
        foreach ($files as $fp) {
            // OR $csm->queue_matchedfile( );
            $extra = substr($fp, $n);
            $sufx = strtr($extra, '\\', '/');
            $after .= <<<EOS
<link rel="stylesheet" href="{$rel_url}/{$sufx}">

EOS;
        }
        add_page_headtext($after); // append this lot

        $jsm = new ScriptsMerger();
        $jsm->queue_file($incs['jqcore'], 1);
//      if (CMS_DEBUG) {
        $jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this or keep if (CMS_DEBUG)
//      }
        $jsm->queue_file($incs['jqui'], 1);
        $jsm->queue_matchedfile('jquery.cmsms_admin.js', 2);
        $out .= $jsm->page_content();
        $jsm->reset(); // start another merger-file
        $jsm->queue_matchedfile('jquery.ui.touch-punch.js', 1);
        $jsm->queue_matchedfile('jquery.toast.js', 1);
        $jsm->queue_matchedfile('standard.js', 3, __DIR__.DIRECTORY_SEPARATOR.'includes');
        $out .= $jsm->page_content('', false, true);

        $add_list[] = $out;
//      $vars[] = anything needed ?;
        return [$vars, $add_list];
    }

    public function display_login_page()
    {
        $auth_module = AppParams::get('loginmodule', ModuleOperations::STD_LOGIN_MODULE);
        $mod = Lone::get('ModuleOperations')->get_module_instance($auth_module, '', true);
        if ($mod) {
            $data = $mod->fetch_login_panel();
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
        $smarty->assign('theme_url', $config['admin_url'] . '/themes/' . self::THEME_NAME);

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
            if (is_file(__DIR__.DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
                $fn .= '-rtl';
            }
        }
        // css: jquery-ui and scripts: jquery, jquery-ui
        $incs = cms_installed_jquery();
        $url = cms_path_to_url($incs['jquicss']);
        $dir = ''; //TODO or '-rtl'
        // OR $csm->queue_matchedfile( );
        $out = <<<EOS
<link rel="stylesheet" href="$url">
<link rel="stylesheet" href="themes/LTE/styles/{$fn}.css">

EOS;
//        get_csp_token(); //setup CSP header (result not used)
        $tpl = '<script type="text/javascript" src="%s"></script>'.PHP_EOL;
        $url = cms_path_to_url($incs['jqcore']);
        $out .= sprintf($tpl, $url)."\n";
        $url = cms_path_to_url($incs['jqui']);
        $out .= sprintf($tpl, $url)."\n";
        $smarty->assign('header_includes', $out);

        // site logo?
        $sitelogo = AppParams::get('site_logo');
        if ($sitelogo) {
            if (!preg_match('~^\w*:?//~', $sitelogo)) {
                $sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
            }
            $smarty->assign('sitelogo', $sitelogo);
        }

        $smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'layouts', -1)
          ->display('login.tpl');
    }

    public function fetch_menu_page($section_name)
    {
        if ($section_name) {
            $page_title = _la($section_name);
            $nodes = $this->get_navigation_tree($section_name, -1, FALSE);
        } else {
            $page_title = '';
            $section_name = '';
            $nodes = $this->get_navigation_tree(-1, 2, FALSE);
        }

        $config = Lone::get('Config');
        $smarty = Lone::get('Smarty');

        // custom js
        $js = <<<'EOS'
<script type="text/javascript">
//<![CDATA[
$(function() {
  // admin home page
  $('#topcontent_wrap').addClass('row');
  $('.dashboard-box').addClass('col-lg-3 col-6 card');
  $('.dashboard-inner').addClass('card-body');
});
//]]>
</script>
EOS;
        add_page_headtext($js);

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

        $smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'layouts', -1);
        return $smarty->fetch('topcontent.tpl');
    }

    public function fetch_page($html)
    {
        // setup titles etc
//      $tree =
        $this->get_navigation_tree(); //TODO if section

        $smarty = Lone::get('Smarty');
        $userid = get_userid(false);

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
            $modname = RequestParameters::get_request_values('module');
            $this->set_value('module_name', $modname);
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
                $title = _la($title, $extra);
            }
//           $subtitle = TODO
        } elseif (!$title) {
            $title = $this->get_active_title(); // try for the active-menu-item title
            if ($title) {
                $subtitle = $this->subtitle;
            } elseif ($modname) {
                $mod = Utils::get_module($modname);
                $title = $mod->GetFriendlyName();
                $subtitle = $mod->GetAdminDescription();
/*          } else {
                // no title, get one from the breadcrumbs.
                $bc = $this->get_breadcrumbs();
                if ($bc) {
                    $title = $bc[count($bc) - 1]['title'];
                }
*/
            }
//        } else {
//           $subtitle = TODO
        }
        $smarty->assign('page_title', $title)
         ->assign('page_subtitle', $this->subtitle)
         ->assign('pagealias', (($alias) ? munge_string_to_url($alias) : ''));

        // icon
        if ($modname && ($icon_url = $this->get_value('module_icon_url'))) {
            $tag = '<img src="'.$icon_url.'" alt="'.$modname.'" class="module-icon">';
        } elseif ($modname && $title) {
            $tag = $this->get_module_icon($modname, ['alt'=>$modname, 'class'=>'module-icon']);
        } elseif (($icon_url = $this->get_value('page_icon_url'))) {
            $tag = '<img src="'.$icon_url.'" alt="'.basename($icon_url).'" class="TODO">';
        } else {
            $name = $this->get_active('name');
            $tag = ''; // TODO icon for admin operation func($name) ?
        }
        $smarty->assign('pageicon', $tag);

        $config = Lone::get('Config');
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
            $smarty->assign('mysettings', 1)
            ->assign('myaccount', 1); //TODO maybe a separate check
        }

        // bookmarks UI
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

        // custom js
        $js = <<<'EOS'
<script type="text/javascript" src="themes/LTE/includes/jquery.overlayScrollbars.min.js"></script>
<script type="text/javascript">
//<![CDATA[
$(function() {
  // resolve conflict between jQueryUI tooltip and Bootstrap tooltip
  $.widget.bridge('uibutton', $.ui.button);

  // text blocks
  $('.pagewarning').addClass('callout callout-danger');
  $('.warning').addClass('callout callout-danger');
  $('.text').addClass('callout callout-warning');
  $('.quote').addClass('callout callout-info');
  $('.note').addClass('callout callout-info');
  $('.information').addClass('callout callout-info');

//$('.green').addClass('callout callout-info');
//$('.red').addClass('alert alert-danger alert-dismissible');

  // tables
  $('.pagetable').addClass('table table-striped table-hover');
//$('.pageicon').addClass('');

  // buttons
//  $('.pagebutton').addClass('btn-sm btn-primary');
//  $('input[type="submit"]').addClass('btn-sm btn-primary');

  // admin home page
//$('#topcontent_wrap').addClass('row');
//$('.dashboard-box').addClass('col-lg-3 col-6 card');
//$('.dashboard-inner').addClass('card-body');

  // scrollbars
  $('body').overlayScrollbars();
//$('body').Layout('fixLayoutHeight');

  // scrollbar for shortcuts bar
  $('#shorcuts-crol-sidebar').overlayScrollbars({className:'os-theme-light'});
});
//]]>
</script>
EOS;
        add_page_headtext($js);

        $smarty->assign('content', str_replace('</body></html>', '', $html))
         ->assign('headertext', get_page_headtext())
         ->assign('footertext', get_page_foottext());

        // and some other common variables
        $smarty->assign([
            'config' => $config,
            'admin_url' => $config['admin_url'],
            'theme' => $this,
            'theme_path' => __DIR__,
            'theme_url' => $config['admin_url'] . '/themes/' . self::THEME_NAME,
            'secureparam' => CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY],
        ]);

        $userops = Lone::get('UserOperations');
        $smarty->assign('user', $userops->LoadUserByID($userid));
        // get user selected language
        $smarty->assign('lang', UserParams::get_for_user($userid, 'default_cms_language'));
        // get language direction
        $lang = NlsOperations::get_current_language();
        $info = NlsOperations::get_language_info($lang);
        $smarty->assign('lang_dir', $info->direction());

        // is the website set down for maintenance?
        if (AppParams::get('enablesitedownmessage') == '1') {
            $smarty->assign('is_sitedown', 'true');
        }
        $smarty->addTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'layouts', -1);
        return $smarty->fetch('pagetemplate.tpl');
    }
} // end of class
