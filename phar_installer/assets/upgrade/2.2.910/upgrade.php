<?php

use __installer\utils;
use CMSMS\Group;
use CMSMS\LogicException;
use CMSMS\SimplePluginOperations;
use function __installer\CMSMS\endswith;
use function __installer\CMSMS\joinpath;
use function __installer\get_app;

$app = get_app();
$destdir = $app->get_destdir();
if( !$destdir || !is_dir($destdir) ) {
    throw new LogicException('Destination directory does not exist');
}
$config = $app->get_config();
$s = ( !empty( $config['admin_dir'] ) ) ? $config['admin_dir'] : 'admin';
$admindir = $destdir . DIRECTORY_SEPARATOR . $s;
$assetsdir = ( !empty( $config['assets_path'] ) ) ? $config['assets_path'] : $destdir . DIRECTORY_SEPARATOR . 'assets';

// 1. Move core modules to /lib/modules
foreach([
'AdminLog',
'AdminSearch',
'CMSContentManager',
'CmsJobManager',
'CoreAdminLogin',
'CoreTextEditing',
'DesignManager',
'FileManager',
'FilePicker',
'MicroTiny',
'ModuleManager',
'Navigator',
'Search',
] as $modname ) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if( is_dir( $fp ) ) {
        $to = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if( !is_dir( $to ) ) {
            rename( $fp, $to );
        } else {
            utils::rrmdir( $fp );
        }
    }
}

// 2. Move ex-core modules to /assets/modules
foreach( ['MenuManager', 'CMSMailer', 'News'] as $modname ) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if( is_dir( $fp ) ) {
        $to = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if( !is_dir( $to ) ) {
            rename( $fp, $to );
        } else {
            utils::rrmdir( $fp );
        }
    }
}

// 3. Create new folders, if necessary
foreach ([
 ['admin','configs'],
 ['assets','admin_custom'],
 ['assets','configs'],
 ['assets','css'],
 ['assets','images'],
 ['assets','module_custom'],
 ['assets','modules'],
 ['assets','plugins'],
 ['assets','simple_plugins'],
 ['assets','templates'],
] as $segs) {
    switch($segs[0]) {
        case 'admin':
            $to = $admindir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'assets':
            $to = $assetsdir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        default:
            break 2;
    }
    if( !is_dir( $to ) ) @mkdir( $to, 0771, true );
    if( !is_dir( $to ) ) throw new LogicException("Could not create $to directory");
    touch($to . DIRECTORY_SEPARATOR . 'index.html');
}
touch($assetsdir . DIRECTORY_SEPARATOR . 'index.html');

// 4. Convert UDT's to simple plugins, widen users-table columns
$udt_list = $db->GetArray('SELECT * FROM '.CMS_DB_PREFIX.'userplugins');
if( $udt_list ) {

    $create_simple_plugin = function( array $row, SimplePluginOperations $ops, $smarty ) {
        $fp = $ops->file_path($row['userplugin_name']);
        if( is_file( $fp ) ) {
            verbose_msg('simple plugin named '.$row['userplugin_name'].' already exists');
            return;
        }

        $code = preg_replace(
                ['/^[\s\r\n]*<\\?php\s*[\r\n]*/i', '/[\s\r\n]*\\?>[\s\r\n]*$/'],
                ['', ''], $row['code']);
        if( !$code ) {
            verbose_msg('UDT named '.$row['userplugin_name'].' is empty, and will be discarded');
            return;
        }

        $meta = ['name'=>$row['userplugin_name']];
        if( $row['description'] ) {
            $desc = trim($row['description'], " \t\n\r");
            if( $desc ) {
                $meta['description'] = $desc;
            }
        }

        if( $ops->save($row['userplugin_name'], $meta, $code, $smarty) ) {
            verbose_msg('Converted UDT '.$row['userplugin_name'].' to a plugin file');
        } else {
            verbose_msg('Error saving UDT named '.$row['userplugin_name']);
        }
    };

    $ops = SimplePluginOperations::get_instance();
    //$smarty defined upstream, used downstream
    foreach( $udt_list as $udt ) {
        $create_simple_plugin( $udt, $ops, $smarty );
    }

    $dict = GetDataDictionary($db);
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins_seq');
    $dict->ExecuteSQLArray($sqlarr);
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins');
    $dict->ExecuteSQLArray($sqlarr);
    status_msg('Converted User Defined Tags to simple-plugin files');

    $db->Execute( 'ALTER TABLE '.CMS_DB_PREFIX.'users MODIFY username VARCHAR(80)' );
    $db->Execute( 'ALTER TABLE '.CMS_DB_PREFIX.'users MODIFY password VARCHAR(128)' );
}

// 5. Tweak callbacks for page and generic layout template types
$page_type = CmsLayoutTemplateType::load('__CORE__::page');
if( $page_type ) {
    $page_type->set_lang_callback('\\CMSMS\\internal\\std_layout_template_callbacks::page_type_lang_callback');
    $page_type->set_content_callback('\\CMSMS\\internal\\std_layout_template_callbacks::reset_page_type_defaults');
    $page_type->set_help_callback('\\CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $page_type->save();
} else {
    error_msg('__CORE__::page template update '.ilang('failed'));
}

$generic_type = CmsLayoutTemplateType::load('__CORE__::generic');
if( $generic_type ) {
    $generic_type->set_lang_callback('\\CMSMS\\internal\\std_layout_template_callbacks::generic_type_lang_callback');
    $generic_type->set_help_callback('\\CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $generic_type->save();
} else {
    error_msg('__CORE__::generic template update '.ilang('failed'));
}

// 6. Revised/extra permissions
$now = time();
$longnow = $db->DbTimeStamp($now);
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_name=?,permission_text=?,modified_date=? WHERE permission_name=?';
$db->Execute($query, ['Modify Simple Plugins','Modify User-Defined Tag Files',$longnow,'Modify User-defined Tags']);
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_source=\'Core\' WHERE permission_source=NULL';
$db->Execute($query);

foreach( [
 'Modify Site Code',
// 'Modify Site Assets',
 'Remote Administration',  //for app management sans admin console
] as $one_perm ) {
    $permission = new CmsPermission();
    $permission->source = 'Core';
    $permission->name = $one_perm;
    $permission->text = $one_perm;
    $permission->save();
}

$group = new Group();
$group->name = 'CodeManager';
$group->description = ilang('grp_coder_desc');
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Code');
//$group->GrantPermission('Modify Site Assets');
$group->GrantPermission('Modify Simple Plugins');
/*
$group = new Group();
$group->name = 'AssetManager';
$group->description = 'Members of this group can add/edit/delete website asset-files';
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Assets');
*/

// 6. Cleanup plugins - remove reference from plugins-argument where necessary
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
                if (endswith($one, '.php')) {
                    $fp = $path.DIRECTORY_SEPARATOR.$one;
                    $content = file_get_contents($fp);
                    if ($content) {
                        $parts = explode('.',$one);
                        $patn = '/function\\s+smarty(_cms)?_'.$parts[0].'_'.$parts[1].'\\s?\\([^,]+,[^,]*(&\\s?)(\\$\\S+)\\s?\\)\\s?[\r\n]/';
                        if (preg_match($patn, $content, $matches)) {
                            $content = str_replace($matches[2].$matches[3], $matches[3], $content);
                            file_put_contents($fp, $content);
                        }
                    }
               }
            }
        }
    }
}

// 7. Drop redundant sequence-tables
$db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
$db->DropSequence(CMS_DB_PREFIX.'userplugins_seq');

$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

// 8. Table revisions
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
$msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
verbose_msg(ilang('install_created_table', CmsLayoutTemplateCategory::TPLTABLE, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_cat_tplasoc_1',
 CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
verbose_msg(ilang('install_creating_index', 'idx_layout_cat_tplasoc_1', $msg_ret));

// migrate existing category_id values to new table
$query = 'SELECT id,category_id FROM '.CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME.' WHERE category_id IS NOT NULL';
$data = $db->GetArray($query);
if ($data) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' (category_id,tpl_id,tpl_order) VALUES (?,?,-1)';
    foreach ($data as $row) {
        $db->Execute($query, [$row['category_id'], $row['id']]);
    }
}

$sqlarray = $dbdict->DropColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,'category_id');
$dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,'originator C(32)');
$dbdict->ExecuteSQLArray($sqlarray);

// layout-templates table indices
// replace this 'unique' by non- (_3 below becomes the validator)
$sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_1',
    CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'name');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_1',
    CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'name');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_3',
    CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'originator,name', ['UNIQUE']);
$dbdict->ExecuteSQLArray($sqlarray);
// content table index used by name
$sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'index_content_by_idhier',
    CMS_DB_PREFIX.'content', 'content_id,hierarchy');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_idhier',
    CMS_DB_PREFIX.'content', 'content_id,hierarchy');
$dbdict->ExecuteSQLArray($sqlarray);

// 9. Migrate module templates to layout-templates table
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_templates ORDER BY module_name,template_name';
$data = $db->GetArray($query);
if ($data) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME.
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
    verbose_msg(ilang('upgrade_modifytable', 'module_templates'));
}

$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_templates');
$dbdict->ExecuteSQLArray($sqlarray);
verbose_msg(ilang('upgrade_deletetable', 'module_templates'));

// 10. Update preferences
// migrate to new default theme
$files = glob(joinpath(CMS_ADMIN_PATH,'themes','*','*Theme.php'),GLOB_NOESCAPE);
foreach ($files as $one) {
    if (is_readable($one)) {
        $name = basename($one, 'Theme.php');
        $query = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value=? WHERE preference=\'admintheme\'';
        $db->Execute($query,[$name]);
        $query = 'UPDATE '.CMS_DB_PREFIX.'siteprefs SET sitepref_value=?,modified_date=? WHERE sitepref_name=\'logintheme\'';
        $db->Execute($query,[$name,$longnow]);
        break;
    }
}
$query = 'INSERT INTO '.CMS_DB_PREFIX.'siteprefs (sitepref_name,create_date,modified_date) VALUES (\'loginmodule\',?,?);';
$db->Execute($query,[$longnow,$longnow]);

//if ($return == 2) {
    $query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 205';
    $db->Execute($query);
//}
