#!/usr/bin/env php
<?php
/*
Script to generate site content from xml data.
Can be run independently, or included in a site-setup script.
*/

$runtime = empty($CMS_INSTALL_PAGE);

if ($runtime) {
	require_once dirname(__FILE__, 4).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
}

if (empty($xmlfile)) {
	//source file not named by includer-script
	$xmlfile = './democontent.xml';
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
if ($xml === false) {
	if ($runtime) {
		echo 'Failed to load file '.$xmlfile."\n"; //TODO proper feedback
	} else {
		verbose_msg(ilang('error_filebad',$xmlfile));
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
				if (!$runtime) {
					verbose_msg(ilang('install_default_designs'));
				}
				foreach ($typenode->children() as $node) {
					$ob = new CmsLayoutCollection();
					try {
						$ob->set_name((string)$node->name);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description((string)$node->description);
					$ob->set_default((string)$node->dflt != false);
//					$ob->save();
					$designs[(string)$node->id] = $ob->get_id();
				}
				break;
			case 'stylesheets':
				if (!$runtime) {
					verbose_msg(ilang('install_stylesheets'));
				}
				foreach ($typenode->children() as $node) {
					$ob = new CmsLayoutStylesheet;
					try {
						$ob->set_name((string)$node->name);
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_description((string)$node->description);
					try {
						$ob->set_content(htmlspecialchars_decode((string)$node->content));
					} catch (\Exception $e) {
						continue;
					}
					$ob->set_media_types((string)$node->media_type);
//					$ob->save();
					$styles[(string)$node->id] = $ob->get_id();
				}
				break;
			case 'designstyles': //relations between styles and designs
				$bank = [];
				$eid = -99;
				foreach ($typenode->children() as $node) {
					$val = $styles[(string)$node->css_id] ?? --$eid;
					$bank[$val][0][] = $designs[(string)$node->design_id] ?? --$eid;
					$bank[$val][1][] = (string)$node->item_order + 0;
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
				if (!$runtime) {
					verbose_msg(ilang('install_templatetypes'));
				}
				foreach ($typenode->children() as $node) {
					$ob = new CmsLayoutTemplateType();
					try {
						$ob->set_name((string)$node->name);
					} catch (\Exception $e) {
						continue;
					}
					$val = (string)$node->description;
					if ($val !== '') $ob->set_description($val);
					$ob->set_owner(1);
					$val = (string)$node->originator; if (!$val) $val = CmsLayoutTemplateType::CORE;
					$ob->set_originator($val);
					$ob->set_dflt_flag((string)$node->has_dflt != false);
					$val = (string)$node->dflt_contents; if ($val) $val = htmlspecialchars_decode($val);
					$ob-set_dflt_contents($val);
					$ob->set_oneonly_flag((string)$node->one_only != false);
					$ob->set_content_block_flag((string)$node->requires_contentblocks != false);
					$ob->set_lang_callback((string)$node->lang_cb);
					$ob->set_content_callback((string)$node->dflt_content_cb);
					$ob->set_help_callback((string)$node->help_content_cb);
					$ob->reset_content_to_factory();
					$ob->set_content_block_flag((string)$node->requires_contentblocks != false);
//					$ob->save();
					$types[(string)$node->id] = $ob->get_id();
				}
				break;
			case 'categories':
				if (!$runtime) {
					verbose_msg(ilang('install_categories'));
				}
				foreach ($typenode->children() as $node) {
					$ob = new CmsLayoutTemplateCategory();
					try {
						$ob->set_name((string)$node->name);
					} catch (\Exception $e) {
						continue;
					}
					$val = (string)$node->description;
					if ($val !== '') $ob->set_description($val);
					$ob->set_item_order((string)$node->item_order + 0);
//					$ob->save();
					$categories[(string)$node->id] = $ob->get_id();
				}
				break;
			case 'templates':
				if (!$runtime) {
					verbose_msg(ilang('install_templates'));
				}
				$eid = -199;
				foreach ($typenode->children() as $node) {
					$ob = new CmsLayoutTemplate();
					try {
						$ob->set_name((string)$node->name);
					} catch (\Exception $e) {
						continue;
					}
					$val = (string)$node->description;
					if ($val !== '') $ob->set_description($val);
					$ob->set_owner(1);
					$ob->set_type($types[(string)$node->type_id] ?? --$eid);
					$val = (string)$node->category_id;
					if ($val !== '') $ob->set_category($val); //name or id
					$ob->set_type_dflt((string)$node->type_dflt != false);
					$ob->set_content(htmlspecialchars_decode((string)$node->content));
//					$ob->save();
					$templates[(string)$node->id] = $ob->get_id();
				}
				break;
			case 'designtemplates': //relations between templates and designs
				$bank = [];
				$eid = -299;
				foreach ($typenode->children() as $node) {
					$val = $templates[(string)$node->tpl_id] ?? --$eid;
					$bank[$val][0][] = $designs[(string)$node->design_id] ?? --$eid;
					$bank[$val][1][] = (string)$node->tpl_order + 0;
				}
				foreach ($bank as $tid=>$arr) {
					try {
						$ob = CmsLayoutTemplate::load($tid);
					} catch (\Exception $e) {
						continue;
					}
					array_multisort($arr[1], $arr[0]);
					$ob->set_designs($arr[0]);
//					$ob->save();
				}
				break;
			case 'categorytemplates': //relations between templates and categories
				$bank = [];
				$eid = -99;
				foreach ($typenode->children() as $node) {
					$val = $templates[(string)$node->tpl_id] ?? --$eid;
					$bank[$val][0][] = $categories[(string)$node->category_id] ?? --$eid;
					$bank[$val][1][] = (string)$node->tpl_order + 0;
				}
				foreach ($bank as $tid=>$arr) {
					try {
						$ob = CmsLayoutTemplate::load($tid);
					} catch (\Exception $e) {
						continue;
					}
					array_multisort($arr[1], $arr[0]);
					$ob->set_categories($arr[0]);
//					$ob->save();
				}
				break;
			case 'pages':
				if (!$runtime) {
					verbose_msg(ilang('install_contentpages'));
				}
				$eid = -99;
				foreach ($typenode->children() as $node) {
					$pagetype = '\\CMSMS\\contenttypes\\'.ucfirst((string)$node->type); //CHECKME case
					$ob = new $pagetype();
					$ob->SetName((string)$node->content_name);
					$val = (string)$node->content_alias;
					if ($val !== '') $ob->SetAlias($val);
					$ob->SetTemplateId($templates[(string)$node->template_id] ?? --$eid);
					$ob->SetDefaultContent((string)$node->default_content != false);
					$ob->SetOwner(1);
					$val = $pages[(string)$node->parent_id] ?? --$eid;
					$ob->SetParentId($val); //TODO update later if $eid
					$ob->SetActive((string)$node->active != false);
					$ob->SetShowInMenu((string)$node->show_in_menu != false);
					$val = (string)$node->menu_text; if ($val) $val = htmlspecialchars_decode($val);
					$ob->SetMenuText($val);
					$ob->SetCachable((string)$node->cachable != false);
//					$ob->Save();
					$val = (string)$node->content_id;
					$pages[$val] = $ob->Id();
					$pageobs[$val] = $ob;
				}
				break;
			case 'properties': //must be processed after pages
				foreach ($typenode->children() as $node) {
					$ob = $pageobs[(string)$node->content_id] ?? null;
					if ($ob) {
						$ob->SetPropertyValue((string)$node->prop_name, htmlspecialchars_decode((string)$node->content));
					}
				}
				foreach ($pageobs as $ob) {
//					$ob->Save();
				}
				break;
		}
	}
}
