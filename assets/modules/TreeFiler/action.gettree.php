<?php
/*
TreeFiler module action: ajax processor to generate a represntation of a tree of folders
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FilePickerProfile;

if (!function_exists('cmsms')) {
    exit;
}
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
if (!($pdev || $this->CheckPermission('Modify Files'))) exit;

// variables shared with included file
global $CFM_ROOTPATH, $CFM_IS_WIN, $CFM_ICONV_INPUT_ENC, $CFM_EXCLUDE_FOLDERS, $CFM_FOLDER_URL, $CFM_FOLDER_TITLE;

$CFM_ROOTPATH = ($pdev) ? CMS_ROOT_PATH : $config['uploads_path'];
$CFM_RELPATH = $params['p'] ?? '';

$pathnow = $CFM_ROOTPATH;
if ($CFM_RELPATH) {
    $pathnow .= DIRECTORY_SEPARATOR . $CFM_RELPATH;
}
if (!is_dir($pathnow)) { //CHECKME link to a dir ok?
    $pathnow = $CFM_ROOTPATH;
    $CFM_RELPATH = '';
}

$user_id = get_userid(false);
$mod = cms_utils::get_module('FilePicker');
$profile = $mod->get_default_profile($pathnow, $user_id);

$CFM_IS_WIN = DIRECTORY_SEPARATOR == '\\';

$CFM_EXCLUDE_FOLDERS = []; //TODO per profile etc
$CFM_FOLDER_URL = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>'']);
$CFM_FOLDER_TITLE = $this->Lang('goto');
$CFM_SHOW_HIDDEN = $profile->show_hidden;

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

$treecontent = cfm_dir_tree($CFM_ROOTPATH, (($CFM_RELPATH) ? $pathnow : ''));
if (isset($params['ajax'])) {
    echo $treecontent;
    exit;
}
