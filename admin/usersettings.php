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

//use CMSMS\SysDataCache;
//use CMSMS\MultiEditor;
use CMSMS\AdminTheme;
use CMSMS\AdminUtils;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\ContentOperations;
use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;
use CMSMS\ModuleOperations;
use CMSMS\UserParams;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('menu.php'.$urlext);
}

$userid = get_userid();
$themeObject = Utils::get_theme_object();

if (!check_permission($userid,'Manage My Settings')) {
//TODO a push-notification    lang('needpermissionto','"Manage My Settings"'));
    return;
}

$userobj = AppSingle::UserOperations()->LoadUserByID($userid); // <- Safe : if get_userid() fails, it redirects automatically to login.
$db = AppSingle::Db();

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
    //TODO in/UI by relevant module
    $syntaxtheme = isset($_POST['syntaxtheme']) ? trim($_POST['syntaxtheme']) : null;
    $wysiwyg = $_POST['wysiwygtype'] ?? null; //rich-text-editor
    //TODO in/UI by relevant module
    $wysiwygtheme = isset($_POST['wysiwygtheme']) ? trim($_POST['wysiwygtheme']) : null;

    // Set prefs
    $themenow = UserParams::get_for_user($userid, 'admintheme');
    if ($themenow != $admintheme) {
        UserParams::set_for_user($userid, 'admintheme', $admintheme);
    }
    UserParams::set_for_user($userid, 'bookmarks', $bookmarks);
    UserParams::set_for_user($userid, 'ce_navdisplay', $ce_navdisplay);
    UserParams::set_for_user($userid, 'date_format_string', $date_format_string);
    UserParams::set_for_user($userid, 'default_cms_language', $default_cms_language);
    UserParams::set_for_user($userid, 'default_parent', $default_parent);
    UserParams::set_for_user($userid, 'hide_help_links', $hide_help_links);
    UserParams::set_for_user($userid, 'homepage', $homepage);
    UserParams::set_for_user($userid, 'indent', $indent);
    UserParams::set_for_user($userid, 'paging', $paging);
    if ($syntaxer !== null) {
        if (strpos($syntaxer, '::') !== false) {
            $parts = explode('::', $syntaxer);
            UserParams::set_for_user($userid, 'syntax_editor', $parts[0]);    //module
            if ($parts[0] != $parts[1]) { UserParams::set_for_user($userid, 'syntax_type', $parts[1]); }//specific editor
        } else {
            UserParams::set_for_user($userid, 'syntax_editor', $syntaxer);    //module only
            UserParams::set_for_user($userid, 'syntax_type', '');
        }
    } else {
        UserParams::set_for_user($userid, 'syntax_editor', '');
        UserParams::set_for_user($userid, 'syntax_type', '');
    }
    //TODO in/UI by relevant module
    UserParams::set_for_user($userid, 'syntax_theme', $syntaxtheme);
    if ($wysiwyg !== null) {
        if (strpos($wysiwyg, '::') !== false) {
            $parts = explode('::', $wysiwyg);
            UserParams::set_for_user($userid, 'wysiwyg', $parts[0]);    //module
            if ($parts[0] != $parts[1]) { UserParams::set_for_user($userid, 'wysiwyg_type', $parts[1]); }//specific editor
        } else {
            UserParams::set_for_user($userid, 'wysiwyg', $wysiwyg);    //module only
            UserParams::set_for_user($userid, 'wysiwyg_type', '');
        }
    } else {
        UserParams::set_for_user($userid, 'wysiwyg', '');
        UserParams::set_for_user($userid, 'wysiwyg_type', '');
    }
    //TODO in/UI by relevant module
    UserParams::set_for_user($userid, 'wysiwyg_theme', $wysiwygtheme);

    // Audit, message, cleanup
    audit($userid, 'Admin Username: '.$userobj->username, 'Edited');
    $themeObject->RecordNotice('success', lang('prefsupdated'));
//    AdminUtils::clear_cached_files();
//    SysDataCache::get_instance()->release('IF ANY');

    if ($themenow != $admintheme) {
        redirect(basename(__FILE__).$urlext);
    }
} // end of prefs submit

/*
 * Get current preferences
 */
$admintheme = UserParams::get_for_user($userid, 'admintheme', AdminTheme::GetDefaultTheme());
$bookmarks = UserParams::get_for_user($userid, 'bookmarks', 0);
$ce_navdisplay = UserParams::get_for_user($userid,'ce_navdisplay');
$date_format_string = UserParams::get_for_user($userid, 'date_format_string', '%x %X');
$default_cms_language = UserParams::get_for_user($userid, 'default_cms_language');
$default_parent = (int)UserParams::get_for_user($userid, 'default_parent', -1);
$hide_help_links = UserParams::get_for_user($userid, 'hide_help_links', 0);
$homepage = UserParams::get_for_user($userid, 'homepage');
$indent = UserParams::get_for_user($userid, 'indent', true);
$old_default_cms_lang = $default_cms_language;
$paging = UserParams::get_for_user($userid, 'paging', 0);
$syntaxmodule = UserParams::get_for_user($userid, 'syntax_editor');
$syntaxtype = UserParams::get_for_user($userid, 'syntax_type');
$syntaxer = $syntaxmodule;
if ($syntaxtype) { $syntaxer .= '::'.$syntaxtype; }
//TODO in/UI by relevant module
$syntaxtheme = UserParams::get_for_user($userid, 'syntax_theme');
$wysiwygmodule = UserParams::get_for_user($userid, 'wysiwyg');
$wysiwygtype = UserParams::get_for_user($userid, 'wysiwyg_type');
$wysiwyg = $wysiwygmodule;
if ($wysiwygtype) { $wysiwyg .= '::'.$wysiwygtype; }
//TODO in/UI by relevant module
$wysiwygtheme = UserParams::get_for_user($userid, 'wysiwyg_theme');

$modules = ModuleOperations::get_modules_with_capability(CoreCapabilities::USER_SETTINGS);
if ($modules) {
    // load those modules if not already done
    foreach ($modules as $i => $modname) {
        $modules[$i] = Utils::get_module($modname);
    }
    $list = HookOperations::do_hook_accumulate('ExtraUserSettings');
    foreach ($list as $bundle) {
        // next level is detail for that contributor
        foreach ($bundle as $propname => $parts) {
            // $parts  = ['content'=>displayable stuff, 'tab'=>name,'head'=>page-header stuff if any, 'foot'=>page-footer js if sny, 'validate'=>post-submit validation PHP if any]
            //remember $propname, $parts['validate'] (probably a callable) for post-submit processing
            //$parts['head','foot'] if any append to corresponding page-construct accumulators
            //$parts['content'] accumulate for sending to smarty
            $here = 1;
        }
    }
}

/*
 * Build page
 */

$contentops = ContentOperations::get_instance();
$modops = ModuleOperations::get_instance();
$smarty = AppSingle::Smarty();

// Rich-text (html) editors
$tmp = $modops->GetCapableModules(CoreCapabilities::WYSIWYG_MODULE);
$editors = [];
if ($tmp) {
    for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
        $ob = Utils::get_module($tmp[$i]);
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
$tmp = $modops->GetCapableModules(CoreCapabilities::SYNTAX_MODULE);
if ($tmp) {
    for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
        $ob = Utils::get_module($tmp[$i]);
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

$smarty->assign('helpicon', $themeObject->DisplayImage('icons/system/info.png', 'help','','','cms_helpicon'));

// Admin themes
$tmp = AdminTheme::GetAvailableThemes();
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
    //TODO in/by relevant module
    'syntaxtheme'=>$syntaxtheme,
    'wysiwyg'=>$wysiwyg,
    //TODO in/by relevant module
    'wysiwygtheme'=>$wysiwygtheme,
]);

//$nonce = get_csp_token();
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

$content = $smarty->fetch('usersettings.tpl');
require './header.php';
echo $content;
require './footer.php';
