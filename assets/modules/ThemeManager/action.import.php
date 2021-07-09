<?php
/*
Import-theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;
use ThemeManager\Utils;
use function CMSMS\sanitizeVal;

if (!isset($gCms) || !($gCms instanceof App)) {
	exit;
}

$ajax = !empty($params['ajax']);
$handle_error = function ($msg) use ($ajax) {
	if ($ajax) {
		//TODO signal failure, send msg
		exit;
	} else {
		$this->SetError($msg);
		$this->RedirectToAdminTab('themes');
	}
};

if (!class_exists('SimpleXMLElement')) {
	$handle_error($this->Lang('err_xml_extension', 'SimpleXML'));
}

if (!$this->CheckPermission('Modify Themes')) { //OR WHATEVER
	$handle_error($this->Lang('nopermission'));
}

// check for uploaded file
$key = $id.'import_file';
if (!isset($_FILES[$key]) || $_FILES[$key]['name'] == '') {
	$handle_error($this->Lang('err_noupload'));
}
if ($_FILES[$key]['error'] != 0 || $_FILES[$key]['tmp_name'] == '' || $_FILES[$key]['type'] == '') {
	$handle_error($this->Lang('err_uploading'));
}
if ($_FILES[$key]['type'] != 'text/xml') {
	$handle_error($this->Lang('err_upload_filetype', $_FILES[$key]['type']));
}

$xmlfile = $_FILES[$key]['tmp_name'];
libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
if ($xml === false) {
	$msg = $this->Lang('err_xmlcontent');
	@unlink($xmlfile);

	foreach (libxml_get_errors() as $error) {
		$msg .= '<br />'.'Line '.$error->line.': '.$error->message;
	}
	libxml_clear_errors();
	$handle_error($msg);
}

$val = (string)$xml->dtdversion;
if (version_compare($val, ThemeManager::DTD_MINVERSION) < 0) {
	unlink($xmlfile);
	$handle_error($this->Lang('xmlversion'));
}

//TODO process metadata etc
// <!ELEMENT name
// <!ELEMENT version
// <!ELEMENT metadata

$meta = (string)$xml->metadata;
$props = parse_ini_string($meta);
//TODO process metdata name, version, ...

$themename = (string)$xml->name;
$themename = 'ImportTester'; //DEBUG
$dirname = sanitizeVal($themename, CMSSAN_FILE); // OR CMSSAN_PATH for a sub-theme?
$basepath = CMS_THEMES_PATH . DIRECTORY_SEPARATOR . $dirname;
if (is_dir($basepath)) {
	//TODO abort if not updating
}

// process dirs node
$typenode = $xml->dirs;
if ($typenode->count() > 0) {
	foreach ($typenode->children() as $node) {
		$rel = (string)$node;
		$fp = $basepath . DIRECTORY_SEPARATOR . strtr($rel, '\\/',  DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
		if (!(is_dir($fp) || @mkdir($fp, 0771, true))) {
			//TODO handle error
		}
	}
}

// process files node
$typenode = $xml->files;
if ($typenode->count() > 0) {
	foreach ($typenode->children() as $node) {
		$rel = (string)$node->relpath;
		$fp = $basepath. DIRECTORY_SEPARATOR . strtr($rel, '\\/' , DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
		if ((string)$node->encoded) {
			if (@file_put_contents($fp, base64_decode((string)$node->content)) === false) {
				//TODO handle error
			}
		} elseif (@file_put_contents($fp, htmlspecialchars_decode((string)$node->content), ENT_XML1 | ENT_NOQUOTES) === false) {
			//TODO handle error
		}
	}
}
unlink($xmlfile);

$utils = new Utils();
$lines = [];
$props['name'] = $themename;
$props['created'] = $props['modified'] = date('Y-m-d');
foreach ($props as $key => $val) {
	$lines[] = $key . ' = ' . $utils->smartquote($val);
}

$fp = $basepath . DIRECTORY_SEPARATOR . 'Theme.cfg';
$fh = fopen($fp, 'wb');
if ($fh) {
	fwrite($fh, implode("\n", $lines));
	fwrite($fh, "\n");
	fclose($fh);
} else {
	$handle_error($this->Lang('err_file_save'));
}

$utils->create_manifest($themename);

if ($ajax) {
	//TODO signal success
	exit;
} else {
	$this->RedirectToAdminTab();
}
