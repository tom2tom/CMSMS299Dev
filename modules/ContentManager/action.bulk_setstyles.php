<?php
/*
ContentManager module action: bulk_setstyles
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Lone;
use ContentManager\Utils;
use function CMSMS\log_error;
use function CMSMS\log_notice;

if (!$this->CheckContext()) {
    exit;
}

if (isset($params['cancel'])) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->Redirect($id, 'defaultadmin', $returnid);
}
if (!$this->CheckPermission('Manage All Content')) {
    $this->SetError($this->Lang('error_bulk_permission'));
    $this->Redirect($id, 'defaultadmin', $returnid);
}
if (empty($params['bulk_content'])) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->Redirect($id, 'defaultadmin', $returnid);
}

$contentops = Lone::get('ContentOperations');
$pagelist = $params['bulk_content'];
//$hm = $gCms->GetHierarchyManager();

if (isset($params['submit'])) {
/*    if( !isset($params['styles']) ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->Redirect($id,'defaultadmin',$returnid);
    }
*/
    $value = (isset($params['styles'])) ? implode(',', $params['styles']) : null; //from checkboxes
    $user_id = get_userid();
    $n = 0;

    try {
        foreach ($pagelist as $pid) {
            $content = $contentops->LoadEditableContentFromId($pid);
            if (!is_object($content)) {
                continue;
            }

            $content->SetStyles($value);
            $content->SetLastModifiedBy($user_id);
            $content->Save();
            ++$n;
        }
        log_notice('ContentManager', 'Changed stylesheet on '.$n.' pages');
        $this->SetMessage($this->Lang('msg_bulk_successful'));
    } catch (Throwable $t) {
        log_error('Failed to change styles on multiple pages', $t->getMessage());
        $this->SetError($t->getMessage());
    }
//    $cache = Lone::get('LoadedData');
    // TODO or refresh() & save, ready for next stage ?
//    $cache->delete('content_quicklist');
//    $cache->delete('content_tree');
//    $cache->delete('content_flatlist');

    $this->Redirect($id, 'defaultadmin', $returnid);
}

list($sheetrows, $grouped, $js) = Utils::get_sheets_data();
if (!$sheetrows) {
    $this->ShowErrorPage('No stylesheet specified');
    return;
}
if ($js) {
    add_page_foottext($js);
}

$displaydata = [];
foreach ($pagelist as $pid) {
    $content = $contentops->LoadEditableContentFromId($pid);
    if (!is_object($content)) {
        continue; // this should never happen
    }

    $rec = [];
    $rec['id'] = $content->Id();
    $rec['name'] = $content->Name();
    $rec['menutext'] = $content->MenuText();
    $rec['owner'] = $content->Owner();
    $rec['alias'] = $content->Alias();
    $displaydata[] = $rec;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('bulk_setstyles.tpl')); //,null,null,$smarty);

$tpl->assign('pagelist', $pagelist)
    ->assign('displaydata', $displaydata)
    ->assign('grouped', $grouped)
    ->assign('sheets', $sheetrows);

$tpl->display();
