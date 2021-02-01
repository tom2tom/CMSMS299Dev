<?php
/*
Export-theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;
use CMSMS\FileTypeHelper;

if (!isset($gCms) || !($gCms instanceof App)) {
	exit;
}

$handlers = ob_list_handlers();
for ($val = 0, $cnt = count($handlers); $val < $cnt; ++$val) {
	ob_end_clean();
}

// for PHP 5.1.2+, XMLWriter is in the core PHP distribution and enabled by default
if (!function_exists('xmlwriter_open_uri')) {
	$this->SetError($this->Lang('err_xml_extension', 'XMLWriter'));
//	$this->RedirectToAdminTab('themes');
	$this->Redirect($id, 'defaultadmin');
}
/*
if (!$this->CheckPermission('Modify Themes')) { //OR WHATEVER
	$this->SetError($this->Lang('nopermission'));
//	$this->RedirectToAdminTab('themes');
	$this->Redirect($id, 'defaultadmin');
}
*/
/*if !empty($params['custom']) {
 TODO support processing some/all content as 'custom'
}
*/

$themename = sanitizeVal($params['theme'], 3);
$xmlfile = 'CMSMS-Theme-' . $themename;
$outfile = PUBLIC_CACHE_LOCATION . DIRECTORY_SEPARATOR . $xmlfile . '.xmltmp';
@unlink($outfile);

$xw = new XMLWriter();
$xw->openUri('file://'.$outfile);
//$xw->openMemory();
$xw->setIndent(true);
$xw->setIndentString("\t");
$xw->startDocument('1.0', 'UTF-8');

$xw->writeDtd('cmsmstheme', null, null, '
 <!ELEMENT dtdversion (#PCDATA)>
 <!ELEMENT name (#PCDATA)>
 <!ELEMENT version (#PCDATA)>
 <!ELEMENT metadata (#PCDATA)>
 <!ELEMENT dirs (relpath*)>
 <!ELEMENT relpath (#PCDATA)>
 <!ELEMENT files (item*)>
 <!ELEMENT item (relpath,encoded?,content)>
 <!ELEMENT encoded (#PCDATA)>
 <!ELEMENT content (#PCDATA)>
');

$xw->startElement('cmsmstheme');
$xw->writeElement('dtdversion', ThemeManager::DTD_VERSION);

$dir = CMS_THEMES_PATH . DIRECTORY_SEPARATOR . $themename;
$items = get_recursive_file_list($dir, [], -1, 'FILES');
if ($items) {
	$meta1 = 'Theme.cfg';
	$meta2 = 'Theme.manifest';

	$path = $dir .DIRECTORY_SEPARATOR . $meta1;
	$data = ''.@file_get_contents($path);
	if (!$data) {
		$xw->endElement(); // cmsmstheme
		$xw->endDocument();
		@unlink($outfile);
		$this->SetError($this->Lang('err_data') . ' ' . $this->Lang('err_metadata'));
//		$this->RedirectToAdminTab('whatever');
		$this->Redirect($id, 'defaultadmin');
	}
	$props = parse_ini_string($data, false, INI_SCANNER_TYPED);
	$val = $props['name'] ?? '<missing>';
	$xw->writeElement('name', $val);
	$val = $props['version'] ?? '<missing>';
	$xw->writeElement('version', $val);
	$xw->startElement('metadata');
	$xw->writeCdata(htmlspecialchars($data, ENT_XML1 | ENT_NOQUOTES));
	$xw->endElement();

	$len = strlen($dir) + 1; // skip to relative path
	$dirs = get_recursive_file_list($dir, [], -1, 'DIRS');
	$xw->startElement('dirs');
	if ($dirs) {
		foreach ($dirs as $path) {
			$rel = substr($path, $len);
			$xw->writeElement('relpath', $rel);
		}
	}
	$xw->endElement(); //dirs

	$xw->startElement('files');

	$helper = new FileTypeHelper();
	foreach ($items as $path) {
		$rel = substr($path, $len);
		switch ($rel) {
			case false:
			case '':
			case '.':
			case '..':
			case $meta1:
			case $meta2:
				continue 2;
//			default:
				// ignore custom updates
//				if (strncmp($rel, 'custom', 6) === 0 && (!isset($rel[7]) || $rel[7] == DIRECTORY_SEPARATOR)) {
//					continue 2;
//				}
		}
		$xw->startElement('item');
		$xw->writeElement('relpath', $rel);

		if ($helper->is_text($path)) {
			$val = 0;
		} else {
			$val = 1;
			$xw->writeElement('encoded', 1);
		}

		$data = ''.@file_get_contents($path);
		$xw->startElement('content');
		if ($val) { // not a text file
			$xw->writeCdata(base64_encode($data));
		} else {
			$xw->writeCdata(htmlspecialchars($data, ENT_XML1 | ENT_NOQUOTES));
		}
		$xw->endElement(); // content

		$xw->endElement(); // item
	}
	$xw->endElement(); // files

	$xw->endElement(); // cmsmstheme
	$xw->endDocument();

	$cnt = $xw->flush(false);
	// send the file
	header('Content-Description: File Transfer');
	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename=CMSMS-Theme-'.$themename.'.xml');
	echo ''.@file_get_contents($outfile);
	unlink($outfile);

	exit;

} // items

$xw->endElement(); // cmsmstheme
$xw->endDocument();
$xw->flush(true);
@unlink($outfile);
$this->SetError($this->Lang('err_data'));
//$this->RedirectToAdminTab('themes');
$this->Redirect($id, 'defaultadmin');
