<?php
/*
Procedure to display system-maintenance actions
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use cms_installer\installer_base;
use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\Error403Exception;
use CMSMS\Lone;
use CMSMS\RouteOperations;
use function CMSMS\log_notice;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
if (0) { //!check_permission($userid, 'TODO some Site Perm')) {
//TODO some pushed popup $themeObject->RecordNotice('error', _la('needpermissionto', '"Modify Site Preferences"'));
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$urlext = get_secure_param();
$themeObject = Lone::get('Theme');

require_once cms_join_path(CMS_ROOT_PATH, 'lib', 'test.functions.php');

$smarty = Lone::get('Smarty');
$smarty->force_compile = true;
$smarty->assign('theme', $themeObject);

/*
 * Database
 */
$db = Lone::get('Db');
$query = "SHOW TABLES LIKE '".CMS_DB_PREFIX."%'";
$tablestmp = $db->getArray($query);
$tables = [];
$nonseqtables = [];
foreach ($tablestmp as $table) {
    foreach ($table as $tabeinfo => $tablename) {
        $tables[] = $tablename;
        if (!stripos($tablename, '_seq')) {
            $nonseqtables[] = $tablename;
        }
    }
}

$smarty->assign('tablecount', count($tables));
$smarty->assign('nonseqcount', count($nonseqtables));

function MakeCommaList($tables)
{
    $out = '';
    foreach ($tables as $table) {
        if ($out != '') {
            $out .= ' ,';
        }
        $out .= '`' . $table . '`';
    }
    return $out;
}

if (isset($_POST['optimizeall'])) {
    $query = 'OPTIMIZE TABLE ' . MakeCommaList($nonseqtables);
    $optimizearray = $db->getArray($query);
    //print_r($optimizearray);
    $errorsfound = 0;
    $errordetails = '';
    foreach ($optimizearray as $check) {
        if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
            $errorsfound++;
            $errordetails .= 'Database table ' . $check['Table'] . ' does not checkout OK.<br>';
        }
    }

    // put mention into the admin log
    log_notice('System Maintenance', 'All db-tables optimized');
    $themeObject->RecordNotice('success', _la('sysmain_tablesoptimized'));
}

if (isset($_POST['repairall'])) {
    $query = 'REPAIR TABLE ' . MakeCommaList($tables);
    $repairarray = $db->getArray($query);
    $errorsfound = 0;
    $errordetails = '';
    foreach ($repairarray as $check) {
        if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
            $errorsfound++;
            $errordetails .= 'Database table ' . $check['Table'] . ' does not checkout OK.<br>';
        }
    }

    // put mention into the admin log
    log_notice('System Maintenance', 'All database tables repaired');
    $themeObject->RecordNotice('success', _la('sysmain_tablesrepaired'));
}

$query = 'CHECK TABLE ' . MakeCommaList($tables);
//echo $query;
$checkarray = $db->getArray($query);
//print_r($checkarray);

$errortables = [];
foreach ($checkarray as $check) {
    if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
        $errortables[] = $check['Table'];
    }
}

$n = count($errortables);
$smarty->assign('errorcount', $n);
if ($n == 1) {
    $smarty->assign('errortables', $errortables[0]);
} elseif ($n > 1) {
    $smarty->assign('errortables', array_unique($errortables));
}

/*
 * Cache and content
 */
if (isset($_POST['clearcache'])) {
    Lone::get('SystemCache')->clear();
    Lone::get('LoadedData')->refresh('*');
    Lone::get('LoadedMetadata')->refresh('*');
    AdminUtils::clear_cached_files();
    // put mention into the admin log
    log_notice('System maintenance', 'Caches cleared');
    $themeObject->ParkNotice('success', _la('cachecleared'));
    redirect(basename(__FILE__).$urlext); //go refresh the caches
}

if (isset($_POST['updateroutes'])) {
    RouteOperations::rebuild_static_routes();
    log_notice('System maintenance', 'Static routes rebuilt');
    $themeObject->RecordNotice('success', _la('routesrebuilt'));
    $smarty->assign('active_content', 1);
}

$contentops = Lone::get('ContentOperations');
if (isset($_POST['updatehierarchy'])) {
    $contentops->SetAllHierarchyPositions();
    log_notice('System maintenance', 'Page hierarchy positions updated');
    $themeObject->RecordNotice('success', _la('sysmain_hierarchyupdated'));
    $smarty->assign('active_content', 1);
}

// Setup types
$realm = 'ContentManager'; //TODO generalize
$contenttypes = Lone::get('ContentTypeOperations')->ListContentTypes(false, true, false, $realm);

//print_r($contenttypes);
$simpletypes = [];
foreach ($contenttypes as $typeid => $typename) {
    $simpletypes[] = $typeid;
}

if (isset($_POST['addaliases'])) {
    //$contentops->SetAllHierarchyPositions();
    $count = 0;
    $query = 'SELECT content_id,content_name,type,content_alias,menu_text FROM ' . CMS_DB_PREFIX . 'content';
    $stmt = $db->prepare('UPDATE ' . CMS_DB_PREFIX . 'content SET content_alias=? WHERE content_id=?');
    $allcontent = $db->execute($query);
    while ($row = $allcontent->FetchRow()) {
        $content_id = $row['content_id'];
        if (trim($row['content_alias']) == '' && $row['type'] != 'separator') {
            $alias = trim($row['menu_text']);
            if ($alias == '') {
                $alias = trim($row['content_name']);
            }

            $tolower = true;
            $alias = munge_string_to_url($alias, $tolower);
            if ($contentops->CheckAliasError($alias, $content_id)) {
                $alias_num_add = 2;
                // If a '-2' version of the alias already exists
                // Check the '-3' version etc.
                while ($contentops->CheckAliasError($alias . '-' . $alias_num_add) !== false) {
                    $alias_num_add++;
                }
                $alias .= '-' . $alias_num_add;
            }
            $db->execute($stmt, [$alias, $content_id]);
            $count++;
        }
    }
    $stmt->close();
    log_notice('System maintenance', 'Fixed pages missing aliases, count:' . $count);
    $themeObject->RecordNotice('success', $count . ' ' . _la('sysmain_aliasesfixed'));
    $smarty->assign('active_content', 1);
}

if (isset($_POST['fixtypes'])) {
    //$contentops->SetAllHierarchyPositions();
    $count = 0;
    $query = 'SELECT content_id,type FROM ' . CMS_DB_PREFIX . 'content';
    $stmt = $db->prepare('UPDATE ' . CMS_DB_PREFIX . "content SET type='content' WHERE content_id=?");
    $allcontent = $db->execute($query);
    while ($row = $allcontent->FetchRow()) {
        if (!in_array($row['type'], $simpletypes)) {
            $db->execute($stmt, [$row['content_id']]);
            $count++;
        }
    }

    $stmt->close();
    log_notice('System maintenance', 'Converted pages with invalid content types, count:' . $count);
    $themeObject->RecordNotice('success', $count . ' ' . _la('sysmain_typesfixed'));
    $smarty->assign('active_content', 1);
}

$query = 'SELECT content_name,type,content_alias FROM ' . CMS_DB_PREFIX . 'content ORDER BY content_name';
$allcontent = $db->execute($query);
$count = 0;
$withoutalias = [];
$invalidtypes = [];
if (is_object($allcontent)) {
    while ($row = $allcontent->FetchRow()) {
        $count++;
        if (trim($row['content_alias']) == '' && $row['type'] != 'separator') {
            $withoutalias[] = $row['content_name'];
        }
        if (!in_array($row['type'], $simpletypes)) {
            $invalidtypes[] = $row;
        }
        //print_r($row);
    }
}
$smarty->assign([
    'pagecount' => $count,
    'pagesmissingalias' => $withoutalias,
    'withoutaliascount' => count($withoutalias),
    'pageswithinvalidtype' => $invalidtypes,
    'invalidtypescount' => count($invalidtypes),
]);

$cache = Lone::get('SystemCache');
$type = get_class($cache->get_driver());
if (!endswith($type, 'File')) {
    $c = stripos($type, 'Cache');
    $type = ucfirst(substr($type, $c+5));
    $smarty->assign('cachetype', $type);
}

/*
 * Site-content export if now in develop-mode
 */
$exportable = false;
if ($config['develop_mode']) {
    $installer_path = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'phar_installer'; // TODO not hardcoded
    // check for the installer base class
    $fp = cms_join_path($installer_path, 'lib', 'classes', 'class.installer_base.php');
    if (isset($_POST['export']) && is_file($fp)) {
        // check for the installer export/import methods
        $space = @require_once cms_join_path($installer_path, 'lib', 'iosite.functions.php');
        if ($space === 1) {
            $space = '';
        } elseif ($space !== false) {
            include $fp;
            $fp = cms_join_path($installer_path, 'lib', 'misc.functions.php');
            require $fp;
            $arr = installer_base::UPLOADFILESDIR;
            if (is_array($arr)) {
                $uploadsin = cms_join_path($installer_path, ...$arr);
                $arr = installer_base::CUSTOMFILESDIR;
                $customin = cms_join_path($installer_path, ...$arr);
                $arr = installer_base::CONTENTXML;
                $xmlfile = cms_join_path($installer_path, ...$arr);

                $function = ($space) ? $space.'\export_content' : 'export_content';
                // save the content in installer tree
                $function($xmlfile, $uploadsin, $customin, $db);
                // and also download it
                $handlers = ob_list_handlers();
                for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }
                $tmp = AppParams::get('sitename','CMSMS-Site');
                $xmlname = strtr("Exported-{$tmp}.xml", ' ', '_');
                header('Content-Description: File Transfer');
                header('Content-Type: application/force-download');
                header('Content-Disposition: attachment; filename='.$xmlname);
                echo file_get_contents($xmlfile);

                exit;
            }
        }
        $themeObject->RecordNotice('error', _la('errornofilesexported'));
    } elseif (is_file($fp)) {
        $exportable = true;
    }
}
$smarty->assign('export', $exportable);

/*
 * Changelog
 */
$ch_filename = cms_join_path(CMS_ROOT_PATH, 'doc', 'CHANGELOG.txt');

if (is_readable($ch_filename)) {
    $close = false;
    $changelog = @file($ch_filename);
    $n = count($changelog);
    for ($i = 0; $i < $n; $i++) {
        if (strncmp($changelog[$i], 'Version', 7) == 0) {
            if (!$close) {
                $changelog[$i] = '<div class="version"><h3>' . $changelog[$i] . '</h3>';
            } else {
                $changelog[$i] = '</div><div class="version"><h3>' . $changelog[$i] . '</h3>';
            }
            $close = true;
        } else {
            $changelog[$i] = specialize($changelog[$i]);
        }
    }
    if ($close) {
        $changelog[] = '</div>';
    }
    $changelog = implode('<br>', $changelog);

    $smarty->assign([
		'changelog' => $changelog,
        'changelogfilename' => $ch_filename,
	]);
}

/*
 * Footer script
 */
//$nonce = get_csp_token();
$out = <<<EOS
<script type="text/javascript">
//<![CDATA[
function confirmsubmit(form,msg) {
 cms_confirm(msg,'',cms_lang('yes')).done(function() {
  $(form).off('submit').trigger('submit');
 });
}
//]]>
</script>
EOS;
add_page_foottext($out);

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'backurl' => $themeObject->BackUrl(),
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

$content = $smarty->fetch('systemmaintenance.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
