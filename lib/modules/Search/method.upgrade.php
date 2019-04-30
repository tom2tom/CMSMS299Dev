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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Database\DataDictionary;

if (!isset($gCms)) exit;
//$db = $this->GetDb();

$uid = null;
if( cmsms()->test_state(CmsApp::STATE_INSTALL) ) {
  $uid = 1; // hardcode to first user
} else {
  $uid = get_userid();
}

if( version_compare($oldversion,'1.50') < 1 ) {
  $this->RegisterModulePlugin(true);
  $this->RegisterSmartyPlugin('search','function','function_plugin');

  $me = $this->GetName();

  try {
      try {
          $searchform_type = new CmsLayoutTemplateType();
          $searchform_type->set_originator($me);
          $searchform_type->set_name('searchform');
          $searchform_type->set_dflt_flag(TRUE);
          $searchform_type->set_lang_callback('Search::page_type_lang_callback');
          $searchform_type->set_content_callback('Search::reset_page_type_defaults');
          $searchform_type->reset_content_to_factory();
          $searchform_type->save();
      }
      catch( CmsInvalidDataException $e ) {
          // ignore this error.
      }

      $content = $this->GetTemplate('displaysearch');
      if( $content ) {
          $tpl = new CmsLayoutTemplate();
          $tpl->set_originator($me);
          $tpl->set_name('Search Form Sample');
          $tpl->set_owner($uid);
          $tpl->set_content($content);
          $tpl->set_type($searchform_type);
          $tpl->set_type_dflt(TRUE);
          $tpl->save();
          $this->DeleteTemplate('displaysearch');
      }

      try {
          $searchresults_type = new CmsLayoutTemplateType();
          $searchresults_type->set_originator($me);
          $searchresults_type->set_name('searchresults');
          $searchresults_type->set_dflt_flag(TRUE);
          $searchresults_type->set_lang_callback('Search::page_type_lang_callback');
          $searchresults_type->set_content_callback('Search::reset_page_type_defaults');
          $searchresults_type->reset_content_to_factory();
          $searchresults_type->save();
      }
      catch( CmsInvalidDataException $e ) {
          // ignore this error.
      }

      $content = $this->GetTemplate('displayresult');
      if( $content ) {
          $tpl = new CmsLayoutTemplate();
          $tpl->set_originator($me);
          $tpl->set_name('Search Results Sample');
          $tpl->set_owner($uid);
          $tpl->set_content($content);
          $tpl->set_type($searchresults_type);
          $tpl->set_type_dflt(TRUE);
          $tpl->save();
          $this->DeleteTemplate('displayresult');
      }
  }
  catch( CmsException $e ) {
    audit('',$me,'Installation Error: '.$e->GetMessage());
  }
}

if( version_compare($oldversion,'1.51') < 0 ) {
    $tables = [CMS_DB_PREFIX.'module_search_items',CMS_DB_PREFIX.'module_search_index',CMS_DB_PREFIX.'module_search_words'];
    $sql_i = 'ALTER TABLE %s ENGINE=InnoDB';
    foreach( $tables as $table ) {
        $db->Execute(sprintf($sql_i,$table));
    }
}

if( version_compare($oldversion,'1.52') < 0 ) {
    $dict = new DataDictionary($db);
    $sqlarray = $dict->CreateIndexSQL('index_search_item',
            CMS_DB_PREFIX.'module_search_index', 'item_id');
    $dict->ExecuteSQLArray($sqlarray);
}
