<?php
# action: process page-reordering
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# This file is a component of the CMSMS ContentManager module
#     <http://dev.cmsmadesimple.org/projects/cmsmadesimple>
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage All Content') ) return;

if( isset($params['cancel']) ) {
  $this->SetMessage($this->Lang('msg_cancelled'));
  $this->Redirect($id,'defaultadmin');
}
if( isset($params['orderlist']) && $params['orderlist'] != '' ) {

    function ordercontent_get_node_rec($str,$prefix = 'page_')
    {
        $gCms = cmsms();
        $tree = $gCms->GetHierarchyManager();

        if( !is_numeric($str) && startswith($str,$prefix) ) $str = substr($str,strlen($prefix));

        $id = (int)$str;
        $tmp = $tree->find_by_tag('id',$id);
        $content = '';
        if( $tmp ) {
            $content = $tmp->getContent(false,true,true);
            if( $content ) {
                $rec = aray();
                $rec['id'] = (int)$str;
            }
        }
    }

    function ordercontent_create_flatlist($tree,$parent_id = -1)
    {
        $data = array();
        $cur_parent = null;
        $order = 1;
        foreach( $tree as &$node ) {
            if( is_string($node) ) {
                $pid = (int)substr($node,strlen('page_'));
                $cur_parent = $pid;
                $data[] = array('id'=>$pid,'parent_id'=>$parent_id,'order'=>$order++);
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
    $changelist = array();
    foreach( $orderlist as &$rec ) {
        $node = $hm->find_by_tag('id',$rec['id']);
        $content = $node->getContent(FALSE,TRUE,TRUE);
        if( $content ) {
            $rec['old_parent'] = $content->ParentId();
            $rec['old_order'] = $content->ItemOrder();

            if( $rec['old_parent'] != $rec['parent_id'] || $rec['old_order'] != $rec['order'] ) $changelist[] = $rec;
        }
    }

    if( !is_array($changelist) || count($changelist) == 0 ) {
        $this->ShowErrors($this->Lang('error_ordercontent_nothingtodo'));
    }
    else {
        $query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = ?, parent_id = ? WHERE content_id = ?';
        foreach( $changelist as $rec ) {
            $db->Execute($query,array($rec['order'],$rec['parent_id'],$rec['id']));
        }
        $contentops = $gCms->GetContentOperations();
        $contentops->SetAllHierarchyPositions();
        audit('','Content','Content pages dynamically reordered');
        $this->RedirectToAdminTab('pages');
    }
}

//custom requirements TODO
$this->AdminBottomContent('<script type="text/javascript" src="'.CMS_SCRIPTS_URL.'/jquery.mjs.nestedSortable.min.js"></script>'."\n");

$msg = json_encode($this->Lang('confirm_reorder'));
$js = <<<EOS
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

$(document).ready(function() {
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
$this->AdminBottomContent($js);

$tree = $gCms->GetHierarchyManager();
$smarty->assign('tree',$tree);
echo $this->ProcessTemplate('admin_ordercontent.tpl');
