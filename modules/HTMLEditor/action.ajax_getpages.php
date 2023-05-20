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

//use CMSMS\Lone;
use function CMSMS\specialize;

//if (some worthy test fails) exit;
//if (!check_login()) exit; // admin only.... but any admin

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

$ptops = $gCms->GetHierarchyManager();

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
			$node = $ptops->get_node_by_id((int)$page);
		} else {
			$node = $ptops->find_by_tag('alias', trim($page)); // TODO cleanValue($page, CMSSAN_TODO)
		}
		if ($node) {
			$content = $node->get_content(false);
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

$rootnodes = $ptops->get_children();

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
$fill_node = function($node, int $depth = 0) use (&$fill_node, &$page, &$title, &$alias) : array
{
	if (!is_object($node)) { return []; }
	$content = $node->get_content(false);
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
			$children = $node->load_children(false, true); //TODO ok to include inactive/disabled?
			if ($children) {
				$child_nodes = [];
				foreach ($children as $child) {
					$tmp = $fill_node($child, $depth+1); //recurse
					if (is_array($tmp)) {
						$child_nodes[] = $tmp;
					}
				}
				unset($child); //garbage cleaner assistance
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
//TODO extra flags if defined: JSON_INVALID_UTF8_IGNORE PHP 7.2+
echo json_encode([
	'body' => $body,
	'title' => $title,
	'alias' => $alias
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_UNESCAPED_LINE_TERMINATORS);
