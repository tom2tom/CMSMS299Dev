<?php

use CMSMS\Database\DataDictionary;

if( !isset($gCms) ) exit;

$newsite = $gCms->test_state(CmsApp::STATE_INSTALL);
if( $newsite ) {
    $uid = 1; // templates owned by initial admin
} else {
    $uid = get_userid();
}

$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];

$flds = '
id I KEY,
module_name C(100),
content_id I,
extra_attr C(100),
expires DT
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_search_items', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence(CMS_DB_PREFIX.'module_search_items_seq');

$sqlarray = $dict->CreateIndexSQL('items_search_items',
            CMS_DB_PREFIX.'module_search_items', 'module_name,content_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('items_search_content',
            CMS_DB_PREFIX.'module_search_items', 'content_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('items_search_attr',
            CMS_DB_PREFIX.'module_search_items', 'extra_attr');
$dict->ExecuteSQLArray($sqlarray);

$flds = '
item_id I,
word C(255),
count I(4)
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_search_index', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->CreateIndexSQL('index_search_item',
            CMS_DB_PREFIX.'module_search_index', 'item_id'); //non-unique field used in join
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('index_search_word',
            CMS_DB_PREFIX.'module_search_index', 'word');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('index_search_count',
            CMS_DB_PREFIX.'module_search_index', 'count');
$dict->ExecuteSQLArray($sqlarray);

$flds = '
word C(255) KEY,
count I(4)
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_search_words', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$this->SetPreference('stopwords', $this->DefaultStopWords());
$this->SetPreference('usestemming', 'false');
$this->SetPreference('searchtext','Enter Search...');

$me = $this->GetName();

try {
    $searchform_type = new CmsLayoutTemplateType();
    $searchform_type->set_originator($me);
    $searchform_type->set_name('searchform');
    $searchform_type->set_dflt_flag(TRUE);
    $searchform_type->set_lang_callback('Search::page_type_lang_callback');
    $searchform_type->set_content_callback('Search::reset_page_type_defaults');
    $searchform_type->reset_content_to_factory();
    $searchform_type->save();

    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('Search Form Sample');
    $tpl->set_owner($uid);
    $tpl->set_content($this->GetSearchHtmlTemplate());
    $tpl->set_type($searchform_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();

    if( $newsite ) { //TODO also test for demonstration content installation
        // setup Simplex Theme search form template
        try {
            $fn = (__DIR__).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'Simplex_Search_template.tpl';
            if( is_file( $fn ) ) {
                $template = @file_get_contents($fn);
                $tpl = new CmsLayoutTemplate();
                $tpl->set_originator($me);
                $tpl->set_name('Simplex Search');
                $tpl->set_owner($uid);
                $tpl->set_content($template);
                $tpl->set_type($searchform_type);
                $tpl->add_design('Simplex');
                $tpl->save();
            }
        } catch( Exception $e ) {
            audit('', $me, 'Installation Error: '.$e->GetMessage());
        }
    }

    $searchresults_type = new CmsLayoutTemplateType();
    $searchresults_type->set_originator($me);
    $searchresults_type->set_name('searchresults');
    $searchresults_type->set_dflt_flag(TRUE);
    $searchresults_type->set_lang_callback('Search::page_type_lang_callback');
    $searchresults_type->set_content_callback('Search::reset_page_type_defaults');
    $searchresults_type->reset_content_to_factory();
    $searchresults_type->save();

    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('Search Results Sample');
    $tpl->set_owner($uid);
    $tpl->set_content($this->GetResultsHtmlTemplate());
    $tpl->set_type($searchresults_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();
} catch( CmsException $e ) {
    audit('',$me,'Installation Error: '.$e->GetMessage());
}

$this->CreateEvent('SearchInitiated');
$this->CreateEvent('SearchCompleted');
$this->CreateEvent('SearchItemAdded');
$this->CreateEvent('SearchItemDeleted');
$this->CreateEvent('SearchAllItemsDeleted');

$this->RegisterEvents();
$this->RegisterModulePlugin(true);
$this->RegisterSmartyPlugin('search','function','function_plugin');

$this->Reindex();
