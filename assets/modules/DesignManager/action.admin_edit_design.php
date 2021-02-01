<?php
/*
DesignManager module action: edit design
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\StylesheetOperations;
use CMSMS\TemplateOperations;
use DesignManager\Design;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage Designs') ) exit;

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->Redirect($id,'defaultadmin',$returnid);
}

try {
  if( empty($params['design']) ) {
    $design = new Design();
    $design->set_name($this->Lang('new_design'));
  }
  else {
    $design = Design::load($params['design']);
  }

  try {
    if( isset($params['submit']) || isset($params['apply']) || !empty($params['ajax']) ) {
      $design->set_name($params['name']); //TODO some validation e.g. unique
      $design->set_description($params['description']);

      if( isset($params['designtpl']) ) {
        $tpl_members = array_keys(array_filter($params['designtpl']));
      }
      else {
        $tpl_members = []; //should never happen
      }
      $design->set_templates($tpl_members);

      if( isset($params['designcss']) ) {
        $css_members = array_keys(array_filter($params['designcss']));
      }
      else {
        $css_members = [];
      }
      $design->set_stylesheets($css_members);
      $design->save();

      if( isset($params['submit']) ) {
        $this->SetMessage($this->Lang('msg_design_saved'));
        $this->Redirect($id,'defaultadmin',$returnid);
      }
      if( isset($params['ajax']) ) {
        //TODO exit;
	  }
      $this->ShowMessage($this->Lang('msg_design_saved'));
    }
  }
  catch( Exception $e ) {
    $this->ShowErrors($e->GetMessage());
  }

  $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_edit_design.tpl')); //,null,null,$smarty);

  $templates = TemplateOperations::get_editable_templates(get_userid());
  if( $templates ) {
    usort($templates,function($a,$b) {
      return strcasecmp($a->get_name(),$b->get_name());
    });
    $names = [];
    for( $i = 0, $n = count($templates); $i < $n; $i++ ) {
      $tpl_id = $templates[$i]->get_id();
      $names[$tpl_id] = $templates[$i]->get_name();
    }
    $tpl->assign('all_templates',$names)
     ->assign('design_templates',$design->get_templates());
  }
  else {
    $tpl->assign('all_templates',$null);
  }

  $stylesheets = StylesheetOperations::get_all_stylesheets(true); //not user-specific
  if( $stylesheets ) {
    uasort($stylesheets,function($a,$b){
      return strcasecmp($a,$b);
    });
    $tpl->assign('all_stylesheets',$stylesheets)
     ->assign('design_stylesheets',$design->get_stylesheets());
  }
  else {
    $tpl->assign('all_stylesheets',null);
  }

  $themeObject = cms_utils::get_theme_object();
  if( $design->get_id() > 0 ) {
    $themeObject->SetSubTitle($this->Lang('edit_design').': '.$design->get_name()." ({$design->get_id()})");
  }
  else {
    $themeObject->SetSubTitle($this->Lang('create_design'));
  }

  $advice = $this->Lang('table_droptip');
  $manage_stylesheets = $this->CheckPermission('Manage Stylesheets');
  $manage_templates = $this->CheckPermission('Modify Templates');
  $tpl->assign('manage_templates',$manage_templates)
   ->assign('manage_stylesheets',$manage_stylesheets)
   ->assign('design',$design)
   ->assign('placeholder',$advice);

  $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 var tbl = $('.draggable'),
  tbod = tbl.find('tbody.rsortable');
  //hide placeholder in tbods with extra rows
  tbod.each(function() {
   var t = $(this);
   if(t.find('>tr').length > 1) {
    t.find('>tr.placeholder').css('display','none');
   }
  });
  tbod.sortable({
  connectWith: '.rsortable',
  items: '> tr:not(".placeholder")',
  appendTo: tbl,
//  helper: 'clone',
  zIndex: 9999
 }).disableSelection();

  tbl.droppable({
  accept: '.rsortable tr',
  hoverClass: 'ui-state-hover', //TODO
  drop: function(ev,ui) {
   //update submittable dropped hidden input
   var row = ui.draggable[0],
    srcbody = row.parentElement || row.parentNode,
    inp = $(row).find('input[type="hidden"]'),
    state = ($(this).hasClass('selected')) ? 1 : 0;
   inp.val(state);
   //adjust display of tbod placeholder rows
   tbod.each(function() {
    var t = $(this),
     len = t.find('> tr').length;
    row = t.find('> tr.placeholder');
    if(len < 2) {
     row.css('display','table');
    } else if(len === 2 && this === srcbody) {
     row.css('display','table');
    } else {
     row.css('display','none');
    }
   });
   return false;
  }
 });
});
//]]>
</script>

EOS;
  add_page_foottext($js);

  $tpl->display();
  return '';
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
