<?php
#procedure for displaying system-maintenance actions
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use cms_installer\installer_base;
use CMSMS\AdminUtils;
use CMSMS\ContentOperations;
use CMSMS\internal\global_cache;
use CMSMS\RouteOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$access = true; //check_permission($userid, 'TODO some Site Perm');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup    $themeObject->RecordNotice('error', lang('needpermissionto', '"Modify Site Preferences"'));
    return;
}

require_once cms_join_path(CMS_ROOT_PATH, 'lib', 'test.functions.php');

$gCms = CmsApp::get_instance();

$smarty = $gCms->GetSmarty();
$smarty->force_compile = true;
$smarty->assign('theme', $themeObject);

/*
 * Database
 */
$db = $gCms->GetDb();
$query = "SHOW TABLES LIKE '".CMS_DB_PREFIX."%'";
$tablestmp = $db->GetArray($query);
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
    $optimizearray = $db->GetArray($query);
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
    audit('', 'System Maintenance', 'All db-tables optimized');
    $themeObject->RecordNotice('success', lang('sysmain_tablesoptimized'));
}

if (isset($_POST['repairall'])) {
    $query = 'REPAIR TABLE ' . MakeCommaList($tables);
    $repairarray = $db->GetArray($query);
    $errorsfound = 0;
    $errordetails = '';
    foreach ($repairarray as $check) {
        if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
            $errorsfound++;
            $errordetails .= 'Database table ' . $check['Table'] . ' does not checkout OK.<br>';
        }
    }

    // put mention into the admin log
    audit('', 'System Maintenance', 'All database tables repaired');
    $themeObject->RecordNotice('success', lang('sysmain_tablesrepaired'));
}

$query = 'CHECK TABLE ' . MakeCommaList($tables);
//echo $query;
$checkarray = $db->GetArray($query);
//print_r($checkarray);

$errortables = [];
foreach ($checkarray as $check) {
    if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
        $errortables[] = $check['Table'];
    }
}

$n = count($errortables);
$smarty->assign('errorcount', $n);
if ($n > 0) {
    $smarty->assign('errortables', implode(',', $errortables));
}

/*
 * Cache and content
 */
if (isset($_POST['clearcache'])) {
    global_cache::clear_all();
    cms_cache_handler::get_instance()->clear();
    AdminUtils::clear_cached_files();
    // put mention into the admin log
    audit('', 'System maintenance', 'Caches cleared');
    $themeObject->RecordNotice('success', lang('cachecleared'));
    $smarty->assign('active_cache', 1);
}

if (isset($_POST['updateroutes'])) {
    RouteOperations::rebuild_static_routes();
    audit('', 'System maintenance', 'Static routes rebuilt');
    $themeObject->RecordNotice('success', lang('routesrebuilt'));
    $smarty->assign('active_content', 1);
}

$contentops = ContentOperations::get_instance();
if (isset($_POST['updatehierarchy'])) {
    $contentops->SetAllHierarchyPositions();
    audit('', 'System maintenance', 'Page hierarchy positions updated');
    $themeObject->RecordNotice('success', lang('sysmain_hierarchyupdated'));
    $smarty->assign('active_content', 1);
}

// Setup types
$realm = 'CMSContentManager'; //TODO generalize
$contenttypes = $contentops->ListContentTypes(false, true, false, $realm);
//print_r($contenttypes);
$simpletypes = [];
foreach ($contenttypes as $typeid => $typename) {
    $simpletypes[] = $typeid;
}

if (isset($_POST['addaliases'])) {
    //$contentops->SetAllHierarchyPositions();
    $count = 0;
    $query = 'SELECT content_id,content_name,type,content_alias,menu_text FROM ' . CMS_DB_PREFIX . 'content';
    $stmt = $db->Prepare('UPDATE ' . CMS_DB_PREFIX . 'content SET content_alias=? WHERE content_id=?');
    $allcontent = $db->Execute($query);
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
            $db->Execute($stmt, [$alias, $content_id]);
            $count++;
        }
    }
    $stmt->close();
	audit('', 'System maintenance', 'Fixed pages missing aliases, count:' . $count);
    $themeObject->RecordNotice('success', $count . ' ' . lang('sysmain_aliasesfixed'));
    $smarty->assign('active_content', 1);
}

if (isset($_POST['fixtypes'])) {
    //$contentops->SetAllHierarchyPositions();
    $count = 0;
    $query = 'SELECT content_id,type FROM ' . CMS_DB_PREFIX . 'content';
    $stmt = $db->Prepare('UPDATE ' . CMS_DB_PREFIX . "content SET type='content' WHERE content_id=?");
    $allcontent = $db->Execute($query);
    while ($row = $allcontent->FetchRow()) {
        if (!in_array($row['type'], $simpletypes)) {
            $db->Execute($stmt, [$row['content_id']]);
            $count++;
        }
    }

    $stmt->close();
    audit('', 'System maintenance', 'Converted pages with invalid content types, count:' . $count);
    $themeObject->RecordNotice('success', $count . ' ' . lang('sysmain_typesfixed'));
    $smarty->assign('active_content', 1);
}

$query = 'SELECT content_name,type,content_alias FROM ' . CMS_DB_PREFIX . 'content ORDER BY content_name';
$allcontent = $db->Execute($query);
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
$smarty->assign('pagecount', $count)
  ->assign('pagesmissingalias', $withoutalias)
  ->assign('withoutaliascount', count($withoutalias))
  ->assign('pageswithinvalidtype', $invalidtypes)
  ->assign('invalidtypescount', count($invalidtypes));

$obj = cms_cache_handler::get_instance();
$type = get_class($obj->get_driver());
if (!endswith($type, 'File')) {
    $c = stripos($type, 'Cache');
    $type = ucfirst(substr($type, $c+5));
    $smarty->assign('cachetype', $type);
}

/*
 * Site-content export
 */
if (!empty($config['developer_mode'])) {
    // try to get folder where 'support' files (if any) will be stored, pending site import
    $fp = cms_join_path(CMS_ROOT_PATH,'phar_installer','lib','classes','class.installer_base.php');
    $exportable = is_file($fp);
} else {
    $exportable = false;
}
$smarty->assign('export', $exportable);

if ($exportable && isset($_POST['export'])) {
    include $fp;
    $arr = installer_base::CONTENTFILESDIR;
    $filesin = cms_join_path(CMS_ROOT_PATH,'phar_installer', ...$arr);
    $arr = installer_base::CONTENTXML;
    $xmlfile = cms_join_path(CMS_ROOT_PATH,'phar_installer', ...$arr);

    include cms_join_path(CMS_ROOT_PATH,'phar_installer','lib','install','iosite.functions.php');
    export_content($xmlfile, $filesin, $db);
    // also download it
    $handlers = ob_list_handlers();
    for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }
    $tmp = cms_siteprefs::get('sitename','CMSMS-Site');
    $xmlname = strtr("Exported-{$tmp}.xml", ' ', '_');
    header('Content-Description: File Transfer');
    header('Content-Type: application/force-download');
    header('Content-Disposition: attachment; filename='.$xmlname);
    echo file_get_contents($xmlfile);

    exit;
}

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
            $changelog[$i] = htmlentities($changelog[$i]);
        }
    }
    if ($close) {
        $changelog[] = '</div>';
    }
    $changelog = implode('<br />', $changelog);

    $smarty->assign('changelog', $changelog)
      ->assign('changelogfilename', $ch_filename);
}

/*
 * Footer script
 */
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
$themeObject->add_footertext($out);

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign('backurl', $themeObject->BackUrl())
  ->assign('selfurl', $selfurl)
  ->assign('extraparms', $extras)
  ->assign('urlext', $urlext);

include_once 'header.php';
$smarty->display('systemmaintenance.tpl');
include_once 'footer.php';
