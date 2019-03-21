<?php
# DesignManager module action: edit template
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

use CMSMS\CmsLockException;
use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\TemplateOperations;
use DesignManager\utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Templates')) {
    // no manage templates permission
    if (!$this->CheckPermission('Add Templates')) {
        // no add templates permission
        if (!isset($params['tpl']) || !TemplateOperations::user_can_edit($params['tpl'])) {
            // no parameter, or no ownership/addt_editors.
            return;
        }
    }
}

$this->SetCurrentTab('templates');
$tpl_id = (int) get_parameter_value($params,'tpl');

if (isset($params['cancel'])) {
    if ($params['cancel'] == $this->Lang('cancel')) $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

try {
//    $tpl_obj = null;
//    $type_obj = null;
    $type_is_readonly = false;
    $message = $this->Lang('msg_template_saved');
    $response = 'success';
    $apply = isset($params['apply']) ? 1 : 0;

    $extraparms = [];
    if (isset($params['import_type'])) {
        $tpl_obj = TemplateOperations::create_by_type($params['import_type']);
        $tpl_obj->set_owner(get_userid());
        $design = CmsLayoutCollection::load_default();
        if( $design ) {
            $tpl_obj->add_design($design);
        }
        $extraparms['import_type'] = $params['import_type'];
        $type_is_readonly = true;
    } else if (isset($params['tpl'])) {
        $tpl_obj = TemplateOperations::load_template($params['tpl']);
        $tpl_obj->get_designs();
        $extraparms['tpl'] = $params['tpl'];
    } else {
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
    }
    $type_obj = CmsLayoutTemplateType::load($tpl_obj->get_type_id());

    try {
        if (isset($params['submit']) || isset($params['apply']) ) {
            // do the magic.
            if (isset($params['description'])) $tpl_obj->set_description($params['description']);
            if (isset($params['type'])) $tpl_obj->set_type($params['type']);
            if (isset($params['default'])) $tpl_obj->set_type_dflt($params['default']);
            if (isset($params['owner_id'])) $tpl_obj->set_owner($params['owner_id']);
            if (isset($params['addt_editors']) && $params['addt_editors']) {
                $tpl_obj->set_additional_editors($params['addt_editors']);
            }
            if (isset($params['category_id'])) $tpl_obj->set_category($params['category_id']);
            $tpl_obj->set_listable($params['listable']??1);
            if( isset($params['contents']) ) $tpl_obj->set_content($params['contents']);

            $old_export_name = $tpl_obj->get_content_filename();
            $tpl_obj->set_name($params['name']);
            $new_export_name = $tpl_obj->get_content_filename();
            if( $old_export_name != $new_export_name && is_file( $old_export_name ) ) {
                if( is_file( $new_export_name ) ) throw new Exception('Cannot rename exported template (destination name exists)');
                $res = rename($old_export_name,$new_export_name);
                if( !$res ) throw new Exception( 'Problem renaming exported template' );
            }

            if ($this->CheckPermission('Manage Designs')) {
                $design_list = [];
                if (isset($params['design_list'])) $design_list = $params['design_list'];
                $tpl_obj->set_designs($design_list);
            }

            // lastly, check for errors in the template before we save.
            if( isset($params['contents']) ) cms_utils::set_app_data('tmp_template', $params['contents']);

            // if we got here, we're golden.
            $tpl_obj->save();

            if (!$apply) {
                $this->SetMessage($message);
                $this->RedirectToAdminTab();
            }

        }
        else if( isset($params['export']) ) {
            $outfile = $tpl_obj->get_content_filename();
            $dn = dirname($outfile);
            if( !is_dir($dn) || !is_writable($dn) ) throw new RuntimeException($this->Lang('error_assets_writeperm'));
            if( is_file($outfile) && !is_writable($outfile) ) throw new RuntimeException($this->Lang('error_assets_writeperm'));
            file_put_contents($outfile,$tpl_obj->get_content());
        }
        else if( isset($params['import']) ) {
            $infile = $tpl_obj->get_content_filename();
            if( !is_file($infile) || !is_readable($infile) || !is_writable($infile) ) {
                throw new RuntimeException($this->Lang('error_assets_readwriteperm'));
            }
            $data = file_get_contents($infile);
            unlink($infile);
            $tpl_obj->set_content($data);
            $tpl_obj->save();
        }
    } catch( Exception $e ) {
        $message = $e->GetMessage();
        $response = 'error';
    }

    //
    // BUILD THE DISPLAY
    //
    if (!$apply && $tpl_obj && $tpl_obj->get_id() && utils::locking_enabled()) {
        $lock_timeout = $this->GetPreference('lock_timeout', 0);
        $lock_refresh = $this->GetPreference('lock_refresh', 0);
        try {
            $lock_id = LockOperations::is_locked('template', $tpl_obj->get_id());
            $lock = null;
            if( $lock_id > 0 ) {
                // it's locked... by somebody, make sure it's expired before we allow stealing it.
                $lock = Lock::load('template',$tpl_obj->get_id());
                if( !$lock->expired() ) throw new CmsLockException('CMSEX_L010');
                LockOperations::unlock($lock_id,'template',$tpl_obj->get_id());
            }
        } catch( CmsException $e ) {
            $message = $e->GetMessage();
            $this->SetError($message);
            $this->RedirectToAdminTab();
        }
    } else {
        $lock_timeout = 0;
        $lock_refresh = 0;
    }

    // handle the response message
    if ($apply) {
        $this->GetJSONResponse($response, $message);
    } elseif (!$apply && $response == 'error') {
        $this->ShowErrors($message);
    }

	$themeObject = cms_utils::get_theme_object();
    if( ($tpl_id = $tpl_obj->get_id()) > 0 ) {
        $themeObject->SetSubTitle($this->Lang('edit_template').': '.$tpl_obj->get_name()." ($tpl_id)");
    } else {
        $tpl_id = 0;
        $themeObject->SetSubTitle($this->Lang('create_template'));
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_edit_template.tpl'),null,null,$smarty);

    $tpl->assign('type_obj', $type_obj)
     ->assign('extraparms', $extraparms)
     ->assign('template', $tpl_obj);

    $cats = CmsLayoutTemplateCategory::get_all();
    $out = ['' => $this->Lang('prompt_none')];
    if ($cats) {
        foreach ($cats as $one) {
            $out[$one->get_id()] = $one->get_name();
        }
    }
    $tpl->assign('category_list', $out);

    $types = CmsLayoutTemplateType::get_all();
    if ($types) {
        $out = [];
        $out2 = [];
        foreach ($types as $one) {
            $out2[] = $one->get_id();
            $out[$one->get_id()] = $one->get_langified_display_value();
        }
        $tpl->assign('type_list', $out)
         ->assign('type_is_readonly', $type_is_readonly);
    }

    $designs = CmsLayoutCollection::get_all();
    if ($designs) {
        $out = [];
        foreach ($designs as $one) {
            $out[$one->get_id()] = $one->get_name();
        }
        $tpl->assign('design_list', $out);
    }

    $user_id = get_userid(false);
    $tpl->assign('has_manage_right', $this->CheckPermission('Modify Templates'))
     ->assign('has_themes_right', $this->CheckPermission('Manage Designs'));
    if ($this->CheckPermission('Modify Templates') || $tpl_obj->get_owner_id() == $user_id) {

        $userops = cmsms()->GetUserOperations();
        $allusers = $userops->LoadUsers();
        $tmp = [];
        foreach ($allusers as $one) {
            //FIXME Why skip admin here? If template owner is admin this would unset admin as owner
            //if ($one->id == 1)
            //    continue;
            $tmp[$one->id] = $one->username;
        }
        if ($tmp) $tpl->assign('user_list', $tmp);

        $groupops = cmsms()->GetGroupOperations();
        $allgroups = $groupops->LoadGroups();
        foreach ($allgroups as $one) {
            if ($one->id == 1) continue;
            if ($one->active == 0) continue;
            $tmp[$one->id * -1] = $this->Lang('prompt_group') . ': ' . $one->name;
            // appends to the tmp array.
        }
        if ($tmp) $tpl->assign('addt_editor_list', $tmp);
    }

//TODO ensure flexbox css for .rowbox, .boxchild

    $content = get_editor_script(['edit'=>true, 'htmlid'=>$id.'contents', 'typer'=>'smarty']);
    if (!empty($content['head'])) {
        $this->AdminHeaderContent($content['head']);
    }
    $js = $content['foot'] ?? '';

    $script_url = CMS_SCRIPTS_URL;
    $do_locking = ($tpl_id > 0 && isset($lock_timeout) && $lock_timeout > 0) ? 1:0;
    $s1 = json_encode($this->Lang('msg_lostlock'));

    $js .= <<<EOS
<script type="text/javascript" src="{$script_url}/jquery.cmsms_dirtyform.min.js"></script>
<script type="text/javascript" src="{$script_url}/jquery.cmsms_lock.min.js"></script>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
  var do_locking = $do_locking;
  $('#form_edittemplate').dirtyForm({
    beforeUnload: function() {
      if(do_locking) $('#form_edittemplate').lockManager('unlock');
    },
    unloadCancel: function() {
      if(do_locking) $('#form_edittemplate').lockManager('relock');
    }
  });
  // initialize lock manager
  if(do_locking) {
    $('#form_edittemplate').lockManager({
      type: 'template',
      oid: $tpl_id,
      uid: $user_id,
      lock_timeout: $lock_timeout,
      lock_refresh: $lock_refresh,
      error_handler: function(err) {
        cms_alert('$this->Lang("error_lock")' + ' ' + err.type + ' // ' + err.msg);
      },
      lostlock_handler: function(err) {
       // we lost the lock on this content... make sure we can't save anything.
       // and display a nice message.
        $('[name$=cancel]').fadeOut().attr('value', '$this->Lang("cancel")').fadeIn();
        $('#form_edittemplate').dirtyForm('option', 'dirty', false);
        $('#submitbtn, #applybtn').attr('disabled', 'disabled');
        $('#submitbtn, #applybtn').button({ 'disabled': true });
        $('.lock-warning').removeClass('hidden-item');
        cms_alert($s1);
      }
    });
  } // do_locking
  $(document).on('cmsms_textchange', function() {
    // editor textchange, set the form dirty.
    $('#form_edittemplate').dirtyForm('option', 'dirty', true);
  });
  $('#form_edittemplate').on('click', '[name$=apply],[name$=submit],[name$=cancel]', function() {
    // if we manually click on one of these buttons, the form is no longer considered dirty for the purposes of warnings.
    $('#form_edittemplate').dirtyForm('option', 'dirty', false);
  });
  $('#submitbtn,#cancelbtn,#importbtn,#exportbtn').on('click', function(ev) {
   if( ! do_locking ) return;
   ev.preventDefault();
   // unlock the item, and submit the form
   var self = this;
   $('#form_edittemplate').lockManager('unlock').done(function() {
    var form = $(self).closest('form'),
      el = $('<input type="hidden"/>');
    el.attr('name',$(self).attr('name')).val($(self).val()).appendTo(form);
    form.submit();
   });
   return false;
  });
  $('#applybtn').on('click', function(ev) {
    ev.preventDefault();
    $('#content').val(editor.session.getValue());
    var url = $('#form_edittemplate').attr('action') + '?cmsjobtype=1&m1_apply=1',
      data = $('#form_edittemplate').serializeArray();
    $.post(url, data, function(data, textStatus, jqXHR) {
      if(data.status === 'success') {
        cms_notify('info', data.message);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    });
    return false;
  });
});
//]]>
</script>
EOS;
    $this->AdminBottomContent($js);

    $tpl->display();
} catch( CmsException $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
