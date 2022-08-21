<?php
/*
FileManager module method: delete
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

use CMSMS\Events;
use FileManager\Utils;
use function CMSMS\log_notice;

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

// decode the sellallstuff.
foreach ($sel as &$one) {
    $one = $this->decodefilename($one);
}

// process form
$errors = [];
if (isset($params['submit'])) {
    $advancedmode = Utils::check_advanced_mode();
    $basedir = CMS_ROOT_PATH; //TODO or $config['uploads_path'] ?
    $cwd = Utils::get_cwd();

    foreach ($sel as $file) {
        // build complete path
        $fn = cms_join_path($basedir, $cwd, $file);
        if (!file_exists($fn)) {
            continue;
        } // no error here.

        if (!is_writable($fn)) {
            $errors[] = $this->Lang('error_notwritable', $file);
            continue;
        }

        if (is_dir($fn)) {
            // check to make sure it's empty
            $tmp = scandir($fn);
            if (count($tmp) > 2) { // account for . and ..
                $errors[] = $this->Lang('error_dirnotempty', $file);
                continue;
            }
        }

        $thumb = '';
        if (Utils::is_image_file($file)) {
            // check for thumb, make sure it's writable.
            $thumb = cms_join_path($basedir, $cwd, 'thumb_'.basename($file));
            if (file_exists($fn) && !is_writable($fn)) {
                $errors[] = $this->Lang('error_thumbnotwritable', $file);
            }
        }

        // at this point, we should be good to delete.
        if (is_dir($fn)) {
            @rmdir($fn);
        } else {
            @unlink($fn);
        }
        if ($thumb != '') {
            @unlink($thumb);
        }

        $parms = ['file' => $fn];
        if ($thumb) {
            $parms['thumb'] = $thumb;
        }
        log_notice('File Manager', 'Removed file: '.$fn);
        Events::SendEvent('FileManager', 'OnFileDeleted', $parms);
    } // foreach

    if (!$errors) {
        $paramsnofiles['fmmessage'] = 'deletesuccess'; //strips the file data
        $this->Redirect($id, 'defaultadmin', $returnid, $paramsnofiles);
    }
} // submit

// give everything to smarty
$tpl = $smarty->createTemplate($this->GetTemplateResource('delete.tpl')); //,null,null,$smarty);

if ($errors) {
    $this->ShowErrors($errors);
    $tpl->assign('errors', $errors);
}
if (is_array($params['sel'])) {
    $params['sel'] = rawurlencode(json_encode($params['sel']));
}
$params['delete'] = 1;

//come back via action.fileaction for credentials check
$tpl->assign('sel', $sel)
    ->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params));
$tpl->display();
