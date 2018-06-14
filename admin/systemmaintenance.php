<?php
#procedure for displaying system-maintenance actions
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();
$access = true; //check_permission($userid, 'TODO some Site Perm');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup    $themeObject->RecordNotice('error', lang('needpermissionto', '"Modify Site Preferences"'));
    return;
}

$CMS_BASE = dirname(__DIR__);
require_once cms_join_path($CMS_BASE, 'lib', 'test.functions.php');

$smarty = CMSMS\internal\Smarty::get_instance();

$smarty->force_compile = true;
$smarty->assign('theme', $themeObject);

/*
 * Database
 */
$db = cmsms()->GetDb();
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

if (!empty($_POST['optimizeall'])) {
    $query = 'OPTIMIZE TABLE ' . MakeCommaList($nonseqtables);
    $optimizearray = $db->GetArray($query);
    //print_r($optimizearray);
    $errorsfound = 0;
    $errordetails = '';
    foreach ($optimizearray as $check) {
        if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
            $errorsfound++;
            $errordetails .= 'MySQL reports that table ' . $check['Table'] . ' does not checkout OK.<br>';
        }
    }

    // put mention into the admin log
    audit('', 'System Maintenance', 'All db-tables optimized');
    $themeObject->RecordNotice('success', lang('sysmain_tablesoptimized'));
}

if (!empty($_POST['repairall'])) {
    $query = 'REPAIR TABLE ' . MakeCommaList($tables);
    $repairarray = $db->GetArray($query);
    $errorsfound = 0;
    $errordetails = '';
    foreach ($repairarray as $check) {
        if (isset($check['Msg_text']) && $check['Msg_text'] != 'OK') {
            $errorsfound++;
            $errordetails .= 'MySQL reports that table ' . $check['Table'] . ' does not checkout OK.<br>';
        }
    }

    // put mention into the admin log
    audit('', 'System Maintenance', 'All db-tables repaired');
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

$smarty->assign('errorcount', count($errortables));
if (count($errortables) > 0) {
    $smarty->assign('errortables', implode(',', $errortables));
}

/*
 * Cache and content
 */
$contentops = cmsms()->GetContentOperations();

if (!empty($_POST['updateurls'])) {
    cms_route_manager::rebuild_static_routes();
    audit('', 'System maintenance', 'Static routes rebuilt');
    $themeObject->RecordNotice('success', lang('routesrebuilt'));
    $smarty->assign('active_content', 'true');
}

if (!empty($_POST['clearcache'])) {
    cmsms()->clear_cached_files(-1);
    // put mention into the admin log
    audit('', 'System maintenance', 'Cache cleared');
    $themeObject->RecordNotice('success', lang('cachecleared'));
    $smarty->assign('active_content', 'true');
}

if (!empty($_POST['updatehierarchy'])) {
    $contentops->SetAllHierarchyPositions();
    audit('', 'System maintenance', 'Page hierarchy positions updated');
    $themeObject->RecordNotice('success', lang('sysmain_hierarchyupdated'));
    $smarty->assign('active_content', 'true');
}

$flag = !empty($config['developer_mode']);
if ($flag && isset($_POST['export'])) {
    include __DIR__.DIRECTORY_SEPARATOR.'function.contentoperation.php';
    $xmlfile = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.uniqid('site').'.xml';
    export_content($xmlfile, $db);
    $handlers = ob_list_handlers();
    for ($c = 0, $n = sizeof($handlers); $c < $n; ++$c) {
        ob_end_clean();
    }
    $xmlname = 'Exported-CMSMS-Site.xml'; //TODO better name
    header('Content-Description: File Transfer');
    header('Content-Type: application/force-download');
    header('Content-Disposition: attachment; filename='.$xmlname);
    echo file_get_contents($xmlfile);
    @unlink($xmlfile);
	exit;
}
$smarty->assign('devmode', $flag);

//Setting up types
$contenttypes = $contentops->ListContentTypes(false, true);
//print_r($contenttypes);
$simpletypes = [];
foreach ($contenttypes as $typeid => $typename) {
    $simpletypes[] = $typeid;
}

if (!empty($_POST['addaliases'])) {
    //$contentops->SetAllHierarchyPositions();
    $count = 0;
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'content';
    $query2 = 'UPDATE ' . CMS_DB_PREFIX . 'content SET content_alias=? WHERE content_id=?';
    $allcontent = $db->Execute($query);
    while ($contentpiece = $allcontent->FetchRow()) {
        $content_id = $contentpiece['content_id'];
        if (trim($contentpiece['content_alias']) == '' && $contentpiece['type'] != 'separator') {
            $alias = trim($contentpiece['menu_text']);
            if ($alias == '') {
                $alias = trim($contentpiece['content_name']);
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
            $db->Execute($query2, [$alias, $content_id]);
            $count++;
        }
    }
    audit('', 'System maintenance', 'Fixed pages missing aliases, count:' . $count);
    $themeObject->RecordNotice('success', $count . ' ' . lang('sysmain_aliasesfixed'));
    $smarty->assign('active_content', 'true');
}

if (!empty($_POST['fixtypes'])) {
    //$contentops->SetAllHierarchyPositions();
    $count = 0;
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'content';
    $query2 = 'UPDATE ' . CMS_DB_PREFIX . "content SET type='content' WHERE content_id=?";
    $allcontent = $db->Execute($query);
    while ($contentpiece = $allcontent->FetchRow()) {
        if (!in_array($contentpiece['type'], $simpletypes)) {
            $db->Execute($query2, [$contentpiece['content_id']]);
            $count++;
        }
    }

    audit('', 'System maintenance', 'Converted pages with invalid content types, count:' . $count);
    $themeObject->RecordNotice('success', $count . ' ' . lang('sysmain_typesfixed'));
    $smarty->assign('active_content', 'true');
}

$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'content';
$allcontent = $db->Execute($query);
$pages = [];
$withoutalias = [];
$invalidtypes = [];
if (is_object($allcontent)) {
    while ($contentpiece = $allcontent->FetchRow()) {
        $pages[] = $contentpiece['content_name'];
        if (trim($contentpiece['content_alias']) == '' && $contentpiece['type'] != 'separator') {
            $withoutalias[] = $contentpiece;
        }
        if (!in_array($contentpiece['type'], $simpletypes)) {
            $invalidtypes[] = $contentpiece;
        }
        //print_r($contentpiece);
    }
}
$smarty->assign('pagesmissingalias', $withoutalias);
$smarty->assign('pageswithinvalidtype', $invalidtypes);

$smarty->assign('pagecount', count($pages));
$smarty->assign('invalidtypescount', count($invalidtypes));
$smarty->assign('withoutaliascount', count($withoutalias));

/*
 * Changelog
 */
$ch_filename = cms_join_path($CMS_BASE, 'doc', 'CHANGELOG.txt');

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

    $smarty->assign('changelog', $changelog);
    $smarty->assign('changelogfilename', $ch_filename);
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

$smarty->assign('backurl', $themeObject->BackUrl());
$smarty->assign('selfurl', basename(__FILE__));
$smarty->assign('urlext', $urlext);

include_once 'header.php';
$smarty->display('systemmaintenance.tpl');
include_once 'footer.php';
