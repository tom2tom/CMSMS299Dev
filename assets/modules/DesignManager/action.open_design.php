<?php
/*
DesignManager module action: edit design
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FormUtils;
use CMSMS\StylesheetOperations;
use CMSMS\TemplateQuery;
use CMSMS\Utils;
use DesignManager\Design;

//if( some worthy test fails ) exit;
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
//TODO consider ajax-processing for form apply-button clicks
    try {
        if( isset($params['submit']) || isset($params['apply']) ) { // || !empty($params['ajax']) ) {
            // TODO $params[] validation / sanitization
            $design->set_name($params['name']); //TODO unique-check
            $design->set_description($params['description']);

            if( isset($params['designtpl']) ) {
                $tpl_members = explode(',', $params['designtpl']);
            }
            else { //should never happen
                $tpl_members = [];
            }
            $design->set_templates($tpl_members);

            if( isset($params['designcss']) ) {
                $css_members = explode(',', $params['designcss']);
            }
            else { //should never happen
                $css_members = [];
            }
            $design->set_stylesheets($css_members);
            $design->save();

            if( isset($params['submit']) ) {
                $this->SetMessage($this->Lang('msg_design_saved'));
                $this->Redirect($id,'defaultadmin',$returnid);
            }
/* TODO ajax for apply-clicks ?
            if( isset($params['ajax']) ) {
                exit;
            }
*/
            $this->ShowMessage($this->Lang('msg_design_saved'));
        }
    }
    catch( Throwable $t ) {
        $this->ShowErrors($t->GetMessage());
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('open_design.tpl')); //,null,null,$smarty);

    $qry = new TemplateQuery(['o:'=>'core']);
    $templates = $qry->GetMatches();
    if( $templates ) {
        $all = [];
        for( $i = 0, $n = count($templates); $i < $n; $i++ ) {
            $obj = $templates[$i];
            $tpl_id = $obj->id;
            $when = $obj->modified_date;
            if( !$when ) { $when = $obj->create_date; } // or something for undefined?
            $all[$tpl_id] = [
                'name' => $obj->name,
                'desc' => $obj->description,
                'when' => $when
            ];
        }
        uasort($all,function($a,$b) {
            return strcasecmp($a['name'],$b['name']);
        });
        $in = $design->get_templates();
        $out = [];
        foreach( $in as $tpl_id ) {
            if( isset($all[$tpl_id]) ) {
                $out[$tpl_id] = $all[$tpl_id];
            }
        }
        $tpl->assign('design_templates',$out)
         ->assign('undesign_templates',array_diff_key($all,$out));
    }
    else {
        $tpl->assign('design_templates',null)
         ->assign('undesign_templates',null);
    }

    $stylesheets = StylesheetOperations::get_all_stylesheets(); // TODO only for core originator
    if( $stylesheets ) {
        $all = [];
        for( $i = 0, $n = count($stylesheets); $i < $n; $i++ ) {
            $obj = $stylesheets[$i];
            $val = $obj->originator;
            if( $val && $val !== '__CORE__' ) { continue; }
            $cssid = $obj->id;
            $when = $obj->modified_date;
            if( !$when ) { $when = $obj->create_date; } //   or undefined?
            $all[$cssid] = [
                'name' => $obj->name,
                'desc' => $obj->description,
                'when' => $when
            ];
        }
        uasort($all,function($a,$b) {
            return strcasecmp($a['name'],$b['name']);
        });
        $in = $design->get_stylesheets();
        $out = [];
        foreach( $in as $cssid ) {
            if( isset($all[$cssid]) ) {
                $out[$cssid] = $all[$cssid];
            }
        }
        $tpl->assign('design_stylesheets',$out)
         ->assign('undesign_stylesheets', array_diff_key($all,$out));
    }
    else {
        $tpl->assign('undesign_stylesheets',null)
         ->assign('design_stylesheets',null);
    }

    $design_name = $design->get_name();
    $themeObject = Utils::get_theme_object();
    if( $design->get_id() > 0 ) {
        $tpl->assign('title',$this->Lang('edit_design'));
        $themeObject->SetSubTitle($this->Lang('title_edit_design').": $design_name ({$design->get_id()})");
    }
    else {
        $tpl->assign('title',$this->Lang('new_design'));
    }

    $did = $design->get_id();
    $design_desc = $design->get_description();
    $manage_stylesheets = $this->CheckPermission('Manage Stylesheets');
    $manage_templates = $this->CheckPermission('Modify Templates');
    $formstart = FormUtils::create_form_start($this, [
        'id' => $id, 'action' => 'open_design',
        'extraparms' => [
            'design'=>$did, 'designcss'=>'', 'designtpl'=>'', 'active_tab' => ''
            ]
    ]);
    $activetab = $params['active_tab'] ?? '';
    $extras = []; // no need

    $tpl->assign('manage_templates',$manage_templates)
     ->assign('manage_stylesheets',$manage_stylesheets)
     ->assign('name',$design_name)
     ->assign('description',$design_desc)
     ->assign('form_start',$formstart)
     ->assign('tab',$activetab)
     ->assign('extraparms',$extras);
// TODO ajax for apply-btn clicks ?
    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 var tbl = $('.draggable'),
  tbod = tbl.find('tbody.rsortable');
 //hide placeholder in tbods with extra rows
 tbod.each(function() {
  var t = $(this);
  if (t.children('tr').length > 1) {
   t.children('tr.placeholder').css('display', 'none');
  }
 });
 tbod.sortable({
  connectWith: '.rsortable',
  items: '> tr:not(".placeholder")',
  appendTo: tbl,
//helper: 'clone',
  zIndex: 9999
 }).disableSelection();
 tbl.droppable({
  accept: '.rsortable tr',
  hoverClass: 'ui-state-hover', //TODO
  drop: function(ev, ui) {
   //update submittable dropped hidden input
   var row = ui.draggable[0],
    srcbody = row.parentElement || row.parentNode,
    inp = $(row).find('input[type="hidden"]'),
    state = ($(this).hasClass('selected')) ? 1 : 0;
   inp.val(state);
   //adjust display of tbod placeholder rows
   tbod.each(function() {
    var t = $(this),
     len = t.children('tr').length;
    row = t.children('tr.placeholder');
    if (len < 2) {
     row.css('display', 'table');
    } else if (len === 2 && this === srcbody) {
     row.css('display', 'table');
    } else {
     row.css('display', 'none');
    }
   });
   return false;
  }
 });
 $('[name="{$id}submit"],[name="{$id}apply"]').on('click', function() {
  var at = $('#page_tabs > .active').attr('id');
  $('[name="{$id}active_tab"]').val(at);
  var members = [];
  $('#designsheets > tbody').find('.rowid').each(function() {
   members.push(this.textContent);
  });
  $('[name="{$id}designcss"]').val(members.join());
  members = [];
  $('#designtemplates > tbody').find('.rowid').each(function() {
   members.push(this.textContent);
  });
  $('[name="{$id}designtpl"]').val(members.join());
 });
});
//]]>
</script>

EOS;
    add_page_foottext($js);

    $tpl->display();
}
catch( Throwable $t ) {
    $this->SetError($t->GetMessage());
    $this->RedirectToAdminTab();
}
