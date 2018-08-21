<?php
# DesignManager module action: defaultadmin
# Copyright (C) 2014-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
if( !$this->VisibleToAdminUser() ) return;

$filter_tpl_rec = ['tpl'=>'','limit'=>100,'offset'=>0,'sortby'=>'name','sortorder'=>'asc'];
$filter_css_rec = ['limit'=>100,'offset'=>0,'sortby'=>'name','sortorder'=>'asc','design'=>''];
if( isset($params['submit_filter_tpl']) ) {
    if( $params['submit_filter_tpl'] == 1 ) {
        $filter_tpl_rec['tpl'] = $params['filter_tpl'];
        $filter_tpl_rec['sortby'] = trim($params['filter_sortby']);
        $filter_tpl_rec['sortorder'] = trim($params['filter_sortorder']);
        $filter_tpl_rec['limit'] = (int)$params['filter_limit_tpl'];
        $filter_tpl_rec['limit'] = max(2,min(100,$filter_tpl_rec['limit']));
    }
    unset($_SESSION[$this->GetName().'tpl_page']);
    cms_userprefs::set($this->GetName().'template_filter',serialize($filter_tpl_rec));
}
else if( isset($params['submit_filter_css']) ) {
    if( $params['submit_filter_css'] == 1 ) {
        $filter_css_rec['design'] = trim($params['filter_css_design']);
        $filter_css_rec['sortby'] = trim($params['filter_css_sortby']);
        $filter_css_rec['sortorder'] = trim($params['filter_css_sortorder']);
        $filter_css_rec['limit'] = max(2,min(100,(int)$params['filter_limit_css']));
    }
    $this->SetCurrentTab('stylesheets');
	unset($_SESSION[$this->GetName().'tpl_page']);
    cms_userprefs::set($this->GetName().'css_filter',serialize($filter_css_rec));
}
else if( isset($params['submit_create']) ) {
	$this->Redirect($id,'admin_edit_template',$returnid,['import_type'=>$params['import_type']]);
	return;
}
else if( isset($params['submit_bulk']) ) {
	$tmp = ['allparms'=>base64_encode(serialize($params))];
	$this->Redirect($id,'admin_bulk_template',$returnid,$tmp);
}
else if( isset($params['submit_bulk_css']) ) {
	$tmp = ['allparms'=>base64_encode(serialize($params))];
	$this->Redirect($id,'admin_bulk_css',$returnid,$tmp);
}
else if( isset($params['design_setdflt']) && $this->CheckPermission('Manage Designs') ) {
    $design_id = (int)$params['design_setdflt'];
    try {
        $cur_dflt = CmsLayoutCollection::load_default();
        if( is_object($cur_dflt) && $cur_dflt->get_id() != $design_id ) {
            $cur_dflt->set_default(false);
            $cur_dflt->save();
        }
    }
    catch( \Exception $e ) {
        // do nothing
    }

    $new_dflt = CmsLayoutCollection::load($design_id);
    $new_dflt->set_default(true);
    $new_dflt->save();

    $this->SetCurrentTab('designs');
    $this->ShowMessage($this->Lang('msg_dflt_design_saved'));
}

$tmp = cms_userprefs::get($this->GetName().'template_filter');
if( $tmp ) $filter_tpl_rec = unserialize($tmp);
if( isset($params['tpl_page']) ) {
	$this->SetCurrentTab('templates');
	$page = max(1,(int)$params['tpl_page']);
	$_SESSION[$this->GetName().'tpl_page'] = $page;
	$filter_tpl_rec['offset'] = ($page - 1) * $filter_tpl_rec['limit'];
} else if( isset($_SESSION[$this->GetName().'tpl_page']) ) {
	$page = max(1,(int)$_SESSION[$this->GetName().'tpl_page']);
	$filter_tpl_rec['offset'] = ($page - 1) * $filter_tpl_rec['limit'];
}

$efilter = $filter_tpl_rec;
if( !empty($efilter['tpl']) ) {
	$efilter[] = $efilter['tpl'];
	unset($efilter['tpl']);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl'),null,null,$smarty);

// build a list of types, categories, and later (designs).
$opts = ['' => $this->Lang('prompt_none')];
$types = CmsLayoutTemplateType::get_all();
$originators = [];
if( count($types) ) {
    $tmp = $tmp2 = $tmp3 = [];
    for( $i = 0; $i < count($types); $i++ ) {
        $tmp['t:'.$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
        $tmp2[$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
        $tmp3[$types[$i]->get_id()] = $types[$i];
		if( !isset($originators[$types[$i]->get_originator()]) ) {
			$originators['o:'.$types[$i]->get_originator()] = $types[$i]->get_originator(TRUE);
		}
    }
    usort($tmp3,function($a,$b){
            // core always beets alphabetic type
            // then sort by originator and then name.
            $ao = $a->get_originator();
            $bo = $b->get_originator();
            if( $ao == $a::CORE && $bo ==  $a::CORE ) return strcasecmp($a->get_name(),$b->get_name());
            if( $ao == $a::CORE ) return -1;
            if( $bo == $b::CORE ) return 1;
            return strcasecmp($a->get_langified_display_value(),$b->get_langified_display_value());
        });
    asort($tmp);
    asort($tmp2);
    asort($originators);
    $tpl->assign('list_all_types',$tmp3)
     ->assign('list_types',$tmp2);
    $opts[$this->Lang('tpl_types')] = $tmp;
	$opts[$this->Lang('tpl_originators')] = $originators;
}
$cats = CmsLayoutTemplateCategory::get_all();
if( $cats && count($cats) ) {
    $tpl->assign('list_categories',$cats);
    $tmp = [];
    for( $i = 0; $i < count($cats); $i++ ) {
        $tmp['c:'.$cats[$i]->get_id()] = $cats[$i]->get_name();
    }
    $opts[$this->Lang('prompt_categories')] = $tmp;
}
$designs = CmsLayoutCollection::get_all();
if( $designs && count($designs) ) {
    $tpl->assign('list_designs',$designs);
    $tmp = [];
    for( $i = 0; $i < count($designs); $i++ ) {
        $tmp['d:'.$designs[$i]->get_id()] = $designs[$i]->get_name();
        $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
    }
    asort($tmp);
    asort($tmp2);
    $tpl->assign('design_names',$tmp2);
    $opts[$this->Lang('prompt_design')] = $tmp;
}
if( $this->CheckPermission('Manage Designs') ) {
    $userops = cmsms()->GetUserOperations();
    $allusers = $userops->LoadUsers();
    $users = [-1=>$this->Lang('prompt_unknown')];
    $tmp = [];
    for( $i = 0; $i < count($allusers); $i++ ) {
        $tmp['u:'.$allusers[$i]->id] = $allusers[$i]->username;
        $users[$allusers[$i]->id] = $allusers[$i]->username;
    }
    asort($tmp);
    asort($users);
    $tpl->assign('list_users',$users);
    $opts[$this->Lang('prompt_user')] = $tmp;
}

if( $this->CheckPermission('Manage Stylesheets') ) {
	$tmp = cms_userprefs::get($this->GetName().'css_filter');
	if( $tmp ) $filter_css_rec = unserialize($tmp);
	if( isset($params['css_page']) ) {
		$this->SetCurrentTab('stylesheets');
		$page = max(1,(int)$params['css_page']);
		$_SESSION[$this->GetName().'css_page'] = $page;
		$filter_css_rec['offset'] = ($page - 1) * $filter_css_rec['limit'];
	} else if( isset($_SESSION[$this->GetName().'css_page']) ) {
		$page = max(1,(int)$_SESSION[$this->GetName().'css_page']);
		$filter_css_rec['offset'] = ($page - 1) * $filter_css_rec['limit'];
	}
}

// give everything to smarty that we can
$tpl->assign('filter_tpl_options',$opts)
 ->assign('tpl_filter',$filter_tpl_rec) // used for filter form
 ->assign('css_filter',$filter_css_rec); // used for filter form
$jsoncssfilter = json_encode($filter_css_rec); // used for ajaxy stuff
$jsonfilter = json_encode($efilter); // used for ajaxy stuff

$tpl->assign('has_add_right',
                $this->CheckPermission('Modify Templates') ||
                $this->CheckPermission('Add Templates'))
 ->assign('coretypename',CmsLayoutTemplateType::CORE)
 ->assign('manage_stylesheets',$this->CheckPermission('Manage Stylesheets'))
 ->assign('manage_templates',$this->CheckPermission('Modify Templates'))
 ->assign('manage_designs',$this->CheckPermission('Manage Designs'))
 ->assign('import_url',$this->create_url($id,'admin_import_template'));
$admin_url = $config['admin_url'];
$tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'));
$url = $this->create_url($id,'ajax_get_templates');
$ajax_templates_url = str_replace('amp;','',$url);
$url = $this->create_url($id,'ajax_get_stylesheets');
$ajax_stylesheets_url = str_replace('amp;','',$url);
$script_url = CMS_SCRIPTS_URL;

// templates script
$s1 = json_encode($this->Lang('confirm_steal_lock'));
$s2 = json_encode($this->Lang('confirm_clearlocks'));
$s3 = json_encode($this->Lang('error_contentlocked'));
$s4 = json_encode($this->Lang('error_nothingselected'));

$js = <<<EOS
<script type="text/javascript" src="{$script_url}/jquery.cmsms_autorefresh.js"></script>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
  // load the templates area.
  cms_busy();
  $('#template_area').autoRefresh({
    url: '$ajax_templates_url',
    data: {
      filter: '$jsonfilter'
    }
  });
  $('#tpl_bulk_action,#tpl_bulk_submit').attr('disabled', 'disabled');
  $('#tpl_bulk_submit').button({ 'disabled': true });
  $('#tpl_selall,.tpl_select').on('click', function() {
    var l = $('.tpl_select:checked').length;
    if(l === 0) {
      $('#tpl_bulk_action').attr('disabled', 'disabled');
      $('#tpl_bulk_submit').attr('disabled', 'disabled');
      $('#tpl_bulk_submit').button({ 'disabled': true });
    } else {
      $('#tpl_bulk_action').removeAttr('disabled');
      $('#tpl_bulk_submit').removeAttr('disabled');
      $('#tpl_bulk_submit').button({ 'disabled': false });
    }
  });
  $('a.steal_tpl_lock').on('click', function(e) {
    // we're gonna confirm stealing this lock
    e.preventDefault();
    cms_confirm_linkclick(this,$s1);
    return false;
  });
  $('#clearlocks,#cssclearlocks').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s2,'{$this->Lang("yes")}');
    return false;
  });
  $('a.sedit_tpl').on('click', function(e) {
    if($(this).hasClass('steal_tpl_lock')) return true;
    // do a double check to see if this page is locked or not.
    var tpl_id = $(this).attr('data-tpl-id');
    var url = '{$admin_url}/ajax_lock.php?cmsjobtype=1';
    var opts = { opt: 'check', type: 'template', oid: tpl_id };
    opts[cms_data.secure_param_name] = cms_data.user_key;
    $.ajax({
      url: url,
      data: opts,
    }).done(function(data) {
      if(data.status === 'success') {
        if(data.locked) {
          // gotta display a message.
          ev.preventDefault();
          cms_alert($s3);
        }
      }
    });
  });
  $('#tpl_bulk_submit').on('click', function() {
    var n = $('input:checkbox:checked.tpl_select').length;
    if(n === 0) {
      cms_alert($s4);
      return false;
    }
  });
  $('#template_area').on('click', '#edittplfilter', function() {
    cms_dialog($('#filterdialog'), {
      open: function(ev, ui) {
        cms_equalWidth($('#filterdialog label.boxchild'));
	  },
      width: 'auto',
      buttons: {
        '{$this->Lang("submit")}': function() {
          $(this).dialog('close');
          $('#filterdialog_form').submit();
        },
        '{$this->Lang("reset")}': function() {
          $(this).dialog('close');
          $('#submit_filter_tpl').val('-1');
          $('#filterdialog_form').submit();
        },
        '{$this->Lang("cancel")}': function() {
          $(this).dialog('close');
        }
      }
    });
  });
  $('#addtemplate').on('click', function() {
    cms_dialog($('#addtemplatedialog'), {
      width: 'auto',
      buttons: {
        '{$this->Lang("submit")}': function() {
          $(this).dialog('close');
          $('#addtemplate_form').submit();
        },
        '{$this->Lang("cancel")}': function() {
          $(this).dialog('close');
        }
      }
    });
  });
});

EOS;

//stylesheets script
$js .= <<<EOS
$(document).ready(function() {
  cms_busy();
  $('#stylesheet_area').autoRefresh({
    url: '$ajax_stylesheets_url',
    data: {
      filter: '$jsoncssfilter'
    }
  });
  $('#css_bulk_action,#css_bulk_submit').attr('disabled', 'disabled');
  $('#css_bulk_submit').button({ 'disabled': true });
  $('#css_selall,.css_select').on('click', function() {
    // if one or more .css_select is checked, enable the bulk actions
    var l = $('.css_select:checked').length;
    if(l === 0) {
      $('#css_bulk_action').attr('disabled', 'disabled');
      $('#css_bulk_submit').attr('disabled', 'disabled');
      $('#css_bulk_submit').button({ 'disabled': true });
    } else {
      $('#css_bulk_action').removeAttr('disabled');
      $('#css_bulk_submit').removeAttr('disabled');
      $('#css_bulk_submit').button({ 'disabled': false });
    }
  });
  $('a.steal_css_lock').on('click', function(e) {
    // we're gonna confirm stealing this lock
    e.preventDefault();
    cms_confirm_linkclick(this,$s1);
    return false;
  });
  $('#stylesheet_area').on('click', '#editcssfilter', function() {
    cms_dialog($('#filtercssdlg'), {
      open: function(ev, ui) {
        cms_equalWidth($('#filtercssdlg label.boxchild'));
      },
      width: 'auto',
      buttons: {
        '{$this->Lang("submit")}': function() {
          $(this).dialog('close');
          $('#filtercssdlg_form').submit();
        },
        '{$this->Lang("reset")}': function() {
          $(this).dialog('close');
          $('#submit_filter_css').val('-1');
          $('#filtercssdlg_form').submit();
        },
        '{$this->Lang("cancel")}': function() {
          $(this).dialog('close');
        }
      }
    });
  });
});

EOS;

// categories script
if (isset($list_categories)) {
	$yes = $this->Lang('yes');
	$s1 = json_encode($this->Lang('confirm_delete_category'));
	$js .= <<<EOS
$(document).ready(function() {
  $('#categorylist tbody').cmsms_sortable_table({
    actionurl: '{cms_action_url action="ajax_order_cats" forjs=1}&cmsjobtype=1',
    callback: function(data) {
      if(data.status === 'success') {
        cms_notify('info', data.message);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    }
  });
  $('#categorylist a.del_cat').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s1,'$yes');
    return false;
  });
});

EOS;
}
$js .= <<<EOS
//]]>
</script>

EOS;
$this->AdminBottomContent($js);

$tpl->display();

