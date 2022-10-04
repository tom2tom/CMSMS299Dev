<?php
/*
Edit/add template method for CMSMS modules.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Lone;
use CMSMS\ScriptsMerger;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\Utils;
//use function CMSMS\add_shutdown;
use function CMSMS\sanitizeVal;

/*
Variables which must or may be defined by including code:

$userid       int
$module       optional object
OR
$modname      optional string module name
$returnaction optional string (default 'defaultadmin')
$returntab    optional string (default '')

$title        optional string
$infomessage  optional string If not provided, the template-type help message (if any) will be used
$warnmessage  optional string

$params[]     array, specifically $params['tpl'] template numeric id (<0 to add), sometimes submit, cancel etc

$can_manage   whether the user is authorized to manage/modify the template (as opposed to just view it)
$content_only optional bool whether to process only the template itself (no related properties)
$show_buttons optional bool display buttons to submit, apply and maybe to cancel
$show_cancel  optional bool display cancel button(s) default true
$display      optional bool display (default) or == false to fetch & return template output
*/

if( !isset($params['tpl']) ) return;

if( empty($module) ) {
    if( empty($modname) ) {
        throw new Exception(basename(__FILE__, '.php').': '.lang('missingparams'));
    }
    $module = Utils::get_module($modname);
    if( !$module ) {
        throw new Exception(basename(__FILE__, '.php').': '.lang('missingparams'));
    }
}

if( isset($params['cancel']) ) {
   $module->SetInfo(_ld('layout', 'msg_cancelled'));
   $module->RedirectToAdminTab(($returntab ?? ''), [], ($returnaction ?? 'defaultadmin'));
}

$originator = $module->GetName();

if( isset($params['submit']) || isset($params['apply']) ) {
    //save stuff
    $update_template = function(Template $tpl, array $params, bool $adding) use ($userid, $originator)
    {
        if( $adding ) {
            $tpl->set_originator($originator);
        }
        if( !empty($params['name']) ) {
            $val = sanitizeVal($params['name'], CMSSAN_PURESPC); //no fancy-pants ...
            $val = TemplateOperations::get_unique_template_name($val);
            $tpl->set_name($val);
        }
        elseif( $adding ) {
            $val = TemplateOperations::get_unique_template_name(_ld('layout', 'new_template'));
            $tpl->set_name($val);
        }
        if( isset($params['description']) ) {
            $tpl->set_description($params['description']); // no sanitizeVal() ??
        }
        if( isset($params['content']) ) {
            $val = str_replace('textare&#97;&gt;', 'textarea>', $params['content']);
            $tpl->set_content($val);
        }
        if( isset($params['listable']) ) {
            $tpl->set_listable((bool)$params['listable']);
        }
        if( isset($params['addt_editors']) ) {
            $tpl->set_additional_editors($params['addt_editors']);
        }
        if( isset($params['default']) ) {
            $tpl->set_type_default((bool)$params['default']);
        }
        if( isset($params['owner_id']) ) {
            $tpl->set_owner($params['owner_id']);
        }
        elseif( $adding ) {
            $tpl->set_owner($userid);
        }
        if( isset($params['type']) ) {
            $tpl->set_type($params['type']);
        }
    };

    if( $params['tpl'] > 0 ) {
        try {
            $template = TemplateOperations::get_template($params['tpl']);
            $update_template($template, $params, false);
            TemplateOperations::save_template($template);
        }
        catch (Throwable $t) {
            $module->SetError($t->getMessage());
            $module->RedirectToAdminTab(($returntab ?? ''), [], ($returnaction ?? 'defaultadmin'));
        }
    }
    else {
        $template = new Template();
        try {
            $update_template($template, $params, true);
            TemplateOperations::save_template($template);
        }
        catch (Throwable $t) {
            $module->SetError($t->getMessage());
            $module->RedirectToAdminTab(($returntab ?? ''), [], ($returnaction ?? 'defaultadmin'));
        }
        $params['tpl'] = $template->get_id();
    }

    if( isset($params['submit']) ) {
        if( $params['tpl'] > 0 ) {
            $msg = _ld('layout', 'msg_template_saved');
        }
        else {
            $msg = _ld('layout', 'msg_template_added');
        }
        $module->SetMessage($msg);
        $module->RedirectToAdminTab(($returntab ?? ''), [], ($returnaction ?? 'defaultadmin'));
    }
    else {
        $module->ShowMessage(_ld('layout', 'msg_template_saved'));
    }
}

if( $params['tpl'] > 0 ) {
    try {
        $template = TemplateOperations::get_template($params['tpl']);
        if( empty($title) ) {
            $title = _ld('layout', 'prompt_edit_template');
        }
        $content = $template->get_content();
        if( $content ) {
            //prevent invalid layout inside textarea
            $content = str_replace('textarea>', 'textare&#97;&gt;', $content);
        }
    }
    catch (Throwable $t) {
        $module->SetError($t->getMessage());
        $module->RedirectToAdminTab(($returntab ?? ''), [], ($returnaction ?? 'defaultadmin'));
    }
}
else {
    $template = new Template();
    $template->set_originator($originator);
    $template->set_owner($userid);
    if( empty($title) ) {
        $title = _ld('layout', 'create_template');
    }
    $content = '';
}

$can_default = false;
$type_list = [];
$user_list = [];
$eds_list = [];

if( $can_manage ) {
    $types = TemplateType::load_all_by_originator($originator);
    if( $types ) {
        foreach( $types as &$one ) {
            $type_list[$one->get_id()] = $one->get_langified_display_value();
        }
        $type_id = $template->get_type_id();
        if( $type_id ) {
            try {
                $type = TemplateType::load($type_id);
                $can_default = $type->get_dflt_flag();
                if( empty($infomessage) ) {
                    $infomessage = $type->get_template_helptext();
                }
                else {
                    $msg = $type->get_template_helptext();
                    if( $msg ) $infomessage .= '<br><br>'.$msg;
                }
            }
            catch (Throwable $t) {
                $module->SetError($t->getMessage());
                $module->RedirectToAdminTab(($returntab ?? ''), [], ($returnaction ?? 'defaultadmin'));
            }
        }
    }

    $user_list = Lone::get('UserOperations')->GetUsers(true, true);

    $allgroups = Lone::get('GroupOperations')->LoadGroups();
    foreach( $allgroups as $one ) {
        if( $one->id == 1) continue;
        if( !$one->active) continue;
        $eds_list[-(int)$one->id] = _ld('layout', 'prompt_group') . ': ' . $one->name;
    }
}

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
//$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
$js = $jsm->page_content();
if( $js ) {
    add_page_foottext($js);
}

$pageincs = get_syntaxeditor_setup(['edit'=>$can_manage, 'typer'=>'smarty']);
if( !empty($pageincs['head']) ) {
    add_page_headtext($pageincs['head']);
}
/*
$do_locking = ($tpl_id > 0 && isset($lock_timeout) && $lock_timeout > 0) ? 1 : 0;
if( $do_locking) {
    add_shutdown(10, 'LockOperations::delete_for_nameduser', $userid);
}
$s1 = addcslashes(_ld('layout', 'error_lock'), "'");
$s2 = addcslashes(_ld('layout', 'msg_lostlock'), "'");
$cancel = lang('cancel');
*/
/*
  var do_locking = $do_locking;
  if(do_locking) {
    // initialize lock manager
    $('#form_edittemplate').lockManager({
      type: 'template',
      oid: $tpl_id,
      uid: $userid,
      lock_timeout: $lock_timeout,
      lock_refresh: $lock_refresh,
      error_handler: function(err) {
        cms_alert('{$s1} ' + err.type + ' // ' + err.msg);
      },
      lostlock_handler: function(err) {
       // we lost the lock on this template... make sure we can't save anything.
       // and display a nice message.
        $('[name$="cancel"]').fadeOut().attr('value', '$cancel').fadeIn();
        $('#form_edittemplate').dirtyForm('option', 'dirty', false);
        cms_button_able($('#submitbtn, #applybtn'), false);
        $('.lock-warning').removeClass('hidden-item');
        cms_alert('$s2');
      }
    });
  }
  $('#form_edittemplate').dirtyForm({
    beforeUnload: function() {
      if(do_locking) $('#form_edittemplate').lockManager('unlock');
    },
    unloadCancel: function() {
      if(do_locking) $('#form_edittemplate').lockManager('relock');
    }
  });

  $('#applybtn').on('click', function(ev) {
    ev.preventDefault();
    var v = geteditorcontent();
    setpagecontent(v);
    var fm = $('#form_edittemplate'),
       url = fm.attr('action') + '?apply=1',
    params = fm.serializeArray();
    $.ajax(url, {
      method: 'POST',
      data: params
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
    }).done(function(data) {
      if(data.status === 'success') {
        cms_notify('success', data.message);
        $('#form_edittemplate').dirtyForm('option', 'dirty', false);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    });
    return false;
  });

  $('#submitbtn, #applybtn').on('click', function(ev) {
    if(this.id !== 'cancelbtn') {
      var v = geteditorcontent();
      setpagecontent(v);
    }
  });
*/
//TODO duplicate sets of buttons exist
$js = $pageincs['foot'] ?? '';
$js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('#form_edittemplate').dirtyForm('option', 'dirty', false);
  $(document).on('cmsms_textchange', function() {
    // editor textchange, set the form dirty TODO something from the actual editor
    $('#form_edittemplate').dirtyForm('option', 'dirty', true);
  });
});
//]]>
</script>
EOS;
add_page_foottext($js); //not $jsm->queue_script() (embedded variables)

//$parms = ['tpl'=>$params['tpl']]; //TODO more, maybe all $params[] ?

$tpl = $smarty->createTemplate('editmoduletemplate.tpl', null, null, $smarty); //.tpl file in admin/templates folder

$tpl->assign('formaction', $returnaction ?? 'defaultadmin')
 ->assign('formparms', $params) //$parms)
 ->assign('title', $title ?? null)
 ->assign('infomessage', $infomessage ?? null)
 ->assign('warnmessage', $warnmessage ?? null)
 ->assign('tpl_obj', $template)
 ->assign('content', $content)
 ->assign('can_default', $can_default)
 ->assign('can_manage', $can_manage)
 ->assign('edit_meta', empty($content_only))
 ->assign('userid', $userid)
 ->assign('user_list', $user_list)
 ->assign('addt_editor_list', $eds_list)
 ->assign('type_list', $type_list)
 ->assign('withbuttons', !empty($show_buttons) || !empty($show_cancel))
 ->assign('withcancel', !empty($show_cancel));

try {
    if( !isset($display) || $display ) {
        $tpl->display();
    }
    else {
        return $tpl->fetch();
    }
} catch(Throwable $t) {
    log_error('Edit template failure', $t->getMessage());
    if( !isset($display) || $display ) {
        echo $t->getMessage();
    }
    else {
        return $t->getMessage();
    }
}
