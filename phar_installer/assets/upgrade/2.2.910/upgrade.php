<?php

use cms_siteprefs;
use CMSMS\Group;
use CMSMS\UserPluginOperations;
use function cms_installer\endswith;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\startswith;

// 1. Convert UDT's to file-lugins, widen users-table columns
$udt_list = $db->GetArray('SELECT userplugin_name,description,code FROM '.CMS_DB_PREFIX.'userplugins');
if ($udt_list) {

    function create_user_plugin(array $row, UserPluginOperations $ops, $smarty)
    {
        $fp = $ops->file_path($row['userplugin_name']);
        if (is_file($fp)) {
            verbose_msg('user-plugin named '.$row['userplugin_name'].' already exists');
            return;
        }

        $code = preg_replace(
                ['/^[\s\r\n]*<\\?php\s*[\r\n]*/i', '/[\s\r\n]*\\?>[\s\r\n]*$/', '/echo/'],
                ['', '', 'return'], $row['code']);
        if (!$code) {
            verbose_msg('UDT named '.$row['userplugin_name'].' is empty, and will be discarded');
            return;
        }

        $meta = ['name'=>$row['userplugin_name']];
        if ($row['description']) {
            $desc = trim($row['description'], " \t\n\r");
            if ($desc) {
                $meta['description'] = $desc;
            }
        }

        if ($ops->save($row['userplugin_name'], $meta, $code, $smarty)) {
            verbose_msg('Converted UDT '.$row['userplugin_name'].' to a plugin file');
        } else {
            verbose_msg('Error saving UDT named '.$row['userplugin_name']);
        }
    }

    $ops = UserPluginOperations::get_instance();
    //$smarty defined upstream, used downstream
    foreach ($udt_list as $udt) {
        create_user_plugin($udt, $ops, $smarty);
    }

    $dict = GetDataDictionary($db);
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins_seq');
    $dict->ExecuteSQLArray($sqlarr);
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins');
    $dict->ExecuteSQLArray($sqlarr);
    status_msg('Converted User Defined Tags to user-plugin files');

    $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'users MODIFY username VARCHAR(80)');
    $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'users MODIFY password VARCHAR(128)');
}

// 2. Tweak callbacks for page and generic layout template types
$page_type = CmsLayoutTemplateType::load('__CORE__::page');
if ($page_type) {
    $page_type->set_lang_callback('\\CMSMS\\internal\\std_layout_template_callbacks::page_type_lang_callback');
    $page_type->set_content_callback('\\CMSMS\\internal\\std_layout_template_callbacks::reset_page_type_defaults');
    $page_type->set_help_callback('\\CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $page_type->save();
} else {
    error_msg('__CORE__::page template update '.lang('failed'));
}

$generic_type = CmsLayoutTemplateType::load('__CORE__::generic');
if ($generic_type) {
    $generic_type->set_lang_callback('\\CMSMS\\internal\\std_layout_template_callbacks::generic_type_lang_callback');
    $generic_type->set_help_callback('\\CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $generic_type->save();
} else {
    error_msg('__CORE__::generic template update '.lang('failed'));
}

// 3. Revised/extra permissions
$now = time();
$longnow = $db->DbTimeStamp($now);
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_name=?,permission_text=?,modified_date=? WHERE permission_name=?';
$db->Execute($query, ['Modify User Plugins','Modify User-Defined Tag Files',$longnow,'Modify User-defined Tags']);
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_source=\'Core\' WHERE permission_source=NULL';
$db->Execute($query);

foreach ([
 'Modify Site Code',
// 'Modify Site Assets',
 'Remote Administration',  //for app management sans admin console
] as $one_perm) {
    $permission = new CmsPermission();
    $permission->source = 'Core';
    $permission->name = $one_perm;
    $permission->text = $one_perm;
    $permission->save();
}

$group = new Group();
$group->name = 'CodeManager';
$group->description = lang('grp_coder_desc');
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Code');
//$group->GrantPermission('Modify Site Assets');
$group->GrantPermission('Modify User Plugins');
/*
$group = new Group();
$group->name = 'AssetManager';
$group->description = 'Members of this group can add/edit/delete website asset-files';
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Assets');
*/

// 4. Cleanup plugins - remove reference from plugins-argument where necessary
foreach ([
 ['lib', 'plugins'],
 ['admin', 'plugins'],
 ['assets', 'plugins'],
 ['plugins'], //deprecated
] as $segs) {
    $path = joinpath($destdir, ...$segs);
    if (is_dir($path)) {
        $files = scandir($path, SCANDIR_SORT_NONE);
        if ($files) {
            foreach ($files as $one) {
                if (startswith($one, 'function') && endswith($one, '.php')) {
                    $fp = $path.DIRECTORY_SEPARATOR.$one;
                    $content = file_get_contents($fp);
                    if ($content) {
                        $parts = explode('.',$one);
                        $patn = '/function\\s+smarty_(cms_)?function_'.$parts[1].'\\s*\\([^,]+,[^,&]*(&\\s*)?(\\$\\S+)\\s*\\)\\s*[\r\n]/';
                        if (preg_match($patn, $content, $matches)) {
                            if (!empty($matches[1])) {
                                $content = str_replace('smarty_cms_function', 'smarty_function', $content);
                            }
                            if (!empty($matches[2])) {
                                $content = str_replace($matches[2].$matches[3], $matches[3], $content);
                            }
                            file_put_contents($fp, $content);
                        }
                    }
               }
            }
        }
    }
}

// 5. Drop redundant sequence-tables
$db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
$db->DropSequence(CMS_DB_PREFIX.'userplugins_seq');

$dbdict = $db->GetDataDictionary();
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

// 6. Table revisions
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE,'tpl_order I(4) DEFAULT 0');
$dbdict->ExecuteSQLArray($sqlarray);

$flds = '
category_id I NOTNULL,
tpl_id I NOTNULL,
tpl_order I(4) DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? lang('done') : lang('failed');
verbose_msg(lang('install_created_table', CmsLayoutTemplateCategory::TPLTABLE, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_cat_tplasoc_1',
 CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? lang('done') : lang('failed');
verbose_msg(lang('install_creating_index', 'idx_layout_cat_tplasoc_1', $msg_ret));

// migrate existing category_id values to new table
$query = 'SELECT id,category_id FROM '.CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME.' WHERE category_id IS NOT NULL';
$data = $db->GetArray($query);
if ($data) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' (category_id,tpl_id,tpl_order) VALUES (?,?,-1)';
    foreach ($data as $row) {
        $db->Execute($query, [$row['category_id'], $row['id']]);
    }
}

$sqlarray = $dbdict->DropColumnSQL(CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME,'category_id');
$dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME,'originator C(32) AFTER id');
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME,'isfile I1 DEFAULT 0 AFTER listable');
$dbdict->ExecuteSQLArray($sqlarray);

// layout-templates table indices
// replace this 'unique' by non- (_3 below becomes the validator)
$sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_1',
    CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME, 'name');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_1',
    CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME, 'name');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_3',
    CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME, 'originator,name', ['UNIQUE']);
$dbdict->ExecuteSQLArray($sqlarray);
// content table index used by name
$sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'index_content_by_idhier',
    CMS_DB_PREFIX.'content', 'content_id,hierarchy');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_idhier',
    CMS_DB_PREFIX.'content', 'content_id,hierarchy');
$dbdict->ExecuteSQLArray($sqlarray);

//events table
$sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'event_id'); //redundant duplicate index
$dbdict->ExecuteSQLArray($sqlarray);
//event-handlers table columns
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.'event_handlers', 'type C(1) NOT NULL DEFAULT "C"');
$dbdict->ExecuteSQLArray($sqlarray);
$query = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET type="M" WHERE module_name IS NOT NULL';
$db->Execute($query);
$query = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET type="U" WHERE tag_name IS NOT NULL';
$db->Execute($query);
$sqlarray = $dbdict->RenameColumnSQL(CMS_DB_PREFIX.'event_handlers', 'module_name', 'class', 'C(96)');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->RenameColumnSQL(CMS_DB_PREFIX.'event_handlers', 'tag_name', 'func', 'C(64)');
$dbdict->ExecuteSQLArray($sqlarray);
verbose_msg(lang('upgrade_modifytable', 'event_handlers'));

// 7. Migrate module templates to layout-templates table
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_templates ORDER BY module_name,template_name';
$data = $db->GetArray($query);
if ($data) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME.
        ' (originator,name,content,type_id,created,modified) VALUES (?,?,?,?,?,?)';
    $dt = new DateTime(null, new DateTimeZone('UTC'));
    $types = [];
    foreach ($data as $row) {
        $name = $row['module_name'];
        if (!isset($types[$name])) {
            $db->Execute('INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME.
            ' (originator,name,description,owner,created,modified) VALUES (?,?,?,-1,?,?)',
            [
                $name,
                'Moduleaction',
                'Action templates for module: '.$name,
                $now,
                $now,
            ]);
            $types[$name] = $db->insert_id();
        }
        $dt->modify($row['create_date']);
        $created = $dt->getTimestamp();
        if (!$created) { $created = $now; }
        $dt->modify($row['modified_date']);
        $modified = $dt->getTimestamp();
        if (!$modified) { $modified = min($now, $created); }
        $db->Execute($query, [
            $name,
            $row['template_name'],
            $row['content'],
            $types[$name],
            $created,
            $modified
        ]);
    }
    verbose_msg(lang('upgrade_modifytable', 'module_templates'));
}

$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_templates');
$dbdict->ExecuteSQLArray($sqlarray);
verbose_msg(lang('upgrade_deletetable', 'module_templates'));

// 8. Update preferences
// migrate to new default theme
$files = glob(joinpath(CMS_ADMIN_PATH,'themes','*','*Theme.php'),GLOB_NOESCAPE);
foreach ($files as $one) {
    if (is_readable($one)) {
        $name = basename($one, 'Theme.php');
        $query = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value=? WHERE preference=\'admintheme\'';
        $db->Execute($query,[$name]);
/*        $query = 'UPDATE '.CMS_DB_PREFIX.'siteprefs SET sitepref_value=?,modified_date=? WHERE sitepref_name=\'logintheme\'';
        $db->Execute($query,[$name,$longnow]);
*/
        cms_siteprefs::set('logintheme', $name);
        break;
    }
}

//$query = 'INSERT INTO '.CMS_DB_PREFIX.'siteprefs (sitepref_name,create_date,modified_date) VALUES (\'loginmodule\',?,?);';
//$db->Execute($query,[$longnow,$longnow]);
foreach([
    'loginmodule' => '',
    'smarty_cachelife' => -1, // smarty default
 ] as $name=>$val) {
    cms_siteprefs::set($name, $val);
}

//if ($return == 2) {
    $query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 206';
    $db->Execute($query);
//}
