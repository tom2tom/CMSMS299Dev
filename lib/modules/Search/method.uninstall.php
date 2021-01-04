<?php
/*
Search module uninstall procedure
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Database\DataDictionary;
use CMSMS\TemplateType;

if (!isset($gCms)) exit;

$dict = new DataDictionary($db);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_search_index');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_search_items');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_search_words');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence( CMS_DB_PREFIX.'module_search_items_seq' );

$this->DeleteTemplate();
$this->RemovePreference();

$this->RemoveEvent('SearchInitiated');
$this->RemoveEvent('SearchCompleted');
$this->RemoveEvent('SearchItemAdded');
$this->RemoveEvent('SearchItemDeleted');
$this->RemoveEvent('SearchAllItemsDeleted');

$this->RemoveEventHandler( 'Core', 'ContentEditPost');
$this->RemoveEventHandler( 'Core', 'ContentDeletePost');
$this->RemoveEventHandler( 'Core', 'AddTemplatePost');
$this->RemoveEventHandler( 'Core', 'EditTemplatePost');
$this->RemoveEventHandler( 'Core', 'DeleteTemplatePost');
$this->RemoveEventHandler( 'Core', 'ModuleUninstalled');

$this->RemoveSmartyPlugin();

// remove templates
// and template types.
try {
  $types = TemplateType::load_all_by_originator($this->GetName());
  if( $types ) {
    foreach( $types as $type ) {
      $templates = $type->get_template_list();
      if( $templates ) {
        foreach( $templates as $template ) {
          $template->delete();
        }
      }
      $type->delete();
    }
  }
}
catch( Throwable $t ) {
  // log it
  audit('',$this->GetName(),'Uninstall Error: '.$t->GetMessage());
  return $t->GetMessage();
}
