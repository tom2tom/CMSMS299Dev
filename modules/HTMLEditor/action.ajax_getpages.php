<?php
/*
HTMLEditor module action: get content to populate a site-page selector, via ajax
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use function CMSMS\specialize;

//if (some worthy test fails) exit;
//if (!check_login()) exit; // admin only.... but any admin

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

$hm = SingleItem::App()->GetHierarchyManager();

if (!empty($params['page'])) {
	$page = $params['page'];
} else {
	$page = false;
}

if (isset($params['infoonly']) && cms_to_bool($params['infoonly'])) {
	$title = '';
	$alias = '';
	if ($page) {
		$title = 'Page Not Available'; // TODO translated
		if (is_numeric($page)) {
			$node = $hm->find_by_tag('id', (int)$page);
		} else {
			$node = $hm->find_by_tag('alias', trim($page)); // TODO cleanValue($page, CMS_SAN...)
		}
		if ($node) {
			$content = $node->getContent(false);
			if (is_object($content)) {
				if ($content->Active()) {
					$type = strtolower($content->Type());
					if (!($type == 'sectionheader' || $type == 'separator')) { // some item-types can be ignored
						$alias = $content->Alias();
						$title = specialize($content->Name()); //TODO shorten/ellipsize?
					}
				}
			}
		}
	}

	echo json_encode([
		'title' => $title,
		'alias' => $alias
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	exit;
}

$rootnodes = $hm->get_children();

if (!$rootnodes) { // nothing to do
	echo json_encode([
		'body' => '',
		'title' => 'No Pages', // TODO translated
		'alias' => ''
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	exit;
}

$title = 'SelPage ID'; // TODO translated
$alias = 'selpagealias';

/**
 * Populate data to be used for generating a page-selector menu-item
 *
 * @param object $node
 * @param int  $depth current recursion level, 0-based
 * @return array
 */
$fill_node = function($node, int $depth = 0) use(&$fill_node, &$page, &$title, &$alias) : array
{
	if (!is_object($node)) { return []; }
	//TODO use already-cached content tree data
	$content = $node->getContent(false);
	if (is_object($content)) {
		if (!$content->Active()) { return []; }
		$type = strtolower($content->Type());
		if ($type == 'sectionheader' || $type == 'separator') { return []; } // some item-types can be ignored

		$pid = $content->Id();
		$pal = $content->Alias();
		$nm = specialize($content->Name()); //TODO shorten/ellipsize?
		if ($page) {
			if ($page == $pid || $page == $pal) {
				$page = $pid; // override alias
				$title = $nm;
				$alias = $pal;
			}
		}

		$data = [
			'id' => $pid,
			'name' => $nm,
			'menutext' => specialize($content->MenuText()), //ditto
			'title' => specialize($content->TitleAttribute()),
			'alias' => $pal,
//			'depth' => $depth + 1,
//			'type' => $type,
			'children' => [],
		];

		if ($node->has_children()) {
			$children = $node->getChildren(false, true); //loads children into cache : SLOW! TODO just get id's
			// recurse [further]?
			if ($children && is_array($children)) {
				$child_nodes = [];
				foreach ($children as $cnode) {
					$tmp = $fill_node($cnode, $depth+1); //recurse
					if (is_array($tmp)) {
						$child_nodes[] = $tmp;
					}
				}
				unset($cnode);
				if ($child_nodes) {
					$data['children'] = $child_nodes;
				}
			}
		}
		return $data;
	}
	return [];
};

$tree = [];
foreach ($rootnodes as $node) {
	$tmp = $fill_node($node);
	if ($tmp) {
		$tree[] = $tmp;
	}
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('pagesmenu.tpl')); //,null,null,$smarty);
$tpl->assign('nodes', $tree)
  ->assign('current', $page);
$body = $tpl->fetch();
/*
try {
	$ADBG = $tpl->fetch();
	return $ADBG;
} catch (Throwable $t) {
	$here = 1;
}
*/
//TODO extra flags if defined: JSON_UNESCAPED_LINE_TERMINATORS | JSON_INVALID_UTF8_IGNORE
echo json_encode([
	'body' => $body,
	'title' => $title,
	'alias' => $alias
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
