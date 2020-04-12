<?php
# CMSMS ContentManager module action: process page-reordering
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\ContentOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage All Content') ) return;

if( isset($params['cancel']) ) {
  $this->SetInfo($this->Lang('msg_cancelled'));
  $this->Redirect($id,'defaultadmin');
}
if( isset($params['orderlist']) && $params['orderlist'] != '' ) {
	// this seems unused
/*    function ordercontent_get_node_rec($str,$prefix = 'page_')
    {
        $hm = cmsms()->GetHierarchyManager();

        if( !is_numeric($str) && startswith($str,$prefix) ) $str = substr($str,strlen($prefix));

        $id = (int)$str;
        $node = $hm->find_by_tag('id',$id);
        if( $node ) {
            $content = $node->getContent(false,true,true);
            if( $content ) {
                $rec = ['id' => $id]; //WHATFOR?
            }
        }
    }
*/
    function ordercontent_create_flatlist($tree,$parent_id = -1)
    {
        $data = [];
        $cur_parent = null;
        $order = 1;
        foreach( $tree as &$node ) {
            if( is_string($node) ) {
                $pid = (int)substr($node,strlen('page_'));
                $cur_parent = $pid;
                $data[] = ['id'=>$pid,'parent_id'=>$parent_id,'order'=>$order++];
            }
            else if( is_array($node) ) {
                $data = array_merge($data,ordercontent_create_flatlist($node,$cur_parent));
            }
        }
        return $data;
    }

    $orderlist = json_decode($params['orderlist'],TRUE);

    // step 1, create a flat list of the content items, and their new orders, and new parents.
    $orderlist = ordercontent_create_flatlist($orderlist);

    // step 2, merge in old orders, and old parents
    $hm = $gCms->GetHierarchyManager();
    $changelist = [];
    foreach( $orderlist as &$rec ) {
        $node = $hm->find_by_tag('id',$rec['id']);
        $content = $node->getContent(FALSE,TRUE,TRUE);
        if( $content ) {
            $rec['old_parent'] = $content->ParentId();
            $rec['old_order'] = $content->ItemOrder();

            if( $rec['old_parent'] != $rec['parent_id'] || $rec['old_order'] != $rec['order'] ) $changelist[] = $rec;
        }
    }

    if( !$changelist ) {
        $this->ShowErrors($this->Lang('error_ordercontent_nothingtodo'));
    }
    else {
        $stmt = $db->Prepare('UPDATE '.CMS_DB_PREFIX.'content SET item_order = ?, parent_id = ? WHERE content_id = ?');
        foreach( $changelist as $rec ) {
            $db->Execute($stmt,[$rec['order'],$rec['parent_id'],$rec['id']]);
        }
        $stmt->close();
        ContentOperations::get_instance()->SetAllHierarchyPositions();
        audit('','Content','Content pages dynamically reordered');
        $this->RedirectToAdminTab('pages');
    }
}

//custom requirements TODO
$base_url = cms_path_to_url(CMS_ASSETS_PATH);
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
    if (subtree.size() > 0) {
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
$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_ordercontent.tpl'),null,null,$smarty);
$tpl->assign('tree',$hm);

$tpl->display();
return false;
