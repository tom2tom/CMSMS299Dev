<?php
#------------------------------------------------------------------------
# Purpose: An Admin theme for CMS Made Simple
# Author: CMS Made Simple Development Team
# Plugins page: https://cmsmadesimple.org
#------------------------------------------------------------------------
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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#------------------------------------------------------------------------
# Based on the OneEleven/Marigold theme for CMS Made Simple tm
# Equally released under GNU General Public License version 2+
# Original author: Goran Ilic
#------------------------------------------------------------------------
namespace CMSMS;

use CmsApp;
use CMSMS\AdminAlerts\Alert;
use CMSMS\AdminTheme;
use CMSMS\Config;
use CMSMS\NlsOperations;
use CMSMS\UserOperations;
use CMSMS\UserParams;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const THEME_NAME;
use function check_permission;
use function sanitizeVal; //2.99+
use function cms_join_path;
use function cmsms;
use function get_site_preference;
use function get_userid;
use function lang;
use function munge_string_to_url;

define ( 'THEME_NAME', 'AdminLTE' );

class LTETheme extends AdminTheme
{
    protected $_errors = array();

    protected $_messages = array();

    public function ShowErrors($errors, $get_var = '') {

        // cache errors for use in the template.
        if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
            if (is_array($_GET[$get_var])) {
                foreach ($_GET[$get_var] as $one) {
                    $this->_errors[] = lang(sanitizeVal($one));
                }
            } else {
                $this->_errors[] = lang(sanitizeVal($_GET[$get_var]));
            }
        } else if (is_array($errors)) {
            foreach ($errors as $one) {
                $this->_errors[] = $one;
            }
        } else if (is_string($errors)) {
            $this->_errors[] = $errors;
        }
        return '<!-- ShowErrors() called -->';
    }

    public function ShowMessage($message, $get_var = '') {

        // cache message for use in the template.
        if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
            if (is_array($_GET[$get_var])) {
                foreach ($_GET[$get_var] as $one) {
                    $this->_messages[] = lang(sanitizeVal($one));
                }
            } else {
                $this->_messages[] = lang(sanitizeVal($_GET[$get_var]));
            }
        } else if (is_array($message)) {
            foreach ($message as $one) {
                $this->_messages[] = $one;
            }
        } else if (is_string($message)) {
            $this->_messages[] = $message;
        }
    }

    public function ShowHeader($title_name, $extra_lang_params = array(), $link_text = '', $module_help_type = FALSE) {

        if ($title_name) $this->set_value('pagetitle', $title_name);

        if (is_array($extra_lang_params) && count($extra_lang_params)) $this->set_value('extra_lang_params', $extra_lang_params);
        $this->set_value('module_help_type', $module_help_type);

        $config = Config::get_instance();

        $module = '';
        if (isset($_REQUEST['module'])) {
            $module = $_REQUEST['module'];
        } else if (isset($_REQUEST['mact'])) {
            $tmp = explode(',', $_REQUEST['mact']);
            $module = $tmp[0];
        }

        // get the image url.
        $icon = "modules/{$module}/images/icon.gif";
        $path = cms_join_path($config['root_path'], $icon);
        if (file_exists($path)) {
            $url = $config->smart_root_url() . '/' . $icon;
            $this->set_value('module_icon_url', $url);
        }

        if ($module_help_type) {
            // set the module help url (this should be supplied TO the theme)
            $module_help_url = $this->get_module_help_url();
            $this->set_value('module_help_url', $module_help_url);
        }

        $bc = $this->get_breadcrumbs();
        if ($bc) {
            for ($i = 0; $i < count($bc); $i++) {
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
                    $module_name = preg_replace('/([A-Z])/', "_$1", $module_name);
                    $module_name = preg_replace('/_([A-Z])_/', "$1", $module_name);
                    if ($module_name[0] == '_')
                        $module_name = substr($module_name, 1);
                } else {
                    if (($p = strrchr($title, ':')) !== FALSE) {
                        $title = substr($title, 0, $p);
                    }
                    // find the key of the item with this title.
                    $title_key = $this->find_menuitem_by_title($title);
                }
            }// for loop.
        }
    }

  public function do_header(){}

  public function do_footer(){}

  public function do_toppage($section_name)
  {
    $smarty = CmsApp::get_instance()->GetSmarty();
    $otd = $smarty->template_dir;
    $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

    if($section_name)
    {
      $page_title = lang($section_name);
      $nodes = $this->get_navigation_tree($section_name, -1, FALSE);
    }
    else
    {
      $page_title = '';
      $section_name = '';
      $nodes = $this->get_navigation_tree(-1, 2, FALSE);
    }

    $config = cmsms()->GetConfig();
    $smarty->assign('section_name', $section_name);
    $smarty->assign('page_title', $page_title);
    $smarty->assign('nodes', $nodes);
    $smarty->assign('config', $config );
    $smarty->assign('admin_url', $config['admin_url']);
    $smarty->assign('theme', $this);
    $smarty->assign('theme_path', __DIR__);
    $smarty->assign('theme_url', $config['admin_url'] . '/themes/' . THEME_NAME);

    // is the website set down for maintenance?
    if( get_site_preference('enablesitedownmessage') == '1' )
        $smarty->assign('is_sitedown', 'true');

    $_contents = $smarty->display('topcontent.tpl');
    $smarty->template_dir = $otd;

    echo $_contents;
  }


    public function do_login($params = null)
    {
        // by default we're gonna grab the theme name
        $config = Config::get_instance();
		$smarty = CmsApp::get_instance()->GetSmarty();
        $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

        global $error, $warningLogin, $acceptLogin, $changepwhash; # todo: check if needed (JM)

        $fn = $config['admin_path']."/themes/".$this->themeName."/login.php";
        include($fn);

        $smarty->assign('config', $config );
        $smarty->assign('admin_url', $config['admin_url']);
        $smarty->assign('theme', $this);
        $smarty->assign('theme_path', __DIR__);
        $smarty->assign('theme_url', $config['admin_url'] . '/themes/' . THEME_NAME);

        $smarty->assign('lang', get_site_preference('frontendlang'));
        $_contents = $smarty->display('login.tpl');
        return $_contents;
    }

    public function do_page($html) {
        $smarty = CmsApp::get_instance()->GetSmarty();
        $otd = $smarty->template_dir;
      $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        $module_help_type = $this->get_value('module_help_type');

        // get a page title
        $title = $this->get_value('pagetitle');
        $alias = $this->get_value('pagetitle');
        if ($title) {
            if (!$module_help_type) {
                // if not doing module help, translate the string.
                $extra = $this->get_value('extra_lang_params');
                if (!$extra)
                    $extra = array();
                $title = lang($title, $extra);
            }
        } else {
          if( $this->title ) {
            $title = $this->title;
          }
          else {
            // no title, get one from the breadcrumbs.
            $bc = $this->get_breadcrumbs();
            if (is_array($bc) && count($bc)) {
              $title = $bc[count($bc) - 1]['title'];
            }
          }
        }
        // page title and alias
        $smarty->assign('page_title', $title);
        $smarty->assign('page_subtitle',$this->subtitle);
        $smarty->assign('pagealias', munge_string_to_url($alias));

        // module name?
        if (($module_name = $this->get_value('module_name')))
            $smarty->assign('module_name', $module_name);

        // module icon?
        if (($module_icon_url = $this->get_value('module_icon_url')))
            $smarty->assign('module_icon_url', $module_icon_url);

        // module_help_url
        if( !UserParams::get_for_user(get_userid(),'hide_help_links',0) ) {
            if (($module_help_url = $this->get_value('module_help_url')))
                $smarty->assign('module_help_url', $module_help_url);
        }

        // my preferences
        if (check_permission(get_userid(),'Manage My Settings'))
          $smarty->assign('myaccount', 1);

        // if bookmarks
        if (UserParams::get_for_user(get_userid(), 'bookmarks') && check_permission(get_userid(),'Manage My Bookmarks'))
        {
          $all_marks = $this->get_bookmarks();
          $marks = [];
          $marks_cntrls = [];

          foreach($all_marks as $one)
          {
            if($one->bookmark_id > -1)
            {
              $marks[] = $one;
            }
            else
            {
              $marks_cntrls[] = $one;
            }
          }

            $smarty->assign('marks', $marks);
            $smarty->assign('marks_cntrls', $marks_cntrls);
        }

        $smarty->assign('headertext', $this->get_headtext());
        $smarty->assign('footertext', $this->get_footertext());

        // and some other common variables
        $config = cmsms()->GetConfig();
        $smarty->assign('content', str_replace('</body></html>', '', $html));
        $smarty->assign('config', $config);
        $smarty->assign('admin_url', $config['admin_url']);
        $smarty->assign('theme', $this);
        $smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
        $userops = UserOperations::get_instance();
        $smarty->assign('user', $userops->LoadUserByID(get_userid()));
        // get user selected language
        $smarty->assign('lang',UserParams::get_for_user(get_userid(), 'default_cms_language'));
        // get language direction
        $lang = NlsOperations::get_current_language();
        $info = NlsOperations::get_language_info($lang);
        $smarty->assign('theme_path', __DIR__);
        $smarty->assign('theme_url', $config['admin_url'] . '/themes/' . THEME_NAME);
        $smarty->assign('lang_dir', $info->direction());

        if (is_array($this->_errors) && count($this->_errors))
            $smarty->assign('errors', $this->_errors);

        if (is_array($this->_messages) && count($this->_messages))
            $smarty->assign('messages', $this->_messages);

        // is the website set down for maintenance?
        if( get_site_preference('enablesitedownmessage') == '1' )
            $smarty->assign('is_sitedown', 'true');

        $_contents = $smarty->fetch('pagetemplate.tpl');
        $smarty->template_dir = $otd;
        return $_contents;
    }

    public function get_my_alerts()
    {
        return Alert::load_my_alerts();
    }

  /**
   * Module Help and misc
   */
  public function GetModuleAbout($modinstance)
  {
    $smarty               = CmsApp::get_instance()->GetSmarty();
    $otd                  = $smarty->template_dir;
    $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

    $author               = $modinstance->GetAuthor();
    $author_email         = $modinstance->GetAuthorEmail();
    $version              = $modinstance->GetVersion();
    $changelog            = $modinstance->GetChangeLog();

    $smarty->assign('author', $author);
    $smarty->assign('author_email', $author_email);
    $smarty->assign('version', $version);
    $smarty->assign('changelog', $changelog);

    $ret                  = $smarty->fetch('module_about.tpl');
    $smarty->template_dir = $otd;

    return $ret;
  }

  public function GetModuleHelp($modinstance)
  {
    $smarty               = CmsApp::get_instance()->GetSmarty();
    $otd                  = $smarty->template_dir;
    $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

    @ob_start();
    echo $modinstance->GetHelp();
    $help_str = @ob_get_contents();
    @ob_end_clean();


    $dependencies = $modinstance->GetDependencies();
    $paramarray   = $modinstance->GetParameters();

    $smarty->assign('help', $help_str);
    $smarty->assign('dependencies', $dependencies);
    $smarty->assign('parammeters', $paramarray);

    $ret                  = $smarty->fetch('module_help.tpl');
    $smarty->template_dir = $otd;

    return $ret;
  }

  /**
   * ------------------------------------------------------------------
   * Tab Functions
   * ------------------------------------------------------------------
   */
/*
  public final function StartTabHeaders()
  {
    return '<div class="card"><div class="card-header"><ul class="nav nav-tabs" id="page_tabs" role="tablist">';
  }

  public final function SetTabHeader($tabid, $title, $active = FALSE)
  {
    $a    = $active ? 'active' : '';
    $as   = $active ? 'true' : 'false';
    $html = '<li class="nav-item">';
    $html .= '<a class="nav-link ' . $a . '" ';
    $html .= 'id="' . $tabid . '-tab" data-toggle="tab" ';
    $html .= 'href="#' . $tabid . '" role="tab" ';
    $html .= 'aria-controls="' . $tabid . '" ';
    $html .= '>' . $title . '</a></li>';
    //$html .= 'aria-selected="' . $as . '">' . $title . '</a></li>';

    return $html;
  }

  public final function EndTabHeaders()
  {
    return "</ul><!-- EndTabHeaders -->";
  }

  public final function StartTabContent()
  {
    return '<!-- Tab panes --><div class="tab-content">';
  }

  public final function EndTabContent()
  {
    return '</div></div></div> <!-- EndTabContent -->';
  }

  public final function StartTab($tabid, $params = array(), $active = FALSE)
  {
    $a    = $active ? 'active' : '';
    return '<div class="tab-pane ' . $a . '" id="' . $tabid . '" role="tabpanel" aria-labelledby="' . $tabid . '-tab">';
  }

  public final function EndTab()
  {
    return '</div> <!-- EndTab -->';
  }
  */
} // end of class
