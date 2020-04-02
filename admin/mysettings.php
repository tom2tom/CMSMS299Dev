<?php
#Procedure to display and modify the user's admin settings/preferences
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AdminUtils;
use CMSMS\AppState;
use CMSMS\ContentOperations;
use CMSMS\ModuleOperations;
//use CMSMS\MultiEditor;
use CMSMS\ThemeBase;
use CMSMS\UserOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('index.php'.$urlext);
}

$userid = get_userid();
$themeObject = cms_utils::get_theme_object();

if (!check_permission($userid,'Manage My Settings')) {
//TODO a push-notification    lang('needpermissionto','"Manage My Settings"'));
    return;
}

$userobj = UserOperations::get_instance()->LoadUserByID($userid); // <- Safe to do, cause if $userid fails, it redirects automatically to login.
$db = cmsms()->GetDb();

if (isset($_POST['submit'])) {
    /*
     * Submit account
     *
     * NOTE: Assumes that we successfully acquired user object.
     */
    cleanArray($_POST);
    // Get values from request and drive em to variables
    $admintheme = $_POST['admintheme'];
    $bookmarks = (isset($_POST['bookmarks'])) ? 1 : 0;
    $ce_navdisplay = $_POST['ce_navdisplay'];
    $date_format_string = $_POST['date_format_string'];
    $default_cms_language = $_POST['default_cms_language'];
    $default_parent = (int)$_POST['parent_id'];
    $hide_help_links = (isset($_POST['hide_help_links'])) ? 1 : 0;
    $homepage = $_POST['homepage'];
    $indent = (isset($_POST['indent'])) ? 1 : 0;
    $old_default_cms_lang = $_POST['old_default_cms_lang'];
    $paging = (isset($_POST['paging'])) ? 1 : 0;
    $syntaxer = $_POST['syntaxtype'] ?? null; //syntax/advanced editor
    $syntaxtheme = isset($_POST['syntaxtheme']) ? trim($_POST['syntaxtheme']) : null;
    $wysiwyg = $_POST['wysiwygtype'] ?? null; //rich-text-editor
    $wysiwygtheme = isset($_POST['wysiwygtheme']) ? trim($_POST['wysiwygtheme']) : null;

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
    cms_userprefs::set_for_user($userid, 'hide_help_links', $hide_help_links);
    cms_userprefs::set_for_user($userid, 'homepage', $homepage);
    cms_userprefs::set_for_user($userid, 'indent', $indent);
    cms_userprefs::set_for_user($userid, 'paging', $paging);
    if ($syntaxer !== null) {
        if (strpos($syntaxer, '::') !== false) {
            $parts = explode('::', $syntaxer);
            cms_userprefs::set_for_user($userid, 'syntax_editor', $parts[0]);    //module
            if ($parts[0] != $parts[1]) { cms_userprefs::set_for_user($userid, 'syntax_type', $parts[1]); }//specific editor
        } else {
            cms_userprefs::set_for_user($userid, 'syntax_editor', $syntaxer);    //module only
            cms_userprefs::set_for_user($userid, 'syntax_type', '');
        }
    } else {
        cms_userprefs::set_for_user($userid, 'syntax_editor', '');
        cms_userprefs::set_for_user($userid, 'syntax_type', '');
    }
    cms_userprefs::set_for_user($userid, 'syntax_theme', $syntaxtheme);
    if ($wysiwyg !== null) {
        if (strpos($wysiwyg, '::') !== false) {
            $parts = explode('::', $wysiwyg);
            cms_userprefs::set_for_user($userid, 'wysiwyg', $parts[0]);    //module
            if ($parts[0] != $parts[1]) { cms_userprefs::set_for_user($userid, 'wysiwyg_type', $parts[1]); }//specific editor
        } else {
            cms_userprefs::set_for_user($userid, 'wysiwyg', $wysiwyg);    //module only
            cms_userprefs::set_for_user($userid, 'wysiwyg_type', '');
        }
    } else {
        cms_userprefs::set_for_user($userid, 'wysiwyg', '');
        cms_userprefs::set_for_user($userid, 'wysiwyg_type', '');
    }
    cms_userprefs::set_for_user($userid, 'wysiwyg_theme', $wysiwygtheme);

    // Audit, message, cleanup
    audit($userid, 'Admin Username: '.$userobj->username, 'Edited');
    $themeObject->RecordNotice('success', lang('prefsupdated'));
//    AdminUtils::clear_cached_files();
//    SysDataCache::release('IF ANY');

    if ($themenow != $admintheme) {
        redirect(basename(__FILE__).$urlext);
    }
} // end of prefs submit

/*
 * Get current preferences
 */
$admintheme = cms_userprefs::get_for_user($userid, 'admintheme', ThemeBase::GetDefaultTheme());
$bookmarks = cms_userprefs::get_for_user($userid, 'bookmarks', 0);
$ce_navdisplay = cms_userprefs::get_for_user($userid,'ce_navdisplay');
$date_format_string = cms_userprefs::get_for_user($userid, 'date_format_string', '%x %X');
$default_cms_language = cms_userprefs::get_for_user($userid, 'default_cms_language');
$default_parent = (int)cms_userprefs::get_for_user($userid, 'default_parent', -1);
$hide_help_links = cms_userprefs::get_for_user($userid, 'hide_help_links', 0);
$homepage = cms_userprefs::get_for_user($userid, 'homepage');
$indent = cms_userprefs::get_for_user($userid, 'indent', true);
$old_default_cms_lang = $default_cms_language;
$paging = cms_userprefs::get_for_user($userid, 'paging', 0);
$syntaxmodule = cms_userprefs::get_for_user($userid, 'syntax_editor');
$syntaxtype = cms_userprefs::get_for_user($userid, 'syntax_type');
$syntaxer = $syntaxmodule;
if ($syntaxtype) { $syntaxer .= '::'.$syntaxtype; }
$syntaxtheme = cms_userprefs::get_for_user($userid, 'syntax_theme');
$wysiwygmodule = cms_userprefs::get_for_user($userid, 'wysiwyg');
$wysiwygtype = cms_userprefs::get_for_user($userid, 'wysiwyg_type');
$wysiwyg = $wysiwygmodule;
if ($wysiwygtype) { $wysiwyg .= '::'.$wysiwygtype; }
$wysiwygtheme = cms_userprefs::get_for_user($userid, 'wysiwyg_theme');

/*
 * Build page
 */

$contentops = ContentOperations::get_instance();
$modops = ModuleOperations::get_instance();
$smarty = CmsApp::get_instance()->GetSmarty();

// Rich-text (html) editors
$tmp = $modops->GetCapableModules(CmsCoreCapabilities::WYSIWYG_MODULE);
$editors = [];
if ($tmp) {
    for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
        $ob = cms_utils::get_module($tmp[$i]);
        if (method_exists($ob, 'ListEditors')) { //aka ($ob instanceof MultiEditor)
            $all = $ob->ListEditors();
            foreach ($all as $editor=>$val) {
                $one = new stdClass();
                $one->label = $ob->Lang(strtolower($editor).'_friendlyname');
                $one->value = $val; // as module::editor
                list($modname, $edname) = explode('::', $val);
                list($realm, $key) = $ob->GetMainHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                    $one->mainkey = $realm.'__'.$key;
                } else {
                    $one->mainkey = null;
                }
                list($realm, $key) = $ob->GetThemeHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                        $one->themekey = $realm.'__'.$key;
                    } else {
                        $one->themekey = null;
                }
                if ($modname == $wysiwygmodule && $edname == $wysiwygtype) { $one->checked = true; }
                $editors[] = $one;
            }
        } else {
            $one = new stdClass();
            $one->label = $ob->GetFriendlyName(); //TODO ->Lang(..._friendlyname if present
            $one->value = $tmp[$i];
            $one->mainkey = null; //TODO ibid if any
            $one->themekey = null; //TODO ditto
            if ($tmp[$i] == $wysiwyg) { $one->checked = true; }
            $editors[] = $one;
        }
    }
    usort($editors, function ($a,$b) { return strcmp($a->label, $b->label); });

    $one = new stdClass();
    $one->value = '';
    $one->label = lang('default');
    $one->mainkey = null;
    $one->themekey = null;
    if (!$wysiwyg) { $one->checked = true; }
    array_unshift($editors, $one);
}
$smarty -> assign('wysiwyg_opts', $editors);

// Syntax-highlight editors
$editors = [];
$tmp = $modops->GetCapableModules(CmsCoreCapabilities::SYNTAX_MODULE);
if ($tmp) {
    for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
        $ob = cms_utils::get_module($tmp[$i]);
        if (method_exists($ob, 'ListEditors')) { //aka ($ob instanceof MultiEditor)
            $all = $ob->ListEditors();
            foreach ($all as $editor=>$val) {
                $one = new stdClass();
                $one->label = $ob->Lang(strtolower($editor).'_friendlyname');
                $one->value = $val; // as module::editor
                list($modname, $edname) = explode('::', $val);
                list($realm, $key) = $ob->GetMainHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                    $one->mainkey = $realm.'__'.$key;
                } else {
                    $one->mainkey = null;
                }
                list($realm, $key) = $ob->GetThemeHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                    $one->themekey = $realm.'__'.$key;
                } else {
                    $one->themekey = null;
                }
                if ($modname == $syntaxmodule && $edname == $syntaxtype) { $one->checked = true; }
                $editors[] = $one;
            }
        } elseif ($tmp[$i] != 'MicroTiny') { //that's only for html :(
            $one = new stdClass();
            $one->label = $ob->GetFriendlyName(); //TODO
            $one->value = $tmp[$i].'::'.$tmp[$i];
            $one->mainkey = null; //TODO
            $one->themekey = null; //TODO
            if ($tmp[$i] == $syntaxer || $one->value == $syntaxer) { $one->checked = true; }
            $editors[] = $one;
        }
    }
    usort($editors, function ($a,$b) { return strcmp($a->label, $b->label); });

    $one = new stdClass();
    $one->value = '';
    $one->label = lang('default');
    $one->mainkey = null;
    $one->themekey = null;
    if (!$syntaxer) { $one->checked = true; }
    array_unshift($editors, $one);
}
$smarty->assign('syntax_opts', $editors);

$theme = cms_utils::get_theme_object();
$smarty->assign('helpicon', $theme->DisplayImage('icons/system/info.png', 'help','','','cms_helpicon'));

// Admin themes
$tmp = ThemeBase::GetAvailableThemes();
if (count($tmp) < 2) {
    $tmp = null;
}
$smarty->assign('themes_opts',$tmp);

// Modules
$allmodules = $modops->GetInstalledModules();
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
    'urlext' => $urlext,
    'userobj'=>$userobj,
    'syntaxer'=>$syntaxer,
    'syntaxtheme'=>$syntaxtheme,
    'wysiwyg'=>$wysiwyg,
    'wysiwygtheme'=>$wysiwygtheme,
]);

$editortitle = lang('syntax_editor_theme');
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
add_page_foottext($out);

include_once 'header.php';
$smarty->display('mysettings.tpl');
include_once 'footer.php';
