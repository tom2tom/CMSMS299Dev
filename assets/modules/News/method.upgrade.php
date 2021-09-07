<?php
/*
News module upgrade process
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
//use CMSMS\SingleItem;
use CMSMS\AdminUtils;
use CMSMS\AppState;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;

if (!isset($gCms)) exit;
//$db = SingleItem::Db(); upstream
$dict = $db->NewDataDictionary(); //or new DataDictionary($db);
$me = $this->GetName();

if( version_compare($oldversion,'2.50') < 0 ) {
    $installer_working = AppState::test(AppState::INSTALL);
    if( $installer_working ) {
        $uid = 1; // hardcode to first user
    }
    else {
        $uid = get_userid(FALSE);
    }

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
            $tpl->set_type_dflt($prototype == $mod->GetPreference($currentdflt));
            $tpl->save();

            $mod->DeleteTemplate($tplname);
        }
        catch( Throwable $t ) {
            // ignore this error
        }

    };

    try {
        $sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'module_news','searchable I1');
        $dict->ExecuteSQLArray($sqlarray);

        $sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'module_news_categories','item_order I1 UNSIGNED DEFAULT 0');
        $dict->ExecuteSQLArray($sqlarray);

        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY parent_id';
        $categories = $db->getArray($query);

        $uquery = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET item_order = ? WHERE news_category_id = ?';
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
        catch( Throwable $t ) {
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
        catch( Throwable $t ) {
            // ignore this error
        }

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
        catch( Throwable $t ) {
            // ignore this error
        }

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
        catch( Throwable $t ) {
            // ignore this error
        }
    }
    catch( Throwable $t ) {
        audit('',$me,'Upgrade Error: '.$t->GetMessage());
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
    catch( Throwable $t ) {
        // log it
        audit('',$me,'Uninstall Error: '.$t->GetMessage());
        return $t->GetMessage();
    }
}

if( version_compare($oldversion,'2.90') < 0 ) {
    $this->CreatePermission('Modify News Preferences', 'Modify News Module Settings');
/*    if( version_compare(CMS_VERSION,'2.99') >= 0 ) {
        $fp = cms_join_path(CMS_ROOT_PATH,'lib','modules',$me);
        if( is_dir($fp) ) recursive_delete($fp);
        $fp = cms_join_path(CMS_ROOT_PATH,'modules',$me);
        if( is_dir($fp) ) recursive_delete($fp);
    }
*/
    $this->RemovePreference();
    $this->SetPreference('date_format','%Y-%m-%e %H:%M');
    $this->SetPreference('default_category',1);
    $this->SetPreference('timeblock',News::HOURBLOCK);

    $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_news_fielddefs');
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_news_fieldvals');
    $dict->ExecuteSQLArray($sqlarray);
    $db->DropSequence(CMS_DB_PREFIX.'module_news_categories_seq');

    $longnow = $db->DbTimeStamp(time(),false);

    $tbl = CMS_DB_PREFIX.'module_news';
    $query = 'UPDATE '.$tbl.' SET author_id=0 WHERE author_id<0';
    $db->execute($query);
    $query = 'UPDATE '.$tbl.' SET modified_date=NULL WHERE modified_date<=create_date';
    $db->execute($query);
    $query = 'UPDATE '.$tbl.' SET status=\'archived\' WHERE status=\'published\' AND end_time IS NOT NULL AND end_time<=?';
    $db->execute($query,[$longnow]);
    $query = 'UPDATE '.$tbl.' SET start_time=MAX(news_date,modified_date,create_date) WHERE start_time IS NULL AND status!=\'draft\'';
    $db->execute($query);

    $sqlarray = $dict->DropColumnSQL($tbl,'news_date');
    $dict->ExecuteSqlArray($sqlarray);
    $sqlarray = $dict->RenameColumnSQL($tbl,'icon','image_url','C(255)');
    $dict->ExecuteSqlArray($sqlarray);
    $sqlarray = $dict->ChangeTableSQL($tbl,
'
news_id I UNSIGNED,
news_category_id I UNSIGNED,
status C(25),
news_data C(15000),
summary C(1000),
alias C(255),
create_date DT NOT NULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
author_id I UNSIGNED DEFAULT 0,
searchable I1 DEFAULT 1
',
    'CHARACTER SET utf8mb4');
    $dict->ExecuteSqlArray($sqlarray);

    $tbl = CMS_DB_PREFIX.'module_news_categories';

    $query = 'UPDATE '.$tbl.' SET modified_date=NULL WHERE modified_date<=create_date';
    $db->execute($query);

    $sqlarray = $dict->ChangeTableSQL($tbl,
'
news_category_id I UNSIGNED AUTO,
news_category_name C(255) CHARACTER SET utf8mb4,
item_order I1 UNSIGNED DEFAULT 0,
long_name C(1000) CHARACTER SET utf8mb4,
alias C(255),
image_url C(255),
create_date DT NOT NULL DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
',
    'CHARACTER SET ascii');
    $dict->ExecuteSqlArray($sqlarray);

    $query = 'DELETE FROM '.CMS_DB_PREFIX.'layout_templates WHERE type_id=(SELECT id FROM '.CMS_DB_PREFIX.'layout_tpl_types WHERE originator=\'News\' AND name=\'form\')';
    $db->execute($query);
    $query = 'DELETE FROM '.CMS_DB_PREFIX.'layout_tpl_types WHERE originator=\'News\' AND name=\'form\'';
    $db->execute($query);
}
