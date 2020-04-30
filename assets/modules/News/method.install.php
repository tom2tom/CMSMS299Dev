<?php
/*
News module installation process
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppState;
use CMSMS\Database\DataDictionary;
use News\AdminOperations;

if( !isset($gCms) ) exit;

// best to avoid module-specific class autoloading during installation
if( !class_exists('News\\AdminOperations') ) {
    $fn = cms_join_path(__DIR__,'lib','class.AdminOperations.php');
    require_once $fn;
}

$newsite = AppState::test_state(AppState::STATE_INSTALL);
if( $newsite ) {
    $uid = 1; // templates owned by intitial admin
}
else {
    $uid = get_userid(FALSE);
}

$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

$tbl = CMS_DB_PREFIX.'module_news';
// icon C(255), no longer used
// news_date I, ditto
$flds = '
news_id I(4) UNSIGNED KEY,
news_category_id I(2) UNSIGNED,
news_title C(255),
status C(24),
news_data X(16384),
news_extra C(255),
news_url C(255),
summary X(1024),
start_time I DEFAULT 0,
end_time I DEFAULT 0,
create_date I,
modified_date I DEFAULT 0,
author_id I DEFAULT 0,
searchable I(1) DEFAULT 1
';

$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_daterange', $tbl, 'start_time,end_time');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_author', $tbl, 'author_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_hier', $tbl, 'news_category_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('news_url', $tbl, 'news_url');
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence(CMS_DB_PREFIX.'module_news_seq'); //race-preventer

$flds = '
news_category_id I(2) UNSIGNED AUTO KEY,
news_category_name C(255) NOT NULL,
parent_id I(4),
hierarchy C(255),
item_order I(2) UNSIGNED,
long_name X(1024),
create_date I,
modified_date I DEFAULT 0
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news_categories', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

//$db->CreateSequence(CMS_DB_PREFIX.'module_news_categories_seq'); //race-preventer not really useful here

$now = time();
// General category
$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories
(news_category_name,parent_id,create_date) VALUES (?,?,?)';
$db->Execute($query, [
    'General',
    -1,
    $now,
]);

AdminOperations::UpdateHierarchyPositions();

// Initial news article
$articleid = $db->GenID(CMS_DB_PREFIX.'module_news_seq'); //OR use $db->Insert_ID();
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

// Permissions
$this->CreatePermission('Modify News', 'Modify News Items');
$this->CreatePermission('Approve News', 'Approve News For Display');
$this->CreatePermission('Delete News', 'Delete News Items');
$this->CreatePermission('Modify News Preferences', 'Modify News Module Settings');
// grant them
$perm_id = $db->GetOne('SELECT permission_id FROM '.CMS_DB_PREFIX."permissions WHERE permission_name = 'Modify News'");
$group_id = $db->GetOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Admin'");

$count = $db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND permission_id = ?', [$group_id, $perm_id]);
if ((int)$count == 0) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'group_perms
(group_id,permission_id,create_date) VALUES (?,?,?)';
    $db->Execute($query, [$group_id, $perm_id, $now]);
}

$group_id = $db->GetOne('SELECT group_id FROM '.CMS_DB_PREFIX."groups WHERE group_name = 'Editor'");

$count = $db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND permission_id = ?', [$group_id, $perm_id]);
if ((int)$count == 0) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'group_perms
(group_id,permission_id,create_date) VALUES (?,?,?)';
    $db->Execute($query, [$group_id, $perm_id, $now]);
}

$me = $this->GetName();
// Setup summary templates type
try {
    $type = new CmsLayoutTemplateType();
    $type->set_originator($me);
    $type->set_name('summary');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->set_help_callback('News::template_help_callback');
    $type->reset_content_to_factory();
    $type->save();
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        // log it
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_summary_template.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new CmsLayoutTemplate();
        $tpl->set_originator($me);
        $tpl->set_name('News Summary Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_dflt(TRUE);
        $tpl->save();
    }
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

if( $newsite ) {
    $extras = [];
    try {
        // And Simplex theme sample summary template
        $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'Summary_Simplex_template.tpl';
        if( is_file( $fn ) ) {
            $content = @file_get_contents($fn);
            $tpl = new CmsLayoutTemplate();
            $tpl->set_originator($me);
            $tpl->set_name('Simplex News Summary');
            $tpl->set_owner($uid);
            $tpl->set_content($content);
            $tpl->set_type($type);
            $tpl->save();
            $extras[] = $tpl->get_id();
        }
    }
    catch( Throwable $t ) {
        return $t->getMessage();
    }
}

try {
    // Setup detail templates type
    $type = new CmsLayoutTemplateType();
    $type->set_originator($me);
    $type->set_name('detail');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->reset_content_to_factory();
    $type->set_help_callback('News::template_help_callback');
    $type->save();
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_detail_template.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new CmsLayoutTemplate();
        $tpl->set_originator($me);
        $tpl->set_name('News Detail Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_dflt(TRUE);
        $tpl->save();
    }
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

if( $newsite ) {
    try {
        // And Simplex theme sample detail template
        $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'Simplex_Detail_template.tpl';
        if( is_file( $fn ) ) {
            $content = @file_get_contents($fn);
            $tpl = new CmsLayoutTemplate();
            $tpl->set_originator($me);
            $tpl->set_name('Simplex News Detail');
            $tpl->set_owner($uid);
            $tpl->set_content($content);
            $tpl->set_type($type);
            $tpl->save();
            $extras[] = $tpl->get_id();
        }
    }
    catch( Throwable $t ) {
        return $t->getMessage();
    }

    if( $extras ) {
        try {
            $ob = CmsLayoutTemplateCategory::load('Simplex');
            $ob->add_members($extras);
            $ob->save();
        }
        catch( Throwable $t) {
            //if modules are installed before demo content, that group won't yet exist
        }
    }
}
/*
try {
    // Setup form template type
    $type = new CmsLayoutTemplateType();
    $type->set_originator($me);
    $type->set_name('form');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->reset_content_to_factory();
    $type->set_help_callback('News::template_help_callback');
    $type->save();
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_form_template.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new CmsLayoutTemplate();
        $tpl->set_originator($me);
        $tpl->set_name('News FEsubmit Form Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_dflt(TRUE);
        $tpl->save();
    }
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}
*/
try {
    // Setup browsecat template type
    $type = new CmsLayoutTemplateType();
    $type->set_originator($me);
    $type->set_name('browsecat');
    $type->set_dflt_flag(TRUE);
    $type->set_lang_callback('News::page_type_lang_callback');
    $type->set_content_callback('News::reset_page_type_defaults');
    $type->reset_content_to_factory();
    $type->set_help_callback('News::template_help_callback');
    $type->save();
}
catch( Throwable $t ) {
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

try {
    // And type-default template
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'browsecat.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new CmsLayoutTemplate();
        $tpl->set_originator($me);
        $tpl->set_name('News Browse Category Sample');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_dflt(TRUE);
        $tpl->save();
    }
}
catch( Throwable $t ) {
    // log it
    if( $newsite) {
        return $t->getMessage();
    }
    else {
        debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
        audit('',$me,'Installation error: '.$t->getMessage());
    }
}

// Default email template and email preferences
//$this->SetPreference('email_subject',$this->Lang('subject_newnews'));
//$this->SetTemplate('email_template',$this->GetDfltEmailTemplate());

// Other preferences
//$this->SetPreference('allowed_upload_types','gif,png,jpeg,jpg');
//$this->SetPreference('auto_create_thumbnails','gif,png,jpeg,jpg');
$this->SetPreference('date_format','%Y-%m-%e %H:%M');
$this->SetPreference('default_category',1);
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
