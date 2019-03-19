<?php
if (!isset($gCms)) exit;

$template = null;
if (isset($params['formtemplate'])) {
    $template = trim($params['formtemplate']);
}
else {
    $tpl = LayoutTemplateOperations::load_default_template_by_type('Search::searchform');
    if( !is_object($tpl) ) {
        audit('',$this->GetName(),'No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);
$inline = false;
if( isset( $params['inline'] ) ) {
    $txt = strtolower(trim($params['inline']));
    if( $txt == 'true' || $txt == '1' || $txt == 'yes' ) $inline = true;
}
$origreturnid = $returnid;
if( isset( $params['resultpage'] ) ) {
    $hm = $gCms->GetHierarchyManager();
    $cid = $hm->find_by_tag_anon($params['resultpage']);
    if( $cid ) $returnid = $cid;
}
//Pretty URL compatibility
$is_method = isset($params['search_method'])?'post':'get';

// Variable named hogan in honor of moorezilla's Rhodesian Ridgeback :) https://forum.cmsmadesimple.org/index.php/topic,9580.0.html
$submittext = $params['submit'] ?? $this->Lang('searchsubmit');
$searchtext = $params['searchtext'] ?? $this->GetPreference('searchtext','');
$tpl->assign('search_actionid',$id)
 ->assign('searchtext',$searchtext)
 ->assign('destpage',$returnid)
 ->assign('form_method',$is_method)
 ->assign('inline',$inline)
 ->assign('startform', $this->CreateFormStart($id, 'dosearch', $returnid, $is_method, '', $inline ))
 ->assign('label', '<label for="'.$id.'searchinput">'.$this->Lang('search').'</label>')
 ->assign('searchprompt',$this->Lang('search'))
// ->assign('inputbox', $this->CreateInputText($id, 'searchinput', $searchtext, 20, 50, $hogan))
// ->assign('submitbutton', '<button type="submit" name="'.$id.'submit" id="'.$id.'submit" class="adminsubmit icon check">'.$submittext.'</button>')
 ->assign('submittext', $submittext);

// only here for backwards compatibility.
$hogan = "onfocus=\"if(this.value==this.defaultValue) this.value='';\""." onblur=\"if(this.value=='') this.value=this.defaultValue;\"";
$tpl->assign('hogan',$hogan);

$hidden = '';
if( $origreturnid != $returnid ) $hidden .= $this->CreateInputHidden($id, 'origreturnid', $origreturnid);
if( isset( $params['modules'] ) ) $hidden .= $this->CreateInputHidden( $id, 'modules', trim($params['modules']) );
if( isset( $params['detailpage'] ) ) $hidden .= $this->CreateInputHidden( $id, 'detailpage', trim($params['detailpage']) );
foreach( $params as $key => $value ) {
    if( preg_match( '/^passthru_/', $key ) > 0 ) $hidden .= $this->CreateInputHidden($id,$key,$value);
}

if( $hidden != '' ) $tpl->assign('hidden',$hidden);
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->display();

