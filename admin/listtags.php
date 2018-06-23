<?php
#procedure to list all plugins (aka tags)
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

$CMS_ADMIN_PAGE=1;
$CMS_LOAD_ALL_PLUGINS=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();
$access = true; //check_permission($userid, 'View Tags'); //TODO relevant permission

if (!$access) {
//TODO some immediate popup
    return;
}

$plugin = (isset($_GET['plugin'])) ? basename(cleanValue($_GET['plugin'])) : '';
$type = (isset($_GET['type'])) ? basename(cleanValue($_GET['type'])) : '';
$action = (isset($_GET['action'])) ? cleanValue($_GET['action']) : '';

$dirs = [];
$dirs[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'plugins';
$bp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR;
$dirs[] = $bp.'lib'.DIRECTORY_SEPARATOR.'plugins';
$dirs[] = $bp.'plugins';
$dirs[] = CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'plugins';

$find_file = function($filename) use ($dirs) {
    $filename = basename($filename); // no sneaky paths
    foreach ($dirs as $dir) {
        $fn = $dir.DIRECTORY_SEPARATOR.$filename;
        if (is_file($fn)) return $fn;
    }
};

$smarty = CMSMS\internal\Smarty::get_instance();

$selfurl = basename(__FILE__);

if ($action == 'showpluginhelp') {
    $content = '';
    $file = $find_file("$type.$plugin.php");
    if (is_file($file)) require_once $file;

    if (function_exists('smarty_cms_help_'.$type.'_'.$plugin)) {
        // Get and display the plugin's help
        $func_name = 'smarty_cms_help_'.$type.'_'.$plugin;
        @ob_start();
        $func_name([]);
        $content = @ob_get_contents();
        @ob_end_clean();
    } elseif (CmsLangOperations::key_exists("help_{$type}_{$plugin}",'tags')) {
        $content = CmsLangOperations::lang_from_realm('tags',"help_{$type}_{$plugin}");
    } elseif (CmsLangOperations::key_exists("help_{$type}_{$plugin}")) {
        $content = lang("help_{$type}_{$plugin}");
    }

    if ($content) {
        $smarty->assign('subheader',lang('pluginhelp',$plugin));
        $smarty->assign('content',$content);
    } else {
        $smarty->assign('error',lang('nopluginhelp'));
    }
} elseif ($action == 'showpluginabout') {
    $file = $find_file("$type.$plugin.php");
    if (file_exists($file)) require_once $file;

    $smarty->assign('subheader',lang('pluginabout',$plugin));
    $func_name = 'smarty_cms_about_'.$type.'_'.$plugin;
    if (function_exists($func_name)) {
        @ob_start();
        $func_name([]);
        $content = @ob_get_contents();
        @ob_end_clean();
        $smarty->assign('content',$content);
    } else {
        $smarty->assign('error',lang('nopluginabout'));
    }
} else {
    $files = [];
    foreach ($dirs as $one) {
        $files = array_merge($files,glob($one.'/*.php'));
    }

    if (is_array($files) && count($files)) {
        $file_array = [];
        foreach ($files as $onefile) {
            $file = basename($onefile);
            $parts = explode('.',$file);
            if (startswith($file,'prefilter.') || startswith($file,'postfilter.')) continue;
            if (!is_array($parts) || count($parts) != 3 ) continue;

            $rec = [];
            $rec['type'] = $parts[0];
            $rec['name'] = $parts[1];
            $rec['admin'] = (startswith($onefile,CMS_ADMIN_PATH)) ? 1 : 0;

			include_once $onefile;

            $rec['cachable'] = !($rec['admin'] || function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name'])); //TODO

            // leave smarty_nocache for compatibility for a while ?? TODO
            if (!(function_exists('smarty_'.$rec['type'].'_'.$rec['name']) ||
                  function_exists('smarty_cms_'.$rec['type'].'_'.$rec['name']) ||
                  function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name']))) continue;

            if (function_exists('smarty_cms_help_'.$rec['type'].'_'.$rec['name'])) {
                $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            } elseif (CmsLangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'],'tags')) {
                $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            } elseif (CmsLangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'])) {
                $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            }

            if (function_exists('smarty_cms_about_'.$rec['type'].'_'.$rec['name'])) {
                $rec['about_url'] = $selfurl.$urlext.'&amp;action=showpluginabout&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            }

            $file_array[] = $rec;
        }
    }

    // add in standard tags...
    $rec = ['type'=>'function','name'=>'content'];
    $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    $rec = ['type'=>'function','name'=>'content_image'];
    $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    $rec = ['type'=>'function','name'=>'content_module'];
    $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    $rec = ['type'=>'function','name'=>'process_pagedata'];
    $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    usort($file_array, function($a,$b)
     {
        return strcmp($a['name'],$b['name']);
     });
    $smarty->assign('plugins',$file_array);
    $themeObject = cms_utils::get_theme_object();
    $smarty->assign('iconyes',$themeObject->DisplayImage('icons/system/true.gif',lang_by_realm('tags','title_admin'),'','','systemicon'));
    $smarty->assign('iconno',$themeObject->DisplayImage('icons/system/false.gif',lang_by_realm('tags','title_notadmin'),'','','systemicon'));
    $smarty->assign('iconcyes',$themeObject->DisplayImage('icons/system/true.gif',lang_by_realm('tags','title_cachable'),'','','systemicon'));
    $smarty->assign('iconcno',$themeObject->DisplayImage('icons/system/false.gif',lang_by_realm('tags','title_notcachable'),'','','systemicon'));
    $smarty->assign('iconhelp',$themeObject->DisplayImage('icons/system/help.gif',lang_by_realm('tags','viewhelp'),'','','systemicon'));
    $smarty->assign('iconabout',$themeObject->DisplayImage('icons/system/info.gif',lang_by_realm('tags','viewabout'),'','','systemicon'));
}

$smarty->assign([
    'urlext' => $urlext,
    'selfurl' => $selfurl,
]);

include_once 'header.php';
$smarty->display('listtags.tpl');
include_once 'footer.php';
