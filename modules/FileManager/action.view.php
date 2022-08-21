<?php
/*
FileManager module action: view
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
if (!isset($params['file'])) {
    $params['fmerror'] = 'nofilesselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$filename = $this->decodefilename($params['file']);
$src = cms_join_path(CMS_ROOT_PATH, Utils::get_cwd(), $filename);
if (!file_exists($src)) {
    $params['fmerror'] = 'filenotfound';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

// get its mime type
$mimetype = Utils::mime_content_type($src);

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) {
    ob_end_clean();
}
//TODO reconcile with CMSMS\sendheaders()
header("Content-Type: $mimetype");
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
echo file_get_contents($src);
exit;
