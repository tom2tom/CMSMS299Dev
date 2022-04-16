<?php
/*
News module installation process
Copyright (C) 2005-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\Database\DataDictionary;
use CMSMS\AdminAlerts\TranslatableAlert;
use CMSMS\AppState;
use CMSMS\Template;
use CMSMS\TemplateType;
use News\AdminOperations;
use function CMSMS\log_error;

if( empty($this) || !($this instanceof News) ) exit;

$installer_working = AppState::test(AppState::INSTALL);
if( $installer_working && 1 ) { // TODO && this is a new demo-site
    $newsite = TRUE;
    $uid = 1; // templates owned by intitial admin
}
else {
    if( !$this->CheckPermission('Modify Modules') ) exit;
    $newsite = FALSE;
    $uid = get_userid(FALSE);
}

// best to avoid module-specific class autoloading during installation
if( !class_exists('News\AdminOperations') ) {
    $fn = cms_join_path(__DIR__,'lib','class.AdminOperations.php');
    require_once $fn;
}

$me = $this->GetName();
$dict = $db->NewDataDictionary();  // OR new DataDictionary($db);

$taboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET utf8mb4'];
$tbl = CMS_DB_PREFIX.'module_news';
// news_date I no longer used
// image_url replaces 'icon' used in ancient versions
$flds = '
news_id I UNSIGNED KEY,
news_category_id I UNSIGNED,
news_title C(255),
status C(25) CHARACTER SET ascii COLLATE ascii_bin,
news_data X(65535),
news_extra C(255),
summary C(1000),
news_url C(255) CHARACTER SET ascii COLLATE ascii_general_ci,
image_url C(255) CHARACTER SET ascii COLLATE ascii_general_ci,
start_time DT,
end_time DT,
create_date DT NOTNULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
author_id I UNSIGNED DEFAULT 0,
searchable I1 UNSIGNED DEFAULT 1
';

$sqlarray = $dict->CreateTableSQL($tbl,$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_starttime_endtime',$tbl,'start_time,end_time');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_authorid',$tbl,'author_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_newscategoryid',$tbl,'news_category_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_newsurl',$tbl,'news_url');
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence(CMS_DB_PREFIX.'module_news_seq'); //race-preventer

//news_category_name for internal use (e.g. db checks) plus public display
//parent_id may be -1 so a signed-int field for that
//long_name akin to id-hierarchy for content pages, cat names separated by ' | ', 630 = name(60) * 10 levels
//category_url alias/slug intended for pretty-urls c.f. content pages
//TODO news_category_name UNIQUE ? OR long_name ?
$taboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET ascii'];
$flds = '
news_category_id I UNSIGNED AUTO KEY,
news_category_name C(60) NOTNULL CHARACTER SET utf8mb4,
parent_id I,
item_order I1 UNSIGNED DEFAULT 0,
hierarchy C(255) COLLATE ascii_bin,
long_name C(630) CHARACTER SET utf8mb4,
category_url C(255),
image_url C(255),
create_date DT NOTNULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';

$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_news_categories',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

//$db->CreateSequence(CMS_DB_PREFIX.'module_news_categories_seq'); //race-preventer not really useful here
$longnow = $db->DbTimeStamp(time(),FALSE);

// General category
$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories
(news_category_name,parent_id,item_order,category_url,create_date) VALUES (?,?,?,?,?)';
$db->execute($query,[
    'General', // TODO langify
    -1,
    1,
    'general',
    $longnow,
]);
$catid = $db->Insert_ID(); // aka 1

AdminOperations::UpdateHierarchyPositions();

if( $installer_working && 1 ) { // TODO && this is a new demo-site OR generate this stuff via the new-site demo content XML

    // Initial (demo) news article NOTE no news_url value recorded here
    // Route registration code expects this module to be installed already
    // and the site default page id to be available
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news
(news_id,
news_category_id,
news_title,
news_data,
status,
summary,
image_url,
start_time,
create_date,
author_id)
VALUES (?,?,?,?,?,?,?,?,?,?)';
    $articleid = $db->genID(CMS_DB_PREFIX.'module_news_seq');
    $db->execute($query,[
        $articleid,
        $catid,
        'Look at my cool new site!',
        '<p>Praesent ultrices hendrerit euismod. Donec sit amet pharetra tellus. Sed rutrum est eu nulla pretium, sed maximus lacus posuere. Vivamus et turpis velit. Duis dignissim vitae arcu vitae viverra. Integer congue sem nec elit suscipit tincidunt. Fusce ac vestibulum neque, at sodales ante. Nunc in cursus diam. Ut quis nisl neque. Pellentesque blandit sem efficitur sem molestie, et tincidunt justo sollicitudin.</p>',
        'published',
        'This is the first news-item',
        $me.'/demo-image.svg',
        $longnow,
        $longnow,
        1,
    ]);

} // newsite-installer_working

// Permissions
$this->CreatePermission('Approve News','Approve News Items For Display');
$this->CreatePermission('Modify News','Modify News Items');
$this->CreatePermission('Propose News','Create News Items For Approval');
$this->CreatePermission('Delete News','Delete News Items');
$this->CreatePermission('Modify News Preferences','Modify News Module Settings');
// grant them
$perm_id = $db->getOne('SELECT id FROM '.CMS_DB_PREFIX."permissions WHERE name = 'Modify News'");
$group_id = $db->getOne('SELECT group_id FROM `'.CMS_DB_PREFIX."groups` WHERE group_name = 'Admin'");

$count = $db->getOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND permission_id = ?',[$group_id,$perm_id]);
if ((int)$count == 0) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'group_perms (group_id,permission_id,create_date) VALUES (?,?,?)';
    $db->execute($query,[$group_id,$perm_id,$longnow]);
}

$group_id = $db->getOne('SELECT group_id FROM `'.CMS_DB_PREFIX."groups` WHERE group_name = 'Editor'");

$count = $db->getOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND permission_id = ?',[$group_id,$perm_id]);
if ((int)$count == 0) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'group_perms (group_id,permission_id,create_date) VALUES (?,?,?)';
    $db->execute($query,[$group_id,$perm_id,$longnow]);
}

// Setup summary templates-type
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
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'summary_template.tpl';
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
    // Setup detail templates-type
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
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'detail_template.tpl';
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
        //TODO get tpl's id, for use in default detail page added below, and SetPreference(detail_returnid)
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
    // And browsecat templates-type
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

try {
    // And approval-request notice templates-type
    $type = new TemplateType();
    $type->set_originator($me);
    $type->set_name('approvalmessage');
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
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'approval_email.tpl';
    if( is_file( $fn ) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name('Article Approval-Request Email');
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

//if( $installer_working && 1 ) { // TODO && this is a new demo-site OR generate this stuff via the new-site demo content XML
    //TODO add a default/dummy news-detail page with tpl derived from orig_detail_template.
//}

// Other preferences
$this->SetPreference('alert_drafts',1);
$this->SetPreference('allow_summary_wysiwyg',1);
$this->SetPreference('article_pagelimit',10); //default 10 articles per displayed (expandable) page
$this->SetPreference('clear_category',0); //don't delete articles in category when category is deleted
$this->SetPreference('current_detail_template',''); //no preferred 'News::detail'-type template for news-item previews (TODO never changed)
$this->SetPreference('date_format','%e %B %Y %l:%M %p');
$this->SetPreference('default_category',1);
$this->SetPreference('detail_returnid',-1); //no default post-detail page
$this->SetPreference('email_subject',$this->Lang('subject_newnews'));
$this->SetPreference('email_template','Article Approval-Request Email'); // notice-body generator
$this->SetPreference('email_to','');
$this->SetPreference('expired_searchable',1);
$this->SetPreference('expired_viewable',0);
$this->SetPreference('expiry_interval',30); //default 30-days lifetime
$this->SetPreference('timeblock',News::HOURBLOCK);

// Events
$this->CreateEvent('NewsArticleAdded');
$this->CreateEvent('NewsArticleEdited');
$this->CreateEvent('NewsArticleDeleted');
$this->CreateEvent('NewsCategoryAdded');
$this->CreateEvent('NewsCategoryEdited');
$this->CreateEvent('NewsCategoryDeleted');
$this->AddEventHandler('Core','DeleteUserPre'); // support item-ownership changes

$this->RegisterModulePlugin(TRUE);
//$this->RegisterSmartyPlugin('news','function','function_plugin'); //ibid, with lower-case name

// and routes
$this->CreateStaticRoutes();

// and uploads
$fn = $config['uploads_path'];
if( $fn && is_dir($fn) ) {
    $fn .= DIRECTORY_SEPARATOR.$me;
    if( !is_dir($fn) ) {
        @mkdir($fn,0771,TRUE);
    }
    if( $newsite ) {
        $t = __DIR__.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'demo-image.svg';
        copy($t,$fn.DIRECTORY_SEPARATOR.'demo-image.svg');
    }
}

if( $installer_working && 1 ) {
    $alert = new TranslatableAlert('Modify Site Preferences');
    $alert->name = 'News Setup Needed';
    $alert->module = $me;
    $alert->titlekey = 'postinstall_title';
    $alert->msgkey = 'postinstall_notice';
    $alert->save();
}
