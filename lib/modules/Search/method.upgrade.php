<?php
/*
Search module upgrade procedure
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
use CMSMS\TemplateType;

if (!isset($gCms)) exit;

if (version_compare($oldversion,'1.50') < 1) {
    $this->RegisterModulePlugin(true);
    $this->RegisterSmartyPlugin('search','function','function_plugin');

    $me = $this->GetName();
    if (AppState::test_state(AppState::STATE_INSTALL)) {
        $userid = 1; // hardcode to first user
    } else {
        $userid = get_userid();
    }

    try {
        try {
            $type = new TemplateType();
            $type->set_originator($me);
            $type->set_name('searchform');
            $type->set_dflt_flag(TRUE);
            $type->set_lang_callback('Search::page_type_lang_callback');
            $type->set_content_callback('Search::reset_page_type_defaults');
            $type->reset_content_to_factory();
            $type->save();
        } catch (Throwable $t) {
            // ignore this error
        }

        $content = $this->GetTemplate('displaysearch');
        if ($content) {
            $tpl = new Template();
            $tpl->set_originator($me);
            $tpl->set_name('Search Form Sample');
            $tpl->set_owner($userid);
            $tpl->set_content($content);
            $tpl->set_type($type);
            $tpl->set_type_dflt(TRUE);
            $tpl->save();
            $this->DeleteTemplate('displaysearch');
        }

        try {
            $type = new TemplateType();
            $type->set_originator($me);
            $type->set_name('searchresults');
            $type->set_dflt_flag(TRUE);
            $type->set_lang_callback('Search::page_type_lang_callback');
            $type->set_content_callback('Search::reset_page_type_defaults');
            $type->reset_content_to_factory();
            $type->save();
        } catch (Throwable $t) {
            // ignore this error
        }

        $content = $this->GetTemplate('displayresult');
        if ($content) {
            $tpl = new Template();
            $tpl->set_originator($me);
            $tpl->set_name('Search Results Sample');
            $tpl->set_owner($userid);
            $tpl->set_content($content);
            $tpl->set_type($type);
            $tpl->set_type_dflt(TRUE);
            $tpl->save();
            $this->DeleteTemplate('displayresult');
        }
    } catch (Throwable $t) {
        audit('',$me,'Installation error: '.$t->GetMessage());
        return $t->GetMessage();
    }
}

if (version_compare($oldversion,'1.51') < 0) {
    $tables = [
        CMS_DB_PREFIX.'module_search_items', //stet??
        CMS_DB_PREFIX.'module_search_index', //stet??
//        CMS_DB_PREFIX.'module_search_words', stet??
    ];
    $sql = 'ALTER TABLE %s ENGINE=InnoDB'; //KEY_BLOCK_SIZE=8 ROW_FORMAT=COMPRESSED support transactions
    foreach ($tables as $table) {
        $db->Execute(sprintf($sql, $table));
    }
}

if (version_compare($oldversion,'1.52') < 0) {
    $dict = new DataDictionary($db);
    $sqlarray = $dict->CreateIndexSQL('index_search_item', CMS_DB_PREFIX.'module_search_index', 'item_id');
    $dict->ExecuteSQLArray($sqlarray);
}

if (version_compare($oldversion,'1.53') < 0) {
    foreach (['alpharesults', 'savephrases', 'usestemming'] as $key) {
        $val = $this->GetPreference($key, 0);
        $val = cms_to_bool($val);
        $this->SetPreference($key, (($val) ? 1 : 0));
    }
}
