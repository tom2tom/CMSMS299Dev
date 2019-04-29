<?php
/*
admin operation: admin theme export/import/delete
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FileTypeHelper;
use CMSMS\ThemeBase;

const THEME_DTD_VERSION = '1.0';
const THEME_DTD_MINVERSION = '1.0';

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

function import_theme(string $xmlfile) : bool
{
	libxml_use_internal_errors(true);
	$xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
	if ($xml === false) {
		//TODO popup message handling
		$msg = 'Failed to load file '.$xmlfile;
		foreach (libxml_get_errors() as $error) {
			$msg .= '<br />'.'Line '.$error->line.': '.$error->message;
		}
		libxml_clear_errors();
		//cms_notify('error', $msg);
		return false;
	}

	$val = (string)$xml->dtdversion;
	if (version_compare($val, THEME_DTD_MINVERSION) < 0) {
		//cms_notify('error', 'Invalid file format');
		return false;
	}

	$themename = (string)$xml->name;
	$all = ThemeBase::GetAvailableThemes(true);
	if (isset($all[$themename])) {
		// theme is installed now
		include_once $all[$themename];
		$class = $themename.'Theme';
		$current = $class::THEME_VERSION ?? '0';
		$val = (string)$xml->version; //TODO validate this
		if ($current !== '0' && version_compare($val, $current) < 0) {
			//cms_notify('error', 'Incompatible theme version');
			return false;
		}
		$basepath = dirname($all[$themename]);
/*DEBUG    if (!recursive_delete($basepath)) {
			cms_notify('error', 'Failed to clear existing theme data');
			return false;
		}
*/
	} elseif ($themename != 'assets') {
		$basepath = cms_join_path(CMS_ADMIN_PATH, 'themes', $themename);
		if (!(is_dir($basepath) || @mkdir($basepath, 0771, true))) {
			//cms_notify('error', 'Failed to create directory for theme data');
			return false;
		}
	} else {
		//malicious name ignored
		return false;
	}

	foreach ($xml->children() as $typenode) {
		if ($typenode->count() > 0) {
			switch ($typenode->getName()) {
				case 'items':
					foreach ($typenode->children() as $node) {
						$rel = (string)$node->relpath;
						$fp = $basepath.DIRECTORY_SEPARATOR.strtr($rel, ['\\'=>DIRECTORY_SEPARATOR,'/'=>DIRECTORY_SEPARATOR]);
						if ((string)$node->isdir) {
							if (!(is_dir($fp) || @mkdir($fp, 0771, true))) {
								//TODO handle error cms_notify('error', ...)
								//break 2;
							}
						} elseif ((string)$node->encoded) {
							if (@file_put_contents($fp, base64_decode((string)$node->content)) === false) {
								//TODO handle error cms_notify('error', ...)
							}
						} elseif (@file_put_contents($fp, htmlspecialchars_decode((string)$node->content)) === false) {
							//TODO handle error
						}
					}
					break;
/* for future processing of in-database theme data (design, template-groups?, styles, templates)
				case 'designs':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
				case 'stylesheets':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
				case 'templates':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
				case 'templategroups':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
				case 'templategroupmembers':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
				case 'designstyles':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
				case 'designtemplates':
					foreach ($typenode->children() as $node) {
						$row = (array)$node;
						//TODO c.f. site import
					}
					break;
*/
			}
		}
	}

	return true;
}

function export_theme(string $themename) : bool
{
	global $config;

	$all = ThemeBase::GetAvailableThemes(true);
	if (!isset($all[$themename])) {
		return false;
	}

	$helper = new FileTypeHelper($config);
	$xw = new XMLWriter();
/*
	$outfile = __DIR__.DIRECTORY_SEPARATOR.$xmlfile;
	@unlink($outfile);
	$xw->openUri('file://'.$outfile);
*/
	$xw->openMemory();
	$xw->setIndent(true);
	$xw->setIndentString("\t");
	$xw->startDocument('1.0', 'UTF-8');

	$xw->writeDtd('cmsmsadmintheme', null, null, '
 <!ELEMENT dtdversion (#PCDATA)>
 <!ELEMENT name (#PCDATA)>
 <!ELEMENT version (#PCDATA)>
 <!ELEMENT items (item*)>
 <!ELEMENT item (relpath,isdir?,encoded?,content)>
 <!ELEMENT relpath (#PCDATA)>
 <!ELEMENT isdir (#PCDATA)>
 <!ELEMENT encoded (#PCDATA)>
 <!ELEMENT content (#PCDATA)>
 <!ELEMENT description (#PCDATA)>
 <!ELEMENT dflt (#PCDATA)>
 <!ELEMENT stylesheets (stylesheet*)>
 <!ELEMENT stylesheet (id,name,description?,media_type?,content)>
 <!ELEMENT id (#PCDATA)>
 <!ELEMENT media_type (#PCDATA)>
 <!ELEMENT stylesheetgroups (stylesheetgroup*)>
 <!ELEMENT stylesheetgroup (id,name,description?)>
 <!ELEMENT stylesheetgroupmembers (stylesheetgroupmember*)>
 <!ELEMENT stylesheetgroupmember (group_id,css_id,item_order?)>
 <!ELEMENT tpltypes (tpltype*)>
 <!ELEMENT tpltype (id,name,description?,originator,one_only?,has_dflt?,dflt_contents?,requires_contentblocks?,lang_cb?,dflt_content_cb?,help_content_cb?)>
 <!ELEMENT originator (#PCDATA)>
 <!ELEMENT one_only (#PCDATA)>
 <!ELEMENT has_dflt (#PCDATA)>
 <!ELEMENT dflt_contents (#PCDATA)>
 <!ELEMENT requires_contentblocks (#PCDATA)>
 <!ELEMENT lang_cb (#PCDATA)>
 <!ELEMENT dflt_content_cb (#PCDATA)>
 <!ELEMENT help_content_cb (#PCDATA)>
 <!ELEMENT templates (template*)>
 <!ELEMENT template (id,name,description?,type_id,group_id?,type_dflt?,content)>
 <!ELEMENT type_id (#PCDATA)>
 <!ELEMENT group_id (#PCDATA)>
 <!ELEMENT type_dflt (#PCDATA)>
 <!ELEMENT templategroups (templategroup*)>
 <!ELEMENT templategroup (id,name,description?)>
 <!ELEMENT templategroupmembers (templategroupmember*)>
 <!ELEMENT templategroupmember (group_id,tpl_id,item_order?)>
 <!ELEMENT design (id,name,description?)>
 <!ELEMENT designstyles (designcss*)>
 <!ELEMENT designcss (design_id,css_id,css_order)>
 <!ELEMENT design_id (#PCDATA)>
 <!ELEMENT css_id (#PCDATA)>
 <!ELEMENT css_order (#PCDATA)>
 <!ELEMENT designtemplates (designtpl*)>
 <!ELEMENT designtpl (design_id,tpl_id,tpl_order?)>
 <!ELEMENT tpl_id (#PCDATA)>
 <!ELEMENT tpl_order (#PCDATA)>
');

	$xw->startElement('cmsmsadmintheme');
	$xw->writeElement('dtdversion', THEME_DTD_VERSION);
	$xw->writeElement('name', $themename);
	include_once $all[$themename];
	$class = $themename.'Theme';
	$val = $class::THEME_VERSION ?? '0';
	$xw->writeElement('version', $val);

	$dir = dirname($all[$themename]);
	$items = get_recursive_file_list($dir);

	if ($items) {
		$xw->startElement('items');
		$len = strlen($dir) + 1; //skip to relative path
		foreach ($items as $path) {
			$rel = substr($path, $len);
			if ($rel === false || $rel === '') {
				continue;
			}
			$val = @is_dir($path) ? 1:0;

			$xw->startElement('item');
			$xw->writeElement('relpath', $rel);
			if ($val) {
				$xw->writeElement('isdir', $val);
			} else {
				if ($helper->is_text($path)) {
					$val = 0;
				} else {
					$val = 1;
					$xw->writeElement('encoded', 1);
				}
				$data = ''.@file_get_contents($path);
				$xw->startElement('content');
				if ($val) { //not a text file
					$xw->writeCdata(base64_encode($data));
				} else {
					$xw->writeCdata(htmlspecialchars($data, ENT_XML1));
				}
				$xw->endElement(); //content
			}
			$xw->endElement(); // item
		}
		$xw->endElement(); // items
	}

/*
	TODO handle database stuff - design, styles, templates etc
	$config = cms_config::get_instance();
	$db = Cmsapp::get_instance()->GetDb();
*/

	$xw->endElement(); // cmsmsadmintheme
	$xw->endDocument();

	// send the file
	$handlers = ob_list_handlers();
	for ($cnt = 0, $val = count($handlers); $cnt < $val; ++$cnt) {
		ob_end_clean();
	}

	header('Content-Description: File Transfer');
	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename=CMSMS-AdminTheme-'.$themename.'.xml');
	echo $xw->flush(false);

	return true;
}

function delete_theme(string $themename) : bool
{
	$all = ThemeBase::GetAvailableThemes(true);
	if (isset($all[$themename]) && count($all) > 1) {
		if (recursive_delete(dirname($all[$themename]))) {
			//adjust default theme if needed
			$deftheme = cms_siteprefs::get('logintheme');
			if ($deftheme && $deftheme == $themename) {
				unset($all[$themename]);
				cms_siteprefs::set('logintheme', key($all));
			}
			return true;
		}
	}
	return false;
}

if (isset($_FILES['import'])) {
	$userid = get_userid();
	if (check_permission($userid, 'Modify Site Preferences')) {
		if (import_theme($_FILES['import']['tmp_name'])) {
			$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
			redirect('siteprefs.php'.$urlext);
		}
	} else {
		//cms_notify('error', lang('needpermissionto', '"Modify Site Preferences"'));
		exit;
	}
}

if (isset($_POST['export'])) {
	export_theme(cleanValue($_POST['export']));
}

if (isset($_POST['delete'])) {
	if (delete_theme(cleanValue($_POST['delete']))) {
		$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		redirect('siteprefs.php'.$urlext);
	}
}

exit;
