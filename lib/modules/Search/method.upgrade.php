<?php
# Search module upgrade procedure
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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppState;
use CMSMS\Database\DataDictionary;

if( !isset($gCms) ) exit;

if( version_compare($oldversion,'1.50') < 1 ) {
    $this->RegisterModulePlugin(true);
    $this->RegisterSmartyPlugin('search','function','function_plugin');

    $me = $this->GetName();
    if( AppState::test_state(AppState::STATE_INSTALL) ) {
        $user_id = 1; // hardcode to first user
    } else {
        $user_id = get_userid();
    }

    try {
        try {
            $type = new CmsLayoutTemplateType();
            $type->set_originator($me);
            $type->set_name('searchform');
            $type->set_dflt_flag(TRUE);
            $type->set_lang_callback('Search::page_type_lang_callback');
            $type->set_content_callback('Search::reset_page_type_defaults');
            $type->reset_content_to_factory();
            $type->save();
        }
        catch( CmsInvalidDataException $e ) {
            // ignore this error.
        }

        $content = $this->GetTemplate('displaysearch');
        if( $content ) {
            $tpl = new CmsLayoutTemplate();
            $tpl->set_originator($me);
            $tpl->set_name('Search Form Sample');
            $tpl->set_owner($user_id);
            $tpl->set_content($content);
            $tpl->set_type($type);
            $tpl->set_type_dflt(TRUE);
            $tpl->save();
            $this->DeleteTemplate('displaysearch');
        }

        try {
            $type = new CmsLayoutTemplateType();
            $type->set_originator($me);
            $type->set_name('searchresults');
            $type->set_dflt_flag(TRUE);
            $type->set_lang_callback('Search::page_type_lang_callback');
            $type->set_content_callback('Search::reset_page_type_defaults');
            $type->reset_content_to_factory();
            $type->save();
        }
        catch( CmsInvalidDataException $e ) {
            // ignore this error.
        }

        $content = $this->GetTemplate('displayresult');
        if( $content ) {
            $tpl = new CmsLayoutTemplate();
            $tpl->set_originator($me);
            $tpl->set_name('Search Results Sample');
            $tpl->set_owner($user_id);
            $tpl->set_content($content);
            $tpl->set_type($type);
            $tpl->set_type_dflt(TRUE);
            $tpl->save();
            $this->DeleteTemplate('displayresult');
        }
    }
    catch( CmsException $e ) {
        audit('',$me,'Installation error: '.$e->GetMessage());
    }
}

if( version_compare($oldversion,'1.51') < 0 ) {
    $tables = [
        CMS_DB_PREFIX.'module_search_items',
        CMS_DB_PREFIX.'module_search_index',
        CMS_DB_PREFIX.'module_search_words'
    ];
    $sql = 'ALTER TABLE %s ENGINE=InnoDB';
    foreach( $tables as $table ) {
        $db->Execute(sprintf($sql,$table));
    }
}

if( version_compare($oldversion,'1.52') < 0 ) {
    $dict = new DataDictionary($db);
    $sqlarray = $dict->CreateIndexSQL('index_search_item', 'module_search_index', 'item_id');
    $dict->ExecuteSQLArray($sqlarray);
}
