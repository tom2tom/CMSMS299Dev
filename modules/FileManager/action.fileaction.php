<?php
/*
FileManager module action: fileaction
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

use FileManager\Utils;

//if (some worthy test fails) exit;
if (!($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed())) {
    exit;
}

if (!isset($params['path'])) {
    $this->Redirect($id, 'defaultadmin');
}
if (!Utils::test_valid_path($params['path'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, ['fmerror' => 'fileoutsideuploads']);
}
$path = $params['path'];

$selfiles = [];
$seldirs = [];
$paramsnofiles = [];
//$somethingselected = false;
foreach ($params as $key => $value) {
    if (substr($key, 0, 5) == 'file_') {
        $selfiles[] = $this->decodefilename(substr($key, 5));
    } elseif (substr($key, 0, 4) == 'dir_') {
        $seldirs[] = $this->decodefilename(substr($key, 4));
    } else {
        $paramsnofiles[$key] = $value;
    }
}

$sel = array_merge($seldirs, $selfiles);

// get the dirs from uploadspath
$dirlist = [];
$filerec = get_recursive_file_list($config['uploads_path'], [], -1, 'DIRS');
//$dirlist[$this->Lang('selecttargetdir')] = '-';
foreach ($filerec as $value) {
    $value1 = str_replace(CMS_ROOT_PATH, '', $value);
    //prevent current dir from showing up
    if ($value1 == $path) {
        continue;
    }
    //Check for hidden items (assumes unix-y hiding)
    $dirs = explode(DIRECTORY_SEPARATOR, $value1);
    foreach ($dirs as $dir) {
        if ($dir !== '' && $dir[0] == '.') {
            continue 2;
        }
    }
    //not hidden, add to list
    $dirlist[$this->Slashes($value1)] = $this->Slashes($value1);
}

$fileaction = $params['fileaction'] ?? '';

if (isset($params['newdir']) || $fileaction == 'newdir') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'method.newdir.php';
    return;
}

if (isset($params['view']) || $fileaction == 'view') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'action.view.php';
    return;
}

if (isset($params['rename']) || $fileaction == 'rename') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'method.rename.php';
    return;
}

if (isset($params['delete']) || $fileaction == 'delete') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'method.delete.php';
    return;
}

if (isset($params['copy']) || $fileaction == 'copy') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'action.copy.php';
    return;
}

if (isset($params['move']) || $fileaction == 'move') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'action.move.php';
    return;
}

if (isset($params['unpack']) || $fileaction == 'unpack') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'method.unpack.php';
    return;
}

if (isset($params['thumb']) || $fileaction == 'thumb') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'method.thumb.php';
    return;
}

if (isset($params['resizecrop']) || $fileaction == 'resizecrop') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'action.resizecrop.php';
    return;
}

if (isset($params['rotate']) || $fileaction == 'rotate') {
    require_once __DIR__.DIRECTORY_SEPARATOR.'action.rotate.php';
    return;
}
$this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmerror' => 'unknownfileaction']);
