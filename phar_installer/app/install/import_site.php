#!/usr/bin/env php
<?php
/*
Script to generate site content from xml data.
Can be run independently, or included in a site-setup script.
*/

global $CMS_INSTALL_PAGE;
$cli = empty($CMS_INSTALL_PAGE);

if ($cli) {
	require_once dirname(__FILE__, 4).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
}

if (empty($xmlfile)) {
	//source file not named by includer-script
	$xmlfile = 'democontent.xml';
}
$infile = __DIR__.DIRECTORY_SEPARATOR.$xmlfile;

libxml_use_internal_errors(true);
$xml = simplexml_load_file($infile);
if ($xml === false) {
	if ($cli) {
		echo 'Failed to load file '.$infile."\n";
	} else {
		verbose_msg(ilang('error_filebad',$infile));
	}
	foreach (libxml_get_errors() as $error) {
		echo 'Line '.$error->line.': '.$error->message."\n";
	}
	libxml_clear_errors();
	exit;
}

$designs = [];
$types = [];
$categories = [];
$templates = [];
$styles = [];
$pages = [-1 => -1];
$pageobs = [];

foreach ($xml->children() as $typenode) {
	if ($typenode->count() > 0) {


		switch ($typenode->getName()) {
			case 'designs':
/*				if (!$cli) {
					verbose_msg(ilang('install_default_designs'));
				}
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$ob = new CmsLayoutCollection();
					try {
						$ob->set_name($row['name']);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description($row['description'] ?? null);
					$ob->set_default($row['dflt'] ?? false);
//					$ob->save();
					$designs[row['id']] = $ob->get_id();
				}
*/
				break;
			case 'stylesheets':
/*				if (!$cli) {
					verbose_msg(ilang('install_stylesheets'));
				}
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$ob = new CmsLayoutStylesheet;
					try {
						$ob->set_name($row['name']);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description($row['description'] ?? null);
					try {
						$ob->set_content(htmlspecialchars_decode($row['content']));
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_media_types($row['media_type']);
//					$ob->save();
					$styles[$row['id']] = $val = $ob->get_id();
				}
*/
				break;
			case 'designstyles':
				$bank = [];
				$eid = -99;
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$sid = $styles[$row['css_id']] ?? --$eid;
					$bank[$sid][0][] = $designs[$row['design_id']] ?? --$eid;
					$bank[$sid][1][] = $row['item_order'];
				}
				foreach ($bank as $sid=>$arr) {
					try {
						$ob = CmsLayoutStylesheet::load($sid);
					} catch (Exception $e) {
						continue;
					}
					array_multisort($arr[1], $arr[0]);
					$ob->set_designs($arr[0]);
//					$ob->save();
				}
				break;
			case 'tpltypes':
				if (!$cli) {
					verbose_msg(ilang('install_templatetypes'));
				}
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$ob = new CmsLayoutTemplateType();
					try {
						$ob->set_name($row['name']);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description($row['description'] ?? null);
					$ob->set_owner(1);
					$val = !empty($row['originator']) ? $row['originator'] : CmsLayoutTemplateType::CORE;
					$ob->set_originator($val);
					$ob->set_dflt_flag($row['has_dflt'] ?? false);
					$val = !empty($row['dflt_contents']) ? htmlspecialchars_decode($row['dflt_contents']) : null;
					$ob-set_dflt_contents($val);
					$ob->set_oneonly_flag($row['one_only'] ?? false);
					$ob->set_content_block_flag($row['requires_contentblocks'] ?? false);
					$ob->set_lang_callback($row['lang_cb'] ?? null);
					$ob->set_content_callback($row['dflt_content_cb'] ?? null);
					$ob->set_help_callback($row['help_content_cb'] ?? null);
					$ob->reset_content_to_factory();
					$ob->set_content_block_flag($row['requires_contentblocks'] ?? false);
//					$ob->save();
					$types[$row['id']] = $ob->get_id();
				}
				break;
			case 'categories':
				if (!$cli) {
					verbose_msg(ilang('install_categories'));
				}
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$ob = new CmsLayoutTemplateCategory();
					try {
						$ob->set_name($row['name']);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description($row['description'] ?? null);
					$ob->set_item_order($row['item_order'] ?? null);
//					$ob->save();
					$categories[$row['id']] = $ob->get_id();
				}
				break;
			case 'templates':
				if (!$cli) {
					verbose_msg(ilang('install_templates'));
				}
				$eid = -199;
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$ob = new CmsLayoutTemplate();
					try {
						$ob->set_name($row['name']);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description($row['description'] ?? null);
					$ob->set_owner(1);
					$ob->set_type($types[$row['type_id']] ?? --$eid);
					if (isset($row['category_id'])) $ob->set_category($row['category_id']); //name or id
					$ob->set_type_dflt($row['type_dflt'] ?? false);
					$ob->set_content(htmlspecialchars_decode($row['content']));
//					$ob->save();
					$templates[$row['id']] = $ob->get_id();
				}
				break;
			case 'designtemplates':
				$bank = [];
				$eid = -299;
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$tid = $templates[$row['tpl_id']] ?? --$eid;
					$bank[$tid][] = $designs[$row['design_id']] ?? --$eid;
//TODO				$bank[$tid][1][] = $row['tpl_order'];
				}
				foreach ($bank as $tid=>$arr) {
					try {
						$ob = CmsLayoutTemplate::load($tid);
					} catch (Exception $e) {
						continue;
					}
//					array_multisort($arr[1], $arr[0]);
					$ob->set_designs($arr);
//					$ob->save();
				}
				break;
			case 'categorytemplates':
				$bank = [];
				$eid = -99;
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$tid = $templates[$row['tpl_id']] ?? --$eid;
					$bank[$tid][0][] = $categories[$row['category_id']] ?? --$eid;
					$bank[$tid][1][] = $row['tpl_order'];
				}
				foreach ($bank as $tid=>$arr) {
					try {
						$ob = CmsLayoutTemplate::load($tid);
					} catch (Exception $e) {
						continue;
					}
					array_multisort($arr[1], $arr[0]);
					$ob->set_categories($arr[0]);
//					$ob->save();
				}
				break;
			case 'pages':
				if (!$cli) {
					verbose_msg(ilang('install_contentpages'));
				}
				$eid = -99;
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$pagetype = '\\CMSMS\\contenttypes\\'.ucfirst($row['type']); //CHECKME case
					$ob = new $pagetype();
					$ob->SetName($row['content_name']);
					$ob->SetAlias($row['content_alias'] ?? null);
					$ob->SetTemplateId($templates[$row['template_id']] ?? --$eid);
					$ob->SetDefaultContent($row['default_content'] ?? false);
					$ob->SetOwner(1);
					$val = $pages[$row['parent_id']] ?? --$eid;
					$ob->SetParentId($val); //TODO update later if $eid
					$ob->SetActive($row['active'] ?? false);
					$ob->SetShowInMenu($row['show_in_menu'] ?? false);
					$val = !empty($row['menu_text']) ? htmlspecialchars_decode($row['menu_text']) : null;
					$ob->SetMenuText($val);
					$ob->SetCachable($row['cachable'] ?? false);
//					$ob->Save();
					$val = $row['content_id'];
					$pages[$val] = $ob->Id();
					$pageobs[$val] = $ob;
				}
				break;
			case 'properties': //must be processed after pages
				foreach ($typenode->children() as $node) {
					$row = (array)$node;
					$ob = $pageobs[$row['content_id']] ?? null;
					if ($ob) {
						$ob->SetPropertyValue($row['prop_name'], htmlspecialchars_decode($row['content']));
					}
				}
				foreach ($pageobs as $ob) {
//					$ob->Save();
				}
				break;
		}
	}
}
