<?php

use CMSMS\Database\DataDictionary;
use News\Adminops;

if( !isset($gCms) ) exit;

//best to avoid module-specific class autoloading during installation
if( !class_exists('Adminops') ) {
  $fn = cms_join_path(__DIR__,'lib','class.Adminops.php');
  require_once($fn);
}

$newsite = $gCms->test_state(CmsApp::STATE_INSTALL);
if( $newsite ) {
  $uid = 1; // templates owned by intitial admin
} else {
  $uid = get_userid();
}

$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

// icon C(255), no longer used
// news_date I, ditto
$flds = '
news_id I(4) KEY,
news_category_id I(4),
news_title C(255),
news_data X(16384),
summary X(1024),
status C(25),
searchable I(1) DEFAULT 1,
start_time I DEFAULT 0,
end_time I DEFAULT 0,
create_date I,
modified_date I DEFAULT 0,
author_id I DEFAULT 0,
news_extra C(255),
news_url C(255)
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);
$db->CreateSequence(CMS_DB_PREFIX.'module_news_seq');

$flds = '
news_category_id I(4) KEY,
news_category_name C(255) NOTNULL,
parent_id I(4),
hierarchy C(255),
item_order I(4),
long_name X(1024),
create_date I,
modified_date I DEFAULT 0
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news_categories', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);
$db->CreateSequence(CMS_DB_PREFIX.'module_news_categories_seq');

$flds = '
id I(4) KEY AUTO,
name C(255),
type C(50),
max_length I(4),
create_date I,
modified_date I DEFAULT 0,
item_order I(4),
public I(1),
extra x(1024)
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news_fielddefs', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
news_id I(4) KEY NOT NULL,
fielddef_id I(4) KEY NOT NULL,
value X(16384),
create_date I,
modified_date I DEFAULT 0
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news_fieldvals', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

// Set Permissions
$this->CreatePermission('Modify News', 'Modify News');
$this->CreatePermission('Approve News', 'Approve News For Frontend Display');
$this->CreatePermission('Delete News', 'Delete News Articles');
$this->CreatePermission('Modify News Preferences', 'Modify News Module Settings');

$me = $this->GetName();
// Setup summary templates
try {
  $summary_template_type = new CmsLayoutTemplateType();
  $summary_template_type->set_originator($me);
  $summary_template_type->set_name('summary');
  $summary_template_type->set_dflt_flag(TRUE);
  $summary_template_type->set_lang_callback('News::page_type_lang_callback');
  $summary_template_type->set_content_callback('News::reset_page_type_defaults');
  $summary_template_type->set_help_callback('News::template_help_callback');
  $summary_template_type->reset_content_to_factory();
  $summary_template_type->save();
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

try {
  $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_summary_template.tpl';
  if( is_file( $fn ) ) {
    $content = @file_get_contents($fn);
    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('News Summary Sample');
    $tpl->set_owner($uid);
    $tpl->set_content($content);
    $tpl->set_type($summary_template_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();
  }
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

if( $newsite ) {
  try {
    // Simplex theme sample summary template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'Summary_Simplex_template.tpl';
    if( is_file( $fn ) ) {
    $content = @file_get_contents($fn);
    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('Simplex News Summary');
    $tpl->set_owner($uid);
    $tpl->set_content($content);
    $tpl->set_type($summary_template_type);
    $tpl->add_design('Simplex');
    $tpl->save();
    }
  } catch( CmsException $e ) {
    // log it
    debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
    audit('',$me,'Installation Error: '.$e->GetMessage());
  }
}

try {
  // Setup detail templates
  $detail_template_type = new CmsLayoutTemplateType();
  $detail_template_type->set_originator($me);
  $detail_template_type->set_name('detail');
  $detail_template_type->set_dflt_flag(TRUE);
  $detail_template_type->set_lang_callback('News::page_type_lang_callback');
  $detail_template_type->set_content_callback('News::reset_page_type_defaults');
  $detail_template_type->reset_content_to_factory();
  $detail_template_type->set_help_callback('News::template_help_callback');
  $detail_template_type->save();
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

try {
  $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_detail_template.tpl';
  if( is_file( $fn ) ) {
    $content = @file_get_contents($fn);
    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('News Detail Sample');
    $tpl->set_owner($uid);
    $tpl->set_content($content);
    $tpl->set_type($detail_template_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();
  }
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

if( $newsite ) {
  try {
    // Simplex Theme sample detail template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'Simplex_Detail_template.tpl';
    if( is_file( $fn ) ) {
    $content = @file_get_contents($fn);
    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('Simplex News Detail');
    $tpl->set_owner($uid);
    $tpl->set_content($content);
    $tpl->set_type($detail_template_type);
    $tpl->add_design('Simplex');
    $tpl->save();
    }
  } catch( CmsException $e ) {
    // log it
    debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
    audit('',$me,'Installation Error: '.$e->GetMessage());
  }
}

try {
  // Setup form template
  $form_template_type = new CmsLayoutTemplateType();
  $form_template_type->set_originator($me);
  $form_template_type->set_name('form');
  $form_template_type->set_dflt_flag(TRUE);
  $form_template_type->set_lang_callback('News::page_type_lang_callback');
  $form_template_type->set_content_callback('News::reset_page_type_defaults');
  $form_template_type->reset_content_to_factory();
  $form_template_type->set_help_callback('News::template_help_callback');
  $form_template_type->save();
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

try {
  $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_form_template.tpl';
  if( is_file( $fn ) ) {
    $content = @file_get_contents($fn);
    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('News Fesubmit Form Sample');
    $tpl->set_owner($uid);
    $tpl->set_content($content);
    $tpl->set_type($form_template_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();
  }
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

try {
  // Setup browsecat template
  $browsecat_template_type = new CmsLayoutTemplateType();
  $browsecat_template_type->set_originator($me);
  $browsecat_template_type->set_name('browsecat');
  $browsecat_template_type->set_dflt_flag(TRUE);
  $browsecat_template_type->set_lang_callback('News::page_type_lang_callback');
  $browsecat_template_type->set_content_callback('News::reset_page_type_defaults');
  $browsecat_template_type->reset_content_to_factory();
  $browsecat_template_type->set_help_callback('News::template_help_callback');
  $browsecat_template_type->save();
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

try {
  $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'browsecat.tpl';
  if( is_file( $fn ) ) {
    $content = @file_get_contents($fn);
    $tpl = new CmsLayoutTemplate();
    $tpl->set_originator($me);
    $tpl->set_name('News Browse Category Sample');
    $tpl->set_owner($uid);
    $tpl->set_content($content);
    $tpl->set_type($browsecat_template_type);
    $tpl->set_type_dflt(TRUE);
    $tpl->save();
  }
} catch( CmsException $e ) {
  // log it
  debug_to_log(__FILE__.':'.__LINE__.' '.$e->GetMessage());
  audit('',$me,'Installation Error: '.$e->GetMessage());
}

// Default email template and email preferences
$this->SetPreference('email_subject',$this->Lang('subject_newnews'));
$this->SetTemplate('email_template',$this->GetDfltEmailTemplate());

// Other preferences
$this->SetPreference('allowed_upload_types','gif,png,jpeg,jpg');
$this->SetPreference('auto_create_thumbnails','gif,png,jpeg,jpg');
$this->SetPreference('timeblock',News::HOURBLOCK);

$now = time();
// General category
$catid = $db->GenID(CMS_DB_PREFIX.'module_news_categories_seq');
$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories (news_category_id, news_category_name, parent_id, create_date, modified_date) VALUES (?,?,?,?,?)';
$db->Execute($query, [
$catid,
'General',
-1,
$now,
$now,
]);

// Initial news article
$articleid = $db->GenID(CMS_DB_PREFIX.'module_news_seq');
$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news (news_id,news_category_id,author_id,news_title,news_data,status,start_time,create_date) VALUES (?,?,?,?,?,?,?,?)';
$db->Execute($query, [
$articleid,
$catid,
1,
'News Module Installed',
'The news module was installed. Exciting. This news article has no Summary field and so there is no link to read more. But you can click on the news heading to read only this article.',
'published',
$now,
$now,
]);

Adminops::UpdateHierarchyPositions();

// Permissions
$perm_id = $db->GetOne('SELECT permission_id FROM '.CMS_DB_PREFIX."permissions WHERE permission_name = 'Modify News'");
$group_id = $db->GetOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Admin'");

$count = $db->GetOne('SELECT COUNT(*) FROM ' . CMS_DB_PREFIX . 'group_perms WHERE group_id = ? AND permission_id = ?', [$group_id, $perm_id]);
if (isset($count) && (int)$count == 0) {
  $new_id = $db->GenID(CMS_DB_PREFIX.'group_perms_seq');
  $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'group_perms (group_perm_id, group_id, permission_id, create_date, modified_date) VALUES ('.$new_id.', '.$group_id.', '.$perm_id.', '. $now . ', ' . $now . ')';
  $db->Execute($query);
}

$group_id = $db->GetOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Editor'");

$count = $db->GetOne('SELECT COUNT(*) FROM ' . CMS_DB_PREFIX . 'group_perms WHERE group_id = ? AND permission_id = ?', [$group_id, $perm_id]);
if (isset($count) && (int)$count == 0) {
  $new_id = $db->GenID(CMS_DB_PREFIX.'group_perms_seq');
  $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'group_perms (group_perm_id, group_id, permission_id, create_date, modified_date) VALUES ('.$new_id.', '.$group_id.', '.$perm_id.', '. $now . ', ' . $now . ')';
  $db->Execute($query);
}

// Indices
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_daterange',
          CMS_DB_PREFIX.'module_news', 'start_time,end_time');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_author',
          CMS_DB_PREFIX.'module_news', 'author_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_hier',
          CMS_DB_PREFIX.'module_news', 'news_category_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_url',
          CMS_DB_PREFIX.'module_news', 'news_url');
$dict->ExecuteSQLArray($sqlarray);
/* useless replication of news_daterange index
$sqlarray = $dict->CreateIndexSQL('news_startenddate',
          CMS_DB_PREFIX.'module_news', 'start_time,end_time');
$dict->ExecuteSQLArray($sqlarray);
*/
// Events
$this->CreateEvent('NewsArticleAdded');
$this->CreateEvent('NewsArticleEdited');
$this->CreateEvent('NewsArticleDeleted');
$this->CreateEvent('NewsCategoryAdded');
$this->CreateEvent('NewsCategoryEdited');
$this->CreateEvent('NewsCategoryDeleted');

$this->RegisterModulePlugin(TRUE);
$this->RegisterSmartyPlugin('news', 'function', 'function_plugin'); //ibid, with lower-case name

// and routes...
$this->CreateStaticRoutes();
