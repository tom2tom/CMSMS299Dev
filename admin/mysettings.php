<?php
#procedure to display and modify the user's admin settings/preferences
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CMSMS\AdminUtils;
use CMSMS\internal\module_meta;
use CMSMS\ModuleOperations;
use CMSMS\SyntaxEditor;
use CMSMS\ThemeBase;
use CMSMS\UserOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
  redirect('index.php'.$urlext);
}

$userid = get_userid();
$themeObject = cms_utils::get_theme_object();

if (!check_permission($userid,'Manage My Settings')) {
//TODO some immediate popup  lang('needpermissionto','"Manage My Settings"'));
  return;
}

$userobj = (new UserOperations())->LoadUserByID($userid); // <- Safe to do, cause if $userid fails, it redirects automatically to login.
$db = cmsms()->GetDb();

/**
 * Submit account
 *
 * NOTE: Assumes that we successfully acquired user object.
 */
if (isset($_POST['submit'])) {
  cleanArray($_POST);
  // Get values from request and drive em to variables
  $admintheme = $_POST['admintheme'];
  $bookmarks = (isset($_POST['bookmarks'])) ? 1 : 0;
  $ce_navdisplay = $_POST['ce_navdisplay'];
  $date_format_string = $_POST['date_format_string'];
  $default_cms_language = $_POST['default_cms_language'];
  $default_parent = (int)$_POST['parent_id'];
  $editortheme = $_POST['editortheme'] ?? null;
  $editortype = $_POST['editortype'] ?? null;
  $hide_help_links = (isset($_POST['hide_help_links'])) ? 1 : 0;
  $homepage = $_POST['homepage'];
  $indent = (isset($_POST['indent'])) ? 1 : 0;
  $old_default_cms_lang = $_POST['old_default_cms_lang'];
  $paging = (isset($_POST['paging'])) ? 1 : 0;
//  $syntaxhighlighter = $_POST['syntaxhighlighter'] ?? null;
  $wysiwyg = $_POST['wysiwyg'];

  // Set prefs
  $themenow = cms_userprefs::get_for_user($userid, 'admintheme');
  if ($themenow != $admintheme) {
    cms_userprefs::set_for_user($userid, 'admintheme', $admintheme);
  }
  cms_userprefs::set_for_user($userid, 'bookmarks', $bookmarks);
  cms_userprefs::set_for_user($userid, 'ce_navdisplay', $ce_navdisplay);
  cms_userprefs::set_for_user($userid, 'date_format_string', $date_format_string);
  cms_userprefs::set_for_user($userid, 'default_cms_language', $default_cms_language);
  cms_userprefs::set_for_user($userid, 'default_parent', $default_parent);
  if ($editortype !== null) {
    cms_userprefs::set_for_user($userid, 'editor_theme', $editortheme);
    cms_userprefs::set_for_user($userid, 'syntax_editor', $editortype);  //as module::editor or module::module
  } else {
    cms_userprefs::set_for_user($userid, 'syntax_editor', '');
  }
  cms_userprefs::set_for_user($userid, 'hide_help_links', $hide_help_links);
  cms_userprefs::set_for_user($userid, 'homepage', $homepage);
  cms_userprefs::set_for_user($userid, 'indent', $indent);
  cms_userprefs::set_for_user($userid, 'paging', $paging);
/*  if ($syntaxhighlighter !== null) {
    cms_userprefs::set_for_user($userid, 'syntaxhighlighter', $syntaxhighlighter);
  }
*/
  cms_userprefs::set_for_user($userid, 'wysiwyg', $wysiwyg);

  // Audit, message, cleanup
  audit($userid, 'Admin Username: '.$userobj->username, 'Edited');
  $themeObject->RecordNotice('success', lang('prefsupdated'));
  AdminUtils::clear_cache();

  if ($themenow != $admintheme) {
    redirect(basename(__FILE__).$urlext);
  }
} // end of prefs submit

/**
 * Get current preferences
 */
$admintheme = cms_userprefs::get_for_user($userid, 'admintheme', ThemeBase::GetDefaultTheme());
$bookmarks = cms_userprefs::get_for_user($userid, 'bookmarks', 0);
$ce_navdisplay = cms_userprefs::get_for_user($userid,'ce_navdisplay');
$date_format_string = cms_userprefs::get_for_user($userid, 'date_format_string', '%x %X');
$default_cms_language = cms_userprefs::get_for_user($userid, 'default_cms_language');
$default_parent = (int)cms_userprefs::get_for_user($userid, 'default_parent', -1);
$editortheme = cms_userprefs::get_for_user($userid, 'editor_theme');
$vars = explode ('::', cms_userprefs::get_for_user($userid, 'syntax_editor'));
$editormodule = $vars[0] ?? '';
$editortype = $vars[1] ?? $editormodule;
$hide_help_links = cms_userprefs::get_for_user($userid, 'hide_help_links', 0);
$homepage = cms_userprefs::get_for_user($userid, 'homepage');
$indent = cms_userprefs::get_for_user($userid, 'indent', true);
$old_default_cms_lang = $default_cms_language;
$paging = cms_userprefs::get_for_user($userid, 'paging', 0);
//$syntaxhighlighter = cms_userprefs::get_for_user($userid, 'syntaxhighlighter'); //probably useless !
$wysiwyg = cms_userprefs::get_for_user($userid, 'wysiwyg');

/**
 * Build page
 */

$contentops = CmsApp::get_instance()->GetContentOperations();
$smarty = CmsApp::get_instance()->GetSmarty();
$metops = new module_meta();

// WYSIWYG editors
$tmp = $metops->module_list_by_capability(CmsCoreCapabilities::WYSIWYG_MODULE);
$n = count($tmp);
$tmp2 = [-1 => lang('none')];
for ($i = 0; $i < $n; ++$i) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}

$smarty -> assign('wysiwyg_opts', $tmp2);

// Syntax highlighters
$editors = [];
$tmp = $metops->module_list_by_capability(CmsCoreCapabilities::SYNTAX_MODULE); //pre 2.0 identifier?
if ($tmp) {
  for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
    $ob = cms_utils::get_module($tmp[$i]);
    if ($ob instanceof SyntaxEditor) {
      $all = $ob->ListEditors(true);
      foreach ($all as $label=>$val) {
        $one = new stdClass();
        $one->label = $label;
        $one->value = $val; // as module::editor
        list($modname, $edname) = explode('::', $val);
        list($realm, $key) = $ob->GetMainHelpKey($edname);
        if (!$realm) $realm = $modname;
        $one->mainkey = $realm.'__'.$key;
        list($realm, $key) = $ob->GetThemeHelpKey($edname);
        if (!$realm) $realm = $modname;
        $one->themekey = $realm.'__'.$key;
        if ($modname == $editormodule && $edname == $editortype) $one->checked = true;
        $editors[] = $one;
      }
    } elseif ($tmp[$i] != 'MicroTiny') { //that's only for html :(
      $one = new stdClass();
      $one->label = $ob->GetFriendlyName();
      $one->value = $tmp[$i].'::'.$tmp[$i];
      $one->mainkey = '';
      $one->themekey = '';
      if ($tmp[$i] == $editortype) $one->checked = true;
      $editors[] = $one;
    }
  }
  usort($editors, function ($a,$b) { return strcmp($a->label, $b->label); });

  $one = new stdClass();
  $one->value = '';
  $one->label = lang('default');
  $one->mainkey = '';
  $one->themekey = '';
  if (!$editortype) $one->checked = true;
  $editors[] = $one;
}
$smarty->assign('editors', $editors);

$n = count($tmp);
if ($n) {
  $tmp2 = [-1 => lang('none')];
  for ($i = 0; $i < $n; ++$i) {
    $tmp2[$tmp[$i]] = $tmp[$i];
  }

  $smarty->assign('syntax_opts', $tmp2); //TODO c.f. 'editors' above
}

$theme = cms_utils::get_theme_object();
$smarty->assign('helpicon', $theme->DisplayImage('icons/system/info.png', 'help','','','cms_helpicon'));

// Admin themes
$tmp = ThemeBase::GetAvailableThemes();
if (count($tmp) < 2) {
  $tmp = null;
}
$smarty->assign('themes_opts',$tmp);

// Modules
$allmodules = (new ModuleOperations())->GetInstalledModules();
$modules = [];
foreach ((array)$allmodules as $onemodule) {
  $modules[$onemodule] = $onemodule;
}

// Pages
$sel = AdminUtils::CreateHierarchyDropdown(0, $default_parent, 'parent_id', false, true);

// Prefs
$tmp = [10 => 10, 20 => 20, 50 => 50, 100 => 100];

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
  'admintheme'=>$admintheme,
  'backurl'=>$themeObject->backUrl(),
  'bookmarks'=>$bookmarks,
  'ce_navdisplay'=>$ce_navdisplay,
  'date_format_string'=>$date_format_string,
  'default_cms_language'=>$default_cms_language,
  'default_parent'=>$sel,
  'editortheme'=>$editortheme,
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
  'extraparms' => $extras,
//'syntaxhighlighter'=>$syntaxhighlighter,
  'urlext' => $urlext,
  'userobj'=>$userobj,
  'wysiwyg'=>$wysiwyg,
]);

$editortitle = lang('text_editor_theme');
$out = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('#theme_help .cms_helpicon').on('click', function() {
  var key = $('input[name="editortype"]:checked').attr('data-themehelp-key');
  if(key) {
   var self = this;
   $.get(cms_data.ajax_help_url, {
    key: key
   }, function(text) {
    var data = {
     cmshelpTitle: '$editortitle'
    };
    cms_help(self, data, text);
   });
  }
 });
});
//]]>
</script>

EOS;
$themeObject->add_footertext($out);

include_once 'header.php';
$smarty->display('mysettings.tpl');
include_once 'footer.php';

