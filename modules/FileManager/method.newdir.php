<?php
/*
FileManager module action: newdir
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
use function CMSMS\log_notice;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

if (isset($params['newdirname'])) {
    $newdirname = trim($params['newdirname']);
    $newdir = cms_join_path(CMS_ROOT_PATH, $params['path'], $newdirname);
    if (!Utils::is_valid_dirname($newdir)) {
        $this->ShowErrors($this->Lang('invalidnewdir'));
    } elseif (is_dir($newdir)) {
        $this->ShowErrors($this->Lang('direxists'));
    } elseif (mkdir($newdir, 0771, true)) {
        $params['fmmessage'] = 'newdirsuccess';
        log_notice('File Manager', 'Created new directory: ' . $params['newdirname']);
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
    } else {
        $params['fmerror'] = 'newdirfail';
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
    }
} else {
    $newdirname = '';
}
$params['newdir'] = 1;
$tpl = $smarty->createTemplate($this->GetTemplateResource('newdir.tpl')); //,null,null,$smarty);
// come back here via action.fileaction, for extra credentials checking
$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params))
    ->assign('newdirtext', $this->lang('newdir'))
    ->assign('newdirname', $newdirname)
    ->display();
