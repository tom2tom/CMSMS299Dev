<?php
#procedure to export an admin theme as xml
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

$name = 'Ghostgum'; //DEBUG
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'include.php';
$all = CmsAdminThemeBase::GetAvailableThemes();

//$all = self::GetAvailableThemes(); //DEBUG
if (!isset($all[$name])) {
	return false;
}

$helper = new \CMSMS\FileTypeHelper($config);
$xw = new \XMLWriter();
/*
$outfile = __DIR__.DIRECTORY_SEPARATOR.$xmlfile;
@unlink($outfile);
$xw->openUri('file://'.$outfile);
*/
$xw->openMemory();
$xw->setIndent(true);
$xw->setIndentString("\t");
$xw->startDocument('1.0', 'UTF-8');

$xw->writeDtd('cmsmsadmintheme', null, null, <<<'EOS'

<!ELEMENT name (#PCDATA)>
<!ELEMENT version? (#PCDATA)>
<!ELEMENT items? (item+)>
<!ELEMENT item (relpath,isdir?,encoded?,content)>
<!ELEMENT relpath (#PCDATA)>
<!ELEMENT isdir (#PCDATA)>
<!ELEMENT encoded (#PCDATA)>
<!ELEMENT content (#PCDATA)>
<!ELEMENT design? (id,name,description?,dflt?)>
<!ELEMENT id (#PCDATA)>
<!ELEMENT description (#PCDATA)>
<!ELEMENT dflt (#PCDATA)>
<!ELEMENT stylesheets? (stylesheet+)>
<!ELEMENT stylesheet (id,name,description?,media_type?,content)>
<!ELEMENT media_type (#PCDATA)>
<!ELEMENT designstyles? (designcss+)>
<!ELEMENT designcss(design_id,css_id,item_order)>
<!ELEMENT design_id (#PCDATA)>
<!ELEMENT css_id (#PCDATA)>
<!ELEMENT item_order (#PCDATA)>
<!ELEMENT tpltypes? (tpltype+)>
<!ELEMENT tpltype (id,name,description?,originator,one_only?,has_dflt?,dflt_contents?,requires_contentblocks?,lang_cb?,dflt_content_cb?,help_content_cb?)>
<!ELEMENT originator (#PCDATA)>
<!ELEMENT one_only (#PCDATA)>
<!ELEMENT has_dflt (#PCDATA)>
<!ELEMENT dflt_contents (#PCDATA)>
<!ELEMENT requires_contentblocks (#PCDATA)>
<!ELEMENT lang_cb (#PCDATA)>
<!ELEMENT dflt_content_cb (#PCDATA)>
<!ELEMENT help_content_cb (#PCDATA)>
<!ELEMENT categories? (category+)>
<!ELEMENT category (id,name,description?,item_order?)>
<!ELEMENT templates? (template+)>
<!ELEMENT template (id,name,description?,type_id,category_id?,type_dflt?,content)>
<!ELEMENT type_id (#PCDATA)>
<!ELEMENT category_id (#PCDATA)>
<!ELEMENT type_dflt (#PCDATA)>
<!ELEMENT designtemplates? (designtpl+)>
<!ELEMENT designtpl(design_id,tpl_id,tpl_order?)>
<!ELEMENT tpl_id (#PCDATA)>
<!ELEMENT tpl_order (#PCDATA)>
<!ELEMENT categorytemplates? (cattpl+)
<!ELEMENT cattpl (category_id,tpl_id,tpl_order?)>

EOS
);

$xw->startElement('cmsmsadmintheme');
$xw->writeElement('name', $name);
//$xw->writeElement('version', TODO);

$dir = dirname($all[$name]);
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
for ($cnt = 0, $val = sizeof($handlers); $cnt < $val; ++$cnt) {
	ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=CMSMS-AdminTheme-'.$name.'.xml');
echo $xw->flush(false);

return true;
