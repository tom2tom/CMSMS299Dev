<?php
#procedure to display and modify the user's admin settings/preferences
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use \CMSMS\internal\module_meta;

$CMS_ADMIN_PAGE = 1;
$CMS_TOP_MENU = 'admin';
$CMS_ADMIN_TITLE = 'mysettings';

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if (isset($_POST['cancel'])) {
  redirect('index.php'.$urlext);
}

$userid = get_userid();

$themeObject = cms_utils::get_theme_object();

if (!check_permission($userid,'Manage My Settings')) {
//TODO some immediate popup  lang('needpermissionto','"Manage My Settings"'));
  return;
}

$userobj = UserOperations::get_instance()->LoadUserByID($userid); // <- Safe to do, cause if $userid fails, it redirects automatically to login.
$db = cmsms()->GetDb();

/**
 * Submit account
 *
 * NOTE: Assumes that we successfully acquired user object.
 */
if (isset($_POST['submit'])) {
  cleanArray($_POST);
  // Get values from request and drive em to variables
  $wysiwyg = $_POST['wysiwyg'];
  $ce_navdisplay = $_POST['ce_navdisplay'];
  $syntaxhighlighter = $_POST['syntaxhighlighter'];
  $default_cms_language = $_POST['default_cms_language'];
  $old_default_cms_lang = $_POST['old_default_cms_lang'];
  $admintheme = $_POST['admintheme'];
  $bookmarks = (!empty($_POST['bookmarks'])) ? 1 : 0;
  $indent = (!empty($_POST['indent'])) ? 1 : 0;
  $paging = (!empty($_POST['paging'])) ? 1 : 0;
  $date_format_string = $_POST['date_format_string'];
  $default_parent = (int)$_POST['parent_id'];
  $homepage = $_POST['homepage'];
  $hide_help_links = (!empty($_POST['hide_help_links'])) ? 1 : 0;

  // Set prefs
  cms_userprefs::set_for_user($userid, 'wysiwyg', $wysiwyg);
  cms_userprefs::set_for_user($userid, 'ce_navdisplay', $ce_navdisplay);
  cms_userprefs::set_for_user($userid, 'syntaxhighlighter', $syntaxhighlighter);
  cms_userprefs::set_for_user($userid, 'default_cms_language', $default_cms_language);
  cms_userprefs::set_for_user($userid, 'admintheme', $admintheme);
  cms_userprefs::set_for_user($userid, 'bookmarks', $bookmarks);
  cms_userprefs::set_for_user($userid, 'hide_help_links', $hide_help_links);
  cms_userprefs::set_for_user($userid, 'indent', $indent);
  cms_userprefs::set_for_user($userid, 'paging', $paging);
  cms_userprefs::set_for_user($userid, 'date_format_string', $date_format_string);
  cms_userprefs::set_for_user($userid, 'default_parent', $default_parent);
  cms_userprefs::set_for_user($userid, 'homepage', $homepage);

  // Audit, message, cleanup
  audit($userid, 'Admin Username: '.$userobj->username, 'Edited');
  $themeObject->RecordNotice('success', lang('prefsupdated'));
  cmsms()->clear_cached_files();
} // end of prefs submit

/**
 * Get current preferences
 */
$wysiwyg = cms_userprefs::get_for_user($userid, 'wysiwyg');
$ce_navdisplay = cms_userprefs::get_for_user($userid,'ce_navdisplay');
$syntaxhighlighter = cms_userprefs::get_for_user($userid, 'syntaxhighlighter');
$default_cms_language = cms_userprefs::get_for_user($userid, 'default_cms_language');
$old_default_cms_lang = $default_cms_language;
$admintheme = cms_userprefs::get_for_user($userid, 'admintheme', CmsAdminThemeBase::GetDefaultTheme());
$bookmarks = cms_userprefs::get_for_user($userid, 'bookmarks', 0);
$indent = cms_userprefs::get_for_user($userid, 'indent', true);
$paging = cms_userprefs::get_for_user($userid, 'paging', 0);
$date_format_string = cms_userprefs::get_for_user($userid, 'date_format_string', '%x %X');
$default_parent = cms_userprefs::get_for_user($userid, 'default_parent', -2);
$homepage = cms_userprefs::get_for_user($userid, 'homepage');
$hide_help_links = cms_userprefs::get_for_user($userid, 'hide_help_links', 0);

/**
 * Build page
 */

$contentops = cmsms()->GetContentOperations();
$smarty = CMSMS\internal\Smarty::get_instance();

# WYSIWYG editors
$tmp = module_meta::get_instance()->module_list_by_capability(CmsCoreCapabilities::WYSIWYG_MODULE);
$n = count($tmp);
$tmp2 = [-1 => lang('none')];
for ($i = 0; $i < $n; ++$i) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}

$smarty -> assign('wysiwyg_opts', $tmp2);

# Syntaxhighlighters
$tmp = module_meta::get_instance()->module_list_by_capability(CmsCoreCapabilities::SYNTAX_MODULE);
$n = count($tmp);
$tmp2 = [-1 => lang('none')];
for ($i = 0; $i < $n; ++$i) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}

$smarty->assign('syntax_opts', $tmp2);

# Admin themes
$smarty->assign('themes_opts',CmsAdminThemeBase::GetAvailableThemes());

# Modules
$allmodules = ModuleOperations::get_instance()->GetInstalledModules();
$modules = [];
foreach ((array)$allmodules as $onemodule) {
  $modules[$onemodule] = $onemodule;
}

# Prefs
$tmp = [10 => 10, 20 => 20, 50 => 50, 100 => 100];

$selfurl = basename(__FILE__);

$smarty->assign([
  'admintheme'=>$admintheme,
  'backurl'=>$themeObject->backUrl(),
  'bookmarks'=>$bookmarks,
  'ce_navdisplay'=>$ce_navdisplay,
  'date_format_string'=>$date_format_string,
  'default_cms_language'=>$default_cms_language,
  'default_parent'=>$contentops->CreateHierarchyDropdown(0, $default_parent, 'parent_id', 0, 1),
  'hide_help_links'=>$hide_help_links,
  'homepage'=>$themeObject->GetAdminPageDropdown('homepage', $homepage, 'homepage'),
  'indent'=>$indent,
  'language_opts'=>get_language_list(),
  'manageaccount'=>true,
  'module_opts'=>$modules,
  'old_default_cms_lang'=>$old_default_cms_lang,
  'pagelimit_opts'=>$tmp,
  'paging'=>$paging,
  'selfurl' => $selfurl,
  'syntaxhighlighter'=>$syntaxhighlighter,
  'urlext' => $urlext,
  'userobj'=>$userobj,
  'wysiwyg'=>$wysiwyg,
]);

include_once 'header.php';
$smarty->display('mysettings.tpl');
include_once 'footer.php';
