<?php
/*
News module installation process
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

//use CMSMS\Database\DataDictionary;
use CMSMS\AppState;
use CMSMS\Template;
use CMSMS\TemplateType;
use News\AdminOperations;
use function CMSMS\log_error;

if( !isset($gCms) ) exit;

// best to avoid module-specific class autoloading during installation
if( !class_exists('News\AdminOperations') ) {
    $fn = cms_join_path(__DIR__,'lib','class.AdminOperations.php');
    require_once $fn;
}

$installer_working = AppState::test(AppState::INSTALL);
if( $installer_working && 1 ) { // TODO && this is a new demo-site
    $newsite = true;
    $uid = 1; // templates owned by intitial admin
}
else {
    $newsite = false;
    $uid = get_userid(FALSE);
}

$dict = $db->NewDataDictionary();  // OR new DataDictionary($db);

$taboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET utf8mb4'];
$tbl = CMS_DB_PREFIX.'module_news';
// news_date I no longer used
// alias intended for pretty-urls c.f. content pages OR will news_url handle that?
// image_url replaces the icon used in ancient versions
$flds = '
news_id I UNSIGNED KEY,
news_category_id I UNSIGNED,
news_title C(255),
status C(25),
news_data X(65535),
news_extra C(255),
news_url C(255),
alias C(255),
summary C(1000),
image_url C(255),
start_time DT,
end_time DT,
create_date DT NOTNULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
author_id I UNSIGNED DEFAULT 0,
searchable I1 DEFAULT 1
';

$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_starttime_endtime', $tbl, 'start_time,end_time');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_authorid', $tbl, 'author_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_newscategoryid', $tbl, 'news_category_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_newsurl', $tbl, 'news_url');
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence(CMS_DB_PREFIX.'module_news_seq'); //race-preventer

//parent_id may be -1 so a signed-int field for that
// alias intended for pretty-urls c.f. content pages
//TODO category_url instead of alias ?
$taboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET ascii'];
$flds = '
news_category_id I UNSIGNED AUTO KEY,
news_category_name C(255) CHARACTER SET utf8mb4,
parent_id I,
hierarchy C(255) COLLATE ascii_bin,
item_order I1 UNSIGNED DEFAULT 0,
long_name C(1000) CHARACTER SET utf8mb4,
alias C(255),
image_url C(255),
create_date DT NOTNULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news_categories', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

//$db->CreateSequence(CMS_DB_PREFIX.'module_news_categories_seq'); //race-preventer not really useful here
$longnow = $db->DbTimeStamp(time(),false);

if( $installer_working && 1 ) { // TODO && this is a new demo-site OR generate this stuff via the new-site demo content XML

// General category
$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories
(news_category_name,parent_id,create_date) VALUES (?,?,?)';
$db->execute($query, [
    'General',
    -1,
    $longnow,
]);
$catid = $db->Insert_ID(); // aka 1

AdminOperations::UpdateHierarchyPositions();

// Initial (demo) news article
$articleid = $db->genID(CMS_DB_PREFIX.'module_news_seq'); //OR use $db->Insert_ID();
$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news
(news_id,
news_category_id,
author_id,
news_title,
news_data,
status,
start_time,
create_date)
VALUES (?,?,?,?,?,?,?,?)';
$db->execute($query, [
$articleid,
$catid,
1,
'News Module Installed',
'The news module was installed. Exciting. This news article has no Summary field and so there is no link to read more. However you can click on the article heading to read only this article.',
'published',
$longnow,
$longnow,
]);

} // newsite-installer_working

// Permissions
$this->CreatePermission('Modify News', 'Modify News Items');
$this->CreatePermission('Approve News', 'Approve News For Display');
$this->CreatePermission('Delete News', 'Delete News Items');
$this->CreatePermission('Modify News Preferences', 'Modify News Module Settings');
// grant them
$perm_id = $db->getOne('SELECT id FROM '.CMS_DB_PREFIX."permissions WHERE name = 'Modify News'");
$group_id = $db->getOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Admin'");

$count = $db->getOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND permission_id = ?',[$group_id,$perm_id]);
if ((int)$count == 0) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'group_perms (group_id,permission_id,create_date) VALUES (?,?,?)';
    $db->execute($query,[$group_id,$perm_id,$longnow]);
}

$group_id = $db->getOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Editor'");

$count = $db->getOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND permission_id = ?',[$group_id,$perm_id]);
if ((int)$count == 0) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'group_perms (group_id,permission_id,create_date) VALUES (?,?,?)';
    $db->execute($query,[$group_id,$perm_id,$longnow]);
}

$me = $this->GetName();
// Setup summary templates type
try {
    $type = new TemplateType();
    $type->set_originator($me);
    $type->set_name('summary');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->set_help_callback('News::template_help_callback');
    $type->reset_content_to_factory();
    $type->save();
}
catch (Throwable $t) {
    if( $newsite ) {
        return $t->getMessage();
    }
    else {
        // log it
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        log_error($me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_summary_template.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name('News Summary Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_default(TRUE);
        $tpl->save();
    }
}
catch (Throwable $t) {
    if( $newsite ) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        log_error($me,'Installation error: '.$t->getMessage());
    }
}

try {
    // Setup detail templates type
    $type = new TemplateType();
    $type->set_originator($me);
    $type->set_name('detail');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->reset_content_to_factory();
    $type->set_help_callback('News::template_help_callback');
    $type->save();
}
catch (Throwable $t) {
    if( $newsite ) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        log_error($me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_detail_template.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name('News Detail Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_default(TRUE);
        $tpl->save();
    }
}
catch (Throwable $t) {
    if( $newsite ) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        log_error($me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And browsecat templates type
    $type = new TemplateType();
    $type->set_originator($me);
    $type->set_name('browsecat');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->reset_content_to_factory();
    $type->set_help_callback('News::template_help_callback');
    $type->save();
}
catch (Throwable $t) {
    if( $newsite ) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        log_error($me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'browsecat.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name('News Browse Category Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_default(TRUE);
        $tpl->save();
    }
}
catch (Throwable $t) {
    if( $newsite ) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        log_error($me,'Installation error: '.$t->getMessage());
    }
}

// Other preferences
//$this->SetPreference('allowed_upload_types','gif,png,jpeg,jpg');
//$this->SetPreference('auto_create_thumbnails','gif,png,jpeg,jpg');
$this->SetPreference('date_format','Y-m-d');
$this->SetPreference('default_category',1);
$this->SetPreference('time_format','H:i');
$this->SetPreference('timeblock',News::HOURBLOCK);

// Events
$this->CreateEvent('NewsArticleAdded');
$this->CreateEvent('NewsArticleEdited');
$this->CreateEvent('NewsArticleDeleted');
$this->CreateEvent('NewsCategoryAdded');
$this->CreateEvent('NewsCategoryEdited');
$this->CreateEvent('NewsCategoryDeleted');

$this->RegisterModulePlugin(TRUE);
//$this->RegisterSmartyPlugin('news', 'function', 'function_plugin'); //ibid, with lower-case name

// and routes
$this->CreateStaticRoutes();

// and uploads
$fn = $config['uploads_path'];
if( $fn && is_dir($fn) ) {
    $fn .= DIRECTORY_SEPARATOR.$me;
    if( !is_dir($fn) ) {
        @mkdir($fn, 0771, TRUE);
    }
}
