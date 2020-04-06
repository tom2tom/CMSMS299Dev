<?php

use CMSMS\Events;
use CMSMS\Group;
use CMSMS\SimpleTagOperations;
use CMSMS\StylesheetOperations;
use CMSMS\StylesheetsGroup;
use CMSMS\TemplateOperations;
use function cms_installer\endswith;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\startswith;

$dict = GetDataDictionary($db);

// 1. Tweak callbacks for page and generic layout template types
$page_type = CmsLayoutTemplateType::load('__CORE__::page');
if ($page_type) {
    $page_type->set_lang_callback('CMSMS\\internal\\std_layout_template_callbacks::page_type_lang_callback');
    $page_type->set_content_callback('CMSMS\\internal\\std_layout_template_callbacks::reset_page_type_defaults');
    $page_type->set_help_callback('CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $page_type->save();
} else {
    error_msg('__CORE__::page template update '.lang('failed'));
}

$generic_type = CmsLayoutTemplateType::load('__CORE__::generic');
if ($generic_type) {
    $generic_type->set_lang_callback('CMSMS\\internal\\std_layout_template_callbacks::generic_type_lang_callback');
    $generic_type->set_help_callback('CMSMS\\internal\\std_layout_template_callbacks::template_help_callback');
    $generic_type->save();
} else {
    error_msg('__CORE__::generic template update '.lang('failed'));
}

// 2. Revised/extra permissions
$now = time();
$longnow = trim($db->DbTimeStamp($now),"'");
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_name=?,permission_text=?,modified_date=? WHERE permission_name=?';
$db->Execute($query, ['Modify User Plugins','Modify User-Defined Tags',$longnow,'Modify User-defined Tags']);
$query = 'UPDATE '.CMS_DB_PREFIX.'permissions SET permission_source=\'Core\' WHERE permission_source=NULL';
$db->Execute($query);

foreach ([
 'Modify DataBase Direct',
 'Modify Restricted Files',
// 'Modify Site Assets',
 'Remote Administration',  //for app management sans admin console
] as $one_perm) {
    $permission = new CmsPermission();
    $permission->source = 'Core';
    $permission->name = $one_perm;
    $permission->text = $one_perm;
    try {
        $permission->save();
    } catch (Exception $e) {
        // nothing here
    }
}

$group = new Group();
$group->name = 'CodeManager';
$group->description = lang('grp_coder_desc');
$group->active = 1;
try {
    $group->Save();
} catch (Exception $e) {
    // nothing here
}
$group->GrantPermission('Modify Restricted Files');
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
        cms_siteprefs::set('logintheme', $name);
        break;
    }
}

//re-spaced task-related properties
$query = 'DELETE FROM '.CMS_DB_PREFIX.'siteprefs WHERE sitepref_name LIKE "Core::%"';
$db->Execute($query);
//revised 'namespace' indicator in recorded names
$query = 'UPDATE '.CMS_DB_PREFIX.'siteprefs SET sitepref_name = REPLACE(sitepref_name,"_mapi_pref_","\\\\"), modified_date = ? WHERE sitepref_name LIKE "%_mapi_pref_%"';
$db->Execute($query,[$longnow]);

//$query = 'INSERT INTO '.CMS_DB_PREFIX.'siteprefs (sitepref_name,create_date,modified_date) VALUES (\'loginmodule\',?,?);';
//$db->Execute($query,[$longnow,$longnow]);
// almost-certainly-unique signature of this site
// this replicates cms_utils::random_string()
$uuid = str_repeat(' ', 32);
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

$app = get_app();
$config = $app->get_config();
$cores = $config['coremodules'];
$str = implode(',', $cores);
foreach([
    'cdn_url' => 'https://cdnjs.cloudflare.com',
	'coremodules', $str,
    'syntax_theme'  => '',
    'lock_refresh' => 120,
    'lock_timeout' => 60,
    'loginmodule' => '',
    'site_help_url' => '',
    'site_uuid' => $uuid,
    'smarty_cachelife' => -1, // smarty default
 ] as $name=>$val) {
    cms_siteprefs::set($name, $val);
}

/* IF UDTfiles are used exclusively instead of database storage ...
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

        if ($ops->SetSimpleTag($row['userplugin_name'], $params)) {
            verbose_msg('Converted UDT '.$row['userplugin_name'].' to a plugin file');
        } else {
            verbose_msg('Error saving UDT named '.$row['userplugin_name']);
        }
    }

    $ops = SimpleTagOperations::get_instance();
    //$smarty defined upstream, used downstream
    foreach ($udt_list as $udt) {
        create_user_plugin($udt, $ops, $smarty);
    }

    $db->DropSequence(CMS_DB_PREFIX.'userplugins_seq'); see  below
    $sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins');
    $dict->ExecuteSQLArray($sqlarr);
    status_msg('Converted User Defined Tags to user-plugin files');
}
ELSE | AND
// 4. Re-format the content of pre-existing 2.3BETA UDTfiles in their folder ?
*/
// ensure the simple_plugins folder includes a .htaccess file
$ops = SimpleTagOperations::get_instance();
$fp = $ops->FilePath('.htaccess');
if (!is_file($fp)) {
	$ext = strtr($ops::PLUGEXT, '.', '');
	file_put_contents($fp, <<<EOS
RedirectMatch 403 (?i)^.*\.($ext|cmsplugin)$
EOS
	);
}
// redundant sequence-table
$sqlarr = $dict->DropTableSQL(CMS_DB_PREFIX.'userplugins_seq');
$dict->ExecuteSQLArray($sqlarr);

$tbl = CMS_DB_PREFIX.'simpleplugins';
$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'userplugins',$tbl);
$dict->ExecuteSQLArray($sqlarr);
$sqlarray = $dict->RenameColumnSQL($tbl,'userplugin_id','id I AUTO KEY');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL($tbl,'userplugin_name','name C(255)');
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
$db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
$db->DropSequence(CMS_DB_PREFIX.'userplugins_seq');

// 7. Other table revisions
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci']; //i.e. case-insensitive matching unless overridden

// 7.1 Misc

// redundant fields
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'content','collapsed');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'content','prop_names');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'modules','allow_fe_lazyload');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL(CMS_DB_PREFIX.'modules','allow_admin_lazyload');
$dict->ExecuteSQLArray($sqlarray);

// extra fields
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'content','styles C(48)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'layout_tpl_categories', 'created I'); //for datetime migration, below
$dict->ExecuteSQLArray($sqlarray);

// modified fields
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'users','username C(80)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AlterColumnSQL(CMS_DB_PREFIX.'users','password C(128)');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->RenameColumnSQL(CMS_DB_PREFIX.'routes','created','create_date','DT DEFAULT CURRENT_TIMESTAMP');
$dict->ExecuteSQLArray($sqlarray);

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
//    ['layout_designs','id'],
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
$tbl = CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME; //layout_tpl_groups
$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_tpl_categories', $tbl);
$return = $dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropColumnSQL($tbl, 'item_order');
$dict->ExecuteSQLArray($sqlarray);

$tbl2 = CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE; //layout_tplgroup_members
$flds = '
id I(2) UNSIGNED AUTO KEY,
group_id I(2) UNSIGNED NOT NULL,
tpl_id I(2) UNSIGNED NOT NULL,
item_order I(2) UNSIGNED DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($tbl2, $flds, $taboptarray);
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
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
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
            $ob = new CmsLayoutTemplateCategory();
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
(originator,name,content,type_id,create_date,modified_date)
VALUES (?,?,?,?,?,?)';
    $types = [];
    $now = time();
    foreach ($data as $row) {
        $name = $row['module_name'];
        if (!isset($types[$name])) {
            // CmsLayoutTemplateType::TABLENAME.
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

$tbl = CMS_DB_PREFIX.'content_types';
$flds = '
id I(2) UNSIGNED AUTO KEY,
originator C(32) NOTNULL,
name C(24) NOTNULL,
publicname_key C(64),
displayclass C(255) NOTNULL COLLATE ascii_bin,
editclass C(255) COLLATE ascii_bin
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->CreateIndexSQL('idx_typename', $tbl, 'name', ['UNIQUE']);
$dict->ExecuteSQLArray($sqlarray);

// 7.4 Events
//redundant duplicate index
$sqlarray = $dict->DropIndexSQL(CMS_DB_PREFIX.'event_id', CMS_DB_PREFIX.'events');
$dict->ExecuteSQLArray($sqlarray);
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
$sqlarray = $dict->AlterColumnSQL($tbl,"name C(48) COLLATE 'utf8_bin' NOT NULL"); //case-sensitive
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

Events::CreateEvent('Core','MetadataPostRender');
Events::CreateEvent('Core','MetadataPreRender');
Events::CreateEvent('Core','PageTopPostRender');
Events::CreateEvent('Core','PageTopPreRender');
Events::CreateEvent('Core','PageHeadPostRender');
Events::CreateEvent('Core','PageHeadPreRender');
Events::CreateEvent('Core','PageBodyPostRender');
Events::CreateEvent('Core','PageBodyPreRender');
Events::CreateEvent('Core','PostRequest');

//if ($return == 2) {
    $query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 206';
    $db->Execute($query);
//}
