<?php
/*
Search module action: defaultadmin
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminUtils;
use CMSMS\FormUtils;
use Search\Utils;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

// TODO sanitizeVal(all $params[])
if (isset($params['reindex'])) {
    Utils::Reindex($this);
    $this->ShowMessage($this->Lang('reindexcomplete'));
} elseif (isset($params['clearsearch'])) {
    $query = 'TRUNCATE TABLE '.CMS_DB_PREFIX.'module_search_words';
    $db->execute($query);
} elseif (isset($params['examplesearch'])) {
    $query = 'TRUNCATE TABLE '.CMS_DB_PREFIX.'module_search_words';
    $db->execute($query);
    $phrase = 'This is a CMSMS website';
    $words = array_values(Utils::StemPhrase($this,$phrase));
    Utils::UpdateWords($this,$phrase,$words);
} elseif (isset($params['exportcsv'])) {
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY `count` DESC';
    $data = $db->getArray($query);
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
    $newval = Utils::CleanWords($params['stopwords']);
    $this->SetPreference('stopwords',$newval);

    $this->SetPreference('searchtext',$params['searchtext']);

    $curval = (bool)$this->GetPreference('usestemming',0);
    $newval = isset($params['usestemming']) && cms_to_bool($params['usestemming']);
    if ($newval != $curval) {
        $this->SetPreference('usestemming',(($newval)?1:0));
        Utils::Reindex($this);
        $this->ShowMessage($this->Lang('reindexcomplete'));
    }

    $newval = isset($params['savephrases']) && cms_to_bool($params['savephrases']);
    $this->SetPreference('savephrases',(($newval)?1:0));

    $newval = isset($params['alpharesults']) && cms_to_bool($params['alpharesults']);
    $this->SetPreference('alpharesults',(($newval)?1:0));

    $this->SetPreference('resultpage',(int)$params['resultpage']);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl')); //,null,null,$smarty);

//tabs
if (!empty($params['activetab'])) {
    $tab = $params['activetab'];
} else {
    $tab = '';
}
$tpl->assign('tab',$tab);

//results tab
$words = [];
$query = 'SELECT word,`count` FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY `count` DESC';
$rst = $db->selectLimit($query,50,0);
if ($rst) {
    $words = $rst->getArray();
    $rst->Close();
}
$tpl->assign('topwords',$words);
$tpl->assign('formstart1',$this->CreateFormStart($id,'defaultadmin'));

//settings tab
$curval = $this->GetPreference('stopwords');
if (!$curval) {
    $curval = $this->DefaultStopWords();
}

$tpl->assign('formstart2',
     $this->CreateFormStart($id,'defaultadmin',$returnid,'post','',false,'',['activetab'=>'settings']))
 ->assign('prompt_stopwords',$this->Lang('stopwords'))
 ->assign('input_stopwords',
     FormUtils::create_textarea([
      'getid' => $id,
      'name' =>'stopwords',
      'rows' => 6,
      'cols' => 50,
      'value' => $curval,
     ]))
 ->assign('prompt_resetstopwords',$this->Lang('prompt_resetstopwords'))
 ->assign('prompt_stemming',$this->Lang('usestemming'))
 ->assign('stemming',$this->GetPreference('usestemming',0))
 ->assign('prompt_searchtext',$this->Lang('prompt_searchtext'))
 ->assign('searchtext',$this->GetPreference('searchtext',''))
 ->assign('prompt_savephrases',$this->Lang('prompt_savephrases'))
 ->assign('savephrases',$this->GetPreference('savephrases',1))
 ->assign('prompt_alpharesults',$this->Lang('prompt_alpharesults'))
 ->assign('alpharesults',$this->GetPreference('alpharesults',0))
 ->assign('prompt_resultpage',$this->Lang('prompt_resultpage'))
 ->assign('input_resultpage',
     AdminUtils::CreateHierarchyDropdown(0,$this->GetPreference('resultpage',-1),$id.'resultpage',true));

$s1 = addcslashes($this->Lang('confirm_clearstats'), "'");
$s2 = addcslashes($this->Lang('confirm_reindex'), "'");

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('button[name="{$id}clearsearch"]').on('click', function() {
  cms_confirm_btnclick(this, '$s1');
  return false;
 });
 $('button[name="{$id}reindex"]').on('click', function() {
  cms_confirm_btnclick(this, '$s2');
  return false;
 });
});
//]]>
</script>
EOS;
add_page_foottext($js);

$tpl->display();
