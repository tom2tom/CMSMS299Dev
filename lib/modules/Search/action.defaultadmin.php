<?php
# Search module action: defaultadmin
# Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AdminUtils;
use CMSMS\FormUtils;

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
        for( $i = 0, $n = count($data); $i < $n; $i++ ) {
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

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl'),null,null,$smarty);

//The tabs
if (!empty($params['active_tab'])) $tab = $params['active_tab'];
else $tab = '';

$tpl->assign('tab', $tab);

include __DIR__.DIRECTORY_SEPARATOR.'function.admin_statistics_tab.php';

$tpl->assign('formstart',$this->CreateFormStart($id, 'defaultadmin',$returnid,'post','',false,'',
                                                   ['active_tab'=>'options']))
 ->assign('reindex', '<button type="submit" name="'.$id.'reindex" id="'.$id.'reindex" class="adminsubmit icon do">'.$this->Lang('reindexallcontent').'</button>')
 ->assign('prompt_stopwords',$this->Lang('stopwords'))
 ->assign('input_stopwords', FormUtils::create_textarea([
	'modid' => $id,
    'name' =>'stopwords',
	'rows' => 6,
	'cols' => 50,
	'value' => str_replace(["\n", "\r"], [' ', ' '], $this->GetPreference('stopwords', $this->DefaultStopWords())),
]))
 ->assign('prompt_resetstopwords',$this->Lang('prompt_resetstopwords'))
 ->assign('input_resetstopwords', '<button type="submit" name="'.$id.'resettodefault" id="'.$id.'resettodefault" class="adminsubmit icon undo">'.$this->Lang('input_resetstopwords').'</button>')

 ->assign('prompt_stemming',$this->Lang('usestemming'))
 ->assign('input_stemming',
                $this->CreateInputCheckbox($id, 'usestemming', 'true',
                                           $this->GetPreference('usestemming', 'false')))

 ->assign('prompt_searchtext',$this->Lang('prompt_searchtext'))
 ->assign('input_searchtext',
                $this->CreateInputText($id,'searchtext',
                                       $this->GetPreference('searchtext','')))

 ->assign('prompt_savephrases',$this->Lang('prompt_savephrases'))
 ->assign('input_savephrases',
                $this->CreateInputCheckbox($id,'savephrases','true',
                                           $this->GetPreference('savephrases','false')))

->assign('prompt_alpharesults',$this->Lang('prompt_alpharesults'))
->assign('input_alpharesults',
                $this->CreateInputCheckbox($id,'alpharesults','true',
                                           $this->GetPreference('alpharesults','false')));

$tpl->assign('prompt_resultpage',$this->Lang('prompt_resultpage'));
$tpl->assign('input_resultpage',
				AdminUtils::CreateHierarchyDropdown(0,$this->GetPreference('resultpage',-1),$id.'resultpage',true));

$tpl->display();
