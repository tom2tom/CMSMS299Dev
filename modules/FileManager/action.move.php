<?php
/*
FileManager module action: move
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use CMSMS\Lone;
use FileManager\Utils;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$sel = $params['sel'];
if (!is_array($sel)) {
    $sel = json_decode(rawurldecode($sel), true);
}
if (!$sel) {
    $params['fmerror'] = 'nofilesselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$dirlist = Utils::get_dirlist();
if (!$dirlist) {
    $params['fmerror'] = 'nodestinationdirs';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

foreach ($sel as &$one) {
    $one = $this->decodefilename($one);
}
unset($one);

$cwd = Utils::get_cwd();
$errors = [];
if (isset($params['destdir'])) { //OR 'submit', 2nd pass, after place-selection
    $destdir = trim($params['destdir']);
    if ($destdir == $cwd) {
        $errors[] = $this->Lang('movedestdirsame');
    }

    $config = Lone::get('Config');
    $advancedmode = Utils::check_advanced_mode();
    $basedir = ($advancedmode) ? CMS_ROOT_PATH : $config['uploads_path'];

    if (!$errors) {
        $destloc = cms_join_path($basedir, $destdir);
        if (!is_dir($destloc) || !is_writable($destloc)) {
            $errors[] = $this->Lang('invalidmovedir');
        }
    }

    if (!$errors) {
        foreach ($sel as $file) {
            $src = cms_join_path(CMS_ROOT_PATH, $cwd, $file);
            $dest = cms_join_path($basedir, $destdir, $file);

            if (!file_exists($src)) {
                $errors[] = $this->Lang('filenotfound')." $file";
                continue;
            }
            if (!is_readable($src)) {
                $errors[] = $this->Lang('insufficientpermission', $file);
                continue;
            }
            if (file_exists($dest)) {
                $errors[] = $this->Lang('fileexistsdest', $file);
                continue;
            }
            if (is_dir($src) && startswith($dest, $src)) {
                $errors[] = $this->Lang('filemovesame', $file);
                continue;
            }

            $thumb = '';
            $src_thumb = '';
            $dest_thumb = '';
            if (Utils::is_image_file($file)) {
                $tmp = 'thumb_'.$file;
                $src_thumb = cms_join_path(CMS_ROOT_PATH, $cwd, $tmp);
                $dest_thumb = cms_join_path($basedir, $destdir, $tmp);

                if (file_exists($src_thumb)) {
                    // have a thumbnail
                    $thumb = $tmp;
                    if (!is_readable($src_thumb)) {
                        $errors[] = $this->Lang('insufficientpermission', $thumb);
                        continue;
                    }
                    if (file_exists($dest_thumb)) {
                        $errors[] = $this->Lang('fileexistsdest', $thumb);
                        continue;
                    }
                }
            }

            // here we can move the file/dir
            $res = rename($src, $dest);
            if (!$res) {
                $errors[] = $this->Lang('movefailed', $file);
                continue;
            }
            if ($thumb) {
                $res = rename($src_thumb, $dest_thumb);
                if (!$res) {
                    $errors[] = $this->Lang('movefailed', $thumb);
                    continue;
                }
            }
        } // foreach
    } // no errors

    if (!$errors) {
        $paramsnofiles['fmmessage'] = 'movesuccess'; //strips the file data
        $this->Redirect($id, 'defaultadmin', $returnid, $paramsnofiles);
    }
} // destdir

if ($errors) {
    $this->ShowErrors($errors);
}
if (is_array($params['sel'])) {
    $params['sel'] = rawurlencode(json_encode($params['sel']));
}
$params['move'] = 1;

$tpl = $smarty->createTemplate($this->GetTemplateResource('move.tpl')); //,null,null,$smarty);
//come back here via action.fileaction, for credentials check
$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params))
    ->assign('cwd', '/'.$cwd)
    ->assign('dirlist', $dirlist)
    ->assign('sel', $sel)
    ->display();
