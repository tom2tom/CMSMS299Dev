<?php

use News\Adminops;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify News Preferences')) return;

if (isset($params['cancel'])) $this->RedirectToAdminTab('customfields','','admin_settings');

if (isset($params['name'])) $name = trim($params['name']);
else $name = '';

$type = $params['type'] ?? '';

if (isset($params['max_length'])) $max_length = max(0,(int)$params['max_length']);
else $max_length = 255;

if( isset($params['public']) ) $public = (int)$params['public'];
else $public = 1;

if( isset($params['options']) ) {
    $options = trim($params['options']);
    $arr_options = Adminops::optionstext_to_array($options);
}
else {
    $options = '';
    $arr_options = [];
}

$userid = get_userid();

if (isset($params['submit'])) {
    $error = false;
    if ($name == '') $error = $this->Lang('nonamegiven');

    if( !$error && $type == 'dropdown' && count($arr_options) == 0 ) $error = $this->Lang('error_nooptions');

    if( !$error ) {
        $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ?';
        $exists = $db->GetOne($query,[$name]);
        if( $exists ) $error = $this->Lang('nameexists');
    }

    if( !$error ) {
        $max = $db->GetOne('SELECT max(item_order) + 1 FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs');
        if( $max == null ) $max = 1;

        $extra = ['options'=>$arr_options];
        $now = time();
        $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_fielddefs (name, type, max_length, item_order, create_date, public, extra) VALUES (?,?,?,?,?,?,?)';
        $parms = [$name, $type, $max_length, $max, $now, $public, serialize($extra)];
        $db->Execute($query, $parms );

        // put mention into the admin log
        audit('', 'News custom: '.$name, 'Field definition added');

        // done.
        $this->SetMessage($this->Lang('fielddefadded'));
        $this->RedirectToAdminTab('customfields','','admin_settings');
    }

    if( $error ) $this->ShowErrors($error);
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

// Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource('editfielddef.tpl'),null,null,$smarty);

$tpl->assign('title',$this->Lang('addfielddef'))
 ->assign('startform', $this->CreateFormStart($id, 'admin_addfielddef', $returnid))
 ->assign('endform', $this->CreateFormEnd())
 ->assign('nametext', $this->Lang('name'))
 ->assign('typetext', $this->Lang('type'))
 ->assign('maxlengthtext', $this->Lang('maxlength'))
 ->assign('showinputtype', true)
 ->assign('info_maxlength', $this->Lang('info_maxlength'))
 ->assign('userviewtext',$this->Lang('public'))

 ->assign('name',$name)
 ->assign('fieldtypes',$this->GetFieldTypes())
 ->assign('type',$type)
 ->assign('max_length',$max_length)
 ->assign('public',$public)
 ->assign('options',$options);

$tpl->display();
