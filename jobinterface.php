<?php
/*
job-request interface for CMS Made Simple <http://www.cmsmadesimple.org>
Copyright (C) 2018 the CMSMS Dev Team <?@cmsmadesimple.org>

This file is free software. You can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the
Free Software Foundation, either version 3 of the License, or (at your
option) any later version.

This file is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY, without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License
(http://www.gnu.org/licenses/licenses.html#AGPL) for more details.
*/

if (!isset($_REQUEST['mact'])) {
	exit;
}
$mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
$ary = explode(',', $mact, 4);
if (count($ary) != 4 || empty($ary[0]) || empty($ary[2])) {
	exit;
}

$mp = false;
$fn = DIRECTORY_SEPARATOR.$ary[0].DIRECTORY_SEPARATOR.'action.'.$ary[2].'.php';
foreach ([
 'lib'.DIRECTORY_SEPARATOR.'modules',
 'assets'.DIRECTORY_SEPARATOR.'modules',
 'modules'
] as $bp) {
	if (is_file(__DIR__.DIRECTORY_SEPARATOR.$bp.$fn)) {
		$mp = __DIR__.DIRECTORY_SEPARATOR.$bp.DIRECTORY_SEPARATOR.$ary[0].DIRECTORY_SEPARATOR.'class'.$ary[0].'.php';
		break;
	}
}
if (!$mp) {
	exit;
}

$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
$bpc = 	$bp .'classes'.DIRECTORY_SEPARATOR;
require_once $bp.'misc.functions.php';
// defines
require_once $bp.'version.php';
define('CONFIG_FILE_LOCATION', __DIR__.DIRECTORY_SEPARATOR.'config.php');
require_once $bpc.'class.cms_config.php';
cms_config::get_instance();
define('CMS_DEFAULT_VERSIONCHECK_URL','https://www.cmsmadesimple.org/latest_version.php');
define('CMS_SECURE_PARAM_NAME','_sk_');
define('CMS_USER_KEY','_userkey_');

require_once $bpc.'class.CmsApp.php';
require_once $bp.'autoloader.php';

require_once $mp;
$modops = ModuleOperations::get_instance();
$modinst = $modops->get_module_instance($ary[0], '', true);
if ($modinst) {
	$id = $ary[1];
	$params = $modops->GetModuleParameters($id);
	unset($modops); //OR keep this?

	require_once $bp.'page.functions.php';
	require_once $bp.'translation.functions.php';
	require_once $bpc.'class.CmsNlsOperations.php';
	require_once $bpc.'class.Events.php';

	cms_siteprefs::setup();
	Events::setup();
	CMSMS\CmsAuditManager::init();

	if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
	}
	// fix for IIS (and others)
	if (!isset($_SERVER['REQUEST_URI'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
		}
	}
	// sanitize some superglobals
	cleanArray($_SERVER);
	cleanArray($_GET);

	$modinst->DoActionJob($ary[2], $id, $params);
}

exit;
