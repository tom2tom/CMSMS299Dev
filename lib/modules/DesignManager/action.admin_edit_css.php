<?php
# DesignManager module action: edit css
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
use CMSMS\StylesheetOperations;
use DesignManager\utils;

if (!isset($gCms)) exit ;
if (!$this->CheckPermission('Manage Stylesheets')) return;

$this->SetCurrentTab('stylesheets');
if( isset($params['cancel']) ) {
    if( $params['cancel'] == $this->Lang('cancel') ) $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

$css_id = (int) get_parameter_value($params,'css');
$apply = isset($params['apply']);

try {
    $css_ob = null;
    $message = $this->Lang('msg_stylesheet_saved');
    $response = 'success';
    $extraparms = [];

    if ($css_id) {
        $css_ob = StylesheetOperations::get_stylesheet($css_id);
        $extraparms['css'] = $css_id;
    } else {
        $css_ob = new CmsLayoutStylesheet();
    }

    try {
        if ($apply || isset($params['submit'])) && $response !== 'error') {
            if (isset($params['description'])) $css_ob->set_description($params['description']);
            if (isset($params['content'])) $css_ob->set_content($params['content']);
            $typ = [];
            if (isset($params['media_type'])) $typ = $params['media_type'];
            $css_ob->set_media_types($typ);
            if (isset($params['media_query'])) $css_ob->set_media_query($params['media_query']);
            if ($this->CheckPermission('Manage Designs')) {
                $design_list = [];
                if (isset($params['design_list'])) $design_list = $params['design_list'];
                $css_ob->set_designs($design_list);
            }

            $old_export_name = $css_ob->get_content_filename();
            if (isset($params['name'])) $css_ob->set_name($params['name']);
            $css_ob->set_name($params['name']);
            $new_export_name = $css_ob->get_content_filename();
            if( $old_export_name && $old_export_name != $new_export_name && is_file( $old_export_name ) ) {
                if( is_file( $new_export_name ) ) throw new Exception('Cannot rename exported stylesheet (destination name exists)');
                $res = rename($old_export_name,$new_export_name);
                if( !$res ) throw new Exception( 'Problem renaming exported stylesheet' );
            }

            $css_ob->save();

            if (!$apply) {
                $this->SetMessage($message);
                $this->RedirectToAdminTab();
            }
        }
        else if( isset($params['export']) ) {
            $outfile = $css_ob->get_content_filename();
            $dn = dirname($outfile);
            if( !is_dir($dn) || !is_writable($dn) ) {
                throw new RuntimeException($this->Lang('error_assets_writeperm'));
            }
            if( is_file($outfile) && !is_writable($outfile) ) {
                throw new RuntimeException($this->Lang('error_assets_writeperm'));
            }
            file_put_contents($outfile,$css_ob->get_content());
        }
        else if( isset($params['import']) ) {
            $infile = $css_ob->get_content_filename();
            if( !is_file($infile) || !is_readable($infile) || !is_writable($infile) ) {
                throw new RuntimeException($this->Lang('error_assets_readwriteperm'));
            }
            $data = file_get_contents($infile);
            unlink($infile);
            $css_ob->set_content($data);
            $css_ob->save();
        }
    } catch( Exception $e ) {
        $message = $e->GetMessage();
        $response = 'error';
    }

    //
    // prepare to display
    //
    $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_edit_css.tpl'),null,null,$smarty);

    if (!$apply && $css_ob && $css_ob->get_id() && utils::locking_enabled()) {
//        $tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'))
//         ->assign('lock_refresh', $this->GetPreference('lock_refresh'));
        try {
            $lock_id = LockOperations::is_locked('stylesheet', $css_ob->get_id());
            $lock = null;
            if( $lock_id > 0 ) {
                // it's locked... by somebody, make sure it's expired before we allow stealing it.
                $lock = Lock::load('stylesheet',$css_ob->get_id());
                if( !$lock->expired() ) throw new CmsLockException('CMSEX_L010');
                LockOperations::unlock($lock_id,'stylesheet',$css_ob->get_id());
            }
        } catch( CmsException $e ) {
            $response = 'error';
            $message = $e->GetMessage();

            if (!$apply) {
                $this->SetError($message);
                $this->RedirectToAdminTab();
            }
        }
    }

    // handle the response message
    if ($apply) {
        $this->GetJSONResponse($response, $message);
    } elseif (!$apply && $response == 'error') {
        $this->ShowErrors($message);
    }

    $designs = CmsLayoutCollection::get_all();
    if ($designs) {
        $out = [];
        foreach ($designs as $one) {
            $out[$one->get_id()] = $one->get_name();
        }
        $tpl->assign('design_list', $out);
    }

    $themeObject = cms_utils::get_theme_object();
    if( $css_ob->get_id() > 0 ) {
        $themeObject->SetSubTitle($this->Lang('edit_stylesheet').': '.$css_ob->get_name()." ({$css_ob->get_id()})");
    } else {
        $themeObject->SetSubTitle($this->Lang('create_stylesheet'));
    }

    $tpl->assign('has_designs_right', $this->CheckPermission('Manage Designs'))
     ->assign('extraparms', $extraparms)
     ->assign('css', $css_ob);
    if ($css_ob && $css_ob->get_id()) $tpl->assign('css_id', $css_ob->get_id());

    //TODO ensure flexbox css for .rowbox, .boxchild

    $content = get_editor_script(['edit'=>true, 'htmlid'=>$id.'content', 'typer'=>'css']);
    if (!empty($content['head'])) {
        $this->AdminHeaderContent($content['head']);
    }
    $js = $content['foot'] ?? '';

    $script_url = CMS_SCRIPTS_URL;
    $user_id = get_userid(false);
    $lock_timeout = $this->GetPreference('lock_timeout');
    $do_locking = ($css_id > 0 && $lock_timeout > 0) ? 1 : 0;
    $lock_refresh = $this->GetPreference('lock_refresh');
    $msg = json_encode($this->Lang('msg_lostlock'));

    $js .= <<<EOS
<script type="text/javascript" src="{$script_url}/jquery.cmsms_dirtyform.min.js"></script>
<script type="text/javascript" src="{$script_url}/jquery.cmsms_lock.min.js"></script>
<script type="text/javascript">
//<![CDATA[
$(function() {
  var do_locking = $do_locking;
  $('#form_editcss').dirtyForm({
    beforeUnload: function() {
      if(do_locking) $('#form_editcss').lockManager('unlock');
    },
    unloadCancel: function() {
      if(do_locking) $('#form_editcss').lockManager('relock');
    }
  });
  // initialize lock manager
  if(do_locking) {
    $('#form_editcss').lockManager({
      type: 'stylesheet',
      oid: $css_id,
      uid: $user_id,
      lock_timeout: $lock_timeout,
      lock_refresh: $lock_refresh,
      error_handler: function(err) {
        cms_alert('$this->Lang("error_lock") ' + err.type + ' // ' + err.msg);
      },
      lostlock_handler: function(err) {
        // we lost the lock on this stylesheet... make sure we can't save anything.
        // and display a nice message.
        console.debug('lost lock handler');
        $('[name$=cancel]').fadeOut().attr('value', '$this->Lang("cancel")').fadeIn();
        $('#form_editcss').dirtyForm('option', 'dirty', false);
        $('#submitbtn, #applybtn').attr('disabled', 'disabled');
        $('#submitbtn, #applybtn').button({ 'disabled': true });
        $('.lock-warning').removeClass('hidden-item');
        cms_alert($msg);
      }
    });
  }
  $(document).on('cmsms_textchange', function() {
    // editor textchange, set the form dirty.
    $('#form_editcss').dirtyForm('option', 'dirty', true);
  });
  $('[name$=apply],[name$=submit]').on('click', function() {
    $('#form_editcss').dirtyForm('option', 'dirty', false);
  });
  $('#submitbtn,#cancelbtn,#importbtn,#exportbtn').on('click', function(e) {
    if(!do_locking) return;
    e.preventDefault();
    // unlock the item, and submit the form
    var self = this;
    $('#form_editcss').lockManager('unlock').done(function() {
      var form = $(self).closest('form'),
        el = $('<input type="hidden" />'),
        v = getcontent();
      setcontent(v);
      el.attr('name',$(self).attr('name')).val(v).appendTo(form);
      form.submit();
    });
    return false;
  });
  $('#applybtn').on('click', function(ev) {
    ev.preventDefault();
    var v = getcontent();
    setcontent(v);
    var url = $('#form_editcss').attr('action') + '?cmsjobtype=1&{$id}apply=1',
      data = $('#form_editcss').serializeArray();
    $.post(url, data, function(data, textStatus, jqXHR) {
      if(data.status === 'success') {
        cms_notify('success', data.message);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    });
    return false;
  });
  // disable media-type checkboxes if media query is in use
  if($('#mediaquery').val() !== '') {
    $('.media-type :checkbox').attr({
      disabled: 'disabled',
      checked: false
    });
  }
  $('#mediaquery').on('keyup', function(e) {
    if($('#mediaquery').val() !== '') {
      $('.media-type :checkbox').attr({
        disabled: 'disabled',
        checked: false
      });
    } else {
      $('.media-type:checkbox').removeAttr('disabled');
    }
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
