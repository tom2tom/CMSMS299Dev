<?php
/*
ContentManager module action: bulk owner-change
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;

//if( some worthy test fails ) exit;
if( !isset($action) || $action != 'admin_bulk_changeowner' ) exit;

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->Redirect($id,'defaultadmin',$returnid);
}
if( !$this->CheckPermission('Manage All Content') ) {
  $this->SetError($this->Lang('error_bulk_permission'));
  $this->Redirect($id,'defaultadmin',$returnid);
}
if( !isset($params['bulk_content']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->Redirect($id,'defaultadmin',$returnid);
}

$pagelist = $params['bulk_content'];
$hm = $gCms->GetHierarchyManager();

if( isset($params['submit']) ) {
  if( !isset($params['confirm1']) || !isset($params['confirm2']) ) {
    $this->SetError($this->Lang('error_notconfirmed'));
    $this->Redirect($id,'defaultadmin',$returnid);
  }
  if( !isset($params['owner']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->Redirect($id,'defaultadmin',$returnid);
  }

  $user_id = get_userid();
  $i = 0;

  try {
    foreach( $pagelist as $pid ) {
      $content = $this->GetContentEditor($pid);
      if( !is_object($content) ) continue;

      $content->SetOwner((int)$params['owner']);
      $content->SetLastModifiedBy($user_id);
      $content->Save();
      ++$i;
    }
    if( $i != count($pagelist) ) {
      throw new Exception('Bulk operation to change ownership did not adjust all selected pages');
    }
    audit('','ContentManager','Changed owner of '.$i.' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
  }
  catch( Throwable $t ) {
      cms_error('', $this->GetName().'::admin_bulk_changeowner', 'Multi-page ownership change failed: '.$t->getMessage());
      $this->SetError($t->getMessage());
  }
  $cache = SingleItem::LoadedData();
// TODO or refresh() & save, ready for next stage ?
  $cache->delete('content_quicklist');
  $cache->delete('content_tree');
  $cache->delete('content_flatlist');

  $this->Redirect($id,'defaultadmin',$returnid);
}

$displaydata = [];
foreach( $pagelist as $pid ) {
  $node = $hm->find_by_tag('id',$pid);
  if( !$node ) continue;  // this should not happen, but hey.
  $content = $node->getContent(FALSE,FALSE,FALSE);
  if( !is_object($content) ) continue; // this should never happen either

  $rec = [];
  $rec['id'] = $content->Id();
  $rec['name'] = $content->Name();
  $rec['menutext'] = $content->MenuText();
  $rec['owner'] = $content->Owner();
  $rec['alias'] = $content->Alias();
  $displaydata[] = $rec;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('bulk_changeowner.tpl')); //,null,null,$smarty);

$tpl->assign('pagelist',$params['bulk_content'])
 ->assign('displaydata',$displaydata);
$userlist = SingleItem::UserOperations()->LoadUsers();
$tmp = [];
foreach( $userlist as $user ) {
  $tmp[$user->id] = $user->username;
}
$tpl->assign('userlist',$tmp)
 ->assign('userid',get_userid());

$tpl->display();
