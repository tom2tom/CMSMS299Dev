<?php
/*
Procedure to display and modify the user's admin settings/preferences
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AdminTheme;
use CMSMS\AdminUtils;
use CMSMS\CapabilityType;
use CMSMS\Error403Exception;
use CMSMS\HookOperations;
use CMSMS\Lone;
use CMSMS\UserParams;
use CMSMS\Utils;
use function CMSMS\de_specialize_array;
use function CMSMS\log_info;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize_array;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('menu.php'.$urlext);
}

$userid = get_userid();
$themeObject = Lone::get('Theme');

if (!check_permission($userid, 'Manage My Settings')) {
//TODO some pushed popup $themeObject->RecordNotice('error', _la('needpermissionto', '"Modify Site Preferences"'));
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$userobj = Lone::get('UserOperations')->LoadUserByID($userid); // <- Safe : if get_userid() fails, it redirects automatically to login.
$db = Lone::get('Db');

if (isset($_POST['submit'])) {
    /*
     * NOTE: Assumes that we successfully acquired user object
     */
    // Get values from request and drive em to variables
    de_specialize_array($_POST);

    $admintheme = sanitizeVal($_POST['admintheme'], CMSSAN_FILE);
    $bookmarks = (isset($_POST['bookmarks'])) ? 1 : 0;
    $ce_navdisplay = sanitizeVal($_POST['ce_navdisplay']); //'title' | 'menutext' | ''
    $val = $_POST['date_format'];
    if ($val) {
        // mixed date()- and/or strftime()-formats for locale_ftime() use
        // strip any time-formatting
        $s = preg_replace('/%[HIklMpPrRSTXzZ]/', '', $val);
        if ($s == $val) {
            $s = preg_replace('/(?<!\\\\)[aABgGhHisuv]/', '', $val);
        }
        if ($s == $val) {
            $val = trim($s);
        } else {
            $val = trim($s, ' :');
        }
        $date_format = sanitizeVal($val, CMSSAN_NONPRINT); // TODO cleaner e.g. all '%' ? - tho' valid, it confuses smarty
    } else {
        $date_format = '';
    }
    $val = $_POST['datetime_format'];
    // mixed date()- and/or strftime()-formats for locale_ftime() use
    //$datetime_format = ($val) ? preg_replace('~[^a-zA-Z0-9,.\-:#\\/ ]~', '', trim($val)) : ''; // no '%' here either
    $datetime_format = sanitizeVal(trim($val), CMSSAN_NONPRINT); // TODO cleaner
    // see http://www.unicode.org/reports/tr35/#Identifiers
    $default_cms_language = sanitizeVal($_POST['default_cms_language'], CMSSAN_NONPRINT);
    $old_default_cms_lang = sanitizeVal($_POST['old_default_cms_lang'], CMSSAN_NONPRINT);
    $default_parent = (int)$_POST['parent_id'];
    $hide_help_links = (isset($_POST['hide_help_links'])) ? 1 : 0;
    // empty or relative-URL of an admin-page
    $homepage = (!empty($_POST['homepage'])) ?
        filter_var(rtrim($_POST['homepage'], ' /'), FILTER_SANITIZE_URL) : // allows letters, digits, $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=
//        sanitizeVal($_POST['homepage'], CMSSAN_NONPRINT) : // no entirely relevant CMSSAN_*
        '';
    $indent = (isset($_POST['indent'])) ? 1 : 0;
    $paging = (isset($_POST['paging'])) ? 1 : 0;
    $syntaxer = isset($_POST['syntaxtype']) ? sanitizeVal($_POST['syntaxtype'], CMSSAN_PUNCTX, ':') : null; // allow '::'
    //TODO in/UI by relevant module
    $syntaxtheme = isset($_POST['syntaxtheme']) ? sanitizeVal($_POST['syntaxtheme'], CMSSAN_PUNCT) : null; // OR , CMSSAN_PURESPC?
    $wysiwyg = isset($_POST['wysiwygtype']) ? sanitizeVal($_POST['wysiwygtype'], CMSSAN_PUNCTX, ':') : null; // allow '::'
    //TODO in/UI by relevant module
    $wysiwygtheme = isset($_POST['wysiwygtheme']) ? sanitizeVal($_POST['wysiwygtheme'], CMSSAN_PUNCT) : null; // OR , CMSSAN_PURESPC?

    // Set prefs
    $themenow = UserParams::get_for_user($userid, 'admintheme');
    if ($themenow != $admintheme) {
        UserParams::set_for_user($userid, 'admintheme', $admintheme);
    }
    UserParams::set_for_user($userid, 'bookmarks', $bookmarks);
    UserParams::set_for_user($userid, 'ce_navdisplay', $ce_navdisplay);
    UserParams::set_for_user($userid, 'date_format', $date_format);
    UserParams::set_for_user($userid, 'datetime_format', $datetime_format);
    UserParams::set_for_user($userid, 'default_cms_language', $default_cms_language);
    UserParams::set_for_user($userid, 'default_parent', $default_parent);
    UserParams::set_for_user($userid, 'hide_help_links', $hide_help_links);
    UserParams::set_for_user($userid, 'homepage', $homepage);
    UserParams::set_for_user($userid, 'indent', $indent);
    UserParams::set_for_user($userid, 'paging', $paging);
    if ($syntaxer !== null) {
        if (strpos($syntaxer, '::') !== false) {
            $parts = explode('::', $syntaxer, 2);
            UserParams::set_for_user($userid, 'syntaxhighlighter', $parts[0]); //module
            if ($parts[0] != $parts[1]) { UserParams::set_for_user($userid, 'syntax_type', $parts[1]); } //specific editor
        } else {
            UserParams::set_for_user($userid, 'syntaxhighlighter', $syntaxer); //module only
            UserParams::set_for_user($userid, 'syntax_type', '');
        }
    } else {
        UserParams::set_for_user($userid, 'syntaxhighlighter', '');
        UserParams::set_for_user($userid, 'syntax_type', '');
    }
    //TODO in/UI by relevant module
    UserParams::set_for_user($userid, 'syntax_theme', $syntaxtheme);
    if ($wysiwyg !== null) {
        if (strpos($wysiwyg, '::') !== false) {
            $parts = explode('::', $wysiwyg, 2);
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
    log_info($userid, 'Admin User '.$userobj->username, 'Edited');
    $themeObject->RecordNotice('success', _la('prefsupdated'));
//    AdminUtils::clear_cached_files();
// TODO  Lone::get('LoadedData')->delete('menu_modules', $userid);

    if ($themenow != $admintheme) {
        redirect(basename(__FILE__).$urlext); //go refresh stuff
    }
} // prefs submit

/*
 * Get current preferences
 */
$admintheme = UserParams::get_for_user($userid, 'admintheme', AdminTheme::GetDefaultTheme());
$bookmarks = UserParams::get_for_user($userid, 'bookmarks', 0);
$ce_navdisplay = UserParams::get_for_user($userid, 'ce_navdisplay');
$date_format = UserParams::get_for_user($userid, 'date_format');
$datetime_format = UserParams::get_for_user($userid, 'datetime_format');
$default_cms_language = UserParams::get_for_user($userid, 'default_cms_language');
$default_parent = (int)UserParams::get_for_user($userid, 'default_parent', -1);
$hide_help_links = UserParams::get_for_user($userid, 'hide_help_links', 0);
$homepage = UserParams::get_for_user($userid, 'homepage');
$indent = UserParams::get_for_user($userid, 'indent', true);
$old_default_cms_lang = $default_cms_language;
$paging = UserParams::get_for_user($userid, 'paging', 0);
$syntaxmodule = UserParams::get_for_user($userid, 'syntaxhighlighter');
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

$modnames = Lone::get('LoadedMetadata')->get('capable_modules', false, CapabilityType::USER_SETTINGS);
if ($modnames) {
    // load those modules if not already done
    for ($i = 0, $n = count($modnames); $i < $n; ++$i) {
        $modnames[$i] = Utils::get_module($modnames[$i]);
        if ($modnames[$i]) { $modnames[$i]->InitializeAdmin(); }
    }
    $list = HookOperations::do_hook_accumulate('ExtraUserSettings');
    // assist the garbage-collector
    for ($i = 0; $i < $n; ++$i) {
        $modnames[$i] = null;
    }
    if ($list) {
        foreach ($list as $bundle) {
            // next level is detail for that contributor
            foreach ($bundle as $propname => $parts) {
                // $parts = ['content' => displayable stuff, 'tab' => name, 'head' => page-header stuff if any, 'foot' => page-footer js if sny, 'validate' => post-submit validation PHP if any]
                //remember $propname, $parts['validate'] (probably a callable) for post-submit processing
                //$parts['head', 'foot'] if any append to corresponding page-construct accumulators
                //$parts['content'] accumulate for sending to smarty
                $here = 1;
            }
        }
    }
}

/*
 * Build page
 */
$contentops = Lone::get('ContentOperations');
$smarty = Lone::get('Smarty');

// Rich-text (html) editors
$editors = [];
$modnames = Lone::get('LoadedMetadata')->get('capable_modules', false, CapabilityType::WYSIWYG_MODULE);
if ($modnames) {
    for ($i = 0, $n = count($modnames); $i < $n; ++$i) {
        $mod = Utils::get_module($modnames[$i]);
        if (method_exists($mod, 'ListEditors')) { //OR ($mod instanceof IMultiEditor)
            $all = $mod->ListEditors();
            foreach ($all as $editor => $val) {
                $one = new stdClass();
                $one->label = $mod->Lang(strtolower($editor).'_publicname');
                $one->value = $val; // as module::editor
                list($modname, $edname) = explode('::', $val, 2);
                list($realm, $key) = $mod->GetMainHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                    $one->mainkey = $realm.'__'.$key;
                } else {
                    $one->mainkey = null;
                }
                list($realm, $key) = $mod->GetThemeHelpKey($edname);
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
            if (method_exists($mod, 'GetEditorName')) {
                $one->label = $mod->GetEditorName();
            } else {
                $one->label = $mod->GetFriendlyName(); // admin menu label, may be useless here
            }
            $one->value = $modnames[$i];
            $one->mainkey = null; //TODO ibid if any
            $one->themekey = null; //TODO ditto
            if ($modnames[$i] == $wysiwyg) { $one->checked = true; }
            $editors[] = $one;
        }
    }
    usort($editors, function ($a, $b) { return strcmp($a->label, $b->label); });

    $one = new stdClass();
    $one->value = '';
    $one->label = _la('default');
    $one->mainkey = null;
    $one->themekey = null;
    if (!$wysiwyg) { $one->checked = true; }
    array_unshift($editors, $one);
}
$smarty -> assign('wysiwyg_opts', $editors);

// Syntax-highlight editors
$editors = [];
$modnames = Lone::get('LoadedMetadata')->get('capable_modules', false, CapabilityType::SYNTAX_MODULE);
if ($modnames) {
    for ($i = 0, $n = count($modnames); $i < $n; ++$i) {
        $mod = Utils::get_module($modnames[$i]);
        if (method_exists($mod, 'ListEditors')) { //OR ($mod instanceof IMultiEditor)
            $all = $mod->ListEditors();
            foreach ($all as $editor => $val) {
                $one = new stdClass();
                $one->label = $mod->Lang(strtolower($editor).'_publicname');
                $one->value = $val; // as module::editor
                list($modname, $edname) = explode('::', $val, 2);
                list($realm, $key) = $mod->GetMainHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                    $one->mainkey = $realm.'__'.$key;
                } else {
                    $one->mainkey = null;
                }
                list($realm, $key) = $mod->GetThemeHelpKey($edname);
                if ($key) {
                    if (!$realm) { $realm = $modname; }
                    $one->themekey = $realm.'__'.$key;
                } else {
                    $one->themekey = null;
                }
                if ($modname == $syntaxmodule && $edname == $syntaxtype) { $one->checked = true; }
                $editors[] = $one;
            }
        } elseif ($modnames[$i] != 'HTMLEditor') { //that's only for html :(
            $one = new stdClass();
            if (method_exists($mod, 'GetEditorName')) {
                $one->label = $mod->GetEditorName();
            } else {
                $one->label = $mod->GetFriendlyName(); // admin menu label, may be useless here
            }
            $one->value = $modnames[$i].'::'.$modnames[$i];
            $one->mainkey = null; //TODO
            $one->themekey = null; //TODO
            if ($modnames[$i] == $syntaxer || $one->value == $syntaxer) { $one->checked = true; }
            $editors[] = $one;
        }
    }
    usort($editors, function ($a, $b) { return strcmp($a->label, $b->label); });

    $one = new stdClass();
    $one->value = '';
    $one->label = _la('default');
    $one->mainkey = null;
    $one->themekey = null;
    if (!$syntaxer) { $one->checked = true; }
    array_unshift($editors, $one);
}
$smarty->assign('syntax_opts', $editors);

$smarty->assign('helpicon', $themeObject->DisplayImage('icons/system/info.png', 'help', '', '', 'cms_helpicon'));

// Admin themes
$tmp = AdminTheme::GetAvailableThemes();
if (count($tmp) < 2) {
    $tmp = null;
}
$smarty->assign('themes_opts', $tmp);

// Modules
$availmodules = Lone::get('ModuleOperations')->GetInstalledModules();
if ($availmodules) {
    $modules = array_combine($availmodules, $availmodules);
} else {
    $modules = [];
}

// Content Pages
$sel = AdminUtils::CreateHierarchyDropdown(0, $default_parent, 'parent_id', false, true);

// Prefs
$tmp = [10 => 10, 20 => 20, 50 => 50, 100 => 100];

$ce_navopts = [
    '' => _la('default'),
    'menutext' => _la('menutext'),
    'title' => _la('title'),
];

// Home Pages
// array with members like
// lib/moduleinterface.php?mact=ContentMultiEditor,m1_,defaultadmin,0 => &nbsp;&nbsp;Rich&nbsp;Text&nbsp;Editing
$adminpages = $themeObject->GetAdminPages(); //GetAdminPageDropdown(name 'homepage', sel $homepage, id 'homepage');
specialize_array($adminpages);

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
// TODO CMSMS\specialize() other relevant displayed values
$smarty->assign([
    'admintheme' => $admintheme,
    'backurl' => $themeObject->backUrl(),
    'bookmarks' => $bookmarks,
    'ce_navdisplay' => $ce_navdisplay,
    'ce_navopts' => $ce_navopts,
    'date_format' => $date_format,
    'datetime_format' => $datetime_format,
    'default_cms_language' => $default_cms_language,
    'default_parent' => $sel,
    'extraparms' => $extras,
    'hide_help_links' => $hide_help_links,
    'home_opts' => array_flip($adminpages),
    'homepage' => $homepage,
    'indent' => $indent,
    'language_opts' => get_language_list(),
    'manageaccount' => true,
    'module_opts' => $modules,
    'old_default_cms_lang' => $old_default_cms_lang,
    'pagelimit_opts' => $tmp,
    'paging' => $paging,
    'selfurl' => $selfurl,
    'syntaxer' => $syntaxer,
    //TODO in/by relevant module
    'syntaxtheme' => $syntaxtheme,
    'urlext' => $urlext,
    'userobj' => $userobj,
    'wysiwyg' => $wysiwyg,
    //TODO in/by relevant module
    'wysiwygtheme' => $wysiwygtheme,
]);

//$nonce = get_csp_token();
$editortitle = _la('syntax_editor_theme');
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
        cms_help(self,data,text);
     });
    }
 });
});
//]]>
</script>

EOS;
add_page_foottext($out);

$content = $smarty->fetch('usersettings.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
