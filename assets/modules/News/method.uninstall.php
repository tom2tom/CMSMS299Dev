<?php
use CMSMS\Database\DataDictionary;

if (!isset($gCms)) exit;

$dict = new DataDictionary($db);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news' );
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news_categories' );
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news_fielddefs' );
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news_fieldvals' );
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence( CMS_DB_PREFIX.'module_news_seq' );
$db->DropSequence( CMS_DB_PREFIX.'module_news_categories_seq' );

$this->RemovePermission('Modify News');
$this->RemovePermission('Approve News');
$this->RemovePermission('Delete News');
$this->RemovePermission('Modify News Preferences');

// Remove all preferences for this module
$this->RemovePreference();

// And events
$this->RemoveEvent('NewsArticleAdded');
$this->RemoveEvent('NewsArticleEdited');
$this->RemoveEvent('NewsArticleDeleted');
$this->RemoveEvent('NewsCategoryAdded');
$this->RemoveEvent('NewsCategoryEdited');
$this->RemoveEvent('NewsCategoryDeleted');

$this->RemoveSmartyPlugin();

cms_route_manager::del_static('',$this->GetName());

// Remove templates and template types
$this->DeleteTemplate();
//$this->DeleteTemplate('displaysummary');
//$this->DeleteTemplate('displaydetail');

try {
  $types = CmsLayoutTemplateType::load_all_by_originator($this->GetName());
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
catch( Exception $e ) {
  // log it
  audit('',$this->GetName(),'Uninstall Error: '.$e->GetMessage());
}
