<?php
/*
FileManager module action: thumb
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
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}
if (!isset($params['thumb'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$sel = $params['sel'];
if (!is_array($sel)) {
    $sel = json_decode(rawurldecode($sel), true);
}
unset($params['sel']);

if (!$sel) {
    $params['fmerror'] = 'nofilesselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$basedir = cms_join_path(CMS_ROOT_PATH, Utils::get_cwd());

foreach ($sel as $one) {
    $filename = $this->decodefilename($one);
    $src = $basedir.DIRECTORY_SEPARATOR.$filename;
    if (!file_exists($src)) {
        $params['fmerror'] = 'filenotfound';
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
    }

    if (Utils::create_thumbnail($src)) {
        $params['fmmessage'] = 'thumbsuccess'; // maybe overwritten
    } else {
        $params['fmerror'] = 'thumberror'; // ditto
    }
}

$this->Redirect($id, 'defaultadmin', $returnid, $params);
