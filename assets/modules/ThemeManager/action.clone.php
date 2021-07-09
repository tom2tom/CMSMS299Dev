<?php
/*
Clone-theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;
use ThemeManager\Utils;
use function CMSMS\sanitizeVal;

if (!isset($gCms) || !($gCms instanceof App)) {
	exit;
}

$pmod = $this->CheckPermission('Modify Themes');
if (!$pmod) {
	$this->SetError($this->Lang('nopermission'));
	$this->RedirectToAdminTab('themes');
}

if (empty($params['name'])) { $params['name'] = 'Clone Tester'; } //DEBUG

$raw = $params['theme'];
$path = cms_join_path(CMS_THEMES_PATH, sanitizeVal($raw, CMSSAN_FILE)); // OR , CMSSAN_PATH for a sub-theme?
if (is_dir($path)) {
	$topath = cms_join_path(CMS_THEMES_PATH, sanitizeVal($params['name'], CMSSAN_FILE)); // ditto
	if (is_dir($topath)) {
		//TODO handle duplication
	}

	$iter = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$path,
			FilesystemIterator::KEY_AS_FILENAME |
			FilesystemIterator::CURRENT_AS_PATHNAME |
			FilesystemIterator::SKIP_DOTS |
			FilesystemIterator::FOLLOW_SYMLINKS
		),
		RecursiveIteratorIterator::SELF_FIRST
	);

	$len = strlen($path.DIRECTORY_SEPARATOR);
	foreach ($iter as $fp) {
		$relpath = substr($fp, $len);
		$tp = $topath . DIRECTORY_SEPARATOR . $relpath;
		if (!is_dir($fp)) {
			copy($fp, $tp);
		} else {
			mkdir($tp, 0771, true);
		}
	}

	$topath .= DIRECTORY_SEPARATOR . 'Theme.cfg';
	$props = parse_ini_file($topath);
	$props['name'] = trim($params['name']);
	$props['created'] = $props['modified'] = date('Y-m-d');
	$lines = [];
	$utils = new Utils();
	foreach ($props as $key => $val) {
		$lines[] = $key . ' = ' . $utils->smartquote($val);
	}

	$fh = fopen($topath, 'wb');
	if ($fh) {
		fwrite($fh, implode("\n", $lines));
		fwrite($fh, "\n");
		fclose($fh);
	} else {
		$this->SetError($this->Lang('err_file_save'));
	}
} else {
	$this->SetError($this->Lang('err_themename'));
}

$this->RedirectToAdminTab('themes');
