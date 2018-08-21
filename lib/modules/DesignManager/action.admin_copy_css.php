<?php
# DesignManager module action: copy stylesheet
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
if( !$this->CheckPermission('Manage Stylesheets') ) return;

$this->SetCurrentTab('stylesheets');
if( !isset($params['css']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

try {
  $orig_css = CmsLayoutStylesheet::load($params['css']);
  if( isset($params['submit']) || isset($params['submitandedit']) ) {
    try {
      $new_css = clone($orig_css);
      $new_css->set_name(trim($params['new_name']));
      $new_css->set_designs([]);
      $new_css->save();

      if( isset($params['submitandedit']) ) {
        $this->SetMessage($this->Lang('msg_stylesheet_copied_edit'));
        $this->Redirect($id,'admin_edit_css',$returnid,array('css'=>$new_css->get_id()));
      }
      else {
        $this->SetMessage($this->Lang('msg_stylesheet_copied'));
        $this->RedirectToAdminTab();
      }
    }
    catch( Exception $e ) {
      $this->ShowErrors($e->GetMessage());
    }
  }

  // build a display
  $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_copy_css.tpl'),null,null,$smarty);

  $designs = CmsLayoutCollection::get_all();
  if( count($designs) ) {
    $tmp = [];
    for( $i = 0, $n = count($designs); $i < $n; $i++ ) {
      $tmp[$designs[$i]->get_id()] = $designs[$i]->get_name();
    }
    $tpl->assign('design_names',$tmp);
  }

  $tpl->assign('css',$orig_css);
  $tpl->display();
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
