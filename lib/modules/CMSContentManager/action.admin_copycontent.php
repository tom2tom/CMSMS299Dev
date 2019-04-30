<?php
# CMSContentManager module action: copy content
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

if( !isset($gCms) ) exit;

//
// init
//
$this->SetCurrentTab('pages');

//
// validation
//
if( !isset($params['page']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$content_id = (int)$params['page'];
if( $content_id < 1 ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}

//
// get the data
//
if( !$this->CanEditContent($content_id) ) {
  $this->SetError($this->Lang('error_copy_permission'));
  $this->RedirectToAdminTab();
}

$hm = cmsms()->GetHierarchyManager();
$node = $hm->find_by_tag('id',$content_id);
if( !$node ) {
  $this->SetError($this->Lang('error_invalidpageid'));
  $this->RedirectToAdminTab();
}
$from_obj = $node->getContent(FALSE,FALSE,FALSE);
if( !$from_obj ) {
  $this->SetError($this->Lang('error_invalidpageid'));
  $this->RedirectToAdminTab();
}
$from_obj->GetAdditionalEditors();
$from_obj->HasProperty('anything'); // forces properties to be loaded.

$to_obj = clone $from_obj;
$to_obj->SetURL('');
$to_obj->SetName('Copy of '.$from_obj->Name());
$to_obj->SetMenuText('Copy of '.$from_obj->MenuText());
$to_obj->SetAlias();
$to_obj->SetDefaultContent(0);
$to_obj->SetOwner(get_userid());
$to_obj->SetLastModifiedBy(get_userid());
$_SESSION['__cms_copy_obj__'] = serialize($to_obj);
$this->Redirect($id,'admin_editcontent','',['content_id'=>'copy']);
