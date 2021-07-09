<?php
/*
Search module action: defaultadmin
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminUtils;
use CMSMS\FormUtils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

if (isset($params['reindex'])) {
    $this->Reindex();
    $this->ShowMessage($this->Lang('reindexcomplete'));
} elseif (isset($params['clearwordcount'])) {
    $query = 'TRUNCATE TABLE '.CMS_DB_PREFIX.'module_search_words';
    $db->Execute($query);
} elseif (isset($params['exportcsv'])) {
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
    $data = $db->GetArray($query);
    if (is_array($data)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=search.csv');
        while(@ob_end_clean());

        $output = '';
        for ($i = 0, $n = count($data); $i < $n; $i++) {
            $output .= "\"{$data[$i]['word']}\",{$data[$i]['count']}\n";
        }
        echo $output;
        exit;
    }
} elseif (isset($params['resettodefault'])) {
    $this->SetPreference('stopwords',$this->DefaultStopWords());
} elseif (isset($params['apply'])) {
    $this->SetPreference('stopwords',$params['stopwords']);
    $this->SetPreference('searchtext',$params['searchtext']);

    $curval = (bool)$this->GetPreference('usestemming',0);
    $newval = !empty($params['usestemming']);
    if ($newval != $curval) {
        $this->SetPreference('usestemming',(($newval)?1:0));
        $this->Reindex();
        $this->ShowMessage($this->Lang('reindexcomplete'));
    }

    $newval = !empty($params['savephrases']);
    $this->SetPreference('savephrases',(($newval)?1:0));

    $newval = !empty($params['alpharesults']);
    $this->SetPreference('alpharesults',(($newval)?1:0));

    $this->SetPreference('resultpage',(int)$params['resultpage']);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl')); //,null,null,$smarty);

//The tabs
if (!empty($params['activetab'])) {
    $tab = $params['activetab'];
} else {
    $tab = '';
}
$tpl->assign('tab', $tab);

include __DIR__.DIRECTORY_SEPARATOR.'function.admin_statistics_tab.php';

$curval = $this->GetPreference('stopwords');
if (!$curval) { $curval = $this->DefaultStopWords(); }

$tpl->assign('formstart',$this->CreateFormStart($id, 'defaultadmin',$returnid,'post','',false,'',
                                                   ['activetab'=>'options']))
// ->assign('reindex', '<button type="submit" name="'.$id.'reindex" id="'.$id.'reindex" class="adminsubmit icon do">'.$this->Lang('reindexallcontent').'</button>')
 ->assign('prompt_stopwords',$this->Lang('stopwords'))
 ->assign('input_stopwords', FormUtils::create_textarea([
    'modid' => $id,
    'name' =>'stopwords',
    'rows' => 6,
    'cols' => 50,
    'value' => strtr($curval,"\r\n",'  '),
]))
 ->assign('prompt_resetstopwords',$this->Lang('prompt_resetstopwords'))
// ->assign('input_resetstopwords', '<button type="submit" name="'.$id.'resettodefault" id="'.$id.'resettodefault" class="adminsubmit icon undo">'.$this->Lang('input_resetstopwords').'</button>')

 ->assign('prompt_stemming',$this->Lang('usestemming'))
// ->assign('input_stemming',$this->CreateInputCheckbox($id, 'usestemming',1,
//            $this->GetPreference('usestemming', 0)))
 ->assign('stemming',$this->GetPreference('usestemming',0))

 ->assign('prompt_searchtext',$this->Lang('prompt_searchtext'))
// ->assign('input_searchtext',$this->CreateInputText($id,'searchtext',
//            $this->GetPreference('searchtext','')))
 ->assign('searchtext',$this->GetPreference('searchtext',''))

 ->assign('prompt_savephrases',$this->Lang('prompt_savephrases'))
// ->assign('input_savephrases',$this->CreateInputCheckbox($id,'savephrases',1,
//            $this->GetPreference('savephrases',1)))
 ->assign('savephrases',$this->GetPreference('savephrases',1))

 ->assign('prompt_alpharesults',$this->Lang('prompt_alpharesults'))
// ->assign('input_alpharesults',$this->CreateInputCheckbox($id,'alpharesults','true',
//            $this->GetPreference('alpharesults',0)))
 ->assign('alpharesults',$this->GetPreference('alpharesults',0))

 ->assign('prompt_resultpage',$this->Lang('prompt_resultpage'))
 ->assign('input_resultpage',
            AdminUtils::CreateHierarchyDropdown(0,$this->GetPreference('resultpage',-1),$id.'resultpage',true));

$s1 = $this->Lang('confirm_clearstats');
$s2 = json_encode($this->Lang('confirm_reindex'));

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('button[name="{$id}clearwordcount"]').on('click', function() {
  cms_confirm_btnclick(this, '$s1');
  return false;
 });
 $('button[name="{$id}reindex"]').on('click', function() {
  cms_confirm_btnclick(this, $s2);
  return false;
 });
});
//]]>
</script>
EOS;
add_page_foottext($js);

$tpl->display();
return '';
