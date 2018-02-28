<?php
#procedure to display and modify the user's account data
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
$CMS_ADMIN_TITLE = 'myaccount';

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST['cancel'])) {
	redirect('index.php'.$urlext);
}

$userid = get_userid(); // Also checks login
if (!check_permission($userid,'Manage My Account')) {
  return;
}

$selfurl = basename(__FILE__);

$userobj = UserOperations::get_instance()->LoadUserByID($userid); // <- Safe to do, cause if $userid fails, it redirects automatically to login.
$db = cmsms()->GetDb();
$error = '';
$message = '';

/**
 * Get preferences
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
 * Submit account
 *
 * NOTE: Assumes that we successfully acquired user object.
 */
if (isset($_POST['submit_account']) && check_permission($userid,'Manage My Account')) {

  // Collect params
  $username = (isset($_POST['user'])) ? cleanValue($_POST['user']) : '';
  $password = (isset($_POST['password'])) ? $_POST['password'] : '';
  $passwordagain = (isset($_POST['passwordagain'])) ? $_POST['passwordagain'] : '';
  $firstname = (isset($_POST['firstname'])) ? cleanValue($_POST['firstname']) : '';
  $lastname = (isset($_POST['lastname'])) ? cleanValue($_POST['lastname']) : '';
  $email = (isset($_POST['email'])) ? filter_var($_POST['email'],FILTER_SANITIZE_EMAIL) : '';

  // Do validations
  $validinfo = true;
  if ($username == '') {
    $validinfo = false;
    $error = lang('nofieldgiven', lang('username'));
  } elseif (!preg_match('/^[a-zA-Z0-9\._ ]+$/', $username)) {
    $validinfo = false;
    $error = lang('illegalcharacters', lang('username'));
  } elseif ($password != $passwordagain) {
    $validinfo = false;
    $error = lang('nopasswordmatch');
  } elseif (!empty($email) && !is_email($email)) {
    $validinfo = false;
    $error = lang('invalidemail').': '.$email;
  }

  // If success do action
  if($validinfo) {
    $userobj->username = $username;
    $userobj->firstname = $firstname;
    $userobj->lastname = $lastname;
    $userobj->email = $email;
    if ($password != '') $userobj->SetPassword($password);

    \CMSMS\HookManager::do_hook('Core::EditUserPre', [ 'user'=>&$userobj ] );
    $result = $userobj->Save();

    if($result) {
      // put mention into the admin log
        audit($userid, 'Admin Username: '.$userobj->username, 'Edited');
        \CMSMS\HookManager::do_hook('Core::EditUserPost', [ 'user'=>&$userobj ] );
        $message = lang('accountupdated');
    } else {
        // throw exception? update just failed.
    }
  }
} // end of account submit

/**
 * Build page
 */

include_once 'header.php';

if (!empty($error)) {
  $themeObject->PrepareError($error);
}
if (!empty($message)) {
  $themeObject->PrepareSuccess($message);
}

$contentops = cmsms()->GetContentOperations();
$smarty->assign('SECURE_PARAM_NAME', CMS_SECURE_PARAM_NAME); // Assigned at include.php?
$smarty->assign('CMS_USER_KEY', $_SESSION[CMS_USER_KEY]); // Assigned at include.php?

# WYSIWYG editors
$tmp = module_meta::get_instance()->module_list_by_capability(CmsCoreCapabilities::WYSIWYG_MODULE);
$n = count($tmp);
$tmp2 = [-1 => lang('none')];
for ($i = 0; $i < $n; ++$i) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}

$smarty->assign('wysiwyg_opts', $tmp2);

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
  'syntaxhighlighter'=>$syntaxhighlighter,
  'urlext' => $urlext,
  'selfurl' => $selfurl,
  'userobj'=>$userobj,
  'wysiwyg'=>$wysiwyg,
]);

$smarty->display('myaccount.tpl');

include_once 'footer.php';
