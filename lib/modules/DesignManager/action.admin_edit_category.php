<?php
# DesignManager module action: edit template category.
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
if( !$this->CheckPermission('Modify Templates') ) return;

$this->SetCurrentTab('categories');
if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

try {
  $category = null;
  if( !isset($params['cat']) ) {
    $category = new CmsLayoutTemplateCategory();
    //$category->set_name('New Category');
  }
  else {
    $category = CmsLayoutTemplateCategory::load(trim($params['cat']));
  }
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}

try {
  if( isset($params['submit']) ) {
      $category->set_name(strip_tags($params['name']));
      $category->set_description(strip_tags($params['description']));
      $category->save();
      $this->SetMessage($this->Lang('category_saved'));
      $this->RedirectToAdminTab();
  }
}
catch( CmsException $e ) {
  $this->ShowErrors($e->GetMessage());
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_edit_category.tpl'),null,null,$smarty);
$tpl->assign('category',$category);
$tpl->display();
