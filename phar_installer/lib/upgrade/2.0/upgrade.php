<?php

// access to CMSMS 2.99+ API is needed
use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\internal\std_layout_template_callbacks;
use CMSMS\LockOperations;
use CMSMS\SingleItem;
use CMSMS\Stylesheet;
use CMSMS\Template;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
use LogicException;
use UnexpectedValueException;
use function cms_installer\endswith;

set_time_limit(3600);
status_msg('Fixing errors with deprecated plugins in versions prior to CMSMS 2.0');
$fn = $destdir.'/plugins/function.process_pagedata.php';
verbose_msg('deleting file '.$fn);
if (file_exists($fn)) {
    @unlink($fn);
}
status_msg('Upgrading database for CMSMS 2.0');

$gCms = cmsms();
$dbdict = $db->NewDataDictionary();
$taboptarray = ['mysql' => 'TYPE=MyISAM'];

verbose_msg('updating structure of content tabless');
$sqlarray = $dbdict->DropColumnSQL(CMS_DB_PREFIX.'content', ['collaapsed', 'markup']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->AlterColumnSQL(CMS_DB_PREFIX.'content_props', 'content X2');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_modifieddate', CMS_DB_PREFIX.'content', 'modified_date');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('add index to the module plugins table');
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_module', CMS_DB_PREFIX.'module_smarty_plugins', 'module');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('updating structure of the permissions table');
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.'permissions', 'permission_source C(255)');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('add index to user groups table');
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_groupid_userid', CMS_DB_PREFIX.'user_groups', 'group_id,user_id', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('deleting old events');
$tmp = ['AddGlobalContentPre', 'AddGlobalContentPost', 'EditGlobalContentPre', 'EditGlobalContentPost',
        'DeleteGlobalContentPre', 'DeleteGlobalContentPost', 'GlobalContentPreCompile', 'GlobalContentPostCompile',
        'ContentStylesheet'];
$query = 'DELETE FROM '.CMS_DB_PREFIX.'events WHERE originator = \'Core\' AND event_name IN ('.implode(',', $tmp).')';
$return = $db->execute($query);

// create new events
verbose_msg('creating new events');
Events::CreateEvent('Core', 'AddTemplateTypePre');
Events::CreateEvent('Core', 'AddTemplateTypePost');
Events::CreateEvent('Core', 'EditTemplateTypePre');
Events::CreateEvent('Core', 'EditTemplateTypePost');
Events::CreateEvent('Core', 'DeleteTemplateTypePre');
Events::CreateEvent('Core', 'DeleteTemplateTypePost');
/* DesignManager module
Events::CreateEvent('Core','AddDesignPre');
Events::CreateEvent('Core','AddDesignPost');
Events::CreateEvent('Core','EditDesignPre');
Events::CreateEvent('Core','EditDesignPost');
Events::CreateEvent('Core','DeleteDesignPre');
Events::CreateEvent('Core','DeleteDesignPost');
*/
// create new tables
verbose_msg('create table '.TemplateType::TABLENAME);
$flds = '
         id I AUTO KEY,
         originator C(50) NOTNULL,
         name C(60) NOTNULL,
         has_dflt I1,
         dflt_contents X2,
         description X,
         lang_cb C(255),
         dflt_content_cb C(255),
         requires_contentblocks I1,
         owner I,
         created I,
         modified I';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.TemplateType::TABLENAME, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_originat_name', CMS_DB_PREFIX.TemplateType::TABLENAME, 'originator,name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('create table '.TemplatesGroup::TABLENAME);
$flds = '
         id I AUTO KEY,
         name C(100) NOTNULL,
         description X,
         item_order X,
         modified I';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.TemplatesGroup::TABLENAME, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_name', CMS_DB_PREFIX.TemplatesGroup::TABLENAME,
                                    'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('create table '.Template::TABLENAME);
$flds = '
         id I AUTO KEY,
         name C(100) NOTNULL,
         content X2,
         description X,
         type_id I NOTNULL,
         type_dflt I1,
         category_id I,
         owner_id I NOTNULL,
         created I,
         modified I';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.Template::TABLENAME, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_name', CMS_DB_PREFIX.Template::TABLENAME, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_typeid_typedflt', CMS_DB_PREFIX.Template::TABLENAME, 'type_id,type_dflt');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('create table '.Stylesheet::TABLENAME);
$flds = '
         id I AUTO KEY,
         name C(100) NOTNULL,
         content X2,
         description X,
         media_type C(255),
         media_query X,
         created I,
         modified I';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.Stylesheet::TABLENAME, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_name', CMS_DB_PREFIX.Stylesheet::TABLENAME, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('create table '.Template::ADDUSERSTABLE);
$flds = '
         tpl_id I KEY,
         user_id I KEY
        ';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.Template::ADDUSERSTABLE, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);

/* these tables are now part of DesignManager module
verbose_msg('create table '.Design::TABLENAME);
$flds = "
         id I AUTO KEY,
         name C(100) NOTNULL,
         description X,
         dflt I1,
         created I,
         modified I
        ";
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.Design::TABLENAME, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_name',CMS_DB_PREFIX.Design::TABLENAME, 'name', array('UNIQUE'));
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('create table '.Design::TPLTABLE);
$flds = "
         design_id I NOTNULL KEY,
         tpl_id I NOTNULL KEY
        ";
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.Design::TPLTABLE, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_tplid', CMS_DB_PREFIX.Design::TPLTABLE, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('create table '.Design::CSSTABLE);
$flds = "
         design_id I NOTNULL KEY,
         css_id I NOTNULL KEY,
         item_order I NOTNULL
        ";
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.Design::CSSTABLE, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
*/
verbose_msg('create table '.LockOperations::LOCK_TABLE);
$flds = '
         id I NOTNULL AUTO KEY,
         type C(25) NOTNULL,
         oid I NOTNULL,
         uid I NOTNULL,
         created I NOTNULL,
         modified I NOTNULL,
         lifetime I2 NOTNULL,
         expires I NOTNULL
        ';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX. LockOperations::LOCK_TABLE, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_type_oid', CMS_DB_PREFIX.'locks', 'type,oid', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_expires', CMS_DB_PREFIX.'locks', 'expires');
$return = $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'i_uid', CMS_DB_PREFIX.'locks', 'uid');
$return = $dbdict->ExecuteSQLArray($sqlarray);

// create initial types.
$page_template_type = $gcb_template_type = null;
for ($tries = 0; $tries < 2; ++$tries) {
    try {
        $page_template_type = TemplateType::load(TemplateType::CORE.'::page');
        $gcb_template_type = TemplateType::load(TemplateType::CORE.'::generic');
        break;
    } catch (Throwable $t) {
        // we insert the records manually... because later versions of the template type
        // add different columns... and the save() method won't work.
        verbose_msg('create initial template types');

        $contents = std_layout_template_callbacks::reset_page_type_defaults();
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.TemplateType::TABLENAME.' (originator,name,has_dflt,dflt_contents,description,
                    lang_cb, dflt_content_cb, requires_contentblocks, owner, created, modified)
                VALUES (?,?,?,?,?,?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())';
        $dbr = $db->execute($sql, [TemplateType::CORE, 'page', true, $contents, null,
               serialize('CMSMS\internal\std_layout_template_callbacks::page_type_lang_callback'),
               serialize('CMSMS\internal\std_layout_template_callbacks::reset_page_type_default'), true, null]);
        $contents = null;
        $dbr = $db->execute($sql, [TemplateType::CORE, 'generic', false, null, null,
               serialize('CMSMS\internal\std_layout_template_callbacks::generic_type_lang_callback'), null, false, null]);
    }
} // tries

    /*
    // if we got here.... the type does not exist.
    $page_template_type = new TemplateType();
    $page_template_type->set_originator(TemplateType::CORE);
    $page_template_type->set_name('page');
    $page_template_type->set_dflt_flag(TRUE);
    $page_template_type->set_lang_callback('CMSMS\internal\std_layout_template_callbacks::page_type_lang_callback');
    $page_template_type->set_content_callback('CMSMS\internal\std_layout_template_callbacks::reset_page_type_defaults');
    $page_template_type->reset_content_to_factory();
    $page_template_type->set_content_block_flag(TRUE);
    $page_template_type->save();

    $gcb_template_type = new TemplateType();
    $gcb_template_type->set_originator(TemplateType::CORE);
    $gcb_template_type->set_name('generic');
    $gcb_template_type->set_lang_callback('CMSMS\internal\std_layout_template_callbacks::generic_type_lang_callback');
    $gcb_template_type->save();
    */
if (!is_object($page_template_type) || !is_object($gcb_template_type)) {
    error_msg('The page template type and/or GCB template type could not be found or created');
    throw new LogicException('This is bad');
}

$_fix_name = function($str) {
    if (AdminUtils::is_valid_itemname($str)) {
        return $str;
    }
    $orig = $str;
    $str = trim($str);
    if (!AdminUtils::is_valid_itemname($str[0])) {
        $str[0] = '_';
    }
    for ($i = 1; $i < strlen($str); ++$i) {
        if (!AdminUtils::is_valid_itemname($str[$i])) {
            $str[$i] = '_';
        }
    }
    for ($i = 0; $i < 5; ++$i) {
        $in = $str;
        $str = str_replace('__', '_', $str);
        if ($in == $str) {
            break;
        }
    }
    if ($str == '_') {
        throw new UnexpectedValueException('Invalid name \''.$orig.'\' cannot be corrected');
    }
    return $str;
};

$_fix_css_name = function($str) {
    // stylesheet names cannot end with .css and must be unique
    if (!endswith($str, '.css') && AdminUtils::is_valid_itemname($str)) {
        return $str;
    }
    $orig = $str;
    $str = trim($str);
    if (!AdminUtils::is_valid_itemname($str[0])) {
        $str[0] = '_';
    }
    for ($i = 1; $i < strlen($str); ++$i) {
        if (!AdminUtils::is_valid_itemname($str[$i])) {
            $str[$i] = '_';
        }
    }
    for ($i = 0; $i < 5; ++$i) {
        $in = $str;
        $str = str_replace('__', '_', $str);
        if ($in == $str) {
            break;
        }
    }
    if ($str == '_') {
        throw new UnexpectedValueException('Invalid name \''.$orig.'\' cannot be corrected');
    }
    return $str;
};

$fix_template_name = function($in) use (&$db,&$_fix_name) {
    // template names have to be unique and cannot end with .tpl
    if (endswith($in, '.tpl')) {
        $in = substr($in, 0, -4);
    }
    $in = $_fix_name($in);
    $name = Template::generate_unique_name($in);
    if ($name != $in) {
        error_msg('Template named '.$in.' conflicted with an existing template, new name is '.$name);
    }
    return $name;
};

// read gcb's and convert them to templates.
// note: we directly write the the Template table instead of using the Template API because
// the database structure changed between 2.0 and 2.1 (listable column) and the Template class relies on a listable colum which may
// not yet exist.
verbose_msg('convert global content blocks to generic templates');
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'htmlblobs';
$sql2 = 'INSERT INTO '.CMS_DB_PREFIX.Template::TABLENAME.' (name,content,description,type_id,type_dflt,owner_id,created,modified) VALUES (?,?,?,?,0,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())';
$gcblist = null;
$tmp = $db->getArray($query);
if (is_array($tmp) && count($tmp)) {
    // for each gcb, come up wit a new name and if the new name does not exist in the database, create a new template by that name.
    foreach ($tmp as $gcb) {
        $new_name = $fix_template_name($gcb['htmlblob_name']);
        try {
            $template = Template::load($new_name);
            // nothing here, template with this name exists.
        } catch (Throwable $t) {
            $db->execute($sql2, [$new_name, $gcb['html'], $gcb['description'], $gcb_template_type->get_id(), $gcb['owner']]);
            $gcb['template_id'] = $db->Insert_ID();
            $gcblist[$gcb['htmlblob_id']] = $gcb;
        }
    }

    if (count($gcblist)) {
        // process all of the additional owners, and sort them into an array of uids, one array for each gcb.
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'additional_htmlblob_users';
        $tmp = $db->getArray($query);
        if (is_array($tmp) && count($tmp)) {
            $users = [];
            foreach ($tmp as $row) {
                $htmlblob_id = $row['htmlblob_id'];
                $uid = (int)$row['user_id'];
                if ($uid < 1) {
                    continue;
                }
                if (!isset($gcblist[$htmlblob_id])) {
                    continue;
                }
                if ($uid == $gcblist[$htmlblob_id]['owner']) {
                    continue;
                }
                if (!isset($users[$htmlblob_id])) {
                    $users[$htmlblob_id] = [];
                }
                $users[$htmlblob_id][] = (int)$row['user_id'];
            }
        }

        // now insert the additional editors directly into the database
        $sql3 = 'INSERT INTO '.CMS_DB_PREFIX.Template::ADDUSERSTABLE.' (tpl_id, user_id) VALUES (?,?)';
        foreach ($gcblist as $htmlblob_id => $gcb) {
            if (!isset($users[$htmlblob_id])) {
                continue;
            }
            foreach ($users[$htmlblob_id] as $add_uid) {
                $db->execute($sql3, [$gcb['template_id'], $add_uid]);
            }
        }
    }
}
unset($gcblist,$tmp);

verbose_msg('dropping gcb related tables...');
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'additional_htmlblob_users_seq');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'additional_htmlblob_users');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'htmlblobs_seq');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'htmlblobs');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('converting stylesheets');
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'css';
$tmp = $db->getArray($query);
if (is_array($tmp) && count($tmp)) {
    $css_list = [];
    foreach ($tmp as $row) {
        $new_name = $_fix_css_name($row['css_name']);
        if ($new_name != $row['css_name']) {
            verbose_msg('Rename stylesheet '.$row['css_name']." to $new_name");
        }
        try {
            $tmp = Stylesheet::load($new_name);
        } catch (Throwable $t) {
            $css_id = $row['css_id'];
            $stylesheet = new Stylesheet();
            $stylesheet->set_name($new_name);
            $stylesheet->set_content($row['css_text']);
            $stylesheet->set_description('CMSMS Upgraded on '.$db->DbTimeStamp(time()));
            $stylesheet->set_media_types($row['media_type']);
            $stylesheet->set_media_query($row['media_query']);
            $stylesheet->save();

            $row['css_obj'] = $stylesheet;
            $csslist[$row['css_id']] = $row;
        }
    }
}
unset($tmp);

verbose_msg('converting page templates');
// todo: handle stylesheets that are orphaned
@ini_set('display_errors', 1);
@error_reporting(E_ALL);

/* this is now DesignManager module stuff
$tpl_query = 'SELECT * FROM '.CMS_DB_PREFIX.'templates';
$tpl_insert_query = 'INSERT INTO '.CMS_DB_PREFIX.Template::TABLENAME.' (name,content,description,type_id,type_dflt,owner_id,created,modified) VALUES (?,?,?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())';
$css_assoc_query = 'SELECT * FROM '.CMS_DB_PREFIX.'css_assoc WHERE assoc_to_id = ? ORDER BY assoc_order ASC';
$tmp = $db->getArray($tpl_query);
$template_list = array();
if( is_array($tmp) && count($tmp) ) {
    foreach( $tmp as $row ) {
        $row['template_name'] = $fix_template_name($row['template_name']);
        $is_default = (int) $row['default_template'];

        // create the design (one design per page template)
        $tpl_id = $row['template_id'];
        $design = new Design();
        $design->set_name($row['template_name']);
        $design->set_description('CMSMS Upgraded on '.$db->DbTimeStamp(time()));
        $design->set_default($is_default);
        $design->save(); // the design will now have an id.
        verbose_msg('created design '.$design->get_name());

        // create the template
        $db->execute($tpl_insert_query,array($row['template_name'],$row['template_content'],'',$page_template_type->get_id(),
                                $is_default,1));
        $new_tpl_id = $db->Insert_ID();
        $design->add_template($new_tpl_id);
        $design->save(); // save the design again.

        $row['new_tpl_id'] = $new_tpl_id;
        $row['new_design_id'] = $design->get_id();
        $template_list[$tpl_id] = $row;
        verbose_msg('created template '.$row['template_name']);

        // get stylesheet(s) attached to this template
        // and associate them with the design.
        $associations = $db->getArray($css_assoc_query,array($row['template_id']));
        if( is_array($associations) && count($associations) ) {
            foreach( $associations as $assoc ) {
                $css_id = $assoc['assoc_css_id'];
                if( !isset($csslist[$css_id]) ) continue;
                $design->add_stylesheet($csslist[$css_id]['css_obj']);
            }
            verbose_msg('associated '.count($associations).' stylesheets with the design');
            $design->save();
        }
    }
}
unset($tmp);

verbose_msg('adjusting pages');
$query = 'SELECT content_id,template_id,content_alias FROM '.CMS_DB_PREFIX.'content WHERE template_id > 0';
$uquery = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id = ? WHERE content_id = ?';
$iquery = 'INSERT INTO '.CMS_DB_PREFIX.'content_props (content_id,type,prop_name,content,create_date) VALUES (?,?,?,?,NOW())';
$content_rows = $db->getArray($query);
$contentops = SingleItem::ContentOperations();
if( is_array($content_rows) && count($content_rows) ) {
    foreach( $content_rows as $row ) {
        if( $row['template_id'] < 1 ) continue;
        $content_id = $row['content_id'];

        $tpl_id = (int) $row['template_id'];
        if( !isset($template_list[$tpl_id]) ) {
            error_msg('ERROR: The page '.$row['content_alias'].' Refers to a template with id '.$tpl_id.' That was not found in the database');
            continue;
        }
        $tpl_row = $template_list[$tpl_id];
        if( !isset($tpl_row['new_tpl_id']) ) {
            error_msg("could not find map to new template for template $tpl_id on page $content_id");
            continue;
        }

        // because we create a new design on upgrade for each page template thre can be only one design
        $design_id = $tpl_row['new_design_id'];
        $tpl_id = $tpl_row['new_tpl_id'];

        $db->execute($uquery,array($tpl_id,$content_id));
        $db->execute($iquery,array($content_id,'string','design_id',$design_id));
        verbose_msg('adjusted page '.$row['content_alias']);
  }
}
*/

verbose_msg('dropping old template tables');
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'templates');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'templates_seq');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'css_assoc');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'css');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'css_seq');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('uninstalling theme manager');
SingleItem::ModuleOperations()->UninstallModule('ThemeManager');

verbose_msg('upgrading cms_groups table');
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.'groups', 'group_desc C(255)');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('Remove the CMSPrinting module from the database');
$query = 'DELETE FROM '.CMS_DB_PREFIX.'modules WHERE module_name = ?';
$db->execute($query, ['CMSPrinting']);

verbose_msg('Creating print UDT');
$params = [
'code' => <<<'EOT'
return '<!-- print tag removed in CMS Made Simple 2.0 -->';
EOT
,
'description' => 'Stub function to replace the print plugin'
];
SingleItem::UserTagOperations()->SetUserTag('print', $params);

$sql = 'SELECT username FROM '.CMS_DB_PREFIX.'users WHERE user_id = 1';
$un = $db->getOne($sql);
if ($un) {
    // make sure that if we have a user with id=1 that this user is in the admin (gid=1) group
    // as 2.0 now does not magically check uid's just gid's for admin access.
    try {
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.'user_groups (group_id,user_id) VALUES (1,1)';
        $db->execute($sql);
    } catch (Throwable $t) {
        // this can throw if the user is already in this group... let it.
    }
}

verbose_msg(ilang('reset_user_settings'));
$theme = 'OneEleven';
$query = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value = ? WHERE preference = ?';
$db->execute($query, [$theme, 'admintheme']);
$query = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value = ? WHERE preference = ? AND value = ?';
$db->execute($query, ['MicroTiny', 'wysiwyg', 'TinyMCE']);
$query = 'DELETE FROM '.CMS_DB_PREFIX.'userprefs WHERE preference = ?';
$db->execute($query, ['collapse']);

verbose_msg(ilang('reset_site_preferences'));
$query = 'UPDATE '.CMS_DB_PREFIX.'siteprefs SET sitepref_value = ?, modified_date = NOW() WHERE sitepref_name = ?';
$db->execute($query, [$theme, 'logintheme']);

verbose_msg(ilang('queue_for_upgrade', 'CMSMailer'));
SingleItem::ModuleOperations()->QueueForInstall('CMSMailer'); // TODO N/A in CMSMS 2.99+

verbose_msg(ilang('upgrading_schema', 200));
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 200';
$db->execute($query);

status_msg('done upgrades for 2.0');
