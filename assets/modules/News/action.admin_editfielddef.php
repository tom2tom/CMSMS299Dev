<?php
#CMSMS News module action: editfield
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use News\Adminops;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify News Preferences')) {
    //TODO some immediate error display >> lang('needpermissionto', '"Modify News Preferences"'));
    return;
}

if (isset($params['cancel'])) $this->RedirectToAdminTab('customfields','','admin_settings');

$fdid = $params['fdid'] ?? '';

if (isset($params['name'])) $name = trim($params['name']);
else $name = '';

if( isset($params['options']) ) {
  $options = trim($params['options']);
  $arr_options = Adminops::optionstext_to_array($options);
}
else {
  $options = '';
  $arr_options = [];
}

$type = $params['type'] ?? '';

if (isset($params['max_length'])) $max_length = max(0,(int)$params['max_length']);
else $max_length = 255;

$origname = $params['origname'] ?? '';

if( isset($params['public']) ) $public = (int)$params['public'];
else $public = 0;

if (isset($params['submit'])) {
  if ($name == '') {
    $error = true;
    $this->ShowErrors($this->Lang('nonamegiven'));
  }
  else {
    $error = false;
  }

  if( !$error ) {
    $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ? AND id != ?';
    $tmp = $db->GetOne($query,[$name,$fdid]);
    if( $tmp ) {
        $error = true;
        $this->ShowErrors($this->Lang('nameexists'));
    }
  }

  if( !$error ) {
    $extra = ['options'=>$arr_options];
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET name = ?, type = ?, max_length = ?, modified_date = ?, public = ?, extra = ? WHERE id = ?';
    $res = $db->Execute($query, [$name, $type, $max_length, time(), $public, serialize($extra), $fdid]);

    if( !$res ) { //TODO update-command result is never reliable
        //TODO some immediate error display >> $db->ErrorMsg()
        return;
    }
    // put mention into the admin log
    audit($name, 'News custom: '.$name, 'Field definition edited');
    $this->SetMessage($this->Lang('fielddefupdated'));
    $this->RedirectToAdminTab('customfields','','admin_settings');
  }
}
else {
   $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
   $row = $db->GetRow($query, [$fdid]);

   if ($row) {
     $name = $row['name'];
     $type = $row['type'];
     $max_length = $row['max_length'];
     $origname = $row['name'];
     $public = $row['public'];
     $extra = unserialize($row['extra']);
     if( isset($extra['options']) ) {
       $options = Adminops::array_to_optionstext($extra['options']);
     }
   }
}

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
function handle_change() {
  var val = $('#fld_type').val();
  if(val === 'dropdown') {
    $('#area_maxlen').hide('slow');
    $('#area_options').show('slow');
  } else if(val === 'checkbox' || val === 'file' || val === 'linkedfile') {
    $('#area_maxlen').hide('slow');
    $('#area_options').hide('slow');
  } else {
    $('#area_maxlen').show('slow');
    $('#area_options').hide('slow');
  }
}
$(function() {
  handle_change();
  $('#fld_type').on('change', handle_change);
  $('#{$id}cancel').on('click', function() {
    $(this).closest('form').attr('novalidate','novalidate');
  });
});
//]]>
</script>
EOS;
$this->AdminBottomContent($js);

//Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource('editfielddef.tpl'),null,null,$smarty);
$tpl->assign('title',$this->Lang('editfielddef'))
 ->assign('startform', $this->CreateFormStart($id, 'admin_editfielddef', $returnid))
 ->assign('endform', $this->CreateFormEnd())
 ->assign('nametext', $this->Lang('name'))
 ->assign('typetext', $this->Lang('type'))
 ->assign('maxlengthtext', $this->Lang('maxlength'))
 ->assign('showinputtype', false)
 ->assign('inputtype', $this->CreateInputHidden($id, 'type', $type))
 ->assign('info_maxlength', $this->Lang('info_maxlength'))
 ->assign('userviewtext',$this->Lang('public'))

 ->assign('name',$name)
 ->assign('fieldtypes',$this->GetFieldTypes())
 ->assign('type',$type)
 ->assign('max_length',$max_length)
 ->assign('public',$public)
 ->assign('options',$options)

//see DoActionBase() ->assign('mod',$this)
 ->assign('hidden',
    $this->CreateInputHidden($id, 'fdid', $fdid).
    $this->CreateInputHidden($id, 'origname', $origname));

$tpl->display();
