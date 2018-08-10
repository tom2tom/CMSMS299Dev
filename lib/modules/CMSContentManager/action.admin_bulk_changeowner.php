<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: Content (c) 2013 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  A module for managing content in CMSMS.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2004 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;
$this->SetCurrentTab('pages');
if( !$this->CheckPermission('Manage All Content') ) {
  $this->SetError($this->Lang('error_bulk_permission'));
  $this->RedirectToAdminTab();
}

if( !isset($params['multicontent']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

$hm = $gCms->GetHierarchyManager();
$pagelist = unserialize(base64_decode($params['multicontent']));

if( isset($params['submit']) ) {
  if( !isset($params['confirm1']) || !isset($params['confirm2']) ) {
    $this->SetError($this->Lang('error_notconfirmed'));
    $this->RedirectToAdminTab();
  }
  if( !isset($params['owner']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
  }

  // do the real work
  try {
    ContentOperations::get_instance()->LoadChildren(-1,FALSE,FALSE,$pagelist);

    $i = 0;
    foreach( $pagelist as $pid ) {
      $node = $hm->find_by_tag('id',$pid);
      if( !$node ) continue;
      $content = $node->getContent(FALSE,FALSE,TRUE);
      if( !is_object($content) ) continue;

      $content->SetOwner((int)$params['owner']);
      $content->SetLastModifiedBy(get_userid());
      $content->Save();
      $i++;
    }
    if( $i != count($pagelist) ) {
      throw new CmsException('Bulk operation to change ownership did not adjust all selected pages');
    }
    audit('','Content','Changed owner on '.count($pagelist).' pages');
    $this->SetMessage($this->Lang('msg_bulk_successful'));
    $this->RedirectToAdminTab();
  }
  catch( Exception $e ) {
      cms_warning('Changing ownership on multiple content items faild: '.$e->GetMessage());
      $this->SetError($e->GetMessage());
      $this->RedirectToAdminTab();
  }
}

$displaydata = array();
foreach( $pagelist as $pid ) {
  $node = $hm->find_by_tag('id',$pid);
  if( !$node ) continue;  // this should not happen, but hey.
  $content = $node->getContent(FALSE,FALSE,FALSE);
  if( !is_object($content) ) continue; // this should never happen either

  $rec = array();
  $rec['id'] = $content->Id();
  $rec['name'] = $content->Name();
  $rec['menutext'] = $content->MenuText();
  $rec['owner'] = $content->Owner();
  $rec['alias'] = $content->Alias();
  $displaydata[] = $rec;
}

$smarty->assign('multicontent',$params['multicontent']);
$smarty->assign('displaydata',$displaydata);
$userlist = UserOperations::get_instance()->LoadUsers();
$tmp = array();
foreach( $userlist as $user ) {
  $tmp[$user->id] = $user->username;
}
$smarty->assign('userlist',$tmp);
$smarty->assign('userid',get_userid());

echo $this->ProcessTemplate('admin_bulk_changeowner.tpl');

#
# EOF
#
