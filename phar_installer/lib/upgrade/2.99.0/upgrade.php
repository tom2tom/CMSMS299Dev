<?php

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\Permission;
use CMSMS\StylesheetOperations;
use CMSMS\StylesheetsGroup;
use CMSMS\TemplateOperations;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
use function cms_installer\endswith;
use function cms_installer\GetDataDictionary;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\startswith;

$corename = TemplateType::CORE;

// 1. Tweak callbacks for page and generic layout template types
$page_type = TemplateType::load($corename.'::page');
if ($page_type) {
    //TODO sometimes double-backslashes in a callable are not accepted by PHP
    $page_type->set_lang_callback('CMSMS\\internal\\std_layout_template_callbacks::page_type_lang_callback');
    $page_type->set_content_callback('CMSMS\\internal\\std_layout_template_callbacks::reset_page_type_defaults');
    $page_type->set_help_callback('CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $page_type->save();
} else {
    error_msg($corename.'::page template update '.lang('failed'));
}

$generic_type = TemplateType::load($corename.'::generic');
if ($generic_type) {
    $generic_type->set_lang_callback('CMSMS\\internal\\std_layout_template_callbacks::generic_type_lang_callback');
    $generic_type->set_help_callback('CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $generic_type->save();
} else {
    error_msg($corename.'::generic template update '.lang('failed'));
}

// Change to 4-byte-utf8 default charset
$query = 'ALTER DATABASE `'.$db->database.'` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';
$db->Execute($query);

// 2. Revised/extra permissions
$now = time();
$longnow = trim($db->DbTimeStamp($now),"'");
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_name=?,permission_text=?,modified_date=? WHERE permission_name=?';
$db->Execute($query, ['Manage User Plugins','Modify User-Defined Tags',$longnow,'Modify User-defined Tags']);
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_source=\'Core\' WHERE permission_source=NULL';
$db->Execute($query);
//$query = 'INSERT INTO '.CMS_DB_PREFIX.'permissions SET permission_name=?,permission_text=?';
//$db->Execute($query, ['Manage Jobs','Manage asynchronous jobs']);

$arr = ['View Admin Log']; // extras but not ultra
foreach ($arr as $one_perm) {
    $permission = new Permission();
    $permission->source = 'Core';
    $permission->name = $one_perm;
    $permission->text = ucfirst($one_perm); //TODO c.f. fresh installation text
    try {
        $permission->save();
    } catch (Throwable $t) {
        // nothing here
    }
}

//  'Modify Site Assets',
$ultras = [
    'Modify Database', //for db structure i.e. +/- tables, change table-propertiies
    'Modify Database Content',
    'Modify Restricted Files',
    'Remote Administration',  //for app management sans admin console
];

foreach ($ultras as $one_perm) {
    $permission = new Permission();
    $permission->source = 'Core';
    $permission->name = $one_perm;
    $permission->text = ucfirst($one_perm); //TODO c.f. fresh installation text
    try {
        $permission->save();
    } catch (Throwable $t) {
        // nothing here
    }
}

$group = new Group();
$group->name = 'CodeManager';
$group->description = lang('grp_coder_desc');
$group->active = 1;
try {
    $group->Save();
} catch (Throwable $t) {
    // nothing here
}
$group->GrantPermission($ultras[2]);
//$group->GrantPermission('Modify Site Assets');
$group->GrantPermission('Manage User Plugins');
/*
$group = new Group();
$group->name = 'AssetManager';
$group->description = 'Members of this group can add/edit/delete website asset-files';
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Assets');
*/

// 3. Update preferences
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
        AppParams::set('logintheme', $name);
        break;
    }
}

// migrate user-homepages like 'index.php*' to 'menu.php*'
$query = 'UPDATE '.CMS_DB_PREFIX."userprefs SET value = REPLACE(value,'index.php','menu.php') WHERE preference='homepage' AND value LIKE 'index.php%'";
$db->Execute($query);

// re-spaced task-related properties
$query = 'DELETE FROM '.CMS_DB_PREFIX."siteprefs WHERE sitepref_name LIKE 'Core::%'";
$db->Execute($query);
// revised 'namespace' indicator in recorded names c.f. AppParams::NAMESPACER
$query = 'UPDATE '.CMS_DB_PREFIX."siteprefs SET sitepref_name = REPLACE(sitepref_name,'_mapi_pref_','\\\\'), modified_date = ? WHERE sitepref_name LIKE '%\\_mapi\\_pref\\_%'";
$db->Execute($query,[$longnow]);

//$query = 'INSERT INTO '.CMS_DB_PREFIX.'siteprefs (sitepref_name,create_date,modified_date) VALUES (\'loginmodule\',?,?);';
//$db->Execute($query,[$longnow,$longnow]);
// almost-certainly-unique signature of this site
// this replicates cms_random_string()
/* $uuid = str_repeat(' ', 32);
for ($i = 0; $i < 32; ++$i) {
    $n = mt_rand(33, 165);
    switch ($n) {
        case 34:
        case 38:
        case 39:
        case 44:
        case 63:
        case 96:
        case 127:
            --$i;
            break;
        default:
            $uuid[$i] = chr($n);
    }
}
*/
// includer-defined variables: $app, $siteinfo;

$config = $app->get_config();
$corenames = $config['coremodules'];
$cores = implode(',', $corenames);
$url =  ( !empty($siteinfo['supporturl']) ) ? $siteinfo['supporturl'] : '';
$salt = Crypto::random_string(16, true);
$r = substr($salt, 0, 2);
$s = Crypto::random_string(32, false, true);
$uuid = strtr($s, '+/', $r);
$down = AppParams::get('enablesitedownmessage', 0); //for rename
$check = AppParams::get('use_smartycompilecheck', 1); //ditto

$arr = [
    'cdn_url' => 'https://cdnjs.cloudflare.com',
    'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
    'current_theme' => '', // frontend theme name
    'lock_refresh' => 120,
    'lock_timeout' => 60,
    'loginmodule' => '', // TODO CMSMS\ModuleOperations::STD_LOGIN_MODULE
    'loginprocessor' => '', // login UI defined by current theme
    'loginsalt' => $salt,
    'password_level' => 0, // p/w policy-type enumerator
    'site_help_url' => $url,
    'site_uuid' => $uuid, // almost-certainly-unique signature of this site
    'site_downnow' => $down, // renamed
    'smarty_cachelife' => -1, // smarty default
    'smarty_cachemodules' => 0, // nope
    'smarty_cacheusertags' => 0,
    'smarty_compilecheck' => $check, // renamed
    'syntax_theme'  => '',
    'ultraroles' => json_encode($ultras),
    'username_level' => 0, // username policy-type enumerator
];
foreach ($arr as $name=>$val) {
    AppParams::set($name, $val);
}

$dict = GetDataDictionary($db);

/* IF UDT-files are used exclusively instead of database storage ...
// 4. Convert UDT's to user-plugin files
$udt_list = $db->GetArray('SELECT name,description,code FROM '.CMS_DB_PREFIX.'userplugins');
if ($udt_list) {

    function create_user_plugin(array $row, UserPluginOperations $ops, $smarty)
    {
        $fp = $ops->FilePath($row['userplugin_name']);
        if (is_file($fp)) {
            verbose_msg('user-plugin named '.$row['userplugin_name'].' already exists');
            return;
        }

        $code = preg_replace(
                ['~^[\s\r\n]*<\\?php\s*[\r\n]*~i', '~[\s\r\n]*\\?>[\s\r\n]*$~', '~echo~'],
                ['', '', 'return'], $row['code']);
        if (!$code) {
            verbose_msg('UDT named '.$row['userplugin_name'].' is empty, and will be discarded');
            return;
        }

        $params = ['id' => $ops::MAXFID, 'code'=>$code];
        if ($row['description']) {
            $desc = trim($row['description'], " \t\n\r");
            if ($desc) {
                $params['description'] = $desc;
            }
        }

        $res = $ops->SetUserTag($row['userplugin_name'], $params);
        if ((is_array($res) && $res[0]) || ($res && !is_array($res))) {
            verbose_msg('Converted UDT '.$row['userplugin_name'].' to a plugin file');
        } else {
            verbose_msg('Error saving UDT named '.$row['userplugin_name']);
        }
    }

    $ops = AppSingle::UserTagOperations();
    //$smarty defined upstream, used downstream
    foreach ($udt_list as $udt) {
        create_user_plugin($udt, $ops, $smarty);
    }

    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins');
    $dict->ExecuteSQLArray($sqlarr);
    status_msg('Converted User Defined Tags to user-plugin files');
}
ELSE | AND
// 4. Re-format the content of pre-existing 2.3BETA UDTfiles in their folder ?
*/
// ensure the user_plugins folder includes a .htaccess file
$ops = AppSingle::UserTagOperations();
$fp = $ops->FilePath('.htaccess');
if (!is_file($fp)) {
    $ext = strtr($ops::PLUGEXT, '.', '');
    file_put_contents($fp, <<<EOS
RedirectMatch 403 (?i)^.*\.($ext|cmsplugin)$
EOS
    );
}

$tbl = CMS_DB_PREFIX.'userplugins';
$sqlarray = $dict->AlterColumnSQL($tbl,'userplugin_id','id I(2) UNSIGNED AUTO KEY');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL($tbl,'userplugin_name','name C(255)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL($tbl,'description T(1023)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL($tbl,'create_date DT DEFAULT CURRENT_TIMESTAMP');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL($tbl,'modified_date DT ON UPDATE CURRENT_TIMESTAMP');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL($tbl,'parameters T(1023) AFTER description');
$dict->ExecuteSQLArray($sqlarray);

// 5. Cleanup plugins - remove reference from function-argument where appropriate
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

// 6. Drop redundant sequence-tables
foreach ([
    'additional_users_seq',
    'admin_bookmarks_seq',
    'content_props_seq',
    'event_handler_seq',
    'events_seq',
    'groups_seq',
    'group_perms_seq',
    'permissions_seq',
    'userplugins_seq',
] as $tbl) {
    $db->DropSequence(CMS_DB_PREFIX.$tbl);
}

// 6A Migrate some formerly genID-populated index-fields to autoincrement
foreach ([
    ['additional_users','additional_users_id'],
    ['admin_bookmarks','bookmark_id'],
    ['event_handlers','handler_id'],
    ['events','event_id'],
    ['groups','group_id'],
    ['group_perms','group_perm_id'],
    ['permissions', 'permission_id'],
//    ['userplugins', 'id'], see above
] as $tbl) {
    $sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.$tbl[0], $tbl[1].' I(2) UNSIGNED AUTO KEY');
    $dict->ExecuteSQLArray($sqlarray);
}

// 7. Other table revisions
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci']; //i.e. case-insensitive matching unless overridden
$asciitaboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET ascii COLLATE ascii_bin'];
// 7.1 Misc

// redundant fields
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'content','collapsed');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'content','prop_names');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'modules','status');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'modules','allow_fe_lazyload');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'modules','allow_admin_lazyload');
$dict->ExecuteSQLArray($sqlarray);
$sql = 'UPDATE '.CMS_DB_PREFIX.'users SET active = 0 WHERE active = 1 AND admin_access = 0';
$db->Execute($sql);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'users','admin_access');
$dict->ExecuteSQLArray($sqlarray);

// extra fields
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'adminlog','severity I(1) UNSIGNED NOTNULL DEFAULT 0 AFTER timestamp');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'content','template_type C(64) AFTER template_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'content','styles C(48) AFTER accesskey');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'layout_templates','hierarchy C(64) COLLATE ascii_general_ci AFTER description');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'layout_tpl_categories', 'created I'); //for datetime migration, below
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'users','oldpassword C(128) AFTER email'); //for p/w differentiation
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'users','passmodified_date DT'); //for p/w timeout
$dict->ExecuteSQLArray($sqlarray);

// modified fields
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'adminlog','user_id I(2) UNSIGNED');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'adminlog','username C(80)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL(CMS_DB_PREFIX.'adminlog','item_name','subject','C(255)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL(CMS_DB_PREFIX.'adminlog','action','message','X(511)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'modules','version C(16)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'module_deps','minimum_version C(16)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL(CMS_DB_PREFIX.'routes','created','create_date','DT DEFAULT CURRENT_TIMESTAMP');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'users','username C(80)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'users','password C(128)');
$dict->ExecuteSQLArray($sqlarray);

// backup each user's create_date to passmodified_date
$sql = 'UPDATE '.CMS_DB_PREFIX.'users SET passmodified_date = create_date';
$db->Execute($sql);

// 7.2 Migrate timestamp fields to auto-update datetime
function migrate_stamps(string $name, string $fid, $db, $dict)
{
    $tbl = CMS_DB_PREFIX.$name;
    $sqlarray = $dict->AddColumnSQL($tbl, 'create_date DT DEFAULT CURRENT_TIMESTAMP');
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->AddColumnSQL($tbl, 'modified_date DT ON UPDATE CURRENT_TIMESTAMP');
    $dict->ExecuteSQLArray($sqlarray);

    $data = $db->GetAssoc('SELECT '.$fid.',created,modified FROM '.$tbl);
    if ($data) {
        $sql = 'UPDATE '.$tbl.' SET create_date=?, modified_date=? WHERE '.$fid.'=?';
        $dt = new DateTime('@0',NULL);
        $fmt = 'Y-m-d H:i:s';
        foreach ($data as $id => &$row) {
            $t1 = (int)$row['created'];
            $t2 = max($t1,(int)$row['modified']);
            if ($t1 == 0) { $t1 = $t2; }
            $dt->setTimestamp($t1);
            $created = $dt->format($fmt);
            $dt->setTimestamp($t2);
            $modified = $dt->format($fmt);
            $db->Execute($sql, [$created,$modified,$id]);
        }
        unset($row);
    }

    $sqlarray = $dict->DropColumnSQL($tbl, 'created');
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->DropColumnSQL($tbl, 'modified');
    $dict->ExecuteSQLArray($sqlarray);
}

foreach ([
//  ['layout_designs','id'],
    ['layout_stylesheets','id'],
    ['layout_templates','id'],
    ['layout_tpl_type','id'],
    ['layout_tpl_categories','id'],
    ['locks','id'],
] as $tbl) {
    migrate_stamps($tbl[0],$tbl[1],$db,$dict);
}

// 7.3 Re-organize layout-related tables
// template-groups table tweaks
$tbl = CMS_DB_PREFIX.TemplatesGroup::TABLENAME; //layout_tpl_groups
$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_tpl_categories', $tbl);
$return = $dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL($tbl, 'item_order');
$dict->ExecuteSQLArray($sqlarray);

$tbl2 = CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE; //layout_tplgroup_members
$flds = '
id I(2) UNSIGNED AUTO KEY,
group_id I(2) UNSIGNED NOT NULL,
tpl_id I(2) UNSIGNED NOT NULL,
item_order I(2) UNSIGNED DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($tbl2, $flds, $asciitaboptarray);
$return = $dict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? lang('done') : lang('failed');
verbose_msg(lang('install_created_table', 'layout_tplgroup_members', $msg_ret));

$sqlarray = $dict->CreateIndexSQL('idx_layout_grp_tpls', $tbl2, 'tpl_id');
$return = $dict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? lang('done') : lang('failed');
verbose_msg(lang('install_creating_index', 'idx_layout_grp_tpls', $msg_ret));

// migrate existing category_id values to new table
$tbl = CMS_DB_PREFIX.TemplateOperations::TABLENAME; //layout templates
$query = 'SELECT id,category_id FROM '.$tbl.' WHERE category_id IS NOT NULL';
$data = $db->GetArray($query);
if ($data) {
    $query = 'INSERT INTO '.$tbl2.' (group_id,tpl_id) VALUES (?,?)';
    foreach ($data as $row) {
        $db->Execute($query, [$row['category_id'], $row['id']]);
    }
}

$sqlarray = $dict->DropColumnSQL($tbl, 'category_id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL($tbl, 'originator C(32) AFTER id');
$dict->ExecuteSQLArray($sqlarray);
$query = 'UPDATE '.$tbl.' T SET originator = (SELECT originator FROM '.CMS_DB_PREFIX.'layout_tpl_type TY WHERE T.type_id=TY.id)';
$db->Execute($query);
$sqlarray = $dict->AddColumnSQL($tbl, 'contentfile I(1) DEFAULT 0 AFTER listable');
$dict->ExecuteSQLArray($sqlarray);

// templates table indices
// replace this 'unique' by non- (_3 below becomes the validator)
$sqlarray = $dict->DropIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_1', $tbl);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('idx_layout_tpl_1', $tbl, 'name');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('idx_layout_tpl_3', $tbl, 'originator,name', ['UNIQUE']);
$dict->ExecuteSQLArray($sqlarray);
// content table index used by name
$sqlarray = $dict->DropIndexSQL(CMS_DB_PREFIX.'index_content_by_idhier', CMS_DB_PREFIX.'content');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('idx_content_by_idhier',
    CMS_DB_PREFIX.'content', 'content_id,hierarchy');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.StylesheetOperations::TABLENAME,'contentfile I(1) DEFAULT 0'); //layout stylesheets
$dict->ExecuteSQLArray($sqlarray);

$tbl = CMS_DB_PREFIX.StylesheetsGroup::TABLENAME; //layout_css_groups
$flds = '
id I(4) AUTO KEY,
name C(64),
description X(1024),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$tbl = CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE; //layout_cssgroup_members
$flds = '
id I(2) UNSIGNED AUTO KEY,
group_id I(2) UNSIGNED NOT NULL,
css_id I(2) UNSIGNED NOT NULL,
item_order I(2) UNSIGNED DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $asciitaboptarray);
$dict->ExecuteSQLArray($sqlarray);

// migrate design stylesheets to layout tables and pages as respective groups
// this must be before any DesignManager-module upgrade which renames the design-related tables
$designs = $db->GetAssoc('SELECT id,name,created,modified FROM '.CMS_DB_PREFIX.'layout_designs');
if ($designs) {
    $dt = new DateTime('@0',NULL);
    $fmt = 'Y-m-d H:i:s';
    $now = time();
    foreach ($designs as &$row) {
        $t1 = (int)$row['created'];
        $t2 = max($t1,(int)$row['modified']);
        if ($t1 == 0) { $t1 = $t2; }
        if ($t1 == 0) { $t1 = $t2 = $now; }
        $dt->setTimestamp($t1);
        $row['create_date'] = $dt->format($fmt);
        $dt->setTimestamp($t2);
        $row['modified_date'] = $dt->format($fmt);
    }
    unset($row);

    $templates = $db->GetArray('SELECT A.* FROM '.
        CMS_DB_PREFIX.'layout_design_tplassoc A LEFT JOIN '.
        CMS_DB_PREFIX.'layout_templates T ON A.tpl_id=T.id ORDER BY A.design_id,T.name');
    if ($templates) {
        $bank = [];
        foreach ($templates as &$row) {
            $id = (int)$row['design_id'];
            if (!isset($bank[$id])) {
                $bank[$id] = [];
            }
            $bank[$id][] = (int)$row['tpl_id'];
        }
        foreach ($bank as $id => &$row) {
            $ob = new TemplatesGroup();
            $ob->set_properties([
                'name'=>$designs[$id]['name'],
                'description'=>'Templates mirrored from design',
                'create_date'=>$designs[$id]['create_date'],
                'modified_date'=>$designs[$id]['modified_date']
            ]);
            $ob->set_members($row);
            $ob->save();
        }
        unset($row);
    }

    $sheets = $db->GetArray('SELECT design_id,css_id FROM '.CMS_DB_PREFIX.'layout_design_cssassoc ORDER BY design_id,item_order');
    if ($sheets) {
        $bank = [];
        foreach ($sheets as &$row) {
            $id = (int)$row['design_id'];
            if (!isset($bank[$id])) {
                $bank[$id] = [];
            }
            $bank[$id][] = (int)$row['css_id'];
        }
        $trans = [];
        foreach ($bank as $id => &$row) {
            $ob = new StylesheetsGroup();
            $ob->set_properties([
                'name'=>$designs[$id]['name'],
                'description'=>'Stylesheets mirrored from design',
                'create_date'=>$designs[$id]['create_date'],
                'modified_date'=>$designs[$id]['modified_date']
            ]);
            $ob->set_members($row);
            $ob->save();
            $trans[$id] = $ob->get_id();
        }
        unset($row);

        $pages = $db->GetAssoc('SELECT C.content_id,P.content AS design_id FROM '.
            CMS_DB_PREFIX.'content C JOIN '.
            CMS_DB_PREFIX.'content_props P ON C.content_id=P.content_id WHERE P.prop_name=\'design_id\'');
        if ($pages) {
            $stmt = $db->Prepare('UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?');
            foreach ($pages as $id => $did) {
                if (!empty($trans[$did])) {
                    $db->Execute($stmt,[-$trans[$did],$id]); //group id's recorded < 0
                }
            }
            $stmt->close();
//NOT YET   $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'content_props WHERE prop_name=\'design_id\'');
        }
    }
}

// migrate module templates to layout-templates table
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_templates ORDER BY module_name,template_name';
$data = $db->GetArray($query);
if ($data) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'layout_templates
(originator,name,content,type_id,create_date)
VALUES (?,?,?,?,?)';
    $types = [];
    $now = time();
    foreach ($data as $row) {
        $name = $row['module_name'];
        if (!isset($types[$name])) {
            // TemplateType::TABLENAME.
            $db->Execute('INSERT INTO '.CMS_DB_PREFIX.'layout_tpl_type
(originator,name,description,owner)
VALUES (?,?,?,-1)',
            [
                $name,
                'Moduleaction',
                'Action templates for module: '.$name,
            ]);
            $types[$name] = $db->insert_id();
        }
        $db->Execute($query, [
            $name,
            $row['template_name'],
            $row['content'],
            $types[$name],
            $row['create_date'],
            $row['modified_date'],
        ]);
    }
    verbose_msg(lang('upgrade_modifytable', 'module_templates'));
}

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_templates');
$dict->ExecuteSQLArray($sqlarray);

$sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'version');
$dict->ExecuteSQLArray($sqlarr);

$tbl = CMS_DB_PREFIX.'content_types';
$flds = '
id I(2) UNSIGNED AUTO KEY,
originator C(32) NOTNULL,
name C(24) NOTNULL,
publicname_key C(64),
displayclass C(255) NOTNULL,
editclass C(255)
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $asciitaboptarray);
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->CreateIndexSQL('idx_typename', $tbl, 'name', ['UNIQUE']);
$dict->ExecuteSQLArray($sqlarray);

//data field holds a serialized class, size 1024 is probably enough
$flds = '
id I UNSIGNED AUTO KEY,
name C(255) NOTNULL,
module C(48),
created I UNSIGNED NOTNULL,
start I UNSIGNED NOTNULL,
until I UNSIGNED DEFAULT 0,
recurs I(2) UNSIGNED,
errors I(2) UNSIGNED DEFAULT 0 NOTNULL,
data X(16383)
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'asyncjobs', $flds, $asciitaboptarray);
$dict->ExecuteSQLArray($sqlarray);
verbose_msg(lang('TODO table', 'asyncjobs'));

// 7.4 Events
//redundant duplicate index
$sqlarray = $dict->DropIndexSQL(CMS_DB_PREFIX.'event_id', CMS_DB_PREFIX.'events');
$dict->ExecuteSQLArray($sqlarray);
// migrate event-initiator CmsJobManager::OnJobFailed to Core::JobFailed
//$query = 'UPDATE '.CMS_DB_PREFIX."events SET originator='Core',event_name='JobFailed' WHERE originator='CmsJobManager' AND event_name='OnJobFailed'";
//$db->Execute($query);
// callable event handlers
$tbl = CMS_DB_PREFIX.'event_handlers';
$sqlarray = $dict->AddColumnSQL($tbl, 'type C(1) NOT NULL DEFAULT "C"');
$dict->ExecuteSQLArray($sqlarray);
$query = 'UPDATE '.$tbl.' SET type="M" WHERE module_name IS NOT NULL';
$db->Execute($query);
$query = 'UPDATE '.$tbl.' SET type="U" WHERE tag_name IS NOT NULL';
$db->Execute($query);
$sqlarray = $dict->RenameColumnSQL($tbl, 'module_name', 'class', 'C(96)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL($tbl, 'tag_name', 'func', 'C(64)');
$dict->ExecuteSQLArray($sqlarray);
verbose_msg(lang('upgrade_modifytable', 'event_handlers'));

// 7.5 Module plugins
$tbl = CMS_DB_PREFIX.'module_smarty_plugins';
$sqlarray = $dict->DropColumnSQL($tbl,'sig');
$dict->ExecuteSQLArray($sqlarray);
//NOT YET
//$sqlarray = $dict->DropColumnSQL($tbl,'cachable');
//$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropIndexSQL(CMS_DB_PREFIX.'idx_smp_module', $tbl);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL($tbl,'id I(2) UNSIGNED FIRST AUTO KEY');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL($tbl,"name C(48) COLLATE 'utf8mb4_bin' NOT NULL"); //case-sensitive
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('idx_tagname',$tbl,'name,module',['UNIQUE']);
$dict->ExecuteSQLArray($sqlarray);

// remove duplicates (now we have caseless tagname-matching)
$db->Execute("DELETE T1 FROM $tbl T1 INNER JOIN $tbl T2
WHERE T1.id > T2.id AND UPPER(T1.name) = UPPER(T2.name) AND UPPER(T1.module) = UPPER(T2.module)");
// convert callbacks from serialized to plain string
$rows = $db->GetArray('SELECT id,module,callback FROM '.$tbl);
foreach ($rows as &$row) {
    $val = unserialize($row['callback']);
    if ($val) {
        //CHECKME use generic spacer '\\\\' ?
        if (is_array($val)) {
            $s = $val[0].'::'.$val[1]; // OR '\\\\' ?
        } elseif (is_string($val)) {
            if (($p = strpos($val,'::')) === false) {
                $s = $row['module'].'::'.$val;
            } elseif (p === 0) {
                $s = $row['module'].$val;
            } else {
                $s = $val;
            }
        } else {
            $s = NULL;
        }
    } else {
        $s = NULL;
    }
    if ($s) {
        $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_smarty_plugins SET callback=? WHERE id=?',[$s,$row['id']]);
    } else {
        $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_smarty_plugins WHERE id=?',[$row['id']]);
    }
}
unset($row);

// 7.6 routes
$tbl = CMS_DB_PREFIX.'routes';
$sqlarray = $dict->DropIndexSQL('PRIMARY',$tbl);
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->AddColumnSQL($tbl,'id I(2) UNSIGNED FIRST AUTO KEY');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL($tbl,'data','object','X(512)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL($tbl,'data X(512) AFTER object');
$dict->ExecuteSQLArray($sqlarray);

$query = 'SELECT id,object FROM '.$tbl;
$data = $db->GetAssoc($query);
$query = 'UPDATE '.$tbl.' SET data=? WHERE id=?';
foreach ($data as $id => $val) {
    $obj = unserialize($val,[]);
    $arr = (array)$obj;
    $raw = reset($arr);
    $cooked = json_encode($raw, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $db->Execute($query, [$cooked,$id]);
}

$sqlarray = $dict->DropColumnSQL($tbl,'object');
$dict->ExecuteSQLArray($sqlarray);

// extra static events

foreach ([
//    'JobFailed',
    'CheckUserData',
    'MetadataPostRender',
    'MetadataPreRender',
    'PageTopPostRender',
    'PageTopPreRender',
    'PageHeadPostRender',
    'PageHeadPreRender',
    'PageBodyPostRender',
    'PageBodyPreRender',
    'PostRequest',
] as $s) {
    Events::CreateEvent('Core', $s);
}

Events::AddStaticHandler('Core', 'PostRequest', '\\CMSMS\\internal\\JobOperations::begin_async_work', 'C', false);
Events::AddStaticHandler('Core', 'ModuleInstalled', '\\CMSMS\\internal\\JobOperations::event_handler', 'C', false);
Events::AddStaticHandler('Core', 'ModuleUninstalled', '\\CMSMS\\internal\\JobOperations::event_handler', 'C', false);
Events::AddStaticHandler('Core', 'ModuleUpgraded', '\\CMSMS\\internal\\JobOperations::event_handler', 'C', false);

//8. de-entitize some recorded values

$from = explode('|',
 '&amp;|&quot;|&apos;|&lt;|&gt;'
);
$to = explode('|',
 '&|"|\'|<|>'
);
$from1 = array_map(function($s) { return "/$s/"; }, explode('|',
 '&#0*38;|&#0*34;|&#0*39;|&#0*60;|&#0*62;'
));
$from2 = array_map(function($s) { return "/$s/i"; }, explode('|',
 '&#x0*26;|&#x0*22;|&#x0*27;|&#x0*3c;|&#x03e*;' //caseless
));
//old cleanValue() extras
$from3 = explode('|',
 '&#37;|&#40;|&#41;|&#43;|&#45;'
);
$to3 = explode('|',
 '%|(|)|+|-'
);
$dentit = function($s) use($from, $to, $from1, $from2, $from3, $to3)
{
    $s = str_replace($from, $to, $s);
    $s = str_replace($from3, $to3, $s);
    $s = preg_replace($from1, $to, $s);
    $s = preg_replace($from2, $to, $s);
    return $s;
};
/*
cleanValue() in
admin/addbookmark.php
admin/addgroup.php
admin/adduser.php
admin/adminlog.php
admin/ajax_alerts.php
admin/editbookmark.php
admin/editevent.php
admin/editgroup.php
admin/edituser.php
admin/editusertag.php
admin/listtags.php
admin/login.php
admin/myaccount.php
admin/siteprefs.php
admin/themes/OneEleven/OneElevenTheme.php
modules/FilePicker/action.ajax_cmd.php
modules/FilePicker/action.filepicker.php
modules/News/action.default.php

hence in tables CMS_DB_PREFIX.*
 admin_bookmarks :: STET title url
 groups :: STET group_name group_desc
 users :: username STET first_name last_name
 siteprefs :: sitepref_name? sitepref_value
 userprefs :: value
 userplugins :: name STET description
*/
$pref = CMS_DB_PREFIX;
foreach ([
    ['admin_bookmarks', 'bookmark_id', 'title', 'url'], // url should be [raw]urlencode() etc ?
    ['groups', 'group_id', 'group_name', 'group_desc'],
    ['users', 'user_id', 'username', 'first_name', 'last_name'],
    ['siteprefs', 'sitepref_name', 'sitepref_name', 'sitepref_value'],
    ['userprefs', 'user_id', 'value'],
    ['userplugins', 'id', 'name', 'description'], // name should be cms_installer\sanitizeVal( ,CMSSAN_FILE) description >> , CMSSAN_PURE
/* these probably never cleanValue()'d
    ['layout_css_groups', 'id', 'name', 'description'],
    ['layout_stylesheets', 'id', 'name', 'description'],
    ['layout_templates', 'id', 'name', 'description'],
    ['layout_tpl_type', 'id', 'name', ' description'],
    ['layout_tpl_groups', 'id', 'name', 'description'],
    [DesignManager\Design::TABLENAME, 'id', 'name', 'description'],
*/
] as $tbl) {
    $flds = array_slice($tbl, 1);
    $sels = implode(',', $flds);
    array_shift($flds);
    $checks = implode(' LIKE \'%&%\' OR ', $flds); // NOT wildcarded
    $query = "SELECT $sels FROM {$pref}{$tbl[0]} WHERE $checks LIKE '%&%'";
    $rows = $db->GetArray($query);
    if ($rows) {
        $updates = implode('=?,', $flds);
        $stmt = $db->Prepare("UPDATE $pref{$tbl[0]} SET {$updates}=? WHERE {$tbl[1]}=?");
        foreach ($rows as $row) {
            $upd = false;
            $id = array_shift($row);
            foreach ($row as &$val) {
                $tmp = $dentit($val);
                if ($tmp != $val) {
                    $val = $tmp;
                    $upd = true;
                }
/*              if (NEEDED) {
                    $val = DO MORE TO($val)
                    $upd = true;
                }
*/
            }
            unset($val);
            if ($upd) { // any change
                $db->Execute($stmt, $row + [999=>$id]);
            }
        }
        $stmt->close();
    }
}

/* TODO improve 2.2.15 users' homepage URL upgrade
// remove old secure param name from homepage url
$url = str_replace('&amp;','&',$url);
$tmp = explode('?',$url);
@parse_str($tmp[1],$tmp2);
$arr = array_keys($tmp2);
// param names have been: '_s_','sp_','_sx_','_sk_','__c','_k_'
foreach (['_s_','sp_','_sx_','_sk_','__c','_k_'] as $sk) {
    if( in_array($sk,$arr) ) unset($tmp2[$sk]);
}

foreach( $tmp2 as $k => $v ) {
    $tmp3[] = $k.'='.$v;
}
$url = $tmp[0].'?'.implode('&',$tmp3);
//remove admin folder from the url (if applicable)
//(url should be relative to admin dir)
$url = preg_replace('@^/[^/]+/@','',$url);
*/

//if ($return == 2) {
    $query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 206';
    $db->Execute($query);
//}
