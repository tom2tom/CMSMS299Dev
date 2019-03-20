<?php
# DesignManager module action: edit design
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
if( !$this->CheckPermission('Manage Designs') ) return;

$this->SetCurrentTab('designs');
if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

$design = null;
try {
  if( !isset($params['design']) || $params['design'] == '' ) {
    $design= new CmsLayoutCollection();
    $design->set_name($this->Lang('new_design'));
  }
  else {
    $design = CmsLayoutCollection::load($params['design']);
  }

  try {
    if( isset($params['submit']) || isset($params['apply']) || (isset($params['ajax']) && $params['ajax'] == '1') ) {
      $design->set_name($params['name']);
      $design->set_description($params['description']);
      $tpl_assoc = [];
      if( isset($params['assoc_tpl']) ) $tpl_assoc = $params['assoc_tpl'];
      $design->set_templates($tpl_assoc);

      $css_assoc = [];
      if( isset($params['assoc_css']) ) $css_assoc = $params['assoc_css'];
      $design->set_stylesheets($css_assoc);
      $design->save();

      if( isset($params['submit']) ) {
        $this->SetMessage($this->Lang('msg_design_saved'));
        $this->RedirectToAdminTab();
      }
      else {
        $this->ShowMessage($this->Lang('msg_design_saved'));
      }
    }
  }
  catch( Exception $e ) {
    $this->ShowErrors($e->GetMessage());
  }

  $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_edit_design.tpl'),null,null,$smarty);

  $templates = TemplateOperations::get_editable_templates(get_userid());
  if( $templates ) {
    usort($templates,function($a,$b) {
      return strcasecmp($a->get_name(),$b->get_name());
    });
    $tpl->assign('all_templates',$templates);
  }

  $stylesheets = CmsLayoutStylesheet::get_all();
  if( $stylesheets ) {
    usort($stylesheets,function($a,$b){
      return strcasecmp($a->get_name(),$b->get_name());
    });
    $out = [];
    $out2 = [];
    for( $i = 0, $n = count($stylesheets); $i < $n; $i++ ) {
      $out[$stylesheets[$i]->get_id()] = $stylesheets[$i]->get_name();
      $out2[$stylesheets[$i]->get_id()] = $stylesheets[$i];
    }
    $tpl->assign('list_stylesheets',$out)
     ->assign('all_stylesheets',$out2);
  }

  $themeObject = cms_utils::get_theme_object();
  if( $design->get_id() > 0 ) {
    $themeObject->SetSubTitle($this->Lang('edit_design').': '.$design->get_name()." ({$design->get_id()})");
  }
  else {
    $themeObject->SetSubTitle($this->Lang('create_design'));
  }

  $manage_stylesheets = $this->CheckPermission('Manage Stylesheets');
  $manage_templates = $this->CheckPermission('Modify Templates');
  $tpl->assign('manage_templates',$manage_templates)
   ->assign('design',$design);

//TODO ensure flexbox css for .rowbox.expand, .boxchild
  //designs tab
/*
function save_design() {
  var form = $('#admin_edit_design');
  var action = form.attr('action');

  $('#ajax').val(1);
  return $.ajax({
    url: action,
    data: form.serialize()
  });
}
*/
    $out = <<<EOS
<script type="text/javascript">
//<![CDATA[
var __changed = 0;
function set_changed() {
  __changed = 1;
  console.debug('design is changed');
}
$(document).ready(function() {
  $('.sortable-list input[type="checkbox"]').hide();
  $(':input').on('change', function() {
    set_changed();
  });
  $('ul.available-items').on('click', 'li', function () {
    $(this).toggleClass('selected ui-state-hover');
  });
  $('#submitme,#applyme').on('click', function() {
    $('select.selall').attr('multiple','multiple');
    $('select.selall option').attr('selected','selected');
  });
});
EOS;
  $this->AdminBottomContent($out);

// stylesheets tab
/* TODO conform to theme, if used
  $out = <<<EOS
<style type="text/css">
#available-stylesheets li.selected {
 background-color: #147fdb;
}
#available-stylesheets li:focus {
 color: #147fdb;
}
#selected-stylesheets li a:focus {
 color: #147fdb;
}
#selected-stylesheets a.ui-icon+a:focus {
 border: 2px solid #147fdb;
}
</style>
EOS;
  $this->AdminHeaderContent($out);
*/

  $out = <<<EOS
$(document).ready(function() {
  var _edit_url = '{cms_action_url action=admin_edit_css css=xxxx forjs=1}';
  $('ul.sortable-stylesheets').sortable({
    connectWith: '#selected-stylesheets ul',
    delay: 150,
    revert: true,
    placeholder: 'ui-state-highlight',
    items: 'li:not(.placeholder)',
    helper: function(event, ui) {
    if(!ui.hasClass('selected')) {
      ui.addClass('selected').siblings().removeClass('selected');
    }
    var elements = ui.parent().children('.selected').clone(),
      helper = $('<li/>');
    ui.data('multidrag', elements).siblings('.selected').remove();
    return helper.append(elements);
    },
    stop: function(event, ui) {
    var elements = ui.item.data('multidrag');
    ui.item.after(elements).remove();
    },
    receive: function(event, ui) {
    var elements = ui.item.data('multidrag');
    $('.sortable-stylesheets .placeholder').hide();
    $(elements).removeClass('selected ui-state-hover')
      .append($('<a href="#"/>')
      .addClass('ui-icon ui-icon-trash sortable-remove')
      .text('{$this->Lang('remove')}'))
      .find('input[type="checkbox"]')
      .attr('checked', true);
    }
  });
  $('#available-stylesheets li').on('click', function(ev) {
    $(this).focus();
  });
  $('#selected-stylesheets li').on('click', function(ev) {
    $('a:first', this).focus();
  });
  $('#available-stylesheets li').on('keyup', function(ev) {
    if(ev.keyCode === $.ui.keyCode.ESCAPE) {
    // escape
    $('#selected-stylesheets li').removeClass('selected');
    ev.preventDefault();
    } else if(ev.keyCode === $.ui.keyCode.SPACE || ev.keyCode === 107) {
    // spacebar or plus
    ev.preventDefault();
    $(this).toggleClass('selected ui-state-hover');
    find_sortable_focus(this);
    } else if(ev.keyCode == 39) {
    // right arrow
    ev.preventDefault();
    $('#available-stylesheets li.selected').each(function() {
      $(this).removeClass('selected ui-state-hover');
      var _css_id = $(this).data('cmsms-item-id');
      var _url = _edit_url.replace('xxx', _css_id);
      var _text = $(this).text().trim();
      var _el = $(this).clone();
      var _a = $('<a/>')
        .attr('href', _url)
        .text(_text)
        .addClass('edit_css unsaved')
        .attr('title', '{$this->Lang('edit_stylesheet')}');
      $('span', _el).remove();
      $(_el).append(_a);
      $(_el).removeClass('selected ui-state-hover')
        .attr('tabindex', -1)
        .addClass('unsaved no-sort')
        .append($('<a href="#"/>')
        .addClass('ui-icon ui-icon-trash sortable-remove')
        .text('{$this->Lang('remove')}')
        .attr('title', '{$this->Lang('remove')}'))
        .find('input[type="checkbox"]')
        .attr('checked', true);
      $('#selected-stylesheets > ul').append(_el);
      $(this).remove();
      set_changed();
      // set focus somewhere
      find_sortable_focus(this);
    });
    }
  });
  $('#selected-stylesheets .sortable-remove').on('click', function(e) {
    e.preventDefault();
    set_changed();
    $(this).next('input[type="checkbox"]').attr('checked', false);
    $(this).parent('li').appendTo('#available-stylesheets ul');
    $(this).remove();
  });
  $('a.edit_css').on('click', function(ev) {
    if(__changed) {
    ev.preventDefault();
    var el = this;
    cms_confirm('{$this->Lang('confirm_save_design')}','{$this->Lang('yes')}').done(function() {
      // save and redirect
      save_design().done(function() {
        window.location = $(el).attr('href');
      });
    });
    return false;
    }
  });
});
EOS;
  $this->AdminBottomContent($out);

// templates tab
/* TODO conform to theme, if used
  $out = <<<EOS
<style type="text/css">
#available-templates li.selected {
 background-color: #147fdb;
}
#template_sel li:focus {
 color: #147fdb;
}
#template_sel li a:focus {
 color: #147fdb;
}
#template_sel a.ui-icon+a:focus {
 border: 2px solid #147fdb;
}
</style>
EOS;
  $this->AdminHeaderContent($out);
*/
    $out = <<<EOS
function find_sortable_focus(in_e) {
  var _list = $(':tabbable');
  var _idx = _list.index(in_e);
  var _out_e = _list.eq(_idx + 1).length ? _list.eq(_idx + 1) : _list.eq(0);
  _out_e.focus();
}

$(document).ready(function() {
  var _edit_url = '{cms_action_url action=admin_edit_template tpl=xxxx forjs=1}';
  $('ul.sortable-templates').sortable({
    connectWith: '#selected-templates ul',
    delay: 150,
    revert: true,
    placeholder: 'ui-state-highlight',
    items: 'li:not(.no-sort)',
    helper: function(event, ui) {
    if(!ui.hasClass('selected')) {
      ui.addClass('selected').siblings().removeClass('selected');
    }
    var elements = ui.parent().children('.selected').clone(),
      helper = $('<li/>');
    ui.data('multidrag', elements).siblings('.selected').remove();
    return helper.append(elements);
    },
    stop: function(event, ui) {
    var elements = ui.item.data('multidrag');
    ui.item.after(elements).remove();
    },
    receive: function(event, ui) {
    var elements = ui.item.data('multidrag');
    $('.sortable-templates .placeholder').hide();
    $(elements).each(function() {
      var _tpl_id = $(this).data('cmsms-item-id');
      var _url = _edit_url.replace('xxxx', _tpl_id);
      var _text = $(this).text().trim();
      var _e;
      if($manage_templates) {
        _e = $('<a/>').attr('href', _url)
        .text(_text)
        .addClass('edit_tpl unsaved')
        .attr('title', '{$this->Lang('edit_template')}');
      } else {
        _e = $('<span/>').text(_text);
      }
      $('span', this).remove();
      $(this).append(_e);
      $(this).removeClass('selected ui-state-hover')
        .attr('tabindex', -1)
        .addClass('unsaved no-sort')
        .append($('<a href="#"/>')
        .addClass('ui-icon ui-icon-trash sortable-remove')
        .text('{$this->Lang('remove')}'))
        .find('input[type="checkbox"]')
        .attr('checked', true);
    });
    set_changed();
    }
  });
  $('#available-templates li').on('click', function(ev) {
    $(this).focus();
  });
  $('#selected-templates li').on('click', function(ev) {
    $('a:first', this).focus();
  });
  $('#available-templates li').on('keyup', function(ev) {
    if(ev.keyCode === $.ui.keyCode.ESCAPE) {
    // escape
    $('#available-templates li').removeClass('selected');
    ev.preventDefault();
    }
    if(ev.keyCode === $.ui.keyCode.SPACE || ev.keyCode === 107) {
    // spacebar or plus
    console.debug('selected');
    ev.preventDefault();
    $(this).toggleClass('selected ui-state-hover');
    find_sortable_focus(this);
    } else if(ev.keyCode === 39) {
    // right arrow.
    $('#available-templates li.selected').each(function() {
      $(this).removeClass('selected');
      var _tpl_id = $(this).data('cmsms-item-id');
      var _url = _edit_url.replace('xxxx', _tpl_id);
      var _text = $(this).text().trim();
      var _el = $(this).clone();
      var _a;
      if($manage_templates) {
        _a = $('<a/>')
        .attr('href', _url)
        .text(_text)
        .addClass('edit_tpl unsaved')
        .attr('title', '{$this->Lang('edit_template')}');
      } else {
        _a = $('<span/>').text(_text);
      }
      $('span', _el).remove();
      $(_el).append(_a);
      $(_el).removeClass('selected ui-state-hover')
        .attr('tabindex', -1)
        .addClass('unsaved no-sort')
        .append($('<a href="#"/>')
        .addClass('ui-icon ui-icon-trash sortable-remove')
        .text('{$this->Lang('remove')}')
        .attr('title', '{$this->Lang('remove')}'))
        .find('input[type="checkbox"]')
        .attr('checked', true);
      $('#selected-templates > ul').append(_el);
      $(this).remove();
      set_changed();
      // set focus somewhere
      find_sortable_focus(this);
    });
    console.debug('got arrow');
    }
  });
  $('#selected-templates .sortable-remove').on('click', function(e) {
    // click on remove icon
    e.preventDefault();
    set_changed();
    $(this).next('input[type="checkbox"]').attr('checked', false);
    $(this).parent('li').removeClass('no-sort').appendTo('#available-templates ul');
    $(this).remove();
  });
  $('a.edit_tpl').on('click', function(ev) {
    if(__changed) {
    ev.preventDefault();
    var el = this;
    cms_confirm('{$this->Lang('confirm_save_design')}','{$this->Lang('yes')}').done(function() {
      // save and redirect
      save_design().done(function() {
        window.location = $(el).attr('href');
      });
    });
    return false;
    }
    // normal default link behavior.
  });
});
EOS;
  $this->AdminBottomContent($out);

  $out = <<<EOS
//]]>
</script>
EOS;
  $this->AdminBottomContent($out);

  $tpl->display();
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
