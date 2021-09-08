<?php
/*
Search module installation proceedure
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

use CMSMS\AppState;
use CMSMS\Database\DataDictionary;
use CMSMS\Template;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;

if (!isset($gCms)) exit;

$newsite = AppState::test(AppState::INSTALL);
if ($newsite) {
    $userid = 1; // templates owned by initial admin
} else {
    $userid = get_userid(false);
}

$dict = new DataDictionary($db);

$flds = '
id I UNSIGNED KEY,
module_name C(50),
content_id I UNSIGNED,
extra_attr C(100),
expires DT
';
// transactions are used on this table, so InnoDB engine
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_search_items', $flds,
['mysqli' => 'CHARACTER SET ascii']); // InnoDB engine to allow transactions TODO ascii ok for extra_attr ? only use is 'content'
$dict->ExecuteSQLArray($sqlarray);
// use of inserted-data identifier is a bit racy elsewhere, and a sequencer helps a bit
$db->CreateSequence(CMS_DB_PREFIX.'module_search_items_seq');

$sqlarray = $dict->CreateIndexSQL('i_modulename_contentid',
            CMS_DB_PREFIX.'module_search_items', 'module_name,content_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_contentid',
            CMS_DB_PREFIX.'module_search_items', 'content_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_extraattr',
            CMS_DB_PREFIX.'module_search_items', 'extra_attr');
$dict->ExecuteSQLArray($sqlarray);

$flds = '
item_id I UNSIGNED NOTNULL,
word C(128) CHARACTER SET utf8mb4 NOTNULL,
count I UNSIGNED NOTNULL DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_search_index', $flds,
['mysqli' => 'ENGINE=MyISAM CHARACTER SET ascii COLLATE ascii_bin']);
$dict->ExecuteSQLArray($sqlarray);
// TODO index params if non-InnoDB engine used
$sqlarray = $dict->CreateIndexSQL('i_itemid',
            CMS_DB_PREFIX.'module_search_index', 'item_id', ['KEY_BLOCK_SIZE'=>1]); //non-unique field used in sub-query/join
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_word',
            CMS_DB_PREFIX.'module_search_index', 'word', ['KEY_BLOCK_SIZE'=>2]);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_count',
            CMS_DB_PREFIX.'module_search_index', 'count', ['KEY_BLOCK_SIZE'=>1]);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
word C(128) NOTNULL KEY,
count I UNSIGNED NOTNULL DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_search_words', $flds,
['mysqli' => 'ENGINE=MyISAM CHARACTER SET utf8mb4']);
$dict->ExecuteSQLArray($sqlarray);

$me = $this->GetName();

try {
    $form_type = new TemplateType();
    $form_type->set_originator($me);
    $form_type->set_name('searchform');
    $form_type->set_dflt_flag(TRUE);
    $form_type->set_lang_callback('Search::page_type_lang_callback');
    $form_type->set_content_callback('Search::reset_page_type_defaults');
    $form_type->reset_content_to_factory();
    $form_type->save();

    $tpl = new Template();
    $tpl->set_originator($me);
    $tpl->set_name('Search Form Sample');
    $tpl->set_owner($userid);
    $tpl->set_content($this->GetSearchHtmlTemplate());
    $tpl->set_type($form_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();

    $results_type = new TemplateType();
    $results_type->set_originator($me);
    $results_type->set_name('searchresults');
    $results_type->set_dflt_flag(TRUE);
    $results_type->set_lang_callback('Search::page_type_lang_callback');
    $results_type->set_content_callback('Search::reset_page_type_defaults');
    $results_type->reset_content_to_factory();
    $results_type->save();

    $tpl = new Template();
    $tpl->set_originator($me);
    $tpl->set_name('Search Results Sample');
    $tpl->set_owner($userid);
    $tpl->set_content($this->GetResultsHtmlTemplate());
    $tpl->set_type($results_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();
} catch( Throwable $t) {
    if ($newsite) {
        return $t->GetMessage();
    } else {
        audit('', $me, 'Installation error: '.$t->GetMessage());
    }
}

$this->SetPreference('alpharesults', 0);
$this->SetPreference('savephrases', 1);
$this->SetPreference('stopwords', $this->DefaultStopWords());
$this->SetPreference('searchtext', $this->Lang('searchplaceholder'));
$this->SetPreference('usestemming', 0);

$this->CreateEvent('SearchInitiated');
$this->CreateEvent('SearchCompleted');
$this->CreateEvent('SearchItemAdded');
$this->CreateEvent('SearchItemDeleted');
$this->CreateEvent('SearchAllItemsDeleted');

$this->RegisterEvents();
$this->RegisterModulePlugin(true);
$this->RegisterSmartyPlugin('search', 'function', 'function_plugin');

$this->Reindex();
