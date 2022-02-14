<?php
/*
News module upgrade process
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use CMSMS\AdminUtils;
use CMSMS\AppState;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use function CMSMS\log_error;

if( empty($this) || !($this instanceof News) ) exit;

$installer_working = AppState::test(AppState::INSTALL);
if( $installer_working ) {
    $uid = 1; // hardcode to first user
}
else {
   if( !$this->CheckPermission('Modify Modules') ) exit;
   $uid = get_userid(FALSE);
}

$dict = $db->NewDataDictionary(); //or new DataDictionary($db);
$me = $this->GetName();

if( version_compare($oldversion,'2.50') < 0 ) {

    $_fix_name = function($str) {
        if( AdminUtils::is_valid_itemname($str) ) return $str;
        $orig = $str;
        $str = trim($str);
        if( !AdminUtils::is_valid_itemname($str[0]) ) $str[0] = '_';
        for( $i = 1; $i < strlen($str); $i++ ) {
            if( !AdminUtils::is_valid_itemname($str[$i]) ) $str[$i] = '_';
        }
        for( $i = 0; $i < 5; $i++ ) {
            $in = $str;
            $str = str_replace('__','_',$str);
            if( $in == $str ) break;
        }
        if( $str == '_' ) throw new Exception('Invalid name '.$orig.' and cannot be corrected');
        return $str;
    };

    // create template types.
    $upgrade_template = function($type,$prefix,$tplname,$currentdflt,$prefix2) use (&$mod,&$_fix_name,$uid) {
        if( !startswith($tplname,$prefix) ) return;
        $contents = $mod->GetTemplate($tplname);
        if( !$contents ) return;
        $prototype = substr($tplname,strlen($prefix));
        $prototype = $_fix_name($prototype);

        try {
            $tpl = new Template();
            $tpl->set_originator($mod->GetName());
            $tpl->set_name(TemplateOperations::get_unique_name($prototype,$prefix2));
            $tpl->set_owner($uid);
            $tpl->set_content($contents);
            $tpl->set_type($type);
            $tpl->set_type_default($prototype == $mod->GetPreference($currentdflt));
            $tpl->save();

            $mod->DeleteTemplate($tplname);
        }
        catch (Throwable $t) {
            // ignore this error
        }
    };

    try {
        $sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'module_news','searchable I1');
        $dict->ExecuteSQLArray($sqlarray);

        $tbl = CMS_DB_PREFIX.'module_news_categories';
        $sqlarray = $dict->AddColumnSQL($tbl,'item_order I1 UNSIGNED DEFAULT 0');
        $dict->ExecuteSQLArray($sqlarray);

        $query = 'SELECT * FROM '.$tbl.' ORDER BY parent_id';
        $categories = $db->getArray($query);

        $uquery = 'UPDATE '.$tbl.' SET item_order = ? WHERE news_category_id = ?';
        if( $categories ) {
            $prev_parent = null;
            $item_order = 0;
            foreach( $categories as $row ) {
                $parent = $row['parent_id'];
                if( $parent != $prev_parent ) $item_order = 0;
                $item_order++;
                $db->execute($uquery,[$item_order,$row['news_category_id']]);
            }
        }

        $mod = $this;
        $alltemplates = $this->ListTemplates();

        try {
            $summary_template_type = new TemplateType();
            $summary_template_type->set_originator($me);
            $summary_template_type->set_name('summary');
            $summary_template_type->set_dflt_flag(TRUE);
            $summary_template_type->set_lang_callback('News::page_type_lang_callback');
            $summary_template_type->set_content_callback('News::reset_page_type_defaults');
            $summary_template_type->reset_content_to_factory();
            $summary_template_type->save();
            foreach( $alltemplates as $tplname ) {
                $upgrade_template($summary_template_type,'summary',$tplname,'current_summary_template','News-Summary-');
            }
        }
        catch (Throwable $t) {
            // ignore this error
        }

        try {
            $detail_template_type = new TemplateType();
            $detail_template_type->set_originator($me);
            $detail_template_type->set_name('detail');
            $detail_template_type->set_dflt_flag(TRUE);
            $detail_template_type->set_lang_callback('News::page_type_lang_callback');
            $detail_template_type->set_content_callback('News::reset_page_type_defaults');
            $detail_template_type->reset_content_to_factory();
            $detail_template_type->save();
            foreach( $alltemplates as $tplname ) {
                $upgrade_template($detail_template_type,'detail',$tplname,'current_detail_template','News-Detail-');
            }
        }
        catch (Throwable $t) {
            // ignore this error
        }
/*
        try {
            $form_template_type = new TemplateType();
            $form_template_type->set_originator($me);
            $form_template_type->set_name('form');
            $form_template_type->set_dflt_flag(TRUE);
            $form_template_type->set_lang_callback('News::page_type_lang_callback');
            $form_template_type->set_content_callback('News::reset_page_type_defaults');
            $form_template_type->reset_content_to_factory();
            $form_template_type->save();
            foreach( $alltemplates as $tplname ) {
                $upgrade_template($form_template_type,'form',$tplname,'current_form_template','News-Form-');
            }
        }
        catch (Throwable $t) {
            // ignore this error
        }
*/
        try {
            $browsecat_template_type = new TemplateType();
            $browsecat_template_type->set_originator($me);
            $browsecat_template_type->set_name('browsecat');
            $browsecat_template_type->set_dflt_flag(TRUE);
            $browsecat_template_type->set_lang_callback('News::page_type_lang_callback');
            $browsecat_template_type->set_content_callback('News::reset_page_type_defaults');
            $browsecat_template_type->reset_content_to_factory();
            $browsecat_template_type->save();
            foreach( $alltemplates as $tplname ) {
                $upgrade_template($browsecat_template_type,'browsecat',$tplname,'current_browsecat_template','News-Browsecat-');
            }
        }
        catch (Throwable $t) {
            // ignore this error
        }
    }
    catch (Throwable $t) {
        log_error($me,'Upgrade Error: '.$t->GetMessage());
        return $t->GetMessage();
    }

    $this->RegisterModulePlugin(TRUE);
    $this->RegisterSmartyPlugin('news','function','function_plugin'); //ibid, with lower-case name
    $this->CreateStaticRoutes();
}

if( version_compare($oldversion,'2.50.8') < 0 ) {
    try {
        $types = TemplateType::load_all_by_originator($me);
        if( $types ) {
            foreach( $types as $type_obj ) {
                $type_obj->set_help_callback('News::template_help_callback');
                $type_obj->save();
            }
        }
    }
    catch (Throwable $t) {
        // log it
        log_error($me,'Uninstall Error: '.$t->GetMessage());
        return $t->GetMessage();
    }
}

if( version_compare($oldversion,'3.1') < 0 ) {
    $this->CreatePermission('Propose News','Create News Items For Approval');
    $this->CreatePermission('Modify News Preferences', 'Modify News Module Settings');
    $this->AddEventHandler('Core','DeleteUserPre'); // support item-ownership changes

    try {
        // Add approval-request notice templates-type
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
        if( $installer_working ) {
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
        if( $installer_working ) {
            return $t->getMessage();
        }
        else {
            debug_to_log(__FILE__.':'.__LINE__.' '.$t->getMessage());
            log_error($me,'Installation error: '.$t->getMessage());
        }
    }

    $this->RemovePreference('allowed_upload_types');
    $this->RemovePreference('auto_create_thumbnails');
    $this->SetPreference('email_template','Article Approval-Request Email'); // notice-body generator
    $this->SetPreference('email_to','');

    $fmt = $this->GetPreference('date_format');
    if( $fmt ) {
        //ensure time is normally included in datevar display
        if( strpos($fmt, '%') !== false ) {
            if( !preg_match('/%[HIklMpPrRSTXzZ]/', $fmt) ) {
                if( strpos($fmt, '-') !== false || strpos($fmt, '/') !== false ) {
                    $fmt .= ' %k:%M';
                }
                else {
                    $fmt .= ' %l:%M %P';
                }
                $this->SetPreference('date_format', $fmt);
            }
        }
        elseif( !preg_match('/(?<!\\\\)[aABgGhHisuv]/', $fmt) ) {
            if( strpos($fmt, '-') !== false || strpos($fmt, '/') !== false ) {
                $fmt .= ' H:i';
            }
            else {
                $fmt .= ' g:i a';
            }
            $this->SetPreference('date_format', $fmt);
        }
    }
    else {
        $this->SetPreference('date_format', '%e %B %Y %l:%M %p');
    }

    foreach( [
     ['alert_drafts',1],
     ['allow_summary_wysiwyg',1],
     ['article_pagelimit',10],
     ['clear_category',0],
     ['current_detail_template',''], // TODO never changed Intended for the preferred preview-template (whose type is 'News::detail')
     ['default_category',1],
     ['detail_returnid',-1],
     ['expired_searchable',1],
     ['expired_viewable',0],
     ['expiry_interval',30],
     ['timeblock',News::HOURBLOCK],
    ] as $row ) {
        $val = $this->GetPreference($row[0],-22);
        if ($val === -22) {
            $this->SetPreference($row[0],$row[1]);
        }
    }

    $up = cms_join_path($config['uploads_path'],$me);
    $fp = str_replace($me,'news',$up);
    if( is_dir($fp) ) {
        rename($fp,$up);
    }
    elseif( !is_dir($up) ) {
        @mkdir($up,0771,true);
    }

    $longnow = $db->DbTimeStamp(time(),false);

    $tbl = CMS_DB_PREFIX.'module_news';
    $query = 'UPDATE '.$tbl.' SET author_id=0 WHERE author_id<0';
    $db->execute($query);
    //TODO better default date-time
    //e.g. $when = $db->getOne("SELECT create_time FROM information_schema.tables WHERE table_schema = '{$db->name}' AND table_name='$tbl'");
    $query = 'UPDATE '.$tbl.' SET create_date=\'2010-1-1 12:00:00\' WHERE create_date IS NULL';
    $db->execute($query);
    $query = 'UPDATE '.$tbl.' SET modified_date=NULL WHERE modified_date<=create_date';
    $db->execute($query);
    $query = 'UPDATE '.$tbl.' SET status=\'archived\' WHERE status=\'published\' AND end_time IS NOT NULL AND end_time<=?';
    $db->execute($query,[$longnow]);
    $query = 'UPDATE '.$tbl.' SET start_time=MAX(news_date,modified_date,create_date) WHERE start_time IS NULL AND status!=\'draft\'';
    $db->execute($query);
    $query = 'UPDATE '.$tbl.' SET searchable=1 WHERE searchable IS NULL';
    $db->execute($query);

    $sqlarray = $dict->DropColumnSQL($tbl,'news_date'); // OR alter table news_date DROP, if that works !!
    $dict->ExecuteSqlArray($sqlarray);
    $sqlarray = $dict->RenameColumnSQL($tbl,'icon','image_url','C(255) CHARACTER SET ascii COLLATE ascii_general_ci'); // ditto
    $dict->ExecuteSqlArray($sqlarray);
    $sqlarray = $dict->ChangeTableSQL($tbl,
'news_id I UNSIGNED,
news_category_id I UNSIGNED,
status C(25) CHARACTER SET ascii COLLATE ascii_bin,
news_data X(65535),
summary C(1000),
news_url C(255) CHARACTER SET ascii COLLATE ascii_general_ci,
create_date DT NOT NULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
author_id I UNSIGNED DEFAULT 0,
searchable I1 UNSIGNED DEFAULT 1
',
    'CHARACTER SET utf8mb4');
    $dict->ExecuteSqlArray($sqlarray);

    // migrate frontend-submitted (hence field-stored) images to corresponding main table::image_url if latter N/A
    // field-types: 'file' i.e. uploaded file (maybe image), 'linkedfile' i.e. external link (maybe image) ignored
    // the recorded value is file basename, stored at $config['uploads_path']/news/'id'.$itemid/$filename
    // see: former AdminOperations::handle_upload()
    $pref = CMS_DB_PREFIX;
    $sql = <<<EOS
SELECT FV.news_id,FV.value
FROM {$pref}module_news_fieldvals FV
INNER JOIN {$pref}module_news_fielddefs FD
ON FV.fielddef_id = FD.id
WHERE FD.type = 'file'
EOS;
    $data = $db->getArray($sql);
    if( $data ) {
        $sql = 'UPDATE '.$tbl.' SET image_url=? WHERE news_id=? AND (image_url IS NULL OR image_url=\'\')';
        foreach( $data as $row ) {
            $fp = cms_join_path($up,'id'.$row['news_id'],$row['value']);
            if( is_file($fp) && 1 ) { // TODO && is image
                $url = $me.'/id'.$row['news_id'].'/'.$row['value'];
                $db->execute($sql,[$url,$row['news_id']]);
            }
        }
    }
    $sql = <<<EOS
SELECT FV.news_id,FV.value
FROM {$pref}module_news_fieldvals FV
INNER JOIN {$pref}module_news_fielddefs FD
ON FV.fielddef_id = FD.id
WHERE FD.type = 'linkedfile'
EOS;
    $data = $db->getArray($sql);
    if( $data ) {
        $tmpl = '<br /><br/>See also: <a href="%s" ...>this related information</a>.'; // TODO translated
        $sql = 'UPDATE '.$tbl.' SET news_data=CONCAT(news_data,?) WHERE news_id=?';
        foreach( $data as $row ) {
            if (0) { // is acceptable link
                $val = sprintf($tmpl,$row['value']);
                $db->execute($sql,[$val,$row['news_id']]);
            }
        }
    }

    $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_news_fielddefs');
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_news_fieldvals');
    $dict->ExecuteSQLArray($sqlarray);

    $tbl = CMS_DB_PREFIX.'module_news_categories';
    //TODO better default date-time
    //e.g. $when = $db->getOne("SELECT create_time FROM information_schema.tables WHERE table_schema = '{$db->name}' AND table_name='$tbl'");
    $query = 'UPDATE '.$tbl.' SET create_date=\'2010-1-1 12:00:00\' WHERE create_date IS NULL';
    $db->execute($query);

    $sqlarray = $dict->ChangeTableSQL($tbl,
'news_category_id I UNSIGNED AUTO,
news_category_name C(60) NOTNULL CHARACTER SET utf8mb4,
hierarchy C(255) COLLATE ascii_bin,
item_order I1 UNSIGNED DEFAULT 0,
long_name C(630) CHARACTER SET utf8mb4,
category_url C(255),
image_url C(255),
create_date DT NOTNULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
',
    'CHARACTER SET ascii');
    $dict->ExecuteSqlArray($sqlarray);

    $query = 'UPDATE '.$tbl.' SET modified_date=NULL WHERE modified_date<=create_date';
    $db->execute($query);

    $db->DropSequence(CMS_DB_PREFIX.'module_news_categories_seq');

    $query = 'DELETE FROM '.CMS_DB_PREFIX.'layout_templates WHERE type_id=(SELECT id FROM '.CMS_DB_PREFIX."layout_tpl_types WHERE originator='$me' AND name='form')";
    $db->execute($query);
    $query = 'DELETE FROM '.CMS_DB_PREFIX."layout_tpl_types WHERE originator='$me' AND name='form'";
    $db->execute($query);
}
