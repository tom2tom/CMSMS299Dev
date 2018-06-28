<?php
if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

if (isset($params['reindex'])) {
    $this->Reindex();
    $this->ShowMessage($this->Lang('reindexcomplete'));
}
else if (isset($params['clearwordcount'])) {
    $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_search_words';
    $db->Execute($query);
}
else if (isset($params['exportcsv']) ) {
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
    $data = $db->GetArray($query);
    if( is_array($data) ) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=search.csv');
        while(@ob_end_clean());

        $output = '';
        for( $i = 0; $i < count($data); $i++ ) {
            $output .= "\"{$data[$i]['word']}\",{$data[$i]['count']}\n";
        }
        echo $output;
        exit();
    }
}
else if (isset($params['resettodefault'])) {
    $this->SetPreference('stopwords', $this->DefaultStopWords());
}
else if (isset($params['submit'])) {
    $this->SetPreference('stopwords', $params['stopwords']);
    $this->SetPreference('searchtext', $params['searchtext']);

    $curval = $this->GetPreference('usestemming', 'false');
    $newval = 'false';
    if (isset($params['usestemming'])) $newval = 'true';

    if ($newval != $curval) {
        $this->SetPreference('usestemming', $newval);
        $this->Reindex();
        $this->ShowMessage($this->Lang('reindexcomplete'));
    }

    $newval = 'false';
    if (isset($params['savephrases'])) $newval = 'true';
    $this->SetPreference('savephrases',$newval);

    $newval = 'false';
    if (isset($params['alpharesults'])) $newval = 'true';
    $this->SetPreference('alpharesults', $newval);

    $this->SetPreference('resultpage', (int)$params['resultpage']);
}


#The tabs
echo $this->StartTabHeaders();
$tab = '';
if (FALSE == empty($params['active_tab'])) $tab = $params['active_tab'];
echo $this->SetTabHeader('statistics',$this->Lang('statistics'),('statistics' == $tab));
echo $this->SetTabHeader('options',$this->Lang('options'), ('options' == $tab));
echo $this->EndTabHeaders();

#The content of the tabs
echo $this->StartTabContent();

echo $this->StartTab('statistics',$params);
include __DIR__.'/function.admin_statistics_tab.php';
echo $this->EndTab();

echo $this->StartTab('options', $params);
$smarty->assign('formstart',$this->CreateFormStart($id, 'defaultadmin',$returnid,'post','',false,'',
                                                   array('active_tab'=>'options')));
$smarty->assign('reindex', '<button type="submit" name="'.$id.'reindex" id="'.$id.'reindex" class="adminsubmit icon do">'.$this->Lang('reindexallcontent').'</button>');
$smarty->assign('prompt_stopwords',$this->Lang('stopwords'));
$smarty->assign('input_stopwords', CmsFormUtils::create_textarea([
	'modid' => $id,
    'name' =>'stopwords',
	'rows' => 6,
	'cols' => 50,
	'value' => str_replace(["\n", "\r"], [' ', ' '], $this->GetPreference('stopwords', $this->DefaultStopWords())),
]));
$smarty->assign('prompt_resetstopwords',$this->Lang('prompt_resetstopwords'));
$smarty->assign('input_resetstopwords', '<button type="submit" name="'.$id.'resettodefault" id="'.$id.'resettodefault" class="adminsubmit icon undo">'.$this->Lang('input_resetstopwords').'</button>');

$smarty->assign('prompt_stemming',$this->Lang('usestemming'));
$smarty->assign('input_stemming',
                $this->CreateInputCheckbox($id, 'usestemming', 'true',
                                           $this->GetPreference('usestemming', 'false')));

$smarty->assign('prompt_searchtext',$this->Lang('prompt_searchtext'));
$smarty->assign('input_searchtext',
                $this->CreateInputText($id,'searchtext',
                                       $this->GetPreference('searchtext','')));

$smarty->assign('prompt_savephrases',$this->Lang('prompt_savephrases'));
$smarty->assign('input_savephrases',
                $this->CreateInputCheckbox($id,'savephrases','true',
                                           $this->GetPreference('savephrases','false')));

$smarty->assign('prompt_alpharesults',$this->Lang('prompt_alpharesults'));
$smarty->assign('input_alpharesults',
                $this->CreateInputCheckbox($id,'alpharesults','true',
                                           $this->GetPreference('alpharesults','false')));

$contentops = $gCms->GetContentOperations();
$smarty->assign('prompt_resultpage',$this->Lang('prompt_resultpage'));
/*
$smarty->assign('input_resultpage',
                $contentops->CreateHierarchyDropdown('',$this->GetPreference('resultpage',-1),$id.'resultpage',1));
*/
$smarty->assign('formend',$this->CreateFormEnd());
echo $this->ProcessTemplate('options_tab.tpl');
echo $this->EndTab();
echo $this->EndTabContent();
