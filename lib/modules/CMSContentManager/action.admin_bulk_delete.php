<?php
/*
CMSContentManager module action: bulk delete
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

use CMSMS\ContentOperations;
//use CMSMS\Utils;

if( !isset($gCms) ) exit;
if( !isset($action) || $action != 'admin_bulk_delete' ) exit;

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->Redirect($id,'defaultadmin',$returnid);
}
if( !isset($params['bulk_content']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->Redirect($id,'defaultadmin',$returnid);
}

$mod = $this;

function cmscm_admin_bulk_delete_can_delete($node)
{
  global $mod;
  // test if can delete this node (not its children)
  //$mod = Utils::get_module('CMSContentManager');
  if( $mod->CheckPermission('Manage All Content') ) return TRUE;
  if( $mod->CheckPermission('Modify Any Page') && $mod->CheckPermission('Remove Pages') ) return TRUE;
  if( !$mod->CheckPermission('Remove Pages') ) return FALSE;

  $id = (int)$node->get_tag('id');
  if( $id < 1 ) return FALSE;
  if( $id == ContentOperations::get_instance()->GetDefaultContent() ) return FALSE;

  return ContentOperations::get_instance()->CheckPageAuthorship(get_userid(),$id);
}

function cmscm_get_deletable_pages($node)
{
  $out = [];
  if( cmscm_admin_bulk_delete_can_delete($node) ) {
    // we can delete the parent node.
    $out[] = $node->get_tag('id');
    if( $node->has_children() ) {
      // it has children.
      $children = $node->get_children();
      foreach( $children as $child_node ) {
        $tmp = cmscm_get_deletable_pages($child_node);
        $out = array_merge($out,$tmp);
      }
    }
  }
  return $out;
}

$pagelist = $params['bulk_content'];
$hm = cmsms()->GetHierarchyManager();
$contentops = ContentOperations::get_instance();

if( isset($params['submit']) ) {

  if( isset($params['confirm1']) && isset($params['confirm2']) && $params['confirm1'] == 1  && $params['confirm2'] == 1 ) {
    //
    // do the real work
    //
    $i = 0;
    try {
      foreach( $pagelist as $pid ) {
        $node = $hm->quickfind_node_by_id($pid);
        if( !$node ) continue;
        $content = $node->getContent(FALSE,FALSE,TRUE);
        if( !is_object($content) ) continue;
        if( $content->DefaultContent() ) continue;
        $content->Delete();
        $i++;
      }
      if( $i > 0 ) {
        $contentops->SetAllHierarchyPositions();
        $contentops->SetContentModified();
        audit('','Content','Deleted '.$i.' pages');
        $this->SetMessage($this->Lang('msg_bulk_successful'));
      }
    }
    catch( Throwable $t ) {
      $this->SetError($t->getMessage());
    }
    $this->Redirect($id,'defaultadmin',$returnid);
  }
  else {
    $this->SetError($this->Lang('error_notconfirmed'));
    $this->Redirect($id,'defaultadmin',$returnid);
  }
}

$xlist = [];
foreach( $pagelist as $pid ) {
  $node = $hm->quickfind_node_by_id($pid);
  if( !$node ) continue;
  $tmp = cmscm_get_deletable_pages($node);
  $xlist = array_merge($xlist,$tmp);
}
$xlist = array_unique($xlist);

//
// build the confirmation display
//
$contentops->LoadChildren(-1,FALSE,FALSE,$xlist);
$displaydata =  [];
foreach( $xlist as $pid ) {
  $node = $hm->quickfind_node_by_id($pid);
  if( !$node ) continue;  // this should not happen, but hey.
  $content = $node->getContent(FALSE,FALSE,FALSE);
  if( !is_object($content) ) continue; // this should never happen either

  if( $content->DefaultContent() ) {
    $this->ShowErrors($this->Lang('error_delete_defaultcontent'));
    continue;
  }

  $rec = [];
  $rec['id'] = $content->Id();
  $rec['name'] = $content->Name();
  $rec['menutext'] = $content->MenuText();
  $rec['owner'] = $content->Owner();
  $rec['alias'] = $content->Alias();
  $displaydata[] = $rec;
}

if( !$displaydata ) {
  $this->SetError($this->Lang('error_delete_novalidpages'));
  $this->Redirect($id,'defaultadmin',$returnid);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_bulk_delete.tpl')); //,null,null,$smarty);
$tpl->assign('pagelist',$xlist)
 ->assign('displaydata',$displaydata);

$tpl->display();
return '';
