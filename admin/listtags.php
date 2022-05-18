<?php
/*
Procedure to list all plugins (a.k.a. tags)
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

use CMSMS\Error403Exception;
use CMSMS\LangOperations;
use CMSMS\Lone;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid(false);
$access = check_permission($userid, 'View Tag Help');
$pdev = $config['develop_mode'] || check_permission($userid, 'Modify Restricted Files');

if (!($access || $pdev)) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

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
		if (is_file($fn)) { return $fn; }
    }
};

de_specialize_array($_GET);

$plugin = (isset($_GET['plugin'])) ? sanitizeVal($_GET['plugin'], CMSSAN_FILE) : ''; // name consistent with filesystem file
$type = (!empty($_GET['type'])) ? preg_replace('/[^a-zA-Z]/','',$_GET['type']) : ''; //'function', 'modifier' etc letters-only
$action = (!empty($_GET['action'])) ? preg_replace('/[^a-z]/','',$_GET['action']) : ''; //'showpluginhelp' etc specific, letters-only

$smarty = Lone::get('Smarty');
$selfurl = basename(__FILE__);
$urlext = get_secure_param();
$themeObject = Lone::get('Theme');

if ($action == 'showpluginhelp') {
    $content = '';
    $file = $find_file("$type.$plugin.php");
    if (is_file($file)) require_once $file;

    // Get the plugin's help, if any
    if (function_exists('smarty_cms_help_'.$type.'_'.$plugin)) {
        $func_name = 'smarty_cms_help_'.$type.'_'.$plugin;
        ob_start();
        $func_name();
        $content = ob_get_clean();
    } elseif (LangOperations::key_exists("help_{$type}_{$plugin}",'tags')) {
        $content = LangOperations::domain_string('tags',"help_{$type}_{$plugin}");
    } elseif (LangOperations::key_exists("help_{$type}_{$plugin}")) {
        $content = _la("help_{$type}_{$plugin}");
    }

    if ($content) {
        $smarty->assign('subheader',_la('pluginhelp',$plugin))
          ->assign('content',$content);
    } else {
        $smarty->assign('error',_la('nopluginhelp'));
    }
} elseif ($action == 'showpluginabout') {
    $file = $find_file("$type.$plugin.php");
    if (is_file($file)) require_once $file;

    $smarty->assign('subheader',_la('pluginabout',$plugin));
    $func_name = 'smarty_cms_about_'.$type.'_'.$plugin;
    if (function_exists($func_name)) {
        ob_start();
        $func_name([]);
        $content = ob_get_contents();
        ob_end_clean();
        $smarty->assign('content',$content);
    } else {
        $smarty->assign('error',_la('nopluginabout'));
    }
} else {

    if ($pdev && isset($_POST['upload'])) {
        if (isset($_FILES['pluginfile'])) {
            $error = false;
            if (!empty($_FILES['pluginfile']['name'])) {
                $checked = false;
                $file = basename($_FILES['pluginfile']['name']);
                $file = sanitizeVal($file, CMSSAN_FILE);
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
                                if (cms_move_uploaded_file($_FILES['pluginfile']['tmp_name'], $fn)) {
                                    // CHECKME register plugin with smarty?
                                } else {
                                    $error = _la('errorcantcreatefile');
                                }
                            } else {
                                $error = _la('errorwrongfile');
                            }
                        } else {
                            $error = _la('error_internal');
                        }
                        break;
                    }
                }
                if (!$checked) {
                    $error = _la('errorwrongfile');
                }
            } elseif ($_FILES['pluginfile']['error'] > 0 || $_FILES['pluginfile']['size'] == 0) {
                $error = _la('error_uploadproblem');
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
                $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
            } elseif (LangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'],'tags')) {
                $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
            } elseif (LangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'])) {
                $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
            }

            if (function_exists('smarty_cms_about_'.$rec['type'].'_'.$rec['name'])) {
                $rec['about_url'] = $selfurl.$urlext.'&action=showpluginabout&plugin='.$rec['name'].'&type='.$rec['type'];
            }

            $file_array[] = $rec;
        }
    }

    // add in standard tags...
    $rec = ['type'=>'function','name'=>'content'];
    $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
    $file_array[] = $rec;

    $rec = ['type'=>'function','name'=>'content_image'];
    $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
    $file_array[] = $rec;

    $rec = ['type'=>'function','name'=>'content_module'];
    $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
    $file_array[] = $rec;

    $rec = ['type'=>'function','name'=>'process_pagedata'];
    $rec['help_url'] = $selfurl.$urlext.'&action=showpluginhelp&plugin='.$rec['name'].'&type='.$rec['type'];
    $file_array[] = $rec;

    usort($file_array, function($a,$b)
     {
        return strcmp($a['name'],$b['name']);
     });
    $smarty->assign([
      'pdev' => $pdev,
	  'plugins' => $file_array,
      'iconyes' => $themeObject->DisplayImage('icons/system/true.png',_ld('tags','title_admin'),'','','systemicon'),
      'iconno' => $themeObject->DisplayImage('icons/system/false.png',_ld('tags','title_notadmin'),'','','systemicon'),
      'iconcyes' => $themeObject->DisplayImage('icons/system/true.png',_ld('tags','title_cachable'),'','','systemicon'),
      'iconcno' => $themeObject->DisplayImage('icons/system/false.png',_ld('tags','title_notcachable'),'','','systemicon'),
      'iconhelp' => $themeObject->DisplayImage('icons/system/info.png',_ld('tags','viewhelp'),'','','systemicon'),
      'iconabout' => $themeObject->DisplayImage('icons/extra/info.png',_ld('tags','viewabout'),'','','systemicon'),
	]);
}

$smarty->assign([
    'urlext' => $urlext,
    'selfurl' => $selfurl,
]);

$content = $smarty->fetch('listtags.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
