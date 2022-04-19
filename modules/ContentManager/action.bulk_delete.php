<?php
/*
ContentManager module action: bulk delete
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use function CMSMS\log_error;
use function CMSMS\log_notice;

if (!$this->CheckContext()) {
	exit;
}

if (isset($params['cancel'])) {
	$this->SetInfo($this->Lang('msg_cancelled'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}
if (empty($params['bulk_content'])) {
	$this->SetError($this->Lang('error_missingparam'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}

$mod = $this;
$userid = get_userid(false);
$contentops = SingleItem::ContentOperations();

$can_bulk_delete = function($node) use ($mod,$userid,$contentops) : bool {
	// test whether the user may delete specified node (not its children)
	if ($mod->CheckPermission('Manage All Content')) {
		return true;
	}
	if ($mod->CheckPermission('Modify Any Page') && $mod->CheckPermission('Remove Pages')) {
		return true;
	}
	if (!$mod->CheckPermission('Remove Pages')) {
		return false;
	}

	$id = (int)$node->get_tag('id');
	if ($id < 1) {
		return false;
	}
	if ($id == $contentops->GetDefaultContent()) {
		return false;
	}
	return $contentops->CheckPageAuthorship($userid, $id);
};

$get_deletable_pages = function($node) use ($can_bulk_delete, &$get_deletable_pages) : array {
	$out = [];
	if ($can_bulk_delete($node)) {
		// we can delete the parent node.
		$out[] = $node->get_tag('id');
		if ($node->has_children()) {
			// it has children.
			$children = $node->get_children();
			foreach ($children as $child_node) {
				$tmp = $get_deletable_pages($child_node); // recurse
				$out = array_merge($out, $tmp);
			}
		}
	}
	return $out;
};

$pagelist = $params['bulk_content'];

if (isset($params['submit'])) {
	if (isset($params['confirm1']) && isset($params['confirm2']) && $params['confirm1'] == 1 && $params['confirm2'] == 1) {
		//
		// do the real work
		//
		$n = 0;
		try {
			foreach ($pagelist as $pid) {
				$content = $contentops->LoadEditableContentFromId($pid);
				if (!is_object($content)) {
					continue;
				}
				if ($content->DefaultContent()) {
					continue;
				}
				$content->Delete();
				++$n;
			}
			if ($n > 0) {
				$contentops->SetAllHierarchyPositions();
				$contentops->SetContentModified();
				log_notice('ContentManager', 'Deleted '.$n.' pages');
				$this->SetMessage($this->Lang('msg_bulk_successful'));
			}
		} catch (Throwable $t) {
			log_error('Multi-page deletion failed', $t->getMessage());
			$this->SetError($t->getMessage());
		}
		$this->Redirect($id, 'defaultadmin', $returnid);
	} else {
		$this->SetError($this->Lang('error_notconfirmed'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
}

$hm = SingleItem::App()->GetHierarchyManager();
$xlist = [];
foreach ($pagelist as $pid) {
	$node = $hm->quickfind_node_by_id($pid);
	if (!$node) {
		continue;
	}
	$tmp = $get_deletable_pages($node);
	$xlist = array_merge($xlist, $tmp);
}
$xlist = array_unique($xlist);

//
// build the confirmation display
//
$contentops->LoadChildren(-1, false, false, $xlist);
$displaydata = [];
foreach ($xlist as $pid) {
	$content = $contentops->LoadEditableContentFromId($pid);
	if (!is_object($content)) {
		continue;
	} // this should never happen either

	if ($content->DefaultContent()) {
		$this->ShowErrors($this->Lang('error_delete_defaultcontent'));
		continue;
	}

	$rec = [];
	$rec['id'] = $content->Id();
	$rec['name'] = $content->Name();
	$rec['menutext'] = $content->MenuText();
	$rec['owner'] = $content->Owner();
	$rec['alias'] = $content->Alias();
	$displaydata[] = $rec;
}

if (!$displaydata) {
	$this->SetError($this->Lang('error_delete_novalidpages'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('bulk_delete.tpl')); //,null,null,$smarty);

$tpl->assign('pagelist', $xlist)
	->assign('displaydata', $displaydata);

$tpl->display();
