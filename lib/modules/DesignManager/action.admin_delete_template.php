<?php
# DesignManager module action: delete template
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

$this->SetCurrentTab('templates');
if( !isset($params['tpl']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

try {
  $tpl_ob = TemplateOperations::load_template($params['tpl']);
  if( $tpl_ob->get_owner_id() != get_userid() && !$this->CheckPermission('Modify Templates') ) {
    throw new CmsException($this->Lang('error_permission'));
  }

  if( isset($params['submit']) ) {
    if( !isset($params['check1']) || !isset($params['check2']) ) {
      $this->ShowErrors($this->Lang('error_notconfirmed'));
    }
    else {
      $tpl_ob->delete();
      $this->SetMessage($this->Lang('msg_template_deleted'));
      $this->RedirectToAdminTab();
    }
  }

  $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_delete_template.tpl'),null,null,$smarty);

  // find the number of 'pages' that use this template.
  $db = cmsms()->GetDb();
  $query = 'SELECT * FROM '.CMS_DB_PREFIX.'content WHERE template_id = ?';
  $n = $db->GetOne($query,[$tpl_ob->get_id()]);
  $tpl->assign('page_usage',$n);

  $cats = CmsLayoutTemplateCategory::get_all();
  $out = [];
  $out[0] = $this->Lang('prompt_none');
  if( $cats ) {
    foreach( $cats as $one ) {
      $out[$one->get_id()] = $one->get_name();
    }
  }
  $tpl->assign('category_list',$out);

  $types = CmsLayoutTemplateType::get_all();
  if( $types ) {
    $out = [];
    foreach( $types as $one ) {
      $out[$one->get_id()] = $one->get_langified_display_value();
    }
    $tpl->assign('type_list',$out);
  }

  $designs = CmsLayoutCollection::get_all();
  if( $designs ) {
    $out = [];
    foreach( $designs as $one ) {
      $out[$one->get_id()] = $one->get_name();
    }
    $tpl->assign('design_list',$out);
  }

  $userops = cmsms()->GetUserOperations();
  $allusers = $userops->LoadUsers();
  $tmp = [];
  foreach( $allusers as $one ) {
    $tmp[$one->id] = $one->username;
  }
  if( $tmp ) {
    $tpl->assign('user_list',$tmp);
  }

  $tpl->assign('tpl',$tpl_ob);
  $tpl->display();
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
