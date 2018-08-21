<?php

use News\news_admin_ops;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

if (isset($params['cancel'])) $this->RedirectToAdminTab('customfields','','admin_settings');

$name = '';
if (isset($params['name'])) $name = trim($params['name']);

$type = '';
if (isset($params['type'])) $type = $params['type'];

$max_length = 255;
if (isset($params['max_length'])) $max_length = max(0,(int)$params['max_length']);

$public = 1;
if( isset($params['public']) ) $public = (int)$params['public'];


$arr_options = array();
$options = '';
if( isset($params['options']) ) {
    $options = trim($params['options']);
    $arr_options = news_admin_ops::optionstext_to_array($options);
}

$userid = get_userid();

if (isset($params['submit'])) {
    $error = false;
    if ($name == '') $error = $this->Lang('nonamegiven');

    if( !$error && $type == 'dropdown' && count($arr_options) == 0 ) $error = $this->Lang('error_nooptions');

    if( !$error ) {
        $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ?';
        $exists = $db->GetOne($query,array($name));
        if( $exists ) $error = $this->Lang('nameexists');
    }

    if( !$error ) {
        $max = $db->GetOne('SELECT max(item_order) + 1 FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs');
        if( $max == null ) $max = 1;

        $extra = array('options'=>$arr_options);
        $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_fielddefs (name, type, max_length, item_order, create_date, modified_date, public, extra) VALUES (?,?,?,?,?,?,?,?)';
        $parms = array($name, $type, $max_length, $max,
                       trim($db->DbTimeStamp(time()), "'"),
                       trim($db->DbTimeStamp(time()), "'"),
                       $public, serialize($extra));
        $db->Execute($query, $parms );

        // put mention into the admin log
        audit('', 'News custom: '.$name, 'Field definition added');

        // done.
        $this->SetMessage($this->Lang('fielddefadded'));
        $this->RedirectToAdminTab('customfields','','admin_settings');
    }

    if( $error ) $this->ShowErrors($error);
}

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

