<?php

// access to CMSMS 2.99+ API is needed
use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\Permission;
use CMSMS\SingleItem;
use CMSMS\TemplateType;
use function cms_installer\endswith;
use function cms_installer\get_server_permissions;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function cms_installer\startswith;
use function cms_module_path;

$done = lang('done');
$failed = lang('failed');
$dict = $db->NewDataDictionary(); //OR new DataDictionary($db);

// 1. Database changes

// change default charset to 4-byte-utf8
// tho' db server might not be authorized to read the following
$sql = 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE table_schema = \''.$db->database.'\' AND table_collation = \'utf8_general_ci\'';
$names = $db->getCol($sql);
if ($names) {
    // alter collation for each table

    foreach ($names as $tbl) {
        //dictionary doesn't support changing table-options only (i.e. no field def'ns)
        $db->execute("ALTER TABLE `$tbl` CHARACTER SET utf8mb4");
    }
}

// TABLE_NAME COLUMN_NAME COLUMN_DEFAULT IS_NULLABLE CHARACTER_SET_NAME COLLATION_NAME
// DATA_TYPE CHARACTER_MAXIMUM_LENGTH or COLUMN_TYPE
$sql = 'SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema = \''.$db->database.'\' AND collation_name = \'utf8_general_ci\'';
$names = $db->getArray($sql);
if ($names) {
    // alter collation for each table-column (200+)
    foreach ($names as $tbl) {
        //TODO $sqlarray = $dict->AlterColumnSQL() - prob need respective full-column-def'n
        $s = $tbl['TABLE_NAME'];
        $r = $tbl['COLUMN_NAME'];
        $res = $db->execute("ALTER TABLE '$s' MODIFY COLUMN $r CHARACTER SET utf8mb4");
    }
}
$sql = 'ALTER DATABASE `'.$db->database.'` DEFAULT CHARACTER SET utf8mb4';
$db->execute($sql);

// drop misnamed index on (content_id,hierarchy) in content table
// e.g. CMS_DB_PREFIX.'index_content_by_idhier'
// the appropriate replacement-index will be created downstream
$iname = '';
$sql = 'SHOW INDEX FROM '.CMS_DB_PREFIX.'content WHERE Column_name = \'content_id\' OR Column_name = \'hierarchy\'';
$data = $db->getArray($sql);
if ($data) {
    $out = [];
    // want $data['Key_name'] field-value which applies to both $data['Column_name'] values 'content_id' and 'hierarchy'
    foreach($data as $row) {
        $r = $row['Column_name'];
        if ($r == 'content_id' || $r == 'hierarchy') {
            $s = $row['Key_name'];
            if (!isset($out[$s])) { $out[$s] = $r; }
            else { $iname = $s; break; }
        }
    }
} else {
    // manual fallback
    $iname = CMS_DB_PREFIX.'index_content_by_idhier';
}
if ($iname) {
    $sqlarray = $dict->DropIndexSQL($iname, CMS_DB_PREFIX.'content');
    $return = $dict->ExecuteSQLArray($sqlarray);
    if ($return != 2) {
        error_msg('Index '.$iname.' removal '.$failed);
    }
} else {
    error_msg('Cannot identify content-table index (content_id,hierarchy), for removal');
}

// the extensive table-tuning here is mostly done via mysqli-object direct usage
$handle = $db->get_inner_mysql();
$tblprefix = CMS_DB_PREFIX;
require_once __DIR__.DIRECTORY_SEPARATOR.'re_schema.php';

// deferred from downstream (i.e. after precursor routes-table updates)
$iname = $dict->IndexName('dest1,page,term');
// NOTE max 1000-byte length for index-key
$sqlarray = $dict->CreateIndexSQL($iname, CMS_DB_PREFIX.'routes', 'dest1,page,term', ['UNIQUE']);
$return = $dict->ExecuteSQLArray($sqlarray);
if ($return != 2) {
    error_msg('Index '.$iname.' creation '.$failed);
}

// 2. Misc. cleanups

// 2.0 reformat layout template types' callbacks
$tbl = CMS_DB_PREFIX.'layout_tpl_types';
$data = $db->getArray('SELECT id,lang_cb,dflt_content_cb,help_content_cb,one_only,owner FROM '.$tbl);
if ($data) {
    $stmt = $db->prepare("UPDATE $tbl SET lang_cb=?,dflt_content_cb=?,help_content_cb=?,one_only=?,owner=? WHERE id=?");
    foreach ($data as $row) {
        $cbl = unserialize($row['lang_cb'], []);
        if (!(is_null($cbl) || is_scalar($cbl))) {
            if (is_array($cbl) && count($cbl) == 2) {
                $cbl = $cbl[0].'::'.$cbl[1];
            } else {
                $cbl = TemplateType::SERIAL.$row['lang_cb'];
            }
        }
        $cbc = unserialize($row['dflt_content_cb'], []);
        if (!(is_null($cbc) || is_scalar($cbc))) {
            if (is_array($cbc) && count($cbc) == 2) {
                $cbc = $cbc[0].'::'.$cbc[1];
            } else {
                $cbc = TemplateType::SERIAL.$row['dflt_content_cb'];
            }
        }
        $cbh = unserialize($row['help_content_cb'], []);
        if (!(is_null($cbh) || is_scalar($cbh))) {
            if (is_array($cbh) && count($cbh) == 2) {
                $cbh = $cbh[0].'::'.$cbh[1];
            } else {
                $cbh = TemplateType::SERIAL.$row['help_content_cb'];
            }
        }
        $singl = ($row['one_only']) ? 1 : 0;
        $owner = ($row['owner'] > 1) ? $row['owner'] : 1;
        $db->execute($stmt, [$cbl, $cbc, $cbh, $singl, $owner, $row['id']]);
    }
    $stmt->close();
}

// 2.1 revise callbacks for page and generic layout template types
// directly to table - can't do object->save() which triggers audit(), N/A during installer
$corename = TemplateType::CORE;
$page_type = TemplateType::load($corename.'::page');
if ($page_type) {
    $sql = 'UPDATE '.$tbl.' SET lang_cb=?,dflt_content_cb=?,help_content_cb=? WHERE originator=\''.$corename.'\' AND name=\'page\'';
    $dbr = $db->execute($sql, [
        'CMSMS\internal\std_layout_template_callbacks::page_type_lang_callback',
        'CMSMS\internal\std_layout_template_callbacks::reset_page_type_defaults',
        'CMSMS\internal\std_layout_template_callbacks::template_help_callback'
    ]);
}
if (!$page_type || empty($dbr)) {
    error_msg($corename.'::page template update '.$failed);
}
$generic_type = TemplateType::load($corename.'::generic');
if ($generic_type) {
    $sql = 'UPDATE '.$tbl.' SET lang_cb=?,help_content_cb=? WHERE originator=\''.$corename.'\' AND name=\'generic\'';
    $dbr = $db->execute($sql, [
        'CMSMS\internal\std_layout_template_callbacks::generic_type_lang_callback',
        'CMSMS\internal\std_layout_template_callbacks::template_help_callback',
    ]);
}
if (!$generic_type || empty($dbr)) {
    error_msg($corename.'::generic template update '.$failed);
}

// 2.2 extra event-handler details
$tbl = CMS_DB_PREFIX.'event_handlers';
$sql = 'UPDATE '.$tbl.' SET type=\'M\' WHERE module_name IS NOT NULL';
$db->execute($sql);
$sql = 'UPDATE '.$tbl.' SET type=\'U\' WHERE tag_name IS NOT NULL';
$db->execute($sql);

// 2.3 remove module-plugin duplicates (now we have caseless tagname-matching)
$tbl = CMS_DB_PREFIX.'module_smarty_plugins';
$db->execute("DELETE T1 FROM $tbl T1 INNER JOIN $tbl T2
WHERE T1.id > T2.id AND UPPER(T1.name) = UPPER(T2.name) AND UPPER(T1.module) = UPPER(T2.module)");

// 2.4 convert plugin-handler-callables from serialize'd to plain string
$data = $db->getArray('SELECT id,module,callable FROM '.$tbl);
if ($data) {
//    $sqlarray = $dict->AddColumnSQL($tbl, 'transfer C(255) AFTER callable');
//    $res = $dict->ExecuteSQLArray($sqlarray);
    $i = 0;
// TODO last-processed row gets NULL for its decoded callable value. No indication why so
//    $stmt = $db->prepare("UPDATE $tbl SET transfer=? WHERE id=?");
    $stmt = $db->prepare("UPDATE $tbl SET callable=? WHERE id=?");
    foreach ($data as $row) {
        $val = unserialize($row['callable']);  // no safety needed ?
        if ($val) {
            if (is_array($val)) {
                $s = $val[0].'::'.$val[1];
            } elseif (is_string($val)) {
                if (($p = strpos($val, '::')) === false) {
                    $s = $row['module'].'::'.$val;
                } elseif ($p === 0) {
                    $s = $row['module'].$val;
                } else {
                    $s = $val;
                }
            } else {
                $s = NULL; // should never happen
                ++$i;
            }
            if ($s) {
                $db->execute($stmt, [$s, (int)$row['id']]);
            }
        } else {
            ++$i;
        }
    }
    $stmt->close();
    if ($i == 0) {
//        $sqlarray = $dict->DropColumnSQL($tbl, 'callable');
//        $res = $dict->ExecuteSQLArray($sqlarray);
//        $sqlarray = $dict->RenameColumnSQL($tbl, 'transfer', 'callable', 'C(255)');
//        $res = $dict->ExecuteSQLArray($sqlarray);
    } else {
        $n = count($data);
        error_msg("Failed to convert $i of $n plugin-callables: fatal for future use. Investigate the '$tbl' database table.");
    }
}

// 2.5 convert routes data
$tbl = CMS_DB_PREFIX.'routes';
$data = $db->getAssoc('SELECT id,term,dest1,page,data FROM '.$tbl);
if ($data) {
//    $sqlarray = $dict->AddColumnSQL($tbl, 'transfer C(600) AFTER data');
//    $res = $dict->ExecuteSQLArray($sqlarray);
    $res = class_alias('CMSMS\Route', 'CmsRoute'); // for unserialize()
    $i = 0;
//    $sql = 'UPDATE '.$tbl.' SET term=?, dest1=?, page=?, transfer=? WHERE id=?';
    $sql = 'UPDATE '.$tbl.' SET term=?, dest1=?, page=?, data=? WHERE id=?';
    // properties used for populating Route's, and possibly in serialize'd data
    $aliases = ['key1' => 'dest1', 'key2' => 'page', 'key3' => 'delmatch', 'absolute' => 'exact'];
    $keeps = ['defaults', 'exact', 'term'];
    $s = '(?P<'; // migrate from PCRE6 syntax
    $r = '(?<';
    foreach ($data as $id => $row) {
        $raw = @unserialize($row['data'], ['allowed_classes' => ['CmsRoute']]);
        if ($raw) {
            $stash = [];
            /* after the following array-cast we get something like:
            [
             'CMSMS\Route_data'=>NULL
             'CmsRoute_data'=>[
              'term'=>"/[Nn]ews\/(?<articleid>[0-9]+)\/(?<returnid>[0-9]+)$/"
              'exact'=>0 or (superseded) 'absolute'=>0
              'key1'=>'News'
              'key2'=>NULL
              ]
             'CmsRoute_results'=>NULL
            ]
            The useful props are the $aliases and $keeps identified above
            */
            foreach ((array)$raw as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k2 => $sval) {
                        if (isset($aliases[$k2])) {
                            $stash[$aliases[$k2]] = $sval;
                        } elseif (in_array($k2, $keeps)) {
                            $stash[$k2] = $sval;
                        }
                    }
                } else {
                    $k2 = substr($key, 10); // omit the (old 'CmsRoute_') class identifier
                    if (isset($aliases[$k2])) {
                        $stash[$aliases[$k2]] = $val;
                    } elseif (in_array($k2, $keeps)) {
                        $stash[$k2] = $val;
                    }
                }
            }
            $cooked = json_encode($stash, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            // cleanups
            $cooked = str_replace([$s, '__CONTENT_'], [$r, '__PAGE__'], $cooked);
            $term = str_replace($s, $r, $row['term']);
            $dest = str_replace('__CONTENT_', '__PAGE__', $row['dest1']);
            $page = ($row['page'] !== '') ? $row['page'] : null;
            $db->execute($sql, [$term, $dest, $page, $cooked, $id]);
        } else {
            ++$i;
        }
    }
    if ($i == 0) {
//        $sqlarray = $dict->DropColumnSQL($tbl, 'data');
//        $res = $dict->ExecuteSQLArray($sqlarray);
//        $sqlarray = $dict->RenameColumnSQL($tbl, 'transfer', 'data', 'C(700)');
//        $res = $dict->ExecuteSQLArray($sqlarray);
    } else {
        $n = count($data);
        error_msg("Failed to convert $i of $n route-parameters: fatal for future use. Investigate the '$tbl' database table.");
    }
}

// 2.6 de-entitize some recorded values

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
$dentit = function($s) use ($from,$to,$from1,$from2,$from3,$to3) {
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
    ['siteprefs', 'sitepref_name', 'sitepref_value'],
    ['userprefs', 'user_id', 'value'],
    ['userplugins', 'id', 'name', 'description'], // name should be cms_installer\sanitizeVal( ,ICMSSAN_FILE) description >> ,ICMSSAN_PURE
/* these probably never cleanValue()'d
    ['layout_css_groups','id','name','description'],
    ['layout_stylesheets','id','name','description'],
    ['layout_templates','id','name','description'],
    ['layout_tpl_types','id','name',' description'],
    ['layout_tpl_groups','id','name','description'],
    [DesignManager\Design::TABLENAME,'id','name','description'],
*/
] as $data) {
    $flds = array_slice($data, 1);
    $sels = implode(',', $flds);
    array_shift($flds);
    $checks = implode(' LIKE \'%&%\' OR ', $flds); // NOT wildcarded
    $sql = "SELECT $sels FROM {$pref}{$data[0]} WHERE $checks LIKE '%&%'";
    $rows = $db->getArray($sql);
    if ($rows) {
        $updates = implode('=?,', $flds);
        $stmt = $db->prepare("UPDATE $pref{$data[0]} SET {$updates}=? WHERE {$data[1]}=?");
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
                $db->execute($stmt, $row + [999 => $id]);
            }
        }
        $stmt->close();
    }
}

// 2.7 supply missing (approximate) create_date values for content_props

$sql = <<<EOS
SELECT DISTINCT CP.content_id, C.create_date
FROM {$pref}content_props CP JOIN {$pref}content C ON CP.content_id=C.content_id
WHERE CP.create_date IS NULL AND C.create_date IS NOT NULL
EOS;
$data = $db->getArray($sql);
if ($data) {
    $sql = "UPDATE {$pref}content_props SET create_date=? WHERE create_date IS NULL AND content_id=?";
    foreach ($data as $row) {
        $db->execute($sql, [$row['create_date'], $row['content_id']]);
    }
    $sql = "UPDATE {$pref}content_props SET modified_date=NULL WHERE create_date IS NOT NULL AND create_date > modified_date";
}

// includer-defined variables e.g. $app,$siteinfo;
$config = $app->get_config();
$longnow = $db->DbTimeStamp(time(), false);

// 2.8 re-space task-related properties
$sql = 'DELETE FROM '.CMS_DB_PREFIX."siteprefs WHERE sitepref_name LIKE 'Core::%'";
$db->execute($sql);
// revised 'namespace' indicator in recorded names c.f. AppParams::NAMESPACER
$sql = 'UPDATE '.CMS_DB_PREFIX."siteprefs SET sitepref_name = REPLACE(sitepref_name,'_mapi_pref_','\\\\\\\\'),modified_date = ? WHERE sitepref_name LIKE '%\\_mapi\\_pref\\_%'";
$db->execute($sql, [$longnow]);

// 4. Extra/revised permissions
$sql = 'UPDATE '.CMS_DB_PREFIX.'permissions SET name=?,description=?,modified_date=? WHERE name=?';
$db->execute($sql, ['Manage User Plugins', 'Modify User-Defined Tags', $longnow, 'Modify User-defined Tags']);
// BEFORE $sql = 'UPDATE '.CMS_DB_PREFIX.'permissions SET originator = \'__CORE__\' WHERE originator IS NULL';
//$db->execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'permissions SET originator = \'__CORE__\' WHERE originator = \'Core\'';
$db->execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'permissions SET description = NULL WHERE description = name OR description =\'\'';
$db->execute($sql);

//$sql = 'INSERT INTO '.CMS_DB_PREFIX.'permissions SET name=?,description=?';
//$db->execute($sql,['Manage Jobs','Manage asynchronous jobs']);

// non-ultra extras
$arr = [
    'View Admin Log',
    'View UserTag Help',
    'Edit User Tags', //distinct from Manage ...
    'View Restricted Files', //i.e. outside the uploads tree
];
foreach ($arr as $one_perm) {
    $permission = new Permission();
    $permission->name = $one_perm;
    try {
        $permission->save();
    } catch (Throwable $t) {
        // nothing here
    }
}

//  'Modify Site Assets',
$ultras = [
    'Modify Database', //for db structure i.e. +/- tables, change table-propertiies
    'Modify Database Content', //for system-management sans admin console
    'Modify Restricted Files', //i.e. outside the uploads tree
    'Remote Administration', //for system-management sans admin console
];
foreach ($ultras as $one_perm) {
    $permission = new Permission();
    $permission->name = $one_perm;
    try {
        $permission->save();
    } catch (Throwable $t) {
        // nothing here
    }
}

// 5. Redundant permissions
$arr = [
    'Core::Add Global Content Blocks',
    'Core::Modify Global Content Blocks',
    'Core::Remove Global Content Blocks',
    'Core::Add Stylesheet Assoc',
    'Core::Modify Stylesheet Assoc',
    'Core::Remove Stylesheet Assoc',
    'Core::Modify Group Assignments',
    'Core::Manage Designs', // migrated to DM
    'Core::Manage Themes',
    'Core::Manage CMSUsers',
//  'CmsJobManager::Manage Jobs', should be handled by un-installation
];
foreach ($arr as $one_perm) {
    try {
        $permission = Permission::load($one_perm);
        if ($permission) {
            $permission->delete();
        }
    } catch (Throwable $t) {
        // nothing here
$here = 1;
    }
}

// 6. Extra site params
$corenames = $config['coremodules'];
$cores = implode(',', $corenames);
$url = (!empty($siteinfo['supporturl'])) ? $siteinfo['supporturl'] : '';
$salt = Crypto::random_string(16, true);
$r = substr($salt, 0, 2);
$s = Crypto::random_string(32, false, true);
$uuid = strtr($s, '+/', $r);
$ulsave = json_encode($ultras);
$down = AppParams::get('enablesitedownmessage', 0); //for rename
$check = AppParams::get('use_smartycompilecheck', 1); //ditto

$arr = [
    'cache_autocleaning' => 1,
    'cache_driver' => 'auto',
    'cache_file_blocking' => 0,
    'cache_file_locking' => 1,
    'cache_lifetime' => 3600,
    'cdn_url' => 'https://cdnjs.cloudflare.com',
    'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
    'current_theme' => '', // frontend theme name
    'jobinterval' => 180,
    'joblastrun' => 0,
    'jobtimeout' => 5,
    'joburl' => '',
    'lock_refresh' => 120,
    'lock_timeout' => 60,
    'loginmodule' => '', // TODO CMSMS\ModuleOperations::STD_LOGIN_MODULE
    'loginprocessor' => '', // login UI defined by current theme
    'loginsalt' => $salt,
    'password_level' => 0, // p/w policy-type enumerator
    'site_downnow' => $down, // renamed
    'site_help_url' => $url,
    'site_logo' => '',
    'site_uuid' => $uuid, // almost-certainly-unique signature of this site
    'smarty_cachelife' => -1, // smarty default
    'smarty_cachemodules' => 0, // nope
    'smarty_cacheusertags' => 0,
    'smarty_compilecheck' => $check, // renamed
    'syntaxhighlighter' => '',
    'ultraroles' => $ulsave,
    'username_level' => 0, // username policy-type enumerator
];
foreach ($arr as $name => $val) {
    AppParams::set($name, $val);
}

// 7. Redundant site params
$arr = [
    'cms_is_uptodate',
    'pseudocron_granularity',
    'pseudocron_lastrun',
    'useadvancedcss',
];
foreach ($arr as $name) {
    AppParams::remove($name);
}

// 8. Extra static events
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

// 9. Redundant events (migrated to DM originator)
foreach ([
    'AddDesignPost',
    'AddDesignPre',
    'DeleteDesignPost',
    'DeleteDesignPre',
    'EditDesignPost',
    'EditDesignPre',
] as $s) {
    Events::RemoveEvent('Core', $s);
}

// 10. extra handlers
Events::AddStaticHandler('Core', 'PostRequest', 'CMSMS\internal\JobOperations::begin_async_work', 'C', false);
Events::AddStaticHandler('Core', 'ModuleInstalled', 'CMSMS\internal\JobOperations::event_handler', 'C', false);
Events::AddStaticHandler('Core', 'ModuleUninstalled', 'CMSMS\internal\JobOperations::event_handler', 'C', false);
Events::AddStaticHandler('Core', 'ModuleUpgraded', 'CMSMS\internal\JobOperations::event_handler', 'C', false);

// 11. Extra group
$group = new Group();
$group->name = 'CodeManager';
$group->description = lang('grp_coder_desc');
$group->active = 1;
try {
    $group->Save();
    $group->GrantPermission($ultras[2]);
    //$group->GrantPermission('Modify Site Assets');
    $group->GrantPermission('Manage User Plugins');
} catch (Throwable $t) {
    // nothing here
}
/*
$group = new Group();
$group->name = 'AssetManager';
$group->description = 'Members of this group can add/edit/delete website asset-files';
$group->active = 1;
try {
    $group->Save();
    $group->GrantPermission('Modify Site Assets');
} catch (Throwable $t) {
    // nothing here
}
*/

// 12. Update user preferences

// 12.1 migrate to new default theme
$files = glob(joinpath(CMS_ADMIN_PATH, 'themes', '*', '*Theme.php'), GLOB_NOESCAPE);  // filesystem path
foreach ($files as $one) {
    if (is_readable($one)) {
        $name = basename($one, 'Theme.php');
        $sql = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value=? WHERE preference=\'admintheme\'';
        $db->execute($sql, [$name]);
/*        $sql = 'UPDATE '.CMS_DB_PREFIX.'siteprefs SET sitepref_value=?,modified_date=? WHERE sitepref_name=\'logintheme\'';
        $db->execute($sql,[$name,$longnow]);
*/
        AppParams::set('logintheme', $name);
        break;
    }
}

/* TODO improve 2.2.15 users' homepage URL upgrade
// 12.2 remove old secure param name from homepage url
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
// 12.3 migrate user-homepages like 'index.php*' to 'menu.php*'
$sql = 'UPDATE '.CMS_DB_PREFIX."userprefs SET value = REPLACE(value,'index.php','menu.php') WHERE preference='homepage' AND value LIKE 'index.php%'";
$db->execute($sql);

// 13. file-changes which must be after the do_files() (main) update

// 13.1 create siteuuid-file
//$assetsdir = $config['assets_path'] ?? 'assets'; // TODO CMS_ASSETS_PATH known here ?
//$fp = joinpath($destdir,$assetsdir,'configs','siteuuid.dat');
$fp = joinpath(CMS_ASSETS_PATH, 'configs', 'siteuuid.dat');
$s = Crypto::random_string(72); //max byte-length of BCRYPT passwords
$p = -1;
while (($p = strpos($s, '\0', $p + 1)) !== false) {
    $c = crc32(substr($s, 0, $p) . 'A') & 0xff;
    $s[$p] = $c;
}
file_put_contents($fp, $s);
$modes = get_server_permissions();
chmod($fp, $modes[0]); // read-only

// 13.2 maybe some classes were missed by the manifest processing
$list = glob($destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Cms*.php'); // filesystem path
foreach ($list as $s) {
    @unlink($s);
}

// 13.3 rename any existing 2.3BETA plugins UDTfiles plus any format change?
$s = $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins';
if ($s != $plugsdir && is_dir($s)) {
    rename($s, $plugsdir);
    touch($plugsdir . DIRECTORY_SEPARATOR . 'index.html');
}
$files = glob($plugsdir.DIRECTORY_SEPARATOR.'*.cmsplugin', GLOB_NOESCAPE | GLOB_NOSORT); // filesystem path
foreach ($files as $fp) {
    $to = $plugsdir.DIRECTORY_SEPARATOR.basename($fp, 'cmsplugin').'phphp'; //c.f. UserTagOperations::PLUGEXT
    rename($fp, $to);
}
/*
// revert force-moved (by 2.2.90x upgrade) 'independent' modules from assets/modules to deprecated /modules
$wizard = wizard::get_instance();
$data = $wizard->get_data('version_info'); //version-datum from session
$fromvers = $data['version'];
if (version_compare($fromvers, '2.2.900') >= 0 && version_compare($fromvers, '2.2.910') < 0) {
    $fp = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . '*';
    $dirs = glob($fp, GLOB_ONLYDIR); // filesystem path
    $d = '';
    foreach ($dirs as $fp) {
        $modname = basename($fp);
        if (!in_array($modname, ['MenuManager', / *'CMSMailer',* / 'News'])) { //TODO exclude all non-core modules in files-tarball
            if (!$d) {
                $d = $destdir . DIRECTORY_SEPARATOR . 'modules';
                @mkdir($d, $dirmode, true);
            }
            $fp = realpath($fp);
            $to = $d . DIRECTORY_SEPARATOR . $modname;
            rename($fp, $to);
        }
    }
}
*/

// 13.4 move 'core' modules to /modules
$s = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'modules';
$list = scandir($s);
foreach ($list as $modname) {
    if ($modname == '.' || $modname == '..') continue;
    $to = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if (is_dir($to)) {
        rrmdir($to);
    }
    $from = $s . DIRECTORY_SEPARATOR . $modname;
    $r = realpath($from); //it might be a link (or even just a file)
    rename($r, $to);
}
rrmdir($s);

// 13.5 move any 'non-core' modules to /modules
// i.e. pre-position them for user to install \ upgrade | delete
$s = $destdir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'modules';
$list = scandir($s);
if ($list) {
    foreach ($list as $modname) {
        if ($modname == '.' || $modname == '..') continue;
        $to = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if (is_dir($to)) {
            rrmdir($to);
        }
        $from = $s . DIRECTORY_SEPARATOR . $modname;
        $r = realpath($from);
        rename($r, $to);
    }
}
rrmdir($s);

// 13.6. redundant modules
// NOTE uninstallation fails if redundant-module classfile is deleted e.g. during files-manifest processing
// TODO some yukky workaround for such deletions
$ops = SingleItem::ModuleOperations();
// replacement version of this has different name
$res = $ops->UninstallModule('CMSContentManager');
if ($res[0]) {
    verbose_msg(lang('uninstall_module','CMSContentManager'));
    $s = cms_module_path('CMSContentManager', true);
    rrmdir($s);
}
else {
    verbose_msg('CMSContentManager : '.$res[1]);
}
// obsolete background-jobs processor
$res = $ops->UninstallModule('CmsJobManager');
if ($res[0]) {
    verbose_msg(lang('uninstall_module','CmsJobManager'));
    $s = cms_module_path('CmsJobManager', true);
    rrmdir($s);
}
else {
    verbose_msg('CmsJobManager : '.$res[1]);
}

// 14. Cleanup plugins
// this is here (last) so that any problem does not abort other changes
$r = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
$s = (!empty($config['assets_path'])) ? $config['assets_path'] : 'assets';
$tmpl = '/^\s*function\\s+smarty_(cms_)?function_%s\\s*\\([^,]+,[^,&]*(&\\s*)?(\\$\\S+)\\s*\\).*[\r\n]+/mU';
foreach ([
    ['lib', 'plugins'],
    [$r, 'plugins'],
    [$s, 'plugins'],
    ['plugins'], // deprecated, should be gone now
] as $segs) {
    $path = joinpath($destdir, ...$segs);
    if (is_dir($path)) {
        $files = scandir($path, SCANDIR_SORT_NONE);
        if ($files) {
            foreach ($files as $one) {
                if (startswith($one, 'function.') && endswith($one, '.php')) {
                    $fp = $path.DIRECTORY_SEPARATOR.$one;
                    $content = file_get_contents($fp);
                    if ($content) {
                        $parts = explode('.', $one);
                        $patn = sprintf($tmpl, $parts[1]);
                        if (preg_match($patn, $content, $matches)) {
                            // remove invalid content from function-declaration
                            $save = false;
                            $r = $matches[0];
                            if (!empty($matches[1])) {
                                $r = str_replace('smarty_cms', 'smarty', $r);
                                $save = true;
                            }
                            if (!empty($matches[2])) {
                                $r = str_replace($matches[2], '', $r);
                                $save = true;
                            }
                            if ($save) {
                                $content = str_replace($matches[0], $r, $content);
                            }
                            // replace old-format licence, if any
                            $p = strpos($content, $r);
                            if (($i = strpos($content, '#', 1)) && $i < $p) {
                                $s = substr($content, 0, $p);
                                $started = preg_match('~^\s*<\?php[\s\r\n]+/\*~m', $s);
                                $ended = false;
                                $patn = '/(\r\n|\n|\r)/';
                                $parts = explode('#', $s);
                                for ($i = 0, $n = count($parts); $i < $n; ++$i) {
                                    $c = preg_match_all($patn, $parts[$i]);
                                    switch ($c) {
                                        case 0:
                                            $parts[$i] = ltrim($parts[$i], " \t").'#';
                                        break;
                                        case 1:
                                            if (!$ended) {
                                                $parts[$i] = ltrim($parts[$i], " \t");
                                            } else {
                                                $parts[$i] = '//'.$parts[$i];
                                            }
                                        break;
                                        default:
                                            if ($started && $ended && $i > 0 && !endswith($parts[$i - 1], '#')) {
                                                $parts[$i] = '//'.$parts[$i];
                                            }
                                            if (!$started) {
                                                $subparts = preg_split($patn, $parts[0], 2);
                                                $parts[0] = trim($subparts[0])."\n/*\n".(($subparts[1]) ? trim($subparts[1]) : '');
                                                $started = true;
                                            }
                                            if (!$ended) { // i.e. $started-only TODO maybe ended in a later part
                                                $ended = preg_match('~[\r\n]+\s*\*/$~m', $parts[$i]);
                                                if (!$ended && $i > 0) {
                                                    $subparts = preg_split($patn, $parts[$i], 2);
                                                    $parts[$i] = (($subparts[0]) ? trim($subparts[0])."\n*/\n" : "*/\n\n").$subparts[1];
                                                    $ended = true;
                                                }
                                            }
                                            $unsplit = preg_match('/(\r\n|\n|\r)\s*$/', $parts[$i]);
                                            if (!$unsplit) {
                                                $parts[$i] .= '#';
                                            }
                                        break;
                                    }
                                }
                                if ($n > 1) {
                                    if (!$started) {
                                        $parts[0] .= "/*\n";
                                    }
                                    if (!($started || $ended)) {
                                        $parts[$n - 1] .= "*/\n";
                                    }
                                    $s = implode('', $parts);
                                    $content = $s.substr($content, $p);
                                    $save = true;
                                }
                            }
                            if ($save) {
                                if (!is_writable($fp)) {
                                    chmod($fp, $modes[1]);
                                }
                                file_put_contents($fp, $content);
                            }
                        }
                    }
                }
            }
        }
    }
}
