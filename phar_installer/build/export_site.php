#!/usr/bin/env php
<?php
/*
Script to export site content to a XML file. Intended primarily for demonstration-content.
This works from an installed site i.e. populated rdatabase, config, autoloader etc can be used.
May be used standalone or included somewhere relevant in the phar-construction process.
*/

global $CMS_INSTALL_PAGE;
$LONE = empty($CMS_INSTALL_PAGE);

if ($LONE) {
    require_once dirname(__FILE__, 3).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
}
$config = cms_config::get_instance();
$db = new CMSMS\Database\mysqli\Connection($config);

//data arrangement
//mostly table names and fieldnames, must be manually reconciled with schema
$skeleton = [
 'designs' => [
  'table' => 'layout_designs',
  'subtypes' => [
   'design' => [
    'id' => [],
    'name' => [],
    'description' => [],
    'dflt' => ['notempty'=>1],
   ]
  ]
 ],
 'stylesheets' => [
  'table' => 'layout_stylesheets',
  'subtypes' => [
   'stylesheet' => [
    'id' => [],
    'name' => [],
    'description' => ['notempty' => 1],
    'media_type' => ['notempty' => 1],
    'content' => ['isdata'=>1],
   ]
  ]
 ],
 'designstyles' => [
  'sql' => 'SELECT * FROM %slayout_design_cssassoc ORDER BY css_id,item_order',
  'subtypes' => [
   'designcss' => [
    'design_id' => [],
    'css_id' => [],
    'item_order' => ['notempty' => 1],
   ]
  ]
 ],
 'tpltypes' => [
  'table' => 'layout_tpl_type',
  'subtypes' => [
   'tpltype' => [
    'id' => [],
    'name' => [],
    'description' => ['notempty' => 1],
    'originator' => [],
    'one_only' => ['notempty' => 1],
    'has_dflt' => ['notempty' => 1],
    'dflt_contents' => ['isdata' => 1, 'notempty' => 1],
    'requires_contentblocks' => ['notempty' => 1],
    'lang_cb' => ['notempty' => 1],
    'dflt_content_cb' => ['notempty' => 1],
    'help_content_cb' => ['notempty' => 1],
   ]
  ]
 ],
 'categories' => [
  'table' => 'layout_tpl_categories',
  'subtypes' => [
   'category' => [
    'id' => [],
    'name' => [],
    'description' => ['notempty' => 1],
    'item_order' => ['notempty' => 1],
   ]
  ]
 ],
 'templates' => [
  'table' => 'layout_templates',
  'subtypes' => [
   'template' => [
    'id' => [],
    'name' => [],
    'description' => ['notempty' => 1],
    'type_id' => [],
    'category_id' => ['notempty' => 1],
    'type_dflt' => ['notempty' => 1],
    'content' => ['isdata'=>1],
   ]
  ]
 ],
 'designtemplates' => [
  'sql' => 'SELECT * FROM %slayout_design_tplassoc ORDER BY tpl_id,tpl_order',
  'subtypes' => [
   'designtpl' => [
    'design_id' => [],
    'tpl_id' => [],
    'tpl_order' => ['notempty' => 1],
   ]
  ]
 ],
 'categorytemplates' => [
  'sql' => 'SELECT * FROM %slayout_cat_tplassoc ORDER BY tpl_id,tpl_order',
  'subtypes' => [
   'cattpl' => [
    'category_id' => [],
    'tpl_id' => [],
    'tpl_order' => ['notempty' => 1],
   ]
  ]
 ],
 'pages' => [
  'table' => 'content',
  'subtypes' => [
   'page' => [
    'content_id' => [],
    'content_name' => [],
    'content_alias' => [],
    'type' => [],
    'template_id' => [],
    'parent_id' => [],
    'active' => ['keeps'=>[1]],
    'default_content' => ['keeps'=>[1]],
    'show_in_menu' => ['keeps'=>[1]],
    'menu_text' => ['isdata'=>1],
    'cachable' => ['keeps'=>[1]],
   ]
  ]
 ],
 'properties' => [
  'table' => 'content_props',
  'subtypes' => [
   'property' => [
    'content_id' => [],
    'prop_name' => [],
    'content' => ['isdata'=>1],
   ]
  ]
 ],
];

if (empty($xmlfile)) {
	//filename not specified by includer: use default
	$xmlfile = 'democontent.xml';
}
$outfile = __DIR__.DIRECTORY_SEPARATOR.$xmlfile;
@unlink($outfile);

$xw = new XMLWriter();
$xw->openUri('file://'.$outfile);
$xw->setIndent(true);
$xw->setIndentString("\t");
$xw->startDocument('1.0', 'UTF-8');

//these data must be manaully reconciled with $skeleton[] above
$xw->writeDtd('cmsmsinstall', null, null,
'<!ELEMENT designs? (design+)>
<!ELEMENT design (id,name,description?,dflt?)>
<!ELEMENT id (#PCDATA)>
<!ELEMENT name (#PCDATA)>
<!ELEMENT description (#PCDATA)>
<!ELEMENT dflt (#PCDATA)>
<!ELEMENT stylesheets? (stylesheet+)>
<!ELEMENT stylesheet (id,name,description?,media_type?,content)>
<!ELEMENT media_type (#PCDATA)>
<!ELEMENT content (#PCDATA)>
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
<!ELEMENT templates? (template)>
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
<!ELEMENT pages? (page+)>
<!ELEMENT page (content_id,content_name,content_alias?,type,template_id,parent_id,active?,default_content?,show_in_menu?,menu_text,cachable?)>
<!ELEMENT content_id (#PCDATA)>
<!ELEMENT content_name (#PCDATA)>
<!ELEMENT content_alias (#PCDATA)>
<!ELEMENT type (#PCDATA)>
<!ELEMENT template_id (#PCDATA)>
<!ELEMENT parent_id (#PCDATA)>
<!ELEMENT active (#PCDATA)>
<!ELEMENT default_content (#PCDATA)>
<!ELEMENT show_in_menu (#PCDATA)>
<!ELEMENT menu_text (#PCDATA)>
<!ELEMENT cacheable (#PCDATA)>
<!ELEMENT properties? (property+)>
<!ELEMENT property (content_id,prop_name,content)>
<!ELEMENT prop_name (#PCDATA)>
');

$xwm = new XMLWriter();
$xwm->openMemory();
$xwm->setIndent(false); //self-managed indentation

function fill_section($structarray, $thistype, $indent)
{
	global $xwm, $db;

	$pref = "\n".str_repeat("\t", $indent);
	$props = $structarray[$thistype];

	if (!empty($props['table'])) {
		$contents = reset($props['subtypes']);
		$fields = implode(',',array_keys($contents));
		$sql = 'SELECT '.$fields.' FROM '.CMS_DB_PREFIX.$props['table'];
	} elseif (!empty($props['sql'])) {
		$sql = sprintf($props['sql'], CMS_DB_PREFIX);
	} elseif (empty($props['subtypes'])) {
		$sql = '';
    } else {
		$xwm->text($pref);
		$xwm->startElement($thistype);
		foreach ($props['subtypes'] as $one=>$dat) {
			fill_section($props['subtypes'], $one, $indent+1);
		}
		$xwm->text($pref);
		$xwm->endElement(); //$thistype
	}
	if ($sql) {
		$rows = $db->getArray($sql);
		if ($rows) {
			$xwm->text($pref);
			$xwm->startElement($thistype);
			$name = key($props['subtypes']);
			foreach ($rows as $row) {
				$xwm->text($pref."\t");
				$xwm->startElement($name);
				foreach ($row as $key=>$val) {
					$A = $props['subtypes'][$name][$key];
					if ((empty($A['keeps']) || in_array($val, $A['keeps'])) &&
						($val || !isset($A['notempty']))) {
						$xwm->text($pref."\t\t");
						if (isset($A['isdata'])) {
							$xwm->startElement($key);
							$xwm->writeCdata(htmlspecialchars($val, ENT_XML1));
							$xwm->endElement();
						} else {
							$xwm->writeElement($key, (string)$val);
						}
					}
				}
				$xwm->text($pref."\t");
				$xwm->endElement();
			}
			$xwm->text($pref);
			$xwm->endElement(); //$thistype
		}
	}
}

$xw->startElement('cmsmsinstall');

foreach ($skeleton as $one=>$props) {
	fill_section($skeleton, $one, 1);
	$xw->writeRaw($xwm->flush());
}

$xw->text("\n");
$xw->endElement(); // cmsmsinstall
$xw->endDocument();
$xw->flush(false);
