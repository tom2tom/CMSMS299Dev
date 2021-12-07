<?php
/*
Admin functions: site-content export/import
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace cms_installer;

use CMSMS\AppState;
use CMSMS\Database\Connection;
use CMSMS\DataException;
use CMSMS\Route;
use CMSMS\RouteOperations;
use CMSMS\SingleItem;
use CMSMS\Stylesheet;
//use CMSMS\StylesheetOperations;
use CMSMS\StylesheetsGroup;
use CMSMS\Template;
//use CMSMS\TemplateOperations;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
//use DesignManager\Design;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use XMLWriter;
use const CMS_ASSETS_PATH;
use const CMS_DB_PREFIX;
use const CMS_FILETAGS_PATH;
use const CMS_ROOT_PATH;
use function check_permission;
//TODO if running in main system, what namespaces,funcnames ?
use function cms_installer\get_server_permissions;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function error_msg;
use function file_put_contents;
use function get_userid;
use function verbose_msg;

/*
This file is used during site installation (among other uses).
So API's, classes, methods, globals etc must be valid during installation
as well as normal operation.
*/

const CONTENT_DTD_VERSION = '0.9';
const CONTENT_DTD_MINVERSION = '0.9';

/**
 * Populate a section of the XML file related to database content
 * @param XMLWriter $xwm
 * @param Connection $db database connection
 * @param array $structarray
 * @param string $thistype
 * @param int $indent
 */
function fill_section(XMLWriter $xwm, Connection $db, array $structarray, string $thistype, int $indent)
{
	$pref = "\n".str_repeat("\t", $indent);
	$props = $structarray[$thistype];

	if (!empty($props['table'])) {
		$contents = reset($props['subtypes']);
		$fields = array_keys($contents);
		$sql = 'SELECT '.implode(',', $fields).' FROM '.CMS_DB_PREFIX.$props['table'];
	} elseif (!empty($props['sql'])) {
		$sql = sprintf($props['sql'], CMS_DB_PREFIX);
		if (!empty($props['subtypes'])) {
			$contents = $props['subtypes'][key($props['subtypes'])];
			switch (key($contents)) {
				case null:
				case 'isdata':
				case 'optional':
				case 'keeps':
					$fields = array_keys($props['subtypes']);
					break;
				default:
					$fields = array_keys($contents);
			}
		}
	} elseif (empty($props['subtypes'])) {
		$sql = '';
	} else {
		$xwm->text($pref);
		$xwm->startElement($thistype);
		foreach ($props['subtypes'] as $one => $dat) {
			fill_section($xwm, $db, $props['subtypes'], $one, $indent + 1);
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
				if (!isset($fields)) {
					$fields = array_keys($row);
				}
				foreach ($row as $key => $val) {
					if (!in_array($key, $fields)) {
						continue;
					}
					if (isset($props['subtypes'][$name][$key])) {
						$A = $props['subtypes'][$name][$key];
						if ((empty($A['keeps']) || in_array($val, $A['keeps'])) &&
							($val || !isset($A['notempty']))) {
							$xwm->text($pref."\t\t");
							if ($val && isset($A['isdata']) && is_string($val) && !is_numeric($val)) {
								$xwm->startElement($key);
								$xwm->writeCdata(htmlspecialchars($val, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false)); // NOT worth CMSMS\specialize
								$xwm->endElement();
							} else {
								$xwm->writeElement($key, (string)$val);
							}
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

/**
 * Populate a section of the XML file related to filesystem files
 * @param XMLWriter $xw worker object
 * @param string $section xml file section name
 * @param string $frombase source folder filepath
 * @param string $tobase destination folder filepath, or '' if $copyfiles is false
 * @param string $install_relative installer-relative sub-path, or ''
 * @return int no. of copied files
 */
function fill_filessection(XMLWriter $xw, string $section, string $frombase, string $tobase, string $install_relative)
{
	$skip = strlen($frombase) + 1;
	$copied = 0;
	$copyfiles = !empty($tobase);

	$xw->startElement($section);
	$iter = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($frombase,
			FilesystemIterator::KEY_AS_PATHNAME |
			FilesystemIterator::FOLLOW_SYMLINKS |
			FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST);
	//TODO support explicit [list of] name pattern(s) to skip e.g. .htaccess [Ww]eb.config
	foreach ($iter as $p => $info) {
		if (!$info->isDir()) {
			$name = $info->getBasename();
			if (fnmatch('index.htm?', $name, FNM_CASEFOLD)) {
				continue;
			}
			if ($name[0] == '.') {
				continue; // ignore hidden's
			}
			if (strcasecmp('web.config', $name) == 0) {
				continue;
			}
			$tail = substr($p, $skip);
			if ($copyfiles) {
				$tp = $tobase.DIRECTORY_SEPARATOR.$tail;
				$dir = dirname($tp);
				@mkdir($dir, 0771, true); // generic perms: relevant server-permissions applied during install
				if (@copy($p, $tp)) {
					chmod($tp, 0664); // ditto
					++$copied;
				}
			} else {
				//TODO consider embedding files as base64_encoded, esp. if only a few
			}
			$xw->startElement('file');
			$xw->writeElement('name', $name);
			$td = dirname($tail);
			if ($td == '.') {
				$td = '';
			} else {
				$td = strtr($td, '\\', '/'); // always use *NIX separators in these data
			}
			$xw->writeElement('relto', $td); // system-sources-filepath-relative
			if ($install_relative) {
				if ($td !== '') {
					$td = $install_relative.'/'.$td;
				} else {
					$td = $install_relative;
				}
			}
			$xw->writeElement('relfrom', $td); // installer-root-filepath-relative

			$xw->endElement(); // file
		}
	}
	$xw->endElement(); // $section
	return $copied;
}

/**
 * Construct an installer-root-relative sub-path for use in a files section of
 * the xml file
 *
 * @param string $toroot Base filepath, no trailing separator
 * @param string $tobase
 * @return string always having *NIX separators
 */
function file_relpath(string $toroot, string $tobase) : string
{
	$toroot = rtrim($toroot, ' \/');
	$len = strlen($toroot);
	$rel = substr($tobase, $len + 1); //skip base-path + trailing separator
	return ($rel) ? strtr($rel, '\\', '/') : '';
}

/* News-module processing, if that module does not itself install enough on a new site
     'newscategories' => [
      'sql' => 'SELECT * FROM %smodule_news_categories ORDER BY news_category_id',
      'subtypes' => [
       'newscategory' => [
        'news_category_id' => [],
        'news_category_name' => ['isdata'=>1, 'optional' => 1],
        'parent_id' => [],
        'hierarchy' => [],
        'item_order'=> ['optional' => 1],
        'long_name' => ['isdata'=>1, 'optional' => 1],
       ]
      ]
     ],
     'newsitems' => [
      'sql' => 'SELECT * FROM %smodule_news ORDER BY news_category_id',
      'subtypes' => [
       'newsitem' => [
        'news_category_id' => [],
        'news_title' => ['isdata'=>1],
        'news_data' => ['isdata'=>1, 'optional' => 1],
        'news_extra'=> ['optional' => 1],
        'news_url' => ['optional' => 1],
        'summary' => ['isdata'=>1, 'optional' => 1],
       ]
      ]
     ],

 <!ELEMENT newscategories (newscategory*)>
 <!ELEMENT newscategory (news_category_id,news_category_name?,parent_id,hierarchy,item_order?,long_name?)>
 <!ELEMENT news_category_id (#PCDATA)>
 <!ELEMENT news_category_name (#PCDATA)>
 <!ELEMENT hierarchy (#PCDATA)>
 <!ELEMENT long_name (#PCDATA)>
 <!ELEMENT newsitems (newsitem*)>
 <!ELEMENT newsitem (news_category_id,news_title,news_data?,news_extra?,news_url?,summary?)>
 <!ELEMENT news_title (#PCDATA)>
 <!ELEMENT news_data (#PCDATA)>
 <!ELEMENT news_extra (#PCDATA)>
 <!ELEMENT news_url (#PCDATA)>
 <!ELEMENT summary (#PCDATA)>

				case 'newscategories':
					foreach ($typenode->children() as $node) {
					//TODO must do this after modules are installed
					}
					break;
				case 'newsitems':
					foreach ($typenode->children() as $node) {
					//TODO must do this after modules are installed
					}
					break;

     'designs' => [
      'table' => 'module_designs',
      'subtypes' => [
       'design' => [
        'id' => [],
        'name' => [],
        'description' => ['optional' => 1],
       ]
      ]
     ],
     'designstyles' => [
      'sql' => 'SELECT * FROM %smodule_designs_css ORDER BY design_id,css_id,css_order',
      'subtypes' => [
       'designcss' => [
        'design_id' => [],
        'css_id' => [],
        'css_order' => ['optional' => 1],
       ]
      ]
     ],
     'designtemplates' => [
      'sql' => 'SELECT * FROM %smodule_designs_tpl ORDER BY design_id,tpl_id,tpl_order',
      'subtypes' => [
       'designtpl' => [
        'design_id' => [],
        'tpl_id' => [],
        'tpl_order' => ['optional' => 1],
       ]
      ]
     ],

 <!ELEMENT designs (design*)>
 <!ELEMENT design (id,name,description?,dflt?)>
 <!ELEMENT dflt (#PCDATA)>
 <!ELEMENT designstyles (designcss*)>
 <!ELEMENT designcss (design_id,css_id,css_order)>
 <!ELEMENT design_id (#PCDATA)>
 <!ELEMENT css_order (#PCDATA)>
 <!ELEMENT designtemplates (designtpl*)>
 <!ELEMENT designtpl (design_id,tpl_id,tpl_order?)>
 <!ELEMENT tpl_order (#PCDATA)>

				case 'designs':
					//TODO must do this section after modules are installed, and DesignManager is one of them
					if (!class_exists('DesignManager\Design')) { DISABLED
						break; //TODO try to load the class
					}
					foreach ($typenode->children() as $node) {
						$ob = new Design();
						try {
							$ob->set_name((string)$node->name);
							$ob->set_description((string)$node->description);
//							$ob->set_default((bool)$node->dflt);
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install design \''.(string)$node->name.'\' : '.$t->getMessage());
							}
							continue;
						}
						$val = (string)$node->id;
						$designs[(int)$val] = $ob->get_id();
					}
					break;
				case 'designstyles': //stylesheets assigned to designs
					//TODO must do this after modules are installed
					if (!class_exists('DesignManager\Design')) {  DISABLED
						break;
					}
					$bank = [];
					foreach ($typenode->children() as $node) {
						$val = (int)$node->css_id;
						$val2 = (int)$node->design_id;
						if (isset($styles[$val]) && isset($designs[$val2])) {
							$val3 = $styles[$val];
							$bank[$val3][0][] = $designs[$val2];
							$bank[$val3][1][] = (int)$node->css_order;
						}
					}
					foreach ($bank as $sid => $arr) {
						array_multisort($arr[1], $arr[0]);
						try {
							$ob = StylesheetOperations::get_stylesheet($sid);
							$ob->set_designs($arr[0]);
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install stylesheet from design ('.$sid.') : '.$t->getMessage());
							}
							continue;
						}
					}
					break;
				case 'designtemplates': //templates assigned to designs
					//TODO must do this after modules are installed
					if (!class_exists('DesignManager\Design')) {  DISABLED
						break;
					}
					$bank = [];
					foreach ($typenode->children() as $node) {
						$val = (int)$node->tpl_id;
						$val2 = (int)$node->design_id;
						if (isset($templates[$val]) && isset($designs[$val2])) {
							$val3 = $templates[$val];
							$bank[$val3][0][] = $designs[$val2];
							$bank[$val3][1][] = (int)$node->tpl_order;
						}
					}
					foreach ($bank as $tid => $arr) {
						array_multisort($arr[1], $arr[0]);
						try {
							$ob = TemplateOperations::get_template($tid);
							$ob->set_designs($arr[0]);
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install template from design ('.$tid.') : '.$t->getMessage());
							}
							continue;
						}
					}
					break;
*/

/**
 * Export site content (pages, templates, designs, styles etc) to XML file.
 * Support files (in the uploads folder) and user-plugin files, template files
 * and stylesheet files (in their respective assets (however named) sub-folders)
 * are recorded as such, and will be copied into the specified $uploadspath if
 * it exists. Otherwise, that remains a manual task.
 *
 * @param string $xmlfile filesystem path of the putput xml file
 * @param string $uploadspath path of installer-tree folder to contain any
 *  uploaded files to be processed, or empty if file-copying is disabled
 * @param string $workerspath path of installer-tree folder to contain any
 *  custom page-production (etc) files to be processed, or empty if file-copying is disabled
 * @param Connection $db database connection object
 */
function export_content(string $xmlfile, string $uploadspath, string $workerspath, Connection $db)
{
	/* data arrangement
	mostly, table- and field-names must be manually reconciled with database schema
	optional sub-key parameters:
	 isdata >> process field value via htmlspecialchars($val,
	  ENT_XML1 | ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') to prevent parser confusion
	 optional >> ignore/omit a field whose value is falsy i.e. optional item in the dtd
	 keeps >> array of field-value(s) which will be included (subject to optional)
	*/
	$corename = TemplateType::CORE;

	$skeleton = [
     'stylegroups' => [
      'table' => 'layout_css_groups',
      'subtypes' => [
       'group' => [
        'id' => [],
        'name' => [],
        'description' => ['optional' => 1],
       ]
      ]
     ],
     'stylesheets' => [
      'sql' => 'SELECT * FROM %slayout_stylesheets WHERE originator=\''.$corename.'\' OR originator LIKE \'%%Theme\' ORDER BY REPLACE(originator,\'_\',\' \'),name',
      'subtypes' => [
       'stylesheet' => [
        'id' => [],
        'originator' => [],
        'name' => [],
        'description' => ['optional' => 1],
        'media_type' => ['optional' => 1],
        'media_query' => ['optional' => 1],
        'owner_id' => ['optional' => 1],
        'type_id' => ['optional' => 1],
        'type_dflt' => ['optional' => 1],
        'listable' => ['optional' => 1],
        'contentfile' => ['optional' => 1],
        'content' => ['isdata' => 1],
       ]
      ]
     ],
     'stylegroupmembers' => [
      'sql' => 'SELECT * FROM %slayout_cssgroup_members ORDER BY css_id,item_order',
      'subtypes' => [
       'grpcss' => [
        'group_id' => [],
        'css_id' => [],
        'item_order' => ['optional' => 1],
       ]
      ]
     ],
     'templatetypes' => [
      'sql' => 'SELECT * FROM %slayout_tpl_types WHERE originator=\''.$corename.'\' OR originator LIKE \'%%Theme\' ORDER BY REPLACE(originator,\'_\',\' \'),name',
      'subtypes' => [
       'tpltype' => [
        'id' => [],
        'originator' => [],
        'name' => [],
        'description' => ['optional' => 1],
        'lang_cb' => ['optional' => 1],
        'dflt_content_cb' => ['optional' => 1],
        'help_content_cb' => ['optional' => 1],
        'has_dflt' => ['optional' => 1],
        'requires_contentblocks' => ['optional' => 1],
        'one_only' => ['optional' => 1],
        'owner_id' => ['optional' => 1],
        'dflt_content' => ['isdata' => 1, 'optional' => 1],
       ]
      ]
     ],
     'templategroups' => [
      'table' => 'layout_tpl_groups',
      'subtypes' => [
       'group' => [
        'id' => [],
        'name' => [],
        'description' => ['optional' => 1],
        'create_date' => [],
        'modified_date' => ['optional' => 1],
       ]
      ]
     ],
     'templates' => [
      'sql' => 'SELECT * FROM %slayout_templates WHERE originator=\''.$corename.'\' OR originator LIKE \'%%Theme\' ORDER BY REPLACE(originator,\'_\',\' \'),name',
      'subtypes' => [
       'template' => [
        'id' => [],
        'originator' => [],
        'name' => [],
        'description' => ['optional' => 1],
        'hierarchy' => ['optional' => 1],
        'owner_id' => ['optional' => 1],
        'type_id' => ['optional' => 1],
        'type_dflt' => ['optional' => 1],
        'listable' => ['optional' => 1],
        'contentfile' => ['optional' => 1],
        'content' => ['isdata' => 1],
       ]
      ]
     ],
     'templategroupmembers' => [
      'sql' => 'SELECT * FROM %slayout_tplgroup_members ORDER BY tpl_id,item_order',
      'subtypes' => [
       'grptpl' => [
        'group_id' => [],
        'tpl_id' => [],
        'item_order' => ['optional' => 1],
       ]
      ]
     ],
     'pages' => [
      'sql' => 'SELECT * FROM %scontent ORDER BY parent_id,content_id',
      'subtypes' => [
       'page' => [
        'content_id' => [],
        'content_name' => [],
        'type' => [],
        'default_content' => ['optional' => 1],
        'show_in_menu' => ['optional' => 1],
        'active' => ['optional' => 1],
        'cachable' => ['optional' => 1],
        'secure' => ['optional' => 1],
        'owner_id' => ['optional' => 1],
        'parent_id' => [],
        'template_id' => [],
        'item_order' => [],
        'menu_text' => ['optional' => 1, 'isdata' => 1],
        'content_alias' => ['optional' => 1],
        'metadata' => ['optional' => 1],
        'titleattribute' => ['optional' => 1],
        'page_url' => ['optional' => 1],
        'tabindex' => ['optional' => 1],
        'accesskey' => ['optional' => 1],
        'styles' => [],
       ]
      ]
     ],
     'properties' => [
      'table' => 'content_props',
      'subtypes' => [
       'property' => [
        'content_id' => [],
        'prop_name' => [],
        'content' => ['isdata' => 1],
       ]
      ]
     ],
     'usertags' => [
      'table' => 'userplugins',
      'subtypes' => [
        'tag' => [
        'name' => [],
        'description' => ['isdata' => 1, 'optional' => 1],
        'parameters' => ['isdata' => 1, 'optional' => 1],
        'contentfile' => ['optional' => 1],
        'code' => ['isdata' => 1],
       ]
      ]
     ],
    ];

	@unlink($xmlfile);

	//worker-object
	$xwm = new XMLWriter();
	$xwm->openMemory();
	$xwm->setIndent(false); //self-managed indentation

	$xw = new XMLWriter();
	$xw->openUri('file://'.$xmlfile);
	$xw->setIndent(true);
	$xw->setIndentString("\t");
	$xw->startDocument('1.0', 'UTF-8');

	//these xml data must be manually reconciled with $skeleton[] above
	$xw->writeDtd('cmsmssitedata', null, null, '
 <!ELEMENT dtdversion (#PCDATA)>
 <!ELEMENT file (name,relto,(relfrom|embedded),content?)>
 <!ELEMENT name (#PCDATA)>
 <!ELEMENT relto (#PCDATA)>
 <!ELEMENT relfrom (#PCDATA)>
 <!ELEMENT embedded (#PCDATA)>
 <!ELEMENT content (#PCDATA)>
 <!ELEMENT stylefiles (file*)>
 <!ELEMENT templatefiles (file*)>
 <!ELEMENT usertagfiles (file*)>
 <!ELEMENT themefiles (file*)>
 <!ELEMENT uploadfiles (file*)>
 <!ELEMENT stylegroups (stylegroup*)>
 <!ELEMENT stylegroup (id,name,description?)>
 <!ELEMENT id (#PCDATA)>
 <!ELEMENT description (#PCDATA)>
 <!ELEMENT stylesheets (stylesheet*)>
 <!ELEMENT stylesheet (id,originator,name,description?,media_type?,media_query?,owner_id?,type_id?,type_dflt?,listable?,contentfile?,content)>
 <!ELEMENT originator (#PCDATA)>
 <!ELEMENT media_type (#PCDATA)>
 <!ELEMENT media_query (#PCDATA)>
 <!ELEMENT owner_id (#PCDATA)>
 <!ELEMENT type_id (#PCDATA)>
 <!ELEMENT type_dflt (#PCDATA)>
 <!ELEMENT listable (#PCDATA)>
 <!ELEMENT contentfile (#PCDATA)>
 <!ELEMENT stylegroupmembers (stylegroupmember*)>
 <!ELEMENT stylegroupmember (group_id,css_id,item_order?)>
 <!ELEMENT group_id (#PCDATA)>
 <!ELEMENT css_id (#PCDATA)>
 <!ELEMENT item_order (#PCDATA)>
 <!ELEMENT templatetypes (tpltype*)>
 <!ELEMENT tpltype (id,originator,name,description?,lang_cb?,dflt_content_cb?,help_content_cb?,has_dflt?,requires_contentblocks?,one_only?,owner_id?,dflt_content?)>
 <!ELEMENT lang_cb (#PCDATA)>
 <!ELEMENT dflt_content_cb (#PCDATA)>
 <!ELEMENT help_content_cb (#PCDATA)>
 <!ELEMENT has_dflt (#PCDATA)>
 <!ELEMENT requires_contentblocks (#PCDATA)>
 <!ELEMENT one_only (#PCDATA)>
 <!ELEMENT dflt_content (#PCDATA)>
 <!ELEMENT templategroups (templategroup*)>
 <!ELEMENT templategroup (id,name,description?)>
 <!ELEMENT templates (template*)>
 <!ELEMENT template (id,originator,name,description?,hierarchy?,owner_id?,type_id?,type_dflt?,listable?,contentfile?,content)>
 <!ELEMENT hierarchy (#PCDATA)>
 <!ELEMENT templategroupmembers (templategroupmember*)>
 <!ELEMENT templategroupmember (group_id,tpl_id,item_order?)>
 <!ELEMENT tpl_id (#PCDATA)>
 <!ELEMENT pages (page*)>
 <!ELEMENT page (content_id,content_name,type,default_content?,show_in_menu?,active?,cachable?,secure?,owner_id,parent_id,template_id,item_order,menu_text?,content_alias?,metadata?,titleattribute?,page_url?,tabindex?,accesskey?,styles?)>
 <!ELEMENT content_id (#PCDATA)>
 <!ELEMENT content_name (#PCDATA)>
 <!ELEMENT type (#PCDATA)>
 <!ELEMENT default_content (#PCDATA)>
 <!ELEMENT show_in_menu (#PCDATA)>
 <!ELEMENT active (#PCDATA)>
 <!ELEMENT cacheable (#PCDATA)>
 <!ELEMENT secure (#PCDATA)>
 <!ELEMENT parent_id (#PCDATA)>
 <!ELEMENT template_id (#PCDATA)>
 <!ELEMENT menu_text (#PCDATA)>
 <!ELEMENT content_alias (#PCDATA)>
 <!ELEMENT metadata (#PCDATA)>
 <!ELEMENT titleattribute (#PCDATA)>
 <!ELEMENT page_url (#PCDATA)>
 <!ELEMENT tabindex (#PCDATA)>
 <!ELEMENT accesskey (#PCDATA)>
 <!ELEMENT styles (#PCDATA)>
 <!ELEMENT properties (property+)>
 <!ELEMENT property (content_id,prop_name,content)>
 <!ELEMENT prop_name (#PCDATA)>
 <!ELEMENT usertags (tag*)>
 <!ELEMENT tag (name,description?,parameters?,contentfile?,code)>
 <!ELEMENT parameters (#PCDATA)>
 <!ELEMENT code (#PCDATA)>
');

	$xw->startElement('cmsmssitedata');
	$xw->writeElement('dtdversion', CONTENT_DTD_VERSION);

	foreach ($skeleton as $one => $props) {
		fill_section($xwm, $db, $skeleton, $one, 1);
		$xw->writeRaw($xwm->flush());
	}

	$xw->text("\n");

	$config = SingleItem::Config();
	$frombase = $config['uploads_path'];
	if (is_dir($frombase)) {
		$copyfiles = is_dir($uploadspath);
		if ($copyfiles) {
			rrmdir($uploadspath, false); //clear it
			$tobase = $uploadspath;
			$install_relative = file_relpath($uploadspath, $tobase); //stored in intaller-top-sub-folder
		} else {
			$copyfiles = @mkdir($uploadspath, 0771, true); // generic perms pending actual server value
			$tobase = ($copyfiles) ? $uploadspath : '';
			$install_relative = ($tobase) ? file_relpath($uploadspath, $tobase) : '';
		}
		$copycount = fill_filessection($xw, 'uploadfiles', $frombase, $tobase, $install_relative);
		if ($copyfiles && $copycount == 0) {
			//nothing here, maybe subfolders needed later
		}
	}

	$copyfiles = is_dir($workerspath);
	if ($copyfiles) {
		rrmdir($workerspath, false); //clear it
	} else {
		$copyfiles = @mkdir($workerspath, 0771, true);
	}
	$frombase = CMS_FILETAGS_PATH;
	if (is_dir($frombase)) {
		//TODO ensure any such files are omitted from the sources-only archive
		if ($copyfiles) {
			$tobase = $workerspath.DIRECTORY_SEPARATOR.'user_plugins'; // 'definite' basename for importing
			if (is_dir($tobase)) {
				//done before rrmdir($tobase, false);
				$install_relative = file_relpath($workerspath, $tobase); //stored in intaller-top-sub-folder
			} elseif (@mkdir($tobase, 0771, true)) {
				$install_relative = file_relpath($workerspath, $tobase);
			} else {
				$install_relative = '';
			}
		} else {
			$tobase = '';
			$install_relative = '';
		}
		$copycount = fill_filessection($xw, 'usertagfiles', $frombase, $tobase, $install_relative);
		if ($copyfiles && $copycount == 0) {
			@rmdir($tobase);
		}
	}

	$frombase = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'templates';
	if (is_dir($frombase)) {
		//TODO ensure any such files are omitted from the sources tarball
		if ($copyfiles) {
			$tobase = $workerspath.DIRECTORY_SEPARATOR.'templates';
			if (is_dir($tobase)) {
				//done before rrmdir($tobase, false);
				$install_relative = file_relpath($workerspath, $tobase); //stored in intaller-top-sub-folder
			} elseif (@mkdir($tobase, 0771, true)) {
				$install_relative = file_relpath($workerspath, $tobase);
			} else {
				$install_relative = '';
			}
		} else {
			$tobase = '';
			$install_relative = '';
		}
		$copycount = fill_filessection($xw, 'templatefiles', $frombase, $tobase, $install_relative);
		if ($copyfiles && $copycount == 0) {
			@rmdir($tobase);
		}
	}

	$frombase = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'styles';
	if (is_dir($frombase)) {
		//TODO ensure any such files are omitted from the sources tarball
		if ($copyfiles) {
			$tobase = $workerspath.DIRECTORY_SEPARATOR.'styles';
			if (is_dir($tobase)) {
				//done before rrmdir($tobase, false);
				$install_relative = file_relpath($workerspath, $tobase); //stored in intaller-top-sub-folder
			} elseif (@mkdir($tobase, 0771, true)) {
				$install_relative = file_relpath($workerspath, $tobase);
			} else {
				$install_relative = '';
			}
		} else {
			$tobase = '';
			$install_relative = '';
		}
		$copycount = fill_filessection($xw, 'stylefiles', $frombase, $tobase, $install_relative);
		if ($copyfiles && $copycount == 0) {
			@rmdir($tobase);
		}
	}

	$frombase = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'themes';
	if (is_dir($frombase)) {
		//TODO ensure any such files are omitted from the sources tarball
		if ($copyfiles) {
			$tobase = $workerspath.DIRECTORY_SEPARATOR.'themes';
			if (is_dir($tobase)) {
				//done before rrmdir($tobase, false);
				$install_relative = file_relpath($workerspath, $tobase); //stored in intaller-top-sub-folder
			} elseif (@mkdir($tobase, 0771, true)) {
				$install_relative = file_relpath($workerspath, $tobase);
			} else {
				$install_relative = '';
			}
		} else {
			$tobase = '';
			$install_relative = '';
		}
		$copycount = fill_filessection($xw, 'themefiles', $frombase, $tobase, $install_relative);
		if ($copyfiles && $copycount == 0) {
			@rmdir($tobase);
		}
	}

	$xw->endElement(); // cmsmsinstall
	$xw->endDocument();
	$xw->flush(false);
}

/**
 * Import site content
 * @param string $xmlfile filesystem path of file to import
 * @param string $uploadspath Optional 'non-default' filesystem path of
 *  folder containing 'uploaded' files e.g. images, iconfonts.
 * @param string $workerspath Optional 'non-default' filesystem path of
 *  folder containing non-db-stored operation files e.g. templates, css, user-plugins
 * @return string status/error message or ''
 */
function import_content(string $xmlfile, string $uploadspath = '', string $workerspath = '') : string
{
	// security checks right here, to supplement upstream/external
	if (AppState::test(AppState::INSTALL)) {
		$runtime = function_exists('lang');
		//NOTE must conform this class with installer
		$valid = class_exists('cms_installer\wizard\wizard'); //TODO some other check too
	} else {
		$runtime = true;
		$userid = get_userid(false);
		if ($userid) {
			$valid = check_permission($userid, 'Manage All Content');
		} else {
			// TODO etc e.g. when force-feeding, maybe async
			$valid = false;
		}
	}
	if (!$valid) {
		return ''; //silent exit
	}

	$modes = get_server_permissions();
	$filemode = $modes[1]; // read + write
	$dirmode = $modes[3]; // read + write + access

	libxml_use_internal_errors(true);
	$xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
	if ($xml === false) {
		if ($runtime) {
			$val = lang('error_filebad', $xmlfile);
		} else {
			$val = 'Failed to load file '.$xmlfile; //TODO lang('')
		}
		foreach (libxml_get_errors() as $error) {
			$val .= "\n".'Line '.$error->line.': '.$error->message;
		}
		libxml_clear_errors();
		return $val;
	}

	$val = (string)$xml->dtdversion;
	if (version_compare($val, CONTENT_DTD_MINVERSION) < 0) {
		if ($runtime) {
			return lang('error_filebad', $xmlfile);
		} else {
			return 'Invalid file format';
		}
	}

	$corename = TemplateType::CORE;
	$styles = [-1 => -1];
	$cssgrps = [-1 => -1];
	$types = [-1 => -1];
	$templates = [-1 => -1];
	$tplgrps = [-1 => -1];
//	$designs = [-1 => -1];
	$pages = [];

	foreach ($xml->children() as $typenode) {
		if ($typenode->count() > 0) {
			switch ($typenode->getName()) {
				case 'stylesheets':
					if ($runtime) {
						verbose_msg(lang('install_stylesheets'));
					}
					foreach ($typenode->children() as $node) {
						$val = (string)$node->originator;
						// process if anonymous, core- or theme-sourced (any other sheet-data installed elsewhere)
						if ($val) {
							if (!($val == '__CORE__' || endswith($val, 'Theme'))) {
								continue;
							}
						}
						$ob = new Stylesheet();
						try {
							$ob->set_originator(($val) ? $val : '__CORE_');
							$ob->set_name((string)$node->name);
							$ob->set_description((string)$node->description);
							if ((string)$node->media_type) {
								$ob->set_media_types((string)$node->media_type); //TODO don't assume a single type
							}
							if ((string)$node->media_query) {
								$ob->set_media_query((string)$node->media_query);
							}
							$ob->set_owner(1);
							$val = (string)$node->type_id;
							$ob->set_type((int)$val);
							$val = (string)$node->type_dflt;
							$ob->set_type_default((bool)$val);
							$val = (string)$node->listable;
							$ob->set_listable((bool)$val);
							$val = (string)$node->contentfile;
							$ob->set_content_file((bool)$val);
							$ob->set_content(htmlspecialchars_decode((string)$node->content, ENT_XML1 | ENT_QUOTES)); // NOT worth CMSMS\de_specialize
							$ob->save();
						} catch (DataException $e) {
							if ($runtime) {
								error_msg('Failed to install stylesheet \''.(string)$node->name).'\' : '.$e->getMessage();
							}
							continue;
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install stylesheet \''.(string)$node->name.'\' : '.$t->getMessage());
							}
							continue;
						}
						$val = (string)$node->id;
						$styles[(int)$val] = $ob->get_id(); // map new to old
					}
					break;
				case 'stylegroups':
					foreach ($typenode->children() as $node) {
						$ob = new StylesheetsGroup(); //TODO cache for members saving
						try {
							$ob->set_name((string)$node->name);
							$ob->set_description((string)$node->description);
							$ob->save();
						} catch (DataException $e) {
							if ($runtime) {
								error_msg('Failed to install stylesheets-group \''.(string)$node->name).'\' : '.$e->getMessage();
							}
							continue;
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install stylesheets-group \''.(string)$node->name).'\' : '.$t->getMessage();
							}
							continue;
						}
						$val = (string)$node->id;
						$cssgrps[(int)$val] = $ob->get_id();
					}
					break;
				case 'stylegroupmembers':
					$bank = [];
					foreach ($typenode->children() as $node) {
						$val = (int)$node->group_id;
						$val2 = (int)$node->css_id;
						if (isset($cssgrps[$val]) && isset($styles[$val2])) {
							$val3 = $cssgrps[$val];
							$bank[$val3][0][] = $styles[$val2];
							$bank[$val3][1][] = (int)$node->item_order;
						}
					}
					foreach ($bank as $gid => $arr) {
						array_multisort($arr[1], $arr[0]);
						try {
							$ob = StylesheetsGroup::load($gid); //or use cached object
							$ob->set_members($arr[0]);
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install stylesheets-group ('.$gid.') members : '.$t->getMessage());
							}
							continue;
						}
					}
					break;
				case 'templatetypes':
					if ($runtime) {
						verbose_msg(lang('install_templatetypes'));
					}
					$pattern = '/^([as]:\d+:|[Nn](ull)?;)/';
					foreach ($typenode->children() as $node) {
						$val = (string)$node->originator;
						if (!$val) {
							$val = $corename;
						} else {
							if (!($val == $corename || endswith($val, 'Theme'))) {
								continue; //modules' template-data installed by them
							}
						}
						$ob = new TemplateType();
						try {
							$ob->set_name((string)$node->name);
							$ob->set_originator($val);
							$val = (string)$node->description;
							if ($val !== '') {
								$ob->set_description($val);
							}
							$ob->set_owner(1);
							$val3 = (string)$node->dflt_content;
							if ($val3 !== '') {
								$ob->set_dflt_contents(htmlspecialchars_decode($val3, ENT_XML1 | ENT_QUOTES));
								$ob->set_dflt_flag(true);
							} else {
								$ob->set_dflt_flag(false);
							}
							$ob->set_oneonly_flag((bool)$node->one_only);
							$ob->set_content_block_flag((bool)$node->requires_contentblocks);
							$val = (string)$node->lang_cb;
							if ($val) {
								if (preg_match($pattern, $val)) {
									$val = unserialize($val, []);
								}
								$val = str_replace('\\\\', '\\', $val); // PHP doesn't recognize callable including double-backslash
								$ob->set_lang_callback($val);
							}
							$val = (string)$node->help_content_cb;
							if ($val) {
								if (preg_match($pattern, $val)) {
									$val = unserialize($val, []);
								}
								$val = str_replace('\\\\', '\\', $val);
								$ob->set_help_callback($val);
							}
							if ($val3 !== '') {
								$val = (string)$node->dflt_content_cb;
								if ($val) {
									if (preg_match($pattern, $val)) {
										$val = unserialize($val, []);
									}
									$val = str_replace('\\\\', '\\', $val);
									$ob->set_content_callback($val);
									$ob->reset_content_to_factory();
								}
							}
							$ob->save();
						} catch (DataException $e) {
							if ($runtime) {
								error_msg('Failed to install template-type \''.(string)$node->name.'\' : '.$e->getMessage());
							}
							continue;
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install template-type \''.(string)$node->name.'\' : '.$t->getMessage());
							}
							continue;
						}
						$val = (string)$node->id;
						$types[(int)$val] = $ob->get_id(); // map id's
					}
					break;
				case 'templates':
					if ($runtime) {
						verbose_msg(lang('install_templates'));
					}
					foreach ($typenode->children() as $node) {
						$val = (int)$node->type_id;
						if ($val && !isset($types[$val])) {
							continue;
						}
						$val2 = (string)$node->originator;
						// process if anonymous, core- or theme-sourced (modules' template-data installed by them)
						if ($val2) {
							if (!($val2 == $corename || endswith($val2, 'Theme'))) {
								continue;
							}
						}
						$ob = new Template();
						try {
							if ($val2) {
								$ob->set_originator($val2);
							}
							$ob->set_name((string)$node->name);
							$ob->set_description((string)$node->description);
							$ob->set_owner(1);
//							$val = (string)$node->group_id; //name or id DEPRECATED & maybe wrong GROUP ID
//							if ($val !== '') { $ob->set_group($val); }
//							$val = (string)$node->category;
//?							$ob->set_category(($val) ? (int)$val : 1)); // TODO
// hierarchy NOT USED ATM
							$ob->set_type($types[$val]);
							$val = (string)$node->type_dflt;
							$ob->set_type_default((bool)$val);
// TODO						$val = (string)$node->groups;
// TODO						$ob->set_groups(($val) ? $val : null));
//							$val = (string)$node->additional_editors;
// no add'l eds @ new site	$ob->set_additional_editors(($val) ? $val : null));
							$val = (string)$node->listable;
							$ob->set_listable((bool)$val);
							$val = (string)$node->contentfile;
							$ob->set_content_file((bool)$val);
							$ob->set_content(htmlspecialchars_decode((string)$node->content, ENT_XML1 | ENT_QUOTES));
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install template \''.(string)$node->name.'\' : '.$t->getMessage());
							}
							continue;
						}
						$val = (string)$node->id;
						$templates[(int)$val] = $ob->get_id();
					}
					break;
				case 'templategroups':
					if ($runtime) {
						verbose_msg(lang('install_groups'));
					}
					foreach ($typenode->children() as $node) {
						$ob = new TemplatesGroup(); //TODO cache for members saving
						try {
							$ob->set_name((string)$node->name);
							$ob->set_description((string)$node->description);
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to install template-group \''.(string)$node->name.'\' : '.$t->getMessage());
							}
							continue;
						}
						$val = (string)$node->id;
						$tplgrps[(int)$val] = $ob->get_id();
					}
					break;
				case 'templategroupmembers':
					$bank = [];
					foreach ($typenode->children() as $node) {
						$val = (int)$node->group_id;
						$val2 = (int)$node->tpl_id;
						if (isset($tplgrps[$val]) && isset($templates[$val2])) {
							$val3 = $tplgrps[$val];
							$bank[$val3][0][] = $templates[$val2];
							$bank[$val3][1][] = (int)$node->item_order;
						}
					}
					foreach ($bank as $gid => $arr) {
						array_multisort($arr[1], $arr[0]);
						try {
							$ob = TemplatesGroup::load($gid); //or use cached object
							$ob->set_members($arr[0]);
							$ob->save();
						} catch (Throwable $t) {
							if ($runtime) {
								error_msg('Failed to add member(s) to templates-group ('.$gid.') : '.$t->getMessage());
							}
							continue;
						}
					}
					break;
				case 'pages':
					if ($runtime) {
						verbose_msg(lang('install_contentpages'));
					}
					$bank = [];
					$eo = -250;
					foreach ($typenode->children() as $node) {
						//replicate table-row somewhat
						$parms = (array)$node;
						$val = (int)$parms['template_id'] ?? 0;
						$parms['template_id'] = $templates[$val] ?? 0;
						$val = $parms['parent_id'] ?? -1;
						if ($val < 1) {
							$parms['parent_id'] = -1;
						}
						$val = $parms['item_order'] ?? -1;
						if ($val < 1) {
							$parms['item_order'] = ++$eo;
						}
						$val = $parms['menu_text'] ?? '';
						if ($val) {
							$parms['menu_text'] = htmlspecialchars_decode($val, ENT_XML1 | ENT_QUOTES);
						}
						$val = $parms['styles'];
						if ($val) {
							$oid = explode(',', $val);
							$nid = [];
							foreach ($oid as $id) {
								if (isset($styles[$id])) {
									$nid[] = $styles[$id];
								}
							}
							$parms['styles'] = implode(',', $nid);
						}
						$bank[] = $parms;
					}
					$col1 = array_column($bank, 'parent_id');
					$col2 = array_column($bank, 'item_order');
					array_multisort($col1, SORT_NUMERIC, $col2, SORT_NUMERIC, $bank);

					$orders = [];
					foreach ($bank as &$row) {
						$pid = (int)$row['parent_id'];
						if (!isset($orders[$pid])) {
							$orders[$pid] = 0;
						}
						$row['item_order'] = ++$orders[$pid];
						$val = (int)$row['content_id'];
						unset($row['content_id']);
						$pages[$val]['fields'] = $row;
					}
					break;
				case 'properties': //must be processed after pages
					foreach ($typenode->children() as $node) {
						$val = (string)$node->content_id;
						if (!$val) {
							continue;
						}
						$val2 = (string)$node->content;
						if ($val2 === '') {
							continue;
						}
						$val = (int)$val;
						if (empty($pages[$val])) {
							$pages[$val] = [];
						}
						if (empty($pages[$val]['props'])) {
							$pages[$val]['props'] = [];
						}
						$pages[$val]['props'][(string)$node->prop_name] = htmlspecialchars_decode($val2, ENT_XML1 | ENT_QUOTES);
					}
					break;
				case 'uploadfiles':
					$config = SingleItem::Config();
					$tobase = $config['uploads_path'];
					if ($tobase) {
						$tobase .= DIRECTORY_SEPARATOR;
					} else {
						break;
					}
					if ($uploadspath) {
						//TODO validity check e.g. somewhere absolute in installer tree
						$frombase = $uploadspath.DIRECTORY_SEPARATOR;
					} else {
						$frombase = '';
					}

					foreach ($typenode->children() as $node) {
						$val = (string)$node->relto;
						if ($val) {
							$to = $tobase . strtr($val, '/', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
						} else {
							$to = $tobase;
						}
						$name = (string)$node->name;
						$val = (string)$node->embedded;
						if ($val) {
							@file_put_contents($to.$name, base64_decode((string)$node->content));
						} else {
							$from = (string)$node->relfrom;
							if ($from) {
								if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $from)) { //not absolute
									if ($frombase) {
										$from = $frombase.$from;
									} else {
										$from = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$from;
									}
								} else {
									//TODO validity check e.g. somewhere absolute in installer tree
								}
								$from .= DIRECTORY_SEPARATOR;
							} elseif ($frombase) {
								$from = $frombase;
							} else {
								continue;
							}
							$dir = dirname($to.$name); //$to sans trailing separator
							if (!is_dir($dir)) {
								@mkdir($dir, $dirmode, true);
								@touch($to.DIRECTORY_SEPARATOR.'index.html');
								@chmod($to.DIRECTORY_SEPARATOR.'index.html', $filemode);
							}
							// intentional fail if path(s) bad
							@copy($from.$name, $to.$name);
							@chmod($to.$name, $filemode);
						}
					}
					$iter = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($config['uploads_path'],
						  FilesystemIterator::CURRENT_AS_PATHNAME |
						  FilesystemIterator::SKIP_DOTS),
						RecursiveIteratorIterator::SELF_FIRST);
					foreach ($iter as $to) {
						if (is_dir($to)) {
							@touch($to.DIRECTORY_SEPARATOR.'index.html');
							@chmod($to.DIRECTORY_SEPARATOR.'index.html', $filemode);
						}
					}
					break;
				case 'usertags':
					if ($runtime) {
						verbose_msg(lang('install_usertags'));
					}
					$db = SingleItem::Db();
					$query = 'INSERT INTO '.CMS_DB_PREFIX.'userplugins (
name,
description,
parameters,
contentfile,
code) VALUES (?,?,?,?,?)';
					foreach ($typenode->children() as $node) {
						$parms = (array)$node;
						$args = [$parms['name']];
						$val = $parms['description'] ?? null;
						if ($val) {
							$args[] = htmlspecialchars_decode($val, ENT_XML1 | ENT_QUOTES);
						} else {
							$args[] = null;
						}
						$val = $parms['parameters'] ?? null;
						if ($val) {
							$args[] = htmlspecialchars_decode($val, ENT_XML1 | ENT_QUOTES);
						} else {
							$args[] = null;
						}
						// if the plugin is file-stored, that file is expected to be processed separately
						$val = $parms['contentfile'] ?? 0;
						if ($val) {
							$args[] = (int)htmlspecialchars_decode($val, ENT_XML1 | ENT_QUOTES);
						} else {
							$args[] = 0;
						}
						$val = $parms['code'] ?? null;
						if ($val) {
							$args[] = htmlspecialchars_decode($val, ENT_XML1 | ENT_QUOTES);
						} else {
							$args[] = null;
						}

						if (!$db->execute($query, $args)) {
							if ($runtime) {
								error_msg('Failed to install user-plugin \''.$parms['name'].'\' : '.$db->errorMsg());
							}
							return false;
						}
					}
					break;
				case 'usertagfiles': //UDTfiles
					$tobase = CMS_FILETAGS_PATH.DIRECTORY_SEPARATOR;
/*					// __DIR__ might be phar://abspath/to/pharfile/relpath/to/thisfolder
					if (isset($typenode['installrel'])) {
						$rel = (string)$typenode->installrel;
						if ($rel) {
							$frombase = dirname(__DIR__).DIRECTORY_SEPARATOR.strtr($rel, '\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
						} else {
							$frombase = ''; //TODO abort
						}
					} else
*/
					if ($workerspath) {
						//TODO validity check e.g. somewhere absolute in installer tree
						$frombase = $workerspath.DIRECTORY_SEPARATOR;
					} else {
						$frombase = '';
					}

					foreach ($typenode->children() as $node) {
						$val = (string)$node->relto;
						if ($val) {
							$to = $tobase . strtr($val, '/', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
						} else {
							$to = $tobase;
						}
						$name = (string)$node->name;
						$val = (string)$node->embedded;
						if ($val) {
							@file_put_contents($to.$name, htmlspecialchars_decode((string)$node->content, ENT_XML1 | ENT_QUOTES));
							@chmod($to.$name, $filemode);
						} else {
							$from = (string)$node->relfrom;
							if ($from) {
								if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $from)) { //not absolute
									if ($frombase) {
										$from = $frombase.$from;
									} else {
										$from = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$from;
									}
								} else {
									//TODO validity check e.g. somewhere absolute in installer tree
								}
								$from .= DIRECTORY_SEPARATOR;
							} elseif ($frombase) {
								$from = $frombase;
							} else {
								continue;
							}
							@copy($from.$name, $to.$name);
							@chmod($to.$name, $filemode);
							@touch($to.'index.html');
						}
					}
					break;
				case 'templatefiles':
					$tobase = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
					if ($workerspath) {
						//TODO validity check e.g. somewhere absolute in installer tree
						$frombase = $workerspath.DIRECTORY_SEPARATOR;
					} else {
						$frombase = '';
					}

					foreach ($typenode->children() as $node) {
						$val = (string)$node->relto;
						if ($val) {
							$to = $tobase . strtr($val, '/', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
						} else {
							$to = $tobase;
						}
						$name = (string)$node->name;
						$val = (string)$node->embedded;
						if ($val) {
							@file_put_contents($to.$name, htmlspecialchars_decode((string)$node->content, ENT_XML1 | ENT_QUOTES));
							@chmod($to.$name, $filemode);
						} else {
							$from = (string)$node->relfrom;
							if ($from) {
								if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $from)) { //not absolute
									if ($frombase) {
										$from = $frombase.$from;
									} else {
										$from = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$from;
									}
								} else {
									//TODO validity check e.g. somewhere absolute in installer tree
								}
								$from .= DIRECTORY_SEPARATOR;
							} elseif ($frombase) {
								$from = $frombase;
							} else {
								continue;
							}
							@copy($from.$name, $to.$name);
							@chmod($to.$name, $filemode);
							@touch($to.'index.html');
						}
					}
					break;
				case 'stylefiles':
					$tobase = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR;
					if ($workerspath) {
						//TODO validity check e.g. somewhere absolute in installer tree
						$frombase = $workerspath.DIRECTORY_SEPARATOR;
					} else {
						$frombase = '';
					}

					foreach ($typenode->children() as $node) {
						$val = (string)$node->relto;
						if ($val) {
							$to = $tobase . strtr($val, '/', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
						} else {
							$to = $tobase;
						}
						$name = (string)$node->name;
						$val = (string)$node->embedded;
						if ($val) {
							@file_put_contents($to.$name, htmlspecialchars_decode((string)$node->content, ENT_XML1 | ENT_QUOTES));
							@chmod($to.$name, $filemode);
						} else {
							$from = (string)$node->relfrom;
							if ($from) {
								if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $from)) { //not absolute
									if ($frombase) {
										$from = $frombase.$from;
									} else {
										$from = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$from;
									}
								} else {
									//TODO validity check e.g. somewhere absolute in installer tree
								}
								$from .= DIRECTORY_SEPARATOR;
							} elseif ($frombase) {
								$from = $frombase;
							} else {
								continue;
							}
							@copy($from.$name, $to.$name);
							@chmod($to.$name, $filemode);
							@touch($to.'index.html');
						}
					}
					break;
				case 'themefiles':
					$tobase = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'themes';
					if ($workerspath) {
						//TODO validity check e.g. somewhere absolute in installer tree
						$frombase = $workerspath.DIRECTORY_SEPARATOR;
					} else {
						$frombase = '';
					}

					foreach ($typenode->children() as $node) {
						$val = (string)$node->relto;
						if ($val) {
							$to = $tobase.DIRECTORY_SEPARATOR.strtr($val, '/', DIRECTORY_SEPARATOR);
						} else {
							$to = $tobase;
						}
						if (!is_dir($to)) {
							@mkdir($to, $dirmode);
						}
						$to .= DIRECTORY_SEPARATOR;
						$name = (string)$node->name;
						$val = (string)$node->embedded;
						if ($val) {
							@file_put_contents($to.$name, htmlspecialchars_decode((string)$node->content, ENT_XML1 | ENT_QUOTES));
							@chmod($to.$name, $filemode);
						} else {
							$from = (string)$node->relfrom;
							if ($from) {
								if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $from)) { //not absolute
									if ($frombase) {
										$from = $frombase.$from;
									} else {
										$from = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$from;
									}
								} else {
									//TODO validity check e.g. somewhere absolute in installer tree
								}
								$from .= DIRECTORY_SEPARATOR;
							} elseif ($frombase) {
								$from = $frombase;
							} else {
								continue;
							}
							@copy($from.$name, $to.$name);
							@chmod($to.$name, $filemode);
							@touch($to.'index.html');
						}
					}
					break;
			} // node-name switch
		} // count > 0
	} // xml children

	if ($pages) {
		$map = [-1 => -1]; // maps proffered id's to installed id's
		$db = SingleItem::Db();
		foreach ($pages as $val => $arr) {
	//TODO revert to using ContentManager\contenttypes\whatever class
			$map[$val] = SavePage($arr, $map, $db);
		}
		if (!$runtime) {
			ContentOperations::get_instance()->SetAllHierarchyPositions();
		}
	}

	return '';
}

/**
 * Save page content direct to database. We do this here cuz during
 * site installation, there may not yet be a PageEditor-compatible
 * class to use for saving content.
 *
 * @param array $parms 2 members: 'fields' and 'props', each an assoc.
 * array suitable for stuffing into database tables
 * @param array $pagemap Map from proffered pageid to installed-page id
 * @param Connection $db Database connection
 * @return mixed int content-id | false upon error
 */
function SavePage(array $parms, array $pagemap, $db)
{
	global $runtime;

	extract($parms['fields']);

	$p = $parent_id ?? -1;
	if ($p > 0) {
		if (!empty($pagemap[$p])) {
			$p = $parent_id = $pagemap[$p];
		} else {
			//TODO handle probably-wrong parent-page id
			$p = $parent_id = -1;
		}
	} else {
		$parent_id = $p; //in case was not set
	}

	$o = $item_order ?? 0;
	if ($o < 1) {
		$query = 'SELECT MAX(item_order) AS new_order FROM '.CMS_DB_PREFIX.'content WHERE parent_id = ?';
		$o = (int)$db->getOne($query, [$p]);
		$item_order = ($o < 1) ? 1 : $o + 1;
	}
	// TODO handle $template_id < 1

	$content_id = $db->genID(CMS_DB_PREFIX.'content_seq'); //as late as possible (less racy)

	// pages' hierarchy-related properties are set upstream
	$query = 'INSERT INTO '.CMS_DB_PREFIX.'content (
content_id,
content_name,
type,
default_content,
show_in_menu,
active,
cachable,
secure,
owner_id,
parent_id,
template_id,
item_order,
menu_text,
content_alias,
metadata,
titleattribute,
page_url,
tabindex,
accesskey,
styles,
last_modified_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)';
	$args = [
		$content_id,
		(!empty($content_name) ? $content_name : null),
		$type ?? 'content',
		$default_content ?? 0,
		$show_in_menu ?? 1,
		$active ?? 1,
		$cachable ?? 1,
		$secure ?? 0,
		$owner_id ?? 1,
		$parent_id,
		(!empty($template_id) ? $template_id : null),
		$item_order,
		(!empty($menu_text) ? $menu_text : null),
		(!empty($content_alias) ? $content_alias : null),
		(!empty($metadata) ? $metadata : null),
		(!empty($titleattribute) ? $titleattribute : null),
		(!empty($page_url) ? $page_url : null),
		$tabindex ?? 0,
		(!empty($accesskey) ? $accesskey : null),
		(!empty($styles) ? $styles : null),
	];

	if (!$db->execute($query, $args)) {
		if (!empty($runtime)) {
			error_msg('Failed to install page \''.$content_name.'\' ('.$content_id.') : '.$db->errorMsg());
		}
		return false;
	}

	if (!empty($parms['props'])) {
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'content_props (
content_id,
type,
prop_name,
content) VALUES (?,?,?,?)';
		foreach ($parms['props'] as $name => $val) {
			if (is_numeric($val) || is_bool($val)) {
				$val = (int)$val;
				$ptype = 'int';
			} else {
				if (!is_null($val)) {
					$val = (string)$val;
				}
				$ptype = 'string';
			}
			if (!$db->execute($query, [$content_id, $ptype, $name, $val])) {
				if (!empty($runtime)) {
					error_msg('Failed to install property \''.$name.'\' for page ('.$content_id.') : '.$db->errorMsg());
				}
			}
		}
	}

	if (!empty($page_url)) {
		$route = Route::new_builder($page_url, '__CONTENT__', $content_id, '', true);
		RouteOperations::add_static($route);
	}
	return $content_id;
}

return __NAMESPACE__;  //tell the includer where we are
