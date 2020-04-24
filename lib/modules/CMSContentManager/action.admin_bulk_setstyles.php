<?php
# CMSContentManager module action: bulk_setstyles
# Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSContentManager\Utils;
use CMSMS\SysDataCache;

if( !isset($gCms) ) exit;
if( !isset($action) || $action != 'admin_bulk_setstyles' ) exit;

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->Redirect($id,'defaultadmin',$returnid);
}
if( empty($params['bulk_content']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->Redirect($id,'defaultadmin',$returnid);
}

$pagelist = $params['bulk_content'];
$hm = $gCms->GetHierarchyManager();

if( isset($params['submit']) ) {
/*    if( !isset($params['styles']) ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->Redirect($id,'defaultadmin',$returnid);
    }
*/
    $value = (isset($params['styles'])) ? implode(',',$params['styles']) : null; //from checkboxes
    $i = 0;
    $user_id = get_userid();

    try {
        foreach( $pagelist as $pid ) {
            $content = $this->GetContentEditor($pid);
            if( !is_object($content) ) continue;

            $content->SetStyles($value);
            $content->SetLastModifiedBy($user_id);
            $content->Save();
            ++$i;
        }
        audit('','Content','Changed stylesheet on '.$i.' pages');
        $this->SetMessage($this->Lang('msg_bulk_successful'));
    }
    catch( Throwable $t ) {
        cms_warning('Changing styles on multiple pages failed: '.$t->getMessage());
        $this->SetError($t->getMessage());
    }
    $cache = SysDataCache::get_instance();
    $cache->release('content_quicklist');
    $cache->release('content_tree');
    $cache->release('content_flatlist');

    $this->Redirect($id,'defaultadmin',$returnid);
}

list($sheetrows,$grouped,$js) = Utils::get_sheets_data();
if( !$sheetrows ) {
    return; //no style, nothing to set
}
if( $js ) {
    add_page_foottext($js);
}

$displaydata = [];
foreach( $pagelist as $pid ) {
    $node = $hm->find_by_tag('id',$pid);
    if( !$node ) continue;  // this should not happen, but hey.
    $content = $node->getContent(false,false,false);
    if( !is_object($content) ) continue; // this should never happen either

    $rec = [];
    $rec['id'] = $content->Id();
    $rec['name'] = $content->Name();
    $rec['menutext'] = $content->MenuText();
    $rec['owner'] = $content->Owner();
    $rec['alias'] = $content->Alias();
    $displaydata[] = $rec;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_bulk_setstyles.tpl'),null,null,$smarty);

$tpl->assign('pagelist',$pagelist)
 ->assign('displaydata',$displaydata)
 ->assign('grouped',$grouped)
 ->assign('sheets',$sheetrows);

$tpl->display();
return false;
