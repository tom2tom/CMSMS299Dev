<?php
/*
CMSMS ContentManager module action: process page-reordering
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
use function CMSMS\log_notice;

if (!$this->CheckContext()) {
	exit;
}

if (!$this->CheckPermission('Manage All Content')) {
	exit;
}

if (isset($params['cancel'])) {
	$this->SetInfo($this->Lang('msg_cancelled'));
	$this->Redirect($id, 'defaultadmin');
}
if (isset($params['orderlist']) && $params['orderlist'] != '') {
/* this seems unused
	function ordercontent_get_node_rec($str,$prefix = 'page_')
	{
		if( !is_numeric($str) && startswith($str,$prefix) ) $str = substr($str,strlen($prefix));
		$pid = (int)$str;
		$content = $contentops->LoadEditableContentFromId($pid);
		if( $content ) {
			$rec = ['id' => $pid]; //WHAT FOR?
		}
	}
*/
	function ordercontent_create_flatlist($tree, $parent_id = -1)
	{
		$data = [];
		$cur_parent = null;
		$order = 1;
		foreach ($tree as &$node) {
			if (is_string($node)) {
				$pid = (int)substr($node, strlen('page_'));
				$cur_parent = $pid;
				$data[] = ['id' => $pid, 'parent_id' => $parent_id, 'order' => $order++];
			} elseif (is_array($node)) {
				$data = array_merge($data, ordercontent_create_flatlist($node, $cur_parent));
			}
		}
		return $data;
	}

	$orderlist = json_decode($params['orderlist'], true);

	// step 1, create a flat list of the content items, and their new orders, and new parents.
	$orderlist = ordercontent_create_flatlist($orderlist);

	// step 2, merge in old orders, and old parents
	$hm = $gCms->GetHierarchyManager();
	$contentops = SingleItem::ContentOperations();
	$changelist = [];
	foreach ($orderlist as &$rec) {
		$content = $contentops->LoadEditableContentFromId($rec['id']);
		if ($content) {
			$rec['old_parent'] = $content->ParentId();
			$rec['old_order'] = $content->ItemOrder();
			if ($rec['old_parent'] != $rec['parent_id'] || $rec['old_order'] != $rec['order']) {
				$changelist[] = $rec;
			}
		}
	}

	if (!$changelist) {
		$this->ShowErrors($this->Lang('error_ordercontent_nothingtodo'));
	} else {
		$stmt = $db->prepare('UPDATE '.CMS_DB_PREFIX.'content SET item_order = ?, parent_id = ? WHERE content_id = ?');
		foreach ($changelist as $rec) {
			$db->execute($stmt, [$rec['order'], $rec['parent_id'], $rec['id']]);
		}
		$stmt->close();
		$contentops->SetAllHierarchyPositions();
		log_notice('ContentManager', 'Content pages dynamically reordered');
		$this->RedirectToAdminTab('pages');
	}
}

//custom requirements TODO
$base_url = CMS_ASSETS_URL;
$msg = json_encode($this->Lang('confirm_reorder'));

$js = <<<EOS
<script type="text/javascript" src="{$base_url}/js/jquery.mjs.nestedSortable.min.js"></script>
<script type="text/javascript">
//<![CDATA[
function parseTree(ul) {
  var tags = [];
  ul.children('li').each(function() {
    var subtree = $(this).children('ul');
    tags.push($(this).attr('id'));
    if (subtree.length > 0) {
      tags.push(parseTree(subtree));
    }
  });
  return tags;
}

$(function() {
  $('#btn_submit').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm($msg).done(function() {
      var tree = JSON.stringify(parseTree($('#masterlist'))); //IE8+
      $('#orderlist').val(tree);
      form.submit();
    });
  });

  $('.haschildren').on('click', function(ev) {
    ev.preventDefault();
    var list = $(this).closest('div.label').next('ul');
    if ($(this).hasClass('expanded')) {
      // currently expanded, now collapse
      list.hide();
      $(this).removeClass('expanded').addClass('collapsed').text('+');
    } else {
      // currently collapsed, now expand
      list.show();
      $(this).removeClass('collapsed').addClass('expanded').text('-');
    }
  });

  $('ul.sortable').nestedSortable({
    disableNesting: 'no-nest',
    forcePlaceholderSize: true,
    handle: 'div',
    items: 'li',
    opacity: 0.6,
    placeholder: 'placeholder',
    tabSize: 20,
    tolerance: 'pointer',
    listType: 'ul',
    toleranceElement: '> div'
  });
});
//]]>
</script>
EOS;
add_page_foottext($js);

$hm = $gCms->GetHierarchyManager(); //TODO direct-use by Smarty OK?
$tpl = $smarty->createTemplate($this->GetTemplateResource('ordercontent.tpl')); //,null,null,$smarty);
$tpl->assign('tree', $hm);

$tpl->display();
