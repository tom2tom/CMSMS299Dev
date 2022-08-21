<?php
/*
FileManager module action: rename
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
if (count($sel) > 1) {
    $params['fmerror'] = 'morethanonefiledirselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$config = Lone::get('Config');

$oldname = $this->decodefilename($sel[0]);
$newname = $oldname; //for initial input box

if (isset($params['newname'])) {
    $cwd = Utils::get_full_cwd();
    $newname = trim($params['newname']);
    $fullnewname = cms_join_path($cwd, $newname);
    if (!Utils::is_valid_filename($fullnewname)) {
        $this->ShowErrors($this->Lang('invaliddestname'));
    } elseif (file_exists($fullnewname)) {
        $this->ShowErrors($this->Lang('namealreadyexists'));
    } else {
        $fulloldname = cms_join_path($cwd, $oldname);
        if (@rename($fulloldname, $fullnewname)) {
            $thumboldname = cms_join_path($cwd, 'thumb_'.$oldname);
            if (file_exists($thumboldname)) {
                $thumbnewname = cms_join_path($cwd, 'thumb_'.$newname);
                @rename($thumboldname, $thumbnewname);
            }
            $this->SetMessage($this->Lang('renamesuccess'));
            log_notice('File Manager', 'Renamed file: '.$fullnewname);
        } else {
            $this->SetError($this->Lang('renameerror'));
        }
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
    }
}

if (is_array($params['sel'])) {
    $params['sel'] = rawurlencode(json_encode($params['sel']));
}
$params['rename'] = 1;
$tpl = $smarty->createTemplate($this->GetTemplateResource('renamefile.tpl')); //,null,null,$smarty);
// come back here via action.fileaction, for extra credentials checking
$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params))
    ->assign('newnametext', $this->lang('newname'))
    ->assign('newname', $newname)
    ->assign('newnameinput', $this->CreateInputText($id, 'newname', $newname, 40))
    ->display();
