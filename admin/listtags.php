<?php
#procedure to list all plugins (aka tags)
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

use CMSMS\AppState;
use CMSMS\LangOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$access = true; //check_permission($userid, 'View Tags'); //TODO relevant permission
$pdev = check_permission($userid, 'Modify Site Code') || !empty($config['developer_mode']);

$themeObject = cms_utils::get_theme_object();

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

$smarty = CmsApp::get_instance()->GetSmarty();
$selfurl = basename(__FILE__);

if ($action == 'showpluginhelp') {
    $content = '';
    $file = $find_file("$type.$plugin.php");
    if (is_file($file)) require_once $file;

    if (function_exists('smarty_cms_help_'.$type.'_'.$plugin)) {
        // Get and display the plugin's help
        $func_name = 'smarty_cms_help_'.$type.'_'.$plugin;
        ob_start();
        $func_name([]);
        $content = ob_get_contents();
        ob_end_clean();
    } elseif (LangOperations::key_exists("help_{$type}_{$plugin}",'tags')) {
        $content = LangOperations::lang_from_realm('tags',"help_{$type}_{$plugin}");
    } elseif (LangOperations::key_exists("help_{$type}_{$plugin}")) {
        $content = lang("help_{$type}_{$plugin}");
    }

    if ($content) {
        $smarty->assign('subheader',lang('pluginhelp',$plugin))
          ->assign('content',$content);
    } else {
        $smarty->assign('error',lang('nopluginhelp'));
    }
} elseif ($action == 'showpluginabout') {
    $file = $find_file("$type.$plugin.php");
    if (is_file($file)) require_once $file;

    $smarty->assign('subheader',lang('pluginabout',$plugin));
    $func_name = 'smarty_cms_about_'.$type.'_'.$plugin;
    if (function_exists($func_name)) {
        ob_start();
        $func_name([]);
        $content = ob_get_contents();
        ob_end_clean();
        $smarty->assign('content',$content);
    } else {
        $smarty->assign('error',lang('nopluginabout'));
    }
} else {

    if (isset($_POST['upload'])) {
        if (isset($_FILES['pluginfile']) && $pdev) {
            $error = false;
            if (!empty($_FILES['pluginfile']['name'])) {
                $checked = false;
                $file = basename($_FILES['pluginfile']['name']);
                foreach ([
//                'block.*.php',
                'function.*.php',
                'modifier.*.php',
//                'postfilter.*.php',
//                'prefilter.*.php',
                ] as $pattern) {
                    if (fnmatch($pattern, $file, FNM_CASEFOLD)) {
                        $checked = true;
                        $fh = fopen($_FILES['pluginfile']['tmp_name'],'rb');
                        if ($fh) {
                            $content = fread($fh, filesize($_FILES['pluginfile']['tmp_name']));
                            fclose($fh);
                            // required content
                            $pattern = '/function\w+smarty_'.str_replace(['.php','.'], ['','_'], $file).'/';
                            if (preg_match($pattern, $content)) {
                                $fn = cms_join_path($dirs[0], $file); // upload goes into assets
                                if (move_uploaded_file($_FILES['pluginfile']['tmp_name'], $fn)) {
                                    chmod($fn, 0640);
                                    // CHECKME register plugin with smarty?
                                } else {
                                    $error = lang('errorcantcreatefile');
                                }
                            } else {
                                $error = lang('errorwrongfile');
                            }
                        } else {
                            $error = lang('error_internal');
                        }
                        break;
                    }
                }
                if (!$checked) {
                    $error = lang('errorwrongfile');
                }
            } elseif ($_FILES['pluginfile']['error'] > 0 || $_FILES['pluginfile']['size'] == 0) {
                $error = lang('error_uploadproblem');
            }
            if ($error) {
                $themeObject->RecordNotice('error', $error);
            }
        }
    }

    $files = [];
    foreach ($dirs as $one) {
        $files = array_merge($files,glob($one.'/*.php'));
    }

    if ($files) {
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

//TODO            $rec['cachable'] = !($rec['admin'] || function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name']));

            // leave smarty_nocache for compatibility for a while ?? NOPE smarty 3.3.31+ can't process them
            if (!(function_exists('smarty_'.$rec['type'].'_'.$rec['name']) /*||
                  function_exists('smarty_cms_'.$rec['type'].'_'.$rec['name']) ||
                  function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name'])*/)) continue;

            if (function_exists('smarty_cms_help_'.$rec['type'].'_'.$rec['name'])) {
                $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            } elseif (LangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'],'tags')) {
                $rec['help_url'] = $selfurl.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            } elseif (LangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'])) {
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
    $smarty->assign('plugins',$file_array)
      ->assign('iconyes',$themeObject->DisplayImage('icons/system/true.png',lang_by_realm('tags','title_admin'),'','','systemicon'))
      ->assign('iconno',$themeObject->DisplayImage('icons/system/false.png',lang_by_realm('tags','title_notadmin'),'','','systemicon'))
      ->assign('iconcyes',$themeObject->DisplayImage('icons/system/true.png',lang_by_realm('tags','title_cachable'),'','','systemicon'))
      ->assign('iconcno',$themeObject->DisplayImage('icons/system/false.png',lang_by_realm('tags','title_notcachable'),'','','systemicon'))
      ->assign('iconhelp',$themeObject->DisplayImage('icons/system/info.png',lang_by_realm('tags','viewhelp'),'','','systemicon'))
      ->assign('iconabout',$themeObject->DisplayImage('icons/extra/info.png',lang_by_realm('tags','viewabout'),'','','systemicon'))
      ->assign('pdev',$pdev);
}

$smarty->assign([
    'urlext' => $urlext,
    'selfurl' => $selfurl,
]);

include_once 'header.php';
$smarty->display('listtags.tpl');
include_once 'footer.php';
