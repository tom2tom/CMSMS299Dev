<?php
use FileManager\Utils;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files')) exit;

if (isset($params['fmmessage']) && $params['fmmessage']!='') {
    // gotta get rid of this stuff.
    $count='';
    if (isset($params['fmmessagecount']) && $params['fmmessagecount']!='') $count=$params['fmmessagecount'];
    $this->ShowMessage($this->Lang($params['fmmessage'],$count));
}

if (isset($params['fmerror']) && $params['fmerror']!='') {
    // gotta get rid of this stuff
    $count='';
    if (isset($params['fmerrorcount']) && $params['fmerrorcount']!='') $count=$params['fmerrorcount'];
    $this->ShowErrors($this->Lang($params['fmerror'],$count));
}

if (isset($params['newsort'])) $this->SetPreference('sortby',$params['newsort']);

$path = trim(ltrim(Utils::get_cwd(), DIRECTORY_SEPARATOR));
if( Utils::can_do_advanced() && $this->GetPreference('advancedmode',0) ) {
    $path = '::top::'.DIRECTORY_SEPARATOR.$path;
}
$tmp_path_parts = explode(DIRECTORY_SEPARATOR,$path);
$path_parts = [];
for( $i = 0, $n = count($tmp_path_parts); $i < $n; $i++ ) {
    $obj = new stdClass();
    if( !$tmp_path_parts[$i] ) continue;
    $obj->name = $tmp_path_parts[$i];
    if( $obj->name == '::top::' ) {
        $obj->name = 'root';
    }
    if( $i < $n - 1 ) {
        // not the last entry
        $fullpath = implode(DIRECTORY_SEPARATOR,array_slice($tmp_path_parts,0,$i+1));
        if( startswith($fullpath,'::top::') ) $fullpath = substr($fullpath,7);
        $obj->url = $this->create_action_url($id, 'changedir', ['setdir' => $fullpath]);
    } else {
        // the last entry... no link
    }
    $path_parts[] = $obj;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('fmpath.tpl')); //,null,null,$smarty);
$tpl->assign('path',$path)
 ->assign('path_parts',$path_parts)
 ->assign('sep', '&raquo;'); //TODO or '&laquo;' for rtl context

$tpl->display();

// get the upload elements
include __DIR__.DIRECTORY_SEPARATOR.'uploadview.php';
// get the files table
include __DIR__.DIRECTORY_SEPARATOR.'action.admin_fileview.php'; // this is also an action, for ajax processing
