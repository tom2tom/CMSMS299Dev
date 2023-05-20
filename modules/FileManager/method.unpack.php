<?php
/*
FileManager module method: unpack
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use wapmorgan\UnifiedArchive\UnifiedArchive;
use function CMSMS\log_notice;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}
if (!Lone::get('Config')['develop_mode']) {
    exit; // TODO interrgotate contents, process each item if valid
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
if (count($sel) > 1) {
    $params['fmerror'] = 'morethanonefiledirselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

//$config = Lone::get('Config');
$filename = $this->decodefilename($sel[0]);
$src = cms_join_path(CMS_ROOT_PATH, Utils::get_cwd(), $filename);
if (!file_exists($src)) {
    $params['fmerror'] = 'filenotfound';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$res = false;
try {
    // archive-classes autoloading
    if (1) { // TODO not already registered
        spl_autoload_register(['FileManager\Utils', 'ArchAutoloader']);
    }
    require_once cms_join_path(__DIR__, 'lib', 'UnifiedArchive', 'UnifiedArchive.php');
    $archive = UnifiedArchive::open($src);
    if ($archive) {
        $destdir = cms_join_path(CMS_ROOT_PATH, Utils::get_cwd());
        $fs = disk_free_space($destdir);
        if ($fs > $archive->getOriginalSize()) {
            if (!endswith($destdir, DIRECTORY_SEPARATOR)) {
                $destdir = rtrim($destdir, '/\\').DIRECTORY_SEPARATOR; //TODO needed ?
            }
            //TODO prevent 'zip-slip', invalid destinations etc
            //i.e. interrogate archive contents, process each item that's valid
            $archive->extractFiles($destdir);
            $res = true; // even if 0 files processed
            //ETC
        } else {
            //TODO report something
        }
    } else {
        //TODO report something
    }
} catch (Throwable $t) {
    //TODO report something
}

if ($res) {
    $params['fmmessage'] = 'unpacksuccess'; //strips the file data
    log_notice('File Manager', 'Unpacked file: '.$src);
} else {
    //TODO
//    $params['fmerror'] = 'something';
//    log_error('File Manager',$subject);
}
$this->Redirect($id, 'defaultadmin', $returnid, $params);
